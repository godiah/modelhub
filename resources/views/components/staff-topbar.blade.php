@props(['crumbs' => [], 'staff'])

{{-- The staff portal's top bar: breadcrumb, global search (opens the command palette), notifications and the account menu. --}}
@php
    $unread = \App\Support\Navigation\StaffMenu::count('notifications', $staff);
    $roles = $staff->roles->pluck('name');
@endphp

<div class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-neutral-200/70 bg-paper/90 px-4 backdrop-blur sm:px-6 lg:px-8">
    <button type="button" @click="mobileOpen = true" aria-label="{{ __('Open menu') }}" aria-controls="app-sidebar" class="-ml-1 rounded-lg p-2 text-neutral-600 hover:bg-neutral-200/60 hover:text-neutral-900 lg:hidden">
        <x-icon name="bars-3" class="h-6 w-6" />
    </button>

    <nav aria-label="{{ __('Breadcrumb') }}" class="min-w-0">
        <ol class="flex min-w-0 items-center gap-2 text-sm">
            @foreach ($crumbs as $crumb)
                @php $isLast = $loop->last; @endphp
                <li @class(['flex min-w-0 items-center gap-2', 'hidden sm:flex' => ! $isLast])>
                    @unless ($loop->first)<span class="hidden text-neutral-300 sm:inline" aria-hidden="true">/</span>@endunless
                    @if ($crumb['url'])
                        <a href="{{ $crumb['url'] }}" wire:navigate class="truncate text-tertiary transition-colors duration-150 hover:text-teal-700 focus:outline-none focus-visible:underline">{{ __($crumb['label']) }}</a>
                    @else
                        <span @class(['truncate', 'font-semibold text-neutral-900' => $isLast, 'text-tertiary' => ! $isLast]) @if ($isLast) aria-current="page" @endif>{{ __($crumb['label']) }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>

    <div class="ml-auto flex items-center gap-2 sm:gap-3">
        <button type="button" @click="$dispatch('open-palette')" aria-label="{{ __('Search') }}" aria-keyshortcuts="Control+K Meta+K"
            class="group hidden w-64 items-center gap-2 rounded-xl border border-neutral-200 bg-white px-3 py-2 text-sm text-neutral-400 shadow-sm transition-colors hover:border-neutral-300 hover:text-neutral-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40 md:flex">
            <x-icon name="magnifying-glass" class="h-4 w-4 shrink-0" />
            <span class="flex-1 text-left">{{ __('Search or jump to…') }}</span>
            <kbd class="rounded border border-neutral-200 bg-neutral-50 px-1.5 py-0.5 font-sans text-[11px] font-medium text-neutral-500"><span x-text="navigator.platform.includes('Mac') ? '⌘' : 'Ctrl'">Ctrl</span> K</kbd>
        </button>
        <button type="button" @click="$dispatch('open-palette')" aria-label="{{ __('Search') }}" class="rounded-xl p-2 text-neutral-600 transition-colors hover:bg-neutral-200/60 hover:text-neutral-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40 md:hidden">
            <x-icon name="magnifying-glass" class="h-6 w-6" />
        </button>

        <a href="{{ route('admin.notifications.index') }}" wire:navigate aria-label="{{ $unread > 0 ? __(':count unread notifications', ['count' => $unread]) : __('Notifications') }}"
            class="relative rounded-xl p-2 text-neutral-600 transition-colors duration-200 hover:bg-neutral-200/60 hover:text-neutral-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">
            <x-icon name="bell" class="h-6 w-6" />
            @if ($unread > 0)
                <span class="absolute right-0.5 top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-semibold leading-none text-white ring-2 ring-paper">{{ $unread > 99 ? '99+' : $unread }}</span>
            @endif
        </a>

        <x-dropdown align="right" width="w-64">
            <x-slot name="trigger">
                <button type="button" aria-label="{{ __('Account menu') }}" :aria-expanded="open.toString()"
                    class="flex items-center gap-2 rounded-xl p-1 pr-2 transition-colors hover:bg-neutral-200/60 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">
                    <x-user-avatar :user="$staff" />
                    <x-icon name="chevron-down" class="hidden h-4 w-4 text-neutral-500 sm:block" />
                </button>
            </x-slot>
            <x-slot name="content">
                <div class="border-b border-neutral-100 px-4 py-3">
                    <p class="truncate text-sm font-semibold text-neutral-900">{{ $staff->name }}</p>
                    <p class="truncate text-xs text-neutral-500">{{ $staff->email }}</p>
                    @if ($roles->isNotEmpty())
                        <p class="mt-1.5 flex flex-wrap gap-1">@foreach ($roles as $role)<span class="rounded-full bg-neutral-100 px-2 py-0.5 text-[11px] font-medium text-neutral-700">{{ $role }}</span>@endforeach</p>
                    @endif
                </div>
                <nav class="py-2" aria-label="{{ __('Account') }}">
                    <a href="{{ route('admin.account.edit') }}" wire:navigate class="flex w-full items-center gap-3 px-4 py-2 text-sm text-neutral-700 transition-colors hover:bg-neutral-50 hover:text-neutral-900">
                        <x-icon name="user" class="h-4 w-4 shrink-0 text-neutral-400" />{{ __('Your account') }}
                    </a>
                </nav>
                <div class="border-t border-neutral-100 py-2">
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-3 px-4 py-2 text-start text-sm font-medium text-red-600 transition-colors hover:bg-red-50 focus:outline-none">
                            <x-icon name="arrow-right-on-rectangle" class="h-4 w-4 shrink-0" />{{ __('Sign out') }}
                        </button>
                    </form>
                </div>
            </x-slot>
        </x-dropdown>
    </div>
</div>
