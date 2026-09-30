@props(['paginator', 'footer' => false, 'navigate' => false, 'ajax' => false])

{{--
    The one pagination design: "Showing a–b of n" on the left, Previous / page / Next on the right. It is what
    `->links()` renders everywhere (see AppServiceProvider), so never hand-roll another.
      footer   — sits flush at the bottom of a card, with its own border and tint
      navigate — Livewire wire:navigate links (pages inside the signed-in shell that swap instantly)
      ajax     — marks the links `data-page-link` so a page's own script can load them without a reload
--}}
@if ($paginator->hasPages())
    @php
        $linkAttributes = fn (string $rel) => new \Illuminate\View\ComponentAttributeBag(array_filter([
            'rel' => $rel,
            'wire:navigate' => $navigate ? true : null,
            'data-page-link' => $ajax ? true : null,
        ]));
        $textSize = $footer ? 'text-xs' : 'text-sm';
    @endphp
    <nav role="navigation" aria-label="{{ __('Pagination') }}" @class([
        'flex items-center justify-between gap-3',
        'mt-6' => ! $footer,
        'border-t border-neutral-100 bg-neutral-50/60 px-5 py-3' => $footer,
    ])>
        <p class="{{ $textSize }} text-tertiary">{{ __('Showing :from–:to of :total', ['from' => $paginator->firstItem(), 'to' => $paginator->lastItem(), 'total' => $paginator->total()]) }}</p>
        <div class="flex items-center gap-2">
            @if ($paginator->onFirstPage())
                <x-btn variant="secondary" size="sm" type="button" disabled>{{ __('Previous') }}</x-btn>
            @else
                <x-btn variant="secondary" size="sm" href="{{ $paginator->previousPageUrl() }}" :attributes="$linkAttributes('prev')">{{ __('Previous') }}</x-btn>
            @endif
            <span class="px-1 {{ $textSize }} tabular-nums text-tertiary">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>
            @if ($paginator->hasMorePages())
                <x-btn variant="secondary" size="sm" href="{{ $paginator->nextPageUrl() }}" :attributes="$linkAttributes('next')">{{ __('Next') }}</x-btn>
            @else
                <x-btn variant="secondary" size="sm" type="button" disabled>{{ __('Next') }}</x-btn>
            @endif
        </div>
    </nav>
@endif
