@php
    $menu = \App\Support\Navigation\SidebarMenu::for(auth()->user());
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    @class(['sidebar-collapsed' => request()->cookie('modelhub_sidebar') === '1'])>

<head>
    @include('partials.app-head')
</head>

{{-- Authenticated shell: collapsible sidebar + sticky top bar. --}}

<body class="font-sans antialiased bg-paper text-neutral-900">
    <div x-data="{
        mobileOpen: false,
        collapsed: document.documentElement.classList.contains('sidebar-collapsed'),
        toggleCollapsed() {
            this.collapsed = !this.collapsed;
            document.documentElement.classList.toggle('sidebar-collapsed', this.collapsed);
            document.cookie = 'modelhub_sidebar=' + (this.collapsed ? '1' : '0') + '; path=/; max-age=31536000; SameSite=Lax';
        },
    }" @keydown.escape.window="mobileOpen = false" class="min-h-screen">
        @include('partials.flash-messages')

        <!-- Drawer backdrop (below lg) -->
        <div x-show="mobileOpen" x-cloak x-transition.opacity @click="mobileOpen = false" aria-hidden="true"
            class="fixed inset-0 z-40 bg-neutral-900/40 lg:hidden"></div>

        <x-sidebar :groups="$menu" />

        <div
            class="flex min-h-screen flex-col transition-[padding] duration-200 ease-out lg:pl-64 lg:[.sidebar-collapsed_&]:pl-[72px]">
            <x-topbar :crumb="\App\Support\Navigation\SidebarMenu::current($menu)" />

            <!-- Page Heading -->
            @if (isset($header))
                <header class="border-b border-neutral-200/70 bg-white">
                    <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main class="flex-1">
                {{ $slot }}
            </main>
        </div>
    </div>
    @include('partials.app-scripts')
</body>

</html>
