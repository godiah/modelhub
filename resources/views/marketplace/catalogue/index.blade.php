@php
    $features = ['free' => __('Free'), 'animated' => __('Animated'), 'pbr' => __('PBR'), 'rigged' => __('Rigged'), 'low_poly' => __('Low-poly'), 'textures' => __('Textures'), 'vr' => __('VR / AR'), 'print' => __('3D print')];
    $selectedFeatures = (array) ($filters['features'] ?? []);
    $fieldClass = 'block w-full rounded-xl border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 placeholder-neutral-400 transition-colors hover:border-neutral-400 focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25';
    // No width in the shared base, so the bar's compact controls (w-auto, w-24) are not overridden by w-full
    $barField = str_replace('block w-full', 'block', $fieldClass);
    $active = ($filters['category'] ?? null) || ($filters['q'] ?? null) || ($filters['format'] ?? null) || ($filters['software'] ?? null) || $selectedFeatures || ($filters['min_price'] ?? null) || ($filters['max_price'] ?? null);
    // Take it from the chips so its sub-categories carry their published counts
    $topSelected = $chips->firstWhere('id', $category?->parent_id ?? $category?->id);
    $chipUrl = fn (?string $slug) => route('models.index', array_filter(array_merge(request()->except(['category', 'page']), ['category' => $slug])));
