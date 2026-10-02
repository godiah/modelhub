<?php

namespace App\Support;

/** Kenyan mobile numbers, in the one form M-Pesa wants: 2547XXXXXXXX or 2541XXXXXXXX. */
final class Phone
{
    /** Accepts 0712 345 678, +254712345678, 254712345678 and 712345678; returns null for anything that is not a Kenyan mobile number. */
    public static function msisdn(?string $input): ?string
    {
        $digits = preg_replace('/[\s\-().]/', '', (string) $input);
        $digits = ltrim($digits, '+');

        return preg_match('/^(?:254|0)?([17]\d{8})$/', $digits, $m) ? '254'.$m[1] : null;
    }

    /** 254712345678 as 0712 345 678, for showing back to the person. */
    public static function local(string $msisdn): string
    {
        return preg_replace('/^254(\d{3})(\d{3})(\d{3})$/', '0$1 $2 $3', $msisdn);
    }
}
