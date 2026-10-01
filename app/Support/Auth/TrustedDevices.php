<?php

namespace App\Support\Auth;

use App\Support\Settings\PlatformSettings;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/**
 * "Trust this device": after a correct code the browser gets a random token in a cookie (and we keep only its hash), so for
 * a while it skips the sign-in code. The platform decides whether this is offered and for how long; a password change,
 * a reset of the account's two-step setup, or switching the feature off ends it.
 */
final class TrustedDevices
{
    public static function enabled(): bool
    {
        return PlatformSettings::bool('security.trusted_devices_enabled');
    }

    public static function recognises(string $guard, Authenticatable $account): bool
    {
        $token = request()->cookie(self::cookie($guard));

        if (! self::enabled() || ! is_string($token) || $token === '') {
            return false;
        }

        $device = $account->trustedDevices()->where('token_hash', hash('sha256', $token))->where('expires_at', '>', now())->first();
        $device?->forceFill(['last_used_at' => now()])->save();

        return $device !== null;
    }

    public static function remember(string $guard, Authenticatable $account): void
    {
        if (! self::enabled()) {
            return;
        }

        $days = PlatformSettings::int('security.trusted_device_days');
        $token = Str::random(60);

        $account->trustedDevices()->create([
            'token_hash' => hash('sha256', $token),
            'user_agent' => Str::limit((string) request()->userAgent(), 250, ''),
            'ip_address' => request()->ip(),
            'last_used_at' => now(),
            'expires_at' => now()->addDays($days),
        ]);

        Cookie::queue(Cookie::make(self::cookie($guard), $token, $days * 1440, '/', null, request()->isSecure(), true, false, 'lax'));
    }

    private static function cookie(string $guard): string
    {
        return 'trusted_device_'.$guard;
    }
}
