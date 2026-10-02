<?php

use App\Enums\GatewayState;
use App\Enums\LicenceTier;
use App\Enums\PayoutStatus;
use App\Models\LedgerTransaction;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\Staff;
use App\Models\StaffActivity;
use App\Models\User;
use App\Notifications\PayoutNotSentNotification;
use App\Notifications\PayoutPaidNotification;
use App\Notifications\PayoutRequestedNotification;
use App\Services\Ledger\LedgerService;
use App\Services\Marketplace\LicenceService;
use App\Services\Payments\EarningsService;
use App\Services\Payments\PaymentService;
use App\Services\Payments\PayoutService;
use App\Support\Ledger\LedgerLine;
use App\Support\Payments\PaymentOutcome;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

/*
 * Earnings and withdrawals: the sale's share leaving the hold, a member asking to withdraw, staff approving or turning it down, the money
 * being sent (on the fake gateway: a phone ending 0 is refused, 1 fails, anything else is paid), and every step in the ledger.
 */

beforeEach(function () {
    config(['marketplace.purchases_enabled' => true, 'payments.fake.delay_seconds' => 0]);
    Notification::fake();

    $this->ledger = app(LedgerService::class);
    $this->seller = User::factory()->create(['name' => 'Seller Person']);
    SellerProfile::factory()->approved()->create(['user_id' => $this->seller->id, 'display_name' => 'Oak Studio']);
    $this->product = Product::factory()->published()->create(['user_id' => $this->seller->id, 'title' => 'Oak armchair', 'price_minor' => 120000, 'extended_price_minor' => 480000]);
});

/** A paid sale of the seller's model to a new buyer (KES 1,200: the seller's share is KES 1,020, held for 7 days). */
function earnedSale(?Product $product = null): Payment
{
    $product ??= test()->product;
    $buyer = User::factory()->create();
    $payments = app(PaymentService::class);

    return $payments->refresh($payments->start($buyer, $product, LicenceTier::Standard, '0712345675'));
}

/** Give the seller money they can withdraw, as if sales had come in and their holds had ended (the platform's gateway holds it). */
function withBalance(User $seller, int $availableMinor): void
{
    $ledger = app(LedgerService::class);
    $ledger->post('release', 'test:balance:'.$seller->id.':'.random_int(1, 999999), [
        LedgerLine::debit($ledger->platformAccount('gateway'), $availableMinor),
        LedgerLine::credit($ledger->userAccount($seller, 'available'), $availableMinor),
    ], 'Test balance');
}

function ledgerBalance(string $key): int
{
    return app(LedgerService::class)->platformAccount($key)->balanceMinor();
}

/** ---------------------------------------------------------------- the hold */
it('keeps a sale\'s share in the hold until the hold period ends, then makes it available', function () {
    $payment = earnedSale();
    $service = app(EarningsService::class);

    expect($this->ledger->balances($this->seller))->toBe(['pending' => 102000, 'available' => 0])->and($service->releaseDue())->toBe(0);

    $this->travel(6)->days();
    expect($service->releaseDue())->toBe(0)->and($this->ledger->balances($this->seller)['available'])->toBe(0);

    $this->travel(2)->days();
    expect($service->releaseDue())->toBe(1);

    expect($this->ledger->balances($this->seller))->toBe(['pending' => 0, 'available' => 102000])->and($payment->fresh()->released_at)->not->toBeNull()->and($this->ledger->trialBalance()['balanced'])->toBeTrue();
});

it('releases each sale once, however often the job runs', function () {
    earnedSale();
    $this->travel(8)->days();

    $this->artisan('earnings:release')->expectsOutput('Released 1 sales.')->assertSuccessful();
    $this->artisan('earnings:release')->expectsOutput('Released 0 sales.')->assertSuccessful();

    expect(LedgerTransaction::where('type', 'release')->count())->toBe(1)->and($this->ledger->balances($this->seller)['available'])->toBe(102000);
});

