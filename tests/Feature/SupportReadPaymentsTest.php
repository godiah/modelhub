<?php

use App\Enums\LicenceTier;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\SupportReadAudit;
use App\Models\User;
use App\Services\Marketplace\LicenceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

/*
 * What the support assistant can read about a member's own payments: only theirs, only an explicit list of fields (no titles or names written
 * by other people), whether paying produced a licence, and nothing written or asked of the gateway. Looking one up by M-Pesa code finds only the
 * member's own and has its own, lower limit.
 */

require_once __DIR__.'/Support/ReadApiHelpers.php';

beforeEach(function () {
    setUpReadApi($this);
    $this->seller = User::factory()->create(['name' => 'Seller Person']);
    SellerProfile::factory()->approved()->create(['user_id' => $this->seller->id]);
    $this->product = Product::factory()->published()->create(['user_id' => $this->seller->id, 'title' => 'Oak armchair <b>SECRET TITLE</b>', 'price_minor' => 120000]);
});

function paymentFor(User $buyer, PaymentStatus $status = PaymentStatus::Succeeded, array $over = []): Payment
{
    return Payment::create($over + [
        'reference' => Payment::newReference(), 'purpose' => Payment::PURPOSE_SALE, 'user_id' => $buyer->id, 'seller_id' => test()->seller->id,
        'product_id' => test()->product->id, 'tier' => LicenceTier::Standard, 'amount_minor' => 120000, 'currency' => 'KES', 'msisdn' => '254712345675',
        'status' => $status, 'gateway' => 'gw', 'gateway_reference' => 'GW-SECRET-'.Str::random(8), 'commission_rate' => 0.15, 'commission_minor' => 18000, 'seller_share_minor' => 102000,
        'hold_days' => 7, 'expires_at' => now()->addMinutes(5),
    ]);
}

/** A sale that really produced a licence, as the purchase flow does. */
function paidWithLicence(User $buyer, array $over = []): Payment
{
    // a buyer holds one licence per model, so each sale is of a different one
    $product = Product::factory()->published()->create(['user_id' => test()->seller->id, 'price_minor' => 120000]);
    $licence = app(LicenceService::class)->grant($buyer, $product, LicenceTier::Standard);

    return paymentFor($buyer, PaymentStatus::Succeeded, ['purchase_id' => $licence->purchase_id, 'receipt' => 'QWE5678RTY', 'completed_at' => now()] + $over);
}

function readPayments(object $test, User $member, string $path = '/api/support/v1/payments'): TestResponse
{
    return call($test, readCall($path, ['claim' => claimFor($member)]), $path);
}

it('lists the member\'s payments with the ones that need attention first, at most three, and a count of all', function () {
    $paid = paidWithLicence($this->member);
    $failed = paymentFor($this->member, PaymentStatus::Failed, ['failure_reason' => 'Wrong PIN']);
    $review = paymentFor($this->member, PaymentStatus::Review, ['failure_reason' => 'Amount was wrong']);
    $pending = paymentFor($this->member, PaymentStatus::Pending);
    $other = paymentFor($this->member, PaymentStatus::Cancelled);

    $response = readPayments($this, $this->member)->assertOk();

    // pending and review need attention; then failed or cancelled; then the rest. Newest first within each.
    expect($response->json('total'))->toBe(5)
        ->and(collect($response->json('data'))->pluck('reference')->all())->toBe([$pending->reference, $review->reference, $other->reference]);
});

it('puts a sale that was paid but has no licence ahead of the ones that went fine', function () {
    paidWithLicence($this->member);
    $noLicence = paymentFor($this->member, PaymentStatus::Succeeded, ['receipt' => 'ZZZ1111AAA']);
    paidWithLicence($this->member);
    paidWithLicence($this->member);

    $first = readPayments($this, $this->member)->json('data.0');

    expect($first['reference'])->toBe($noLicence->reference)->and($first['licence'])->toBe('none');
});

it('says whether paying produced a licence: active, ended, none, or not applicable to job funding', function () {
    $active = paidWithLicence($this->member);
    $ended = paidWithLicence($this->member);
    $ended->purchase->licence->update(['revoked_at' => now(), 'revoked_reason' => 'Refunded']);
    $ended->update(['status' => PaymentStatus::Refunded]);
    $none = paymentFor($this->member, PaymentStatus::Succeeded);
    $escrow = paymentFor($this->member, PaymentStatus::Succeeded, ['purpose' => Payment::PURPOSE_ESCROW, 'product_id' => null, 'tier' => null]);

    $byReference = collect(readPayments($this, $this->member)->json('data'))->keyBy('reference');
    $one = fn (Payment $p) => call($this, readCall($path = '/api/support/v1/payments/'.$p->reference, ['claim' => claimFor($this->member)]), $path)->assertOk()->json('data.licence');

    expect($one($active))->toBe('active')->and($one($ended))->toBe('ended')->and($one($none))->toBe('none')->and($one($escrow))->toBe('not_applicable');
    expect($byReference->count())->toBe(3); // the list shows three; the single reads above cover all four states
});

