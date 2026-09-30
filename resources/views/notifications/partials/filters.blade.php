@php
    $link = fn (string $key) => route('notifications.index', $key === 'all' ? [] : ['filter' => $key]);
    $items = [
        ['key' => 'all', 'label' => __('All'), 'icon' => 'inbox', 'count' => $counts['all'], 'badge' => false],
        ['key' => 'unread', 'label' => __('Unread'), 'icon' => 'bell', 'count' => $counts['unread'], 'badge' => true],
    ];
    $categories = collect(\App\Enums\NotificationCategory::cases())
        ->filter(fn ($category) => $counts['categories'][$category->value]['total'] > 0 || $filter === $category->value)
        ->map(fn ($category) => [
            'key' => $category->value,
            'label' => __($category->label()),
            'icon' => $category->icon(),
            'count' => $counts['categories'][$category->value]['total'],
            'badge' => false,
        ])
        ->values()
        ->all();
@endphp

{{-- Filter rail (lg+) / scrolling pills (below lg) --}}
<aside class="lg:sticky lg:top-24">
    <!-- Below lg: horizontal pills -->
    <nav aria-label="{{ __('Notification filters') }}"
        class="-mx-4 overflow-x-auto px-4 [scrollbar-width:none] lg:hidden [&::-webkit-scrollbar]:hidden">
        <ul class="flex w-max gap-2 pb-1">
            @foreach ([...$items, ...$categories] as $item)
                <li>
                    <a href="{{ $link($item['key']) }}" wire:navigate @if ($filter === $item['key']) aria-current="page" @endif
                        @class([
                            'inline-flex items-center gap-2 whitespace-nowrap rounded-full border px-3.5 py-1.5 text-sm font-medium transition-colors duration-150',
                            'border-neutral-900 bg-neutral-900 text-white' => $filter === $item['key'],
                            'border-neutral-200 bg-white text-neutral-700 hover:bg-neutral-50' => $filter !== $item['key'],
                        ])>
                        {{ $item['label'] }}
                        <span class="text-xs tabular-nums opacity-70">{{ $item['count'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    <!-- lg+: vertical rail -->
    <x-card class="hidden rounded-2xl p-2 lg:block">
        <nav aria-label="{{ __('Notification filters') }}">
            <ul class="space-y-1">
                @foreach ($items as $item)
                    @include('notifications.partials.filter-link')
                @endforeach
            </ul>

            @if (count($categories) > 0)
                <p class="mb-1 mt-4 px-3 text-xs font-semibold uppercase tracking-wider text-neutral-400">{{ __('Categories') }}</p>
                <ul class="space-y-1">
                    @foreach ($categories as $item)
                        @include('notifications.partials.filter-link')
                    @endforeach
                </ul>
            @endif
        </nav>
    </x-card>
</aside>
