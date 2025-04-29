<!-- resources/views/jobBoard/jobs/partials/jobs-list.blade.php -->
@forelse ($jobs as $job)
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
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-white/80" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
            @endif
        </div>

        <!-- Job Content Section (Bottom on mobile, Right on desktop) -->
        <div class="flex-grow p-4 flex flex-col overflow-hidden">
            <!-- Top Row: Title and Deadline -->
            <div class="flex flex-col space-y-2 sm:flex-row sm:justify-between sm:items-start sm:space-y-0 mb-2">
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
                        <span class="bg-accent text-white text-xs font-semibold px-2 py-0.5 rounded-full">
                            No Fixed Deadline
                        </span>
                    @else
                        <span
                            class="bg-secondary text-white font-semibold px-2 py-0.5 rounded-full md:flex md:items-center text-xs">
                            <svg xmlns="http://www.w3.org/2000/svg" class="hidden md:block h-3 w-3 mr-0.5"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            {{ $job->deadline->format('M d') }}
                        </span>
                    @endif
                </div>
            </div>

            <!-- Middle: Description -->
            <div class="h-auto md:h-10 text-ellipsis">
                <div class="text-sm text-justify line-clamp-3 md:line-clamp-2 overflow-hidden">
                    {!! Str::markdown($job->description) !!}
                </div>
            </div>

            <!-- Bottom Row: Skills, Budget and CTA -->
            <div class="flex flex-col space-y-3 sm:flex-row sm:items-center sm:justify-between sm:space-y-0 mt-3">
                <!-- Skills Tags -->
                <div class="overflow-hidden">
                    <div class="flex flex-wrap gap-1">
                        @php
                            $allTags = [];
                            if ($job->skills) {
                                $allTags = array_merge($allTags, $job->skills);
                            }
                            if ($job->software) {
                                $allTags = array_merge($allTags, $job->software);
                            }

                            $displayCount = 5;
                            $totalCount = count($allTags);
                            $displayTags = array_slice($allTags, 0, $displayCount);
                            $remainingCount = max(0, $totalCount - $displayCount);
                        @endphp

                        @foreach ($displayTags as $tag)
                            <span class="px-1.5 py-0.5 bg-neutral-100 text-neutral-700 text-xs rounded">
                                {{ $tag }}
                            </span>
                        @endforeach

                        @if ($remainingCount > 0)
                            <span class="px-1.5 py-0.5 bg-neutral-200 text-neutral-600 text-xs rounded">
                                +{{ $remainingCount }}
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Budget and CTA -->
                <div class="flex items-center justify-between sm:justify-end sm:space-x-3 flex-shrink-0">
                    <div>
                        <p class="text-xs text-neutral-500 font-tertiary">Budget</p>
                        <p class="text-primary font-secondary font-bold text-sm whitespace-nowrap">
                            Ksh. {{ number_format($job->budget) }}
                        </p>
                    </div>
                    <a href="{{ route('jobs.apply', $job->slug) }}"
                        class="inline-flex items-center px-3 py-1.5 bg-secondary hover:bg-secondary/90 text-white rounded transition-colors text-xs font-semibold whitespace-nowrap">
                        Apply
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 ml-1" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
@empty
    <div class="bg-white rounded-xl shadow p-6 text-center max-w-2xl mx-auto">
        <div class="mx-auto w-12 h-12 bg-neutral-100 rounded-full flex items-center justify-center mb-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-neutral-400" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <h3 class="text-base font-semibold text-neutral-700 mb-1">No jobs found</h3>
        <p class="text-sm text-neutral-500 mb-3">Try adjusting your search filters or check back later</p>
        <button onclick="document.getElementById('reset-filters').click();"
            class="text-secondary font-semibold hover:underline text-sm">
            Clear filters
        </button>
    </div>
@endforelse

<div class="mt-6">
    {{ $jobs->links() }}
</div>
