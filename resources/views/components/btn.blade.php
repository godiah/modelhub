@props(['variant' => 'primary', 'size' => 'md', 'block' => false, 'href' => null])

@php
    $classes = [
        'relative inline-flex items-center justify-center gap-2 rounded-xl font-secondary font-semibold transition-all duration-200 focus:outline-none focus-visible:ring-4 disabled:cursor-not-allowed disabled:opacity-60',
        'px-3 py-1.5 text-sm' => $size === 'sm',
        'px-4 py-2 text-sm' => $size === 'md',
        'px-6 py-3 text-sm' => $size === 'lg',
        'w-full' => $block,
        'bg-teal-600 text-white hover:bg-teal-700 focus-visible:ring-secondary/40 hover:shadow-lg hover:shadow-secondary/30' => $variant === 'primary',
        'border border-neutral-300 bg-white text-neutral-700 hover:bg-neutral-50 hover:text-neutral-900 focus-visible:ring-neutral-200' => $variant === 'secondary',
        'border border-red-200 bg-white text-red-700 hover:bg-red-50 focus-visible:ring-red-200' => $variant === 'danger-outline',
        'bg-red-600 text-white hover:bg-red-700 focus-visible:ring-red-300' => $variant === 'danger',
        'text-teal-700 hover:bg-teal-50 focus-visible:ring-secondary/30' => $variant === 'ghost',
    ];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => 'submit'])->class($classes) }} wire:loading.attr="disabled">
        <x-spinner class="hidden h-4 w-4" wire:loading.class.remove="hidden" {{ $attributes->whereStartsWith('wire:target') }} />
        {{ $slot }}
    </button>
@endif
