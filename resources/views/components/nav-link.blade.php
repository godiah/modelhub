@props(['active'])

@php
    $classes =
        $active ?? false
            ? 'inline-flex items-center px-1 pt-1 border-b-2 border-secondary text-sm font-medium leading-5 text-secondary focus:outline-none focus:border-secondary/80 transition-all duration-300 ease-in-out font-secondary'
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-neutral-700 hover:text-secondary hover:border-secondary/50 focus:outline-none focus:text-secondary focus:border-secondary/50 transition-all duration-300 ease-in-out font-secondary';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
