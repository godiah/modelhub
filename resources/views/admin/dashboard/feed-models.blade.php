<section aria-labelledby="feed-models">
    <x-card clip>
        <div class="flex items-center justify-between gap-3 border-b border-neutral-100 px-5 py-4"><h2 id="feed-models" class="font-tertiary text-base font-semibold text-neutral-900">{{ __('Models') }}</h2><a href="{{ route('admin.catalogue.index') }}" class="text-sm font-medium text-teal-700 hover:text-teal-800">{{ __('Open directory') }}</a></div>
        <div class="p-5">
            <h3 class="text-xs font-semibold uppercase tracking-wider text-neutral-400">{{ __('Newly published') }}</h3>
            <ul class="mt-2 divide-y divide-neutral-100">
                @forelse ($feed['published'] as $model)
                    @php $cover = $model->images->first(); @endphp
                    <li><a href="{{ route('admin.catalogue.show', $model) }}" class="flex items-center gap-3 py-2.5 text-sm hover:text-teal-700"><span class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-neutral-100">@if ($cover)<img src="{{ $cover->url() }}" alt="" loading="lazy" class="h-full w-full object-cover">@else<x-icon name="cube" class="h-4 w-4 text-neutral-300" />@endif</span><span class="min-w-0 flex-1"><span class="block truncate font-medium text-neutral-900">{{ $model->title }}</span><span class="block truncate text-xs text-tertiary">{{ $model->sellerProfile?->display_name }}</span></span><span class="shrink-0 text-xs text-tertiary">{{ $model->published_at?->diffForHumans() }}</span></a></li>
                @empty<li class="py-4 text-sm text-tertiary">{{ __('Nothing published yet.') }}</li>@endforelse
            </ul>
            @if ($feed['saved']->isNotEmpty())
                <h3 class="mt-5 text-xs font-semibold uppercase tracking-wider text-neutral-400">{{ __('Most saved this week') }}</h3>
                <ul class="mt-2 divide-y divide-neutral-100">
                    @foreach ($feed['saved'] as $model)
                        <li><a href="{{ route('admin.catalogue.show', $model) }}" class="flex items-center justify-between gap-3 py-2.5 text-sm hover:text-teal-700"><span class="min-w-0 truncate font-medium text-neutral-900">{{ $model->title }}</span><span class="shrink-0 text-xs tabular-nums text-tertiary">{{ trans_choice(':count save|:count saves', $model->week_saves, ['count' => $model->week_saves]) }}</span></a></li>
                    @endforeach
                </ul>
            @endif
        </div>
    </x-card>
</section>
