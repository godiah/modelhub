<?php

use App\Enums\PaymentStatus;
use App\Enums\PayoutStatus;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\SupportReadAudit;
use App\Models\User;
use App\Services\Ledger\LedgerService;
use App\Services\Payments\PayoutEligibility;
use App\Services\Payments\PayoutService;
use App\Support\Ledger\LedgerLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;

/*
 * Where the member stands on withdrawing, as the assistant reads it: what is available and held, the most they could take out, and what is stopping
 * them. The rules are PayoutEligibility's, the same ones a withdrawal request goes through, so the two cannot disagree.
 */

require_once __DIR__.'/Support/ReadApiHelpers.php';

beforeEach(function () {
    setUpReadApi($this);
    config(['payments.max_kes' => 150000]);
});

function fund(User $user, int $availableMinor, int $pendingMinor = 0): void
{
    $ledger = app(LedgerService::class);
    $lines = [LedgerLine::debit($ledger->platformAccount('gateway'), $availableMinor + $pendingMinor)];
    $availableMinor > 0 && $lines[] = LedgerLine::credit($ledger->userAccount($user, 'available'), $availableMinor);
    $pendingMinor > 0 && $lines[] = LedgerLine::credit($ledger->userAccount($user, 'pending'), $pendingMinor);
    $ledger->post('seed', 'seed:'.$user->id.':'.uniqid(), $lines, 'Funds');
}

function readBalance(object $test, User $member): TestResponse
{
    return call($test, readCall($path = '/api/support/v1/balance', ['claim' => claimFor($member)]), $path);
}

it('says what is available and held, with the words ModelHub would use for each amount', function () {
    fund($this->member, 250000, 80000);

    $data = readBalance($this, $this->member)->assertOk()->json('data');

    expect($data['available_minor'])->toBe(250000)->and($data['available_display'])->toBe('Ksh2,500')
        ->and($data['pending_minor'])->toBe(80000)->and($data['pending_display'])->toBe('Ksh800')
        ->and($data['can_withdraw'])->toBeTrue()->and($data['blockers'])->toBe([])
        ->and($data['withdrawable_minor'])->toBe(250000)->and($data['withdrawable_display'])->toBe('Ksh2,500')
        ->and($data['min_withdrawal_display'])->toStartWith('Ksh')->and($data['fee_display'])->toStartWith('Ksh');
});

it('never offers more than M-Pesa will send in one go', function () {
    config(['payments.max_kes' => 1000]);
    fund($this->member, 900000);

    $data = readBalance($this, $this->member)->json('data');

    // the cap is on what is SENT; the fee comes out of the amount asked for, so the largest request is the cap plus the fee
    expect($data['withdrawable_minor'])->toBe(100000 + $data['fee_minor'])->and($data['max_per_withdrawal_minor'])->toBe(100000);
});

it('names why a member cannot withdraw', function (callable $setUp, string $blocker) {
    $setUp($this->member);

    $data = readBalance($this, $this->member)->json('data');

    expect($data['can_withdraw'])->toBeFalse()->and($data['blockers'])->toContain($blocker)->and($data['withdrawable_minor'])->toBe(0);
})->with([
    'nothing at all' => [fn () => null, 'nothing_available'],
    'less than the smallest withdrawal' => [fn (User $m) => fund($m, 100), 'below_minimum'],
    'a withdrawal already in progress' => [function (User $m) {
        fund($m, 500000);
        Payout::create(['reference' => Payout::newReference(), 'user_id' => $m->id, 'amount_minor' => 100000, 'fee_minor' => 3000, 'net_minor' => 97000, 'currency' => 'KES', 'msisdn' => '254712345675', 'status' => PayoutStatus::Processing]);
    }, 'open_withdrawal'],
]);

it('names the withdrawal in progress and nothing else about it', function () {
    fund($this->member, 500000);
    $open = Payout::create(['reference' => Payout::newReference(), 'user_id' => $this->member->id, 'amount_minor' => 100000, 'fee_minor' => 3000, 'net_minor' => 97000, 'currency' => 'KES', 'msisdn' => '254712345675', 'status' => PayoutStatus::Requested]);

    $data = readBalance($this, $this->member)->json('data');

    expect($data['open_withdrawal'])->toBe(['reference' => $open->reference, 'status' => 'requested', 'status_label' => 'Waiting for approval']);
});

