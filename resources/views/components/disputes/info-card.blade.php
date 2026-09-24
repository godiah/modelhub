@props(['title', 'color' => 'primary'])

@php
    // Tailwind's class scanner needs full literal class names, not interpolated ones —
    // this map keeps "bg-accent/10" etc. as text the scanner can find in this file.
    $iconBg = match ($color) {
        'accent' => 'bg-accent/10',
        'secondary' => 'bg-secondary/10',
        'tertiary' => 'bg-tertiary/10',
        default => 'bg-primary/10',
    };
@endphp

<div {{ $attributes->merge(['class' => 'bg-white/90 backdrop-blur-sm rounded-xl shadow-lg border border-neutral-200/50 p-6']) }}>
    <div class="flex items-center space-x-2 mb-2">
        <div class="{{ $iconBg }} rounded-lg p-2">
            {{ $icon }}
        </div>
        <h3 class="font-bold font-main text-neutral-800">{{ $title }}</h3>
    </div>
    {{ $slot }}
</div>
