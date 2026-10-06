<?php

use App\Enums\PayoutStatus;
use App\Models\Payout;
use App\Models\SupportReadAudit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;

/*
 * What the support assistant can read about a member's own withdrawals: only theirs, only an explicit list of fields, nothing written,
 * and a clear "not confirmed" instead of a claim that anyone was alerted.
 */

require_once __DIR__.'/Support/ReadApiHelpers.php';

beforeEach(fn () => setUpReadApi($this));

function payoutFor(User $user, PayoutStatus $status = PayoutStatus::Requested, array $over = []): Payout
{
    return Payout::create($over + [
        'reference' => Payout::newReference(), 'user_id' => $user->id, 'amount_minor' => 150000, 'fee_minor' => 5000, 'net_minor' => 145000,
        'currency' => 'KES', 'msisdn' => '254712345675', 'status' => $status,
    ]);
}

function asMember(object $test, User $member, string $path): TestResponse
{
    return call($test, readCall($path, ['claim' => claimFor($member)]), $path);
}

it('lists the member\'s withdrawals with the ones that need attention first, at most three, and a count of all', function () {
    payoutFor($this->member, PayoutStatus::Paid, ['receipt' => 'ABC1234XYZ']);
    $failed = payoutFor($this->member, PayoutStatus::Failed, ['failure_reason' => 'The phone could not be reached.']);
    payoutFor($this->member, PayoutStatus::Paid);
    $open = payoutFor($this->member, PayoutStatus::Processing, ['approved_at' => now()->subHour()]);
    $cancelled = payoutFor($this->member, PayoutStatus::Cancelled, ['failure_reason' => 'Cancelled by you.']);

    $response = asMember($this, $this->member, '/api/support/v1/withdrawals')->assertOk();

    expect($response->json('total'))->toBe(5)
        ->and(collect($response->json('data'))->pluck('reference')->all())->toBe([$open->reference, $failed->reference, $cancelled->reference]) // open, then failed, then the newest of the rest
        ->and($response->json('as_of'))->not->toBeNull();
});

it('shows only an explicit list of fields, with the phone cut to three digits', function () {
    $payout = payoutFor($this->member, PayoutStatus::Paid, ['receipt' => 'ABC1234XYZ', 'gateway_reference' => 'GW-SECRET', 'approved_by' => null, 'approved_at' => now()->subDay(), 'completed_at' => now()]);

    $data = asMember($this, $this->member, '/api/support/v1/withdrawals/'.$payout->reference)->assertOk()->json('data');

    expect(array_keys($data))->toBe([
        'reference', 'status', 'status_label', 'is_open', 'amount_minor', 'fee_minor', 'net_minor', 'currency', 'amount_display', 'net_display',
        'phone_last3', 'requested_at', 'approved_at', 'completed_at', 'receipt', 'reason', 'not_confirmed', 'not_confirmed_after_hours',
    ])
        ->and($data['phone_last3'])->toBe('675')
        ->and($data['receipt'])->toBe('ABC1234XYZ')
        ->and($data['amount_display'])->toBe('Ksh1,500')
        ->and(json_encode($data))->not->toContain('254712345675')->not->toContain('GW-SECRET');
});

it('answers someone else\'s reference and a reference that does not exist, or is malformed, with exactly the same thing', function (string $reference) {
    $other = payoutFor(User::factory()->create());
    $path = '/api/support/v1/withdrawals/'.($reference === 'theirs' ? $other->reference : $reference);

    $response = asMember($this, $this->member, $path);

    $response->assertNotFound()->assertExactJson(['error' => 'not_found']);
    expect($response->headers->get('Cache-Control'))->toContain('no-store');
})->with(['theirs', 'PO0000000000', 'PO12', 'not-a-reference', '%27%20OR%201%3D1']);

