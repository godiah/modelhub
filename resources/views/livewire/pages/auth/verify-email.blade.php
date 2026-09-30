<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    /**
     * Send an email verification notification to the user.
     */
    public function sendVerification(): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);

            return;
        }

        Auth::user()->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<x-auth-layout :title="__('Verify your email')"
    :subtitle="__('Thanks for signing up! Before getting started, please verify your email address by clicking the link we just emailed to you. If you didn’t receive it, we’ll gladly send another.')"
    :panelTitle="__('Get started with ModelHub')"
    :panelText="__('One quick step left before you can post or find 3D modeling jobs.')">

    <!-- Session Status -->
    <x-auth-session-status class="mb-6" :status="session('status')" />

    <!-- Action Buttons -->
    <div class="space-y-3">
        <x-auth-button type="button" wire:click="sendVerification" wire:target="sendVerification">
            {{ __('Resend verification email') }}
        </x-auth-button>

        <x-auth-button type="button" variant="secondary" wire:click="logout" wire:target="logout">
            {{ __('Log out') }}
        </x-auth-button>
    </div>

    <x-slot:footer>
        <span class="text-xs">{{ __('Check your spam folder if you don’t see the email in your inbox.') }}</span>
    </x-slot:footer>
</x-auth-layout>
