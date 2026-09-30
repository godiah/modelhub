@props(['rounded' => 'xl', 'shadow' => 'sm', 'border' => 'neutral-200', 'clip' => false])

@php
    $roundedClass = ['lg' => 'rounded-lg', 'xl' => 'rounded-xl', '2xl' => 'rounded-2xl'][$rounded];
    $shadowClass = ['none' => '', 'sm' => 'shadow-sm', 'md' => 'shadow-md', 'lg' => 'shadow-lg', 'xl' => 'shadow-xl'][$shadow];
    $borderClass = [
        'neutral-200' => 'border-neutral-200',
        'neutral-100' => 'border-neutral-100',
        'red-100' => 'border-red-100',
        'orange-100' => 'border-orange-100',
        'green-100' => 'border-green-100',
    ][$border];
@endphp

<div {{ $attributes->class(['bg-white border', $borderClass, $roundedClass, $shadowClass, 'overflow-hidden' => $clip]) }}>
    {{ $slot }}
</div>
