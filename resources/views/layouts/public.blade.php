<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('partials.app-head')
</head>

{{-- Public shell: top navigation bar. Used for guests (landing, job board browsing). --}}

<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100">
        @include('partials.flash-messages')
        <livewire:layout.navigation :hasHeader="isset($header)" />

        <!-- Page Heading -->
        @if (isset($header))
            <header class="bg-white shadow-lg mt-16 w-full fixed top-0 z-40">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endif

        <!-- Page Content -->
        <div class="flex flex-col min-h-screen mt-36">
            <main class="flex-grow">
                {{ $slot }}
            </main>
        </div>

    </div>
    @include('partials.app-scripts')
</body>

</html>
