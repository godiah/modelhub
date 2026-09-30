<x-app-layout>
    <x-slot name="toolbar">
        <x-button class="text-sm shadow-sm" href="{{ route('my-jobs.archived.posted-jobs') }}">
            <x-icon name="archive-box-2" class="h-5 w-5 mr-2" />
            Archived Jobs
        </x-button>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if ($postedJobs->isEmpty() && !$hasFilters)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-neutral-200">
                    <div class="p-12 flex flex-col items-center justify-center">
                        <x-icon name="document-text" class="h-24 w-24 text-neutral-300" stroke-width="1.5" />
                        <p class="mt-6 text-neutral-500 font-main text-lg">You haven't posted any jobs yet.</p>
                        <x-button variant="secondary" class="mt-4" href="{{ route('jobs.create') }}">
                            <x-icon name="plus-solid" class="h-5 w-5 mr-2" />
                            Post Your First Job
                        </x-button>
                    </div>
                </div>
            @else
                <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:justify-between sm:items-center">
                    <h3 class="font-secondary text-neutral-700 text-lg">
                        Showing <span class="font-medium">{{ $postedJobs->count() }}</span> jobs
                    </h3>
                    <div class="flex flex-wrap items-center gap-2 font-main">
                        <select id="statusFilter"
                            class="rounded-lg border-neutral-300 text-neutral-700 text-sm focus:ring-primary focus:border-primary">
                            <option value="all">All Jobs</option>
                            <option value="active">Active Jobs</option>
                            <option value="closed">Closed Jobs</option>
                        </select>
                        <select id="sortFilter"
                            class="rounded-lg border-neutral-300 text-neutral-700 text-sm focus:ring-primary focus:border-primary">
                            <option value="newest">Sort by Newest</option>
                            <option value="deadline">Sort by Deadline</option>
                            <option value="budget_high">Sort by Budget (High-Low)</option>
                            <option value="budget_low">Sort by Budget (Low-High)</option>
                        </select>
                        <a href="#" id="clearFiltersButton"
                            style="display: {{ $hasFilters ? 'inline-block' : 'none' }}"
                            class="text-sm text-neutral-600 hover:text-primary underline">
                            Clear Filters
                        </a>
                    </div>
                </div>

                <div id="jobsContainer">
                    @include('jobBoard.posted.partials.jobs-grid', [
                        'postedJobs' => $postedJobs,
                        'hasFilters' => $hasFilters,
                    ])
                </div>

                {{-- <div class="mt-8">
                    {{ $postedJobs->links() }}
                </div> --}}
            @endif
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const statusFilter = document.getElementById('statusFilter');
            const sortFilter = document.getElementById('sortFilter');
            const jobsContainer = document.getElementById('jobsContainer');
            const jobCount = document.getElementById('jobCount');
            const clearFiltersButton = document.getElementById('clearFiltersButton');

            function fetchJobs() {
                const status = statusFilter.value;
                const sort = sortFilter.value;

                // Show loading state
                jobsContainer.classList.add('opacity-50');

                fetch(`{{ route('my-jobs.index') }}?status=${status}&sort=${sort}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.text())
                    .then(html => {
                        jobsContainer.innerHTML = html;
                        jobsContainer.classList.remove('opacity-50');

                        // Update job count
                        const newCount = jobsContainer.querySelectorAll('.grid > div').length;
                        jobCount.textContent = newCount;

                        // Show/hide clear filters button
                        const hasFilters = status !== 'all' || sort !== 'newest';
                        clearFiltersButton.style.display = hasFilters ? 'inline-block' : 'none';

                        // Reinitialize pagination links
                        initializePaginationLinks();
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        jobsContainer.classList.remove('opacity-50');
                    });
            }

            function initializePaginationLinks() {
                const paginationLinks = jobsContainer.querySelectorAll('.pagination a');
                paginationLinks.forEach(link => {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        const url = this.href;

                        // Show loading state
                        jobsContainer.classList.add('opacity-50');

                        fetch(url, {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            })
                            .then(response => response.text())
                            .then(html => {
                                jobsContainer.innerHTML = html;
                                jobsContainer.classList.remove('opacity-50');

                                // Scroll to top of jobs container
                                jobsContainer.scrollIntoView({
                                    behavior: 'smooth'
                                });

                                // Reinitialize pagination links
                                initializePaginationLinks();
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                jobsContainer.classList.remove('opacity-50');
                            });
                    });
                });
            }

            // Event listeners
            statusFilter.addEventListener('change', fetchJobs);
            sortFilter.addEventListener('change', fetchJobs);

            clearFiltersButton.addEventListener('click', function(e) {
                e.preventDefault();
                statusFilter.value = 'all';
                sortFilter.value = 'newest';
                fetchJobs();
            });

            // When clicking clear filters in the empty state
            jobsContainer.addEventListener('click', function(e) {
                if (e.target.id === 'clearFilters') {
                    e.preventDefault();
                    statusFilter.value = 'all';
                    sortFilter.value = 'newest';
                    fetchJobs();
                }
            });

            // Initialize pagination links on page load
            initializePaginationLinks();

            // Set initial filter values from URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('status')) {
                statusFilter.value = urlParams.get('status');
            }
            if (urlParams.has('sort')) {
                sortFilter.value = urlParams.get('sort');
            }
        });
    </script>
</x-app-layout>
