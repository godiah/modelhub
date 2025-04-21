<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-tertiary font-bold text-xl text-primary leading-tight">
                {{ __('Applications for') }}: <span class="text-secondary">{{ $job->title }}</span>
            </h2>
            <a href="{{ route('my-jobs.index', ['slug' => $job->slug]) }}"
                class="flex items-center px-4 py-2 bg-neutral-100 rounded-md text-sm font-main text-primary hover:bg-neutral-200 transition shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to My Jobs
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Job Summary Card -->
            <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg border border-neutral-200">
                <div class="p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-xl font-secondary font-semibold text-neutral-800">{{ $job->title }}</h3>
                            <div class="mt-2 flex flex-wrap items-center gap-4">
                                <p class="text-sm text-neutral-500 flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-secondary"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    Posted on {{ $job->created_at->format('M d, Y') }}
                                </p>
                                <p class="text-sm text-neutral-500 flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-accent"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    @if ($job->no_deadline)
                                        No deadline
                                    @else
                                        Deadline: {{ $job->deadline->format('M d, Y') }}
                                    @endif
                                </p>
                                <p class="text-sm text-neutral-500 flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" stroke="currentColor" fill="#1e3a8a"
                                        class="h-4 w-4 mr-1 text-primary" viewBox="0 0 512 512">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M512 80c0 18-14.3 34.6-38.4 48c-29.1 16.1-72.5 27.5-122.3 30.9c-3.7-1.8-7.4-3.5-11.3-5C300.6 137.4 248.2 128 192 128c-8.3 0-16.4 .2-24.5 .6l-1.1-.6C142.3 114.6 128 98 128 80c0-44.2 86-80 192-80S512 35.8 512 80zM160.7 161.1c10.2-.7 20.7-1.1 31.3-1.1c62.2 0 117.4 12.3 152.5 31.4C369.3 204.9 384 221.7 384 240c0 4-.7 7.9-2.1 11.7c-4.6 13.2-17 25.3-35 35.5c0 0 0 0 0 0c-.1 .1-.3 .1-.4 .2c0 0 0 0 0 0s0 0 0 0c-.3 .2-.6 .3-.9 .5c-35 19.4-90.8 32-153.6 32c-59.6 0-112.9-11.3-148.2-29.1c-1.9-.9-3.7-1.9-5.5-2.9C14.3 274.6 0 258 0 240c0-34.8 53.4-64.5 128-75.4c10.5-1.5 21.4-2.7 32.7-3.5zM416 240c0-21.9-10.6-39.9-24.1-53.4c28.3-4.4 54.2-11.4 76.2-20.5c16.3-6.8 31.5-15.2 43.9-25.5l0 35.4c0 19.3-16.5 37.1-43.8 50.9c-14.6 7.4-32.4 13.7-52.4 18.5c.1-1.8 .2-3.5 .2-5.3zm-32 96c0 18-14.3 34.6-38.4 48c-1.8 1-3.6 1.9-5.5 2.9C304.9 404.7 251.6 416 192 416c-62.8 0-118.6-12.6-153.6-32C14.3 370.6 0 354 0 336l0-35.4c12.5 10.3 27.6 18.7 43.9 25.5C83.4 342.6 135.8 352 192 352s108.6-9.4 148.1-25.9c7.8-3.2 15.3-6.9 22.4-10.9c6.1-3.4 11.8-7.2 17.2-11.2c1.5-1.1 2.9-2.3 4.3-3.4l0 3.4 0 5.7 0 26.3zm32 0l0-32 0-25.9c19-4.2 36.5-9.5 52.1-16c16.3-6.8 31.5-15.2 43.9-25.5l0 35.4c0 10.5-5 21-14.9 30.9c-16.3 16.3-45 29.7-81.3 38.4c.1-1.7 .2-3.5 .2-5.3zM192 448c56.2 0 108.6-9.4 148.1-25.9c16.3-6.8 31.5-15.2 43.9-25.5l0 35.4c0 44.2-86 80-192 80S0 476.2 0 432l0-35.4c12.5 10.3 27.6 18.7 43.9 25.5C83.4 438.6 135.8 448 192 448z" />
                                    </svg>
                                    Budget: <span class="font-medium">Ksh.{{ number_format($job->budget) }}</span>
                                </p>
                            </div>
                        </div>
                        <span
                            class="px-3 py-1.5 text-xs font-medium rounded-full inline-flex items-center {{ $job->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            <span
                                class="h-2 w-2 rounded-full {{ $job->is_active ? 'bg-green-500' : 'bg-red-500' }} mr-1.5"></span>
                            {{ $job->is_active ? 'Active' : 'Closed' }}
                        </span>
                    </div>

                    <div x-data="{ expanded: false, shouldShowMore: false }" x-init="$nextTick(() => {
                        const el = $refs.content;
                        shouldShowMore = el.scrollHeight > (window.innerWidth < 768 ? 80 : 40); // Approximate 5rem/2.5rem in pixels
                    })"
                        class="mt-4 bg-neutral-50 rounded-lg p-4 border border-neutral-100">

                        <!-- Markdown content div with reference -->
                        <div x-ref="content"
                            class="prose prose-sm max-w-none text-neutral-700 text-sm transition-all duration-300 overflow-hidden
                                        [&>ul]:list-disc [&>ul]:pl-5 [&>ul]:mb-1 
                                        [&>ol]:list-decimal [&>ol]:pl-5 [&>ol]:mb-1
                                        [&>blockquote]:border-l-4 [&>blockquote]:border-neutral-200 [&>blockquote]:pl-4 [&>blockquote]:italic [&>blockquote]:my-2
                                        [&>h1]:text-lg [&>h1]:font-bold [&>h1]:mb-2 [&>h1]:mt-3
                                        [&>h2]:text-base [&>h2]:font-bold [&>h2]:mb-1.5 [&>h2]:mt-2.5
                                        [&>h3]:text-sm [&>h3]:font-bold [&>h3]:mb-1 [&>h3]:mt-2
                                        [&>h4,&>h5,&>h6]:text-sm [&>h4,&>h5,&>h6]:font-semibold [&>h4,&>h5,&>h6]:mb-1 [&>h4,&>h5,&>h6]:mt-2
                                        [&>p]:mb-1"
                            :class="expanded ? 'max-h-none' : 'max-h-[5rem] md:max-h-[2.5rem]'">
                            {!! Str::markdown($job->description) !!}
                        </div>

                        <!-- Toggle button that only shows when needed -->
                        <button x-show="shouldShowMore" x-on:click="expanded = !expanded"
                            class="mt-2 text-secondary text-sm font-medium hover:text-primary transition"
                            x-text="expanded ? 'Show less' : 'Read more'"></button>
                    </div>

                </div>
            </div>

            <!-- Applications List -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-neutral-200">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-tertiary font-semibold text-neutral-800 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-primary" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span>{{ $applications->count() }} Applications</span>
                            @if ($applications->count() > 0)
                                <span
                                    class="ml-2 px-2 py-0.5 text-xs font-medium bg-primary text-white rounded-full">{{ $applications->where('status', 'submitted')->count() }}
                                    New</span>
                            @endif
                        </h3>

                        @if ($applications->count() > 0)
                            <div class="flex gap-2">
                                <div class="relative">
                                    <input id="searchInput" type="text" placeholder="Search applications"
                                        class="pl-9 pr-3 py-2 border border-neutral-300 rounded-md text-sm focus:ring-2 focus:ring-secondary focus:border-secondary">
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        class="h-4 w-4 absolute left-3 top-2.5 text-neutral-400" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <select id="statusFilter"
                                    class="pl-3 pr-8 py-2 border border-neutral-300 rounded-md text-sm focus:ring-2 focus:ring-secondary focus:border-secondary">
                                    <option value="all">All Status</option>
                                    <option value="submitted">Submitted</option>
                                    <option value="reviewed">Reviewed</option>
                                    <option value="hired">Hired</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                                <a href="#" id="clearFiltersButton"
                                    style="display: {{ $hasFilters ? 'inline-flex' : 'none' }}"
                                    class="items-center gap-1 px-3 py-1.5 border border-neutral-300 text-sm rounded-md text-neutral-700 bg-white hover:bg-neutral-100 hover:border-primary hover:text-primary transition-colors duration-200">
                                    Clear Filters
                                </a>

                            </div>
                        @endif
                    </div>

                    @if ($applications->isEmpty() && !$hasFilters)
                        <div
                            class="flex flex-col items-center justify-center p-10 space-y-4 text-center rounded-lg bg-neutral-50 border border-neutral-100">
                            <div class="p-6 rounded-full bg-white shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-14 w-14 text-neutral-300"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-secondary font-semibold text-neutral-800">No Applications Yet</h3>
                            <p class="text-neutral-500 max-w-md font-main">
                                No applications have been received for this job yet. This could be because the job was
                                recently posted or it hasn't gained visibility yet.
                            </p>
                            <div class="space-x-4 pt-2">
                                <a href="{{ route('jobs.edit', ['job' => $job->slug]) }}"
                                    class="px-4 py-2 text-sm font-medium text-primary bg-white border border-primary rounded-md shadow-sm hover:bg-primary hover:text-white transition">
                                    Edit Job Details
                                </a>
                                <a href=""
                                    class="px-4 py-2 text-sm font-medium text-white bg-secondary rounded-md shadow-sm hover:bg-secondary/90 transition">
                                    Share Job
                                </a>
                            </div>
                        </div>
                    @else
                        <div id="applicationsContainer">
                            @include('jobBoard.posted.applications.partials.applications-list', [
                                'applications' => $applications,
                                'hasFilters' => $hasFilters,
                            ])
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @include('partials\footer-secondary')

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const statusFilter = document.getElementById('statusFilter');
            const applicationsContainer = document.getElementById('applicationsContainer');
            const clearFiltersButton = document.getElementById('clearFiltersButton');
            let searchTimeout;

            // Search with debounce
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(fetchApplications, 300);
            });

            // Status filter change
            statusFilter.addEventListener('change', fetchApplications);

            // Clear filters button
            if (clearFiltersButton) {
                clearFiltersButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    clearAllFilters();
                });
            }

            // Handle clear filters link in empty state
            applicationsContainer.addEventListener('click', function(e) {
                if (e.target.id === 'clearFilters' || e.target.closest('#clearFilters')) {
                    e.preventDefault();
                    clearAllFilters();
                }
            });

            function clearAllFilters() {
                searchInput.value = '';
                statusFilter.value = 'all';
                fetchApplications();
            }

            function fetchApplications() {
                const search = searchInput.value;
                const status = statusFilter.value;

                // Show loading state
                applicationsContainer.classList.add('opacity-50');

                // Construct URL with parameters
                const url = new URL(window.location.href);
                url.searchParams.set('search', search);
                url.searchParams.set('status', status);

                fetch(url.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.text())
                    .then(html => {
                        applicationsContainer.innerHTML = html;
                        applicationsContainer.classList.remove('opacity-50');

                        // Update URL without page reload
                        window.history.pushState({}, '', url.toString());

                        // Update clear filters button visibility
                        const hasFilters = search !== '' || status !== 'all';
                        if (clearFiltersButton) {
                            clearFiltersButton.style.display = hasFilters ? 'inline-block' : 'none';
                        }

                        // Reinitialize pagination links
                        initializePaginationLinks();
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        applicationsContainer.classList.remove('opacity-50');
                    });
            }

            function initializePaginationLinks() {
                const paginationLinks = applicationsContainer.querySelectorAll('.pagination a');
                paginationLinks.forEach(link => {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        const url = this.href;

                        // Show loading state
                        applicationsContainer.classList.add('opacity-50');

                        fetch(url, {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            })
                            .then(response => response.text())
                            .then(html => {
                                applicationsContainer.innerHTML = html;
                                applicationsContainer.classList.remove('opacity-50');

                                // Update URL without page reload
                                window.history.pushState({}, '', url);

                                // Scroll to top of applications container
                                applicationsContainer.scrollIntoView({
                                    behavior: 'smooth'
                                });

                                // Reinitialize pagination links
                                initializePaginationLinks();
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                applicationsContainer.classList.remove('opacity-50');
                            });
                    });
                });
            }

            // Initialize pagination links on page load
            initializePaginationLinks();

            // Set initial filter values from URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('search')) {
                searchInput.value = urlParams.get('search');
            }
            if (urlParams.has('status')) {
                statusFilter.value = urlParams.get('status');
            }

            // Show/hide clear filters button on page load
            const hasFilters = searchInput.value !== '' || statusFilter.value !== 'all';
            if (clearFiltersButton) {
                clearFiltersButton.style.display = hasFilters ? 'inline-block' : 'none';
            }
        });
    </script>
</x-app-layout>
