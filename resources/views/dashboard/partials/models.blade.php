<section aria-labelledby="models-heading">
    <x-card clip>
        <div class="flex items-center justify-between gap-3 border-b border-neutral-100 px-5 py-4">
            <h3 id="models-heading" class="font-tertiary text-base font-semibold text-neutral-900">{{ __('My models') }}</h3>
            <a href="{{ route('seller.models.index') }}" wire:navigate
                class="text-sm font-medium text-teal-700 hover:text-teal-800">{{ __('View all') }}</a>
        </div>

        @if ($models->isEmpty())
            <div class="flex flex-col items-center px-6 py-10 text-center">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-neutral-100 text-neutral-600">
                    <x-icon name="cube" class="h-6 w-6" />
                </span>
                <p class="mt-4 font-semibold text-neutral-900">{{ __('No models listed yet') }}</p>
                <p class="mt-1 max-w-sm text-sm text-tertiary">{{ __('Add your first model: previews, files and details, then send it for review.') }}</p>
                <div class="mt-5">
                    <x-btn href="{{ route('seller.models.create') }}" class="text-sm shadow-sm">
                        <x-icon name="plus" class="h-4 w-4" />
                        {{ __('Add a model') }}
                    </x-btn>
                </div>
            </div>
        @else
            <ul class="divide-y divide-neutral-100">
                @foreach ($models as $model)
                    @php $cover = $model->images->first(); @endphp
                    <li>
                        <a href="{{ $model->status === \App\Enums\ProductStatus::Published ? route('models.show', $model) : route('seller.models.edit', $model) }}" wire:navigate
                            class="flex items-center gap-4 px-5 py-3.5 transition-colors hover:bg-neutral-50 focus:outline-none focus-visible:bg-neutral-50 focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-secondary/40">
                            <span class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-neutral-100 ring-1 ring-black/5">
                                @if ($cover)
                                    <img src="{{ $cover->url() }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                @else
                                    <x-icon name="cube" class="h-6 w-6 text-neutral-300" />
                                @endif
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                    <span class="truncate text-sm font-semibold text-neutral-900">{{ $model->title }}</span>
                                    <x-badge :tone="$model->status->tone()" class="px-2 py-0.5 text-xs font-medium">{{ __($model->status->label()) }}</x-badge>
                                </span>
                                <span class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-tertiary">
                                    <span class="tabular-nums">{{ $model->isFree() ? __('Free') : \App\Support\Money::formatMinor($model->price_minor, 0) }}</span>
                                    @if ($model->rating_count > 0)
                                        <x-models.stars :rating="$model->rating_avg" :count="$model->rating_count" size="h-3.5 w-3.5" />
                                    @elseif ($model->status === \App\Enums\ProductStatus::Published)
                                        <span>{{ __('No reviews yet') }}</span>
                                    @endif
                                    @if ($model->wishlist_items_count > 0)
                                        <span class="inline-flex items-center gap-1" title="{{ __('Members who saved this model') }}"><x-icon name="heart" class="h-3.5 w-3.5" />{{ $model->wishlist_items_count }}</span>
                                    @endif
                                </span>
                            </span>
                            <x-icon name="chevron-right" class="h-4 w-4 shrink-0 text-neutral-300" />
                        </a>
                    </li>
                @endforeach
            </ul>
            <div class="border-t border-neutral-100 px-5 py-3">
                <a href="{{ route('sellers.show', $store->slug) }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-teal-700 hover:text-teal-800">
                    {{ __('View your public storefront') }}<x-icon name="arrow-top-right-on-square" class="h-4 w-4" />
                </a>
            </div>
        @endif
    </x-card>
</section>
