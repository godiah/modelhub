<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Livewire\Volt\Component;

new class extends Component {
    public string $password = '';
    public string $verification_code = '';
    public bool $showPasswordForm = false;
    public bool $showVerificationForm = false;
    public bool $showDisablePasswordForm = false;
    public bool $showDisableVerificationForm = false;
    public bool $twoFactorEnabled = false;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->twoFactorEnabled = Auth::user()->hasTwoFactorEnabled();
    }

    /**
     * Toggle two-factor authentication.
     */
    public function toggleTwoFactor(): void
    {
        if ($this->twoFactorEnabled) {
            // Show password form for disabling 2FA
            $this->showDisablePasswordForm = true;
        } else {
            // Show password form for enabling 2FA
            $this->showPasswordForm = true;
        }
    }

    /**
     * Verify password and send verification code.
     */
    public function verifyPasswordAndSendCode(): void
    {
        $this->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = Auth::user();
        $user->generateTwoFactorCode();

        $this->showPasswordForm = false;
        $this->showVerificationForm = true;
        $this->password = '';

        Session::flash('status', 'verification-code-sent');
    }

    /**
     * Verify the code and enable two-factor authentication.
     */
    public function enableTwoFactor(): void
    {
        $this->validate([
            'verification_code' => ['required', 'string', 'size:6'],
        ]);

        $user = Auth::user();

        if (!$user->verifyTwoFactorCode($this->verification_code)) {
            $this->addError('verification_code', 'The verification code is invalid or has expired.');
            return;
        }

        $user->enableTwoFactor();

        $this->twoFactorEnabled = true;
        $this->showVerificationForm = false;
        $this->verification_code = '';

        Session::flash('status', 'two-factor-enabled');
        $this->dispatch('two-factor-updated');
    }

    /**
     * Verify password and send verification code for disabling 2FA.
     */
    public function verifyPasswordAndSendDisableCode(): void
    {
        $this->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = Auth::user();
        $user->generateTwoFactorCode();

        $this->showDisablePasswordForm = false;
        $this->showDisableVerificationForm = true;
        $this->password = '';

        Session::flash('status', 'disable-verification-code-sent');
    }

    /**
     * Verify the disable code and disable two-factor authentication.
     */
    public function verifyDisableCodeAndDisable(): void
    {
        $this->validate([
            'verification_code' => ['required', 'string', 'size:6'],
        ]);

        $user = Auth::user();

        if (!$user->verifyTwoFactorCode($this->verification_code)) {
            $this->addError('verification_code', 'The verification code is invalid or has expired.');
            return;
        }

        $user->disableTwoFactor();

        $this->twoFactorEnabled = false;
        $this->showDisableVerificationForm = false;
        $this->verification_code = '';

        Session::flash('status', 'two-factor-disabled');
        $this->dispatch('two-factor-updated');
    }

    /**
     * Cancel the two-factor setup process.
     */
    public function cancel(): void
    {
        $this->showPasswordForm = false;
        $this->showVerificationForm = false;
        $this->showDisablePasswordForm = false;
        $this->showDisableVerificationForm = false;
        $this->password = '';
        $this->verification_code = '';

        // Clear any pending verification code
        Auth::user()->clearTwoFactorCode();
    }

    /**
     * Resend verification code.
     */
    public function resendCode(): void
    {
        Auth::user()->generateTwoFactorCode();
        Session::flash('status', 'verification-code-sent');
    }

    /**
     * Resend disable verification code.
     */
    public function resendDisableCode(): void
    {
        Auth::user()->generateTwoFactorCode();
        Session::flash('status', 'disable-verification-code-sent');
    }
}; ?>

