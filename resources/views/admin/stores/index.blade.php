<x-staff-layout :title="__('All stores')">
    <div class="container mx-auto max-w-6xl px-4 py-8">
        <div class="mb-6">
            <h1 class="font-tertiary text-2xl font-semibold text-neutral-900">{{ __('All stores') }}</h1>
            <p class="mt-1 max-w-2xl text-sm text-tertiary">{{ __('Every seller, whatever their status. Applications waiting for a decision are in the Seller applications queue.') }}</p>
        </div>

        <nav aria-label="{{ __('Filter by status') }}" class="-mx-4 mb-4 overflow-x-auto px-4 sm:mx-0 sm:px-0">
            <ul class="flex min-w-max items-center gap-2">
                @foreach ($statuses as $key => $label)
                    @php $on = $status === $key; @endphp
                    <li><a href="{{ route('admin.stores.index', array_filter(['status' => $key, 'q' => $term])) }}" @if ($on) aria-current="true" @endif
                        @class(['inline-flex items-center gap-2 rounded-full border px-3.5 py-1.5 text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40', 'border-teal-600 bg-teal-600 text-white' => $on, 'border-neutral-200 bg-white text-neutral-700 hover:border-neutral-300 hover:bg-neutral-50' => ! $on])>
                        {{ __($label) }}<span @class(['text-xs tabular-nums', 'text-teal-100' => $on, 'text-tertiary' => ! $on])>{{ $counts[$key] }}</span></a></li>
                @endforeach
            </ul>
        </nav>

        <form method="GET" action="{{ route('admin.stores.index') }}" role="search" class="mb-5 flex gap-2">
            <input type="hidden" name="status" value="{{ $status }}">
            <label for="q" class="sr-only">{{ __('Search stores') }}</label>
            <input id="q" type="search" name="q" value="{{ $term }}" placeholder="{{ __('Store or seller name') }}" class="min-w-0 flex-1 rounded-xl border border-neutral-300 px-3 py-2 text-sm focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25 sm:max-w-sm">
            <x-btn type="submit" variant="secondary">{{ __('Search') }}</x-btn>
        </form>

        @if ($stores->isEmpty())
            <x-empty-state icon="tag" :title="__('Nothing here')" :description="__('No stores match this filter.')" />
        @else
            <x-card clip>
                <ul class="divide-y divide-neutral-100">
                    @foreach ($stores as $store)
                        <li>
                            <a href="{{ route('admin.stores.show', $store) }}" class="flex flex-wrap items-center gap-4 px-5 py-4 hover:bg-neutral-50 focus:outline-none focus-visible:bg-neutral-50">
                                <x-store-avatar :store="$store" size="h-11 w-11" />
                                <div class="min-w-0 flex-1">
                                    <p class="flex flex-wrap items-center gap-2 text-sm font-semibold text-neutral-900">{{ $store->display_name }}<x-badge :tone="match ($store->status->value) { 'approved' => 'green', 'pending' => 'amber', 'rejected' => 'neutral', default => 'red' }" class="px-2 py-0.5 text-xs font-medium">{{ ucfirst($store->status->value) }}</x-badge>@if ($store->user?->isSuspended())<x-badge tone="red" class="px-2 py-0.5 text-xs font-medium">{{ __('Member suspended') }}</x-badge>@endif</p>
                                    <p class="mt-0.5 truncate text-xs text-tertiary">{{ $store->user?->name }} · {{ __('applied :date', ['date' => ($store->submitted_at ?? $store->created_at)->format('M j, Y')]) }}</p>
                                </div>
                                <p class="w-28 text-right text-xs tabular-nums text-tertiary">{{ $store->published_count }} / {{ $store->products_count }} {{ __('live') }}</p>
                                <p class="hidden w-28 text-right md:block">@if ($store->hasPublicRating())<x-models.stars :rating="$store->rating_avg" :count="$store->rating_count" size="h-3.5 w-3.5" />@else<span class="text-xs text-tertiary">{{ $store->rating_count ? trans_choice(':count review|:count reviews', $store->rating_count, ['count' => $store->rating_count]) : __('No reviews') }}</span>@endif</p>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </x-card>
            <x-pager :paginator="$stores" />
        @endif
    </div>
</x-staff-layout>
