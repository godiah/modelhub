@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination') }}"
        class="flex items-center justify-between gap-3 border-t border-neutral-100 bg-neutral-50/60 px-5 py-3">
        <p class="text-xs text-tertiary">
            {{ __('Showing :from–:to of :total', ['from' => $paginator->firstItem(), 'to' => $paginator->lastItem(), 'total' => $paginator->total()]) }}
        </p>

        <div class="flex items-center gap-2">
            @if ($paginator->onFirstPage())
                <x-btn variant="secondary" size="sm" type="button" disabled>{{ __('Previous') }}</x-btn>
            @else
                <x-btn variant="secondary" size="sm" href="{{ $paginator->previousPageUrl() }}" wire:navigate rel="prev">{{ __('Previous') }}</x-btn>
            @endif

            <span class="px-1 text-xs tabular-nums text-tertiary">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>

            @if ($paginator->hasMorePages())
                <x-btn variant="secondary" size="sm" href="{{ $paginator->nextPageUrl() }}" wire:navigate rel="next">{{ __('Next') }}</x-btn>
            @else
                <x-btn variant="secondary" size="sm" type="button" disabled>{{ __('Next') }}</x-btn>
            @endif
        </div>
    </nav>
@endif
