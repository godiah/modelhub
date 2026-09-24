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

<x-auth-card :title="__('Confirm Password')"
    :subtitle="__('This is a secure area of the application. Please confirm your password before continuing.')">

    <form wire:submit="confirmPassword" class="space-y-4 font-main">
        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" class="sr-only" />
            <x-text-input wire:model="password" id="password"
                class="w-full px-4 py-3 bg-neutral-50 border border-neutral-200 rounded-xl text-neutral-900 placeholder-neutral-400 transition-all duration-200 focus:outline-none"
                type="password" name="password" placeholder="{{ __('Password') }}" required
                autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Submit Button -->
        <x-primary-button
            class="font-secondary inline-flex items-center justify-center w-full px-6 py-3 bg-secondary border border-transparent rounded-xl font-semibold text-sm text-white tracking-widest hover:bg-teal-600 focus:bg-teal-600 active:bg-teal-700 focus:outline-none focus:ring-4 focus:ring-secondary focus:ring-offset-2 focus:ring-secondary/50 transition-all duration-200 transform hover:shadow-lg hover:shadow-secondary/30">
            {{ __('Confirm') }}
        </x-primary-button>
    </form>
</x-auth-card>
