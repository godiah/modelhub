@php
    $change = function (array $trend) {
        $now = $trend['now']; $then = $trend['then'];
        if ($then === 0) { return $now > 0 ? ['+'.$now, 'text-green-700'] : ['no change', 'text-tertiary']; }
        $pct = (int) round(($now - $then) / $then * 100);

        return [($pct >= 0 ? '+' : '').$pct.'%', $pct >= 0 ? 'text-green-700' : 'text-red-700'];
    };
    $tiles = [
        ['Members', $stats['members']['total'], 'user-group', __(':n new this week', ['n' => $stats['members']['new']['now']]), $change($stats['members']['new']), __(':n signed in this week', ['n' => $stats['members']['active']])],
        ['Open projects', $stats['projects']['open'], 'briefcase', __(':n posted this week', ['n' => $stats['projects']['new']['now']]), $change($stats['projects']['new']), __(':n projects in all', ['n' => $stats['projects']['total']])],
        ['Hires in progress', $stats['hires']['active'], 'chat-bubble-left-right', __(':n completed', ['n' => $stats['hires']['completed']]), null, __(':n in dispute', ['n' => $stats['hires']['disputed']])],
        ['In escrow', \App\Support\Money::format($stats['hires']['escrow'], 0), 'banknotes', __('Funded and not yet released'), null, null],
        ['Live models', $stats['models']['live'], 'cube', __(':n listed this week', ['n' => $stats['models']['new']['now']]), $change($stats['models']['new']), __(':n waiting for review', ['n' => $stats['models']['in_review']])],
        ['Stores', $stats['sellers']['approved'], 'tag', __(':n applications waiting', ['n' => $stats['sellers']['pending']]), null, null],
        ['Applications', $stats['applications']['now'], 'document-text', __('sent in the last 7 days'), $change($stats['applications']), null],
        ['Model reviews', $stats['reviews']['now'], 'star', __('written in the last 7 days'), $change($stats['reviews']), null],
    ];
    $charts = [['New members per week', $stats['series']['members']], ['New projects per week', $stats['series']['projects']], ['New models per week', $stats['series']['models']]];
@endphp
<x-staff-layout :title="__('Platform overview')">
    <div class="container mx-auto max-w-7xl px-4 py-8">
        <x-staff.header :title="__('Platform overview')">{{ __('How the platform is doing. The numbers refresh every few minutes; trends compare the last 7 days with the 7 before.') }}</x-staff.header>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($tiles as [$label, $value, $icon, $hint, $trend, $extra])
                <x-card class="p-5">
                    <div class="flex items-start justify-between gap-3"><p class="text-sm font-medium text-tertiary">{{ __($label) }}</p><span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-teal-50 text-teal-700"><x-icon :name="$icon" class="h-5 w-5" /></span></div>
                    <p class="mt-3 font-tertiary text-3xl font-bold tabular-nums text-neutral-900">{{ is_numeric($value) ? number_format($value) : $value }}</p>
                    <p class="mt-1 text-xs text-tertiary">{{ $hint }}@if ($trend) · <span class="font-semibold {{ $trend[1] }}">{{ $trend[0] }}</span>@endif</p>
                    @if ($extra)<p class="text-xs text-tertiary">{{ $extra }}</p>@endif
                </x-card>
            @endforeach
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
            @foreach ($charts as [$title, $series])
                @php $max = max(1, collect($series)->max('count')); @endphp
                <x-panel :title="__($title)" :description="__('Last :n weeks', ['n' => count($series)])">
                    <div class="flex h-32 items-end gap-1.5" role="img" aria-label="{{ $title }}: {{ collect($series)->pluck('count')->implode(', ') }}">
                        @foreach ($series as $week)
                            <div class="flex h-full flex-1 flex-col justify-end" title="{{ $week['label'] }}: {{ $week['count'] }}"><div class="w-full rounded-t bg-teal-500/80" style="height: {{ max(2, round($week['count'] / $max * 100)) }}%"></div></div>
                        @endforeach
                    </div>
                    <div class="mt-2 flex justify-between text-[11px] text-tertiary"><span>{{ $series[0]['label'] }}</span><span>{{ $series[count($series) - 1]['label'] }}</span></div>
                </x-panel>
            @endforeach
        </div>

        @if ($stats['members']['suspended'] || $stats['projects']['taken_down'] || $stats['members']['unverified'])
            <p class="mt-6 text-sm text-tertiary">{{ __(':s suspended members · :t projects taken down · :u members with an unverified email', ['s' => $stats['members']['suspended'], 't' => $stats['projects']['taken_down'], 'u' => $stats['members']['unverified']]) }}</p>
        @endif
    </div>
</x-staff-layout>
