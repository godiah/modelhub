@php
    $tabs = collect($statuses)->map(fn ($label, $key) => ['label' => $label, 'count' => $counts[$key], 'on' => $status === $key, 'url' => route('admin.stores.index', array_filter(['status' => $key, 'q' => $term, 'sort' => request('sort'), 'dir' => request('dir')]))])->values()->all();
    $chips = [$term !== '' ? ['label' => __('Search: :term', ['term' => $term]), 'remove' => ['q']] : null];
    $filtered = $term !== '' || $status !== 'all';
    $columns = [
        ['key' => 'name', 'label' => 'Store', 'sort' => 'name'],
        ['key' => 'models', 'label' => 'Live / all models', 'sort' => 'models', 'first' => 'desc', 'align' => 'right'],
        ['key' => 'rating', 'label' => 'Rating', 'sort' => 'rating', 'first' => 'desc', 'class' => 'hidden md:table-cell'],
        ['key' => 'applied', 'label' => 'Applied', 'sort' => 'applied', 'first' => 'desc', 'align' => 'right', 'class' => 'hidden sm:table-cell'],
    ];
@endphp
<x-staff-layout :title="__('All stores')">
    <div class="container mx-auto max-w-7xl px-4 py-8">
        <x-staff.header :title="__('All stores')" :description="__('Every seller, whatever their status. Applications waiting for a decision are in the Seller applications queue.')" />

        <x-staff.toolbar :tabs="$tabs" :search="$term" :placeholder="__('Store or seller name')" :chips="$chips" :action="route('admin.stores.index')" />

        @if ($stores->isEmpty())
            <x-empty-state icon="tag" :title="$filtered ? __('No stores match') : __('No stores yet')" :description="$filtered ? __('Try a different search or filter.') : __('Stores appear here as members apply to sell.')">
                @if ($filtered)<x-btn variant="secondary" :href="route('admin.stores.index')" wire:navigate>{{ __('Clear filters') }}</x-btn>@endif
            </x-empty-state>
        @else
            <x-staff.table :columns="$columns" :sort="$sort" :dir="$dir" :paginator="$stores" :summary="trans_choice(':count store|:count stores', $stores->total(), ['count' => number_format($stores->total())])">
                @foreach ($stores as $store)
                    <x-staff.row :href="route('admin.stores.show', $store)">
                        <td class="px-4">
                            <a href="{{ route('admin.stores.show', $store) }}" wire:navigate class="flex items-center gap-3 focus:outline-none focus-visible:underline">
                                <x-store-avatar :store="$store" size="h-10 w-10" />
                                <span class="min-w-0">
                                    <span class="flex flex-wrap items-center gap-2 font-semibold text-neutral-900">{{ $store->display_name }}<x-badge :tone="match ($store->status->value) { 'approved' => 'green', 'pending' => 'amber', 'rejected' => 'neutral', default => 'red' }" class="px-2 py-0.5 text-xs font-medium">{{ ucfirst($store->status->value) }}</x-badge>@if ($store->user?->isSuspended())<x-badge tone="red" class="px-2 py-0.5 text-xs font-medium">{{ __('Owner suspended') }}</x-badge>@endif</span>
                                    <span class="block truncate text-xs font-normal text-tertiary">{{ $store->user?->name }}</span>
                                </span>
                            </a>
                        </td>
                        <td class="whitespace-nowrap px-4 text-right tabular-nums text-neutral-700">{{ $store->published_count }} / {{ $store->products_count }}</td>
                        <td class="hidden whitespace-nowrap px-4 md:table-cell">@if ($store->hasPublicRating())<x-models.stars :rating="$store->rating_avg" :count="$store->rating_count" size="h-3.5 w-3.5" />@else<span class="text-xs text-tertiary">{{ $store->rating_count ? trans_choice(':count review|:count reviews', $store->rating_count, ['count' => $store->rating_count]) : __('No reviews') }}</span>@endif</td>
                        <td class="hidden whitespace-nowrap px-4 text-right text-neutral-600 sm:table-cell">{{ ($store->submitted_at ?? $store->created_at)->format('M j, Y') }}</td>
                    </x-staff.row>
                @endforeach
            </x-staff.table>
        @endif
    </div>
</x-staff-layout>
