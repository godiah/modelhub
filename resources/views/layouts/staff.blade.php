@php
    $staff = auth()->user();
    $menu = \App\Support\Navigation\StaffMenu::for($staff);
    $pageTitle = $title ? $title.' · Staff' : 'Staff portal';
    $roles = $staff->roles->pluck('name');
    $section = collect($menu)->first(fn ($group) => collect($group['items'])->contains('active', true))['label'] ?? null;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('partials.app-head')
    <meta name="robots" content="noindex, nofollow">
    {{-- Staff pages have no Livewire components, which is where Alpine (menus, the mobile drawer, dialogs) normally comes from --}}
    @livewireStyles
</head>

{{-- The staff portal shell. Deliberately unlike the member app: a dark rail, its own account menu, and no member pages. --}}

<body class="bg-neutral-100 font-sans text-neutral-900 antialiased">
    <div x-data="{ drawer: false }" @keydown.escape.window="drawer = false" class="min-h-screen">
        @include('partials.flash-messages')

        <div x-show="drawer" x-cloak x-transition.opacity @click="drawer = false" aria-hidden="true" class="fixed inset-0 z-40 bg-neutral-900/50 lg:hidden"></div>

        <aside id="staff-nav" aria-label="{{ __('Staff navigation') }}"
            class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col bg-neutral-900 text-neutral-300 transition-transform duration-200 ease-out lg:z-30 lg:w-64 lg:translate-x-0"
            :class="{ '!translate-x-0 shadow-2xl': drawer }" x-trap.noscroll="drawer">
            <div class="flex h-16 shrink-0 items-center justify-between border-b border-white/10 px-5">
                <a href="{{ route('admin.dashboard') }}" class="flex min-w-0 items-center gap-3" aria-label="{{ config('app.name') }} {{ __('staff portal') }}">
                    <x-application-logo variant="mark" class="h-8 w-auto shrink-0 brightness-0 invert" />
                    <span class="min-w-0">
                        <span class="block truncate font-secondary text-sm font-semibold uppercase tracking-[0.18em] text-white">{{ config('app.name') }}</span>
                        <span class="block text-[10px] font-semibold uppercase tracking-widest text-teal-400">{{ __('Staff portal') }}</span>
                    </span>
                </a>
                <button type="button" @click="drawer = false" aria-label="{{ __('Close menu') }}" class="rounded-lg p-2 text-neutral-400 hover:bg-white/10 hover:text-white lg:hidden">
                    <x-icon name="x-mark" class="h-5 w-5" />
                </button>
            </div>

            <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-5">
                @foreach ($menu as $group)
                    <div>
                        <p class="mb-2 px-3 text-[11px] font-semibold uppercase tracking-wider text-neutral-500">{{ __($group['label']) }}</p>
                        <ul class="space-y-0.5">
                            @foreach ($group['items'] as $item)
                                <li>
                                    <a href="{{ route($item['route']) }}" @if ($item['active']) aria-current="page" @endif
                                        @class([
                                            'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-400',
                                            'bg-white/10 text-white' => $item['active'],
                                            'text-neutral-300 hover:bg-white/5 hover:text-white' => ! $item['active'],
                                        ])>
                                        <x-icon :name="$item['icon']" @class(['h-5 w-5 shrink-0', 'text-teal-400' => $item['active'], 'text-neutral-500' => ! $item['active']]) />
                                        <span class="flex-1 truncate">{{ __($item['label']) }}</span>
                                        @if ($item['badge'] > 0)
                                            <span class="rounded-full bg-teal-500 px-2 py-0.5 text-xs font-semibold tabular-nums text-neutral-900">{{ $item['badge'] > 99 ? '99+' : $item['badge'] }}</span>
                                        @endif
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </nav>
        </aside>

        <div class="flex min-h-screen flex-col lg:pl-64">
            <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-neutral-200 bg-white/95 px-4 backdrop-blur sm:px-6 lg:px-8">
                <button type="button" @click="drawer = true" aria-label="{{ __('Open menu') }}" aria-controls="staff-nav" class="-ml-1 rounded-lg p-2 text-neutral-600 hover:bg-neutral-100 lg:hidden">
                    <x-icon name="bars-3" class="h-6 w-6" />
                </button>
                <p class="min-w-0 truncate text-sm font-medium text-neutral-500">{{ $section ? __($section) : __('Staff portal') }}</p>

                <div class="ml-auto flex items-center gap-2 sm:gap-3">
                    @php($unread = \App\Support\Navigation\StaffMenu::count('notifications', $staff))
                    <a href="{{ route('admin.notifications.index') }}" aria-label="{{ $unread > 0 ? __(':count unread notifications', ['count' => $unread]) : __('Notifications') }}"
                        class="relative rounded-xl p-2 text-neutral-600 transition-colors hover:bg-neutral-100 hover:text-neutral-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">
                        <x-icon name="bell" class="h-6 w-6" />
                        @if ($unread > 0)
                            <span class="absolute right-0.5 top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-semibold leading-none text-white ring-2 ring-white">{{ $unread > 99 ? '99+' : $unread }}</span>
                        @endif
                    </a>

                    <x-dropdown align="right" width="w-64">
                        <x-slot name="trigger">
                            <button type="button" aria-label="{{ __('Account menu') }}" :aria-expanded="open.toString()"
                                class="flex items-center gap-2 rounded-xl p-1 pr-2 transition-colors hover:bg-neutral-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">
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
                                <a href="{{ route('admin.account.edit') }}" class="flex w-full items-center gap-3 px-4 py-2 text-sm text-neutral-700 transition-colors hover:bg-neutral-50 hover:text-neutral-900">
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
            </header>

            <main class="flex-1">{{ $slot }}</main>

            <footer class="border-t border-neutral-200 px-4 py-4 text-xs text-neutral-500 sm:px-6 lg:px-8">
                {{ config('app.name') }} {{ __('staff portal') }} · {{ __('Your actions here are recorded in the activity log.') }}
            </footer>
        </div>
    </div>
    @livewireScripts
</body>

</html>
