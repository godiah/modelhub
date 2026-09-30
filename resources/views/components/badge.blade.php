@props(['tone' => 'neutral'])

@php
    $toneClass = [
        'red' => 'bg-red-100 text-red-800',
        'amber' => 'bg-amber-100 text-amber-800',
        'green' => 'bg-green-100 text-green-800',
        'blue' => 'bg-blue-100 text-blue-800',
        'yellow' => 'bg-yellow-100 text-yellow-800',
        'neutral' => 'bg-neutral-100 text-neutral-700',
    ][$tone];
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-full', $toneClass]) }}>{{ $slot }}</span>
