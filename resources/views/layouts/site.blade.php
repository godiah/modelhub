<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ ! empty($title) ? $title.' · ' : '' }}{{ config('app.name') }}@empty($title) — Hire 3D artists and find 3D projects @endempty</title>
    @isset($description)
        <meta name="description" content="{{ $description }}">
    @endisset
    @include('partials.favicon')
    @include('partials.fonts')
    @if (session('alert') || session('success') || session('error') || session('info'))
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @if ($alpine ?? false)
        @livewireStyles
    @endif
</head>

{{-- Public shell for guests: one header, one footer. The page supplies everything in between. --}}

<body class="bg-white font-sans text-neutral-800 antialiased">
    @include('partials.flash-messages')

    <x-site.header />

    @php $hasToolbar = isset($toolbar) && trim((string) $toolbar) !== ''; @endphp
    @if ($hasToolbar)
        <div class="border-b border-neutral-200 bg-white">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-4 py-5 sm:px-6 lg:px-8">
                <div class="ml-auto flex flex-wrap items-center gap-3">{{ $toolbar }}</div>
            </div>
        </div>
    @endif

    <main class="bg-paper">
        {{ $slot }}
    </main>

    <x-site.footer />

    @if ($alpine ?? false)
        @livewireScripts
    @endif
</body>

</html>
