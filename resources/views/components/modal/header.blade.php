@props(['title', 'icon' => null, 'variant' => 'plain'])

@php
    $isBrand = $variant === 'brand';
    $iconClass = \Illuminate\Support\Arr::toCssClasses(['mr-2', 'h-6 w-6' => $isBrand, 'h-5 w-5 text-primary' => ! $isBrand]);
@endphp

<div {{ $attributes->class([
    'flex items-center justify-between px-6 py-4',
    'bg-primary text-white' => $isBrand,
    'border-b border-neutral-200' => ! $isBrand,
]) }}>
    <h3 @class([
        'flex items-center font-tertiary font-bold',
        'text-xl text-white' => $isBrand,
        'text-lg text-neutral-800' => ! $isBrand,
    ])>
        @if ($icon)
            <x-icon :name="$icon" class="{{ $iconClass }}" />
        @endif
        {{ $title }}
    </h3>

    <button type="button" x-on:click="dismiss()" aria-label="Close"
        @class([
            'transition-colors',
            'text-white hover:text-neutral-200' => $isBrand,
            'text-neutral-400 hover:text-neutral-600' => ! $isBrand,
        ])>
        <x-icon name="x-mark" class="h-5 w-5" />
    </button>
</div>
