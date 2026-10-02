<?php

use App\Enums\GatewayState;
use App\Enums\LicenceTier;
use App\Enums\PaymentStatus;
use App\Enums\PayoutStatus;
use App\Models\IssuedLicence;
use App\Models\LedgerAccount;
use App\Models\LedgerTransaction;
use App\Models\LicenceDownload;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\StaffActivity;
use App\Models\User;
use App\Notifications\PaymentRefundedNotification;
use App\Notifications\SaleRefundedNotification;
use App\Services\Ledger\LedgerService;
use App\Services\Marketplace\LicenceService;
use App\Services\Payments\EarningsService;
use App\Services\Payments\PaymentService;
use App\Services\Payments\PayoutService;
use App\Services\Payments\RefundService;
use App\Support\Ledger\LedgerLine;
use App\Support\Navigation\StaffMenu;
use App\Support\Payments\PaymentOutcome;
use Illuminate\Support\Facades\Notification;

/*
 * Staff and money: refunding a sale or returning money that arrived unmatched, the payments list and page, and the read-only ledger.
 */

beforeEach(function () {
    config(['marketplace.purchases_enabled' => true, 'payments.fake.delay_seconds' => 0]);
    Notification::fake();

    $this->ledger = app(LedgerService::class);
    $this->buyer = User::factory()->create(['name' => 'Buyer Person']);
    $this->seller = User::factory()->create(['name' => 'Seller Person']);
    SellerProfile::factory()->approved()->create(['user_id' => $this->seller->id, 'display_name' => 'Oak Studio']);
    $this->product = Product::factory()->published()->create(['user_id' => $this->seller->id, 'title' => 'Oak armchair', 'price_minor' => 120000, 'extended_price_minor' => 480000]);
    $this->finance = staffWith('Finance');
});

/** A paid sale of the model to the buyer: KES 1,200, the seller's share KES 1,020, commission KES 180. */
function soldToBuyer(?User $buyer = null): Payment
{
    $payments = app(PaymentService::class);

    return $payments->refresh($payments->start($buyer ?? test()->buyer, test()->product, LicenceTier::Standard, '0712345675'));
}

/** A payment that arrived in the wrong amount and was parked for review. */
function parkedPayment(int $paid = 100000, ?User $buyer = null): Payment
{
    $payments = app(PaymentService::class);
    $payment = $payments->start($buyer ?? User::factory()->create(['name' => 'Odd Payer']), test()->product, LicenceTier::Standard, '0712345675');

    return $payments->applyOutcome($payment, new PaymentOutcome($payment->gateway_reference, GatewayState::Succeeded, 'ODD0000001', $paid));
}

function balanceOf(string $key): int
{
    return app(LedgerService::class)->platformAccount($key)->balanceMinor();
}

/** ---------------------------------------------------------------- refunding a sale */
it('refunds a sale in the hold: the licence ends, the seller\'s pending share and the commission are taken back, both sides are told', function () {
    $payment = soldToBuyer();
    $licence = IssuedLicence::sole();

    $refunded = app(RefundService::class)->refund($payment, $this->finance, '  The file would not open in Blender.  ');

    expect($refunded->status)->toBe(PaymentStatus::Refunded)->and($refunded->refund_reason)->toBe('The file would not open in Blender.')->and($refunded->refunded_by)->toBe($this->finance->id)->and($refunded->refunded_at)->not->toBeNull();
    expect($licence->fresh()->isActive())->toBeFalse()->and($licence->fresh()->revoked_reason)->toBe('Refunded: The file would not open in Blender.')->and($payment->purchase->fresh()->status)->toBe('refunded');

    expect(balanceOf('gateway'))->toBe(0)->and(balanceOf('revenue'))->toBe(0)->and($this->ledger->balances($this->seller))->toBe(['pending' => 0, 'available' => 0])->and($this->ledger->trialBalance()['balanced'])->toBeTrue();
    expect(LedgerTransaction::where('type', 'refund')->sole()->idempotency_key)->toBe("refund:payment:{$payment->id}");

    Notification::assertSentTo($this->buyer, PaymentRefundedNotification::class, fn ($n) => $n->payment->is($refunded));
    Notification::assertSentTo($this->seller, SaleRefundedNotification::class);
    expect(StaffActivity::where('action', 'payment.refunded')->where('staff_id', $this->finance->id)->exists())->toBeTrue();
});