it('releases only the sales whose hold has ended', function () {
    earnedSale();
    $this->travel(5)->days();
    earnedSale(); // sold five days later: its hold ends at day 12
    $this->travel(3)->days(); // day 8: only the first is due

    app(EarningsService::class)->releaseDue();

    expect($this->ledger->balances($this->seller))->toBe(['pending' => 102000, 'available' => 102000]);
});

it('does not release the share of a refunded sale', function () {
    $payment = earnedSale();
    app(LicenceService::class)->revoke($payment->purchase->licence, 'Refunded: the file was broken.');
    $this->travel(8)->days();

    expect(app(EarningsService::class)->releaseDue())->toBe(0)->and($this->ledger->balances($this->seller)['available'])->toBe(0);
});

it('uses the hold days that applied when the sale was made', function () {
    setting('fees.sale_hold_days', 0);
    $payment = earnedSale();

    expect($payment->hold_days)->toBe(0)->and(app(EarningsService::class)->releaseDue())->toBe(1)->and($this->ledger->balances($this->seller)['available'])->toBe(102000);
});

it('works out a seller\'s figures', function () {
    earnedSale();
    earnedSale();
    $this->travel(8)->days();
    app(EarningsService::class)->releaseDue();
    $payouts = app(PayoutService::class);
    $first = $payouts->request($this->seller, 100000, '0712345675');
    $payouts->approve($first, staffWith('Super admin'));
    $payouts->refresh($first->fresh());
    $payouts->request($this->seller, 50000, '0712345675');

    expect(app(EarningsService::class)->summary($this->seller))->toBe(['pending' => 0, 'available' => 204000 - 100000 - 50000, 'earned' => 204000, 'withdrawn' => 100000, 'in_progress' => 50000]);
});

/** ---------------------------------------------------------------- asking to withdraw */
it('takes a withdrawal out of the available balance at once, with the fee shown and kept apart', function () {
    withBalance($this->seller, 300000);
    $approver = staffWith('Finance');
    $bystander = staffWith('Support');

    $payout = app(PayoutService::class)->request($this->seller, 200000, '0712 345 678');

    expect($payout)->toBeInstanceOf(Payout::class)->and($payout->status)->toBe(PayoutStatus::Requested)->and($payout->amount_minor)->toBe(200000)->and($payout->fee_minor)->toBe(3000)->and($payout->net_minor)->toBe(197000)
        ->and($payout->msisdn)->toBe('254712345678')->and($payout->reference)->toHaveLength(12)->and($payout->gateway)->toBeNull();
    expect($this->ledger->balances($this->seller)['available'])->toBe(100000)->and(ledgerBalance('payout_clearing'))->toBe(200000)->and($this->ledger->trialBalance()['balanced'])->toBeTrue();

    Notification::assertSentTo($approver, PayoutRequestedNotification::class, fn ($n) => $n->payout->is($payout));
    Notification::assertNotSentTo($bystander, PayoutRequestedNotification::class);
});

it('refuses a withdrawal that is not allowed, with the reason, and changes nothing', function () {
    withBalance($this->seller, 300000);
    $request = fn (int $amount, string $phone = '0712345678') => app(PayoutService::class)->request($this->seller, $amount, $phone);

    expect($request(200000, 'hello'))->toBe('Enter a valid Safaricom number, for example 0712 345 678.')
        ->and($request(200050))->toBe('Withdraw a whole number of shillings.')
        ->and($request(0))->toBe('Withdraw a whole number of shillings.')
        ->and($request(40000))->toBe('The smallest withdrawal is Ksh500.')
        ->and($request(400000))->toBe('You do not have that much available to withdraw.')
        ->and($request(20000000))->toBe('M-Pesa sends up to KES 150,000 at a time. Withdraw a smaller amount.');

    setting('fees.min_payout', 0);
    setting('fees.payout_fee', 600);
    expect($request(50000))->toBe('That is not enough to cover the withdrawal fee of Ksh600.');

    expect(Payout::count())->toBe(0)->and($this->ledger->balances($this->seller)['available'])->toBe(300000)->and(ledgerBalance('payout_clearing'))->toBe(0);
});

