@props(['values' => [], 'tone' => 'teal', 'label' => null])

{{-- A tiny area chart of a list of numbers, drawn as inline SVG. Stretches to its container's width. --}}
@php
    $values = array_values($values);
    $n = count($values);
    $max = max(1, $n ? max($values) : 1);
    $points = collect($values)->map(fn ($v, $i) => [round($i / max(1, $n - 1) * 100, 2), round(30 - ($v / $max) * 26, 2)]);
    $line = $n > 1 ? 'M'.$points->map(fn ($p) => $p[0].' '.$p[1])->implode(' L') : '';
    $color = ['teal' => '#0d9488', 'amber' => '#d97706', 'red' => '#dc2626', 'blue' => '#2563eb'][$tone] ?? '#0d9488';
    $id = 'spark-'.\Illuminate\Support\Str::random(6);
@endphp
@if ($n > 1)
    <svg viewBox="0 0 100 32" preserveAspectRatio="none" role="img" aria-label="{{ $label ?? implode(', ', $values) }}" {{ $attributes->class('h-8 w-full overflow-visible') }}>
        <defs><linearGradient id="{{ $id }}" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="{{ $color }}" stop-opacity="0.22" /><stop offset="1" stop-color="{{ $color }}" stop-opacity="0" /></linearGradient></defs>
        <path d="{{ $line }} L100 32 L0 32 Z" fill="url(#{{ $id }})" />
        <path d="{{ $line }}" fill="none" stroke="{{ $color }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke" />
    </svg>
@endif
