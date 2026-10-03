@props(['buckets', 'max', 'unit' => 'day'])

{{--
    Earnings over a period as stacked columns, models and jobs. $buckets: [['label' => 'Oct 1', 'models' => 120000, 'jobs' => 0], ...] in minor units.
    Built from plain elements rather than a fixed-size drawing, so it fills whatever height its card gives it and its text never stretches.
    Hovering or focusing a column reads out that day, week or month; the readout is plain text, so it works for screen readers too.
--}}
@php
    $n = count($buckets);

    // A round top for the scale: 1, 2, 2.5 or 5 times a power of ten, in whole shillings
    $top = max(1, $max / 100);
    $power = 10 ** floor(log10($top));
    $niceTop = collect([1, 2, 2.5, 5, 10])->map(fn ($m) => $m * $power)->first(fn ($v) => $v >= $top) * 100;

    $compact = function (float $minor): string {
        $kes = $minor / 100;
        if ($kes >= 1_000_000) return 'Ksh '.rtrim(rtrim(number_format($kes / 1_000_000, 1), '0'), '.').'M';
        if ($kes >= 1000) return 'Ksh '.rtrim(rtrim(number_format($kes / 1000, 1), '0'), '.').'k';
        return 'Ksh '.number_format($kes);
    };

    $ticks = [0, 0.25, 0.5, 0.75, 1];
    $labelEvery = max(1, (int) ceil($n / 8));
    $noun = ['day' => __('day'), 'week' => __('week of'), 'month' => __('month')][$unit] ?? '';
@endphp
<div x-data="{ hover: null, b: @js($buckets) }" data-earnings-chart {{ $attributes->class('flex min-h-[19rem] flex-1 flex-col') }}>
    <p class="min-h-[2.75rem] text-sm text-tertiary" aria-live="polite">
        <template x-if="hover === null"><span>{{ __('Hover a bar to see the details.') }}</span></template>
        <template x-if="hover !== null">
            <span>
                <span class="font-semibold text-neutral-900" x-text="b[hover].label"></span>
                <span class="mx-1">·</span>
                <span class="font-tertiary text-lg font-bold tabular-nums text-neutral-900" x-text="'Ksh ' + ((b[hover].models + b[hover].jobs) / 100).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })"></span>
                <span class="ml-2 inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-teal-600"></span><span x-text="'Ksh ' + (b[hover].models / 100).toLocaleString('en-US')"></span></span>
                <span class="ml-2 inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-indigo-500"></span><span x-text="'Ksh ' + (b[hover].jobs / 100).toLocaleString('en-US')"></span></span>
            </span>
        </template>
    </p>

    <div class="mt-1 flex flex-1 gap-2">
        {{-- The scale: each label sits at its own height, and a spacer matches the row of date labels below the columns --}}
        <div class="flex w-14 shrink-0 flex-col" aria-hidden="true">
            <div class="relative flex-1">
                @foreach ($ticks as $f)
                    <span class="absolute right-0 translate-y-1/2 whitespace-nowrap text-[10px] leading-none text-neutral-400" style="bottom: {{ $f * 100 }}%">{{ $compact($niceTop * $f) }}</span>
                @endforeach
            </div>
            <div class="h-[1.375rem]"></div>
        </div>

        <div class="flex min-w-0 flex-1 flex-col">
            <div class="relative flex-1">
                @foreach ($ticks as $f)
                    <div class="absolute inset-x-0 border-t {{ $f > 0 ? 'border-dashed border-neutral-200' : 'border-neutral-200' }}" style="bottom: {{ $f * 100 }}%"></div>
                @endforeach
                <div class="absolute inset-0 flex">
                    @foreach ($buckets as $i => $bucket)
                        @php
                            $total = $bucket['models'] + $bucket['jobs'];
                            $height = $total > 0 ? max(1.2, $total / $niceTop * 100) : 0;
                        @endphp
                        <div class="relative flex flex-1 items-end justify-center outline-none" tabindex="0" role="img" aria-label="{{ $noun }} {{ $bucket['label'] }}: {{ number_format($total / 100) }} shillings"
                            @mouseenter="hover = {{ $i }}" @mouseleave="hover = null" @focus="hover = {{ $i }}" @blur="hover = null">
                            @if ($total > 0)
                                <div class="flex w-full max-w-[34px] flex-col-reverse overflow-hidden rounded-t-md transition-opacity" :class="hover !== null && hover !== {{ $i }} ? 'opacity-45' : ''" style="height: {{ round($height, 2) }}%; width: 62%">
                                    @if ($bucket['models'] > 0)<div class="bg-teal-600" style="height: {{ round($bucket['models'] / $total * 100, 2) }}%"></div>@endif
                                    @if ($bucket['jobs'] > 0)<div class="bg-indigo-500" style="height: {{ round($bucket['jobs'] / $total * 100, 2) }}%"></div>@endif
                                </div>
                            @else
                                <div class="h-0.5 w-[62%] max-w-[34px] rounded-full bg-neutral-200"></div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="mt-1.5 flex h-4" aria-hidden="true">
                @foreach ($buckets as $i => $bucket)
                    <div class="relative flex-1">
                        @if ($i % $labelEvery === 0)<span @class(['absolute left-1/2 -translate-x-1/2 whitespace-nowrap text-[10px] text-neutral-400', 'hidden sm:block' => $i % ($labelEvery * 2) !== 0])>{{ $bucket['label'] }}</span>@endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
