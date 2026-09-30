{{--
    Browse results: swapped wholesale by the filter rail's AJAX call (and rendered inline on first load), so it
    carries no Alpine of its own. `data-jobs-grid` lets the page flip grid/list; `data-page-link` and
    `data-clear-filters` are picked up by the page's delegated click handler.
--}}
@if ($jobs->isEmpty())
    <x-empty-state icon="magnifying-glass" :title="__('No projects match your filters')"
        :description="__('Try removing a filter or searching for something broader. New projects are posted every day.')">
        <x-btn variant="secondary" type="button" data-clear-filters>
            <x-icon name="arrow-path" class="h-4 w-4" />
            {{ __('Clear all filters') }}
        </x-btn>
    </x-empty-state>
@else
    <p class="sr-only" role="status" data-results-count="{{ $jobs->total() }}">
        {{ trans_choice(':count project found|:count projects found', $jobs->total(), ['count' => $jobs->total()]) }}
    </p>

    <div data-jobs-grid class="grid grid-cols-1 gap-4 md:grid-cols-2">
        @foreach ($jobs as $job)
            <x-jobs.card :job="$job" :status="$statuses[$job->id] ?? null" />
        @endforeach
    </div>

    @if ($jobs->hasPages())
        <nav role="navigation" aria-label="{{ __('Pagination') }}" class="mt-6 flex items-center justify-between gap-3">
            <p class="text-sm text-tertiary">
                {{ __('Showing :from–:to of :total', ['from' => $jobs->firstItem(), 'to' => $jobs->lastItem(), 'total' => $jobs->total()]) }}
            </p>

            <div class="flex items-center gap-2">
                @if ($jobs->onFirstPage())
                    <x-btn variant="secondary" size="sm" type="button" disabled>{{ __('Previous') }}</x-btn>
                @else
                    <x-btn variant="secondary" size="sm" href="{{ $jobs->previousPageUrl() }}" rel="prev" data-page-link>{{ __('Previous') }}</x-btn>
                @endif

                <span class="px-1 text-sm tabular-nums text-tertiary">{{ $jobs->currentPage() }} / {{ $jobs->lastPage() }}</span>

                @if ($jobs->hasMorePages())
                    <x-btn variant="secondary" size="sm" href="{{ $jobs->nextPageUrl() }}" rel="next" data-page-link>{{ __('Next') }}</x-btn>
                @else
                    <x-btn variant="secondary" size="sm" type="button" disabled>{{ __('Next') }}</x-btn>
                @endif
            </div>
        </nav>
    @endif
@endif
