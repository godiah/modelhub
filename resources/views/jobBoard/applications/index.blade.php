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
                <a href="{{ route('jobs.browse') }}"
                    class="inline-flex items-center px-4 py-2 bg-secondary text-white rounded-lg hover:bg-secondary/90 transition-colors duration-200 font-main text-sm font-medium shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    Browse More Jobs
                </a>
                <a href="{{ route('applications.archived') }}"
                    class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors duration-200 font-main text-sm font-medium shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="h-5 w-5 mr-2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                    </svg>
                    View Archived
                </a>
            </div>
        </div>
    </x-slot>

    <section class="bg-gradient-to-br from-neutral-50 to-neutral-100">
        <div class="container mx-auto max-w-7xl px-4 py-12 min-h-screen">
            <!-- Filters/Stats Bar -->
            <div
                class="bg-white rounded-xl shadow-sm mb-6 p-4 border border-neutral-200 flex flex-wrap items-center justify-between">
                <div class="flex items-center space-x-2 text-tertiary font-main">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-secondary" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <span>Total: <strong>{{ $applications->total() }}</strong> Applications</span>
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

            </div>
            <!-- Applications Container -->
            <div id="applicationsList">
                @if ($applications->isEmpty() && !$activeFilters)
                    <div class="bg-white p-8 rounded-2xl shadow-lg border border-neutral-200 relative overflow-hidden">
                        <div class="text-center py-16 relative z-10">
                            <div
                                class="bg-neutral-100 h-24 w-24 mx-auto rounded-full flex items-center justify-center mb-6">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-tertiary" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold text-neutral-800 mb-3 font-tertiary">No Applications Yet</h3>
                            <p class="text-tertiary mb-8 max-w-lg mx-auto font-secondary text-lg">You haven't submitted
                                any
                                applications yet. Start your career journey by exploring available opportunities.</p>
                            <a href="{{ route('jobs.browse') }}"
                                class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-accent to-accent/90 text-white rounded-lg hover:shadow-md transition-all duration-300 font-main font-medium">
                                Find Jobs
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </a>
                        </div>
                    </div>
                @else
                    @include('jobBoard.applications.partials.applications-list')
                @endif
            </div>
        </div>

        <!-- Footer -->
        @include('partials\footer-secondary')
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