it('never releases the share of a sale that was refunded, and lets the buyer buy again', function () {
    $payment = soldToBuyer();
    app(RefundService::class)->refund($payment, $this->finance, 'Not as described.');

    $this->travel(8)->days();
    expect(app(EarningsService::class)->releaseDue())->toBe(0)->and($this->ledger->balances($this->seller)['available'])->toBe(0);

    expect(app(LicenceService::class)->grant($this->buyer, $this->product, LicenceTier::Standard))->toBeInstanceOf(IssuedLicence::class);
});

it('refunds a sale after the hold from what the seller still has available', function () {
    $payment = soldToBuyer();
    $this->travel(8)->days();
    app(EarningsService::class)->releaseDue();
    expect($this->ledger->balances($this->seller)['available'])->toBe(102000);

    $refunded = app(RefundService::class)->refund($payment, $this->finance, 'The model is not as described.');

    expect($refunded->status)->toBe(PaymentStatus::Refunded)->and($this->ledger->balances($this->seller))->toBe(['pending' => 0, 'available' => 0])->and(balanceOf('gateway'))->toBe(0)->and($this->ledger->trialBalance()['balanced'])->toBeTrue();
});

it('will not refund a sale whose money the seller has already withdrawn, and changes nothing', function () {
    $payment = soldToBuyer();
    $this->travel(8)->days();
    app(EarningsService::class)->releaseDue();
    $payouts = app(PayoutService::class);
    $payouts->refresh($payouts->approve($payouts->request($this->seller, 100000, '0712345675'), staffWith('Super admin')));
    expect($this->ledger->balances($this->seller)['available'])->toBe(2000);

    $result = app(RefundService::class)->refund($payment, $this->finance, 'Too late to refund.');

    expect($result)->toBe('The seller has already withdrawn this money, so it cannot be taken back from their earnings here.')
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Succeeded)->and(IssuedLicence::sole()->isActive())->toBeTrue()->and(LedgerTransaction::where('type', 'refund')->count())->toBe(0)
        ->and($this->ledger->balances($this->seller)['available'])->toBe(2000);
    Notification::assertNotSentTo($this->buyer, PaymentRefundedNotification::class);
});

it('refunds a sale once, and only a sale or a payment that needs review', function () {
    $service = app(RefundService::class);
    $sale = soldToBuyer();

    expect($service->refund($sale, $this->finance, 'First refund.'))->toBeInstanceOf(Payment::class)
        ->and($service->refund($sale, $this->finance, 'Second refund.'))->toBe('This payment has already been refunded.')
        ->and(LedgerTransaction::where('type', 'refund')->count())->toBe(1);

    foreach ([PaymentStatus::Pending, PaymentStatus::Failed, PaymentStatus::Cancelled, PaymentStatus::Expired] as $status) {
        $payment = Payment::create(['reference' => Payment::newReference(), 'user_id' => $this->buyer->id, 'seller_id' => $this->seller->id, 'product_id' => $this->product->id, 'tier' => 'standard', 'amount_minor' => 120000, 'currency' => 'KES', 'msisdn' => '254712345675', 'status' => $status, 'gateway' => 'fake', 'commission_rate' => 0.15, 'commission_minor' => 18000, 'seller_share_minor' => 102000, 'hold_days' => 7, 'expires_at' => now()]);
        expect($service->refund($payment, $this->finance, 'Not paid at all.'))->toBe('Nothing was paid, so there is nothing to refund.');
    }
});

it('refunds a sale with no commission line when the commission was zero', function () {
    SellerProfile::where('user_id', $this->seller->id)->update(['commission_percent' => 0]);
    $payment = soldToBuyer();

    app(RefundService::class)->refund($payment, $this->finance, 'Refund with no commission.');

    expect(balanceOf('gateway'))->toBe(0)->and($this->ledger->balances($this->seller)['pending'])->toBe(0)->and($this->ledger->trialBalance()['balanced'])->toBeTrue();
});

