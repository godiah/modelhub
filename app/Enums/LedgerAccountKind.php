<?php

namespace App\Enums;

/** What an account is, which decides the side its balance grows on: assets grow with debits, liabilities and income with credits. */
enum LedgerAccountKind: string
{
    /** Money the platform holds, such as what sits with the payment gateway. */
    case Asset = 'asset';

    /** Money the platform owes: a seller's earnings, or a payout on its way out. */
    case Liability = 'liability';

    /** What the platform has earned: commission and fees. */
    case Income = 'income';

    public function growsWith(): string
    {
        return $this === self::Asset ? 'debit' : 'credit';
    }
}