it('caps a single transfer at what M-Pesa can send', function () {
    config(['payments.max_kes' => 1000]);
    withBalance($this->seller, 500000);

    expect(app(PayoutService::class)->request($this->seller, 150000, '0712345678'))->toBe('M-Pesa sends up to KES 1,000 at a time. Withdraw a smaller amount.');
});

it('never lets pending earnings, or the same money twice, be withdrawn', function () {
    earnedSale(); // KES 1,020 is in the hold, none is available
    $payouts = app(PayoutService::class);

    expect($payouts->request($this->seller, 100000, '0712345678'))->toBe('You do not have that much available to withdraw.');

    withBalance($this->seller, 150000);
    expect($payouts->request($this->seller, 100000, '0712345678'))->toBeInstanceOf(Payout::class)
        ->and($payouts->request($this->seller, 50000, '0712345678'))->toBe('You already have a withdrawal in progress. Wait for it to finish, or cancel it.');
});

it('follows the withdrawal fee and minimum a Super admin sets', function () {
    setting('fees.min_payout', 1000);
    setting('fees.payout_fee', 50);
    withBalance($this->seller, 300000);

    $payouts = app(PayoutService::class);
    expect($payouts->request($this->seller, 90000, '0712345678'))->toBe('The smallest withdrawal is Ksh1,000.');

    $payout = $payouts->request($this->seller, 100000, '0712345678');
    expect($payout->fee_minor)->toBe(5000)->and($payout->net_minor)->toBe(95000);
});

/** ---------------------------------------------------------------- cancelling and turning down */
it('lets a member take back a request nobody has approved, and gives them the money back', function () {
    withBalance($this->seller, 300000);
    $payouts = app(PayoutService::class);
    $payout = $payouts->request($this->seller, 200000, '0712345678');

    expect($payouts->cancel($payout, User::factory()->create()))->toBe('This is not your withdrawal.');

    $cancelled = $payouts->cancel($payout, $this->seller);

    expect($cancelled->status)->toBe(PayoutStatus::Cancelled)->and($this->ledger->balances($this->seller)['available'])->toBe(300000)->and(ledgerBalance('payout_clearing'))->toBe(0)
        ->and($payouts->cancel($payout, $this->seller))->toBe('It has already been approved, so it can no longer be cancelled.');
    expect(LedgerTransaction::where('type', 'payout_returned')->count())->toBe(1);
});

it('lets staff turn a request down with a reason, tells the member and gives them the money back', function () {
    withBalance($this->seller, 300000);
    $staff = staffWith('Finance');
    $payouts = app(PayoutService::class);
    $payout = $payouts->request($this->seller, 200000, '0712345678');

    $rejected = $payouts->reject($payout, $staff, 'The number does not match your profile.');

    expect($rejected->status)->toBe(PayoutStatus::Rejected)->and($rejected->failure_reason)->toBe('The number does not match your profile.')->and($rejected->approved_by)->toBe($staff->id)
        ->and($this->ledger->balances($this->seller)['available'])->toBe(300000)->and(ledgerBalance('payout_clearing'))->toBe(0);
    Notification::assertSentTo($this->seller, PayoutNotSentNotification::class, fn ($n) => $n->payout->is($rejected));
    expect(StaffActivity::where('action', 'payout.rejected')->where('staff_id', $staff->id)->exists())->toBeTrue()
        ->and($payouts->reject($payout, $staff, 'Again, please'))->toBe('This withdrawal is no longer waiting for approval.');
});

