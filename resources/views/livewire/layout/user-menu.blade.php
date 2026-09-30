<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component {
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<x-dropdown align="right" width="64">
    <x-slot name="trigger">
        <button type="button"
            class="flex items-center gap-2 rounded-xl p-1 pr-2 transition-colors duration-200 hover:bg-neutral-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40"
            aria-label="{{ __('Account menu') }}">
            <x-user-avatar :user="auth()->user()" />
            <x-icon name="chevron-down" class="hidden h-4 w-4 text-neutral-500 sm:block" />
        </button>
    </x-slot>

    <x-slot name="content">
        <div class="flex items-center gap-3 border-b border-neutral-100 px-4 py-3">
            <x-user-avatar :user="auth()->user()" size="h-10 w-10" />
            <div class="min-w-0">
                <p class="truncate font-main text-sm font-semibold text-neutral-900">{{ auth()->user()->name }}</p>
                <p class="truncate font-main text-xs text-neutral-500">{{ auth()->user()->email }}</p>
            </div>
        </div>

        <div class="py-2">
            <x-dropdown-link :href="route('profile')" wire:navigate>
                <x-slot name="icon">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </x-slot>
                {{ __('Profile') }}
            </x-dropdown-link>
        </div>

        <div class="border-t border-neutral-100 py-2">
            <button wire:click="logout"
                class="group flex w-full items-center px-4 py-3 font-main text-sm text-red-600 transition-colors duration-200 hover:bg-red-50">
                <x-icon name="arrow-right-on-rectangle" class="mr-3 h-4 w-4" />
                {{ __('Log Out') }}
            </button>
        </div>
    </x-slot>
</x-dropdown>
