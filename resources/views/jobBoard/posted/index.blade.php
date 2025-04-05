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

    <div class="py-12 bg-gradient-to-b from-neutral-50 to-white min-h-screen">
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
                            <option>Inactive Jobs</option>
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
                                                {{ $job->is_active ? 'Active' : 'Inactive' }}
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
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="h-4 w-4 mr-1.5 text-neutral-400" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
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
                                                <span>{{ rand(3, 15) }} applications</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 bg-neutral-50 p-4 rounded-lg border border-neutral-100">
                                    <div class="[&>p]:text-sm [&>p]:text-neutral-600 [&>p]:line-clamp-3">
                                        {!! Str::markdown($job->description) !!}
                                    </div>
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
                                                    d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
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
                                        <button
                                            class="flex items-center text-neutral-600 hover:text-primary transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1.5"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                            </svg>
                                            Duplicate
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

                {{-- <div class="mt-8 flex justify-center">
                    {{ $postedJobs->links() }}
                </div> --}}
            @endif
        </div>
    </div>

    @include('partials\footer-secondary')
</x-app-layout>
