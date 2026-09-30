<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $password = '';

    /**
     * Confirm the current user's password.
     */
    public function confirmPassword(): void
    {
        $this->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('web')->validate([
            'email' => Auth::user()->email,
            'password' => $this->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        session(['auth.password_confirmed_at' => time()]);

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<x-auth-layout :title="__('Confirm password')"
    :subtitle="__('This is a secure area of the application. Please confirm your password before continuing.')"
    :panelTitle="__('Just a quick check')"
    :panelText="__('We ask for your password again before sensitive actions to keep your account and payments safe.')">

    <form wire:submit="confirmPassword" class="space-y-5">
        <x-auth-field name="password" type="password" :label="__('Password')" wire:model="password"
            placeholder="{{ __('Enter your password') }}" required autofocus autocomplete="current-password" />

        <x-auth-button wire:target="confirmPassword">{{ __('Confirm') }}</x-auth-button>
    </form>
</x-auth-layout>