it('gives the same status, body and headers for theirs and for missing', function () {
    $other = payoutFor(User::factory()->create());

    $theirs = asMember($this, $this->member, '/api/support/v1/withdrawals/'.$other->reference);
    $missing = asMember($this, $this->member, '/api/support/v1/withdrawals/PO0000000000');

    expect($theirs->status())->toBe($missing->status())
        ->and($theirs->getContent())->toBe($missing->getContent())
        ->and($theirs->headers->get('Content-Type'))->toBe($missing->headers->get('Content-Type'));
});

it('never lets a query string choose whose withdrawals are listed', function () {
    $other = User::factory()->create();
    payoutFor($other);

    $response = asMember($this, $this->member, '/api/support/v1/withdrawals?user_id='.$other->id.'&member='.$other->id);

    expect($response->json('total'))->toBe(0)->and($response->json('data'))->toBe([]);
});

it('says "not confirmed" for a withdrawal being sent for too long, and never that anyone was alerted', function () {
    config(['payments.payout_stale_hours' => 6]);
    $stale = payoutFor($this->member, PayoutStatus::Processing, ['approved_at' => now()->subHours(7)]);
    $fresh = payoutFor($this->member, PayoutStatus::Processing, ['approved_at' => now()->subHour()]);

    $staleData = asMember($this, $this->member, '/api/support/v1/withdrawals/'.$stale->reference)->json('data');
    $freshData = asMember($this, $this->member, '/api/support/v1/withdrawals/'.$fresh->reference)->json('data');

    expect($staleData['not_confirmed'])->toBeTrue()->and($staleData['not_confirmed_after_hours'])->toBe(6)
        ->and($freshData['not_confirmed'])->toBeFalse()
        ->and(array_keys($staleData))->not->toContain('alerted', 'staff_alerted');
});

it('gives the recorded reason only for a withdrawal that did not go through', function () {
    $rejected = payoutFor($this->member, PayoutStatus::Rejected, ['failure_reason' => 'The phone number does not match the store name.']);
    $open = payoutFor($this->member, PayoutStatus::Requested, ['failure_reason' => 'left over']);

    expect(asMember($this, $this->member, '/api/support/v1/withdrawals/'.$rejected->reference)->json('data.reason'))->toBe('The phone number does not match the store name.')
        ->and(asMember($this, $this->member, '/api/support/v1/withdrawals/'.$open->reference)->json('data.reason'))->toBeNull();
});

it('is empty, not an error, for a member with no withdrawals', function () {
    asMember($this, $this->member, '/api/support/v1/withdrawals')->assertOk()->assertJsonPath('total', 0)->assertJsonPath('data', []);
});

it('writes nothing but its own audit row, and reads each payout without changing it', function () {
    $payout = payoutFor($this->member, PayoutStatus::Processing, ['approved_at' => now()->subHours(9), 'gateway_reference' => 'GW-1']);
    $before = DB::table('payouts')->get()->toJson();

    asMember($this, $this->member, '/api/support/v1/withdrawals');
    asMember($this, $this->member, '/api/support/v1/withdrawals/'.$payout->reference);

    expect(DB::table('payouts')->get()->toJson())->toBe($before)
        ->and(DB::table('ledger_entries')->count())->toBe(0)
        ->and(SupportReadAudit::count())->toBe(2);
});

it('can be switched off on its own', function () {
    config(['support.reads.capabilities.withdrawals' => false]);

    asMember($this, $this->member, '/api/support/v1/withdrawals')->assertNotFound()->assertExactJson(['error' => 'not_found']);
    // the rest of the read API is unaffected
    call($this, readCall('/api/support/v1/ping', ['claim' => claimFor($this->member)]), '/api/support/v1/ping')->assertOk();
});

it('is behind every check the read API has', function () {
    $path = '/api/support/v1/withdrawals';

    call($this, readCall($path, ['claim' => claimFor($this->member), 'secret' => 'wrong']), $path)->assertUnauthorized();
    call($this, readCall($path, ['claim' => false]), $path)->assertUnauthorized();
    config(['support.reads.enabled' => false]);
    call($this, readCall($path, ['claim' => claimFor($this->member)]), $path)->assertNotFound();
});