it('shows only an explicit list of fields, and none written by other people', function () {
    $payment = paidWithLicence($this->member);

    $data = readPayments($this, $this->member, '/api/support/v1/payments/'.$payment->reference)->assertOk()->json('data');

    expect(array_keys($data))->toBe([
        'reference', 'purpose', 'status', 'status_label', 'amount_minor', 'amount_display', 'received_display', 'currency', 'tier', 'tier_label', 'phone_last3',
        'requested_at', 'expires_at', 'completed_at', 'pending_past_expiry', 'receipt', 'reason', 'licence',
    ])
        ->and($data['receipt'])->toBe('QWE5678RTY')
        ->and($data['phone_last3'])->toBe('675')
        ->and($data['amount_display'])->toBe('Ksh1,200');

    $json = json_encode($data);
    foreach (['SECRET TITLE', 'Oak armchair', 'Seller Person', 'GW-SECRET', '254712345675', 'commission', 'seller_share'] as $private) {
        expect($json)->not->toContain($private);
    }
});

it('marks a payment still pending after its time ran out, and does not touch it', function () {
    $late = paymentFor($this->member, PaymentStatus::Pending, ['expires_at' => now()->subMinutes(3)]);
    $fresh = paymentFor($this->member, PaymentStatus::Pending, ['expires_at' => now()->addMinutes(3)]);

    $lateData = readPayments($this, $this->member, '/api/support/v1/payments/'.$late->reference)->json('data');
    $freshData = readPayments($this, $this->member, '/api/support/v1/payments/'.$fresh->reference)->json('data');

    expect($lateData['pending_past_expiry'])->toBeTrue()->and($lateData['status'])->toBe('pending')->and($freshData['pending_past_expiry'])->toBeFalse()
        ->and($freshData['expires_at'])->not->toBeNull()->and($lateData['receipt'])->toBeNull();
    expect($late->fresh()->status)->toBe(PaymentStatus::Pending); // reading never settles, expires or refreshes it
});

it('asks the gateway for nothing and writes nothing but its own audit row', function () {
    Http::fake();
    $payment = paymentFor($this->member, PaymentStatus::Pending);
    $before = DB::table('payments')->get()->toJson();

    readPayments($this, $this->member);
    readPayments($this, $this->member, '/api/support/v1/payments/'.$payment->reference);

    Http::assertNothingSent();
    expect(DB::table('payments')->get()->toJson())->toBe($before)->and(DB::table('ledger_entries')->count())->toBe(0)->and(SupportReadAudit::count())->toBe(2);
});

it('lists only payments the member made, not the ones where they were the seller', function () {
    $buyer = User::factory()->create();
    paymentFor($buyer, PaymentStatus::Succeeded, ['seller_id' => $this->member->id]);

    expect(readPayments($this, $this->member)->json('total'))->toBe(0);
});

it('answers someone else\'s reference, a missing one and a malformed one with exactly the same thing', function (string $reference) {
    $other = paymentFor(User::factory()->create());
    $path = '/api/support/v1/payments/'.($reference === 'theirs' ? $other->reference : $reference);

    readPayments($this, $this->member, $path)->assertNotFound()->assertExactJson(['error' => 'not_found']);
})->with(['theirs', 'MH0000000000', 'MH12', 'PO0000000000', 'not-a-reference', '%27%20OR%201%3D1']);

it('finds a payment by M-Pesa code among the member\'s own, in either case', function () {
    $mine = paidWithLicence($this->member);

    foreach (['QWE5678RTY', 'qwe5678rty'] as $code) {
        $found = readPayments($this, $this->member, '/api/support/v1/payments?mpesa_code='.$code)->assertOk();
        expect($found->json('total'))->toBe(1)->and($found->json('data.0.reference'))->toBe($mine->reference);
    }
});

it('finds nothing for someone else\'s code, a made-up one, or a malformed one, all alike', function (string $code) {
    $other = User::factory()->create();
    paymentFor($other, PaymentStatus::Succeeded, ['receipt' => 'OTH9999ZZZ']);
    paidWithLicence($this->member);

    $response = readPayments($this, $this->member, '/api/support/v1/payments?mpesa_code='.$code)->assertOk();

    expect($response->json('total'))->toBe(0)->and($response->json('data'))->toBe([]);
})->with(['OTH9999ZZZ', 'NOP0000XYZ', 'short', 'WAY-too-long-and-odd!!', "' OR 1=1 --"]);

it('limits M-Pesa code lookups on their own, lower, per-member limit', function () {
    config(['support.reads.code_lookups_per_minute' => 2]);
    RateLimiter::clear('support-reads:code:'.$this->member->id);

    readPayments($this, $this->member, '/api/support/v1/payments?mpesa_code=AAA1111BBB')->assertOk();
    readPayments($this, $this->member, '/api/support/v1/payments?mpesa_code=AAA1111BBB')->assertOk();
    readPayments($this, $this->member, '/api/support/v1/payments?mpesa_code=AAA1111BBB')->assertStatus(429);
    // plain listing is not held back by it
    readPayments($this, $this->member)->assertOk();
});

it('never lets a query string choose whose payments are listed', function () {
    $other = User::factory()->create();
    paymentFor($other);

    $response = readPayments($this, $this->member, '/api/support/v1/payments?user_id='.$other->id.'&member='.$other->id);

    expect($response->json('total'))->toBe(0);
});

it('can be switched off on its own, without touching withdrawals', function () {
    config(['support.reads.capabilities.payments' => false]);

    readPayments($this, $this->member)->assertNotFound()->assertExactJson(['error' => 'not_found']);
    readPayments($this, $this->member, '/api/support/v1/withdrawals')->assertOk();
});

it('is empty, not an error, for a member who has paid for nothing', function () {
    readPayments($this, $this->member)->assertOk()->assertJsonPath('total', 0)->assertJsonPath('data', []);
});
