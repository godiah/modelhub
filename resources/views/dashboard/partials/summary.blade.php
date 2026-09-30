@php
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

<div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
    @foreach ($tiles as $tile)
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