/** ---------------------------------------------------------------- approving and sending */
it('sends an approved withdrawal, and records it in the ledger and to the member once it lands', function () {
    withBalance($this->seller, 300000);
    $staff = staffWith('Finance');
    $payouts = app(PayoutService::class);
    $payout = $payouts->request($this->seller, 200000, '0712345675');

    $sending = $payouts->approve($payout, $staff);

    expect($sending->status)->toBe(PayoutStatus::Processing)->and($sending->gateway)->toBe('fake')->and($sending->gateway_reference)->toStartWith('AG_')->and($sending->approved_by)->toBe($staff->id)->and($sending->approved_at)->not->toBeNull();
    expect(StaffActivity::where('action', 'payout.approved')->where('staff_id', $staff->id)->exists())->toBeTrue();

    $paid = $payouts->refresh($sending);

    expect($paid->status)->toBe(PayoutStatus::Paid)->and($paid->receipt)->toStartWith('FAKEPO')->and($paid->completed_at)->not->toBeNull();
    // KES 2,000 left the seller; KES 1,970 left the gateway; the KES 30 fee is the platform's
    expect(ledgerBalance('payout_clearing'))->toBe(0)->and(ledgerBalance('payout_fees'))->toBe(3000)->and($this->ledger->balances($this->seller)['available'])->toBe(100000)
        ->and(ledgerBalance('gateway'))->toBe(300000 - 197000)->and($this->ledger->trialBalance()['balanced'])->toBeTrue();
    Notification::assertSentTo($this->seller, PayoutPaidNotification::class, fn ($n) => $n->payout->is($paid));
});

it('records a paid withdrawal once, however many times it is told', function () {
    withBalance($this->seller, 300000);
    $payouts = app(PayoutService::class);
    $payout = $payouts->approve($payouts->request($this->seller, 200000, '0712345675'), staffWith('Finance'));
    $paid = $payouts->refresh($payout);

    $payouts->refresh($paid);
    $payouts->applyOutcome($paid, new PaymentOutcome($paid->gateway_reference, GatewayState::Succeeded, 'AGAIN', 197000));
    $payouts->applyOutcome($paid, new PaymentOutcome($paid->gateway_reference, GatewayState::Failed, reason: 'Late failure'));

    expect(LedgerTransaction::where('type', 'payout_paid')->count())->toBe(1)->and($paid->fresh()->status)->toBe(PayoutStatus::Paid)->and($paid->fresh()->receipt)->not->toBe('AGAIN')
        ->and(ledgerBalance('payout_fees'))->toBe(3000);
    Notification::assertSentToTimes($this->seller, PayoutPaidNotification::class, 1);
});

it('gives the money back when the transfer fails after approval', function () {
    withBalance($this->seller, 300000);
    $payouts = app(PayoutService::class);
    $sending = $payouts->approve($payouts->request($this->seller, 200000, '0712345671'), staffWith('Finance'));

    $failed = $payouts->refresh($sending);

    expect($failed->status)->toBe(PayoutStatus::Failed)->and($failed->failure_reason)->toBe('The transfer failed.')->and($this->ledger->balances($this->seller)['available'])->toBe(300000)
        ->and(ledgerBalance('payout_clearing'))->toBe(0)->and(ledgerBalance('payout_fees'))->toBe(0)->and($this->ledger->trialBalance()['balanced'])->toBeTrue();
    Notification::assertSentTo($this->seller, PayoutNotSentNotification::class);
    Notification::assertNotSentTo($this->seller, PayoutPaidNotification::class);
});

it('gives the money back at once when the gateway refuses to start the transfer', function () {
    withBalance($this->seller, 300000);
    $payouts = app(PayoutService::class);

    $result = $payouts->approve($payouts->request($this->seller, 200000, '0712345670'), staffWith('Finance'));

    expect($result->status)->toBe(PayoutStatus::Failed)->and($result->failure_reason)->toBe('The transfer could not be started.')->and($result->gateway_reference)->toBeNull()
        ->and($this->ledger->balances($this->seller)['available'])->toBe(300000)->and(ledgerBalance('payout_clearing'))->toBe(0);
    Notification::assertSentToTimes($this->seller, PayoutNotSentNotification::class, 1);
});

