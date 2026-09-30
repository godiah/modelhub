@props(['variant' => 'primary', 'size' => 'md', 'href' => null])

@php
    $variantClass = [
        'primary' => 'bg-primary text-white hover:bg-primary/90',
        'secondary' => 'bg-secondary text-white hover:bg-secondary/90',
        'danger' => 'bg-red-600 text-white hover:bg-red-700',
        'neutral' => 'bg-neutral-200 text-neutral-700 hover:bg-neutral-300',
    ][$variant];
    $sizeClass = ['md' => 'px-4 py-2', 'lg' => 'px-6 py-3'][$size];
    $classes = "inline-flex items-center rounded-lg font-main font-medium transition-colors duration-200 {$sizeClass} {$variantClass}";
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
