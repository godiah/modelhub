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

<div>
    @php
        $flash = [
            'verification-code-sent' => __('A verification code has been sent to your email address.'),
            'disable-verification-code-sent' => __('A verification code has been sent to your email address to disable two-factor authentication.'),
            'two-factor-enabled' => __('Two-factor authentication has been successfully enabled.'),
            'two-factor-disabled' => __('Two-factor authentication has been disabled.'),
        ];
        $idle = !$showPasswordForm && !$showVerificationForm && !$showDisablePasswordForm && !$showDisableVerificationForm;
    @endphp

    <x-panel :title="__('Two-factor authentication')"
        :description="__('Add a second step to signing in: a 6-digit code sent to your email.')">
        <div class="space-y-5">
            @if (isset($flash[session('status')]))
                <div class="flex items-center gap-3 rounded-xl border border-teal-200 bg-teal-50 p-4" role="status">
                    <x-icon name="check-circle-solid" class="h-5 w-5 shrink-0 text-teal-700" />
                    <p class="text-sm font-medium text-teal-900">{{ $flash[session('status')] }}</p>
                </div>
            @endif

            <!-- Status -->
            <div class="flex flex-col gap-4 rounded-xl border border-neutral-200 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <span @class([
                        'flex h-11 w-11 shrink-0 items-center justify-center rounded-full',
                        'bg-teal-50 text-teal-700' => $twoFactorEnabled,
                        'bg-neutral-100 text-neutral-500' => !$twoFactorEnabled,
                    ])>
                        <x-icon name="shield-check" class="h-6 w-6" />
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-neutral-900">{{ __('Email verification codes') }}</p>
                        @if ($twoFactorEnabled)
                            <x-badge tone="green" class="mt-1 px-2.5 py-0.5 text-xs font-medium">
                                <x-icon name="check-circle-solid" class="mr-1 h-3 w-3" />{{ __('Enabled') }}
                            </x-badge>
                        @else
                            <x-badge tone="neutral" class="mt-1 px-2.5 py-0.5 text-xs font-medium">{{ __('Not enabled') }}</x-badge>
                        @endif
                    </div>
                </div>

                @if ($idle)
                    <x-btn type="button" wire:click="toggleTwoFactor" wire:target="toggleTwoFactor"
                        :variant="$twoFactorEnabled ? 'danger-outline' : 'primary'">
                        {{ $twoFactorEnabled ? __('Disable') : __('Enable') }}
                    </x-btn>
                @endif
            </div>

            <!-- Enable: confirm password -->
            @if ($showPasswordForm)
                <form wire:submit="verifyPasswordAndSendCode" class="space-y-4 rounded-xl border border-neutral-200 bg-neutral-50/60 p-5">
                    <div>
                        <p class="text-sm font-semibold text-neutral-900">{{ __('Confirm your password') }}</p>
                        <p class="mt-1 text-sm text-tertiary">{{ __('Please confirm your password to enable two-factor authentication.') }}</p>
                    </div>
                    <x-field id="two_factor_password" name="password" type="password" :label="__('Password')"
                        wire:model="password" icon="lock-closed" placeholder="{{ __('Enter your password') }}" required />
                    <div class="flex flex-wrap gap-3">
                        <x-btn wire:target="verifyPasswordAndSendCode">{{ __('Continue') }}</x-btn>
                        <x-btn variant="secondary" type="button" wire:click="cancel">{{ __('Cancel') }}</x-btn>
                    </div>
                </form>
            @endif

            <!-- Disable: confirm password -->
            @if ($showDisablePasswordForm)
                <form wire:submit="verifyPasswordAndSendDisableCode" class="space-y-4 rounded-xl border border-red-200 bg-red-50/40 p-5">
                    <div>
                        <p class="text-sm font-semibold text-red-900">{{ __('Confirm your password to disable 2FA') }}</p>
                        <p class="mt-1 text-sm text-red-800">{{ __('Please confirm your password to disable two-factor authentication. This will reduce your account security.') }}</p>
                    </div>
                    <x-field id="disable_password" name="password" type="password" :label="__('Password')"
                        wire:model="password" icon="lock-closed" placeholder="{{ __('Enter your password') }}" required />
                    <div class="flex flex-wrap gap-3">
                        <x-btn variant="danger" wire:target="verifyPasswordAndSendDisableCode">{{ __('Disable two-factor authentication') }}</x-btn>
                        <x-btn variant="secondary" type="button" wire:click="cancel">{{ __('Cancel') }}</x-btn>
                    </div>
                </form>
            @endif

            <!-- Enable: verification code -->
            @if ($showVerificationForm)
                <form wire:submit="enableTwoFactor" class="space-y-4 rounded-xl border border-neutral-200 bg-neutral-50/60 p-5">
                    <div>
                        <p class="text-sm font-semibold text-neutral-900">{{ __('Enter verification code') }}</p>
                        <p class="mt-1 text-sm text-tertiary">{{ __('We\'ve sent a 6-digit verification code to your email address. The code will expire in 5 minutes.') }}</p>
                    </div>
                    <x-field id="verification_code" name="verification_code" :label="__('Verification code')"
                        wire:model="verification_code" placeholder="000000" maxlength="6" inputmode="numeric"
                        autocomplete="one-time-code" required class="text-center text-lg font-semibold tracking-[0.4em]" />
                    <div class="flex flex-wrap gap-3">
                        <x-btn wire:target="enableTwoFactor">{{ __('Enable two-factor authentication') }}</x-btn>
                        <x-btn variant="ghost" type="button" wire:click="resendCode" wire:target="resendCode">{{ __('Resend code') }}</x-btn>
                        <x-btn variant="secondary" type="button" wire:click="cancel">{{ __('Cancel') }}</x-btn>
                    </div>
                </form>
            @endif

            <!-- Disable: verification code -->
            @if ($showDisableVerificationForm)
                <form wire:submit="verifyDisableCodeAndDisable" class="space-y-4 rounded-xl border border-red-200 bg-red-50/40 p-5">
                    <div>
                        <p class="text-sm font-semibold text-red-900">{{ __('Enter verification code to disable 2FA') }}</p>
                        <p class="mt-1 text-sm text-red-800">{{ __('We\'ve sent a 6-digit verification code to your email address. Enter it below to complete the 2FA disable process. The code will expire in 5 minutes.') }}</p>
                    </div>
                    <x-field id="verification_code_disable" name="verification_code" :label="__('Verification code')"
                        wire:model="verification_code" placeholder="000000" maxlength="6" inputmode="numeric"
                        autocomplete="one-time-code" required class="text-center text-lg font-semibold tracking-[0.4em]" />
                    <div class="flex flex-wrap gap-3">
                        <x-btn variant="danger" wire:target="verifyDisableCodeAndDisable">{{ __('Disable two-factor authentication') }}</x-btn>
                        <x-btn variant="ghost" type="button" wire:click="resendDisableCode" wire:target="resendDisableCode">{{ __('Resend code') }}</x-btn>
                        <x-btn variant="secondary" type="button" wire:click="cancel">{{ __('Cancel') }}</x-btn>
                    </div>
                </form>
            @endif

            <!-- What it gives you -->
            @if ($twoFactorEnabled && $idle)
                <ul class="grid gap-2 text-sm text-neutral-700 sm:grid-cols-2">
                    <li class="flex items-center gap-2"><x-icon name="check-circle-solid" class="h-5 w-5 shrink-0 text-teal-700" />{{ __('Email verification required at sign-in') }}</li>
                    <li class="flex items-center gap-2"><x-icon name="check-circle-solid" class="h-5 w-5 shrink-0 text-teal-700" />{{ __('Enhanced account security') }}</li>
                </ul>
            @endif
        </div>
    </x-panel>
</div>
