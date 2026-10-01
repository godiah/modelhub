@props(['store', 'size' => 'h-10 w-10', 'rounded' => 'rounded-xl'])

{{-- A store's avatar picture (the seller picks one; new stores get a random one). Decorative: the store name is shown beside it. --}}
<img src="{{ $store->avatarUrl() }}" alt="" loading="lazy"
    {{ $attributes->class([$size, $rounded, 'shrink-0 border border-neutral-200 bg-white object-cover']) }}>
