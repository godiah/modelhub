@props(['tabs' => [], 'search' => null, 'placeholder' => null, 'chips' => [], 'action' => null])

{{--
    The filter bar above a staff list: status tabs with counts, a search box, anything extra in the slot (selects), and the filters
    currently applied as removable chips with a clear-all. Filters are plain GET parameters, so every state is linkable.
      tabs  — list of ['label', 'url', 'count', 'on']
      chips — list of ['label' => 'Search: kev', 'remove' => ['q']]  (query keys the chip clears)
--}}
@php
    $action ??= url()->current();
    $keep = collect(request()->query())->except(['q', 'page'])->filter(fn ($v) => is_scalar($v) && $v !== '');
    $chips = collect($chips)->filter();
@endphp
<div class="mb-5 space-y-4">
    @if ($tabs)
        <nav aria-label="{{ __('Filter by status') }}" class="-mx-4 overflow-x-auto px-4 sm:mx-0 sm:px-0">
            <ul class="flex min-w-max items-center gap-2">
                @foreach ($tabs as $tab)
                    <li><a href="{{ $tab['url'] }}" wire:navigate @if ($tab['on']) aria-current="true" @endif
                        @class(['inline-flex items-center gap-2 rounded-full border px-3.5 py-1.5 text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40', 'border-teal-600 bg-teal-600 text-white' => $tab['on'], 'border-neutral-200 bg-white text-neutral-700 hover:border-neutral-300 hover:bg-neutral-50' => ! $tab['on']])>
                        {{ __($tab['label']) }}@isset($tab['count'])<span @class(['text-xs tabular-nums', 'text-teal-100' => $tab['on'], 'text-tertiary' => ! $tab['on']])>{{ number_format($tab['count']) }}</span>@endisset</a></li>
                @endforeach
            </ul>
        </nav>
    @endif

    @if ($search !== null)
        <form method="GET" action="{{ $action }}" role="search" class="flex flex-wrap items-center gap-3">
            @foreach ($keep as $key => $value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach
            <div class="relative min-w-[14rem] flex-1 sm:max-w-md">
                <x-icon name="magnifying-glass" class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-neutral-400" />
                <label for="list-search" class="sr-only">{{ $placeholder ?? __('Search') }}</label>
                <input id="list-search" type="search" name="q" value="{{ $search }}" placeholder="{{ $placeholder ?? __('Search') }}" class="block w-full rounded-xl border border-neutral-300 bg-white py-2 pl-9 pr-3 text-sm focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25">
            </div>
            {{ $slot }}
            <x-btn type="submit" variant="secondary">{{ __('Search') }}</x-btn>
        </form>
    @endif

    @if ($chips->isNotEmpty())
        <div class="flex flex-wrap items-center gap-2" aria-label="{{ __('Active filters') }}">
            @foreach ($chips as $chip)
                <a href="{{ request()->fullUrlWithoutQuery([...$chip['remove'], 'page']) }}" wire:navigate class="inline-flex items-center gap-1.5 rounded-full bg-neutral-200/70 py-1 pl-3 pr-2 text-xs font-medium text-neutral-800 transition-colors hover:bg-neutral-300/70 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">
                    {{ $chip['label'] }}<x-icon name="x-mark" class="h-3.5 w-3.5 text-neutral-500" /><span class="sr-only">{{ __('Remove filter') }}</span>
                </a>
            @endforeach
            <a href="{{ request()->fullUrlWithoutQuery([...$chips->pluck('remove')->flatten()->all(), 'page']) }}" wire:navigate class="text-xs font-medium text-teal-700 hover:text-teal-800">{{ __('Clear all') }}</a>
        </div>
    @endif
</div>
