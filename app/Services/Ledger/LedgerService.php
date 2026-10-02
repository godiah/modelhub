<?php

namespace App\Services\Ledger;

use App\Enums\LedgerAccountKind;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\LedgerTransaction;
use App\Models\Staff;
use App\Models\User;
use App\Support\Ledger\LedgerException;
use App\Support\Ledger\LedgerLine;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * The one way money is recorded. A posting is a set of debits and credits that balance exactly, written together or not at all. Nothing is
 * ever edited or deleted: a mistake is put right with a new, opposite posting. Balances are worked out from the entries.
 *
 * Every posting carries an idempotency key (for example "sale:payment:123"), so a payment callback that arrives twice, or a job that
 * runs twice, records the money once.
 */
class LedgerService
{
    /** The platform's own accounts: key => [name, kind]. They are the only accounts allowed to go below zero. */
    public const PLATFORM_ACCOUNTS = [
        'gateway' => ['Held at the payment gateway', LedgerAccountKind::Asset],
        'revenue' => ['Commission earned', LedgerAccountKind::Income],
        'payout_clearing' => ['Payouts on their way out', LedgerAccountKind::Liability],
        'payout_fees' => ['Payout fees charged', LedgerAccountKind::Income],
        'suspense' => ['Payments waiting to be sorted out', LedgerAccountKind::Liability],
    ];

    /** The two accounts every seller has. */
    public const USER_ACCOUNTS = [
        'pending' => 'Earnings in the hold period',
        'available' => 'Earnings available to withdraw',
    ];

    public function platformAccount(string $key): LedgerAccount
    {
        [$name, $kind] = self::PLATFORM_ACCOUNTS[$key] ?? throw new LedgerException("There is no platform account [{$key}].");

        return LedgerAccount::firstOrCreate(['code' => "platform.{$key}"], ['name' => $name, 'kind' => $kind, 'currency' => $this->currency(), 'allow_negative' => true]);
    }

    /** One of a member's accounts: 'pending' while their earnings are in the hold, 'available' once they can withdraw. */
    public function userAccount(User $user, string $which): LedgerAccount
    {
        $name = self::USER_ACCOUNTS[$which] ?? throw new LedgerException("There is no member account [{$which}].");

        return LedgerAccount::firstOrCreate(['code' => "user.{$user->id}.{$which}"], ['name' => $name, 'kind' => LedgerAccountKind::Liability, 'user_id' => $user->id, 'currency' => $this->currency(), 'allow_negative' => false]);
    }

    /**
     * Record a balanced set of lines. Returns the transaction; if the idempotency key was already posted it returns that one and writes nothing.
     *
     * @param  list<LedgerLine>  $lines
     * @param  array<string, mixed>  $meta
     *
     * @throws LedgerException when the lines do not balance, are malformed, or would take a member's account below zero
     */
    public function post(string $type, string $idempotencyKey, array $lines, string $description, ?Model $reference = null, array $meta = [], ?Staff $by = null): LedgerTransaction
    {
        if ($existing = LedgerTransaction::where('idempotency_key', $idempotencyKey)->first()) {
            return $existing;
        }

        $this->check($lines);

        try {
            return DB::transaction(function () use ($type, $idempotencyKey, $lines, $description, $reference, $meta, $by) {
                // Lock the accounts in a fixed order, so two postings touching the same accounts cannot deadlock or overdraw together
                $accounts = LedgerAccount::whereIn('id', collect($lines)->map(fn (LedgerLine $l) => $l->account->id)->unique()->sort()->values())->orderBy('id')->lockForUpdate()->get()->keyBy('id');

                $transaction = LedgerTransaction::create([
                    'type' => $type, 'idempotency_key' => $idempotencyKey, 'description' => mb_substr($description, 0, 255), 'meta' => $meta ?: null,
                    'reference_type' => $reference?->getMorphClass(), 'reference_id' => $reference?->getKey(), 'staff_id' => $by?->id, 'occurred_at' => now(),
                ]);

                foreach ($lines as $line) {
                    LedgerEntry::create(['transaction_id' => $transaction->id, 'account_id' => $line->account->id, 'direction' => $line->direction, 'amount_minor' => $line->amountMinor, 'currency' => $line->account->currency]);
                }

                foreach ($accounts as $account) {
                    if (! $account->allow_negative && $account->balanceMinor() < 0) {
                        throw new LedgerException("{$account->name} cannot go below zero.");
                    }
                }

                return $transaction;
            });
        } catch (UniqueConstraintViolationException) {
            // The same posting arrived twice at once; the other one won
            return LedgerTransaction::where('idempotency_key', $idempotencyKey)->firstOrFail();
        }
    }

    /** What a member has, in minor units: ['pending' => ..., 'available' => ...]. */
    public function balances(User $user): array
    {
        return ['pending' => $this->userAccount($user, 'pending')->balanceMinor(), 'available' => $this->userAccount($user, 'available')->balanceMinor()];
    }

    /**
     * The whole ledger in one line each: total debits and total credits across every entry. They are always equal, and the
     * platform's books are wrong the moment they are not.
     *
     * @return array{debits: int, credits: int, balanced: bool}
     */
    public function trialBalance(): array
    {
        $debits = (int) LedgerEntry::where('direction', 'debit')->sum('amount_minor');
        $credits = (int) LedgerEntry::where('direction', 'credit')->sum('amount_minor');

        return ['debits' => $debits, 'credits' => $credits, 'balanced' => $debits === $credits];
    }

    /** @param  list<LedgerLine>  $lines */
    private function check(array $lines): void
    {
        if (count($lines) < 2) {
            throw new LedgerException('A posting needs at least two lines.');
        }

        $currencies = [];
        $debits = $credits = 0;

        foreach ($lines as $line) {
            if (! $line instanceof LedgerLine) {
                throw new LedgerException('Every line must be a LedgerLine.');
            }
            if ($line->amountMinor <= 0) {
                throw new LedgerException('Every amount must be a positive whole number of minor units.');
            }

            $currencies[$line->account->currency] = true;
            $line->direction === 'debit' ? $debits += $line->amountMinor : $credits += $line->amountMinor;
        }

        if (count($currencies) > 1) {
            throw new LedgerException('A posting cannot mix currencies.');
        }

        if ($debits !== $credits) {
            throw new LedgerException("The posting does not balance: debits {$debits}, credits {$credits}.");
        }
    }

    private function currency(): string
    {
        return config('marketplace.currency');
    }
}
