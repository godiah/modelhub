<?php

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component {
    public string $email = '';

    /**
     * Send a password reset link to the provided email address.
     */
    public function sendPasswordResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::sendResetLink($this->only('email'));

        if ($status != Password::RESET_LINK_SENT) {
            $this->addError('email', __($status));

            return;
        }

        $this->reset('email');

        session()->flash('status', __($status));
    }
}; ?>

<!-- Forgot Password Form -->
<div class="w-full max-w-2xl p-8 mx-auto bg-white rounded-3xl shadow-2xl overflow-hidden ">
    <div>

        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-neutral-900 font-tertiary tracking-wider">
                {{ __('Forgot Password?') }}</h1>
            <p class="text-tertiary text-sm leading-relaxed font-main">
                {{ __('No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
            </p>
        </div>

        <!-- Session Status -->
        <x-auth-session-status class="mb-6 font-main" :status="session('status')" />

        <!-- Forgot Password Form -->
        <form wire:submit="sendPasswordResetLink" class="space-y-4 font-main">

            <!-- Email Field -->
            <div>
                <x-input-label for="email" :value="__('Email')" class="sr-only" />
                <x-text-input wire:model="email" id="email"
                    class="w-full px-4 py-3 bg-neutral-50 border border-neutral-200 rounded-xl text-neutral-900 placeholder-neutral-400 transition-all duration-200 focus:outline-none"
                    type="email" name="email" placeholder="{{ __('Email') }}" required autofocus />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <!-- Submit Button -->
            <x-primary-button
                class="font-secondary inline-flex items-center justify-center w-full px-6 py-3 bg-secondary border border-transparent rounded-xl font-semibold text-sm text-white tracking-widest hover:bg-teal-600 focus:bg-teal-600 active:bg-teal-700 focus:outline-none focus:ring-4 focus:ring-secondary focus:ring-offset-2 focus:ring-secondary/50 transition-all duration-200 transform hover:shadow-lg hover:shadow-secondary/30">
                {{ __('Email Password Reset Link') }}
            </x-primary-button>

        </form>

        <!-- Footer Links -->
        <div class="mt-6 space-y-2 text-center font-main">
            <!-- Back to Login Link -->
            <p class="text-sm text-tertiary">
                {{ __('Remember your password?') }}
                <a href="{{ route('login') }}" wire:navigate
                    class="text-secondary hover:text-teal-600 font-medium transition-colors duration-200">
                    {{ __('Sign in') }}
                </a>
            </p>
        </div>
    </div>
</div>
