<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('partials.app-head')
    <meta name="robots" content="noindex, nofollow">
</head>

{{-- Staff sign-in pages: a plain centred card, clearly not the member sign-in. --}}

<body class="bg-neutral-900 font-sans text-neutral-900 antialiased">
    <main class="flex min-h-screen flex-col items-center justify-center px-4 py-10">
        <a href="{{ route('admin.login') }}" class="mb-8 flex items-center gap-3" aria-label="{{ config('app.name') }}">
            <x-application-logo variant="mark" class="h-10 w-auto brightness-0 invert" />
            <span>
                <span class="block font-secondary text-base font-semibold uppercase tracking-[0.18em] text-white">{{ config('app.name') }}</span>
                <span class="block text-xs font-semibold uppercase tracking-widest text-teal-400">{{ __('Staff portal') }}</span>
            </span>
        </a>

        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl sm:p-8">
            {{ $slot }}
        </div>

        <p class="mt-6 max-w-md text-center text-xs text-neutral-500">{{ __('This area is for ModelHub staff only. Looking for your account as a buyer, seller or freelancer?') }} <a href="{{ route('login') }}" class="font-medium text-teal-400 hover:underline">{{ __('Sign in here') }}</a></p>
    </main>
</body>

</html>
