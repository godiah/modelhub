<x-app-layout>
    @php
        // Tab key = the value the request validates (Pending is `employer_accepted`).
        $tabs = [
            'all' => __('All'),
            'active' => __('Active'),
            'employer_accepted' => __('Pending'),
            'completed' => __('Completed'),
            'cancelled' => __('Withdrawn'),
        ];
        // Less common states only get a tab when there is something in them (or it is the current filter).
        foreach (['disputed' => __('Disputed'), 'settled' => __('Settled')] as $key => $label) {
            if (($statusCounts[$key] ?? 0) > 0 || $activeStatus === $key) {
                $tabs[$key] = $label;
            }
        }
        $tabUrl = fn (string $key) => route('engagements.index', array_filter([
            'status' => $key === 'all' ? null : $key,
            'search' => request('search'),
        ]));
    @endphp

    <div class="container mx-auto max-w-7xl px-4 py-8 pb-24">
        <!-- Search + archived -->
        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="w-full sm:max-w-md">
                <label for="searchEngagements" class="sr-only">{{ __('Search engagements') }}</label>
                <x-field id="searchEngagements" name="search" icon="magnifying-glass" value="{{ request('search') }}"
                    placeholder="{{ __('Search by project or person…') }}" />
            </div>

            @if ($hasArchivedEngagements)
                <x-btn variant="secondary" href="{{ route('engagements.archived') }}">
                    <x-icon name="archive-box-2" class="h-4 w-4" />
                    {{ __('View archived') }}
                </x-btn>
            @endif
        </div>

        <!-- Status tabs -->
        <div class="-mx-4 mb-6 overflow-x-auto px-4 [scrollbar-width:none] sm:mx-0 sm:px-0 [&::-webkit-scrollbar]:hidden">
            <nav aria-label="{{ __('Filter engagements by status') }}"
                class="inline-flex min-w-full gap-1 border-b border-neutral-200 sm:flex">
                @foreach ($tabs as $key => $label)
                    @php($active = $activeStatus === $key)
                    <a href="{{ $tabUrl($key) }}" wire:navigate @if ($active) aria-current="page" @endif
                        @class([
                            '-mb-px inline-flex shrink-0 items-center gap-2 whitespace-nowrap border-b-2 px-4 py-3 text-sm font-medium transition-colors duration-150 focus:outline-none focus-visible:rounded-t-lg focus-visible:ring-2 focus-visible:ring-secondary/40',
                            'border-teal-600 text-neutral-900' => $active,
                            'border-transparent text-neutral-500 hover:text-neutral-800' => !$active,
                        ])>
                        {{ $label }}
                        <span @class([
                            'rounded-full px-2 py-0.5 text-xs font-semibold tabular-nums',
                            'bg-teal-50 text-teal-800' => $active,
                            'bg-neutral-100 text-neutral-600' => !$active,
                        ])>{{ $statusCounts[$key] ?? 0 }}</span>
                    </a>
                @endforeach
            </nav>
        </div>

        <div id="engagementsContainer">
            @if ($engagements->isEmpty() && !$hasFilters)
                <x-card class="rounded-2xl p-12 text-center">
                    <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-neutral-100 text-neutral-500">
                        <x-icon name="chat-bubble-left-right" class="h-8 w-8" />
                    </span>
                    <h3 class="mt-5 font-tertiary text-lg font-semibold text-neutral-900">{{ __('No engagements yet') }}</h3>
                    <p class="mx-auto mt-2 max-w-md text-sm text-tertiary">
                        {{ __('When an offer is made or accepted, the work shows up here with its deliverables, deadlines and messages.') }}
                    </p>
                    <div class="mt-6 flex flex-wrap justify-center gap-3">
                        <x-btn href="{{ route('jobs.browse') }}">
                            <x-icon name="magnifying-glass" class="h-4 w-4" />
                            {{ __('Browse projects') }}
                        </x-btn>
                        <x-btn variant="secondary" href="{{ route('jobs.create') }}">
                            <x-icon name="plus" class="h-4 w-4" />
                            {{ __('Post a project') }}
                        </x-btn>
                    </div>
                </x-card>
            @else
                @include('jobBoard.engagements.partials.engagements-list')
            @endif
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.getElementById('searchEngagements');
            const container = document.getElementById('engagementsContainer');
            let debounce;

            // Search re-renders just the list (status comes from the URL, so it survives searching).
            function fetchList(url) {
                container.classList.add('opacity-50');
                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => r.text())
                    .then(html => {
                        container.innerHTML = html;
                        window.history.replaceState({}, '', url);
                        container.classList.remove('opacity-50');
                        bindPagination();
                    });
            }

            searchInput.addEventListener('input', () => {
                clearTimeout(debounce);
                debounce = setTimeout(() => {
                    const url = new URL(window.location.href);
                    url.searchParams.set('search', searchInput.value);
                    url.searchParams.delete('page');
                    if (!searchInput.value) url.searchParams.delete('search');
                    fetchList(url);
                }, 300);
            });

            document.addEventListener('click', e => {
                if (e.target.closest('#clearEngagementFilters')) {
                    e.preventDefault();
                    window.location.href = @js(route('engagements.index'));
                }
            });

            function bindPagination() {
                container.querySelectorAll('a[data-page-link]').forEach(link => {
                    link.addEventListener('click', e => {
                        e.preventDefault();
                        fetchList(link.href);
                    });
                });
            }

            bindPagination();
        });
    </script>
</x-app-layout>
