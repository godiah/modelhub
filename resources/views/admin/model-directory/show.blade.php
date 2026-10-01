@php
    $canReview = auth()->user()->can('review models');
    $images = $product->images->map(fn ($image) => $image->url())->values();
    $files = $product->files;
    $size = fn ($bytes) => $bytes >= 1048576 ? number_format($bytes / 1048576, 1).' MB' : number_format($bytes / 1024, 0).' KB';
@endphp
<x-staff-layout :title="$product->title">
    <div class="container mx-auto max-w-6xl space-y-6 px-4 py-8">
        <a href="{{ route('admin.catalogue.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-teal-700 hover:underline"><x-icon name="arrow-left" class="h-4 w-4" />{{ __('All models') }}</a>

        <x-card class="rounded-2xl">
            <div class="flex flex-wrap items-start justify-between gap-4 p-6">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-3"><h1 class="font-tertiary text-2xl font-semibold text-neutral-900">{{ $product->title }}</h1><x-badge :tone="$product->status->tone()" class="px-2.5 py-0.5 text-xs font-medium">{{ __($product->status->label()) }}</x-badge></div>
                    <p class="mt-1.5 text-sm text-tertiary">{{ $product->category?->path() ?? __('No category') }} · {{ $product->isFree() ? __('Free') : \App\Support\Money::formatMinor($product->price_minor) }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    @if ($product->status === \App\Enums\ProductStatus::Published)<x-btn variant="secondary" href="{{ route('models.show', $product) }}" target="_blank">{{ __('Open public page') }}</x-btn>@elseif ($canReview)<x-btn variant="secondary" href="{{ route('models.show', $product) }}" target="_blank">{{ __('Preview') }}</x-btn>@endif
                    @if ($canReview)<x-btn variant="secondary" href="{{ route('admin.models.index', ['status' => $product->status->value]) }}">{{ __('Open in the review queue') }}</x-btn>@endif
                </div>
            </div>
        </x-card>

        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
            <div class="space-y-6">
                @if ($images->isNotEmpty())
                    <x-panel :title="__('Previews')"><ul class="grid grid-cols-2 gap-2 sm:grid-cols-4">@foreach ($images as $url)<li><a href="{{ $url }}" target="_blank" rel="noopener" class="block overflow-hidden rounded-lg bg-neutral-100 ring-1 ring-black/5"><img src="{{ $url }}" alt="" loading="lazy" class="aspect-square w-full object-cover"></a></li>@endforeach</ul></x-panel>
                @endif

                <x-panel :title="__('Description')">@if (filled($product->description))<x-jobs.markdown-description :content="$product->description" />@else<p class="text-sm text-tertiary">{{ __('No description.') }}</p>@endif
                    @if ($product->tags)<ul class="mt-4 flex flex-wrap gap-2 border-t border-neutral-100 pt-4">@foreach ($product->tags as $tag)<li class="rounded-full bg-neutral-100 px-3 py-1 text-xs text-neutral-700">{{ $tag }}</li>@endforeach</ul>@endif</x-panel>

                <x-panel :title="trans_choice(':count file|:count files', $files->count(), ['count' => $files->count()])" :description="__('Names and sizes only. Reviewers open the files from the review queue.')">
                    @forelse ($files as $file)<p class="flex items-center justify-between gap-3 border-b border-neutral-100 py-2 text-sm last:border-0"><span class="min-w-0 truncate text-neutral-900">{{ $file->original_name }}</span><span class="shrink-0 text-xs capitalize text-tertiary">{{ $file->kind }} · {{ $size($file->size_bytes) }}</span></p>@empty<p class="text-sm text-tertiary">{{ __('No files uploaded.') }}</p>@endforelse
                </x-panel>

                <x-panel :title="__('Reviews (:count)', ['count' => $counts['reviews']])" :description="$counts['hidden'] ? trans_choice(':count is hidden|:count are hidden', $counts['hidden'], ['count' => $counts['hidden']]) : null">
                    @forelse ($reviews as $review)
                        <div class="border-b border-neutral-100 py-3 text-sm last:border-0">
                            <p class="flex flex-wrap items-center gap-2"><x-models.stars :rating="$review->rating" size="h-3.5 w-3.5" /><span class="font-medium text-neutral-900">{{ $review->author->publicName() }}</span><span class="text-xs text-tertiary">{{ $review->created_at->format('M j, Y') }}</span>@if ($review->status === 'hidden')<x-badge tone="red" class="px-2 py-0.5 text-xs font-medium">{{ __('Hidden') }}</x-badge>@endif</p>
                            <p class="mt-1 line-clamp-3 text-neutral-700">{{ $review->comment }}</p>
                        </div>
                    @empty<p class="text-sm text-tertiary">{{ __('No reviews yet.') }}</p>@endforelse
                </x-panel>
            </div>

            <div class="space-y-6">
                <x-panel :title="__('Numbers')">
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between gap-4"><dt class="text-tertiary">{{ __('Rating') }}</dt><dd class="text-neutral-900">@if ($product->rating_count){{ number_format($product->rating_avg, 1) }} ★ ({{ $product->rating_count }})@else—@endif</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-tertiary">{{ __('Saved by') }}</dt><dd class="tabular-nums text-neutral-900">{{ $counts['saves'] }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-tertiary">{{ __('Sales') }}</dt><dd class="tabular-nums text-neutral-900">{{ $counts['purchases'] }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-tertiary">{{ __('Polygons') }}</dt><dd class="tabular-nums text-neutral-900">{{ $product->polygons ? number_format($product->polygons) : '—' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-tertiary">{{ __('Model #') }}</dt><dd class="tabular-nums text-neutral-900">{{ $product->id }}</dd></div>
                    </dl>
                </x-panel>
                <x-panel :title="__('Seller')">
                    <div class="flex items-center gap-3">@if ($product->sellerProfile)<x-store-avatar :store="$product->sellerProfile" size="h-10 w-10" />@endif
                        <div class="min-w-0"><p class="truncate text-sm font-semibold text-neutral-900">{{ $product->sellerProfile?->display_name ?? $product->seller?->name }}</p>
                            <p class="text-xs"><span class="text-tertiary">{{ $product->seller?->name }}</span>@if ($product->sellerProfile)@can('view sellers') · <a href="{{ route('admin.stores.show', $product->sellerProfile) }}" class="font-medium text-teal-700 hover:underline">{{ __('Store') }}</a>@endcan @endif @if ($product->seller)@can('view members') · <a href="{{ route('admin.members.show', $product->seller) }}" class="font-medium text-teal-700 hover:underline">{{ __('Member') }}</a>@endcan @endif</p></div></div>
                </x-panel>
                <x-panel :title="__('Review history')">
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between gap-4"><dt class="text-tertiary">{{ __('Submitted') }}</dt><dd class="text-neutral-900">{{ $product->submitted_at?->format('M j, Y') ?? '—' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-tertiary">{{ __('Published') }}</dt><dd class="text-neutral-900">{{ $product->published_at?->format('M j, Y') ?? '—' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-tertiary">{{ __('Last decision') }}</dt><dd class="text-right text-neutral-900">{{ $product->reviewed_at ? $product->reviewed_at->format('M j, Y').' · '.($product->reviewer?->name ?? __('a reviewer')) : '—' }}</dd></div>
                    </dl>
                    @if (filled($product->review_notes))<p class="mt-3 rounded-xl bg-neutral-50 px-3 py-2 text-sm text-neutral-700">{{ $product->review_notes }}</p>@endif
                </x-panel>
            </div>
        </div>
    </div>
</x-staff-layout>
