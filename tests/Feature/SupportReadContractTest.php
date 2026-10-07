<?php

use App\Enums\LicenceTier;
use App\Enums\PaymentStatus;
use App\Enums\PayoutStatus;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use App\Services\Marketplace\LicenceService;
use App\Services\Support\Inbound\InboundRequestVerifier;
use App\Services\Support\Inbound\RequestNonces;
use App\Services\Support\Inbound\SupportRequestRejected;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;

/*
 * The real read API, held to the contract it publishes (docs/support-read-api.openapi.yaml). The same file is kept in modelhub-support, whose
 * tests hold its fakes and its parsing to it; both pin the file's hash, so the contract only changes on purpose, in both places.
 *
 * Every answer is checked against its schema with nothing allowed that the schema does not name: a field added to an endpoint without the
 * contract being updated fails here, and so does a field the contract promises that the endpoint stopped sending.
 */

require_once __DIR__.'/Support/ReadApiHelpers.php';
require_once __DIR__.'/Support/SchemaCheck.php';

// The same value is pinned in modelhub-support's tests/unit/test_contract.py. Change the contract in both repositories, then update both.
const SUPPORT_CONTRACT_SHA256 = 'e1b57358ded9caa14de0f41c8bc2e66ee978c894c377f2578891e1404764f97b';

beforeEach(function () {
    setUpReadApi($this);
    $this->seller = User::factory()->create();
    SellerProfile::factory()->approved()->create(['user_id' => $this->seller->id]);
});

/** A member with something of everything: withdrawals in several states, payments in several states, licences, a hold. */
function memberWithEverything(object $test): void
{
    $member = $test->member;
    $withdrawal = fn (PayoutStatus $status, array $over = []) => Payout::create($over + [
        'reference' => Payout::newReference(), 'user_id' => $member->id, 'amount_minor' => 150000, 'fee_minor' => 5000, 'net_minor' => 145000, 'currency' => 'KES',
        'msisdn' => '254712345675', 'status' => $status,
    ]);
    $withdrawal(PayoutStatus::Paid, ['receipt' => 'WDR9988ABC', 'approved_at' => now()->subDay(), 'completed_at' => now()]);
    $withdrawal(PayoutStatus::Rejected, ['failure_reason' => 'The phone number does not match.']);
    $withdrawal(PayoutStatus::Processing, ['approved_at' => now()->subHours(9), 'gateway_reference' => 'GW-1']);

    $product = fn () => Product::factory()->published()->create(['user_id' => test()->seller->id, 'price_minor' => 120000]);
    $payment = fn (PaymentStatus $status, array $over = []) => Payment::create($over + [
        'reference' => Payment::newReference(), 'purpose' => Payment::PURPOSE_SALE, 'user_id' => $member->id, 'seller_id' => test()->seller->id, 'product_id' => $product()->id,
        'tier' => LicenceTier::Standard, 'amount_minor' => 120000, 'currency' => 'KES', 'msisdn' => '254712345675', 'status' => $status, 'gateway' => 'gw',
        'gateway_reference' => uniqid('gw'), 'commission_rate' => 0.15, 'commission_minor' => 18000, 'seller_share_minor' => 102000, 'hold_days' => 7, 'expires_at' => now()->addMinutes(5),
    ]);
    $licence = app(LicenceService::class)->grant($member, $product(), LicenceTier::Standard);
    $payment(PaymentStatus::Succeeded, ['purchase_id' => $licence->purchase_id, 'receipt' => 'QWE5678RTY', 'completed_at' => now()]);
    $payment(PaymentStatus::Review, ['failure_reason' => 'Paid Ksh1,250.00 but Ksh1,200.00 was expected.', 'received_minor' => 125000, 'receipt' => 'ODD8039907']);
    $payment(PaymentStatus::Pending, ['expires_at' => now()->subMinute()]);
    $payment(PaymentStatus::Succeeded, ['purpose' => Payment::PURPOSE_ESCROW, 'product_id' => null, 'tier' => null]);
    $ended = app(LicenceService::class)->grant($member, $product(), LicenceTier::Standard);
    $ended->update(['revoked_at' => now(), 'revoked_reason' => 'Refunded']);

    // money held for them as a seller, one hold already ended but not yet released
    $sale = fn (array $over) => Payment::create($over + [
        'reference' => Payment::newReference(), 'purpose' => Payment::PURPOSE_SALE, 'user_id' => test()->seller->id, 'seller_id' => $member->id, 'product_id' => $product()->id,
        'tier' => 'standard', 'amount_minor' => 100000, 'currency' => 'KES', 'msisdn' => '254712345675', 'status' => PaymentStatus::Succeeded, 'gateway' => 'gw',
        'gateway_reference' => uniqid('gw'), 'commission_rate' => 0.15, 'commission_minor' => 15000, 'seller_share_minor' => 85000, 'hold_days' => 7, 'expires_at' => now(),
    ]);
    $sale(['release_at' => now()->addDays(3)]);
    $sale(['release_at' => now()->subHours(2)]);
}