it('shows money still on hold with when it is released, and says so when the hold has ended but the hourly release has not run', function () {
    $buyer = User::factory()->create();
    $product = Product::factory()->published()->create(['user_id' => $this->member->id, 'price_minor' => 100000]);
    SellerProfile::factory()->approved()->create(['user_id' => $this->member->id]);
    $sale = fn (array $over) => Payment::create($over + [
        'reference' => Payment::newReference(), 'purpose' => Payment::PURPOSE_SALE, 'user_id' => $buyer->id, 'seller_id' => $this->member->id, 'product_id' => $product->id,
        'tier' => 'standard', 'amount_minor' => 100000, 'currency' => 'KES', 'msisdn' => '254712345675', 'status' => PaymentStatus::Succeeded, 'gateway' => 'gw',
        'gateway_reference' => uniqid('gw'), 'commission_rate' => 0.15, 'commission_minor' => 15000, 'seller_share_minor' => 85000, 'hold_days' => 7, 'expires_at' => now(),
    ]);
    $sale(['release_at' => now()->addDays(3)]);
    $sale(['release_at' => now()->addDays(5)]);
    $sale(['release_at' => now()->subHours(2)]); // the hold has ended, not yet released by the hourly job
    $sale(['release_at' => now()->subDays(2), 'released_at' => now()->subDay()]); // already released: not held
    $sale(['seller_id' => User::factory()->create()->id, 'release_at' => now()->addDays(2)]); // another seller's hold is not theirs

    $data = readBalance($this, $this->member)->json('data');

    expect($data['held_payments'])->toBe(3)->and($data['held_minor'])->toBe(255000)->and($data['overdue_payments'])->toBe(1)
        ->and(Carbon\Carbon::parse($data['next_release_at'])->isSameDay(now()->addDays(3)))->toBeTrue();
});

it('is about the member named by the claim only', function () {
    $other = User::factory()->create();
    fund($other, 999900);
    fund($this->member, 100000);

    expect(readBalance($this, $this->member)->json('data.available_minor'))->toBe(100000);
});

it('reads only: nothing is posted to the ledger and no withdrawal is made', function () {
    fund($this->member, 300000);
    $entries = DB::table('ledger_entries')->count();

    readBalance($this, $this->member)->assertOk();

    expect(DB::table('ledger_entries')->count())->toBe($entries)->and(Payout::count())->toBe(0)->and(SupportReadAudit::sole()->endpoint)->toBe('support.api.balance.show');
});

it('can be switched off on its own', function () {
    config(['support.reads.capabilities.balance' => false]);

    readBalance($this, $this->member)->assertNotFound()->assertExactJson(['error' => 'not_found']);
});

// ---- the rules are one set: what the assistant says is what a request would be told ------------------------------

it('agrees with PayoutService about every amount, so the explanation can never contradict a real request', function (int $amountMinor) {
    $member = User::factory()->create();
    fund($member, 100000000);
    $problem = app(PayoutEligibility::class)->amountProblem($amountMinor);

    $result = app(PayoutService::class)->request($member, $amountMinor, '0712345675');

    // a real request is refused with the very words the eligibility check gives, or it goes through when there is no problem
    expect($problem === null ? $result instanceof Payout : $result === $problem)->toBeTrue();
})->with([0, 150, 100, 4900, 5000, 60000, 99999999, 100000, 20000000]);

it('keeps the order of the rules a request has always applied', function () {
    $member = User::factory()->create();
    fund($member, 500000);
    app(PayoutService::class)->request($member, 100000, '0712345675');

    $eligibility = app(PayoutEligibility::class);

    // an amount that is wrong is reported before the withdrawal already in progress, as before
    expect(app(PayoutService::class)->request($member, 150, '0712345675'))->toBe($eligibility->amountProblem(150))
        ->and(app(PayoutService::class)->request($member, 100000, '0712345675'))->toBe('You already have a withdrawal in progress. Wait for it to finish, or cancel it.');
});

it('words each amount problem as a withdrawal request always has, so a member is told the same thing everywhere', function () {
    config(['payments.max_kes' => 150000]);
    $eligibility = app(PayoutEligibility::class);

    expect($eligibility->amountProblem(150))->toBe('Withdraw a whole number of shillings.')   // 150 cents is not a whole shilling
        ->and($eligibility->amountProblem(0))->toBe('Withdraw a whole number of shillings.')
        ->and($eligibility->amountProblem(200))->toStartWith('The smallest withdrawal is')     // Ksh2 is a whole number but under the minimum
        ->and($eligibility->amountProblem(20000000))->toBe('M-Pesa sends up to KES 150,000 at a time. Withdraw a smaller amount.')
        ->and($eligibility->amountProblem(100000))->toBeNull();
});
