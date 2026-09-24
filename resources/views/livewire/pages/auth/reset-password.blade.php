<?php

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component {
    #[Locked]
    public string $token = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Mount the component.
     */
    public function mount(string $token): void
    {
        $this->token = $token;

        $this->email = request()->string('email');
    }

    /**
     * Reset the password for the given user.
     */
    public function resetPassword(): void
    {
        $this->validate([
            'token' => ['required'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $status = Password::reset($this->only('email', 'password', 'password_confirmation', 'token'), function ($user) {
            $user
                ->forceFill([
                    'password' => Hash::make($this->password),
                    'remember_token' => Str::random(60),
                ])
                ->save();

            event(new PasswordReset($user));
        });

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        if ($status != Password::PASSWORD_RESET) {
            $this->addError('email', __($status));

            return;
        }

        Session::flash('status', __($status));

        $this->redirectRoute('login', navigate: true);
    }
}; ?>

<!-- Password Reset Form -->
<div class="w-full max-w-2xl p-8 mx-auto bg-white rounded-3xl shadow-2xl overflow-hidden ">
    <div class=" mx-auto">

        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-neutral-900 font-tertiary tracking-wider">
                {{ __('Reset Password') }}</h1>
            <p class="text-tertiary text-sm leading-relaxed font-main">
                Enter your new password below
            </p>
        </div>

        <!-- Password Reset Form -->
        <form wire:submit="resetPassword" class="space-y-4 font-main">

            <!-- Email Address -->
            <div>
                <x-input-label for="email" :value="__('Email')" class="sr-only" />
                <x-text-input wire:model="email" id="email"
                    class="w-full px-4 py-3 bg-neutral-50 border border-neutral-200 rounded-xl text-neutral-900 placeholder-neutral-400 transition-all duration-200 focus:outline-none"
                    type="email" name="email" placeholder="{{ __('Email') }}" required autofocus
                    autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <!-- Password -->
            <div>
                <x-input-label for="password" :value="__('Password')" class="sr-only" />
                <x-text-input wire:model="password" id="password"
                    class="w-full px-4 py-3 bg-neutral-50 border border-neutral-200 rounded-xl text-neutral-900 placeholder-neutral-400 transition-all duration-200 focus:outline-none"
                    type="password" name="password" placeholder="{{ __('New Password') }}" required
                    autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <!-- Confirm Password -->
            <div>
                <x-input-label for="password_confirmation" :value="__('Confirm Password')" class="sr-only" />
                <x-text-input wire:model="password_confirmation" id="password_confirmation"
                    class="w-full px-4 py-3 bg-neutral-50 border border-neutral-200 rounded-xl text-neutral-900 placeholder-neutral-400 transition-all duration-200 focus:outline-none"
                    type="password" name="password_confirmation" placeholder="{{ __('Confirm Password') }}" required
                    autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>

            <!-- Submit Button -->
            <x-primary-button
                class="font-secondary inline-flex items-center justify-center w-full px-6 py-3 bg-secondary border border-transparent rounded-xl font-semibold text-sm text-white tracking-widest hover:bg-teal-600 focus:bg-teal-600 active:bg-teal-700 focus:outline-none focus:ring-4 focus:ring-secondary focus:ring-offset-2 focus:ring-secondary/50 transition-all duration-200 transform hover:shadow-lg hover:shadow-secondary/30">
                {{ __('Reset Password') }}
            </x-primary-button>

        </form>
    </div>
</div>
