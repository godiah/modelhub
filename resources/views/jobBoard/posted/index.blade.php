<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-tertiary font-semibold text-2xl text-primary leading-tight">
                {{ __('Jobs Posted') }}
            </h2>

            <div class="flex space-x-3">
                <a href="{{ route('jobs.create') }}"
                    class="inline-flex items-center px-4 py-2 bg-secondary hover:bg-secondary/90 text-white font-medium rounded-lg transition-colors duration-200 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                            clip-rule="evenodd" />
                    </svg>
                    Post New Job
                </a>

                <a href="{{ route('my-jobs.archived.posted-jobs') }}"
                    class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors duration-200 font-main text-sm font-medium shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="h-5 w-5 mr-2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                    </svg>
                    Archived Jobs
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-10 bg-gradient-to-b from-neutral-50 to-white min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if ($postedJobs->isEmpty() && !$hasFilters)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-neutral-200">
                    <div class="p-12 flex flex-col items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 text-neutral-300" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="mt-6 text-neutral-500 font-main text-lg">You haven't posted any jobs yet.</p>
                        <a href="{{ route('jobs.create') }}"
                            class="mt-4 inline-flex items-center px-4 py-2 bg-secondary hover:bg-secondary/90 text-white font-medium rounded-lg transition-colors duration-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                                    clip-rule="evenodd" />
                            </svg>
                            Post Your First Job
                        </a>
                    </div>
                </div>
            @else
                <div class="mb-6 flex justify-between items-center">
                    <h3 class="font-secondary text-neutral-700 text-lg">
                        Showing <span class="font-medium">{{ $postedJobs->count() }}</span> jobs
                    </h3>
                    <div class="flex items-center space-x-2">
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

    @include('partials\footer-secondary')

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
