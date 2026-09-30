<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<section class="bg-white rounded-xl shadow-sm border border-red-200 overflow-hidden">
    <x-section-header :title="__('Delete Account')"
        :subtitle="__('Permanently remove your account and all associated data')" variant="danger">
        <x-slot:icon>
            <x-icon name="exclamation-triangle-2" class="w-6 h-6 text-white" />
        </x-slot:icon>
    </x-section-header>

    <!-- Content Section -->
    <div class="p-6">
        <!-- Warning Notice -->
        <div class="bg-accent/10 border border-accent/30 rounded-lg p-4 mb-6">
            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                    <x-icon name="exclamation-triangle-solid" class="w-6 h-6 text-accent mt-0.5" />
                </div>
                <div class="flex-1">
                    <h3 class="font-semibold text-accent font-main">
                        {{ __('Warning: This action cannot be undone') }}
                    </h3>
                    <p class="text-neutral-700 text-sm font-medium font-tertiary mt-2 leading-relaxed">
                        {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Data Loss Information -->
        <div class="bg-neutral-50 rounded-lg p-4 mb-6">
            <h4 class="text-sm font-semibold text-neutral-800 font-main mb-3">
                {{ __('The following data will be permanently deleted:') }}
            </h4>
            <ul class="space-y-2 text-sm text-neutral-600 font-tertiary font-medium">
                <li class="flex items-center space-x-2">
                    <x-icon name="x-circle-solid" class="w-4 h-4 text-red-500" />
                    <span>{{ __('Profile information and settings') }}</span>
                </li>
                <li class="flex items-center space-x-2">
                    <x-icon name="x-circle-solid" class="w-4 h-4 text-red-500" />
                    <span>{{ __('All uploaded files and documents') }}</span>
                </li>
                <li class="flex items-center space-x-2">
                    <x-icon name="x-circle-solid" class="w-4 h-4 text-red-500" />
                    <span>{{ __('Account history and activity logs') }}</span>
                </li>
                <li class="flex items-center space-x-2">
                    <x-icon name="x-circle-solid" class="w-4 h-4 text-red-500" />
                    <span>{{ __('All associated data and preferences') }}</span>
                </li>
            </ul>
        </div>

        <!-- Delete Button -->
        <div class="flex justify-end">
            <x-button variant="danger" size="lg" class="shadow-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 text-sm" x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')">
                <x-icon name="trash" class="w-4 h-4 mr-2" />
                {{ __('Delete Account') }}
            </x-button>
        </div>
    </div>

    <x-modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable>
        <form wire:submit="deleteUser">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-red-600 to-red-700 px-6 py-4">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                        <x-icon name="exclamation-triangle-2" class="w-6 h-6 text-white" />
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-white font-main">
                            {{ __('Confirm Account Deletion') }}
                        </h2>
                        <p class="text-red-100 text-sm font-main">
                            {{ __('This action cannot be undone') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="px-6 py-6">
                <!-- Warning Message -->
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                    <div class="flex items-start space-x-3">
                        <x-icon name="x-circle-solid" class="w-6 h-6 text-red-600 mt-0.5 flex-shrink-0" />
                        <div>
                            <h3 class="text-sm font-semibold text-red-800 font-main">
                                {{ __('Are you absolutely sure?') }}
                            </h3>
                            <p class="text-sm text-red-700 font-main mt-1 leading-relaxed">
                                {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Password Confirmation -->
                <div class="space-y-2">
                    <label for="password" class="block text-sm font-medium text-neutral-700 font-main">
                        {{ __('Confirm with your password') }}
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <x-icon name="lock-closed" class="h-5 w-5 text-neutral-400" />
                        </div>
                        <input wire:model="password" id="password" name="password" type="password"
                            class="block w-full pl-10 pr-3 py-3 border border-neutral-300 rounded-lg shadow-sm placeholder-neutral-400 focus:outline-none focus:ring-1 focus:ring-red-500 focus:border-red-500 transition-colors duration-200 font-main text-neutral-900"
                            placeholder="{{ __('Enter your password to confirm') }}" required />
                    </div>
                    @error('password')
                        <div class="flex items-center space-x-2 text-red-600 text-sm font-main">
                            <x-icon name="exclamation-circle-solid" class="w-4 h-4" />
                            <span>{{ $message }}</span>
                        </div>
                    @enderror
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="bg-neutral-50 px-6 py-4 flex justify-end space-x-3 border-t border-neutral-200 text-sm">
                <button type="button" x-on:click="$dispatch('close')"
                    class="inline-flex items-center px-4 py-2 bg-white border border-neutral-300 rounded-lg text-neutral-700 hover:bg-neutral-50 focus:outline-none focus:ring-2 focus:ring-neutral-500 focus:ring-offset-2 transition-colors duration-200 font-main font-medium">
                    {{ __('Cancel') }}
                </button>

                <button type="submit"
                    class="inline-flex items-center px-6 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 font-main">
                    <x-icon name="trash" class="w-4 h-4 mr-2" />
                    {{ __('Delete Account') }}
                </button>
            </div>
        </form>
    </x-modal>
</section>