/** ---------------------------------------------------------------- money that arrived unmatched */
it('returns money that arrived in the wrong amount, without a licence and without telling the seller', function () {
    $payment = parkedPayment(100000, $this->buyer);
    expect($payment->status)->toBe(PaymentStatus::Review)->and($payment->received_minor)->toBe(100000)->and(balanceOf('suspense'))->toBe(100000);

    $refunded = app(RefundService::class)->refund($payment, $this->finance, 'You paid the wrong amount.');

    expect($refunded->status)->toBe(PaymentStatus::Refunded)->and(balanceOf('suspense'))->toBe(0)->and(balanceOf('gateway'))->toBe(0)->and($this->ledger->trialBalance()['balanced'])->toBeTrue()
        ->and(LedgerTransaction::where('type', 'refund_unallocated')->count())->toBe(1)->and(IssuedLicence::count())->toBe(0);
    Notification::assertSentTo($this->buyer, PaymentRefundedNotification::class);
    Notification::assertNotSentTo($this->seller, SaleRefundedNotification::class);
});

it('records what actually arrived on every payment', function () {
    $sale = soldToBuyer();
    $parked = parkedPayment(90000);

    expect($sale->received_minor)->toBe(120000)->and($parked->received_minor)->toBe(90000);
});

/** ---------------------------------------------------------------- who may see and do what */
it('keeps payments and the ledger to those who may view them, and refunds to those who may refund', function () {
    $payment = soldToBuyer();
    $auditor = staffWith('Auditor');

    foreach (['Platform manager', 'Support', 'Marketplace moderator', 'Dispute manager'] as $role) {
        $staff = staffWith($role);
        $this->actingAs($staff, 'staff')->get(route('admin.payments.index'))->assertForbidden();
        $this->get(route('admin.ledger.index'))->assertForbidden();
    }

    foreach ([$this->finance, $auditor] as $viewer) {
        $this->actingAs($viewer, 'staff')->get(route('admin.payments.index'))->assertOk();
        $this->get(route('admin.payments.show', $payment))->assertOk();
        $this->get(route('admin.ledger.index'))->assertOk();
    }

    $this->actingAs($auditor, 'staff')->post(route('admin.payments.refund', $payment), ['reason' => 'Trying to refund.'])->assertForbidden();
    expect($payment->fresh()->status)->toBe(PaymentStatus::Succeeded);

    auth('staff')->logout();
    $this->get(route('admin.payments.index'))->assertRedirect(route('admin.login'));
    $this->get(route('admin.ledger.index'))->assertRedirect(route('admin.login'));
});

it('gives Finance and Auditor the right default permissions, and Super admin all of them', function () {
    $has = fn (string $role) => staffWith($role)->getAllPermissions()->pluck('name')->filter(fn ($p) => in_array($p, ['view payments', 'refund payments', 'view ledger']))->sort()->values()->all();

    expect($has('Finance'))->toBe(['refund payments', 'view ledger', 'view payments'])->and($has('Auditor'))->toBe(['view ledger', 'view payments'])->and($has('Super admin'))->toBe(['refund payments', 'view ledger', 'view payments'])
        ->and($has('Support'))->toBe([])->and($has('Platform manager'))->toBe([]);
});

/** ---------------------------------------------------------------- the payments list and page */
it('lists payments with tabs and counts, search and sorting', function () {
    $paid = soldToBuyer();
    $other = User::factory()->create(['name' => 'Second Buyer']);
    $payments = app(PaymentService::class);
    $pending = $payments->start($other, $this->product, LicenceTier::Standard, '0712345673'); // times out on the fake gateway
    $parked = parkedPayment();
    $this->actingAs($this->finance, 'staff');

    $this->get(route('admin.payments.index'))->assertOk()->assertSee('Needs review')->assertSee('Oak armchair')->assertSee($paid->reference)->assertSee('Buyer Person')->assertSee('Second Buyer');
    $this->get(route('admin.payments.index', ['status' => 'review']))->assertSee($parked->reference)->assertDontSee($paid->reference)->assertSee('Paid Ksh1,000.00 but Ksh1,200.00 was expected.');
    $this->get(route('admin.payments.index', ['status' => 'succeeded']))->assertSee($paid->reference)->assertDontSee($parked->reference);
    $this->get(route('admin.payments.index', ['status' => 'pending']))->assertSee($pending->reference);
    $this->get(route('admin.payments.index', ['q' => 'Second Buyer']))->assertSee($pending->reference)->assertDontSee($paid->reference);
    $this->get(route('admin.payments.index', ['q' => $paid->receipt]))->assertSee($paid->reference);
    $this->get(route('admin.payments.index', ['status' => 'bogus', 'sort' => 'evil', 'dir' => 'up']))->assertOk();
});

