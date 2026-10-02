<?php

namespace App\Support\Ledger;

use App\Models\LedgerAccount;

/** One side of a posting: an amount (minor units, always positive) debited or credited to an account. */
final readonly class LedgerLine
{
    private function __construct(public LedgerAccount $account, public string $direction, public int $amountMinor) {}

    public static function debit(LedgerAccount $account, int $amountMinor): self
    {
        return new self($account, 'debit', $amountMinor);
    }

    public static function credit(LedgerAccount $account, int $amountMinor): self
    {
        return new self($account, 'credit', $amountMinor);
    }
}
