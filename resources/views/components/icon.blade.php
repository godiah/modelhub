@props(['name'])

@php
    $icon = config('icons.'.$name) ?? throw new InvalidArgumentException("Unknown icon [{$name}].");
@endphp

<svg xmlns="http://www.w3.org/2000/svg" viewBox="{{ $icon['viewBox'] }}" {{ $attributes->merge($icon['attrs']) }}><path d="{{ $icon['d'] }}" /></svg>