it('approves a withdrawal only once, and only while it is waiting', function () {
    withBalance($this->seller, 300000);
    $payouts = app(PayoutService::class);
    $payout = $payouts->request($this->seller, 200000, '0712345675');
    $staff = staffWith('Finance');

    expect($payouts->approve($payout, $staff))->toBeInstanceOf(Payout::class)->and($payouts->approve($payout, $staff))->toBe('This withdrawal is no longer waiting for approval.')
        ->and($payouts->reject($payout, $staff, 'Too late now'))->toBe('This withdrawal is no longer waiting for approval.')->and($payouts->cancel($payout, $this->seller))->toBeString();

    expect(Payout::sole()->status)->toBe(PayoutStatus::Processing);
});

it('keeps a withdrawal being sent until the gateway answers, and checks on it by itself', function () {
    config(['payments.fake.delay_seconds' => 3600]);
    withBalance($this->seller, 300000);
    $payouts = app(PayoutService::class);
    $sending = $payouts->approve($payouts->request($this->seller, 200000, '0712345675'), staffWith('Finance'));

    expect($payouts->refresh($sending)->status)->toBe(PayoutStatus::Processing)->and(ledgerBalance('payout_clearing'))->toBe(200000);

    config(['payments.fake.delay_seconds' => 0]);
    $this->travel(2)->hours();
    Cache::flush(); // the gateway's record of it is gone, so it reports the transfer as unknown

    $this->artisan('payouts:check')->expectsOutput('Checked 1 withdrawals.')->assertSuccessful();
    expect($sending->fresh()->status)->toBe(PayoutStatus::Failed)->and($this->ledger->balances($this->seller)['available'])->toBe(300000);
});

