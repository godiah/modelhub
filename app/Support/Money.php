<?php

namespace App\Support;

class Money
{
    /**
     * Format an amount with the app's currency symbol, e.g. "Ksh1,250.00".
     */
    public static function format(int|float|string|null $amount, int $decimals = 2): string
    {
        return config('app.currency_symbol').number_format((float) $amount, $decimals);
    }

    /** Format an amount held in minor units (cents), e.g. 125000 -> "Ksh1,250.00". */
    public static function formatMinor(int $minor, int $decimals = 2): string
    {
        return self::format($minor / 100, $decimals);
    }
}
