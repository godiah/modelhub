@props(['crumbs' => []])

<!-- Sticky top bar -->
<div
    class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-neutral-200/70 bg-paper/90 px-4 backdrop-blur sm:px-6 lg:px-8">
    <!-- Open drawer (below lg) -->
    <button type="button" @click="mobileOpen = true" aria-label="{{ __('Open menu') }}" aria-controls="app-sidebar"
        class="-ml-1 rounded-lg p-2 text-neutral-600 hover:bg-neutral-200/60 hover:text-neutral-900 lg:hidden">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
    </button>

    <!-- Breadcrumb: full trail from sm up, current page only on phones -->
    @if ($crumbs)
        <nav aria-label="{{ __('Breadcrumb') }}" class="min-w-0">
            <ol class="flex min-w-0 items-center gap-2 text-sm">
                @foreach ($crumbs as $crumb)
                    @php($isLast = $loop->last)
                    <li @class([
                        'flex min-w-0 items-center gap-2',
                        'hidden sm:flex' => !$isLast,
                    ])>
                        @if (!$loop->first)
                            <span class="hidden text-neutral-300 sm:inline" aria-hidden="true">/</span>
                        @endif

                        @if ($crumb['url'])
                            <a href="{{ $crumb['url'] }}" wire:navigate
                                class="truncate text-tertiary transition-colors duration-150 hover:text-teal-700 focus:outline-none focus-visible:underline">{{ $crumb['label'] }}</a>
                        @else
                            <span @class([
                                'truncate',
                                'font-semibold text-neutral-900' => $isLast,
                                'text-tertiary' => !$isLast,
                            ]) @if ($isLast) aria-current="page" @endif>{{ $crumb['label'] }}</span>
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>
    @endif

    <div class="ml-auto flex items-center gap-2 sm:gap-3">
        <x-btn href="{{ route('jobs.create') }}" wire:navigate>
            <x-icon name="plus" class="h-4 w-4" />
            <span class="hidden sm:inline">{{ __('Post a project') }}</span>
            <span class="sr-only sm:hidden">{{ __('Post a project') }}</span>
        </x-btn>

        @php($unread = auth()->user()->unreadNotifications()->count())
        <a href="{{ route('notifications.index') }}" wire:navigate
            aria-label="{{ $unread > 0 ? __(':count unread notifications', ['count' => $unread]) : __('Notifications') }}"
            class="relative rounded-xl p-2 text-neutral-600 transition-colors duration-200 hover:bg-neutral-200/60 hover:text-neutral-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">
            <x-icon name="bell" class="h-6 w-6" />
            @if ($unread > 0)
                <span
                    class="absolute right-0.5 top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-semibold leading-none text-white ring-2 ring-paper">{{ $unread > 99 ? '99+' : $unread }}</span>
            @endif
        </a>

        <livewire:layout.user-menu />
    </div>
</div>
