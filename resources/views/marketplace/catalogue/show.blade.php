@use('App\Enums\ProductStatus')
@php
    $images = $product->images->map(fn ($image) => $image->url())->values();
    $byKind = fn (array $kinds) => $product->files->whereIn('kind', $kinds)->groupBy('extension');
    $native = $byKind(['native']);
    $exchange = $byKind(['exchange']);
    $textures = $byKind(['texture']);
    $totalBytes = $product->files->sum('size_bytes');
    $totalSize = $totalBytes >= 1048576 ? number_format($totalBytes / 1048576, 1).' MB' : number_format($totalBytes / 1024, 0).' KB';
    $features = $product->feature_labels();
    $isOwner = auth()->id() === $product->user_id;
@endphp
<x-app-layout :title="$product->title" :crumb="$product->title">
    <div class="container mx-auto max-w-7xl px-4 py-8">
        @if ($preview)
            <div class="mb-6 flex items-center gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-3 text-sm text-amber-900" role="status">
                <x-icon name="eye" class="h-5 w-5 shrink-0 text-amber-600" />
                <p>{{ __('Preview: this model is :status, so buyers cannot see this page yet.', ['status' => strtolower(__($product->status->label()))]) }}
                    @if ($isOwner)<a href="{{ route('seller.models.edit', $product) }}" class="font-medium underline">{{ __('Back to editing') }}</a>@endif</p>
            </div>
        @endif

        <nav aria-label="{{ __('Breadcrumb') }}" class="mb-4 text-sm text-tertiary">
            <a href="{{ route('models.index') }}" class="hover:text-teal-700">{{ __('3D models') }}</a>
            @if ($product->category?->parent)<span aria-hidden="true"> / </span><a href="{{ route('models.index', ['category' => $product->category->parent->slug]) }}" class="hover:text-teal-700">{{ $product->category->parent->name }}</a>@endif
            @if ($product->category)<span aria-hidden="true"> / </span><a href="{{ route('models.index', ['category' => $product->category->slug]) }}" class="hover:text-teal-700">{{ $product->category->name }}</a>@endif
        </nav>

        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[minmax(0,1fr)_24rem]">
            <div class="min-w-0 space-y-6">
                <!-- Gallery -->
                <x-card class="rounded-2xl p-3 sm:p-4">
                  <div x-data="{ current: 0, images: @js($images) }">
                    <div class="flex aspect-[4/3] items-center justify-center overflow-hidden rounded-xl bg-white ring-1 ring-neutral-100">
                        @if ($images->isNotEmpty())
                            <img :src="images[current]" alt="{{ $product->title }}" class="h-full w-full object-contain">
                        @else
                            <x-icon name="cube" class="h-16 w-16 text-neutral-300" />
                        @endif
                    </div>
                    @if ($images->count() > 1)
                        <ul class="mt-3 flex gap-2 overflow-x-auto pb-1">
                            @foreach ($images as $index => $url)
                                <li class="shrink-0">
                                    <button type="button" @click="current = {{ $index }}" :aria-current="current === {{ $index }} ? 'true' : null" aria-label="{{ __('Show image :number', ['number' => $index + 1]) }}"
                                        :class="current === {{ $index }} ? 'ring-2 ring-teal-600' : 'ring-1 ring-neutral-200 hover:ring-neutral-300'" class="block overflow-hidden rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/60">
                                        <img src="{{ $url }}" alt="" loading="lazy" class="h-16 w-20 object-cover">
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                  </div>
                </x-card>

                <x-panel :title="__('Description')">
                    @if (filled($product->description))
                        <x-jobs.markdown-description :content="$product->description" />
                    @else
                        <p class="text-sm text-tertiary">{{ __('The seller has not written a description yet.') }}</p>
                    @endif
                    @if ($product->tags)
                        <ul class="mt-6 flex flex-wrap gap-2 border-t border-neutral-100 pt-5" aria-label="{{ __('Tags') }}">
                            @foreach ($product->tags as $tag)
                                <li><a href="{{ route('models.index', ['q' => $tag]) }}" class="inline-block rounded-full bg-neutral-100 px-3 py-1 text-xs text-neutral-700 hover:bg-teal-50 hover:text-teal-800">{{ $tag }}</a></li>
                            @endforeach
                        </ul>
                    @endif
                </x-panel>
            </div>

            <aside class="space-y-6 lg:sticky lg:top-24">
                <x-panel>
                    <h1 class="font-tertiary text-xl font-semibold leading-snug text-neutral-900">{{ $product->title }}</h1>
                    <p class="mt-1 text-sm text-tertiary">{{ __('by :seller', ['seller' => $product->sellerProfile?->display_name ?? __('a ModelHub seller')]) }}</p>
                    <p class="mt-4 font-tertiary text-3xl font-bold tabular-nums text-neutral-900">{{ $product->isFree() ? __('Free') : \App\Support\Money::formatMinor($product->price_minor) }}</p>
                    <p class="mt-1 text-xs text-tertiary">{{ __('Standard licence') }}</p>

                    @if (config('marketplace.purchases_enabled'))
                        <x-btn block size="lg" class="mt-5" type="button" disabled>{{ $product->isFree() ? __('Download') : __('Add to cart') }}</x-btn>
                    @else
                        <x-btn block size="lg" class="mt-5" type="button" disabled>{{ __('Purchases open soon') }}</x-btn>
                        <p class="mt-2 text-center text-xs text-tertiary">{{ __('Checkout is not open yet. Models are visible so sellers can see how their listings look.') }}</p>
                    @endif
                </x-panel>

                <x-panel :title="__('Files')">
                    <dl class="space-y-4 text-sm">
                        @foreach ([__('Native formats') => $native, __('Exchange formats') => $exchange, __('Textures') => $textures] as $label => $group)
                            @if ($group->isNotEmpty())
                                <div>
                                    <dt class="text-xs text-tertiary">{{ $label }}</dt>
                                    <dd class="mt-1.5 flex flex-wrap gap-1.5">
                                        @foreach ($group as $extension => $files)
                                            <span class="rounded-md bg-neutral-100 px-2 py-1 font-mono text-xs font-semibold uppercase text-neutral-700">{{ $extension }}@if ($files->count() > 1) <span class="font-sans font-normal text-tertiary">× {{ $files->count() }}</span>@endif</span>
                                        @endforeach
                                    </dd>
                                </div>
                            @endif
                        @endforeach
                        <div class="flex justify-between border-t border-neutral-100 pt-3"><dt class="text-tertiary">{{ __('Total size') }}</dt><dd class="font-medium tabular-nums text-neutral-900">{{ $totalSize }}</dd></div>
                    </dl>
                </x-panel>

                <x-panel :title="__('Details')">
                    @if ($features)
                        <ul class="mb-5 flex flex-wrap gap-1.5">
                            @foreach ($features as $label)<li class="rounded-full border border-teal-100 bg-teal-50 px-2.5 py-0.5 text-xs font-medium text-teal-800">{{ __($label) }}</li>@endforeach
                        </ul>
                    @endif
                    <dl class="space-y-2.5 text-sm">
                        @if ($product->geometry_type)<div class="flex justify-between gap-4"><dt class="text-tertiary">{{ __('Geometry') }}</dt><dd class="text-right text-neutral-900">{{ $product->geometry_type->label() }}</dd></div>@endif
                        @if ($product->polygons)<div class="flex justify-between gap-4"><dt class="text-tertiary">{{ __('Polygons') }}</dt><dd class="text-right tabular-nums text-neutral-900">{{ number_format($product->polygons) }}</dd></div>@endif
                        @if ($product->vertices)<div class="flex justify-between gap-4"><dt class="text-tertiary">{{ __('Vertices') }}</dt><dd class="text-right tabular-nums text-neutral-900">{{ number_format($product->vertices) }}</dd></div>@endif
                        @if ($product->uv_layout)<div class="flex justify-between gap-4"><dt class="text-tertiary">{{ __('Unwrapped UVs') }}</dt><dd class="text-right text-neutral-900">{{ $product->uv_layout->label() }}</dd></div>@endif
                        @if ($product->render_engine)<div class="flex justify-between gap-4"><dt class="text-tertiary">{{ __('Render engine') }}</dt><dd class="text-right text-neutral-900">{{ $product->render_engine }}</dd></div>@endif
                        @if ($product->software->isNotEmpty())<div class="flex justify-between gap-4"><dt class="text-tertiary">{{ __('Software') }}</dt><dd class="text-right text-neutral-900">{{ $product->software->pluck('name')->implode(', ') }}</dd></div>@endif
                        @if ($product->published_at)<div class="flex justify-between gap-4"><dt class="text-tertiary">{{ __('Published') }}</dt><dd class="text-right text-neutral-900">{{ $product->published_at->format('M j, Y') }}</dd></div>@endif
                        <div class="flex justify-between gap-4"><dt class="text-tertiary">{{ __('Model ID') }}</dt><dd class="text-right tabular-nums text-neutral-900">#{{ $product->id }}</dd></div>
                    </dl>
                </x-panel>
            </aside>
        </div>

        @if ($related->isNotEmpty())
            <section class="mt-12 border-t border-neutral-200 pt-8" aria-labelledby="related-models">
                <h2 id="related-models" class="mb-4 font-tertiary text-lg font-semibold text-neutral-900">{{ __('More in this category') }}</h2>
                <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                    @foreach ($related as $item)<x-models.card :product="$item" />@endforeach
                </div>
            </section>
        @endif
    </div>
</x-app-layout>
