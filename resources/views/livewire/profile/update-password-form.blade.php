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

<section class="bg-white rounded-xl shadow-sm border border-neutral-200 overflow-hidden">
    <!-- Header Section -->
    <div class="bg-gradient-to-r from-primary to-primary/90 px-6 py-5">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                    </path>
                </svg>
            </div>
            <div>
                <h2 class="text-xl font-semibold text-white font-secondary">
                    {{ __('Update Password') }}
                </h2>
                <p class="text-white/60 text-sm font-main mt-1">
                    {{ __('Ensure your account is using a long, random password to stay secure.') }}
                </p>
            </div>
        </div>
    </div>

    <!-- Form Section -->
    <div class="p-6">
        <form wire:submit="updatePassword" class="space-y-6">
            <!-- Current Password Field -->
            <div class="space-y-2">
                <label for="update_password_current_password"
                    class="block text-sm font-medium text-neutral-700 font-main">
                    {{ __('Current Password') }}
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z">
                            </path>
                        </svg>
                    </div>
                    <input wire:model="current_password" id="update_password_current_password" name="current_password"
                        type="password"
                        class="block w-full pl-10 pr-10 py-3 border border-neutral-300 rounded-lg shadow-sm placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary transition-colors duration-200 font-main text-neutral-900"
                        placeholder="Enter your current password" autocomplete="current-password" />
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                        <button type="button" class="text-neutral-400 hover:text-neutral-600 focus:outline-none"
                            onclick="togglePasswordVisibility('update_password_current_password')">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                </path>
                            </svg>
                        </button>
                    </div>
                </div>
                @error('current_password')
                    <div class="flex items-center space-x-2 text-red-600 text-sm font-main">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd"></path>
                        </svg>
                        <span>{{ $message }}</span>
                    </div>
                @enderror
            </div>

            <!-- New Password Field -->
            <div class="space-y-2">
                <label for="update_password_password" class="block text-sm font-medium text-neutral-700 font-main">
                    {{ __('New Password') }}
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                            </path>
                        </svg>
                    </div>
                    <input wire:model="password" id="update_password_password" name="password" type="password"
                        class="block w-full pl-10 pr-10 py-3 border border-neutral-300 rounded-lg shadow-sm placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary transition-colors duration-200 font-main text-neutral-900"
                        placeholder="Enter your new password" autocomplete="new-password" />
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                        <button type="button" class="text-neutral-400 hover:text-neutral-600 focus:outline-none"
                            onclick="togglePasswordVisibility('update_password_password')">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                </path>
                            </svg>
                        </button>
                    </div>
                </div>
                @error('password')
                    <div class="flex items-center space-x-2 text-red-600 text-sm font-main">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd"></path>
                        </svg>
                        <span>{{ $message }}</span>
                    </div>
                @enderror

                <!-- Password Strength Indicator -->
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
                
                    // Update UI
                    width = (s * 20) + '%';
                
                    if (s <= 1) color = 'bg-red-500';
                    else if (s <= 2) color = 'bg-orange-500';
                    else if (s <= 3) color = 'bg-yellow-500';
                    else if (s <= 4) color = 'bg-secondary';
                    else color = 'bg-green-500';
                })" class="mt-2">
                    <div class="w-full h-1.5 bg-neutral-200 rounded-full overflow-hidden">
                        <div class="h-full transition-all duration-300 ease-out" :class="color"
                            :style="'width: ' + width"></div>
                    </div>
                    <div class="flex justify-between mt-1">
                        <p class="text-xs text-neutral-500 font-main" x-show="strength > 0">
                            <span x-show="strength <= 2">Weak</span>
                            <span x-show="strength === 3">Medium</span>
                            <span x-show="strength === 4">Strong</span>
                            <span x-show="strength === 5">Very Strong</span>
                        </p>
                        <p class="text-xs text-neutral-500 font-main">
                            <template x-if="strength > 0">
                                <span x-text="width"></span>
                            </template>
                        </p>
                    </div>
                </div>

                <p class="text-xs text-neutral-500 font-main mt-1">
                    {{ __('Password should be at least 8 characters and include uppercase, lowercase, numbers, and special characters.') }}
                </p>
            </div>

            <!-- Confirm Password Field -->
            <div class="space-y-2">
                <label for="update_password_password_confirmation"
                    class="block text-sm font-medium text-neutral-700 font-main">
                    {{ __('Confirm Password') }}
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-neutral-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                            </path>
                        </svg>
                    </div>
                    <input wire:model="password_confirmation" id="update_password_password_confirmation"
                        name="password_confirmation" type="password"
                        class="block w-full pl-10 pr-10 py-3 border border-neutral-300 rounded-lg shadow-sm placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary transition-colors duration-200 font-main text-neutral-900"
                        placeholder="Confirm your new password" autocomplete="new-password" />
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                        <button type="button" class="text-neutral-400 hover:text-neutral-600 focus:outline-none"
                            onclick="togglePasswordVisibility('update_password_password_confirmation')">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                </path>
                            </svg>
                        </button>
                    </div>
                </div>
                @error('password_confirmation')
                    <div class="flex items-center space-x-2 text-red-600 text-sm font-main">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd"></path>
                        </svg>
                        <span>{{ $message }}</span>
                    </div>
                @enderror
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-between pt-4 border-t border-neutral-200">
                <div class="flex items-center space-x-4">
                    <button type="submit"
                        class="inline-flex items-center px-6 py-3 bg-primary hover:bg-primary/90 text-white font-medium rounded-lg shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 font-main text-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                            </path>
                        </svg>
                        {{ __('Update Password') }}
                    </button>

                    <!-- Success Message -->
                    <div x-data="{ show: false }" x-show="show" x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 transform scale-90"
                        x-transition:enter-end="opacity-100 transform scale-100"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 transform scale-100"
                        x-transition:leave-end="opacity-0 transform scale-90"
                        @password-updated.window="show = true; setTimeout(() => show = false, 3000)"
                        class="flex items-center space-x-2 text-secondary font-medium text-sm font-main"
                        style="display: none;">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd"></path>
                        </svg>
                        <span>{{ __('Password updated successfully!') }}</span>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<!-- Password Toggle Script -->
<script>
    function togglePasswordVisibility(inputId) {
        const input = document.getElementById(inputId);
        if (input.type === 'password') {
            input.type = 'text';
        } else {
            input.type = 'password';
        }
    }
</script>
