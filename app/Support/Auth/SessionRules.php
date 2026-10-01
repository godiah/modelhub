<?php

namespace App\Support\Auth;

use App\Support\Settings\PlatformSettings;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * The platform's session rules, for members and staff separately: how long a session may sit idle, the longest it may last,
 * and whether signing in somewhere else ends the others. Each sign-in stamps the session (start time, last activity, and a
 * token that is also kept on the account); EnforceSessionRules reads the stamp on every request.
 */
final class SessionRules
{
    private const AUDIENCES = ['web' => 'member', 'staff' => 'staff'];

    public static function audience(string $guard): string
    {
        return self::AUDIENCES[$guard];
    }

    /** Called when someone signs in (or is signed in from a "keep me signed in" cookie). */
    public static function start(string $guard, Authenticatable $account): void
    {
        $session = session();
        $token = Str::random(40);

        $account->forceFill(['session_token' => $token])->saveQuietly();
        $session->put("session_rules.{$guard}", ['started' => now()->timestamp, 'last' => now()->timestamp, 'token' => $token]);
    }

    /** "Keep me signed in" would outlive a session limit, so it is switched off whenever there is one. */
    public static function allowsRemember(string $guard): bool
    {
        return PlatformSettings::int('security.'.self::audience($guard).'_max_hours') === 0;
    }

    /**
     * Why this request's session for the guard is no longer allowed, or null if it is fine (and then it counts as activity).
     */
    public static function violation(string $guard, Request $request): ?string
    {
        $account = Auth::guard($guard)->user();
        $audience = self::audience($guard);
        $maxHours = PlatformSettings::int("security.{$audience}_max_hours");
        $state = $request->session()->get("session_rules.{$guard}");

        if ($state === null) {
            // Signed in without our stamp: from a "keep me signed in" cookie (or a session older than these rules)
            if ($maxHours > 0 && Auth::guard($guard)->viaRemember()) {
                return 'Your session reached its time limit. Please sign in again.';
            }

            self::start($guard, $account);

            return null;
        }

        $idle = PlatformSettings::int("security.{$audience}_idle_minutes");

        if (now()->timestamp - $state['last'] > $idle * 60) {
            return 'You were signed out because you were inactive for a while.';
        }

        if ($maxHours > 0 && now()->timestamp - $state['started'] > $maxHours * 3600) {
            return 'Your session reached its time limit. Please sign in again.';
        }

        if (PlatformSettings::bool("security.{$audience}_single_session") && ! hash_equals((string) $account->session_token, (string) $state['token'])) {
            return 'You were signed out because this account signed in on another device.';
        }

        $request->session()->put("session_rules.{$guard}.last", now()->timestamp);

        return null;
    }

    public static function forget(string $guard, Request $request): void
    {
        $request->session()->forget("session_rules.{$guard}");
    }
}
