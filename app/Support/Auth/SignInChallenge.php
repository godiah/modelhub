<?php

namespace App\Support\Auth;

use App\Support\Settings\PlatformSettings;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Signing in, for both guards ("web" for members, "staff"). The password is checked first without signing anyone in; if the
 * account needs a code (its own choice, or the platform rule) and the browser is not trusted, the person is parked in a
 * "challenge" in the session and only signed in once the code is right. Wrong codes are counted per account, so starting the
 * sign-in over does not hand out fresh guesses.
 */
final class SignInChallenge
{
    private const SESSION_KEY = 'sign_in_challenge';

    /** The step must be finished within this many minutes of the password. */
    private const LIFETIME_MINUTES = 10;

    /** The account for these credentials, if they are right. Nobody is signed in. */
    public static function authenticate(string $guard, array $credentials): ?Authenticatable
    {
        $provider = Auth::guard($guard)->getProvider();
        $account = $provider->retrieveByCredentials(Arr::except($credentials, 'password'));

        return $account && $provider->validateCredentials($account, ['password' => $credentials['password']]) ? $account : null;
    }

    /**
     * Sign in now, or start the code step. True means the person is signed in. Someone who has used up their wrong codes is
     * told so here, at the password, instead of after typing a code that would not be looked at. $errorKey is the form field
     * the message goes on.
     */
    public static function attempt(string $guard, Authenticatable $account, bool $remember, string $errorKey = 'email'): bool
    {
        if ($account->requiresSecondFactor() && ! TrustedDevices::recognises($guard, $account)) {
            if (RateLimiter::tooManyAttempts(self::wrongCodeKey($guard, $account->getAuthIdentifier()), PlatformSettings::int('security.otp_max_attempts'))) {
                throw ValidationException::withMessages([$errorKey => self::lockedOutMessage($guard, $account->getAuthIdentifier())]);
            }

            self::begin($guard, $account, $remember);

            return false;
        }

        self::signIn($guard, $account, $remember);

        return true;
    }

    public static function signIn(string $guard, Authenticatable $account, bool $remember): void
    {
        Auth::guard($guard)->login($account, $remember && SessionRules::allowsRemember($guard));
        session()->regenerate();
        SessionRules::start($guard, $account);
    }

    /* ---------------------------------------------------------------- the code step */

    private static function begin(string $guard, Authenticatable $account, bool $remember): void
    {
        $method = $account->usesAuthenticator() ? 'authenticator' : 'email';

        session()->put(self::SESSION_KEY, [
            'guard' => $guard, 'id' => $account->getAuthIdentifier(), 'remember' => $remember, 'method' => $method,
            'expires_at' => now()->addMinutes(self::LIFETIME_MINUTES)->timestamp,
        ]);

        if ($method === 'email') {
            $account->generateTwoFactorCode();
        }
    }

    /** The unfinished sign-in for this guard, if there is a live one. @return array{guard: string, id: mixed, remember: bool, method: string}|null */
    public static function pending(string $guard): ?array
    {
        $challenge = session()->get(self::SESSION_KEY);

        if (! $challenge || $challenge['guard'] !== $guard) {
            return null;
        }

        if ($challenge['expires_at'] < now()->timestamp) {
            self::cancel();

            return null;
        }

        return $challenge;
    }

    public static function account(array $challenge): ?Authenticatable
    {
        return Auth::guard($challenge['guard'])->getProvider()->retrieveById($challenge['id']);
    }

    /** Send a fresh emailed code (a person using the email method who never got it, or lost it). Once every 30 seconds. */
    public static function resend(string $guard): bool
    {
        $challenge = self::pending($guard);
        $account = $challenge && $challenge['method'] === 'email' ? self::account($challenge) : null;

        if (! $account || RateLimiter::tooManyAttempts("otp-resend:{$guard}:{$challenge['id']}", 1)) {
            return false;
        }

        RateLimiter::hit("otp-resend:{$guard}:{$challenge['id']}", 30);
        $account->generateTwoFactorCode();

        return true;
    }

    /**
     * Check what the person typed: the 6-digit code (from their app, or emailed) or a recovery code. On success the code is used
     * up. Throws a validation error on the `code` field when it is wrong, expired or too many wrong ones were given.
     */
    public static function verify(string $guard, string $input): void
    {
        $challenge = self::pending($guard) ?? throw ValidationException::withMessages(['code' => 'This sign-in has expired. Please start again.']);
        $account = self::account($challenge) ?? throw ValidationException::withMessages(['code' => 'This sign-in has expired. Please start again.']);

        $key = self::wrongCodeKey($guard, $challenge['id']);

        if (RateLimiter::tooManyAttempts($key, PlatformSettings::int('security.otp_max_attempts'))) {
            self::cancel();

            throw ValidationException::withMessages(['code' => self::lockedOutMessage($guard, $challenge['id'])]);
        }

        $code = preg_replace('/\s+/', '', $input);

        $valid = preg_match('/^\d{6}$/', $code)
            ? ($challenge['method'] === 'authenticator' ? $account->verifyAuthenticatorCode($code) : $account->verifyTwoFactorCode($code))
            : $account->consumeRecoveryCode($input);

        if (! $valid) {
            RateLimiter::hit($key, PlatformSettings::int('security.signin_lockout_minutes') * 60);

            throw ValidationException::withMessages(['code' => $challenge['method'] === 'authenticator' ? 'That code is not right. Codes change every 30 seconds.' : 'That code is not right or has expired.']);
        }

        RateLimiter::clear($key);
        $account->clearTwoFactorCode();
    }

    private static function wrongCodeKey(string $guard, mixed $id): string
    {
        return "otp:{$guard}:{$id}";
    }

    private static function lockedOutMessage(string $guard, mixed $id): string
    {
        $minutes = max(1, (int) ceil(RateLimiter::availableIn(self::wrongCodeKey($guard, $id)) / 60));

        return "Too many wrong codes. Try signing in again in {$minutes} ".($minutes === 1 ? 'minute' : 'minutes').'.';
    }

    /** The code was right: sign in, and trust this browser if the person asked and the platform allows it. */
    public static function complete(string $guard, bool $trustDevice): Authenticatable
    {
        $challenge = self::pending($guard) ?? throw ValidationException::withMessages(['code' => 'This sign-in has expired. Please start again.']);
        $account = self::account($challenge);

        self::cancel();
        self::signIn($guard, $account, $challenge['remember']);

        if ($trustDevice) {
            TrustedDevices::remember($guard, $account);
        }

        return $account;
    }

    public static function cancel(): void
    {
        session()->forget(self::SESSION_KEY);
    }
}
