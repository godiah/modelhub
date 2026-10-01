@php
    $store = $models['store'] ?? null;
    $m = $models['summary'] ?? null;
    $modelTiles = $m ? [
        [
            'label' => __('Live models'),
            'value' => $m['live'],
            'hint' => $m['in_review'] > 0 ? trans_choice(':count more in review|:count more in review', $m['in_review'], ['count' => $m['in_review']]) : __('Visible to buyers'),
            'icon' => 'cube',
            'url' => route('seller.models.index', ['status' => 'published']),
        ],
        [
            'label' => __('Need changes'),
            'value' => $m['needs_changes'],
            'hint' => $m['needs_changes'] > 0 ? __('Fix and send again') : __('Nothing sent back'),
            'icon' => 'pencil-square',
            'url' => route('seller.models.index', ['status' => $m['needs_changes'] > 0 ? 'rejected' : null]),
            'alert' => $m['needs_changes'] > 0,
        ],
        [
            'label' => __('Store rating'),
            'value' => $m['rating_public'] ? number_format($m['rating'], 1) : '—',
            'hint' => $m['rating_public']
                ? trans_choice(':count buyer review|:count buyer reviews', $m['rating_count'], ['count' => $m['rating_count']])
                : __('Shown after :count reviews', ['count' => config('marketplace.min_store_reviews')]),
            'icon' => 'star',
            'url' => route('sellers.show', $store->slug),
        ],
        [
            'label' => __('Saves'),
            'value' => $m['saves'],
            'hint' => __('Members who saved your models'),
            'icon' => 'heart',
            'url' => route('seller.models.index'),
        ],
    ] : [];

    $tiles = [
        [
            'label' => __('Active engagements'),
            'value' => $summary['active'],
            'hint' => __('In progress right now'),
            'icon' => 'chat-bubble-left-right',
            'url' => route('engagements.index'),
        ],
        [
            'label' => __('Needs your action'),
            'value' => $summary['needs_action'],
            'hint' => $summary['needs_action'] > 0 ? __('See the list below') : __('Nothing waiting on you'),
            'icon' => 'clock',
            'url' => '#attention',
            'alert' => $summary['needs_action'] > 0,
        ],
        [
            'label' => __('Earned'),
            'value' => \App\Support\Money::format($summary['earned'], 0),
            'hint' => __('From completed work'),
            'icon' => 'banknotes',
            'url' => route('engagements.index', ['status' => 'completed']),
        ],
        [
            'label' => __('Open applications'),
            'value' => $summary['open_applications'],
            'hint' => trans_choice(':count project posted|:count projects posted', $summary['posted_projects'], ['count' => $summary['posted_projects']]),
            'icon' => 'document-text',
            'url' => route('applications.my'),
        ],
    ];
@endphp

@php
    // Sellers get a Projects row and a Models row. "Needs your action" counts both products, so it would be
    // misfiled under either heading: for sellers the greeting carries that count (and links to the list) instead.
    if ($modelTiles !== []) {
        $tiles = array_values(array_filter($tiles, fn ($tile) => $tile['url'] !== '#attention'));
    }
@endphp
@foreach ([[__('Projects'), $tiles], [__('Models'), $modelTiles]] as [$rowLabel, $row])
    @continue($row === [])
    <div @class(['mt-6' => ! $loop->first])>
        @if ($modelTiles !== [])
            <h3 class="mb-2 text-xs font-semibold uppercase tracking-wider text-neutral-400">{{ $rowLabel }}</h3>
        @endif
        <div @class(['grid grid-cols-2 gap-4', 'lg:grid-cols-4' => count($row) === 4, 'lg:grid-cols-3' => count($row) === 3])>
            @foreach ($row as $tile)
                <a href="{{ $tile['url'] }}" @if ($tile['url'][0] !== '#') wire:navigate @endif
                    class="group block focus:outline-none">
                    <x-card
                        class="h-full p-4 transition-shadow duration-200 group-hover:shadow-md group-focus-visible:ring-2 group-focus-visible:ring-secondary/40 sm:p-5">
                        <div class="flex items-start justify-between gap-3">
                            <p class="text-sm font-medium text-tertiary">{{ $tile['label'] }}</p>
                            <span @class([
                                'flex h-9 w-9 shrink-0 items-center justify-center rounded-full',
                                'bg-amber-50 text-amber-700' => $tile['alert'] ?? false,
                                'bg-teal-50 text-teal-700' => !($tile['alert'] ?? false),
                            ])>
                                <x-icon :name="$tile['icon']" class="h-5 w-5" />
                            </span>
                        </div>
                        <p class="mt-3 font-tertiary text-2xl font-bold text-neutral-900 sm:text-3xl">{{ $tile['value'] }}</p>
                        <p class="mt-1 text-xs text-tertiary">{{ $tile['hint'] }}</p>
                    </x-card>
                </a>
            @endforeach
        </div>
    </div>
@endforeach
