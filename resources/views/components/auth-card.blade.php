@props(['title', 'subtitle' => null])

<!-- Auth Card -->
<div class="w-full max-w-2xl p-8 mx-auto bg-white rounded-3xl shadow-2xl overflow-hidden">
    <div class="mx-auto">

        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-neutral-900 font-tertiary tracking-wider">{{ $title }}</h1>
            @if ($subtitle)
                <p class="text-tertiary text-sm leading-relaxed font-main">
                    {{ $subtitle }}
                </p>
            @endif
        </div>

        {{ $slot }}
    </div>
</div>
