@use('App\Http\Controllers\Admin\AdminModelDirectoryController')
<x-staff-layout :title="__('All models')">
    <div class="container mx-auto max-w-6xl px-4 py-8">
        <div class="mb-6">
            <h1 class="font-tertiary text-2xl font-semibold text-neutral-900">{{ __('All models') }}</h1>
            <p class="mt-1 max-w-2xl text-sm text-tertiary">{{ __('The whole catalogue in every status. Models waiting for a decision are in the review queue under Moderation.') }}</p>
        </div>

        <nav aria-label="{{ __('Filter by status') }}" class="-mx-4 mb-4 overflow-x-auto px-4 sm:mx-0 sm:px-0">
            <ul class="flex min-w-max items-center gap-2">
                @foreach ($statuses as $key => $label)
                    @php $on = $status === $key; @endphp
                    <li><a href="{{ route('admin.catalogue.index', array_filter(['status' => $key, 'sort' => $sort !== 'newest' ? $sort : null, 'q' => $term])) }}" @if ($on) aria-current="true" @endif
                        @class(['inline-flex items-center gap-2 rounded-full border px-3.5 py-1.5 text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40', 'border-teal-600 bg-teal-600 text-white' => $on, 'border-neutral-200 bg-white text-neutral-700 hover:border-neutral-300 hover:bg-neutral-50' => ! $on])>
                        {{ __($label) }}<span @class(['text-xs tabular-nums', 'text-teal-100' => $on, 'text-tertiary' => ! $on])>{{ $counts[$key] }}</span></a></li>
                @endforeach
            </ul>
        </nav>

        <form method="GET" action="{{ route('admin.catalogue.index') }}" role="search" class="mb-5 flex flex-wrap gap-2">
            <input type="hidden" name="status" value="{{ $status }}">
            <label for="q" class="sr-only">{{ __('Search models') }}</label>
            <input id="q" type="search" name="q" value="{{ $term }}" placeholder="{{ __('Title or store name') }}" class="min-w-0 flex-1 rounded-xl border border-neutral-300 px-3 py-2 text-sm focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25 sm:max-w-sm">
            <label for="sort" class="sr-only">{{ __('Sort by') }}</label>
            <select id="sort" name="sort" onchange="this.form.requestSubmit()" class="rounded-xl border border-neutral-300 bg-white px-3 py-2 text-sm focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25">
                @foreach (AdminModelDirectoryController::SORTS as $key => $label)<option value="{{ $key }}" @selected($sort === $key)>{{ __($label) }}</option>@endforeach
            </select>
            <x-btn type="submit" variant="secondary">{{ __('Search') }}</x-btn>
        </form>

        @if ($models->isEmpty())
            <x-empty-state icon="cube" :title="__('Nothing here')" :description="__('No models match this filter.')" />
        @else
            <x-card clip>
                <ul class="divide-y divide-neutral-100">
                    @foreach ($models as $model)
                        @php $cover = $model->images->first(); @endphp
                        <li>
                            <a href="{{ route('admin.catalogue.show', $model) }}" class="flex flex-wrap items-center gap-4 px-5 py-3.5 hover:bg-neutral-50 focus:outline-none focus-visible:bg-neutral-50">
                                <span class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-neutral-100 ring-1 ring-black/5">@if ($cover)<img src="{{ $cover->url() }}" alt="" loading="lazy" class="h-full w-full object-cover">@else<x-icon name="cube" class="h-6 w-6 text-neutral-300" />@endif</span>
                                <div class="min-w-0 flex-1">
                                    <p class="flex flex-wrap items-center gap-2 text-sm font-semibold text-neutral-900">{{ $model->title }}<x-badge :tone="$model->status->tone()" class="px-2 py-0.5 text-xs font-medium">{{ __($model->status->label()) }}</x-badge></p>
                                    <p class="mt-0.5 truncate text-xs text-tertiary">{{ $model->sellerProfile?->display_name ?? $model->seller?->name }} · {{ $model->category?->name ?? __('No category') }}</p>
                                </div>
                                <p class="w-24 text-right text-sm font-medium tabular-nums text-neutral-900">{{ $model->isFree() ? __('Free') : \App\Support\Money::formatMinor($model->price_minor, 0) }}</p>
                                <p class="hidden w-28 text-right md:block">@if ($model->rating_count > 0)<x-models.stars :rating="$model->rating_avg" :count="$model->rating_count" size="h-3.5 w-3.5" />@else<span class="text-xs text-tertiary">{{ __('No reviews') }}</span>@endif</p>
                                <p class="hidden w-28 text-right text-xs tabular-nums text-tertiary lg:block">{{ $model->wishlist_items_count }} {{ __('saves') }} · {{ $model->purchases_count }} {{ __('sales') }}</p>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </x-card>
            <x-pager :paginator="$models" />
        @endif
    </div>
</x-staff-layout>
