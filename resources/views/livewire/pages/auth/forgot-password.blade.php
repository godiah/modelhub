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

<x-auth-layout :title="__('Forgot password?')"
    :subtitle="__('No problem. Enter your email address and we will send you a link to choose a new password.')"
    :panelTitle="__('Locked out? We’ve got you')"
    :panelText="__('Reset links are sent to your registered email address and expire automatically, so your account stays protected.')">

    <!-- Session Status -->
    <x-auth-session-status class="mb-6" :status="session('status')" />

    <!-- Forgot Password Form -->
    <form wire:submit="sendPasswordResetLink" class="space-y-5">
        <x-auth-field name="email" type="email" :label="__('Email')" wire:model="email"
            placeholder="you@example.com" required autofocus autocomplete="email" />

        <x-auth-button wire:target="sendPasswordResetLink">{{ __('Email password reset link') }}</x-auth-button>
    </form>

    <x-slot:footer>
        <a href="{{ route('login') }}" wire:navigate
            class="inline-flex items-center gap-1.5 font-semibold text-teal-700 hover:text-teal-800 transition-colors duration-200">
            <x-icon name="arrow-left" class="h-4 w-4" />
            {{ __('Back to sign in') }}
        </a>
    </x-slot:footer>
</x-auth-layout>
