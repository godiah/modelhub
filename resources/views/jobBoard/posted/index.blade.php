<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-tertiary font-semibold text-2xl text-primary leading-tight">
                {{ __('Jobs Posted') }}
            </h2>
            <a href="{{ route('jobs.create') }}"
                class="inline-flex items-center px-4 py-2 bg-secondary hover:bg-secondary/90 text-white font-medium rounded-lg transition-colors duration-200 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                        clip-rule="evenodd" />
                </svg>
                Post New Job
            </a>
        </div>
    </x-slot>

    <div class="py-10 bg-gradient-to-b from-neutral-50 to-white min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if ($postedJobs->isEmpty())
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
                        <select
                            class="rounded-lg border-neutral-300 text-neutral-700 text-sm focus:ring-primary focus:border-primary">
                            <option>All Jobs</option>
                            <option>Active Jobs</option>
                            <option>Closed Jobs</option>
                        </select>
                        <select
                            class="rounded-lg border-neutral-300 text-neutral-700 text-sm focus:ring-primary focus:border-primary">
                            <option>Sort by Newest</option>
                            <option>Sort by Deadline</option>
                            <option>Sort by Budget (High-Low)</option>
                            <option>Sort by Budget (Low-High)</option>
                        </select>
                    </div>
                </div>

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
                                                class="ml-3 px-3 py-1 text-xs font-medium rounded-full {{ $job->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $job->is_active ? 'Active' : 'Closed' }}
                                            </span>
                                        </div>

                                        <div class="mt-2 flex flex-wrap items-center text-sm text-neutral-500 gap-x-4">
                                            <div class="flex items-center">
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="h-4 w-4 mr-1.5 text-neutral-400" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                @if ($job->no_deadline)
                                                    <span>No deadline</span>
                                                @else
                                                    <span>Deadline: {{ $job->deadline->format('M d, Y') }}</span>
                                                @endif
                                            </div>
                                            <div class="flex items-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="#9ca3af"
                                                    stroke="currentColor" class="h-4 w-4 mr-1.5 text-neutral-400"
                                                    viewBox="0 0 512 512">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M512 80c0 18-14.3 34.6-38.4 48c-29.1 16.1-72.5 27.5-122.3 30.9c-3.7-1.8-7.4-3.5-11.3-5C300.6 137.4 248.2 128 192 128c-8.3 0-16.4 .2-24.5 .6l-1.1-.6C142.3 114.6 128 98 128 80c0-44.2 86-80 192-80S512 35.8 512 80zM160.7 161.1c10.2-.7 20.7-1.1 31.3-1.1c62.2 0 117.4 12.3 152.5 31.4C369.3 204.9 384 221.7 384 240c0 4-.7 7.9-2.1 11.7c-4.6 13.2-17 25.3-35 35.5c0 0 0 0 0 0c-.1 .1-.3 .1-.4 .2c0 0 0 0 0 0s0 0 0 0c-.3 .2-.6 .3-.9 .5c-35 19.4-90.8 32-153.6 32c-59.6 0-112.9-11.3-148.2-29.1c-1.9-.9-3.7-1.9-5.5-2.9C14.3 274.6 0 258 0 240c0-34.8 53.4-64.5 128-75.4c10.5-1.5 21.4-2.7 32.7-3.5zM416 240c0-21.9-10.6-39.9-24.1-53.4c28.3-4.4 54.2-11.4 76.2-20.5c16.3-6.8 31.5-15.2 43.9-25.5l0 35.4c0 19.3-16.5 37.1-43.8 50.9c-14.6 7.4-32.4 13.7-52.4 18.5c.1-1.8 .2-3.5 .2-5.3zm-32 96c0 18-14.3 34.6-38.4 48c-1.8 1-3.6 1.9-5.5 2.9C304.9 404.7 251.6 416 192 416c-62.8 0-118.6-12.6-153.6-32C14.3 370.6 0 354 0 336l0-35.4c12.5 10.3 27.6 18.7 43.9 25.5C83.4 342.6 135.8 352 192 352s108.6-9.4 148.1-25.9c7.8-3.2 15.3-6.9 22.4-10.9c6.1-3.4 11.8-7.2 17.2-11.2c1.5-1.1 2.9-2.3 4.3-3.4l0 3.4 0 5.7 0 26.3zm32 0l0-32 0-25.9c19-4.2 36.5-9.5 52.1-16c16.3-6.8 31.5-15.2 43.9-25.5l0 35.4c0 10.5-5 21-14.9 30.9c-16.3 16.3-45 29.7-81.3 38.4c.1-1.7 .2-3.5 .2-5.3zM192 448c56.2 0 108.6-9.4 148.1-25.9c16.3-6.8 31.5-15.2 43.9-25.5l0 35.4c0 44.2-86 80-192 80S0 476.2 0 432l0-35.4c12.5 10.3 27.6 18.7 43.9 25.5C83.4 438.6 135.8 448 192 448z" />
                                                </svg>
                                                <span>Budget: Ksh{{ number_format($job->budget) }}</span>
                                            </div>
                                            <div class="flex items-center">
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="h-4 w-4 mr-1.5 text-neutral-400" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                                </svg>
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

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-5">
                                    <div>
                                        <h4 class="text-sm font-medium text-neutral-700 flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                class="h-4 w-4 mr-1.5 text-secondary" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                            </svg>
                                            Skills Required
                                        </h4>
                                        <div class="mt-2 flex flex-wrap gap-1.5">
                                            @foreach ($job->skills as $skill)
                                                <span
                                                    class="px-2.5 py-1 text-xs font-medium rounded-full bg-blue-50 text-primary border border-blue-100">
                                                    {{ $skill }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-neutral-700 flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                class="h-4 w-4 mr-1.5 text-secondary" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8.25 2.25A.75.75 0 0 1 9 3v.75h2.25V3a.75.75 0 0 1 1.5 0v.75H15V3a.75.75 0 0 1 1.5 0v.75h.75a3 3 0 0 1 3 3v.75H21A.75.75 0 0 1 21 9h-.75v2.25H21a.75.75 0 0 1 0 1.5h-.75V15H21a.75.75 0 0 1 0 1.5h-.75v.75a3 3 0 0 1-3 3h-.75V21a.75.75 0 0 1-1.5 0v-.75h-2.25V21a.75.75 0 0 1-1.5 0v-.75H9V21a.75.75 0 0 1-1.5 0v-.75h-.75a3 3 0 0 1-3-3v-.75H3A.75.75 0 0 1 3 15h.75v-2.25H3a.75.75 0 0 1 0-1.5h.75V9H3a.75.75 0 0 1 0-1.5h.75v-.75a3 3 0 0 1 3-3h.75V3a.75.75 0 0 1 .75-.75ZM6 6.75A.75.75 0 0 1 6.75 6h10.5a.75.75 0 0 1 .75.75v10.5a.75.75 0 0 1-.75.75H6.75a.75.75 0 0 1-.75-.75V6.75Z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            Software Required
                                        </h4>
                                        <div class="mt-2 flex flex-wrap gap-1.5">
                                            @foreach ($job->software as $software)
                                                <span
                                                    class="px-2.5 py-1 text-xs font-medium rounded-full bg-purple-50 text-purple-700 border border-purple-100">
                                                    {{ $software }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-6 flex items-center justify-between pt-4 border-t border-neutral-100">
                                    <div class="flex space-x-4">
                                        <button
                                            class="flex items-center text-neutral-600 hover:text-primary transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1.5"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                                            </svg>
                                            Share
                                        </button>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <a href="{{ route('jobs.edit', ['job' => $job->slug]) }}"
                                            class="inline-flex items-center px-3 py-1.5 text-sm font-medium border border-neutral-300 rounded-lg hover:bg-neutral-50 text-neutral-700 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            Edit
                                        </a>
                                        <a href="{{ route('my-jobs.applications.index', ['slug' => $job->slug]) }}"
                                            class="inline-flex items-center px-3 py-1.5 text-sm font-medium bg-primary hover:bg-primary/90 text-white rounded-lg transition-colors shadow-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                            </svg>
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
        </div>
    </div>

    @include('partials\footer-secondary')
</x-app-layout>
