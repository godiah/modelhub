@props(['label', 'value', 'hint' => null, 'icon', 'url' => null, 'tone' => 'teal', 'trend' => null, 'series' => null])

{{-- A number with its label, as on the member dashboard: icon chip, large numeral, hint, and optionally a trend chip and sparkline. tone: teal|amber|red --}}
@php
    $chip = ['teal' => 'bg-teal-50 text-teal-700', 'amber' => 'bg-amber-50 text-amber-700', 'red' => 'bg-red-50 text-red-700'][$tone] ?? 'bg-teal-50 text-teal-700';
    $tag = $url ? 'a' : 'div';
@endphp
<{{ $tag }} @if ($url) href="{{ $url }}" wire:navigate @endif {{ $attributes->class(['group block focus:outline-none']) }}>
    <x-card @class(['h-full p-4 transition-shadow duration-200 sm:p-5', 'group-hover:shadow-md group-focus-visible:ring-2 group-focus-visible:ring-secondary/40' => $url])>
        <div class="flex items-start justify-between gap-3">
            <p class="text-sm font-medium text-tertiary">{{ $label }}</p>
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $chip }}"><x-icon :name="$icon" class="h-5 w-5" /></span>
        </div>
        <div class="mt-3 flex items-baseline gap-2">
            <p class="font-tertiary text-2xl font-bold tabular-nums text-neutral-900 sm:text-3xl">{{ $value }}</p>
            @if ($trend)
                <span @class(['rounded-full px-1.5 py-0.5 text-xs font-semibold tabular-nums', 'bg-green-50 text-green-700' => $trend[1], 'bg-red-50 text-red-700' => ! $trend[1]])>{{ $trend[0] }}</span>
            @endif
        </div>
        @if ($hint)<p class="mt-1 text-xs text-tertiary">{{ $hint }}</p>@endif
        @if ($series)<x-sparkline :values="$series" :tone="$tone" :label="$label" class="mt-3" />@endif
    </x-card>
</{{ $tag }}>
