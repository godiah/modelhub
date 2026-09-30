@if ($postedJobs->isEmpty() && $hasFilters)
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-neutral-200 font-main">
        <div class="p-8 text-center">
            <p class="text-neutral-600">No jobs found matching your filters.</p>
            <a href="#" id="clearFilters" class="mt-4 inline-block text-primary hover:text-primary/80 underline">
                Clear filters to see all jobs
            </a>
        </div>
    </div>
@else
    <div class="grid grid-cols-1 gap-6">
        @foreach ($postedJobs as $job)
            <div
                class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-neutral-200 hover:shadow-md transition-all duration-300 group">
                <div class="p-6">
                    <div class="flex justify-between items-start">
                        <div class="flex-grow">
                            <div class="flex items-center">
                                <h3
                                    class="text-xl font-semibold font-tertiary text-neutral-800 group-hover:text-primary transition-colors">
                                    {{ $job->title }}</h3>
                                <span
                                    class="font-main ml-3 px-3 py-1 text-xs font-medium rounded-full {{ $job->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $job->is_active ? 'Active' : 'Closed' }}
                                </span>
                            </div>

                            <div
                                class="mt-2 flex flex-wrap items-center text-sm text-neutral-500 gap-x-4 font-secondary">
                                <div class="flex items-center">
                                    <x-icon name="calendar" class="h-4 w-4 mr-1.5 text-neutral-400" />
                                    @if ($job->no_deadline)
                                        <span>No deadline</span>
                                    @else
                                        <span>Deadline: {{ $job->deadline->format('M d, Y') }}</span>
                                    @endif
                                </div>
                                <div class="flex items-center">
                                    <x-icon name="coins" class="h-4 w-4 mr-1.5 text-neutral-400" fill="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    <span>Budget: Ksh{{ number_format($job->budget, 2) }}</span>
                                </div>
                                <div class="flex items-center">
                                    <x-icon name="users" class="h-4 w-4 mr-1.5 text-neutral-400" />
                                    <span>{{ $job->applicants_count }} applications</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div x-data="{ expanded: false, shouldShowMore: false }" x-init="$nextTick(() => {
                        const el = $refs.content;
                        shouldShowMore = el.scrollHeight > (window.innerWidth < 768 ? 80 : 40); // Approximate 5rem/2.5rem in pixels
                    })"
                        class="mt-4 bg-neutral-50 rounded-lg p-4 border border-neutral-100">

                        <!-- Markdown content div with reference -->
                        <div x-ref="content"
                            class="prose prose-sm max-w-none text-neutral-700 text-sm transition-all duration-300 overflow-hidden font-main 
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

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-5">
                        <div>
                            <h4 class="text-sm font-medium text-neutral-700 flex items-center font-main">
                                <x-icon name="light-bulb" class="h-4 w-4 mr-1.5 text-secondary" />
                                Skills Required
                            </h4>
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                @foreach ($job->skills as $skill)
                                    <span
                                        class="px-2.5 py-1 text-xs font-medium font-secondary rounded-full bg-blue-50 text-primary border border-blue-100">
                                        {{ $skill }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <h4 class="text-sm font-medium text-neutral-700 flex items-center font-main">
                                <x-icon name="cpu-chip" class="h-4 w-4 mr-1.5 text-secondary" />
                                Software Required
                            </h4>
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                @foreach ($job->software as $software)
                                    <span
                                        class="px-2.5 py-1 text-xs font-medium font-secondary rounded-full bg-purple-50 text-purple-700 border border-purple-100">
                                        {{ $software }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-between pt-4 border-t border-neutral-100">
                        <div class="flex space-x-2">
                            <a href="{{ route('jobs.show', ['job' => $job->slug]) }}"
                                class="font-main text-sm flex items-center text-neutral-600 hover:text-primary transition-colors">
                                <x-icon name="briefcase" class="h-5 w-5 mr-1.5" stroke-width="1.5" />
                                View Job
                            </a>
                            @if (!$job->is_active)
                                <form action="{{ route('my-jobs.archive', $job) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                        class="inline-flex items-center px-4 py-2 text-neutral-600  hover:text-primary transition-colors duration-200 font-main text-sm">
                                        <x-icon name="archive-box" class="h-5 w-5 mr-1.5" />
                                        Archive Job
                                    </button>
                                </form>
                            @endif
                        </div>
                        <div class="flex items-center space-x-2 font-main">
                            <a href="{{ route('jobs.edit', ['job' => $job->slug]) }}"
                                class="inline-flex items-center px-3 py-1.5 text-sm font-medium border border-neutral-300 rounded-lg hover:bg-neutral-50 text-neutral-700 transition-colors">
                                <x-icon name="pencil-square" class="h-4 w-4 mr-1.5" />
                                Edit
                            </a>
                            <a href="{{ route('my-jobs.applications.index', ['slug' => $job->slug]) }}"
                                class="inline-flex items-center px-3 py-1.5 text-sm font-medium bg-primary hover:bg-primary/90 text-white rounded-lg transition-colors shadow-sm">
                                <x-icon name="user-group" class="h-4 w-4 mr-1.5" />
                                View Applications
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-8">
        {{ $postedJobs->links() }}
    </div>
@endif
