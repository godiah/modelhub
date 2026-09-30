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
    <x-section-header :title="__('Two-Factor Authentication')"
        :subtitle="__('Add additional security to your account using two-factor authentication')">
        <x-slot:icon>
            <x-icon name="shield-check" class="w-5 h-5 text-white" />
        </x-slot:icon>
    </x-section-header>

    <!-- Content Section -->
    <div class="p-6">
        <!-- Status Messages -->
        @if (session('status') === 'verification-code-sent')
            <div class="mb-6 bg-secondary/10 border border-secondary/30 rounded-lg p-4">
                <div class="flex items-center space-x-3">
                    <x-icon name="check-circle-solid" class="w-5 h-5 text-secondary" />
                    <p class="text-sm font-medium text-secondary font-main">
                        {{ __('A verification code has been sent to your email address.') }}
                    </p>
                </div>
            </div>
        @endif

        @if (session('status') === 'disable-verification-code-sent')
            <div class="mb-6 bg-accent/10 border border-accent/30 rounded-lg p-4">
                <div class="flex items-center space-x-3">
                    <x-icon name="exclamation-triangle-solid" class="w-5 h-5 text-accent" />
                    <p class="text-sm font-medium text-accent font-main">
                        {{ __('A verification code has been sent to your email address to disable two-factor authentication.') }}
                    </p>
                </div>
            </div>
        @endif

        @if (session('status') === 'two-factor-enabled')
            <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex items-center space-x-3">
                    <x-icon name="check-circle-solid" class="w-5 h-5 text-green-600" />
                    <p class="text-sm font-medium text-green-800 font-main">
                        {{ __('Two-factor authentication has been successfully enabled.') }}
                    </p>
                </div>
            </div>
        @endif

        @if (session('status') === 'two-factor-disabled')
            <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex items-center space-x-3">
                    <x-icon name="x-circle-solid" class="w-5 h-5 text-red-600" />
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
                        <x-icon name="shield-check" class="w-6 h-6 {{ $twoFactorEnabled ? 'text-secondary' : 'text-neutral-500' }}" />
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-neutral-900 font-main">
                            {{ __('Two-Factor Authentication Status') }}
                        </h3>
                        <div class="flex items-center space-x-2 mt-1 font-tertiary">
                            @if ($twoFactorEnabled)
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-secondary/20 text-secondary">
                                    <x-icon name="check-circle-solid" class="w-3 h-3 mr-1" />
                                    {{ __('Enabled') }}
                                </span>
                            @else
                                <x-badge tone="red" class="px-3 py-1 text-xs font-medium">
                                    <x-icon name="x-circle-solid" class="w-3 h-3 mr-1" />
                                    {{ __('Disabled') }}
                                </x-badge>
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
                        <x-icon name="lock-closed" class="w-4 h-4 text-secondary" />
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
                                <x-icon name="lock-closed" class="h-5 w-5 text-neutral-400" />
                            </div>
                            <input type="password" wire:model="password" id="password"
                                class="block w-full pl-10 pr-3 py-3 border border-neutral-300 rounded-lg shadow-sm placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary transition-colors duration-200 font-main"
                                placeholder="{{ __('Enter your password') }}" required />
                        </div>
                        @error('password')
                            <div class="flex items-center space-x-2 text-red-600 text-sm font-main mt-2">
                                <x-icon name="exclamation-circle-solid" class="w-4 h-4" />
                                <span>{{ $message }}</span>
                            </div>
                        @enderror
                    </div>

                    <div class="flex space-x-3 pt-2">
                        <x-button variant="secondary" size="lg" class="text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-secondary focus:ring-offset-2" type="submit">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                            {{ __('Continue') }}
                        </x-button>
                        <x-button variant="neutral" size="lg" class="text-sm focus:outline-none focus:ring-2 focus:ring-neutral-500 focus:ring-offset-2" type="button" wire:click="cancel">
                            {{ __('Cancel') }}
                        </x-button>
                    </div>
                </form>
            </div>
        @endif

        <!-- Password Verification Form for Disabling 2FA -->
        @if ($showDisablePasswordForm)
            <div class="bg-red-50 border border-red-200 rounded-lg p-6 mb-6">
                <div class="flex items-start space-x-3 mb-4">
                    <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <x-icon name="exclamation-triangle-2" class="w-5 h-5 text-red-600" />
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
                                <x-icon name="lock-closed" class="h-5 w-5 text-red-400" />
                            </div>
                            <input type="password" wire:model="password" id="disable_password"
                                class="block w-full pl-10 pr-3 py-3 border border-red-300 rounded-lg shadow-sm placeholder-red-300 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-colors duration-200 font-main"
                                placeholder="{{ __('Enter your password') }}" required />
                        </div>
                        @error('password')
                            <div class="flex items-center space-x-2 text-red-600 text-sm font-main mt-2">
                                <x-icon name="exclamation-circle-solid" class="w-4 h-4" />
                                <span>{{ $message }}</span>
                            </div>
                        @enderror
                    </div>

                    <div class="flex space-x-3 pt-2 text-sm">
                        <x-button variant="danger" size="lg" class="text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2" type="submit">
                            <x-icon name="x-mark" class="w-4 h-4 mr-2" />
                            {{ __('Disable Two-Factor Authentication') }}
                        </x-button>
                        <x-button variant="neutral" size="lg" class="text-sm focus:outline-none focus:ring-2 focus:ring-neutral-500 focus:ring-offset-2" type="button" wire:click="cancel">
                            {{ __('Cancel') }}
                        </x-button>
                    </div>
                </form>
            </div>
        @endif

        <!-- Verification Code Form -->
        @if ($showVerificationForm)
            <div class="bg-secondary/5 border border-secondary/20 rounded-lg p-6 mb-6">
                <div class="flex items-start space-x-3 mb-4">
                    <div class="w-8 h-8 bg-secondary/20 rounded-lg flex items-center justify-center flex-shrink-0">
                        <x-icon name="envelope-3" class="w-4 h-4 text-secondary" />
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
                                <x-icon name="exclamation-circle-solid" class="w-4 h-4" />
                                <span>{{ $message }}</span>
                            </div>
                        @enderror
                    </div>

                    <div class="flex flex-wrap gap-3 pt-2 text-sm">
                        <button type="submit"
                            class="inline-flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 font-main">
                            <x-icon name="shield-check" class="w-4 h-4 mr-2" />
                            {{ __('Enable Two-Factor Authentication') }}
                        </button>
                        <x-button size="lg" class="shadow-sm focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2" type="button" wire:click="resendCode">
                            <x-icon name="arrow-path" class="w-4 h-4 mr-2" />
                            {{ __('Resend Code') }}
                        </x-button>
                        <x-button variant="neutral" size="lg" class="focus:outline-none focus:ring-2 focus:ring-neutral-500 focus:ring-offset-2" type="button" wire:click="cancel">
                            {{ __('Cancel') }}
                        </x-button>
                    </div>
                </form>
            </div>
        @endif

        <!-- Disable Verification Code Form -->
        @if ($showDisableVerificationForm)
            <div class="bg-red-50 border border-red-200 rounded-lg p-6 mb-6">
                <div class="flex items-start space-x-3 mb-4">
                    <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <x-icon name="envelope-3" class="w-4 h-4 text-red-600" />
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
                                <x-icon name="exclamation-circle-solid" class="w-4 h-4" />
                                <span>{{ $message }}</span>
                            </div>
                        @enderror
                    </div>

                    <div class="flex flex-wrap gap-3 pt-2 text-sm">
                        <x-button variant="danger" size="lg" class="shadow-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2" type="submit">
                            <x-icon name="x-mark" class="w-4 h-4 mr-2" />
                            {{ __('Disable Two-Factor Authentication') }}
                        </x-button>
                        <button type="button" wire:click="resendDisableCode"
                            class="inline-flex items-center px-6 py-3 bg-accent hover:bg-accent/90 text-white font-medium rounded-lg shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2 font-main">
                            <x-icon name="arrow-path" class="w-4 h-4 mr-2" />
                            {{ __('Resend Code') }}
                        </button>
                        <x-button variant="neutral" size="lg" class="focus:outline-none focus:ring-2 focus:ring-neutral-500 focus:ring-offset-2" type="button" wire:click="cancel">
                            {{ __('Cancel') }}
                        </x-button>
                    </div>
                </form>
            </div>
        @endif

        <!-- 2FA Active Information -->
        @if ($twoFactorEnabled)
            <div class="bg-secondary/10 border border-secondary/20 rounded-lg p-6">
                <div class="flex items-start space-x-3">
                    <div class="w-8 h-8 bg-secondary/20 rounded-lg flex items-center justify-center flex-shrink-0">
                        <x-icon name="information-circle-solid-2" class="w-4 h-4 text-secondary" />
                    </div>
                    <div>
                        <h4 class="font-semibold text-secondary font-main">
                            {{ __('Two-Factor Authentication is Active') }}
                        </h4>
                        <div
                            class="mt-3 flex items-center space-x-4 text-sm text-neutral-600 font-tertiary font-medium">
                            <div class="flex items-center space-x-1">
                                <x-icon name="check-circle-solid" class="w-3 h-3" />
                                <span>{{ __('Email verification required') }}</span>
                            </div>
                            <div class="flex items-center space-x-1">
                                <x-icon name="check-circle-solid" class="w-3 h-3" />
                                <span>{{ __('Enhanced security') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</section>