it('sorts payments by amount', function () {
    $small = soldToBuyer();
    $this->product->update(['price_minor' => 900000]);
    $big = soldToBuyer(User::factory()->create());
    $this->actingAs($this->finance, 'staff');

    $this->get(route('admin.payments.index', ['sort' => 'amount']))->assertSeeInOrder([$big->reference, $small->reference]);
    $this->get(route('admin.payments.index', ['sort' => 'amount', 'dir' => 'asc']))->assertSeeInOrder([$small->reference, $big->reference]);
});

it('shows a payment: who, how much, the split, the licence, what it posted, and what a refund would do', function () {
    $payment = soldToBuyer();
    $file = $this->product->files()->create(['disk' => 'local', 'path' => 'x/y.glb', 'original_name' => 'y.glb', 'extension' => 'glb', 'kind' => 'exchange', 'size_bytes' => 10, 'checksum' => str_repeat('a', 64)]);
    LicenceDownload::create(['issued_licence_id' => IssuedLicence::sole()->id, 'product_file_id' => $file->id, 'file_name' => 'y.glb']);
    $this->actingAs($this->finance, 'staff');

    $this->get(route('admin.payments.show', $payment))->assertOk()->assertSee('Oak armchair')->assertSee($payment->reference)->assertSee('Buyer Person')->assertSee('Seller Person')->assertSee('Ksh1,200.00')->assertSee('Ksh180.00')->assertSee('(15%)')
        ->assertSee($payment->receipt)->assertSee('0712 345 675')->assertSee(IssuedLicence::sole()->key)->assertSee('1 file download')->assertSee('Files were downloaded')
        ->assertSee('Sale of')->assertSee('Held at the payment gateway')->assertSee('Record a refund')->assertSee('taken back from the seller')->assertSee('M-Pesa portal');
});

it('masks the phone and hides the refund controls from staff who can only view', function () {
    $payment = soldToBuyer();
    $this->actingAs(staffWith('Auditor'), 'staff');

    $this->get(route('admin.payments.show', $payment))->assertOk()->assertDontSee('0712 345 675')->assertDontSee('Record a refund');
    $this->get(route('admin.payments.index'))->assertOk()->assertDontSee('254712345675');
});

it('refunds from the payment page, needing a proper reason, and shows it as refunded afterwards', function () {
    $payment = soldToBuyer();
    $this->actingAs($this->finance, 'staff');

    $this->post(route('admin.payments.refund', $payment), [])->assertSessionHasErrors('reason');
    $this->post(route('admin.payments.refund', $payment), ['reason' => 'abc'])->assertSessionHasErrors('reason');
    expect($payment->fresh()->status)->toBe(PaymentStatus::Succeeded);

    $this->post(route('admin.payments.refund', $payment), ['reason' => 'The file is corrupt.'])->assertRedirect()->assertSessionHas('success');

    expect($payment->fresh()->status)->toBe(PaymentStatus::Refunded);
    $this->get(route('admin.payments.show', $payment))->assertSee('Refunded')->assertSee('The file is corrupt.')->assertSee($this->finance->name)->assertDontSee('Record a refund');
    $this->post(route('admin.payments.refund', $payment), ['reason' => 'Refund it again please.'])->assertSessionHas('error');
});

it('shows an unmatched payment\'s ledger posting and warns about it', function () {
    $payment = parkedPayment();
    $this->actingAs($this->finance, 'staff');

    $this->get(route('admin.payments.show', $payment))->assertOk()->assertSee('arrived but could not become a licence')->assertSee('held as unallocated money')->assertSee('Return the parked money')->assertSee('Record a refund')->assertSee('Payments waiting to be sorted out')->assertDontSee('Commission')->assertDontSee("Seller's share");
});

it('queues unmatched payments for review in the menu, the dashboard and the attention list', function () {
    parkedPayment();
    $page = $this->actingAs($this->finance, 'staff')->get(route('admin.dashboard'))->assertOk();

    $page->assertSee('Payments to review')->assertSee('Payment to review')->assertSee('Oak armchair');
    expect(collect(StaffMenu::for($this->finance))->flatMap->items->firstWhere('label', 'Payments')['badge'])->toBe(1);

    $this->actingAs(staffWith('Auditor'), 'staff')->get(route('admin.dashboard'))->assertDontSee('Payments to review');
});

