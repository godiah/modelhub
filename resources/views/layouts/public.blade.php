<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('partials.app-head')
</head>

{{-- Public shell: top navigation bar. Used for guests (landing, job board browsing). --}}

<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100">
        @php
            $hasToolbar = isset($toolbar) && trim((string) $toolbar) !== '';
            $hasHeader = !empty($title) || $hasToolbar;
        @endphp
        @include('partials.flash-messages')
        <livewire:layout.navigation :hasHeader="$hasHeader" />

        <!-- Page Heading (guests have no breadcrumb bar: page title on the left, toolbar on the right) -->
        @if ($hasHeader)
            <header class="bg-white shadow-lg mt-16 w-full fixed top-0 z-40">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 flex flex-wrap items-center justify-between gap-3">
                    @if (!empty($title))
                        <h1 class="font-tertiary font-bold text-2xl text-primary leading-tight">{{ $title }}</h1>
                    @endif
                    @if ($hasToolbar)
                        <div class="ml-auto flex flex-wrap items-center gap-3">{{ $toolbar }}</div>
                    @endif
                </div>
            </header>
        @endif

        <!-- Page Content -->
        <div @class(['flex flex-col min-h-screen', 'mt-36' => $hasHeader, 'mt-16' => !$hasHeader])>
            <main class="flex-grow">
                {{ $slot }}
            </main>

            @include('partials.footer-public')
        </div>

    </div>
    @include('partials.app-scripts')
</body>

</html>
