<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component {
    public string $name = '';
    public string $email = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-info-updated', name: $user->name);
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function sendVerification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<section class="bg-white rounded-xl shadow-sm border border-neutral-200 overflow-hidden">
    <x-section-header :title="__('Profile Information')"
        :subtitle="__('Update your account\'s profile information and email address.')">
        <x-slot:icon>
            <x-icon name="user" class="w-5 h-5 text-white" />
        </x-slot:icon>
    </x-section-header>

    <!-- Form Section -->
    <div class="p-6">
        <form wire:submit="updateProfileInformation" class="space-y-6">
            <!-- Name Field -->
            <div class="space-y-2">
                <x-form.label class="font-main" for="name">
                    {{ __('Full Name') }}
                </x-form.label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <x-icon name="user" class="h-5 w-5 text-neutral-400" />
                    </div>
                    <input wire:model="name" id="name" name="name" type="text"
                        class="block w-full pl-10 pr-3 py-3 border border-neutral-300 rounded-lg shadow-sm placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary transition-colors duration-200 font-main text-neutral-900"
                        placeholder="Enter your full name" required autofocus autocomplete="name" />
                </div>
                <x-form.error name="name" />
            </div>

            <!-- Email Field -->
            <div class="space-y-2">
                <x-form.label class="font-main" for="email">
                    {{ __('Email Address') }}
                </x-form.label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207">
                            </path>
                        </svg>
                    </div>
                    <input wire:model="email" id="email" name="email" type="email"
                        class="block w-full pl-10 pr-3 py-3 border border-neutral-300 rounded-lg shadow-sm placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary transition-colors duration-200 font-main text-neutral-900"
                        placeholder="Enter your email address" required autocomplete="username" />
                </div>
                <x-form.error name="email" />

                <!-- Email Verification Notice -->
                @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !auth()->user()->hasVerifiedEmail())
                    <div class="bg-accent/10 border border-accent/20 rounded-lg p-4 space-y-3">
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0">
                                <x-icon name="exclamation-triangle-solid" class="w-5 h-5 text-accent mt-0.5" />
                            </div>
                            <div class="flex-1">
                                <p class="text-sm text-accent font-medium font-main">
                                    {{ __('Email Verification Required') }}
                                </p>
                                <p class="text-sm text-neutral-600 font-main mt-1">
                                    {{ __('Your email address is unverified.') }}
                                </p>
                                <button wire:click.prevent="sendVerification"
                                    class="inline-flex items-center mt-2 text-sm font-medium text-secondary hover:text-secondary/80 transition-colors duration-200 font-main focus:outline-none focus:underline">
                                    <x-icon name="paper-airplane" class="w-4 h-4 mr-1" />
                                    {{ __('Resend verification email') }}
                                </button>
                            </div>
                        </div>

                        <x-auth-session-status :status="session('status')" />
                    </div>
                @endif
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-between pt-4 border-t border-neutral-200">
                <div class="flex items-center space-x-4">
                    <x-button size="lg" class="shadow-sm focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 text-sm" type="submit">
                        <x-icon name="check" class="w-4 h-4 mr-2" />
                        {{ __('Save Changes') }}
                    </x-button>

                    <x-success-toast event="profile-info-updated" :message="__('Profile updated successfully!')" />
                </div>
            </div>
        </form>
    </div>
</section>