function readContract(object $test, string $path, ?User $member = null): TestResponse
{
    return call($test, readCall($path, ['claim' => claimFor($member ?? $test->member)]), $path);
}

function expectFits(TestResponse $response, string $schemaName): void
{
    $contract = supportContract();

    expect(schemaProblems($response->json(), supportSchema($schemaName, $contract), $contract))->toBe([]);
}

it('is the contract both repositories agreed', function () {
    expect(hash_file('sha256', SUPPORT_CONTRACT))->toBe(SUPPORT_CONTRACT_SHA256);
});

it('has the same paths as the routes, no more and no fewer', function () {
    $routes = collect(Route::getRoutes()->getRoutes())->map(fn ($r) => $r->uri())
        ->filter(fn ($uri) => str_starts_with($uri, 'api/support/v1/'))
        ->map(fn ($uri) => '/'.substr($uri, strlen('api/support/v1/')))->unique()->sort()->values()->all();
    $documented = collect(array_keys(supportContract()['paths']))->sort()->values()->all();

    expect($routes)->toBe($documented);
});

it('answers every read exactly as the contract describes', function (string $path, string $schemaName) {
    memberWithEverything($this);

    expectFits(readContract($this, $path)->assertOk(), $schemaName);
})->with([
    ['/api/support/v1/ping', 'Ping'],
    ['/api/support/v1/withdrawals', 'WithdrawalList'],
    ['/api/support/v1/payments', 'PaymentList'],
    ['/api/support/v1/balance', 'BalanceOne'],
    ['/api/support/v1/licences', 'LicenceList'],
    ['/api/support/v1/licences?q=a', 'LicenceList'],
    ['/api/support/v1/payments?mpesa_code=QWE5678RTY', 'PaymentList'],
]);

it('answers a single withdrawal and a single payment as the contract describes', function () {
    memberWithEverything($this);
    $withdrawal = Payout::where('user_id', $this->member->id)->first();
    $payment = Payment::where('user_id', $this->member->id)->first();

    expectFits(readContract($this, '/api/support/v1/withdrawals/'.$withdrawal->reference)->assertOk(), 'WithdrawalOne');
    expectFits(readContract($this, '/api/support/v1/payments/'.$payment->reference)->assertOk(), 'PaymentOne');
});

it('answers every state a withdrawal or a payment can be in as the contract describes', function () {
    memberWithEverything($this);
    foreach (PayoutStatus::cases() as $status) {
        Payout::create([
            'reference' => Payout::newReference(), 'user_id' => $this->member->id, 'amount_minor' => 100000, 'fee_minor' => 3000, 'net_minor' => 97000, 'currency' => 'KES',
            'msisdn' => '254712345675', 'status' => $status,
        ]);
    }

    foreach (Payout::where('user_id', $this->member->id)->get() as $payout) {
        expectFits(readContract($this, '/api/support/v1/withdrawals/'.$payout->reference)->assertOk(), 'WithdrawalOne');
    }
    foreach (Payment::where('user_id', $this->member->id)->get() as $payment) {
        expectFits(readContract($this, '/api/support/v1/payments/'.$payment->reference)->assertOk(), 'PaymentOne');
    }
});

