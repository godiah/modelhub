<?php

use App\Enums\LedgerAccountKind;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\LedgerTransaction;
use App\Models\User;
use App\Services\Ledger\LedgerService;
use App\Support\Ledger\LedgerException;
use App\Support\Ledger\LedgerLine;

/*
 * The ledger: balanced, idempotent, append-only postings, with balances worked out from the entries.
 */

beforeEach(function () {
    $this->ledger = app(LedgerService::class);
    $this->seller = User::factory()->create();
    $this->gateway = $this->ledger->platformAccount('gateway');
    $this->revenue = $this->ledger->platformAccount('revenue');
    $this->pending = $this->ledger->userAccount($this->seller, 'pending');
    $this->available = $this->ledger->userAccount($this->seller, 'available');
});

/** A sale of KES 1,000 (100000 minor units) at 15% commission: 85000 to the seller (held), 15000 to the platform. */
function postSale(string $key = 'sale:1', int $price = 100000, int $commission = 15000)
{
    $ledger = app(LedgerService::class);

    return $ledger->post('sale', $key, [
        LedgerLine::debit($ledger->platformAccount('gateway'), $price),
        LedgerLine::credit($ledger->userAccount(test()->seller, 'pending'), $price - $commission),
        LedgerLine::credit($ledger->platformAccount('revenue'), $commission),
    ], 'Sale of a model');
}

it('creates each account once, with the right kind and rules', function () {
    expect($this->ledger->platformAccount('gateway')->is($this->gateway))->toBeTrue()->and(LedgerAccount::count())->toBe(4)
        ->and($this->gateway->kind)->toBe(LedgerAccountKind::Asset)->and($this->gateway->allow_negative)->toBeTrue()
        ->and($this->revenue->kind)->toBe(LedgerAccountKind::Income)
        ->and($this->pending->kind)->toBe(LedgerAccountKind::Liability)->and($this->pending->allow_negative)->toBeFalse()->and($this->pending->user_id)->toBe($this->seller->id)
        ->and($this->pending->code)->toBe("user.{$this->seller->id}.pending")->and($this->pending->currency)->toBe('KES');
});

it('refuses an account it does not know', function () {
    $this->ledger->platformAccount('mystery');
})->throws(LedgerException::class, 'There is no platform account [mystery].');

it('records a balanced posting and works balances out from its entries', function () {
    $tx = postSale();

    expect($tx->entries)->toHaveCount(3)->and($this->gateway->balanceMinor())->toBe(100000)->and($this->pending->balanceMinor())->toBe(85000)->and($this->revenue->balanceMinor())->toBe(15000)
        ->and($this->ledger->balances($this->seller))->toBe(['pending' => 85000, 'available' => 0])
        ->and($tx->type)->toBe('sale')->and($tx->occurred_at)->not->toBeNull();
});

it('refuses a posting that does not balance, writing nothing', function () {
    expect(fn () => $this->ledger->post('sale', 'bad:1', [LedgerLine::debit($this->gateway, 1000), LedgerLine::credit($this->pending, 900)], 'Off by 100'))
        ->toThrow(LedgerException::class, 'does not balance: debits 1000, credits 900');

    expect(LedgerTransaction::count())->toBe(0)->and(LedgerEntry::count())->toBe(0);
});

it('refuses malformed postings', function () {
    $post = fn (array $lines) => fn () => $this->ledger->post('t', 'k'.random_int(1, 999999), $lines, 'x');

    expect($post([LedgerLine::debit($this->gateway, 100)]))->toThrow(LedgerException::class, 'at least two lines')
        ->and($post([LedgerLine::debit($this->gateway, 0), LedgerLine::credit($this->pending, 0)]))->toThrow(LedgerException::class, 'positive whole number')
        ->and($post([LedgerLine::debit($this->gateway, -5), LedgerLine::credit($this->pending, -5)]))->toThrow(LedgerException::class, 'positive whole number')
        ->and($post([LedgerLine::debit($this->gateway, 100), 'not a line']))->toThrow(LedgerException::class, 'must be a LedgerLine');

    $dollars = LedgerAccount::create(['code' => 'platform.usd', 'name' => 'USD', 'kind' => LedgerAccountKind::Asset, 'currency' => 'USD', 'allow_negative' => true]);
    expect($post([LedgerLine::debit($dollars, 100), LedgerLine::credit($this->pending, 100)]))->toThrow(LedgerException::class, 'cannot mix currencies');
    expect(LedgerTransaction::count())->toBe(0);
});

it('posts the same event once, however many times it arrives', function () {
    $first = postSale('sale:payment:7');
    $second = postSale('sale:payment:7');

    expect($second->is($first))->toBeTrue()->and(LedgerTransaction::count())->toBe(1)->and(LedgerEntry::count())->toBe(3)->and($this->pending->balanceMinor())->toBe(85000);
});

it('moves earnings from pending to available when the hold ends, and refuses to release more than is pending', function () {
    postSale();

    $this->ledger->post('release', 'release:payment:1', [LedgerLine::debit($this->pending, 85000), LedgerLine::credit($this->available, 85000)], 'Hold ended');
    expect($this->ledger->balances($this->seller))->toBe(['pending' => 0, 'available' => 85000]);

    expect(fn () => $this->ledger->post('release', 'release:payment:2', [LedgerLine::debit($this->pending, 1), LedgerLine::credit($this->available, 1)], 'Too much'))
        ->toThrow(LedgerException::class, 'Earnings in the hold period cannot go below zero.');
    expect($this->ledger->balances($this->seller))->toBe(['pending' => 0, 'available' => 85000])->and(LedgerTransaction::where('idempotency_key', 'release:payment:2')->exists())->toBeFalse();
});

