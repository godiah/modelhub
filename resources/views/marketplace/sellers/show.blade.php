@php
    $selected = $filters['category'] ?? null;
    $chipUrl = fn (?string $slug) => route('sellers.show', array_filter(array_merge(['seller' => $seller->slug], request()->except(['category', 'page']), ['category' => $slug])));
    $since = ($seller->reviewed_at ?? $seller->created_at)->format('F Y');
@endphp
<x-app-layout :title="$seller->display_name.' · 3D models'" :crumb="$seller->display_name">
    <div class="container mx-auto max-w-7xl px-4 py-8">
        <!-- Store header -->
        <x-card class="mb-6 rounded-2xl">
            <div class="flex flex-col gap-5 p-6 sm:flex-row sm:items-start sm:p-8">
                <x-store-avatar :store="$seller" size="h-20 w-20" rounded="rounded-2xl" />
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-medium uppercase tracking-wide text-tertiary">{{ __('Seller') }}</p>
                    <h1 class="mt-1 font-tertiary text-2xl font-semibold text-neutral-900 sm:text-3xl">{{ $seller->display_name }}</h1>
                    @if ($seller->tagline)
                        <p class="mt-1 text-base text-neutral-600">{{ $seller->tagline }}</p>
                    @endif
                    <p class="mt-2 flex flex-wrap items-center gap-x-5 gap-y-1 text-sm text-tertiary">
                        @if ($seller->hasPublicRating())
                            <span title="{{ __('Average of :count buyer reviews across all their models', ['count' => $seller->rating_count]) }}"><x-models.stars :rating="$seller->rating_avg" :count="$seller->rating_count" showNumber /></span>
                        @else
                            <span class="inline-flex items-center gap-1.5"><x-icon name="star" class="h-4 w-4" />{{ __('Not rated yet') }}</span>
                        @endif
                        <span class="inline-flex items-center gap-1.5"><x-icon name="cube" class="h-4 w-4" />{{ trans_choice(':count model|:count models', $total, ['count' => $total]) }}</span>
                        <span class="inline-flex items-center gap-1.5"><x-icon name="calendar" class="h-4 w-4" />{{ __('Selling since :date', ['date' => $since]) }}</span>
                        @if ($seller->website_url)
                            <a href="{{ $seller->website_url }}" target="_blank" rel="nofollow noopener" class="inline-flex items-center gap-1.5 font-medium text-teal-700 hover:underline"><x-icon name="arrow-top-right-on-square" class="h-4 w-4" />{{ preg_replace('#^https?://(www\.)?#', '', rtrim($seller->website_url, '/')) }}</a>
                        @endif
                    </p>
                    <p class="mt-4 max-w-3xl whitespace-pre-line break-words text-sm leading-relaxed text-neutral-700">{{ $seller->bio }}</p>
                    @if ($seller->focus)
                        <p class="mt-3 max-w-3xl text-sm text-neutral-600"><span class="font-medium text-neutral-800">{{ __('What they make:') }}</span> {{ $seller->focus }}</p>
                    @endif
                </div>
            </div>
        </x-card>

        <!-- Their categories and sort -->
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <nav aria-label="{{ __('Categories') }}" class="-mx-4 min-w-0 flex-1 overflow-x-auto px-4 sm:mx-0 sm:px-0">
                <ul class="flex min-w-max items-center gap-2">
                    <li><a href="{{ $chipUrl(null) }}" @if (! $selected) aria-current="true" @endif @class(['inline-flex items-center rounded-full border px-3.5 py-1.5 text-sm font-medium transition-colors', 'border-teal-600 bg-teal-600 text-white' => ! $selected, 'border-neutral-200 bg-white text-neutral-700 hover:border-neutral-300' => $selected])>{{ __('All') }}</a></li>
                    @foreach ($categories as $top)
                        @php $on = $selected === $top->slug; @endphp
                        <li><a href="{{ $chipUrl($top->slug) }}" @if ($on) aria-current="true" @endif @class(['inline-flex items-center gap-2 rounded-full border px-3.5 py-1.5 text-sm font-medium transition-colors', 'border-teal-600 bg-teal-600 text-white' => $on, 'border-neutral-200 bg-white text-neutral-700 hover:border-neutral-300' => ! $on])>{{ $top->name }}<span @class(['text-xs tabular-nums', 'text-teal-100' => $on, 'text-tertiary' => ! $on])>{{ $top->published_total }}</span></a></li>
                    @endforeach
                </ul>
            </nav>
            <form method="GET" action="{{ route('sellers.show', $seller->slug) }}">
                @if ($selected)<input type="hidden" name="category" value="{{ $selected }}">@endif
                <label class="sr-only" for="sort">{{ __('Sort by') }}</label>
                <select id="sort" name="sort" onchange="this.form.requestSubmit()" class="block rounded-xl border border-neutral-300 bg-white px-3 py-2 text-sm font-medium text-neutral-900 hover:border-neutral-400 focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25">
                    @foreach (['newest' => __('Newest first'), 'price_low' => __('Price: low to high'), 'price_high' => __('Price: high to low'), 'top_rated' => __('Top rated')] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['sort'] ?? 'newest') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        @if ($products->isEmpty())
            <x-empty-state icon="cube" :title="__('No models here yet')" :description="$selected ? __('This seller has nothing in that category.') : __('This seller has not published any models yet.')">
                @if ($selected)<x-btn variant="secondary" href="{{ $chipUrl(null) }}">{{ __('Show all models') }}</x-btn>@endif
            </x-empty-state>
        @else
            <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                @foreach ($products as $product)
                    <x-models.card :product="$product" :saved="in_array($product->id, $savedIds)" />
                @endforeach
            </div>
            <x-pager :paginator="$products" />
        @endif
    </div>
</x-app-layout>
