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

<!-- Email Verification Form -->
<div class="w-full max-w-2xl p-8 mx-auto bg-white rounded-3xl shadow-2xl overflow-hidden ">
    <div class=" mx-auto">

        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-neutral-900 font-tertiary tracking-wider">
                {{ __('Verify Your Email') }}</h1>
            <p class="text-tertiary text-sm leading-relaxed font-main">
                {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
            </p>
        </div>

        <!-- Success Message -->
        @if (session('status') == 'verification-link-sent')
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl">
                <p class="text-sm text-green-600 font-main">
                    {{ __('A new verification link has been sent to the email address you provided during registration.') }}
                </p>
            </div>
        @endif

        <!-- Action Buttons -->
        <div class="space-y-4 font-main">
            <!-- Resend Verification Button -->
            <x-primary-button wire:click="sendVerification"
                class="font-secondary inline-flex items-center justify-center w-full px-6 py-3 bg-secondary border border-transparent rounded-xl font-semibold text-sm text-white tracking-widest hover:bg-teal-600 focus:bg-teal-600 active:bg-teal-700 focus:outline-none focus:ring-4 focus:ring-secondary focus:ring-offset-2 focus:ring-secondary/50 transition-all duration-200 transform hover:shadow-lg hover:shadow-secondary/30">
                {{ __('Resend Verification Email') }}
            </x-primary-button>

            <!-- Logout Button -->
            <button wire:click="logout" type="submit" 
                class="w-full px-6 py-3 bg-neutral-50 border border-neutral-200 rounded-xl font-semibold text-sm text-tertiary hover:bg-neutral-100 hover:text-neutral-900 focus:outline-none focus:ring-4 focus:ring-neutral-200 transition-all duration-200">
                {{ __('Log Out') }}
            </button>
        </div>

        <!-- Footer Info -->
        <div class="mt-6 text-center font-main">
            <p class="text-xs text-tertiary">
                {{ __('Check your spam folder if you don\'t see the email in your inbox.') }}
            </p>
        </div>
    </div>
</div>
