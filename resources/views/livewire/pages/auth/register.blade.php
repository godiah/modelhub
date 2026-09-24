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

<div>
    <!-- Main Container -->
    <div class="bg-white rounded-3xl shadow-2xl overflow-hidden max-w-7xl w-full flex">

        <!-- Left Side - Illustration Area -->
        <div
            class="font-main hidden lg:flex lg:w-1/2 bg-gradient-to-br from-secondary via-teal-400 to-secondary relative overflow-hidden">
            <!-- Decorative Background Elements -->
            <div class="absolute inset-0 overflow-hidden">
                <div class="absolute top-1/4 left-1/4 w-20 h-20 bg-white/10 rounded-full animate-pulse"></div>
                <div class="absolute top-2/4 right-1/4 w-16 h-16 bg-white/10 rounded-full animate-pulse delay-75"></div>
                <div class="absolute bottom-1/3 left-1/5 w-12 h-12 bg-white/10 rounded-full animate-pulse delay-150">
                </div>
            </div>

            <!-- Main Illustration Area -->
            <div class="relative z-10 flex items-center justify-center w-full p-12">
                <div class="text-center text-white">
                    <!-- Logo or Illustration Placeholder -->
                    <div
                        class="w-48 h-48 mx-auto mb-8 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-sm">
                        <svg class="w-24 h-24 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold mb-4 font-tertiary">{{ config('app.name', 'ModelHub') }}</h2>
                    <p class="text-white/90 text-base leading-relaxed">
                        Register now to post or find 3D modeling jobs, connect with talented freelance architects, and
                        unlock a world of premium models to elevate your projects.
                    </p>
                </div>
            </div>
        </div>

        <!-- Right Side - Register Form -->
        <div class="w-full lg:w-1/2 p-8 lg:p-8">
            <div class=" mx-auto">

                <!-- Header -->
                <div class="text-center mb-8">
                    <h1 class="text-2xl font-bold text-neutral-900 font-tertiary tracking-wider">
                        {{ __('Create Account') }}</h1>
                    <p class="text-tertiary text-sm leading-relaxed font-main">
                        Sign up to get started
                    </p>
                </div>

                <!-- Register Form -->
                <form wire:submit="register" class="space-y-4 font-main">
                    <!-- Name -->
                    <div>
                        <x-input-label for="name" :value="__('Name')" class="sr-only" />
                        <x-text-input wire:model="name" id="name"
                            class="w-full px-4 py-3 bg-neutral-50 border border-neutral-200 rounded-xl text-neutral-900 placeholder-neutral-400 transition-all duration-200 focus:outline-none"
                            type="text" name="name" placeholder="{{ __('Full Name') }}" required autofocus
                            autocomplete="name" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <!-- Email Address -->
                    <div>
                        <x-input-label for="email" :value="__('Email')" class="sr-only" />
                        <x-text-input wire:model="email" id="email"
                            class="w-full px-4 py-3 bg-neutral-50 border border-neutral-200 rounded-xl text-neutral-900 placeholder-neutral-400 transition-all duration-200 focus:outline-none"
                            type="email" name="email" placeholder="{{ __('Email') }}" required
                            autocomplete="username" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <!-- Password -->
                    <div>
                        <x-input-label for="password" :value="__('Password')" class="sr-only" />
                        <x-text-input wire:model="password" id="password"
                            class="w-full px-4 py-3 bg-neutral-50 border border-neutral-200 rounded-xl text-neutral-900 placeholder-neutral-400 transition-all duration-200 focus:outline-none"
                            type="password" name="password" placeholder="{{ __('Password') }}" required
                            autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <x-input-label for="password_confirmation" :value="__('Confirm Password')" class="sr-only" />
                        <x-text-input wire:model="password_confirmation" id="password_confirmation"
                            class="w-full px-4 py-3 bg-neutral-50 border border-neutral-200 rounded-xl text-neutral-900 placeholder-neutral-400 transition-all duration-200 focus:outline-none"
                            type="password" name="password_confirmation" placeholder="{{ __('Confirm Password') }}"
                            required autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                    </div>

                    <!-- Submit Button -->
                    <x-primary-button
                        class="font-secondary inline-flex items-center justify-center w-full  px-6 py-3 bg-secondary border border-transparent rounded-xl font-semibold text-sm text-white tracking-widest hover:bg-teal-600 focus:bg-teal-600 active:bg-teal-700 focus:outline-none focus:ring-4 focus:ring-secondary focus:ring-offset-2 focus:ring-secondary/50 transition-all duration-200 transform hover:shadow-lg hover:shadow-secondary/30">
                        {{ __('Create Account') }}
                    </x-primary-button>

                </form>

                <!-- Footer Links -->
                <div class="mt-4 space-y-2 text-center font-main">
                    <!-- Sign In Link -->
                    <p class="text-sm text-tertiary">
                        {{ __('Already have an account?') }}
                        <a href="{{ route('login') }}" wire:navigate
                            class="text-secondary hover:text-teal-600 font-medium transition-colors duration-200">
                            {{ __('Sign in') }}
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
