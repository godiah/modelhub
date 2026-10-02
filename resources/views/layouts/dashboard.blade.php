@php
    $menu = \App\Support\Navigation\SidebarMenu::for(auth()->user());
    $breadcrumb = \App\Support\Navigation\SidebarMenu::breadcrumb($crumb ?? null);
    // Browser-tab title: the most specific breadcrumb segment.
    $pageTitle = $breadcrumb ? end($breadcrumb)['label'] : null;
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
            class="flex min-h-screen flex-col transition-[padding] duration-200 ease-out lg:pl-64 lg:[.sidebar-collapsed_&]:pl-[72px] print:pl-0">
            <x-topbar :crumbs="$breadcrumb" />

            <!-- Page toolbar: contextual actions / summary supplied by the page (titles live in the breadcrumb) -->
            @if (isset($toolbar) && trim((string) $toolbar) !== '')
                <div class="container mx-auto flex max-w-7xl flex-wrap items-center justify-end gap-3 px-4 pt-5">
                    {{ $toolbar }}
                </div>
            @endif

            <!-- Page Content -->
            <main class="flex-1">
                <h1 class="sr-only">{{ $pageTitle ?? config('app.name') }}</h1>
                {{ $slot }}
            </main>

            <div class="print:hidden">@include('partials.footer-app')</div>
        </div>
    </div>
    @include('partials.app-scripts')
</body>

</html>
