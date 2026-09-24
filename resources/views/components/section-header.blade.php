@props(['title', 'subtitle' => null, 'variant' => 'primary'])

@php
    $gradientClasses = match ($variant) {
        'danger' => 'bg-gradient-to-r from-red-600 to-red-700',
        default => 'bg-gradient-to-r from-primary to-primary/90',
    };
@endphp

<!-- Header Section -->
<div class="{{ $gradientClasses }} px-6 py-5">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                {{ $icon }}
            </div>
            <div>
                <h2 class="text-xl font-semibold text-white font-secondary">
                    {{ $title }}
                </h2>
                @if ($subtitle)
                    <p class="text-white/60 text-sm font-main mt-1">
                        {{ $subtitle }}
                    </p>
                @endif
            </div>
        </div>

        @isset($action)
            {{ $action }}
        @endisset
    </div>
</div>
