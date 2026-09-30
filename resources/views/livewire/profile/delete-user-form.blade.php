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

<div>
    <x-panel danger :title="__('Delete account')" :description="__('Permanently remove your account and everything associated with it.')">
        <div class="space-y-5">
            <div class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4">
                <x-icon name="exclamation-triangle-solid" class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" />
                <div>
                    <p class="text-sm font-semibold text-amber-900">{{ __('This action cannot be undone') }}</p>
                    <p class="mt-1 text-sm leading-relaxed text-amber-800">
                        {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
                    </p>
                </div>
            </div>

            <div>
                <p class="text-sm font-medium text-neutral-800">{{ __('The following will be permanently deleted:') }}</p>
                <ul class="mt-3 grid gap-2 text-sm text-neutral-600 sm:grid-cols-2">
                    @foreach ([__('Profile information and settings'), __('All uploaded files and documents'), __('Account history and activity logs'), __('All associated data and preferences')] as $item)
                        <li class="flex items-center gap-2">
                            <x-icon name="x-circle-solid" class="h-4 w-4 shrink-0 text-red-500" />
                            {{ $item }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <x-slot:footer>
            <span class="text-xs text-tertiary">{{ __('You will be asked for your password to confirm.') }}</span>
            <x-btn variant="danger" type="button" x-data=""
                x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')">
                <x-icon name="trash" class="h-4 w-4" />
                {{ __('Delete account') }}
            </x-btn>
        </x-slot:footer>
    </x-panel>

    <x-modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable>
        <form wire:submit="deleteUser">
            <x-modal.header :title="__('Confirm account deletion')" icon="exclamation-triangle-2" />

            <div class="space-y-5 px-6 py-6">
                <div class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4">
                    <x-icon name="x-circle-solid" class="mt-0.5 h-5 w-5 shrink-0 text-red-600" />
                    <div>
                        <p class="text-sm font-semibold text-red-800">{{ __('Are you absolutely sure?') }}</p>
                        <p class="mt-1 text-sm leading-relaxed text-red-700">
                            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
                        </p>
                    </div>
                </div>

                <x-field id="delete_account_password" name="password" type="password"
                    :label="__('Confirm with your password')" wire:model="password" icon="lock-closed"
                    placeholder="{{ __('Enter your password to confirm') }}" required />
            </div>

            <x-modal.footer>
                <x-btn variant="secondary" type="button" x-on:click="$dispatch('close')">{{ __('Cancel') }}</x-btn>
                <x-btn variant="danger" wire:target="deleteUser">
                    <x-icon name="trash" class="h-4 w-4" />
                    {{ __('Delete account') }}
                </x-btn>
            </x-modal.footer>
        </form>
    </x-modal>
</div>
