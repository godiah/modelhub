<?php

namespace App\Models\Concerns;

use App\Mail\TwoFactorCode;
use App\Models\TrustedDevice;
use App\Support\Auth\Totp;
use App\Support\Settings\PlatformSettings;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * The second sign-in step, shared by members and staff: a 6-digit code sent by email, or one from an authenticator app (with
 * recovery codes for a lost phone). Whether the step is needed comes from the account's own choice, or the platform rule.
 *
 * Needs the columns two_factor_code, two_factor_expires_at, two_factor_secret, two_factor_confirmed_at,
 * two_factor_recovery_codes and two_factor_last_step.
 */
trait HasTwoFactor
{
    /** The recovery codes handed out when an authenticator app is set up. */
    public const RECOVERY_CODE_COUNT = 8;

    /** Does the platform require a sign-in code from this kind of account? */
    abstract protected function platformRequiresSecondFactor(): bool;

    /** Did this account turn on emailed codes itself? Members can; staff are covered by the platform rule. */
    protected function emailCodesOptedIn(): bool
    {
        return false;
    }

    protected function initializeHasTwoFactor(): void
    {
        $this->mergeCasts([
            'two_factor_expires_at' => 'datetime',
            'two_factor_secret' => 'encrypted',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_recovery_codes' => 'encrypted:array',
        ]);

        $this->makeHidden(['two_factor_code', 'two_factor_secret', 'two_factor_recovery_codes', 'session_token']);
    }

    protected static function bootHasTwoFactor(): void
    {
        // Whoever changes a password also ends every device that was trusted with the old one
        static::updated(function ($account) {
            if ($account->wasChanged('password')) {
                $account->trustedDevices()->delete();
            }
        });
    }

    public function trustedDevices(): MorphMany
    {
        return $this->morphMany(TrustedDevice::class, 'authenticatable');
    }

    /* ---------------------------------------------------------------- what the sign-in needs */

    /** Must this account give a code when it signs in? */
    public function requiresSecondFactor(): bool
    {
        return $this->emailCodesOptedIn() || $this->hasAuthenticator() || $this->platformRequiresSecondFactor();
    }

    /** Has an authenticator app been set up and confirmed? (Whether the platform still allows it is `usesAuthenticator`.) */
    public function hasAuthenticator(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }

    /** Is the authenticator app the way this account gets its code? Otherwise it is emailed. */
    public function usesAuthenticator(): bool
    {
        return $this->hasAuthenticator() && PlatformSettings::bool('security.authenticator_allowed');
    }

    /** Is a code, from any source, part of this account's sign-in? Used to show what is in force on the account page. */
    public function secondFactorMethod(): ?string
    {
        return ! $this->requiresSecondFactor() ? null : ($this->usesAuthenticator() ? 'authenticator' : 'email');
    }

    /* ---------------------------------------------------------------- emailed codes */

    public function generateTwoFactorCode(): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->forceFill(['two_factor_code' => Hash::make($code), 'two_factor_expires_at' => now()->addMinutes(5)])->save();

        Mail::to($this->email)->queue(new TwoFactorCode($code));
    }

    public function verifyTwoFactorCode(string $code): bool
    {
        if (! $this->two_factor_code || ! $this->two_factor_expires_at) {
            return false;
        }

        if ($this->two_factor_expires_at->isPast()) {
            $this->clearTwoFactorCode();

            return false;
        }

        return Hash::check($code, $this->two_factor_code);
    }

    public function clearTwoFactorCode(): void
    {
        $this->forceFill(['two_factor_code' => null, 'two_factor_expires_at' => null])->save();
    }

    public function enableTwoFactor(): void
    {
        $this->forceFill(['two_factor_enabled' => true])->save();
        $this->clearTwoFactorCode();
    }

    public function disableTwoFactor(): void
    {
        $this->forceFill(['two_factor_enabled' => false])->save();
        $this->clearTwoFactorCode();
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->emailCodesOptedIn();
    }

    /* ---------------------------------------------------------------- authenticator app */

    /** Start setting up an app: a new secret that does nothing until a code from the app confirms it. */
    public function startAuthenticatorSetup(): string
    {
        $secret = Totp::generateSecret();
        $this->forceFill(['two_factor_secret' => $secret, 'two_factor_confirmed_at' => null, 'two_factor_recovery_codes' => null, 'two_factor_last_step' => null])->save();

        return $secret;
    }

    public function authenticatorSetupPending(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at === null;
    }

    /**
     * Confirm the setup with a code from the app. Returns the recovery codes (shown once, never stored in the clear), or null
     * when the code is wrong.
     *
     * @return list<string>|null
     */
    public function confirmAuthenticator(string $code): ?array
    {
        if (! $this->authenticatorSetupPending() || ($step = Totp::verify($this->two_factor_secret, $code)) === null) {
            return null;
        }

        $this->forceFill(['two_factor_confirmed_at' => now(), 'two_factor_last_step' => $step])->save();

        return $this->regenerateRecoveryCodes();
    }

    /** A code from the app, accepted once. */
    public function verifyAuthenticatorCode(string $code): bool
    {
        if (! $this->hasAuthenticator() || ($step = Totp::verify($this->two_factor_secret, $code, $this->two_factor_last_step)) === null) {
            return false;
        }

        $this->forceFill(['two_factor_last_step' => $step])->save();

        return true;
    }

    /** @return list<string> */
    public function regenerateRecoveryCodes(): array
    {
        $codes = collect(range(1, self::RECOVERY_CODE_COUNT))->map(fn () => Str::lower(Str::random(5).'-'.Str::random(5)))->all();

        $this->forceFill(['two_factor_recovery_codes' => array_map(fn ($code) => self::hashRecoveryCode($code), $codes)])->save();

        return $codes;
    }

    /** Use up a recovery code, if it is one. */
    public function consumeRecoveryCode(string $code): bool
    {
        $hash = self::hashRecoveryCode($code);
        $codes = $this->two_factor_recovery_codes ?? [];

        if (! in_array($hash, $codes, true)) {
            return false;
        }

        $this->forceFill(['two_factor_recovery_codes' => array_values(array_diff($codes, [$hash]))])->save();

        return true;
    }

    public function recoveryCodesRemaining(): int
    {
        return count($this->two_factor_recovery_codes ?? []);
    }

    /** Remove the app and everything that goes with it. */
    public function removeAuthenticator(): void
    {
        $this->forceFill(['two_factor_secret' => null, 'two_factor_confirmed_at' => null, 'two_factor_recovery_codes' => null, 'two_factor_last_step' => null])->save();
    }

    /** A person who lost their phone and codes: back to a clean slate (emailed codes still apply if the platform requires them). */
    public function resetTwoFactor(): void
    {
        $this->removeAuthenticator();
        $this->forceFill(['two_factor_code' => null, 'two_factor_expires_at' => null])->save();
        $this->trustedDevices()->delete();

        if ($this->emailCodesOptedIn()) {
            $this->disableTwoFactor();
        }
    }

    private static function hashRecoveryCode(string $code): string
    {
        return hash_hmac('sha256', Str::lower(preg_replace('/[^a-z0-9]/i', '', $code)), config('app.key'));
    }
}
