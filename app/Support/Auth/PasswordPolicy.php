<?php

namespace App\Support\Auth;

use App\Support\Settings\PlatformSettings;
use Illuminate\Validation\Rules\Password;

/** What a new password must look like, from the platform's password settings. Staff always need at least 12 characters. */
final class PasswordPolicy
{
    private const STAFF_MINIMUM = 12;

    public static function rule(bool $staff = false): Password
    {
        $rule = Password::min(self::length($staff));

        if ($staff || PlatformSettings::bool('security.password_mixed_case')) {
            $rule->letters();
        }
        if (PlatformSettings::bool('security.password_mixed_case')) {
            $rule->mixedCase();
        }
        if ($staff || PlatformSettings::bool('security.password_number')) {
            $rule->numbers();
        }
        if (PlatformSettings::bool('security.password_symbol')) {
            $rule->symbols();
        }

        return $rule;
    }

    /** The rule in words, for the hint under a password field. */
    public static function describe(bool $staff = false): string
    {
        $parts = ['at least '.self::length($staff).' characters'];

        if (PlatformSettings::bool('security.password_mixed_case')) {
            $parts[] = 'upper and lower case letters';
        } elseif ($staff) {
            $parts[] = 'letters';
        }
        if ($staff || PlatformSettings::bool('security.password_number')) {
            $parts[] = 'a number';
        }
        if (PlatformSettings::bool('security.password_symbol')) {
            $parts[] = 'a symbol';
        }

        $last = array_pop($parts);

        return ucfirst($parts === [] ? $last : implode(', ', $parts).' and '.$last).'.';
    }

    private static function length(bool $staff): int
    {
        return max(PlatformSettings::int('security.password_min_length'), $staff ? self::STAFF_MINIMUM : 0);
    }
}
