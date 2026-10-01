@props(['groups', 'home' => 'dashboard', 'subtitle' => null, 'label' => null, 'badge' => 'bg-red-500 text-white'])

{{--
    App sidebar. Desktop (lg+): fixed rail, 256px wide or 72px when html has .sidebar-collapsed (set before paint
    by the layout's head script and toggled here). Below lg: off-canvas drawer driven by the shell's `mobileOpen`.
--}}
<aside id="app-sidebar" aria-label="{{ $label ?? __('Main navigation') }}"
    class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col border-r border-neutral-200/70 bg-white transition-[transform,width] duration-200 ease-out lg:z-30 lg:w-64 lg:translate-x-0 lg:[.sidebar-collapsed_&]:w-[72px]"
    :class="{ '!translate-x-0 shadow-2xl': mobileOpen }" x-trap.noscroll="mobileOpen">

    <!-- Brand -->
    <div class="flex h-16 shrink-0 items-center justify-between border-b border-neutral-200/70 px-4">
        <a href="{{ route($home) }}" wire:navigate class="flex min-w-0 items-center gap-2.5"
            aria-label="{{ config('app.name', 'ModelHub') }}">
            <x-application-logo variant="mark" class="h-9 w-auto shrink-0" />
            <span class="min-w-0 lg:[.sidebar-collapsed_&]:hidden">
                <span class="block truncate font-secondary text-sm font-semibold uppercase tracking-[0.18em] text-neutral-800">{{ config('app.name', 'ModelHub') }}</span>
                @if ($subtitle)<span class="block text-[10px] font-semibold uppercase tracking-widest text-teal-700">{{ $subtitle }}</span>@endif
            </span>
        </a>

        <!-- Close (mobile drawer only) -->
        <button type="button" @click="mobileOpen = false" aria-label="{{ __('Close menu') }}"
            class="rounded-lg p-2 text-neutral-500 hover:bg-neutral-100 hover:text-neutral-900 lg:hidden">
            <x-icon name="x-mark" class="h-5 w-5" />
        </button>
    </div>

    <!-- Menu -->
    <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-5">
        @foreach ($groups as $group)
            <div>
                <p
                    class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-neutral-400 lg:[.sidebar-collapsed_&]:hidden">
                    {{ $group['label'] }}</p>
                @unless ($loop->first)
                    <div class="mx-3 mb-2 hidden h-px bg-neutral-200 lg:[.sidebar-collapsed_&]:block"></div>
                @endunless

                <ul class="space-y-1">
                    @foreach ($group['items'] as $item)
                        {{-- A sub-label (or, when the rail is collapsed, a hairline) where a new section starts inside a group --}}
                        @if (($item['section'] ?? null) && $item['section'] !== ($group['items'][$loop->index - 1]['section'] ?? null))
                            <li class="mx-3 mt-3 border-t border-neutral-100 px-0 pb-1 pt-3 text-xs font-medium text-neutral-400 lg:[.sidebar-collapsed_&]:mt-2 lg:[.sidebar-collapsed_&]:border-neutral-200 lg:[.sidebar-collapsed_&]:pt-0">
                                <span class="lg:[.sidebar-collapsed_&]:hidden">{{ $item['section'] }}</span>
                            </li>
                        @endif
                        <li>
                            <a href="{{ route($item['route']) }}" wire:navigate title="{{ $item['label'] }}"
                                @if ($item['active']) aria-current="page" @endif
                                @class([
                                    'group relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40 lg:[.sidebar-collapsed_&]:justify-center lg:[.sidebar-collapsed_&]:px-0',
                                    'border border-neutral-200/80 bg-paper text-neutral-900 shadow-sm' => $item['active'],
                                    'border border-transparent text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900' => !$item['active'],
                                ])>
                                <x-icon :name="$item['icon']" :class="$item['active'] ? 'h-5 w-5 shrink-0 text-teal-700' : 'h-5 w-5 shrink-0 text-neutral-400 group-hover:text-neutral-600'" />
                                <span class="flex-1 truncate lg:[.sidebar-collapsed_&]:hidden">{{ $item['label'] }}</span>

                                @if ($item['badge'] > 0)
                                    <span
                                        class="rounded-full {{ $badge }} px-2 py-0.5 text-xs font-semibold tabular-nums lg:[.sidebar-collapsed_&]:hidden">{{ $item['badge'] > 99 ? '99+' : $item['badge'] }}</span>
                                    <span aria-hidden="true"
                                        class="absolute right-3 top-2 hidden h-2 w-2 rounded-full {{ $badge }} ring-2 ring-white lg:[.sidebar-collapsed_&]:block"></span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>

    <!-- Collapse toggle (desktop only) -->
    <div class="hidden shrink-0 border-t border-neutral-200/70 p-3 lg:block">
        <button type="button" @click="toggleCollapsed()" :aria-expanded="(!collapsed).toString()"
            aria-controls="app-sidebar"
            class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-neutral-500 transition-colors duration-150 hover:bg-neutral-50 hover:text-neutral-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40 lg:[.sidebar-collapsed_&]:justify-center lg:[.sidebar-collapsed_&]:px-0"
            title="{{ __('Collapse menu') }}">
            <x-icon name="chevron-double-right"
                class="h-5 w-5 shrink-0 rotate-180 transition-transform duration-200 lg:[.sidebar-collapsed_&]:rotate-0" />
            <span class="lg:[.sidebar-collapsed_&]:hidden">{{ __('Collapse') }}</span>
        </button>
    </div>
</aside>
