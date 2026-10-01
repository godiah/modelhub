<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component {
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        event(new Registered(($user = User::create($validated))));

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<x-auth-layout :title="__('Create your account')" :subtitle="__('Sign up to start posting or finding 3D modeling jobs.')"
    :panelTitle="__('Get started with ModelHub')"
    :panelText="__('Register now to post or find 3D modeling jobs, connect with talented freelance architects, and unlock a world of premium models to elevate your projects.')">

    <!-- Social Sign-up (inert until OAuth is implemented) -->
    <x-social-buttons />

    <!-- Register Form -->
    <form wire:submit="register" class="space-y-5">
        <x-field size="lg" name="name" :label="__('Full name')" wire:model="name" placeholder="{{ __('Jane Doe') }}"
            required autofocus autocomplete="name" />

        <x-field size="lg" name="email" type="email" :label="__('Email')" wire:model="email"
            placeholder="you@example.com" required autocomplete="username" />

        <x-field size="lg" name="password" type="password" :label="__('Password')" wire:model="password"
            placeholder="{{ __('Create a password') }}" required autocomplete="new-password" />

        <x-field size="lg" name="password_confirmation" type="password" :label="__('Confirm password')"
            wire:model="password_confirmation" placeholder="{{ __('Repeat your password') }}" required
            autocomplete="new-password" />

        <x-btn block size="lg" wire:target="register">{{ __('Create account') }}</x-btn>

        <p class="text-center text-xs leading-relaxed text-tertiary">
            {{ __('By creating an account you agree to our') }}
            <a href="{{ route('legal.terms') }}" target="_blank" rel="noopener" class="font-medium text-teal-700 hover:underline">{{ __('Terms of Service') }}</a>
            {{ __('and') }}
            <a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener" class="font-medium text-teal-700 hover:underline">{{ __('Privacy Policy') }}</a>.
        </p>
    </form>

    <x-slot:footer>
        {{ __('Already have an account?') }}
        <a href="{{ route('login') }}" wire:navigate
            class="font-semibold text-teal-700 hover:text-teal-800 transition-colors duration-200">{{ __('Sign in') }}</a>
    </x-slot:footer>
</x-auth-layout>
