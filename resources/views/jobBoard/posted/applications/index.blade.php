<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-tertiary font-bold text-xl text-primary leading-tight">
                {{ __('Applications for') }}: <span class="text-secondary">{{ $job->title }}</span>
            </h2>
            <a href="{{ route('my-jobs.index', ['slug' => $job->slug]) }}"
                class="flex items-center px-4 py-2 bg-neutral-100 rounded-md text-sm font-main text-primary hover:bg-neutral-200 transition shadow-sm">
                <x-icon name="arrow-left" class="h-4 w-4 mr-2" />
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
                            <h3 class="text-xl font-main font-semibold text-neutral-800">{{ $job->title }}</h3>
                            <div class="mt-2 flex flex-wrap items-center gap-4 font-secondary">
                                <p class="text-sm text-neutral-500 flex items-center">
                                    <x-icon name="calendar" class="h-4 w-4 mr-1 text-secondary" />
                                    Posted on <x-date :date="$job->created_at" format="M d, Y" />
                                </p>
                                <p class="text-sm text-neutral-500 flex items-center">
                                    <x-icon name="clock" class="h-4 w-4 mr-1 text-accent" />
                                    @if ($job->no_deadline)
                                        No deadline
                                    @else
                                        Deadline: <x-date :date="$job->deadline" format="M d, Y" />
                                    @endif
                                </p>
                                <p class="text-sm text-neutral-500 flex items-center">
                                    <x-icon name="coins" class="h-4 w-4 mr-1 text-primary" fill="#1e3a8a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    Budget: <span class="font-medium"><x-money :amount="$job->budget" :decimals="0" /></span>
                                </p>
                            </div>
                        </div>
                        <span
                            class="px-3 py-1.5 text-xs font-medium font-secondary rounded-full inline-flex items-center {{ $job->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            <span
                                class="h-2 w-2 rounded-full {{ $job->is_active ? 'bg-green-500' : 'bg-red-500' }} mr-1.5"></span>
                            {{ $job->is_active ? 'Active' : 'Closed' }}
                        </span>
                    </div>

                    <div x-data="{ expanded: false, shouldShowMore: false }" x-init="$nextTick(() => {
                        const el = $refs.content;
                        shouldShowMore = el.scrollHeight > (window.innerWidth < 768 ? 80 : 40);
                    })"
                        class="mt-4 bg-neutral-50 rounded-lg p-4 border border-neutral-100">

                        <!-- Markdown content div with reference -->
                        <div x-ref="content"
                            class="prose prose-sm max-w-none text-neutral-700 text-sm font-main transition-all duration-300 overflow-hidden
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
                            class="mt-2 text-secondary text-sm font-medium font-main hover:text-primary transition"
                            x-text="expanded ? 'Show less' : 'Read more'"></button>
                    </div>

                </div>
            </div>

            <!-- Applications List -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-neutral-200">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-tertiary font-semibold text-neutral-800 flex items-center">
                            <x-icon name="document-text" class="h-5 w-5 mr-2 text-primary" />
                            <span>{{ $applications->count() }} Applications</span>
                            @if ($applications->count() > 0)
                                <span
                                    class="ml-2 px-2 py-0.5 text-xs font-medium bg-primary text-white rounded-full">{{ $applications->where('status', 'submitted')->count() }}
                                    New</span>
                            @endif
                        </h3>

                        @if ($applications->count() > 0)
                            <div class="flex gap-2 font-secondary">
                                <div class="relative">
                                    <input id="searchInput" type="text" placeholder="Search applications"
                                        class="pl-9 pr-3 py-2 border border-neutral-300 rounded-md text-sm focus:ring-2 focus:ring-secondary focus:border-secondary">
                                    <x-icon name="magnifying-glass" class="h-4 w-4 absolute left-3 top-2.5 text-neutral-400" />
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
                                <x-icon name="envelope" class="h-14 w-14 text-neutral-300" />
                            </div>
                            <h3 class="text-xl font-secondary font-semibold text-neutral-800">No Applications Yet</h3>
                            <p class="text-neutral-500 max-w-md font-main">
                                No applications have been received for this job yet. This could be because the job was
                                recently posted or it hasn't gained visibility yet.
                            </p>
                            <div class="space-x-4 pt-2 font-secondary">
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

    @include('partials.footer-secondary')

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
