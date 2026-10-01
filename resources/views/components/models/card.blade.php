@props(['product', 'saved' => false])

{{--
    One model in the catalogue: cover, file-format tags, price, title, feature tags and the seller's store name.
    Needs images, sellerProfile and files loaded. `saved` fills the wishlist heart.
--}}
@php
    $cover = $product->images->first();
    $allFormats = $product->files->whereIn('kind', ['native', 'exchange'])->pluck('extension')->unique()->values();
    $formats = $allFormats->take(2);
    $moreFormats = $allFormats->count() - $formats->count();
    $features = collect(\App\Models\Product::FEATURES)->only(['is_pbr', 'is_rigged', 'is_animated', 'is_low_poly', 'is_print_ready'])->filter(fn ($label, $field) => $product->{$field});
@endphp
<article {{ $attributes->class('group flex flex-col overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm transition-shadow duration-200 hover:shadow-md') }}>
    <div class="relative">
    <a href="{{ route('models.show', $product) }}" class="relative block aspect-square overflow-hidden bg-neutral-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40" tabindex="-1" aria-hidden="true">
        @if ($cover)
            <img src="{{ $cover->url() }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.03]">
        @else
            <span class="flex h-full w-full items-center justify-center text-neutral-300"><x-icon name="cube" class="h-12 w-12" /></span>
        @endif
        @if ($formats->isNotEmpty())
            <span class="absolute left-2.5 top-2.5 flex gap-1">
                @foreach ($formats as $format)
                    <span class="rounded bg-neutral-900/70 px-1.5 py-0.5 font-mono text-[10px] font-semibold uppercase tracking-wide text-white">{{ $format }}</span>
                @endforeach
                @if ($moreFormats > 0)
                    <span class="rounded bg-neutral-900/70 px-1.5 py-0.5 text-[10px] font-semibold text-white">+{{ $moreFormats }}</span>
                @endif
            </span>
        @endif
        <span class="absolute right-2.5 top-2.5 rounded-lg bg-white px-2.5 py-1 text-sm font-semibold tabular-nums text-neutral-900 shadow">
            {{ $product->isFree() ? __('Free') : \App\Support\Money::formatMinor($product->price_minor, 0) }}
        </span>
    </a>
        <x-models.wishlist-button :product="$product" :saved="$saved" class="absolute bottom-2.5 right-2.5 z-10" />
    </div>
    <div class="flex flex-1 flex-col p-4">
        <h3 class="font-tertiary text-sm font-semibold leading-snug text-neutral-900">
            <a href="{{ route('models.show', $product) }}" class="rounded transition-colors hover:text-teal-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">{{ $product->title }}</a>
        </h3>
        @if ($features->isNotEmpty())
            <ul class="mt-2 flex flex-wrap gap-1.5">
                @foreach ($features as $label)
                    <li class="rounded-full border border-teal-100 bg-teal-50 px-2 py-0.5 text-[11px] font-medium text-teal-800">{{ __($label) }}</li>
                @endforeach
            </ul>
        @endif
        <p class="mt-auto pt-3 text-xs text-tertiary">
            @if ($product->sellerProfile?->slug)
                <a href="{{ route('sellers.show', $product->sellerProfile->slug) }}" class="rounded hover:text-teal-700 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">{{ $product->sellerProfile->display_name }}</a>
            @else
                {{ __('ModelHub seller') }}
            @endif
        </p>
    </div>
</article>
