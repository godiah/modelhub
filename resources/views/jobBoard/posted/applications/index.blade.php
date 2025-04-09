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
                            {{ $job->is_active ? 'Active' : 'Inactive' }}
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
                                    <input type="text" placeholder="Search applications"
                                        class="pl-9 pr-3 py-2 border border-neutral-300 rounded-md text-sm focus:ring-2 focus:ring-secondary focus:border-secondary">
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        class="h-4 w-4 absolute left-3 top-2.5 text-neutral-400" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <select
                                    class="pl-3 pr-8 py-2 border border-neutral-300 rounded-md text-sm focus:ring-2 focus:ring-secondary focus:border-secondary">
                                    <option>All Status</option>
                                    <option>Submitted</option>
                                    <option>Reviewed</option>
                                    <option>Hired</option>
                                    <option>Rejected</option>
                                </select>
                            </div>
                        @endif
                    </div>

                    @if ($applications->isEmpty())
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
                        <div class="space-y-6">
                            @foreach ($applications as $application)
                                <div
                                    class="border border-neutral-200 rounded-lg p-6 hover:shadow-md transition bg-gradient-to-r from-white to-neutral-50/50">
                                    <!-- Applicant Info -->
                                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start">
                                        <div class="flex items-center">
                                            <!-- Applicant Avatar -->
                                            <div
                                                class="flex items-center justify-center h-10 w-10 rounded-full bg-primary/10 text-primary font-medium mr-3 border border-primary/20">
                                                {{ $application->applicant->getInitials() }}
                                            </div>

                                            <div>
                                                <h4 class="font-secondary font-medium text-neutral-900">
                                                    {{ $application->applicant->name }}
                                                </h4>
                                                <p class="text-sm text-neutral-500 flex items-center">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1"
                                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    Applied {{ $application->created_at->diffForHumans() }}
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Application Status -->
                                        <div class="flex items-center mt-4 sm:mt-0">
                                            <span class="mr-3 flex items-center">
                                                @if ($application->status === 'submitted')
                                                    <span
                                                        class="px-3 py-1 inline-flex items-center text-sm font-medium rounded-full bg-yellow-100 text-yellow-800 border border-yellow-200">
                                                        <span class="h-2 w-2 rounded-full bg-yellow-500 mr-2"></span>
                                                        New Application
                                                    </span>
                                                @elseif ($application->status === 'reviewed')
                                                    <span
                                                        class="px-3 py-1 inline-flex items-center text-sm font-medium rounded-full bg-blue-100 text-blue-800 border border-blue-200">
                                                        <span class="h-2 w-2 rounded-full bg-blue-500 mr-2"></span>
                                                        Reviewed
                                                    </span>
                                                @elseif ($application->status === 'hired')
                                                    <span
                                                        class="px-3 py-1 inline-flex items-center text-sm font-medium rounded-full bg-green-100 text-green-800 border border-green-200">
                                                        <span class="h-2 w-2 rounded-full bg-green-600 mr-2"></span>
                                                        Hired
                                                    </span>
                                                @elseif ($application->status === 'rejected')
                                                    <span
                                                        class="px-3 py-1 inline-flex items-center text-sm font-medium rounded-full bg-red-100 text-red-800 border border-red-200">
                                                        <span class="h-2 w-2 rounded-full bg-red-500 mr-2"></span>
                                                        Rejected
                                                    </span>
                                                @else
                                                    <span
                                                        class="px-3 py-1 inline-flex items-center text-sm font-medium rounded-full bg-neutral-100 text-neutral-800 border border-neutral-200">
                                                        <span class="h-2 w-2 rounded-full bg-neutral-500 mr-2"></span>
                                                        {{ ucfirst($application->status) }}
                                                    </span>
                                                @endif
                                            </span>

                                            <div x-data="{ open: false }" class="relative">
                                                <button @click="open = !open"
                                                    class="p-1 rounded-full hover:bg-neutral-100">
                                                    <svg class="h-5 w-5 text-neutral-400 hover:text-primary"
                                                        fill="currentColor" viewBox="0 0 20 20">
                                                        <path
                                                            d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z">
                                                        </path>
                                                    </svg>
                                                </button>

                                                <div x-show="open" @click.away="open = false"
                                                    class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border border-neutral-200">
                                                    <div class="py-1">
                                                        <form method="POST"
                                                            action="{{ route('my-jobs.applications.update-status', $application->id) }}">
                                                            @csrf
                                                            @method('PATCH')
                                                            <input type="hidden" name="status" value="reviewed">
                                                            <button type="submit"
                                                                class="block w-full text-left px-4 py-2 text-sm text-neutral-700 hover:bg-neutral-50 hover:text-primary">
                                                                <span class="flex items-center">
                                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                                        class="h-4 w-4 mr-2 text-blue-500"
                                                                        fill="none" viewBox="0 0 24 24"
                                                                        stroke="currentColor">
                                                                        <path stroke-linecap="round"
                                                                            stroke-linejoin="round" stroke-width="2"
                                                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                    </svg>
                                                                    Mark as Reviewed
                                                                </span>
                                                            </button>
                                                        </form>

                                                        <form method="POST"
                                                            action="{{ route('my-jobs.applications.update-status', $application->id) }}">
                                                            @csrf
                                                            @method('PATCH')
                                                            <input type="hidden" name="status" value="hired">
                                                            <button type="submit"
                                                                class="block w-full text-left px-4 py-2 text-sm text-neutral-700 hover:bg-neutral-50 hover:text-primary">
                                                                <span class="flex items-center">
                                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                                        class="h-4 w-4 mr-2 text-green-500"
                                                                        fill="none" viewBox="0 0 24 24"
                                                                        stroke="currentColor">
                                                                        <path stroke-linecap="round"
                                                                            stroke-linejoin="round" stroke-width="2"
                                                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                    </svg>
                                                                    Mark as Hired
                                                                </span>
                                                            </button>
                                                        </form>

                                                        <form method="POST"
                                                            action="{{ route('my-jobs.applications.update-status', $application->id) }}">
                                                            @csrf
                                                            @method('PATCH')
                                                            <input type="hidden" name="status" value="rejected">
                                                            <button type="submit"
                                                                class="block w-full text-left px-4 py-2 text-sm text-neutral-700 hover:bg-neutral-50 hover:text-primary">
                                                                <span class="flex items-center">
                                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                                        class="h-4 w-4 mr-2 text-red-500"
                                                                        fill="none" viewBox="0 0 24 24"
                                                                        stroke="currentColor">
                                                                        <path stroke-linecap="round"
                                                                            stroke-linejoin="round" stroke-width="2"
                                                                            d="M6 18L18 6M6 6l12 12" />
                                                                    </svg>
                                                                    Mark as Rejected
                                                                </span>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 mt-4">
                                        <!-- Proposal -->
                                        <div class="md:col-span-9">
                                            <div class="bg-white rounded-lg border border-neutral-200 p-4">
                                                <h4
                                                    class="text-sm font-secondary font-medium text-neutral-800 flex items-center">
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="h-4 w-4 mr-1 text-secondary" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                                    </svg>
                                                    Proposal
                                                </h4>
                                                <div class="mt-2 mb-2 text-sm">
                                                    @if ($application->proposal)
                                                        <div
                                                            class="text-neutral-700 text-justify bg-neutral-50 p-4 rounded-md border border-neutral-100 max-h-32 overflow-y-auto">
                                                            {!! nl2br(e($application->proposal)) !!}
                                                        </div>
                                                    @else
                                                        <div
                                                            class="flex items-center justify-center py-6 text-neutral-400 bg-neutral-50 rounded-md border border-neutral-100">
                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                                viewBox="0 0 24 24" stroke-width="1.5"
                                                                stroke="currentColor" class="h-6 w-6 mr-2">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                                            </svg>
                                                            <span class="text-sm">No proposal provided by the
                                                                applicant</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Bid Amount & Actions -->
                                        <div class="md:col-span-3">
                                            <div
                                                class="bg-white rounded-lg border border-neutral-200 p-4 h-full flex flex-col justify-between">
                                                <!-- Bid Amount -->
                                                @if (isset($application->offer_amount))
                                                    <div>
                                                        <h4
                                                            class="text-sm font-secondary font-medium text-neutral-800 flex items-center">
                                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                                stroke="currentColor" fill="#f59e0b"
                                                                class="h-4 w-4 mr-1.5 text-accent"
                                                                viewBox="0 0 512 512">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M512 80c0 18-14.3 34.6-38.4 48c-29.1 16.1-72.5 27.5-122.3 30.9c-3.7-1.8-7.4-3.5-11.3-5C300.6 137.4 248.2 128 192 128c-8.3 0-16.4 .2-24.5 .6l-1.1-.6C142.3 114.6 128 98 128 80c0-44.2 86-80 192-80S512 35.8 512 80zM160.7 161.1c10.2-.7 20.7-1.1 31.3-1.1c62.2 0 117.4 12.3 152.5 31.4C369.3 204.9 384 221.7 384 240c0 4-.7 7.9-2.1 11.7c-4.6 13.2-17 25.3-35 35.5c0 0 0 0 0 0c-.1 .1-.3 .1-.4 .2c0 0 0 0 0 0s0 0 0 0c-.3 .2-.6 .3-.9 .5c-35 19.4-90.8 32-153.6 32c-59.6 0-112.9-11.3-148.2-29.1c-1.9-.9-3.7-1.9-5.5-2.9C14.3 274.6 0 258 0 240c0-34.8 53.4-64.5 128-75.4c10.5-1.5 21.4-2.7 32.7-3.5zM416 240c0-21.9-10.6-39.9-24.1-53.4c28.3-4.4 54.2-11.4 76.2-20.5c16.3-6.8 31.5-15.2 43.9-25.5l0 35.4c0 19.3-16.5 37.1-43.8 50.9c-14.6 7.4-32.4 13.7-52.4 18.5c.1-1.8 .2-3.5 .2-5.3zm-32 96c0 18-14.3 34.6-38.4 48c-1.8 1-3.6 1.9-5.5 2.9C304.9 404.7 251.6 416 192 416c-62.8 0-118.6-12.6-153.6-32C14.3 370.6 0 354 0 336l0-35.4c12.5 10.3 27.6 18.7 43.9 25.5C83.4 342.6 135.8 352 192 352s108.6-9.4 148.1-25.9c7.8-3.2 15.3-6.9 22.4-10.9c6.1-3.4 11.8-7.2 17.2-11.2c1.5-1.1 2.9-2.3 4.3-3.4l0 3.4 0 5.7 0 26.3zm32 0l0-32 0-25.9c19-4.2 36.5-9.5 52.1-16c16.3-6.8 31.5-15.2 43.9-25.5l0 35.4c0 10.5-5 21-14.9 30.9c-16.3 16.3-45 29.7-81.3 38.4c.1-1.7 .2-3.5 .2-5.3zM192 448c56.2 0 108.6-9.4 148.1-25.9c16.3-6.8 31.5-15.2 43.9-25.5l0 35.4c0 44.2-86 80-192 80S0 476.2 0 432l0-35.4c12.5 10.3 27.6 18.7 43.9 25.5C83.4 438.6 135.8 448 192 448z" />
                                                            </svg>
                                                            Bid Amount
                                                        </h4>
                                                        <p class="mt-1 text-xl font-semibold text-primary">
                                                            Ksh{{ number_format($application->offer_amount, 2) }}
                                                        </p>
                                                        <p class="text-xs text-neutral-500">
                                                            @php
                                                                $offerAmount = $application->offer_amount;
                                                                $budget = $job->budget;
                                                                $comparison = '';

                                                                if ($budget == 0) {
                                                                    $comparison =
                                                                        'Budget is zero, cannot calculate percentage difference';
                                                                } elseif ($offerAmount == 0) {
                                                                    $comparison =
                                                                        'Offer amount is zero, cannot calculate percentage difference';
                                                                } else {
                                                                    if ($offerAmount > $budget) {
                                                                        $percentageDifference =
                                                                            ($offerAmount / $budget) * 100 - 100;
                                                                        $roundedDifference = round(
                                                                            $percentageDifference,
                                                                        );
                                                                        $comparison =
                                                                            $roundedDifference . '% above budget';
                                                                    } else {
                                                                        $percentageDifference =
                                                                            ($budget / $offerAmount) * 100 - 100;
                                                                        $roundedDifference = round(
                                                                            $percentageDifference,
                                                                        );
                                                                        $comparison =
                                                                            $roundedDifference . '% below budget';
                                                                    }
                                                                }
                                                            @endphp
                                                            {{ $comparison }}
                                                        </p>
                                                    </div>
                                                @else
                                                    <div>
                                                        <h4
                                                            class="text-sm font-secondary font-medium text-neutral-800 flex items-center">
                                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                                class="h-4 w-4 mr-1 text-accent" fill="none"
                                                                viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                            </svg>
                                                            Bid Amount
                                                        </h4>
                                                        <p class="text-sm text-neutral-500 italic">
                                                            No bid provided
                                                        </p>
                                                    </div>
                                                @endif

                                                <!-- Actions -->
                                                <div class="mt-3">
                                                    <a href="{{ route('my-jobs.applications.show', ['application' => $application->id]) }}"
                                                        class="block w-full text-center px-4 py-2 text-sm font-medium bg-primary text-white rounded hover:bg-primary/90 transition shadow-sm">
                                                        View Full Details
                                                    </a>
                                                    <a href="#"
                                                        class="block w-full text-center px-4 py-2 text-sm font-medium bg-white text-primary border border-primary rounded mt-2 hover:bg-primary/5 transition">
                                                        Contact Applicant
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach

                            <!-- Pagination -->

                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @include('partials\footer-secondary')
</x-app-layout>
