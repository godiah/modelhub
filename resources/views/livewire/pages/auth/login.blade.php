<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component {
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
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
                        Discover and post 3D modeling jobs, connect with top-tier freelance architects, and access
                        premium models to bring your architectural visions to life.
                    </p>
                </div>
            </div>
        </div>

        <!-- Right Side - Login Form -->
        <div class="w-full lg:w-1/2 p-8 lg:p-8">
            <div class=" mx-auto">

                <!-- Header -->
                <div class="text-center mb-8">
                    <h1 class="text-2xl font-bold text-neutral-900 font-tertiary tracking-wider">
                        {{ __('Welcome Back !') }}</h1>
                    <p class="text-tertiary text-sm leading-relaxed font-main">
                        Sign in to your account
                    </p>
                </div>

                <!-- Session Status -->
                <x-auth-session-status class="mb-6 font-main" :status="session('status')" />

                <!-- Login Form -->
                <form wire:submit="login" class="space-y-4 font-main">

                    <!-- Email Field -->
                    <div>
                        <x-input-label for="email" :value="__('Email')" class="sr-only" />
                        <x-text-input wire:model="form.email" id="email"
                            class="w-full px-4 py-3 bg-neutral-50 border border-neutral-200 rounded-xl text-neutral-900 placeholder-neutral-400 transition-all duration-200 focus:outline-none"
                            type="email" name="email" placeholder="{{ __('Email') }}" required autofocus
                            autocomplete="username" />
                        <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
                    </div>

                    <!-- Password Field -->
                    <div>
                        <x-input-label for="password" :value="__('Password')" class="sr-only" />
                        <x-text-input wire:model="form.password" id="password"
                            class="w-full px-4 py-3 bg-neutral-50 border border-neutral-200 rounded-xl text-neutral-900 placeholder-neutral-400 transition-all duration-200 focus:outline-none"
                            type="password" name="password" placeholder="{{ __('Password') }}" required
                            autocomplete="current-password" />
                        <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center">
                        <input wire:model="form.remember" id="remember" type="checkbox"
                            class="w-4 h-4 text-secondary bg-neutral-50 border-neutral-300 rounded focus:ring-secondary focus:ring-1"
                            name="remember">
                        <label for="remember" class="ml-3 text-sm text-tertiary">
                            {{ __('Remember me') }}
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <x-primary-button
                        class="font-secondary inline-flex items-center justify-center w-full px-6 py-3 bg-secondary border border-transparent rounded-xl font-semibold text-sm text-white tracking-widest hover:bg-teal-600 focus:bg-teal-600 active:bg-teal-700 focus:outline-none focus:ring-4 focus:ring-secondary focus:ring-offset-2 focus:ring-secondary/50 transition-all duration-200 transform hover:shadow-lg hover:shadow-secondary/30">
                        {{ __('Continue') }}
                    </x-primary-button>

                </form>

                <!-- Social Login -->
                <div class="mt-6">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-neutral-200"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-4 bg-white text-tertiary font-main">{{ __('Or continue with') }}</span>
                        </div>
                    </div>

                    <div class="mt-2 flex justify-center space-x-4">
                        <!-- Facebook Login -->
                        <button type="button"
                            class="w-12 h-12 bg-neutral-50 hover:bg-neutral-100 rounded-xl flex items-center justify-center transition-all duration-200 transform  hover:shadow-md">
                            <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                            </svg>
                        </button>

                        <!-- Google Login -->
                        <button type="button"
                            class="w-12 h-12 bg-neutral-50 hover:bg-neutral-100 rounded-xl flex items-center justify-center transition-all duration-200 transform hover:shadow-md">
                            <svg class="w-5 h-5" viewBox="0 0 24 24">
                                <path fill="#4285F4"
                                    d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                                <path fill="#34A853"
                                    d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                                <path fill="#FBBC05"
                                    d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                                <path fill="#EA4335"
                                    d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Footer Links -->
                <div class="mt-2 space-y-2 text-center font-main">
                    <!-- Sign Up Link -->
                    <p class="text-sm text-tertiary">
                        {{ __("Don't have an account?") }}
                        <a href="{{ route('register') }}" wire:navigate
                            class="text-secondary hover:text-teal-600 font-medium transition-colors duration-200">
                            {{ __('Sign up') }}
                        </a>
                    </p>

                    <!-- Forgot Password -->
                    @if (Route::has('password.request'))
                        <a class="block text-sm text-tertiary hover:text-secondary transition-colors duration-200"
                            href="{{ route('password.request') }}" wire:navigate>
                            {{ __('Forgot your password?') }}
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