it('settles a withdrawal from the gateway\'s callback, and acknowledges anything else', function () {
    config(['payments.fake.delay_seconds' => 3600]);
    withBalance($this->seller, 300000);
    $payouts = app(PayoutService::class);
    $sending = $payouts->approve($payouts->request($this->seller, 200000, '0712345675'), staffWith('Finance'));

    $this->postJson(route('webhooks.payouts', 'fake'), ['gateway_reference' => 'AG_UNKNOWN', 'state' => 'succeeded'])->assertOk()->assertExactJson(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    $this->postJson(route('webhooks.payouts', 'fake'), ['nonsense' => 1])->assertOk();
    $this->postJson(route('webhooks.payouts', 'mpesa'), ['gateway_reference' => 'x', 'state' => 'succeeded'])->assertNotFound();
    expect($sending->fresh()->status)->toBe(PayoutStatus::Processing);

    $this->postJson(route('webhooks.payouts', 'fake'), ['gateway_reference' => $sending->gateway_reference, 'state' => 'succeeded', 'receipt' => 'CBPO000001', 'amount_minor' => 197000])->assertOk();
    $this->postJson(route('webhooks.payouts', 'fake'), ['gateway_reference' => $sending->gateway_reference, 'state' => 'succeeded', 'receipt' => 'CBPO000001'])->assertOk();

    expect($sending->fresh()->status)->toBe(PayoutStatus::Paid)->and($sending->fresh()->receipt)->toBe('CBPO000001')->and(LedgerTransaction::where('type', 'payout_paid')->count())->toBe(1);
});

it('keeps the books balanced through a sale, its release, a withdrawal and a payout', function () {
    earnedSale();
    $this->travel(8)->days();
    app(EarningsService::class)->releaseDue();
    $payouts = app(PayoutService::class);
    $paid = $payouts->refresh($payouts->approve($payouts->request($this->seller, 100000, '0712345675'), staffWith('Finance')));

    expect($paid->status)->toBe(PayoutStatus::Paid)->and($this->ledger->trialBalance()['balanced'])->toBeTrue()
        // The gateway holds what was paid in less what was sent; the platform has its commission and the fee; the seller is owed the rest
        ->and(ledgerBalance('gateway'))->toBe(120000 - 97000)->and(ledgerBalance('revenue') + ledgerBalance('payout_fees') + $this->ledger->balances($this->seller)['available'])->toBe(ledgerBalance('gateway'));
});

/** ---------------------------------------------------------------- the earnings page */
it('shows a seller their balances, sales (with each hold) and withdrawals', function () {
    $payment = earnedSale();
    $this->actingAs($this->seller);

    $this->get(route('earnings.index'))->assertOk()->assertSee('Earnings')->assertSee('Oak armchair')->assertSee('Standard licence')->assertSee('Ksh1,020.00')->assertSee('In hold until')
        ->assertSee('You can withdraw once you have at least Ksh500 available. You have Ksh0.')->assertDontSee('Ask to withdraw');

    $this->travel(8)->days();
    app(EarningsService::class)->releaseDue();
    $this->withSession(['session_rules.web.last' => now()->timestamp]); // the seller was active all along
    $this->get(route('earnings.index'))->assertSee('Available')->assertSee('Ask to withdraw')->assertSee('Withdrawal fee')->assertSee('You receive')->assertDontSee('In hold until');
});

it('lets a seller ask to withdraw from the page, and shows it while it is in progress', function () {
    withBalance($this->seller, 300000);
    $this->actingAs($this->seller);

    $this->post(route('earnings.withdraw'), ['amount' => '2000', 'phone' => '0712 345 675'])->assertRedirect()->assertSessionHas('success');

    $payout = Payout::sole();
    expect($payout->amount_minor)->toBe(200000)->and($payout->status)->toBe(PayoutStatus::Requested);
    $this->get(route('earnings.index'))->assertSee('A withdrawal is in progress')->assertSee('Waiting for approval')->assertDontSee('Ask to withdraw');

    $this->delete(route('earnings.cancel', $payout))->assertRedirect();
    expect($payout->fresh()->status)->toBe(PayoutStatus::Cancelled);
    $this->get(route('earnings.index'))->assertSee('Cancelled')->assertSee('Ask to withdraw');
});

it('says why a withdrawal was refused, and checks the form', function () {
    withBalance($this->seller, 300000);
    $this->actingAs($this->seller);

    $this->post(route('earnings.withdraw'), ['amount' => '99999', 'phone' => '0712345675'])->assertSessionHas('error');
    $this->post(route('earnings.withdraw'), ['amount' => 'abc', 'phone' => '0712345675'])->assertSessionHasErrors('amount');
    $this->post(route('earnings.withdraw'), ['amount' => '2000', 'phone' => 'hello'])->assertSessionHasErrors('phone');
    $this->post(route('earnings.withdraw'), ['phone' => '0712345675'])->assertSessionHasErrors('amount');

    expect(Payout::count())->toBe(0);
});

it('keeps earnings and withdrawals private to their owner, and behind the sign-in', function () {
    withBalance($this->seller, 300000);
    $payout = app(PayoutService::class)->request($this->seller, 200000, '0712345675');
    $other = User::factory()->create();

    $this->get(route('earnings.index'))->assertRedirect(route('login'));
    $this->post(route('earnings.withdraw'), ['amount' => '1000', 'phone' => '0712345675'])->assertRedirect(route('login'));

    $this->actingAs($other)->get(route('earnings.index'))->assertOk()->assertDontSee($payout->reference)->assertDontSee('Ksh2,000');
    $this->delete(route('earnings.cancel', $payout))->assertRedirect();
    expect($payout->fresh()->status)->toBe(PayoutStatus::Requested);
    $this->post(route('earnings.withdraw'), ['amount' => '1000', 'phone' => '0712345675'])->assertSessionHas('error');
});

it('puts Earnings in a seller\'s sidebar and not a buyer\'s', function () {
    $this->actingAs($this->seller)->get(route('dashboard'))->assertOk()->assertSee(route('earnings.index'), false);
    $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertOk()->assertDontSee(route('earnings.index'), false);
});

it('shows the withdrawal settings to Super admins on the fees page', function () {
    $this->actingAs(staffWith('Super admin'), 'staff')->get(route('admin.settings.fees'))->assertOk()->assertSee('Withdrawals')->assertSee('Smallest withdrawal')->assertSee('Withdrawal fee');
});
