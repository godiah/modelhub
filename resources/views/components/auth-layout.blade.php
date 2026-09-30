@props([
    'title',
    'subtitle' => null,
    'panelTitle' => 'Hire and deliver 3D work with confidence',
    'panelText' => 'Discover and post 3D modeling jobs, connect with top-tier freelance architects, and access premium models to bring your architectural visions to life.',
])

<!-- Auth Layout: quiet brand panel (lg+) + form column -->
<div class="min-h-screen font-main bg-white lg:grid lg:grid-cols-2">

    <!-- Brand Panel -->
    <aside class="hidden lg:flex flex-col bg-paper border-r border-neutral-200/70 p-10 xl:p-14">
        <a href="{{ url('/') }}" class="w-fit" aria-label="{{ config('app.name', 'ModelHub') }}">
            <x-application-logo class="h-auto w-44 xl:w-56" />
        </a>

        <div class="my-auto max-w-md py-12">
            <h2 class="font-tertiary text-balance text-3xl xl:text-4xl font-bold leading-tight tracking-tight text-neutral-900">
                {{ $panelTitle }}</h2>
            <p class="mt-4 text-base leading-relaxed text-neutral-600">{{ $panelText }}</p>
        </div>

        <p class="text-xs text-tertiary">&copy; {{ date('Y') }} {{ config('app.name', 'ModelHub') }}</p>
    </aside>

    <!-- Form Column -->
    <main class="flex min-h-screen flex-col lg:min-h-0">
        <!-- Compact brand header (below lg) -->
        <header class="lg:hidden border-b border-neutral-200/70 bg-paper px-6 py-3 sm:px-10">
            <a href="{{ url('/') }}" class="inline-flex items-center gap-2.5" aria-label="{{ config('app.name', 'ModelHub') }}">
                <x-application-logo variant="mark" class="h-9 w-auto" />
                <span
                    class="font-secondary text-sm font-semibold uppercase tracking-[0.18em] text-neutral-800">{{ config('app.name', 'ModelHub') }}</span>
            </a>
        </header>

        <div class="flex flex-1 items-center justify-center px-6 py-10 sm:px-10 lg:py-12">
            <div class="w-full max-w-md">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="font-tertiary text-2xl sm:text-3xl font-bold tracking-tight text-neutral-900">
                        {{ $title }}</h1>
                    @if ($subtitle)
                        <p class="mt-2 text-sm sm:text-base leading-relaxed text-tertiary">{{ $subtitle }}</p>
                    @endif
                </div>

                {{ $slot }}

                @isset($footer)
                    <div class="mt-8 text-center text-sm text-tertiary">
                        {{ $footer }}
                    </div>
                @endisset
            </div>
        </div>
    </main>
</div>