<section class="bg-white rounded-xl shadow-sm border border-neutral-200 overflow-hidden">
    <!-- Header Section -->
    <div class="bg-gradient-to-r from-primary to-primary/90 px-6 py-5">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                    </path>
                </svg>
            </div>
            <div>
                <h2 class="text-xl font-semibold text-white font-secondary">
                    {{ __('Two-Factor Authentication') }}
                </h2>
                <p class="text-white/60 text-sm font-main mt-1">
                    {{ __('Add additional security to your account using two-factor authentication') }}
                </p>
            </div>
        </div>
    </div>

    <!-- Content Section -->
    <div class="p-6">
        <!-- Status Messages -->
        @if (session('status') === 'verification-code-sent')
            <div class="mb-6 bg-secondary/10 border border-secondary/30 rounded-lg p-4">
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5 text-secondary" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd"></path>
                    </svg>
                    <p class="text-sm font-medium text-secondary font-main">
                        {{ __('A verification code has been sent to your email address.') }}
                    </p>
                </div>
            </div>
        @endif

        @if (session('status') === 'disable-verification-code-sent')
            <div class="mb-6 bg-accent/10 border border-accent/30 rounded-lg p-4">
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5 text-accent" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd"></path>
                    </svg>
                    <p class="text-sm font-medium text-accent font-main">
                        {{ __('A verification code has been sent to your email address to disable two-factor authentication.') }}
                    </p>
                </div>
            </div>
        @endif

        @if (session('status') === 'two-factor-enabled')
            <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd"></path>
                    </svg>
                    <p class="text-sm font-medium text-green-800 font-main">
                        {{ __('Two-factor authentication has been successfully enabled.') }}
                    </p>
                </div>
            </div>
        @endif

        @if (session('status') === 'two-factor-disabled')
            <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                            clip-rule="evenodd"></path>
                    </svg>
                    <p class="text-sm font-medium text-red-800 font-main">
                        {{ __('Two-factor authentication has been disabled.') }}
                    </p>
                </div>
            </div>
        @endif

        <!-- 2FA Status Card -->
        <div class="bg-neutral-50 rounded-lg p-6 mb-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div
                        class="w-12 h-12 rounded-lg flex items-center justify-center {{ $twoFactorEnabled ? 'bg-secondary/20' : 'bg-neutral-200' }}">
                        <svg class="w-6 h-6 {{ $twoFactorEnabled ? 'text-secondary' : 'text-neutral-500' }}"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-neutral-900 font-main">
                            {{ __('Two-Factor Authentication Status') }}
                        </h3>
                        <div class="flex items-center space-x-2 mt-1 font-tertiary">
                            @if ($twoFactorEnabled)
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-secondary/20 text-secondary">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                            clip-rule="evenodd"></path>
                                    </svg>
                                    {{ __('Enabled') }}
                                </span>
                            @else
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                            clip-rule="evenodd"></path>
                                    </svg>
                                    {{ __('Disabled') }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                @if (!$showPasswordForm && !$showVerificationForm && !$showDisablePasswordForm && !$showDisableVerificationForm)
                    <button wire:click="toggleTwoFactor"
                        class="inline-flex items-center px-6 py-3 font-medium text-sm rounded-lg shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 font-main
                               {{ $twoFactorEnabled ? 'bg-red-600 hover:bg-red-700 text-white focus:ring-red-500' : 'bg-secondary hover:bg-secondary/90 text-white focus:ring-secondary' }}">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            @if ($twoFactorEnabled)
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            @else
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                                </path>
                            @endif
                        </svg>
                        {{ $twoFactorEnabled ? __('Disable') : __('Enable') }} {{ __('Two-Factor Authentication') }}
                    </button>
                @endif
            </div>
        </div>

        <!-- Password Verification Form For Enabling 2FA -->
        @if ($showPasswordForm)
            <div class="bg-secondary/5 border border-secondary/20 rounded-lg p-6 mb-6">
                <div class="flex items-start space-x-3 mb-4">
                    <div class="w-8 h-8 bg-secondary/20 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-lg font-semibold text-neutral-900 font-main">
                            {{ __('Confirm Your Password') }}
                        </h4>
                        <p class="text-sm text-neutral-600 font-tertiary mt-1">
                            {{ __('Please confirm your password to enable two-factor authentication.') }}
                        </p>
                    </div>
                </div>

                <form wire:submit="verifyPasswordAndSendCode" class="space-y-4">
                    <div>
                        <label for="password" class="block text-sm font-medium text-neutral-700 font-main mb-2">
                            {{ __('Password') }}
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-neutral-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                                    </path>
                                </svg>
                            </div>
                            <input type="password" wire:model="password" id="password"
                                class="block w-full pl-10 pr-3 py-3 border border-neutral-300 rounded-lg shadow-sm placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary transition-colors duration-200 font-main"
                                placeholder="{{ __('Enter your password') }}" required />
                        </div>
                        @error('password')
                            <div class="flex items-center space-x-2 text-red-600 text-sm font-main mt-2">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd"></path>
                                </svg>
                                <span>{{ $message }}</span>
                            </div>
                        @enderror
                    </div>

                    <div class="flex space-x-3 pt-2">
                        <button type="submit"
                            class="inline-flex items-center text-sm px-6 py-3 bg-secondary hover:bg-secondary/90 text-white font-medium rounded-lg shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-secondary focus:ring-offset-2 font-main">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                            {{ __('Continue') }}
                        </button>
                        <button type="button" wire:click="cancel"
                            class="inline-flex items-center text-sm px-6 py-3 bg-neutral-200 hover:bg-neutral-300 text-neutral-700 font-medium rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-neutral-500 focus:ring-offset-2 font-main">
                            {{ __('Cancel') }}
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <!-- Password Verification Form for Disabling 2FA -->
        @if ($showDisablePasswordForm)
            <div class="bg-red-50 border border-red-200 rounded-lg p-6 mb-6">
                <div class="flex items-start space-x-3 mb-4">
                    <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-lg font-semibold text-red-900 font-main">
                            {{ __('Confirm Your Password to Disable 2FA') }}
                        </h4>
                        <p class="text-sm text-red-700 font-tertiary mt-1">
                            {{ __('Please confirm your password to disable two-factor authentication. This will reduce your account security.') }}
                        </p>
                    </div>
                </div>

                <form wire:submit="verifyPasswordAndSendDisableCode" class="space-y-4">
                    <div>
                        <label for="disable_password" class="block text-sm font-medium text-red-800 font-main mb-2">
                            {{ __('Password') }}
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                                    </path>
                                </svg>
                            </div>
                            <input type="password" wire:model="password" id="disable_password"
                                class="block w-full pl-10 pr-3 py-3 border border-red-300 rounded-lg shadow-sm placeholder-red-300 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-colors duration-200 font-main"
                                placeholder="{{ __('Enter your password') }}" required />
                        </div>
                        @error('password')
                            <div class="flex items-center space-x-2 text-red-600 text-sm font-main mt-2">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd"></path>
                                </svg>
                                <span>{{ $message }}</span>
                            </div>
                        @enderror
                    </div>

                    <div class="flex space-x-3 pt-2 text-sm">
                        <button type="submit"
                            class="inline-flex text-sm items-center px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 font-main">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            {{ __('Disable Two-Factor Authentication') }}
                        </button>
                        <button type="button" wire:click="cancel"
                            class="inline-flex text-sm items-center px-6 py-3 bg-neutral-200 hover:bg-neutral-300 text-neutral-700 font-medium rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-neutral-500 focus:ring-offset-2 font-main">
                            {{ __('Cancel') }}
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <!-- Verification Code Form -->
        @if ($showVerificationForm)
            <div class="bg-secondary/5 border border-secondary/20 rounded-lg p-6 mb-6">
                <div class="flex items-start space-x-3 mb-4">
                    <div class="w-8 h-8 bg-secondary/20 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-secondary" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-lg font-semibold text-neutral-900 font-main">
                            {{ __('Enter Verification Code') }}
                        </h4>
                        <p class="text-sm text-neutral-600 font-main mt-1">
                            {{ __('We\'ve sent a 6-digit verification code to your email address. The code will expire in 5 minutes.') }}
                        </p>
                    </div>
                </div>

                <form wire:submit="enableTwoFactor" class="space-y-4">
                    <div>
                        <label for="verification_code"
                            class="block text-sm font-medium text-neutral-700 font-main mb-2">
                            {{ __('Verification Code') }}
                        </label>
                        <input type="text" wire:model="verification_code" id="verification_code" maxlength="6"
                            placeholder="000000"
                            class="block w-full px-4 py-4 border border-neutral-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary text-center text-2xl tracking-widest font-mono transition-colors duration-200"
                            required />
                        @error('verification_code')
                            <div class="flex items-center space-x-2 text-red-600 text-sm font-main mt-2">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd"></path>
                                </svg>
                                <span>{{ $message }}</span>
                            </div>
                        @enderror
                    </div>

                    <div class="flex flex-wrap gap-3 pt-2 text-sm">
                        <button type="submit"
                            class="inline-flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 font-main">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                                </path>
                            </svg>
                            {{ __('Enable Two-Factor Authentication') }}
                        </button>
                        <button type="button" wire:click="resendCode"
                            class="inline-flex items-center px-6 py-3 bg-primary hover:bg-primary/90 text-white font-medium rounded-lg shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 font-main">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                </path>
                            </svg>
                            {{ __('Resend Code') }}
                        </button>
                        <button type="button" wire:click="cancel"
                            class="inline-flex items-center px-6 py-3 bg-neutral-200 hover:bg-neutral-300 text-neutral-700 font-medium rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-neutral-500 focus:ring-offset-2 font-main">
                            {{ __('Cancel') }}
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <!-- Disable Verification Code Form -->
        @if ($showDisableVerificationForm)
            <div class="bg-red-50 border border-red-200 rounded-lg p-6 mb-6">
                <div class="flex items-start space-x-3 mb-4">
                    <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-lg font-semibold text-red-900 font-main">
                            {{ __('Enter Verification Code to Disable 2FA') }}
                        </h4>
                        <p class="text-sm text-red-700 font-main mt-1">
                            {{ __('We\'ve sent a 6-digit verification code to your email address. Enter it below to complete the 2FA disable process. The code will expire in 5 minutes.') }}
                        </p>
                    </div>
                </div>

                <form wire:submit="verifyDisableCodeAndDisable" class="space-y-4">
                    <div>
                        <label for="verification_code_disable"
                            class="block text-sm font-medium text-red-800 font-main mb-2">
                            {{ __('Verification Code') }}
                        </label>
                        <input type="text" wire:model="verification_code" id="verification_code_disable"
                            maxlength="6" placeholder="000000"
                            class="block w-full px-4 py-4 border border-red-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 text-center text-2xl tracking-widest font-mono transition-colors duration-200"
                            required />
                        @error('verification_code')
                            <div class="flex items-center space-x-2 text-red-600 text-sm font-main mt-2">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd"></path>
                                </svg>
                                <span>{{ $message }}</span>
                            </div>
                        @enderror
                    </div>

                    <div class="flex flex-wrap gap-3 pt-2 text-sm">
                        <button type="submit"
                            class="inline-flex items-center px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 font-main">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            {{ __('Disable Two-Factor Authentication') }}
                        </button>
                        <button type="button" wire:click="resendDisableCode"
                            class="inline-flex items-center px-6 py-3 bg-accent hover:bg-accent/90 text-white font-medium rounded-lg shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2 font-main">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                </path>
                            </svg>
                            {{ __('Resend Code') }}
                        </button>
                        <button type="button" wire:click="cancel"
                            class="inline-flex items-center px-6 py-3 bg-neutral-200 hover:bg-neutral-300 text-neutral-700 font-medium rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-neutral-500 focus:ring-offset-2 font-main">
                            {{ __('Cancel') }}
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <!-- 2FA Active Information -->
        @if ($twoFactorEnabled)
            <div class="bg-secondary/10 border border-secondary/20 rounded-lg p-6">
                <div class="flex items-start space-x-3">
                    <div class="w-8 h-8 bg-secondary/20 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-secondary" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-semibold text-secondary font-main">
                            {{ __('Two-Factor Authentication is Active') }}
                        </h4>
                        <div
                            class="mt-3 flex items-center space-x-4 text-sm text-neutral-600 font-tertiary font-medium">
                            <div class="flex items-center space-x-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd"></path>
                                </svg>
                                <span>{{ __('Email verification required') }}</span>
                            </div>
                            <div class="flex items-center space-x-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd"></path>
                                </svg>
                                <span>{{ __('Enhanced security') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</section>
