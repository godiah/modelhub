@php
    $tabs = collect($statuses)->map(fn ($label, $key) => ['label' => $label, 'count' => $counts[$key], 'on' => $status === $key, 'url' => route('admin.catalogue.index', array_filter(['status' => $key, 'q' => $term, 'sort' => request('sort'), 'dir' => request('dir')]))])->values()->all();
    $chips = [$term !== '' ? ['label' => __('Search: :term', ['term' => $term]), 'remove' => ['q']] : null];
    $filtered = $term !== '' || $status !== 'all';
    $columns = [
        ['key' => 'title', 'label' => 'Model', 'sort' => 'title'],
        ['key' => 'price', 'label' => 'Price', 'sort' => 'price', 'first' => 'desc', 'align' => 'right'],
        ['key' => 'rating', 'label' => 'Rating', 'sort' => 'rating', 'first' => 'desc', 'class' => 'hidden md:table-cell'],
        ['key' => 'saves', 'label' => 'Saves', 'sort' => 'saves', 'first' => 'desc', 'align' => 'right', 'class' => 'hidden lg:table-cell'],
        ['key' => 'sales', 'label' => 'Sales', 'sort' => 'sales', 'first' => 'desc', 'align' => 'right', 'class' => 'hidden lg:table-cell'],
        ['key' => 'newest', 'label' => 'Added', 'sort' => 'newest', 'first' => 'desc', 'align' => 'right', 'class' => 'hidden xl:table-cell'],
    ];
@endphp
<x-staff-layout :title="__('All models')">
    <div class="container mx-auto max-w-7xl px-4 py-8">
        <x-staff.header :title="__('All models')" :description="__('The whole catalogue in every status. Models waiting for a decision are in the review queue under Moderation.')" />

        <x-staff.toolbar :tabs="$tabs" :search="$term" :placeholder="__('Title or store name')" :chips="$chips" :action="route('admin.catalogue.index')" />

        @if ($models->isEmpty())
            <x-empty-state icon="cube" :title="$filtered ? __('No models match') : __('No models yet')" :description="$filtered ? __('Try a different search or filter.') : __('Models appear here as sellers add them.')">
                @if ($filtered)<x-btn variant="secondary" :href="route('admin.catalogue.index')" wire:navigate>{{ __('Clear filters') }}</x-btn>@endif
            </x-empty-state>
        @else
            <x-staff.table :columns="$columns" :sort="$sort" :dir="$dir" :paginator="$models" :summary="trans_choice(':count model|:count models', $models->total(), ['count' => number_format($models->total())])">
                @foreach ($models as $model)
                    @php $cover = $model->images->first(); @endphp
                    <x-staff.row :href="route('admin.catalogue.show', $model)">
                        <td class="px-4">
                            <a href="{{ route('admin.catalogue.show', $model) }}" wire:navigate class="flex items-center gap-3 focus:outline-none focus-visible:underline">
                                <span class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-neutral-100 ring-1 ring-black/5">@if ($cover)<img src="{{ $cover->url() }}" alt="" loading="lazy" class="h-full w-full object-cover">@else<x-icon name="cube" class="h-5 w-5 text-neutral-300" />@endif</span>
                                <span class="min-w-0">
                                    <span class="flex flex-wrap items-center gap-2 font-semibold text-neutral-900">{{ $model->title }}<x-badge :tone="$model->status->tone()" class="px-2 py-0.5 text-xs font-medium">{{ __($model->status->label()) }}</x-badge></span>
                                    <span class="block truncate text-xs font-normal text-tertiary">{{ $model->sellerProfile?->display_name ?? $model->seller?->name }} · {{ $model->category?->name ?? __('No category') }}</span>
                                </span>
                            </a>
                        </td>
                        <td class="whitespace-nowrap px-4 text-right font-medium tabular-nums text-neutral-900">{{ $model->isFree() ? __('Free') : \App\Support\Money::formatMinor($model->price_minor, 0) }}</td>
                        <td class="hidden whitespace-nowrap px-4 md:table-cell">@if ($model->rating_count > 0)<x-models.stars :rating="$model->rating_avg" :count="$model->rating_count" size="h-3.5 w-3.5" />@else<span class="text-xs text-tertiary">{{ __('No reviews') }}</span>@endif</td>
                        <td class="hidden px-4 text-right tabular-nums text-neutral-700 lg:table-cell">{{ $model->wishlist_items_count }}</td>
                        <td class="hidden px-4 text-right tabular-nums text-neutral-700 lg:table-cell">{{ $model->purchases_count }}</td>
                        <td class="hidden whitespace-nowrap px-4 text-right text-neutral-600 xl:table-cell">{{ $model->created_at->format('M j, Y') }}</td>
                    </x-staff.row>
                @endforeach
            </x-staff.table>
        @endif
    </div>
</x-staff-layout>
