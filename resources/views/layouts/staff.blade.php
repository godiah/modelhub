@php
    $staff = auth()->user();
    $menu = \App\Support\Navigation\StaffMenu::for($staff);
    $crumbs = \App\Support\Navigation\StaffMenu::breadcrumb($menu, $title);
    $pageTitle = $title ? $title.' · Staff' : 'Staff portal';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['sidebar-collapsed' => request()->cookie('modelhub_sidebar') === '1'])>

<head>
    @include('partials.app-head')
    <meta name="robots" content="noindex, nofollow">
    {{-- Staff pages have no Livewire components of their own, which is where Alpine (menus, the mobile drawer, dialogs) and wire:navigate come from --}}
    @livewireStyles
</head>

{{-- The staff portal shell: the same design language as the member app (paper background, light collapsible rail, breadcrumb bar), marked as staff by its label and tools. --}}

<body class="bg-paper font-sans text-neutral-900 antialiased">
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

        <div x-show="mobileOpen" x-cloak x-transition.opacity @click="mobileOpen = false" aria-hidden="true" class="fixed inset-0 z-40 bg-neutral-900/40 lg:hidden"></div>

        <x-sidebar :groups="$menu" home="admin.dashboard" :subtitle="__('Staff portal')" :label="__('Staff navigation')" badge="bg-teal-600 text-white" />

        <div class="flex min-h-screen flex-col transition-[padding] duration-200 ease-out lg:pl-64 lg:[.sidebar-collapsed_&]:pl-[72px]">
            <x-staff-topbar :crumbs="$crumbs" :staff="$staff" />

            <main class="flex-1">{{ $slot }}</main>

            <footer class="border-t border-neutral-200/70 px-4 py-4 text-xs text-neutral-500 sm:px-6 lg:px-8">
                {{ config('app.name') }} {{ __('staff portal') }} · {{ __('Your actions here are recorded in the activity log.') }}
            </footer>
        </div>
    </div>
    <x-staff-palette :menu="$menu" :staff="$staff" />
    @livewireScripts
</body>

</html>
