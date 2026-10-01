<?php

namespace App\Livewire\Forms;

use App\Models\User;
use App\Support\Auth\SignInChallenge;
use App\Support\Settings\PlatformSettings;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Form;

class LoginForm extends Form
{
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    #[Validate('boolean')]
    public bool $remember = false;

    /**
     * Check the credentials against the member accounts, without signing anyone in: the caller signs them in, or starts the
     * sign-in code step (see SignInChallenge).
     *
     * @throws ValidationException
     */
    public function authenticate(): User
    {
        $this->ensureIsNotRateLimited();

        $credentials = $this->only(['email', 'password']);

        // A suspended account fails like a wrong password; only someone who knows the password is told it is suspended
        $user = SignInChallenge::authenticate('web', $credentials + ['suspended_at' => null]);

        if (! $user) {
            RateLimiter::hit($this->throttleKey(), PlatformSettings::int('security.signin_lockout_minutes') * 60);

            if (SignInChallenge::authenticate('web', $credentials)) {
                throw ValidationException::withMessages([
                    'form.email' => 'This account has been suspended. If you think that is a mistake, contact support.',
                ]);
            }

            throw ValidationException::withMessages([
                'form.email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        return $user;
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), PlatformSettings::int('security.signin_max_attempts'))) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'form.email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}