/** ---------------------------------------------------------------- the ledger */
it('says the books add up after a full cycle of sale, release, withdrawal and refund', function () {
    soldToBuyer();
    $second = soldToBuyer(User::factory()->create());
    app(RefundService::class)->refund($second, $this->finance, 'Not as described.');
    $this->travel(8)->days();
    app(EarningsService::class)->releaseDue();
    $payouts = app(PayoutService::class);
    $payouts->refresh($payouts->approve($payouts->request($this->seller, 100000, '0712345675'), staffWith('Super admin')));
    $this->withSession(['session_rules.staff.last' => now()->timestamp]);

    $this->actingAs($this->finance, 'staff')->get(route('admin.ledger.index'))->assertOk()->assertSee('The books add up.')->assertDontSee('do not add up')->assertSee('Held at the gateway')->assertSee('Owed to members')->assertSee('Earned by the platform');
});

it('raises the alarm when the books do not add up', function () {
    soldToBuyer();
    $stray = LedgerAccount::create(['code' => 'platform.stray', 'name' => 'Stray account', 'kind' => 'income', 'currency' => 'KES', 'allow_negative' => true]);
    $this->ledger->post('adjustment', 'stray:1', [LedgerLine::debit($this->ledger->platformAccount('gateway'), 5000), LedgerLine::credit($stray, 5000)], 'Money nobody accounts for');

    $this->actingAs($this->finance, 'staff')->get(route('admin.ledger.index'))->assertOk()->assertSee('The books do not add up')->assertSee('a difference of Ksh50.00')->assertDontSee('The books add up.');
});

it('lists the postings, with tabs, search and a member filter', function () {
    $sale = soldToBuyer();
    $this->actingAs($this->finance, 'staff');

    $this->get(route('admin.ledger.index'))->assertOk()->assertSee('Sale of')->assertSee("sale:payment:{$sale->id}")->assertSee('Ksh1,200.00');
    $this->get(route('admin.ledger.index', ['tab' => 'refunds']))->assertOk()->assertDontSee("sale:payment:{$sale->id}")->assertSee('No postings here');
    $this->get(route('admin.ledger.index', ['tab' => 'sales']))->assertSee("sale:payment:{$sale->id}");
    $this->get(route('admin.ledger.index', ['q' => 'oak armchair']))->assertSee("sale:payment:{$sale->id}");
    $this->get(route('admin.ledger.index', ['q' => 'nothing like this']))->assertSee('No postings here');
    $this->get(route('admin.ledger.index', ['member' => $this->seller->id]))->assertSee("sale:payment:{$sale->id}")->assertSee('Member: Seller Person');
    $this->get(route('admin.ledger.index', ['member' => User::factory()->create()->id]))->assertSee('No postings here');
    $this->get(route('admin.ledger.index', ['tab' => 'bogus', 'member' => 'x']))->assertOk();
});

it('shows one posting with its debits and credits and where it came from, and offers no way to change it', function () {
    $sale = soldToBuyer();
    $transaction = LedgerTransaction::where('type', 'sale')->sole();
    $this->actingAs($this->finance, 'staff');

    $page = $this->get(route('admin.ledger.show', $transaction))->assertOk();
    $page->assertSee('Held at the payment gateway')->assertSee('Earnings in the hold period')->assertSee('Seller Person')->assertSee('Commission earned')->assertSee("user.{$this->seller->id}.pending")
        ->assertSee('Payment '.$sale->reference)->assertSee(route('admin.payments.show', $sale), false)->assertSee('never changed or deleted')->assertSee('Ksh1,200.00');

    $this->post(route('admin.ledger.show', $transaction))->assertStatus(405);
    $this->delete(route('admin.ledger.show', $transaction))->assertStatus(405);
    $this->get(route('admin.ledger.show', 999999))->assertNotFound();
});

it('links a withdrawal\'s postings back to the withdrawals page', function () {
    soldToBuyer();
    $this->travel(8)->days();
    app(EarningsService::class)->releaseDue();
    $payout = app(PayoutService::class)->request($this->seller, 100000, '0712345675');
    $this->actingAs($this->finance, 'staff');

    $this->get(route('admin.ledger.show', LedgerTransaction::where('type', 'payout_requested')->sole()))->assertOk()->assertSee('Withdrawal '.$payout->reference);
    expect($payout->status)->toBe(PayoutStatus::Requested);
});
