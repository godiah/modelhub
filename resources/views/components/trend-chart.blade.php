@props(['series', 'title' => null])

{{--
    A weekly trend chart with a tab per series, drawn as inline SVG. $series: key => ['label' => 'Members', 'points' => [['label' => 'Jun 5', 'count' => 3], ...]].
    Hovering (or focusing) a column reads out that week; the readout is plain text so it also works for screen readers.
--}}
@php
    $first = array_key_first($series);
    $W = 640; $H = 220; $padL = 8; $padR = 8; $padT = 14; $padB = 26;
    $geometry = collect($series)->map(function ($s) use ($W, $H, $padL, $padR, $padT, $padB) {
        $counts = collect($s['points'])->pluck('count')->all();
        $n = count($counts);
        $max = max(4, (int) ceil(max($counts ?: [0]) * 1.15));
        $x = fn ($i) => round($padL + $i / max(1, $n - 1) * ($W - $padL - $padR), 1);
        $y = fn ($v) => round($padT + (1 - $v / $max) * ($H - $padT - $padB), 1);
        $line = 'M'.collect($counts)->map(fn ($v, $i) => $x($i).' '.$y($v))->implode(' L');

        return ['counts' => $counts, 'labels' => collect($s['points'])->pluck('label')->all(), 'max' => $max, 'xs' => collect($counts)->map(fn ($v, $i) => $x($i))->all(), 'ys' => collect($counts)->map(fn ($v) => $y($v))->all(),
            'line' => $line, 'area' => $line.' L'.$x($n - 1).' '.($H - $padB).' L'.$x(0).' '.($H - $padB).' Z', 'total' => array_sum($counts), 'step' => ($W - $padL - $padR) / max(1, $n - 1)];
    });
@endphp
<div x-data="{ active: @js($first), hover: null }" {{ $attributes }}>
    <div class="flex flex-wrap items-center justify-between gap-3">
        @if ($title)<h2 class="font-tertiary text-base font-semibold text-neutral-900">{{ $title }}</h2>@endif
        <div role="tablist" aria-label="{{ __('Chart series') }}" class="flex w-full gap-1 rounded-xl bg-neutral-100 p-1 sm:w-auto">
            @foreach ($series as $key => $s)
                <button type="button" role="tab" @click="active = '{{ $key }}'; hover = null" :aria-selected="(active === '{{ $key }}').toString()"
                    :class="active === '{{ $key }}' ? 'bg-white text-neutral-900 shadow-sm' : 'text-neutral-600 hover:text-neutral-900'"
                    class="flex-1 rounded-lg px-2 py-1 text-sm font-medium transition-colors focus:outline-none sm:flex-none sm:px-3 focus-visible:ring-2 focus-visible:ring-secondary/40">{{ __($s['label']) }}</button>
            @endforeach
        </div>
    </div>

    @foreach ($series as $key => $s)
        @php $g = $geometry[$key]; $n = count($g['counts']); @endphp
        <div x-show="active === '{{ $key }}'" @if (! $loop->first) x-cloak @endif role="tabpanel">
            <p class="mt-3 text-sm text-tertiary" aria-live="polite">
                <span class="font-tertiary text-2xl font-bold tabular-nums text-neutral-900" x-text="hover === null ? {{ $g['total'] }} : {{ \Illuminate\Support\Js::from($g['counts']) }}[hover]">{{ $g['total'] }}</span>
                <span x-text="hover === null ? @js(__('new in the last :n weeks', ['n' => $n])) : @js(__('new, week of')) + ' ' + {{ \Illuminate\Support\Js::from($g['labels']) }}[hover]">{{ __('new in the last :n weeks', ['n' => $n]) }}</span>
            </p>
            <svg viewBox="0 0 {{ $W }} {{ $H }}" class="mt-2 h-52 w-full" role="img" aria-label="{{ __($s['label']) }}: {{ implode(', ', $g['counts']) }}">
                <defs><linearGradient id="trend-{{ $key }}" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#0d9488" stop-opacity="0.25" /><stop offset="1" stop-color="#0d9488" stop-opacity="0" /></linearGradient></defs>
                @foreach ([0, 0.5, 1] as $f)
                    @php $gy = round($padT + (1 - $f) * ($H - $padT - $padB), 1); @endphp
                    <line x1="{{ $padL }}" x2="{{ $W - $padR }}" y1="{{ $gy }}" y2="{{ $gy }}" stroke="#e5e7eb" stroke-width="1" @if ($f > 0) stroke-dasharray="3 4" @endif />
                    @if ($f > 0)<text x="{{ $padL }}" y="{{ $gy - 4 }}" font-size="10" fill="#9ca3af">{{ (int) round($g['max'] * $f) }}</text>@endif
                @endforeach
                <path d="{{ $g['area'] }}" fill="url(#trend-{{ $key }})" />
                <path d="{{ $g['line'] }}" fill="none" stroke="#0d9488" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                @foreach ($g['counts'] as $i => $v)
                    <circle cx="{{ $g['xs'][$i] }}" cy="{{ $g['ys'][$i] }}" :r="hover === {{ $i }} ? 5 : 3" fill="#fff" stroke="#0d9488" stroke-width="2" />
                    <rect x="{{ $g['xs'][$i] - $g['step'] / 2 }}" y="0" width="{{ $g['step'] }}" height="{{ $H - $padB }}" fill="transparent" tabindex="0" role="img" aria-label="{{ __('Week of :week: :count', ['week' => $g['labels'][$i], 'count' => $v]) }}"
                        @mouseenter="hover = {{ $i }}" @mouseleave="hover = null" @focus="hover = {{ $i }}" @blur="hover = null" class="outline-none" />
                    @if ($i % 2 === 0 || $n < 7)<text x="{{ $g['xs'][$i] }}" y="{{ $H - 8 }}" font-size="10" text-anchor="middle" fill="#9ca3af">{{ $g['labels'][$i] }}</text>@endif
                @endforeach
            </svg>
        </div>
    @endforeach
</div>
