@props(['product', 'saved' => false, 'big' => false])

{{--
    One model as a gallery tile: the render fills a square (or a 2x2 block with `big`), and the title, store, rating
    and price appear over it on hover or keyboard focus (always on small screens, where there is no hover). The whole
    tile opens the model; the heart saves it. Needs images, sellerProfile and files loaded.
--}}
@php $cover = $product->images->first(); @endphp
<article {{ $attributes->class(['group relative overflow-hidden rounded-xl bg-neutral-100 ring-1 ring-black/5', 'col-span-2 row-span-2' => $big, 'aspect-square' => ! $big]) }}>
    @if ($cover)
        <img src="{{ $cover->url() }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.04]">
    @else
        <span class="absolute inset-0 flex items-center justify-center text-neutral-300"><x-icon name="cube" class="h-12 w-12" /></span>
    @endif

    <a href="{{ route('models.show', $product) }}" class="absolute inset-0 z-10 rounded-xl focus:outline-none focus-visible:ring-4 focus-visible:ring-inset focus-visible:ring-secondary/70"><span class="sr-only">{{ $product->title }}</span></a>

    <div class="pointer-events-none absolute inset-x-0 bottom-0 z-20 bg-gradient-to-t from-neutral-950/80 via-neutral-950/40 to-transparent px-3 pb-3 pt-10 text-white opacity-100 transition-opacity duration-200 sm:opacity-0 sm:group-focus-within:opacity-100 sm:group-hover:opacity-100">
        <p @class(['font-tertiary font-semibold leading-snug', 'line-clamp-2 text-sm' => ! $big, 'line-clamp-2 text-lg' => $big])>{{ $product->title }}</p>
        <p class="mt-0.5 flex items-center justify-between gap-2 text-xs text-white/85">
            <span class="min-w-0 truncate">{{ $product->sellerProfile?->display_name ?? __('ModelHub seller') }}</span>
            <span class="shrink-0 font-semibold tabular-nums text-white">{{ $product->isFree() ? __('Free') : \App\Support\Money::formatMinor($product->price_minor, 0) }}</span>
        </p>
        @if ($product->rating_count > 0)
            <p class="mt-1 flex items-center gap-1 text-xs text-white/85"><x-icon name="star-solid" class="h-3.5 w-3.5 text-amber-400" /><span class="tabular-nums">{{ number_format($product->rating_avg, 1) }}</span><span class="text-white/70">({{ $product->rating_count }})</span></p>
        @endif
    </div>

    <x-models.wishlist-button :product="$product" :saved="$saved" :class="\Illuminate\Support\Arr::toCssClasses(['absolute right-2.5 top-2.5 z-30', 'sm:opacity-0 sm:transition-opacity sm:group-focus-within:opacity-100 sm:group-hover:opacity-100' => ! $saved])" />
</article>
