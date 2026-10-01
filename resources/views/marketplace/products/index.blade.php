@use('App\Enums\ProductStatus')
@php
    $pills = ['all' => __('All')] + collect(ProductStatus::cases())->mapWithKeys(fn ($case) => [$case->value => __($case->label())])->all();
    $fieldClass = 'block w-full rounded-xl border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 placeholder-neutral-400 transition-colors hover:border-neutral-400 focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25';
@endphp
<x-app-layout title="My models" crumb="My models">
    <div class="container mx-auto max-w-7xl px-4 py-8">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="font-tertiary text-2xl font-semibold text-neutral-900">{{ __('My models') }}</h1>
                <p class="mt-1 max-w-2xl text-sm text-tertiary">{{ __('The 3D models you are selling. Drafts are private; a reviewer checks each model before it goes live.') }}</p>
            </div>
            <x-btn href="{{ route('seller.models.create') }}" class="shrink-0"><x-icon name="plus" class="h-4 w-4" />{{ __('Add a model') }}</x-btn>
        </div>

        @if (($counts['all'] ?? 0) === 0)
            <x-empty-state icon="cube" :title="__('No models yet')" :description="__('Add your first model: pick a category, upload previews and files, and send it for review.')">
                <x-btn href="{{ route('seller.models.create') }}">{{ __('Add a model') }}</x-btn>
            </x-empty-state>
        @else
            <nav aria-label="{{ __('Filter by status') }}" class="-mx-4 mb-4 overflow-x-auto px-4 sm:mx-0 sm:px-0">
                <ul class="flex min-w-max items-center gap-2">
                    @foreach ($pills as $key => $label)
                        @continue($key !== 'all' && ($counts[$key] ?? 0) === 0 && $status !== $key)
                        @php $active = $status === $key; @endphp
                        <li>
                            <a href="{{ route('seller.models.index', array_filter(['status' => $key === 'all' ? null : $key, 'search' => $search ?: null])) }}" @if ($active) aria-current="true" @endif
                                @class([
                                    'inline-flex items-center gap-2 rounded-full border px-3.5 py-1.5 text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40',
                                    'border-teal-600 bg-teal-600 text-white' => $active,
                                    'border-neutral-200 bg-white text-neutral-700 hover:border-neutral-300 hover:bg-neutral-50' => ! $active,
                                ])>
                                {{ $label }}
                                <span @class(['text-xs tabular-nums', 'text-teal-100' => $active, 'text-tertiary' => ! $active])>{{ $counts[$key] ?? 0 }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            <form method="GET" action="{{ route('seller.models.index') }}" role="search" class="mb-6 flex max-w-xl gap-3">
                @if ($status !== 'all')<input type="hidden" name="status" value="{{ $status }}">@endif
                <div class="relative min-w-0 flex-1">
                    <x-icon name="magnifying-glass" class="pointer-events-none absolute left-3.5 top-2.5 h-5 w-5 text-neutral-400" />
                    <label for="search" class="sr-only">{{ __('Search your models') }}</label>
                    <input type="search" id="search" name="search" value="{{ $search }}" maxlength="100" placeholder="{{ __('Search your models…') }}" class="{{ $fieldClass }} pl-11">
                </div>
                <x-btn type="submit">{{ __('Search') }}</x-btn>
            </form>

            @if ($products->isEmpty())
                <x-empty-state icon="magnifying-glass" :title="__('No models match')" :description="__('Try a different status or search.')">
                    <x-btn variant="secondary" href="{{ route('seller.models.index') }}">{{ __('Show all models') }}</x-btn>
                </x-empty-state>
            @else
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($products as $product)
                        @php $cover = $product->images->first(); @endphp
                        <article class="group flex flex-col overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm transition-shadow hover:shadow-md">
                            <a href="{{ route('seller.models.edit', $product) }}" tabindex="-1" aria-hidden="true" class="relative block aspect-[4/3] bg-neutral-100">
                                @if ($cover)
                                    <img src="{{ $cover->url() }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                @else
                                    <span class="flex h-full w-full items-center justify-center text-neutral-300"><x-icon name="cube" class="h-12 w-12" /></span>
                                @endif
                                <x-badge :tone="$product->status->tone()" class="absolute left-3 top-3 px-2.5 py-0.5 text-xs font-medium shadow-sm">{{ __($product->status->label()) }}</x-badge>
                            </a>
                            <div class="flex flex-1 flex-col p-4">
                                <h2 class="font-tertiary text-base font-semibold leading-snug text-neutral-900">
                                    <a href="{{ route('seller.models.edit', $product) }}" class="rounded hover:text-teal-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">{{ $product->title }}</a>
                                </h2>
                                <p class="mt-1 text-xs text-tertiary">{{ $product->category?->path() ?? __('No category yet') }}</p>
                                @if ($product->status === ProductStatus::Rejected && $product->review_notes)
                                    <p class="mt-2 rounded-lg bg-red-50 px-3 py-2 text-xs text-red-800">{{ \Illuminate\Support\Str::limit($product->review_notes, 120) }}</p>
                                @endif
                                <div class="mt-auto flex items-center justify-between gap-3 border-t border-neutral-100 pt-4">
                                    <span class="font-tertiary text-base font-semibold tabular-nums text-neutral-900">
                                        @if ($product->isFree()) {{ __('Free') }} @else {{ \App\Support\Money::formatMinor($product->price_minor, 0) }} @endif
                                    </span>
                                    <x-btn size="sm" variant="secondary" href="{{ route('seller.models.edit', $product) }}">{{ $product->status->isEditable() ? __('Edit') : __('Open') }}</x-btn>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
                <x-pager :paginator="$products" />
            @endif
        @endif
    </div>
</x-app-layout>
