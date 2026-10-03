@props(['parts', 'center' => null, 'caption' => null])

{{-- A doughnut of a few parts: [['label' => 'Model sales', 'value' => 120000, 'color' => '#0d9488', 'text' => 'Ksh1,200'], ...]. Parts of zero are left out; the text under the ring says the rest. --}}
@php
    $total = array_sum(array_column($parts, 'value'));
    $offset = 25; // start at twelve o'clock
@endphp
<div {{ $attributes->class('flex items-center gap-5') }}>
    <div class="relative h-28 w-28 shrink-0">
        <svg viewBox="0 0 36 36" class="h-full w-full -rotate-0" role="img" aria-label="{{ collect($parts)->map(fn ($p) => $p['label'].': '.$p['text'])->implode(', ') }}">
            <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#f3f4f6" stroke-width="4.5" />
            @if ($total > 0)
                @foreach ($parts as $part)
                    @continue($part['value'] <= 0)
                    @php $share = $part['value'] / $total * 100; @endphp
                    <circle cx="18" cy="18" r="15.9155" fill="none" stroke="{{ $part['color'] }}" stroke-width="4.5" stroke-dasharray="{{ round($share, 2) }} {{ round(100 - $share, 2) }}" stroke-dashoffset="{{ round($offset, 2) }}" />
                    @php $offset -= $share; @endphp
                @endforeach
            @endif
        </svg>
        @if ($center)
            <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                <span class="font-tertiary text-sm font-bold tabular-nums text-neutral-900">{{ $center }}</span>
                @if ($caption)<span class="text-[10px] text-tertiary">{{ $caption }}</span>@endif
            </div>
        @endif
    </div>
    <ul class="min-w-0 flex-1 space-y-2 text-sm">
        @foreach ($parts as $part)
            <li class="flex items-center justify-between gap-3">
                <span class="flex min-w-0 items-center gap-2 text-neutral-700"><span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background: {{ $part['color'] }}"></span><span class="truncate">{{ $part['label'] }}</span></span>
                <span class="shrink-0 font-medium tabular-nums text-neutral-900">{{ $part['text'] }}</span>
            </li>
        @endforeach
    </ul>
</div>
