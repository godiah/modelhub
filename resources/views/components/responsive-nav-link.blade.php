@props(['active'])

@php
    $classes =
        $active ?? false
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-secondary text-start text-base font-medium text-secondary bg-secondary/10 focus:outline-none focus:text-secondary focus:bg-secondary/20 focus:border-secondary transition-all duration-300 ease-in-out font-secondary'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-neutral-600 hover:text-secondary hover:bg-secondary/10 hover:border-secondary/50 focus:outline-none focus:text-secondary focus:bg-secondary/10 focus:border-secondary/50 transition-all duration-300 ease-in-out font-secondary';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
