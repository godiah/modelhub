<section aria-labelledby="pulse-heading">
    <div class="mb-3 flex items-center justify-between gap-3">
        <h2 id="pulse-heading" class="font-tertiary text-base font-semibold text-neutral-900">{{ __('Platform pulse') }}</h2>
        <a href="{{ route('admin.overview') }}" class="text-sm font-medium text-teal-700 hover:text-teal-800">{{ __('Full overview') }}</a>
    </div>
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
        @foreach ($pulse['tiles'] as $tile)
            <x-card class="p-4">
                <div class="flex items-start justify-between gap-2"><p class="text-xs font-medium text-tertiary">{{ __($tile['label']) }}</p><x-icon :name="$tile['icon']" class="h-4 w-4 text-teal-600" /></div>
                <p class="mt-2 font-tertiary text-2xl font-bold tabular-nums text-neutral-900">{{ $tile['value'] }}</p>
                <p class="mt-0.5 text-xs text-tertiary">{{ $tile['hint'] }}@if ($tile['trend']) · <span class="font-semibold {{ $tile['trend'][1] ? 'text-green-700' : 'text-red-700' }}">{{ $tile['trend'][0] }}</span>@endif</p>
                @if ($tile['series'])
                    @php $max = max(1, collect($tile['series'])->max('count')); @endphp
                    <div class="mt-3 flex h-8 items-end gap-0.5" role="img" aria-label="{{ __($tile['label']) }}: {{ collect($tile['series'])->pluck('count')->implode(', ') }}">
                        @foreach ($tile['series'] as $week)<div class="flex h-full flex-1 flex-col justify-end" title="{{ $week['label'] }}: {{ $week['count'] }}"><div class="w-full rounded-t-sm bg-teal-500/70" style="height: {{ max(8, round($week['count'] / $max * 100)) }}%"></div></div>@endforeach
                    </div>
                @endif
            </x-card>
        @endforeach
    </div>
</section>
