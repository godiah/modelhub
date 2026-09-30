<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Component;

new class extends Component {
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => ['required', 'string', 'current_password'],
                'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('password-updated');
    }
}; ?>

<div>
    <form wire:submit="updatePassword">
        <x-panel :title="__('Change password')" :description="__('Use a long, unique password so your account stays secure.')">
            <div class="space-y-5">
                <x-field id="update_password_current_password" name="current_password" type="password"
                    :label="__('Current password')" wire:model="current_password" icon="lock-closed"
                    placeholder="{{ __('Enter your current password') }}" autocomplete="current-password" />

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <x-field id="update_password_password" name="password" type="password" :label="__('New password')"
                            wire:model="password" icon="lock-closed" placeholder="{{ __('Enter a new password') }}"
                            autocomplete="new-password" />

                        <!-- Strength meter -->
                        <div x-data="{ strength: 0, width: '0%', color: 'bg-neutral-200' }" x-init="$watch('$wire.password', value => {
                            if (!value) {
                                strength = 0;
                                width = '0%';
                                color = 'bg-neutral-200';
                                return;
                            }

                            // Calculate password strength
                            let s = 0;
                            if (value.length > 6) s++;
                            if (value.length > 10) s++;
                            if (value.match(/[A-Z]/)) s++;
                            if (value.match(/[0-9]/)) s++;
                            if (value.match(/[^A-Za-z0-9]/)) s++;

                            strength = s;
                            width = (s * 20) + '%';

                            if (s <= 1) color = 'bg-red-500';
                            else if (s <= 2) color = 'bg-orange-500';
                            else if (s <= 3) color = 'bg-yellow-500';
                            else if (s <= 4) color = 'bg-secondary';
                            else color = 'bg-green-500';
                        })" class="mt-2">
                            <div class="h-1.5 w-full overflow-hidden rounded-full bg-neutral-100">
                                <div class="h-full transition-all duration-300 ease-out" :class="color"
                                    :style="'width: ' + width"></div>
                            </div>
                            <p class="mt-1 h-4 text-xs text-tertiary" x-show="strength > 0">
                                <span x-show="strength <= 2">{{ __('Weak') }}</span>
                                <span x-show="strength === 3">{{ __('Medium') }}</span>
                                <span x-show="strength === 4">{{ __('Strong') }}</span>
                                <span x-show="strength === 5">{{ __('Very strong') }}</span>
                            </p>
                        </div>
                    </div>

                    <x-field id="update_password_password_confirmation" name="password_confirmation" type="password"
                        :label="__('Confirm new password')" wire:model="password_confirmation" icon="shield-check"
                        placeholder="{{ __('Repeat password') }}" autocomplete="new-password" />
                </div>

                <p class="text-xs text-tertiary">
                    {{ __('At least 8 characters, including uppercase and lowercase letters, numbers and a symbol.') }}
                </p>
            </div>

            <x-slot:footer>
                <x-success-toast event="password-updated" :message="__('Password updated successfully!')" />
                <span class="hidden text-xs text-tertiary sm:block">{{ __('You stay signed in on this device.') }}</span>
                <x-btn wire:target="updatePassword">{{ __('Update password') }}</x-btn>
            </x-slot:footer>
        </x-panel>
    </form>
</div>
