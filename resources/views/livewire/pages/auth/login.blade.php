<?php

use App\Livewire\Forms\LoginForm;
use App\Support\Auth\SignInChallenge;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component {
    public LoginForm $form;

    /**
     * Handle an incoming authentication request. A correct password signs the member in, unless a sign-in code is needed
     * (their own two-step setting, or the platform rule): then they go on to enter it.
     */
    public function login(): void
    {
        $this->validate();

        $user = $this->form->authenticate();

        if (! SignInChallenge::attempt('web', $user, $this->form->remember, 'form.email')) {
            $this->redirectRoute('two-factor.challenge', navigate: true);

            return;
        }

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<x-auth-layout :title="__('Welcome back')" :subtitle="__('Sign in to your account to continue.')">

    <!-- Session Status -->
    <x-auth-session-status class="mb-6" :status="session('status')" />

    {{-- Sent back here from the code step (too many wrong codes, or the sign-in ran out of time) --}}
    @if ($errors->has('code'))
        <div class="mb-6 rounded-md bg-red-50 p-4 text-sm font-medium text-red-800" role="alert">{{ $errors->first('code') }}</div>
    @endif

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
</x-auth-layout>
