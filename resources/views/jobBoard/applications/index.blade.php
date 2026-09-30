<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-tertiary font-bold text-2xl text-primary leading-tight">
                    {{ __('My Job Applications') }}
                </h2>
                {{-- <p class="text-tertiary mt-2 font-main text-xs">Track and manage your job applications</p> --}}
            </div>
            <div class="flex space-x-3">
                <x-button variant="secondary" class="text-sm shadow-sm" href="{{ route('jobs.browse') }}">
                    <x-icon name="magnifying-glass" class="h-5 w-5 mr-2" />
                    Browse More Jobs
                </x-button>
                <x-button class="text-sm shadow-sm" href="{{ route('applications.archived') }}">
                    <x-icon name="archive-box-2" class="h-5 w-5 mr-2" />
                    View Archived
                </x-button>
            </div>
        </div>
    </x-slot>

    <section class="bg-gradient-to-br from-neutral-50 to-neutral-100">
        <div class="container mx-auto max-w-7xl px-4 py-12 min-h-screen">
            <!-- Filters/Stats Bar -->
            <x-card class="mb-6 p-4 flex flex-wrap items-center justify-between">
                <div class="flex items-center space-x-4 text-tertiary font-main">
                    <div class="flex items-center space-x-2">
                        <x-icon name="clipboard-list" class="h-5 w-5 text-secondary" />
                        <span>Total: <strong>{{ $applications->total() }}</strong> Applications</span>
                    </div>
                    @if ($draftCount > 0)
                        <a href="{{ route('applications.drafts') }}"
                            class="flex items-center space-x-2 text-primary hover:text-primary-dark transition-colors duration-200">
                            <x-icon name="pencil-square" class="h-5 w-5" />
                            <span>View Drafts ({{ $draftCount }})</span>
                        </a>
                    @endif
                </div>
                <div class="flex flex-wrap gap-2">
                    <select id="statusApplicationFilter" name="status"
                        class="rounded-lg border-neutral-200 text-sm font-main focus:border-secondary focus:ring focus:ring-secondary/20">
                        <option value="all" {{ $activeFilters['status'] == 'all' ? 'selected' : '' }}>All
                            Statuses</option>
                        <option value="submitted"{{ $activeFilters['status'] == 'submitted' ? 'selected' : '' }}>
                            Submitted</option>
                        <option value="reviewed" {{ $activeFilters['status'] == 'reviewed' ? 'selected' : '' }}>
                            Reviewed</option>
                        <option value="hired" {{ $activeFilters['status'] == 'hired' ? 'selected' : '' }}>Hired
                        </option>
                        <option value="rejected" {{ $activeFilters['status'] == 'rejected' ? 'selected' : '' }}>
                            Rejected</option>
                    </select>

                    <select id="sortApplicationFilter" name="sort"
                        class="rounded-lg border-neutral-200 text-sm font-main focus:border-secondary focus:ring focus:ring-secondary/20">
                        <option value="date_desc"{{ $activeFilters['sort'] == 'date_desc' ? 'selected' : '' }}>
                            Sort by Date (Newest)
                        </option>
                        <option value="date_asc" {{ $activeFilters['sort'] == 'date_asc' ? 'selected' : '' }}>
                            Sort by Date (Oldest)
                        </option>
                        <option value="status" {{ $activeFilters['sort'] == 'status' ? 'selected' : '' }}>
                            Sort by Status
                        </option>
                    </select>
                </div>

            </x-card>
            <!-- Applications Container -->
            <div id="applicationsList">
                @if ($applications->isEmpty() && !$activeFilters)
                    <x-card rounded="2xl" shadow="lg" clip class="p-8 relative">
                        <div class="text-center py-16 relative z-10">
                            <div
                                class="bg-neutral-100 h-24 w-24 mx-auto rounded-full flex items-center justify-center mb-6">
                                <x-icon name="document-text" class="h-12 w-12 text-tertiary" />
                            </div>
                            <h3 class="text-2xl font-bold text-neutral-800 mb-3 font-tertiary">No Applications Yet</h3>
                            <p class="text-tertiary mb-8 max-w-lg mx-auto font-secondary text-lg">You haven't submitted
                                any
                                applications yet. Start your career journey by exploring available opportunities.</p>
                            <a href="{{ route('jobs.browse') }}"
                                class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-accent to-accent/90 text-white rounded-lg hover:shadow-md transition-all duration-300 font-main font-medium">
                                Find Jobs
                                <x-icon name="arrow-right" class="h-5 w-5 ml-2" />
                            </a>
                        </div>
                    </x-card>
                @else
                    @include('jobBoard.applications.partials.applications-list')
                @endif
            </div>
        </div>

        <!-- Footer -->
        @include('partials.footer-secondary')
    </section>

    <style>
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(20, 184, 166, 0.4);
            }

            70% {
                box-shadow: 0 0 0 6px rgba(20, 184, 166, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(20, 184, 166, 0);
            }
        }

        .pulse-animation {
            animation: pulse 2s infinite;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const statusSelect = document.getElementById('statusApplicationFilter');
            const sortSelect = document.getElementById('sortApplicationFilter');
            const listContainer = document.getElementById('applicationsList');

            if (!statusSelect || !sortSelect || !listContainer) return;

            function fetchList() {
                const url = new URL(window.location.href);
                url.searchParams.set('status', statusSelect.value);
                url.searchParams.set('sort', sortSelect.value);

                listContainer.classList.add('opacity-50');
                fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(r => r.text())
                    .then(html => {
                        listContainer.innerHTML = html;
                        window.history.pushState({}, '', url);
                        listContainer.classList.remove('opacity-50');
                        bindPagination();
                    });
            }

            statusSelect.addEventListener('change', fetchList);
            sortSelect.addEventListener('change', fetchList);

            function bindPagination() {
                listContainer.querySelectorAll('.pagination a').forEach(link => {
                    link.addEventListener('click', e => {
                        e.preventDefault();
                        fetch(link.href, {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            })
                            .then(r => r.text())
                            .then(html => {
                                listContainer.innerHTML = html;
                                window.history.pushState({}, '', link.href);
                                bindPagination();
                            });
                    });
                });
            }

            bindPagination();
        });
    </script>
</x-app-layout>
