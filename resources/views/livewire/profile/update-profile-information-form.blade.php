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

<div>
    <form wire:submit="updateProfileInformation">
        <x-panel :title="__('Personal information')" :description="__('Your name and the email address we use to reach you.')">
            <div class="space-y-5">
                <x-field name="name" :label="__('Full name')" wire:model="name" icon="user"
                    placeholder="{{ __('Enter your full name') }}" required autocomplete="name" />

                <div class="space-y-3">
                    <x-field name="email" type="email" :label="__('Email address')" wire:model="email" icon="envelope"
                        placeholder="{{ __('you@example.com') }}" required autocomplete="username" />

                    @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !auth()->user()->hasVerifiedEmail())
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                            <div class="flex items-start gap-3">
                                <x-icon name="exclamation-triangle-solid" class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" />
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-amber-900">{{ __('Verify your email address') }}</p>
                                    <p class="mt-0.5 text-sm text-amber-800">{{ __('Your email address is unverified.') }}</p>
                                    <button type="button" wire:click.prevent="sendVerification"
                                        class="mt-2 inline-flex items-center gap-1.5 text-sm font-semibold text-amber-900 underline-offset-2 hover:underline">
                                        <x-icon name="paper-airplane" class="h-4 w-4" />
                                        {{ __('Resend verification email') }}
                                    </button>
                                </div>
                            </div>
                            <x-auth-session-status class="mt-3" :status="session('status')" />
                        </div>
                    @endif
                </div>
            </div>

            <x-slot:footer>
                <x-success-toast event="profile-info-updated" :message="__('Profile updated successfully!')" />
                <span class="hidden text-xs text-tertiary sm:block">{{ __('Changing your email requires verifying it again.') }}</span>
                <x-btn wire:target="updateProfileInformation">{{ __('Save changes') }}</x-btn>
            </x-slot:footer>
        </x-panel>
    </form>
</div>
