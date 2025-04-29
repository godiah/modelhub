<x-app-layout>

    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-tertiary font-bold text-xl text-primary leading-tight">
                    {{ __('Browse Available Jobs') }}
                </h2>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('jobs.index') }}"
                    class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors duration-200 font-main text-sm font-medium shadow-sm">
                    <svg class="h-5 w-5 mr-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                        viewBox="0 0 20 20">
                        <path
                            d="M19.707 9.293l-2-2-7-7a1 1 0 00-1.414 0l-7 7-2 2a1 1 0 001.414 1.414L2 10.414V18a2 2 0 002 2h3a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1h3a2 2 0 002-2v-7.586l.293.293a1 1 0 001.414-1.414z" />
                    </svg>
                    Home
                </a>
            </div>
        </div>
    </x-slot>

    <section>
        <div class="container mx-auto max-w-7xl">
            <!-- Jobs Grid -->
            <div class="font-main px-4 sm:px-6 lg:px-8 py-14">
                <!-- Header Section -->
                <div class="max-w-7xl mx-auto mb-8 px-4">
                    <!-- Search and Filters -->
                    <div class="space-y-4">
                        <!-- Search Bar -->
                        <div class="relative">
                            <div class="flex">
                                <div class="relative flex-grow">
                                    <input type="text" id="search-input" name="search"
                                        placeholder="Search for jobs..." value="{{ request()->get('search') }}"
                                        class="w-full h-12 pl-4 pr-10 rounded-lg border border-neutral-300 focus:ring-2 focus:ring-secondary focus:border-transparent outline-none"
                                        autocomplete="off">
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-neutral-400"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </div>

                                    <!-- Search history dropdown -->
                                    <div id="search-history"
                                        class="absolute z-10 w-full mt-1 bg-white rounded-md shadow-lg hidden">
                                        <div class="py-1 text-sm text-gray-700">
                                            <div class="px-4 py-2 flex justify-between items-center">
                                                <span class="text-xs text-gray-500">Recent Searches</span>
                                                <button id="clear-history"
                                                    class="text-xs text-red-500 hover:text-red-700">Clear All</button>
                                            </div>
                                            <div id="search-history-items" class="max-h-48 overflow-y-auto">
                                                <!-- This will be populated via JS -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Filters -->
                        <div class="flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-4">
                            <!-- Skills Filter -->
                            <div class="w-full md:w-1/3">
                                <label for="skills-filter"
                                    class="block text-sm font-medium text-neutral-700 mb-1">Filter by Skills</label>
                                <select id="skills-filter" name="skills"
                                    class="filter-select w-full h-10 pl-3 pr-10 border border-neutral-300 rounded-lg focus:ring-2 focus:ring-secondary focus:border-transparent cursor-pointer">
                                    <option value="">All Skills</option>
                                    @foreach (App\Models\Skill::where('is_active', true)->get() as $skill)
                                        <option value="{{ $skill->id }}"
                                            {{ request()->get('skills') == $skill->id ? 'selected' : '' }}>
                                            {{ $skill->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Software Filter -->
                            <div class="w-full md:w-1/3">
                                <label for="software-filter"
                                    class="block text-sm font-medium text-neutral-700 mb-1">Filter by Software</label>
                                <select id="software-filter" name="software"
                                    class="filter-select w-full h-10 pl-3 pr-10 border border-neutral-300 rounded-lg focus:ring-2 focus:ring-secondary focus:border-transparent cursor-pointer">
                                    <option value="">All Software</option>
                                    @foreach (App\Models\Software::where('is_active', true)->get() as $software)
                                        <option value="{{ $software->id }}"
                                            {{ request()->get('software') == $software->id ? 'selected' : '' }}>
                                            {{ $software->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Sort By -->
                            <div class="w-full md:w-1/3">
                                <label for="sort-by" class="block text-sm font-medium text-neutral-700 mb-1">Sort
                                    By</label>
                                <select id="sort-by" name="sort"
                                    class="filter-select w-full h-10 pl-3 pr-10 border border-neutral-300 rounded-lg focus:ring-2 focus:ring-secondary focus:border-transparent cursor-pointer">
                                    <option value="newest"
                                        {{ request()->get('sort') == 'newest' || !request()->has('sort') ? 'selected' : '' }}>
                                        Newest First
                                    </option>
                                    <option value="budget_high"
                                        {{ request()->get('sort') == 'budget_high' ? 'selected' : '' }}>
                                        Budget (High to Low)
                                    </option>
                                    <option value="budget_low"
                                        {{ request()->get('sort') == 'budget_low' ? 'selected' : '' }}>
                                        Budget (Low to High)
                                    </option>
                                    <option value="deadline"
                                        {{ request()->get('sort') == 'deadline' ? 'selected' : '' }}>
                                        Deadline (Soonest)
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Filter Reset Button -->
                        <div class="flex justify-end">
                            <button id="reset-filters"
                                class="px-4 py-2 text-neutral-600 border border-neutral-300 hover:bg-neutral-100 rounded-lg transition-colors text-sm font-medium">
                                Reset Filters
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Loading indicator -->
                <div id="loading-indicator" class="text-center py-8 hidden">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mx-auto"></div>
                    <p class="mt-4 text-sm text-neutral-600">Loading jobs...</p>
                </div>

                <!-- Jobs listing container -->
                <div id="jobs-container" class="space-y-4 max-w-7xl mx-auto px-4">
                    @include('jobBoard.jobs.partials.jobs-list', ['jobs' => $jobs])
                </div>
            </div>
        </div>

        <!-- Footer -->
        @include('partials\footer-secondary')
    </section>

    <!-- JavaScript for enhanced interactivity -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('search-input');
            const skillsFilter = document.getElementById('skills-filter');
            const softwareFilter = document.getElementById('software-filter');
            const sortBy = document.getElementById('sort-by');
            const resetButton = document.getElementById('reset-filters');
            const jobsContainer = document.getElementById('jobs-container');
            const loadingIndicator = document.getElementById('loading-indicator');

            let searchTimeout;

            // Function to update jobs based on filters
            function updateJobs() {
                const searchValue = searchInput.value;
                const skillsValue = skillsFilter.value;
                const softwareValue = softwareFilter.value;
                const sortValue = sortBy.value;

                // Show loading indicator
                loadingIndicator.classList.remove('hidden');
                jobsContainer.style.opacity = '0.5';

                // Create URL with query parameters
                const params = new URLSearchParams();
                if (searchValue) params.append('search', searchValue);
                if (skillsValue) params.append('skills', skillsValue);
                if (softwareValue) params.append('software', softwareValue);
                if (sortValue) params.append('sort', sortValue);

                // Make AJAX request
                fetch(`${window.location.pathname}?${params.toString()}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.text())
                    .then(html => {
                        jobsContainer.innerHTML = html;
                        jobsContainer.style.opacity = '1';
                        loadingIndicator.classList.add('hidden');

                        // Update URL without page reload
                        window.history.pushState({}, '', `${window.location.pathname}?${params.toString()}`);
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        loadingIndicator.classList.add('hidden');
                        jobsContainer.style.opacity = '1';
                    });
            }

            // Search input with debounce
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(updateJobs, 300);
            });

            // Instant filter changes
            [skillsFilter, softwareFilter, sortBy].forEach(element => {
                element.addEventListener('change', updateJobs);
            });

            // Reset filters
            resetButton.addEventListener('click', function() {
                searchInput.value = '';
                skillsFilter.value = '';
                softwareFilter.value = '';
                sortBy.value = 'newest';
                updateJobs();
            });

            // Handle browser back/forward buttons
            window.addEventListener('popstate', function() {
                const params = new URLSearchParams(window.location.search);
                searchInput.value = params.get('search') || '';
                skillsFilter.value = params.get('skills') || '';
                softwareFilter.value = params.get('software') || '';
                sortBy.value = params.get('sort') || 'newest';
                updateJobs();
            });
        });
    </script>
</x-app-layout>
