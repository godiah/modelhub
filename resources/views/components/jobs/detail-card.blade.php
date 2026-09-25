@props(['label', 'color' => 'secondary'])

@php
    $borderHover = $color === 'accent' ? 'hover:border-accent' : 'hover:border-secondary';
    $iconBg = $color === 'accent' ? 'bg-accent/10 group-hover:bg-accent/20' : 'bg-secondary/10 group-hover:bg-secondary/20';
@endphp

<div
    class="group bg-neutral-50 rounded-xl border border-neutral-100 p-5 {{ $borderHover }} hover:bg-white transition-all duration-300 hover:shadow-md">
    <div class="flex items-start">
        <div class="rounded-lg {{ $iconBg }} p-3 transition-colors duration-300">
            {{ $icon }}
        </div>
        <div class="ml-4">
            <p class="text-xs uppercase tracking-wider text-neutral-500 font-main font-medium">{{ $label }}</p>
            {{ $slot }}
        </div>
    </div>
</div>