it('answers the balance as the contract describes when something blocks a withdrawal too', function () {
    $open = Payout::create([
        'reference' => Payout::newReference(), 'user_id' => $this->member->id, 'amount_minor' => 100000, 'fee_minor' => 3000, 'net_minor' => 97000, 'currency' => 'KES',
        'msisdn' => '254712345675', 'status' => PayoutStatus::Requested,
    ]);

    $response = readContract($this, '/api/support/v1/balance')->assertOk();

    expectFits($response, 'BalanceOne');
    expect($response->json('data.open_withdrawal.reference'))->toBe($open->reference);
});

it('answers an empty member as the contract describes', function () {
    foreach ([['/api/support/v1/withdrawals', 'WithdrawalList'], ['/api/support/v1/payments', 'PaymentList'], ['/api/support/v1/licences', 'LicenceList'], ['/api/support/v1/balance', 'BalanceOne']] as [$path, $schema]) {
        expectFits(readContract($this, $path)->assertOk(), $schema);
    }
});

it('answers every refusal with the one shape the contract allows', function (string $path, callable $make, int $status) {
    $headers = $make($this);

    $response = call($this, $headers, $path)->assertStatus($status);

    expectFits($response, 'Error');
})->with([
    'not found' => ['/api/support/v1/withdrawals/PO0000000000', fn ($t) => readCall('/api/support/v1/withdrawals/PO0000000000', ['claim' => claimFor($t->member)]), 404],
    'bad signature' => ['/api/support/v1/ping', fn ($t) => readCall('/api/support/v1/ping', ['claim' => claimFor($t->member), 'secret' => 'wrong']), 401],
    'no claim' => ['/api/support/v1/ping', fn ($t) => readCall('/api/support/v1/ping', ['claim' => false]), 401],
]);

it('is a contract the checker really enforces: an extra field, a missing one and a wrong value are all caught', function () {
    $contract = supportContract();
    $schema = supportSchema('Ping', $contract);

    expect(schemaProblems(['status' => 'ok', 'as_of' => 't'], $schema, $contract))->toBe([])
        ->and(schemaProblems(['status' => 'ok'], $schema, $contract))->not->toBe([])
        ->and(schemaProblems(['status' => 'ok', 'as_of' => 't', 'extra' => 1], $schema, $contract))->not->toBe([])
        ->and(schemaProblems(['status' => 'bad', 'as_of' => 't'], $schema, $contract))->not->toBe([])
        ->and(schemaProblems(['status' => 'ok', 'as_of' => 5], $schema, $contract))->not->toBe([]);
});

it('accepts the signature modelhub-support makes for a read with a query, so both sides agree on what is signed', function () {
    // The same literal is pinned in modelhub-support (tests/unit/test_hmac.py), which signs reads; this app verifies them.
    $verifier = new InboundRequestVerifier(
        ['vector' => 'vector-secret'], 60, new RequestNonces,
    );

    $keyId = $verifier->verify(
        'GET', '/api/support/v1/payments?mpesa_code=QWE5678RTY', '', 'vector', '1800000000', 'nonce-reads-1',
        '3116eba959b224e7c5633e1ac6c19eda2aa0675a24cb85e29087d8e2fcabd06a', now: 1800000000,
    );

    expect($keyId)->toBe('vector');
});

it('rejects that signature when any part of what was signed differs', function (array $change) {
    $verifier = new InboundRequestVerifier(
        ['vector' => 'vector-secret'], 60, new RequestNonces,
    );
    $args = ['GET', '/api/support/v1/payments?mpesa_code=QWE5678RTY', '', 'vector', '1800000000', 'nonce-reads-1', '3116eba959b224e7c5633e1ac6c19eda2aa0675a24cb85e29087d8e2fcabd06a'];

    foreach ($change as $i => $value) {
        $args[$i] = $value;
    }

    expect(fn () => $verifier->verify(...$args, now: 1800000000))->toThrow(SupportRequestRejected::class);
})->with([
    'another query' => [[1 => '/api/support/v1/payments?mpesa_code=QWE5678RTZ']],
    'another path' => [[1 => '/api/support/v1/withdrawals']],
    'another method' => [[0 => 'POST']],
    'a body' => [[2 => 'x']],
    'another nonce' => [[5 => 'nonce-reads-2']],
    'another timestamp' => [[4 => '1800000001']],
]);
