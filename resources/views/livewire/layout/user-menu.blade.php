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

@php
    $user = auth()->user();
    $store = $user->isApprovedSeller() ? \App\Models\SellerProfile::where('user_id', $user->id)->first(['slug', 'display_name']) : null;

    // Account-level pages only: the sidebar carries the day-to-day ones. The profile tabs are linked by their hash.
    $account = [
        [__('Your profile'), route('profile').'#overview', 'user'],
        [__('Edit profile'), route('profile').'#profile', 'pencil-square'],
        [__('Security'), route('profile').'#security', 'shield-check'],
    ];
    $help = [
        [__('Terms of service'), route('legal.terms'), 'document-text'],
        [__('Privacy policy'), route('legal.privacy'), 'shield-check'],
        [__('Cancellation policy'), route('engagements.policy'), 'scale'],
    ];
    $item = 'flex w-full items-center gap-3 px-4 py-2 text-start font-main text-sm text-neutral-700 transition-colors duration-150 hover:bg-neutral-50 hover:text-neutral-900 focus:bg-neutral-50 focus:outline-none';
@endphp
<x-dropdown align="right" width="64" content-classes="bg-white max-h-[calc(100vh-5rem)] overflow-y-auto">
    <x-slot name="trigger">
        <button type="button"
            class="flex items-center gap-2 rounded-xl p-1 pr-2 transition-colors duration-200 hover:bg-neutral-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40"
            aria-label="{{ __('Account menu') }}" :aria-expanded="open.toString()">
            <x-user-avatar :user="$user" />
            <x-icon name="chevron-down" class="hidden h-4 w-4 text-neutral-500 sm:block" />
        </button>
    </x-slot>

    <x-slot name="content">
        <div class="flex items-center gap-3 border-b border-neutral-100 px-4 py-3.5">
            <x-user-avatar :user="$user" size="h-11 w-11" />
            <div class="min-w-0">
                <p class="truncate font-main text-sm font-semibold text-neutral-900">{{ $user->name }}</p>
                <p class="truncate font-main text-xs text-neutral-500">{{ $user->email }}</p>
            </div>
        </div>

        <nav aria-label="{{ __('Account') }}" class="py-2">
            @foreach ($account as [$label, $href, $icon])
                <a href="{{ $href }}" wire:navigate class="{{ $item }}">
                    <x-icon :name="$icon" class="h-4 w-4 shrink-0 text-neutral-400" />{{ $label }}
                </a>
            @endforeach
        </nav>

        @if ($store)
            <nav aria-label="{{ __('Your store') }}" class="border-t border-neutral-100 py-2">
                <p class="px-4 pb-1 pt-1 text-[11px] font-semibold uppercase tracking-wider text-neutral-400">{{ __('Your store') }}</p>
                <a href="{{ route('seller.store.edit') }}" wire:navigate class="{{ $item }}">
                    <x-icon name="tag" class="h-4 w-4 shrink-0 text-neutral-400" /><span class="min-w-0 flex-1 truncate">{{ $store->display_name }}</span>
                </a>
                <a href="{{ route('sellers.show', $store->slug) }}" class="{{ $item }}">
                    <x-icon name="arrow-top-right-on-square" class="h-4 w-4 shrink-0 text-neutral-400" />{{ __('View public storefront') }}
                </a>
            </nav>
        @endif

        <nav aria-label="{{ __('Help and legal') }}" class="border-t border-neutral-100 py-2">
            <p class="px-4 pb-1 pt-1 text-[11px] font-semibold uppercase tracking-wider text-neutral-400">{{ __('Help & legal') }}</p>
            @foreach ($help as [$label, $href, $icon])
                <a href="{{ $href }}" wire:navigate class="{{ $item }}">
                    <x-icon :name="$icon" class="h-4 w-4 shrink-0 text-neutral-400" />{{ $label }}
                </a>
            @endforeach
        </nav>

        <div class="border-t border-neutral-100 py-2">
            <button type="button" wire:click="logout" class="flex w-full items-center gap-3 px-4 py-2 text-start font-main text-sm font-medium text-red-600 transition-colors duration-150 hover:bg-red-50 focus:bg-red-50 focus:outline-none">
                <x-icon name="arrow-right-on-rectangle" class="h-4 w-4 shrink-0" />
                {{ __('Log out') }}
            </button>
        </div>
    </x-slot>
</x-dropdown>
