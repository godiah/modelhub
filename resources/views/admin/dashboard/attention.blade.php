@if ($attention['queues'] !== [])
    <section aria-labelledby="attention-heading">
        <x-card clip>
            <div class="flex items-center justify-between gap-3 border-b border-neutral-100 px-5 py-4">
                <h2 id="attention-heading" class="font-tertiary text-base font-semibold text-neutral-900">{{ __('Needs attention') }}</h2>
                <p class="text-xs text-tertiary">{{ __('Oldest first. Amber after :a days, red after :r.', ['a' => \App\Services\Admin\StaffDashboardService::AMBER_DAYS, 'r' => \App\Services\Admin\StaffDashboardService::RED_DAYS]) }}</p>
            </div>
            @if ($attention['items'] === [])
                <div class="flex flex-col items-center px-6 py-10 text-center">
                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-green-50 text-green-600"><x-icon name="check-circle" class="h-6 w-6" /></span>
                    <p class="mt-4 font-semibold text-neutral-900">{{ __('Nothing is waiting for you') }}</p>
                    <p class="mt-1 max-w-sm text-sm text-tertiary">{{ __('Every queue you work is clear. New items appear here as they come in.') }}</p>
                </div>
            @else
                <ul class="divide-y divide-neutral-100">
                    @foreach ($attention['items'] as $item)
                        <li>
                            <a href="{{ $item['url'] }}" class="flex items-center gap-4 px-5 py-3.5 hover:bg-neutral-50 focus:outline-none focus-visible:bg-neutral-50">
                                <span @class(['flex h-9 w-9 shrink-0 items-center justify-center rounded-full', 'bg-red-50 text-red-700' => $item['tone'] === 'red', 'bg-amber-50 text-amber-700' => $item['tone'] === 'amber', 'bg-neutral-100 text-neutral-600' => $item['tone'] === 'neutral'])><x-icon :name="$item['icon']" class="h-5 w-5" /></span>
                                <span class="min-w-0 flex-1">
                                    <span class="flex flex-wrap items-center gap-2"><span class="truncate text-sm font-semibold text-neutral-900">{{ $item['title'] }}</span>@if ($item['tag'])<x-badge tone="blue" class="px-2 py-0.5 text-xs font-medium">{{ __($item['tag']) }}</x-badge>@endif</span>
                                    <span class="block truncate text-xs text-tertiary">{{ __($item['kind']) }} · {{ $item['detail'] }}</span>
                                </span>
                                <span @class(['shrink-0 text-xs font-medium tabular-nums', 'text-red-700' => $item['tone'] === 'red', 'text-amber-700' => $item['tone'] === 'amber', 'text-tertiary' => $item['tone'] === 'neutral'])>{{ $item['since'] ? __('waiting :when', ['when' => $item['since']->diffForHumans(null, true)]) : '' }}</span>
                                <x-icon name="chevron-right" class="h-4 w-4 shrink-0 text-neutral-300" />
                            </a>
                        </li>
                    @endforeach
                </ul>
                @if ($attention['total'] > count($attention['items']))
                    <p class="border-t border-neutral-100 px-5 py-3 text-xs text-tertiary">{{ __('Showing the oldest :shown of :total. Open a queue above for the rest.', ['shown' => count($attention['items']), 'total' => $attention['total']]) }}</p>
                @endif
            @endif
        </x-card>
    </section>
@endif