it('never lets a member withdraw more than they have', function () {
    postSale();
    $this->ledger->post('release', 'r1', [LedgerLine::debit($this->pending, 85000), LedgerLine::credit($this->available, 85000)], 'Hold ended');
    $clearing = $this->ledger->platformAccount('payout_clearing');
    $withdraw = fn (string $key) => $this->ledger->post('payout_requested', $key, [LedgerLine::debit($this->available, 60000), LedgerLine::credit($clearing, 60000)], 'Withdrawal');

    $withdraw('w1');
    expect(fn () => $withdraw('w2'))->toThrow(LedgerException::class)->and($this->available->balanceMinor())->toBe(25000)->and($clearing->balanceMinor())->toBe(60000);
});

it('keeps the books balanced through a whole sale, release and payout, with the platform holding what is left', function () {
    $clearing = $this->ledger->platformAccount('payout_clearing');
    $fees = $this->ledger->platformAccount('payout_fees');

    postSale();
    expect($this->ledger->trialBalance())->toBe(['debits' => 100000, 'credits' => 100000, 'balanced' => true]);

    $this->ledger->post('release', 'r1', [LedgerLine::debit($this->pending, 85000), LedgerLine::credit($this->available, 85000)], 'Hold ended');
    // The seller withdraws everything; the transfer fee (KES 30) comes out of it, the rest leaves the gateway
    $this->ledger->post('payout_requested', 'w1', [LedgerLine::debit($this->available, 85000), LedgerLine::credit($clearing, 85000)], 'Withdrawal requested');
    $this->ledger->post('payout_paid', 'w1:paid', [LedgerLine::debit($clearing, 85000), LedgerLine::credit($this->gateway, 82000), LedgerLine::credit($fees, 3000)], 'Withdrawal sent');

    expect($this->ledger->trialBalance()['balanced'])->toBeTrue()
        ->and($this->gateway->balanceMinor())->toBe(18000)         // 100000 in, 82000 out
        ->and($this->revenue->balanceMinor())->toBe(15000)          // commission
        ->and($fees->balanceMinor())->toBe(3000)                    // fee kept
        ->and($this->ledger->balances($this->seller))->toBe(['pending' => 0, 'available' => 0])
        ->and($clearing->balanceMinor())->toBe(0)
        // What the platform holds equals what it has earned plus what it still owes
        ->and($this->gateway->balanceMinor())->toBe($this->revenue->balanceMinor() + $fees->balanceMinor());
});

it('puts a failed payout back with the seller', function () {
    postSale();
    $clearing = $this->ledger->platformAccount('payout_clearing');
    $this->ledger->post('release', 'r1', [LedgerLine::debit($this->pending, 85000), LedgerLine::credit($this->available, 85000)], 'Hold ended');
    $this->ledger->post('payout_requested', 'w1', [LedgerLine::debit($this->available, 85000), LedgerLine::credit($clearing, 85000)], 'Withdrawal requested');

    $this->ledger->post('payout_failed', 'w1:failed', [LedgerLine::debit($clearing, 85000), LedgerLine::credit($this->available, 85000)], 'Withdrawal failed, returned');

    expect($this->available->balanceMinor())->toBe(85000)->and($clearing->balanceMinor())->toBe(0)->and($this->ledger->trialBalance()['balanced'])->toBeTrue();
});

it('reverses a refunded sale with an opposite posting and keeps the original', function () {
    postSale();

    $this->ledger->post('refund', 'refund:payment:1', [
        LedgerLine::debit($this->pending, 85000), LedgerLine::debit($this->revenue, 15000), LedgerLine::credit($this->gateway, 100000),
    ], 'Refund of a sale');

    expect($this->gateway->balanceMinor())->toBe(0)->and($this->pending->balanceMinor())->toBe(0)->and($this->revenue->balanceMinor())->toBe(0)
        ->and(LedgerTransaction::count())->toBe(2)->and($this->ledger->trialBalance()['balanced'])->toBeTrue();
});

it('is append-only: entries and transactions cannot be changed or removed', function () {
    $tx = postSale();
    $entry = $tx->entries->first();

    expect(fn () => $entry->update(['amount_minor' => 1]))->toThrow(LogicException::class, 'cannot be changed')
        ->and(fn () => $entry->delete())->toThrow(LogicException::class, 'cannot be deleted')
        ->and(fn () => $tx->update(['description' => 'edited']))->toThrow(LogicException::class, 'cannot be changed')
        ->and(fn () => $tx->delete())->toThrow(LogicException::class, 'cannot be deleted');

    expect(LedgerEntry::count())->toBe(3)->and($this->pending->fresh()->balanceMinor())->toBe(85000);
});

it('records what a posting was about and who made it', function () {
    $staff = staffWith('Super admin');
    $tx = $this->ledger->post('adjustment', 'adj:1', [LedgerLine::debit($this->gateway, 500), LedgerLine::credit($this->revenue, 500)], 'Correction', $this->seller, ['why' => 'test'], $staff);

    expect($tx->reference->is($this->seller))->toBeTrue()->and($tx->meta)->toBe(['why' => 'test'])->and($tx->staff->is($staff))->toBeTrue()->and($tx->description)->toBe('Correction');
});

it('keeps each member\'s money apart', function () {
    $other = User::factory()->create();
    postSale('sale:a');
    $this->ledger->post('sale', 'sale:b', [
        LedgerLine::debit($this->gateway, 50000), LedgerLine::credit($this->ledger->userAccount($other, 'pending'), 42500), LedgerLine::credit($this->revenue, 7500),
    ], 'Another sale');

    expect($this->ledger->balances($this->seller)['pending'])->toBe(85000)->and($this->ledger->balances($other)['pending'])->toBe(42500)->and($this->gateway->balanceMinor())->toBe(150000);
});
