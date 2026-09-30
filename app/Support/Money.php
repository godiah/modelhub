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
}