@endphp
<x-app-layout title="3D models">
    <div class="container mx-auto max-w-7xl px-4 py-8">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="font-tertiary text-3xl font-semibold tracking-tight text-neutral-900">{{ $category?->name ?? __('3D models') }}</h1>
                <p class="mt-1 text-sm text-tertiary">{{ trans_choice(':count model|:count models', $products->total(), ['count' => $products->total()]) }}</p>
            </div>
            @auth
                <x-btn variant="secondary" href="{{ route('seller.index') }}"><x-icon name="cube" class="h-4 w-4" />{{ __('Sell your models') }}</x-btn>
            @endauth
        </div>

        <!-- Categories -->
        <nav aria-label="{{ __('Categories') }}" class="-mx-4 mb-3 overflow-x-auto px-4 sm:mx-0 sm:px-0">
            <ul class="flex min-w-max items-center gap-2">
                <li><a href="{{ $chipUrl(null) }}" @if (! $category) aria-current="true" @endif @class(['inline-flex items-center rounded-full border px-3.5 py-1.5 text-sm font-medium transition-colors', 'border-teal-600 bg-teal-600 text-white' => ! $category, 'border-neutral-200 bg-white text-neutral-700 hover:border-neutral-300' => $category])>{{ __('All') }}</a></li>
                @foreach ($chips as $top)
                    @continue($top->published_total === 0 && $topSelected?->id !== $top->id)
                    @php $on = $topSelected?->id === $top->id; @endphp
                    <li><a href="{{ $chipUrl($top->slug) }}" @if ($on) aria-current="true" @endif @class(['inline-flex items-center gap-2 rounded-full border px-3.5 py-1.5 text-sm font-medium transition-colors', 'border-teal-600 bg-teal-600 text-white' => $on, 'border-neutral-200 bg-white text-neutral-700 hover:border-neutral-300' => ! $on])>{{ $top->name }}<span @class(['text-xs tabular-nums', 'text-teal-100' => $on, 'text-tertiary' => ! $on])>{{ $top->published_total }}</span></a></li>
                @endforeach
            </ul>
        </nav>
        @if ($topSelected && $topSelected->children->isNotEmpty())
            <nav aria-label="{{ __('Sub-categories') }}" class="-mx-4 mb-4 overflow-x-auto px-4 sm:mx-0 sm:px-0">
                <ul class="flex min-w-max items-center gap-1.5">
                    @foreach ($topSelected->children as $child)
                        @continue($child->published_total === 0 && $category?->id !== $child->id)
                        @php $on = $category?->id === $child->id; @endphp
                        <li><a href="{{ $chipUrl($on ? $topSelected->slug : $child->slug) }}" @class(['inline-flex items-center gap-1.5 rounded-lg px-3 py-1 text-sm transition-colors', 'bg-teal-50 font-medium text-teal-800 ring-1 ring-teal-600/20' => $on, 'text-neutral-600 hover:bg-white hover:text-neutral-900' => ! $on])>{{ $child->name }} <span class="text-xs tabular-nums text-tertiary">{{ $child->published_total }}</span></a></li>
                    @endforeach
                </ul>
            </nav>
        @endif

        <!-- Filter bar (a real GET form) -->
        <form method="GET" action="{{ route('models.index') }}" role="search" class="mb-6 flex flex-wrap items-center gap-3" id="catalogue-filters">
            @if ($category)<input type="hidden" name="category" value="{{ $category->slug }}">@endif
            <div class="relative min-w-[14rem] flex-1">
                <x-icon name="magnifying-glass" class="pointer-events-none absolute left-3.5 top-2.5 h-5 w-5 text-neutral-400" />
                <label for="q" class="sr-only">{{ __('Search models') }}</label>
                <input type="search" id="q" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100" placeholder="{{ __('Search models…') }}" class="{{ $fieldClass }} pl-11">
            </div>

            <label class="sr-only" for="format">{{ __('File format') }}</label>
            <select id="format" name="format" onchange="this.form.requestSubmit()" class="{{ $barField }} w-auto">
                <option value="">{{ __('File formats') }}</option>
                @foreach ($formats as $format)<option value="{{ $format }}" @selected(($filters['format'] ?? '') === $format)>{{ strtoupper($format) }}</option>@endforeach
            </select>

            @if ($software->isNotEmpty())
                <label class="sr-only" for="software">{{ __('Software') }}</label>
                <select id="software" name="software" onchange="this.form.requestSubmit()" class="{{ $barField }} w-auto">
                    <option value="">{{ __('Software') }}</option>
                    @foreach ($software as $item)<option value="{{ $item->id }}" @selected((int) ($filters['software'] ?? 0) === $item->id)>{{ $item->name }}</option>@endforeach
                </select>
            @endif

            <div class="flex items-center gap-1.5">
                <label class="sr-only" for="min_price">{{ __('Minimum price') }}</label>
                <input type="number" id="min_price" name="min_price" value="{{ $filters['min_price'] ?? '' }}" min="0" placeholder="{{ __('Min') }}" class="{{ $barField }} w-24 tabular-nums">
                <span class="text-tertiary" aria-hidden="true">–</span>
                <label class="sr-only" for="max_price">{{ __('Maximum price') }}</label>
                <input type="number" id="max_price" name="max_price" value="{{ $filters['max_price'] ?? '' }}" min="0" placeholder="{{ __('Max') }}" class="{{ $barField }} w-24 tabular-nums">
            </div>

            <label class="sr-only" for="sort">{{ __('Sort by') }}</label>
            <select id="sort" name="sort" onchange="this.form.requestSubmit()" class="{{ $barField }} w-auto font-medium">
                @foreach (['newest' => __('Newest first'), 'price_low' => __('Price: low to high'), 'price_high' => __('Price: high to low'), 'top_rated' => __('Top rated')] as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['sort'] ?? 'newest') === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <x-btn type="submit">{{ __('Search') }}</x-btn>

            <div class="flex basis-full flex-wrap items-center gap-2" role="group" aria-label="{{ __('Quick filters') }}">
                @foreach ($features as $value => $label)
                    <label class="cursor-pointer">
                        <input type="checkbox" name="features[]" value="{{ $value }}" @checked(in_array($value, $selectedFeatures, true)) onchange="this.form.requestSubmit()" class="peer sr-only">
                        <span class="inline-flex items-center rounded-full border border-neutral-200 bg-white px-3.5 py-1.5 text-sm font-medium text-neutral-700 transition-colors hover:border-neutral-300 peer-checked:border-teal-600 peer-checked:bg-teal-50 peer-checked:text-teal-800 peer-focus-visible:ring-2 peer-focus-visible:ring-secondary/40">{{ $label }}</span>
                    </label>
                @endforeach
                @if ($active)
                    <a href="{{ route('models.index') }}" class="ml-1 text-sm font-medium text-teal-700 hover:underline">{{ __('Clear all') }}</a>
                @endif
            </div>
        </form>

        @if ($products->isEmpty())
            <x-empty-state icon="cube" :title="$active ? __('No models match') : __('No models yet')"
                :description="$active ? __('Try removing a filter or searching for something broader.') : __('Approved sellers are adding the first models. Check back soon.')">
                @if ($active)<x-btn variant="secondary" href="{{ route('models.index') }}">{{ __('Clear all filters') }}</x-btn>@endif
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
