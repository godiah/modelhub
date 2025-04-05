<x-app-layout>
    <section>
        <div class="container mx-auto max-w-7xl">
            <!-- Breadcrumb -->
            <nav class="flex max-w-xl  p-8" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-2">
                    <!-- First Link -->
                    <li class="inline-flex items-center">
                        <a href="{{ route('home') }}"
                            class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-700">
                            <svg class="w-3 h-3 mr-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M19.707 9.293l-2-2-7-7a1 1 0 00-1.414 0l-7 7-2 2a1 1 0 001.414 1.414L2 10.414V18a2 2 0 002 2h3a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1h3a2 2 0 002-2v-7.586l.293.293a1 1 0 001.414-1.414z" />
                            </svg>
                            ModelHub
                        </a>
                    </li>
                    <!-- Second Link -->
                    <li>
                        <div class="flex items-center">
                            <svg class="rtl:rotate-180 w-3 h-3 text-gray-400 mx-1" aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2" d="M1 9l4-4-4-4" />
                            </svg>
                            <a href="{{ route('job.home') }}"
                                class="ml-1 text-sm font-medium text-gray-500 hover:text-gray-700 md:ml-2">
                                Modelling Jobs
                            </a>
                        </div>
                    </li>
                    <!-- Active Link -->
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg class="rtl:rotate-180 w-3 h-3 text-gray-400 mx-1" aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2" d="M1 9l4-4-4-4" />
                            </svg>
                            <span class="ml-1 text-sm font-medium text-black md:ml-2">
                                Browse
                            </span>
                        </div>
                    </li>
                </ol>
            </nav>

            <!-- Jobs Grid -->
            <div class="font-main px-4 sm:px-6 lg:px-8 pb-24">
                <!-- Header Section -->
                <div class="max-w-7xl mx-auto mb-8 px-4">
                    <h1 class="text-2xl md:text-3xl font-bold text-neutral-800 mb-6">Browse Available Jobs</h1>

                    <!-- Search and Filters -->
                    <div class="space-y-4">
                        <!-- Search Bar -->
                        <div class="relative">
                            <form action="{{ route('jobs.browse') }}" method="GET">
                                <div class="flex">
                                    <div class="relative flex-grow">
                                        <input type="text" name="search" placeholder="Search for jobs..."
                                            value="{{ request()->get('search') }}"
                                            class="w-full h-12 pl-4 pr-10 rounded-l-lg border border-neutral-300 focus:ring-2 focus:ring-secondary focus:border-transparent outline-none">
                                        <div
                                            class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-neutral-400"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                            </svg>
                                        </div>
                                    </div>
                                    <button type="submit"
                                        class="bg-secondary hover:bg-secondary/90 text-white px-6 font-medium rounded-r-lg transition-colors">
                                        Search
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Filters -->
                        <div class="flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-4">
                            <!-- Skills Filter -->
                            <div class="w-full md:w-1/3">
                                <label for="skills-filter"
                                    class="block text-sm font-medium text-neutral-700 mb-1">Filter by Skills</label>
                                <select id="skills-filter" name="skills"
                                    class="w-full h-10 pl-3 pr-10 border border-neutral-300 rounded-lg focus:ring-2 focus:ring-secondary focus:border-transparent cursor-pointer">
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
                                    class="w-full h-10 pl-3 pr-10 border border-neutral-300 rounded-lg focus:ring-2 focus:ring-secondary focus:border-transparent cursor-pointer">
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
                                    class="w-full h-10 pl-3 pr-10 border border-neutral-300 rounded-lg focus:ring-2 focus:ring-secondary focus:border-transparent cursor-pointer">
                                    <option value="newest"
                                        {{ request()->get('sort') == 'newest' || !request()->has('sort') ? 'selected' : '' }}>
                                        Newest First</option>
                                    <option value="budget_high"
                                        {{ request()->get('sort') == 'budget_high' ? 'selected' : '' }}>Budget (High to
                                        Low)</option>
                                    <option value="budget_low"
                                        {{ request()->get('sort') == 'budget_low' ? 'selected' : '' }}>Budget (Low to
                                        High)</option>
                                    <option value="deadline"
                                        {{ request()->get('sort') == 'deadline' ? 'selected' : '' }}>Deadline (Soonest)
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Filter Apply/Reset Button -->
                        <div class="flex justify-end space-x-3">
                            <button id="reset-filters"
                                class="px-4 py-2 text-neutral-600 border border-neutral-300 hover:bg-neutral-100 rounded-lg transition-colors text-sm font-medium">
                                Reset Filters
                            </button>
                            <button id="apply-filters"
                                class="px-4 py-2 bg-primary hover:bg-primary/90 text-white rounded-lg transition-colors text-sm font-medium">
                                Apply Filters
                            </button>
                        </div>
                    </div>
                </div>

                <div class="space-y-4 max-w-7xl mx-auto px-4">
                    @foreach ($jobs as $job)
                        <div
                            class="bg-white rounded-xl shadow-lg overflow-hidden border border-neutral-200 hover:shadow-xl transition-all duration-300 
                                        md:flex md:h-40">

                            <!-- Job Image Section (Top on mobile, Left on desktop) -->
                            <div class="relative h-auto w-full md:w-40 md:min-w-40 md:h-full">
                                @if ($job->images)
                                    <img src="{{ asset('storage/' . $job->images) }}" alt="{{ $job->title }}"
                                        class="w-full h-full object-cover">
                                @else
                                    <div
                                        class="w-full h-full bg-gradient-to-r from-primary/90 to-primary/70 flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-white/80"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                @endif
                            </div>

                            <!-- Job Content Section (Bottom on mobile, Right on desktop) -->
                            <div class="flex-grow p-4 flex flex-col overflow-hidden">
                                <!-- Top Row: Title and Deadline -->
                                <div
                                    class="flex flex-col space-y-2 sm:flex-row sm:justify-between sm:items-start sm:space-y-0 mb-2">
                                    <!-- Left Side: Title and Posted Date -->
                                    <div class="flex flex-col">
                                        <h2 class="text-base font-semibold text-neutral-800 pr-2">
                                            {{ $job->title }}
                                        </h2>
                                        <p class="text-xs text-gray-500 mt-0.5">
                                            Posted on {{ $job->created_at->format('M d, Y') }}
                                        </p>
                                    </div>

                                    <!-- Right Side: Deadline Badge -->
                                    <div class="flex-shrink-0">
                                        @if ($job->no_deadline)
                                            <span
                                                class="bg-accent text-white text-xs font-semibold px-2 py-0.5 rounded-full">
                                                No Fixed Deadline
                                            </span>
                                        @else
                                            <span
                                                class="bg-secondary text-white font-semibold px-2 py-0.5 rounded-full md:flex md:items-center text-xs">
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="hidden md:block h-3 w-3 mr-0.5" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                {{ $job->deadline->format('M d') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Middle: Description -->
                                <div class="h-auto md:h-10">
                                    <div
                                        class="[&>p]:text-sm [&>p]:text-justify [&>p]:line-clamp-4 md:[&>p]:line-clamp-2">
                                        {!! Str::markdown($job->description) !!}
                                    </div>
                                </div>

                                <!-- Bottom Row: Skills, Budget and CTA -->
                                <div
                                    class="flex flex-col space-y-3 sm:flex-row sm:items-center sm:justify-between sm:space-y-0 mt-3">
                                    <!-- Skills Tags -->
                                    <div class="overflow-hidden">
                                        <div class="flex flex-wrap gap-1">
                                            @php
                                                // Combine skills and software into a single array
                                                $allTags = [];
                                                if ($job->skills) {
                                                    $allTags = array_merge($allTags, $job->skills);
                                                }
                                                if ($job->software) {
                                                    $allTags = array_merge($allTags, $job->software);
                                                }

                                                // Limit to 3 items on very small screens, 5 on larger
                                                $displayCount = 5;
                                                $totalCount = count($allTags);
                                                $displayTags = array_slice($allTags, 0, $displayCount);
                                                $remainingCount = max(0, $totalCount - $displayCount);
                                            @endphp

                                            @foreach ($displayTags as $tag)
                                                <span
                                                    class="px-1.5 py-0.5 bg-neutral-100 text-neutral-700 text-xs rounded">
                                                    {{ $tag }}
                                                </span>
                                            @endforeach

                                            @if ($remainingCount > 0)
                                                <span
                                                    class="px-1.5 py-0.5 bg-neutral-200 text-neutral-600 text-xs rounded">
                                                    +{{ $remainingCount }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Budget and CTA -->
                                    <div
                                        class="flex items-center justify-between sm:justify-end sm:space-x-3 flex-shrink-0">
                                        <div>
                                            <p class="text-xs text-neutral-500 font-tertiary">Budget</p>
                                            <p class="text-primary font-secondary font-bold text-sm whitespace-nowrap">
                                                Ksh. {{ number_format($job->budget) }}
                                            </p>
                                        </div>
                                        <a href="{{ route('jobs.apply', $job->slug) }}"
                                            class="inline-flex items-center px-3 py-1.5 bg-secondary hover:bg-secondary/90 text-white rounded transition-colors text-xs font-semibold whitespace-nowrap">
                                            Apply
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 ml-1"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Empty State (if no jobs found) -->
                @if (count($jobs) === 0)
                    <div class="bg-white rounded-xl shadow p-6 text-center max-w-2xl mx-auto">
                        <div
                            class="mx-auto w-12 h-12 bg-neutral-100 rounded-full flex items-center justify-center mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-neutral-400" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-base font-semibold text-neutral-700 mb-1">No jobs found</h3>
                        <p class="text-sm text-neutral-500 mb-3">Try adjusting your search filters or check back later
                        </p>
                        <a href="{{ route('jobs.browse') }}"
                            class="text-secondary font-semibold hover:underline text-sm">
                            Clear filters
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Footer -->
        @include('partials\footer-secondary')
    </section>

    <!-- JavaScript for enhanced interactivity -->
    @push('scripts')
        <script>
            // Format currency
            document.addEventListener('DOMContentLoaded', function() {
                const budgetElements = document.querySelectorAll('.budget');
                budgetElements.forEach(element => {
                    const amount = parseFloat(element.textContent.replace('$', ''));
                    element.textContent = '$' + amount.toLocaleString();
                });
            });
        </script>
    @endpush
</x-app-layout>
