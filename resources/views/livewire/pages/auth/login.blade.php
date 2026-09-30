<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component {
    public LoginForm $form;
    public string $verification_code = '';
    public bool $showTwoFactorForm = false;
    public ?int $pendingUserId = null;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        $user = Auth::user();

        // Check if user has 2FA enabled
        if ($user->hasTwoFactorEnabled()) {
            // Log out the user temporarily
            Auth::logout();

            // Store user ID for 2FA verification
            $this->pendingUserId = $user->id;

            // Generate and send 2FA code
            $user->generateTwoFactorCode();

            // Show 2FA form
            $this->showTwoFactorForm = true;

            Session::flash('status', 'two-factor-code-sent');
            return;
        }

        // If no 2FA, proceed with normal login
        Session::regenerate();
        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }

    /**
     * Verify the two-factor authentication code and complete login.
     */
    public function verifyTwoFactorCode(): void
    {
        $this->validate([
            'verification_code' => ['required', 'string', 'size:6'],
        ]);

        if (!$this->pendingUserId) {
            $this->addError('verification_code', 'Session expired. Please try logging in again.');
            return;
        }

        $user = \App\Models\User::find($this->pendingUserId);

        if (!$user || !$user->verifyTwoFactorCode($this->verification_code)) {
            $this->addError('verification_code', 'The verification code is invalid or has expired.');
            return;
        }

        // Clear the 2FA code
        $user->clearTwoFactorCode();

        // Log the user in
        Auth::login($user, $this->form->remember);
        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }

    /**
     * Resend the two-factor authentication code.
     */
    public function resendTwoFactorCode(): void
    {
        if (!$this->pendingUserId) {
            return;
        }

        $user = \App\Models\User::find($this->pendingUserId);
        if ($user) {
            $user->generateTwoFactorCode();
            Session::flash('status', 'two-factor-code-sent');
        }
    }

    /**
     * Cancel the two-factor authentication process.
     */
    public function cancelTwoFactor(): void
    {
        if ($this->pendingUserId) {
            $user = \App\Models\User::find($this->pendingUserId);
            if ($user) {
                $user->clearTwoFactorCode();
            }
        }

        $this->showTwoFactorForm = false;
        $this->pendingUserId = null;
        $this->verification_code = '';
    }
}; ?>

<x-auth-layout :title="$showTwoFactorForm ? __('Two-factor authentication') : __('Welcome back')"
    :subtitle="$showTwoFactorForm ? __('Enter the verification code sent to your email.') : __('Sign in to your account to continue.')">

    <!-- Session Status -->
    <x-auth-session-status class="mb-6" :status="session('status')" />

    @if (!$showTwoFactorForm)
        <!-- Social Login (inert until OAuth is implemented) -->
        <x-social-buttons />

        <!-- Login Form -->
        <form wire:submit="login" class="space-y-5">
            <x-field size="lg" name="email" type="email" :label="__('Email')" wire:model="form.email"
                placeholder="you@example.com" required autofocus autocomplete="username" />

            <x-field size="lg" name="password" type="password" :label="__('Password')" wire:model="form.password"
                placeholder="{{ __('Enter your password') }}" required autocomplete="current-password">
                @if (Route::has('password.request'))
                    <x-slot:action>
                        <a href="{{ route('password.request') }}" wire:navigate
                            class="font-medium text-teal-700 hover:text-teal-800 transition-colors duration-200">
                            {{ __('Forgot password?') }}
                        </a>
                    </x-slot:action>
                @endif
            </x-field>

            <!-- Remember Me -->
            <label for="remember" class="flex items-center gap-3 text-sm text-tertiary cursor-pointer w-fit">
                <input wire:model="form.remember" id="remember" type="checkbox" name="remember"
                    class="h-4 w-4 rounded border-neutral-300 text-teal-600 focus:ring-secondary/40 focus:ring-2">
                {{ __('Remember me') }}
            </label>

            <x-btn block size="lg" wire:target="login">{{ __('Sign in') }}</x-btn>
        </form>

        <x-slot:footer>
            {{ __("Don't have an account?") }}
            <a href="{{ route('register') }}" wire:navigate
                class="font-semibold text-teal-700 hover:text-teal-800 transition-colors duration-200">{{ __('Sign up') }}</a>
        </x-slot:footer>
    @else
        <!-- Two-Factor Authentication Form -->
        <form wire:submit="verifyTwoFactorCode" class="space-y-5">
            <x-field size="lg" name="verification_code" :label="__('Verification code')" wire:model="verification_code"
                :hint="__('Enter the 6-digit code sent to your email. It expires in 5 minutes.')" placeholder="000000"
                inputmode="numeric" maxlength="6" pattern="[0-9]*" autocomplete="one-time-code" required autofocus
                class="text-center text-xl font-semibold tracking-[0.5em]" />

            <x-btn block size="lg" wire:target="verifyTwoFactorCode">{{ __('Verify and sign in') }}</x-btn>

            <div class="grid grid-cols-2 gap-3">
                <x-btn block size="lg" type="button" variant="secondary" wire:click="resendTwoFactorCode"
                    wire:target="resendTwoFactorCode">{{ __('Resend code') }}</x-btn>
                <x-btn block size="lg" type="button" variant="secondary" wire:click="cancelTwoFactor"
                    wire:target="cancelTwoFactor">{{ __('Cancel') }}</x-btn>
            </div>
        </form>
    @endif
</x-auth-layout>
