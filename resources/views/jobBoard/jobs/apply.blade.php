<x-app-layout>

    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-tertiary font-bold text-xl text-primary leading-tight">
                {{ __('3D Project Application') }}
            </h2>
            <a href="{{ route('jobs.browse') }}"
                class="inline-flex items-center px-4 py-2 bg-secondary text-white rounded-lg hover:bg-secondary/90 transition-colors duration-200 font-main text-sm font-medium shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                Browse More Jobs
            </a>
        </div>
    </x-slot>

    <section>
        <div class="container mx-auto max-w-7xl px-4 mb-24 pt-16">
            <!-- Main Content Container -->
            <div class="space-y-8">
                <!-- Job Details Card -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-neutral-200">
                    <!-- Main Job Card Container -->
                    <div>
                        <!-- Job Header  -->
                        <div class="p-6 sm:p-8">
                            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                                <!-- Title and Job User-Poster Info -->
                                <div class="flex-1">
                                    <span
                                        class="inline-flex items-center px-3 py-1 text-xs font-medium rounded-full bg-secondary/10 text-secondary mb-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-1" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                        3D Modelling Jobs
                                    </span>

                                    <h1 class="text-2xl sm:text-3xl font-bold font-tertiary text-neutral-800 mb-4">
                                        {{ $job->title }}</h1>

                                    <div class="flex items-center">
                                        <div
                                            class="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center font-bold text-lg">
                                            {{ $job->user->getInitials() }}
                                        </div>
                                        <div class="ml-3">
                                            <span
                                                class="text-neutral-700 font-semibold font-main">{{ $job->user->name }}</span>
                                            <div class="flex items-center text-sm text-neutral-500 mt-1">
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="h-4 w-4 mr-1 text-secondary" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                Posted on {{ $job->created_at->format('M d, Y') }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Job Details Card -->
                        <div class="border-t border-neutral-200">
                            <!-- Details Section -->
                            <div class="p-6 space-y-6">
                                <!-- Detail Cards with Interactive Hover -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                    <!-- Deadline Card -->
                                    <div
                                        class="group bg-neutral-50 rounded-xl border border-neutral-100 p-5 hover:border-secondary hover:bg-white transition-all duration-300 hover:shadow-md">
                                        <div class="flex items-start">
                                            <div
                                                class="rounded-lg bg-secondary/10 p-3 group-hover:bg-secondary/20 transition-colors duration-300">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-secondary"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                            <div class="ml-4">
                                                <p
                                                    class="text-xs uppercase tracking-wider text-neutral-500 font-main font-medium">
                                                    Deadline</p>
                                                @if ($job->no_deadline)
                                                    <p class="text-neutral-800 font-secondary mt-1">No Fixed Deadline
                                                    </p>
                                                @else
                                                    <p class="text-neutral-800 font-secondary font-bold mt-1">
                                                        {{ $job->deadline->format('F j, Y') }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Applicants Card -->
                                    <div
                                        class="group bg-neutral-50 rounded-xl border border-neutral-100 p-5 hover:border-secondary hover:bg-white transition-all duration-300 hover:shadow-md">
                                        <div class="flex items-start">
                                            <div
                                                class="rounded-lg bg-secondary/10 p-3 group-hover:bg-secondary/20 transition-colors duration-300">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-secondary"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                                </svg>
                                            </div>
                                            <div class="ml-4">
                                                <p
                                                    class="text-xs uppercase tracking-wider text-neutral-500 font-main font-medium">
                                                    Applicants</p>
                                                <div class="flex items-center mt-1">
                                                    <p class="text-neutral-800 font-secondary font-bold">
                                                        {{ $job->applicants_count }}</p>
                                                    <span class="text-neutral-600 ml-1 font-main">applied</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Budget Card -->
                                    <div
                                        class="group bg-neutral-50 rounded-xl border border-neutral-100 p-5 hover:border-accent hover:bg-white transition-all duration-300 hover:shadow-md">
                                        <div class="flex items-start">
                                            <div
                                                class="rounded-lg bg-accent/10 p-3 group-hover:bg-accent/20 transition-colors duration-300">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6"
                                                    viewBox="0 0 512 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.-->
                                                    <path fill="#f59e0b"
                                                        d="M512 80c0 18-14.3 34.6-38.4 48c-29.1 16.1-72.5 27.5-122.3 30.9c-3.7-1.8-7.4-3.5-11.3-5C300.6 137.4 248.2 128 192 128c-8.3 0-16.4 .2-24.5 .6l-1.1-.6C142.3 114.6 128 98 128 80c0-44.2 86-80 192-80S512 35.8 512 80zM160.7 161.1c10.2-.7 20.7-1.1 31.3-1.1c62.2 0 117.4 12.3 152.5 31.4C369.3 204.9 384 221.7 384 240c0 4-.7 7.9-2.1 11.7c-4.6 13.2-17 25.3-35 35.5c0 0 0 0 0 0c-.1 .1-.3 .1-.4 .2c0 0 0 0 0 0s0 0 0 0c-.3 .2-.6 .3-.9 .5c-35 19.4-90.8 32-153.6 32c-59.6 0-112.9-11.3-148.2-29.1c-1.9-.9-3.7-1.9-5.5-2.9C14.3 274.6 0 258 0 240c0-34.8 53.4-64.5 128-75.4c10.5-1.5 21.4-2.7 32.7-3.5zM416 240c0-21.9-10.6-39.9-24.1-53.4c28.3-4.4 54.2-11.4 76.2-20.5c16.3-6.8 31.5-15.2 43.9-25.5l0 35.4c0 19.3-16.5 37.1-43.8 50.9c-14.6 7.4-32.4 13.7-52.4 18.5c.1-1.8 .2-3.5 .2-5.3zm-32 96c0 18-14.3 34.6-38.4 48c-1.8 1-3.6 1.9-5.5 2.9C304.9 404.7 251.6 416 192 416c-62.8 0-118.6-12.6-153.6-32C14.3 370.6 0 354 0 336l0-35.4c12.5 10.3 27.6 18.7 43.9 25.5C83.4 342.6 135.8 352 192 352s108.6-9.4 148.1-25.9c7.8-3.2 15.3-6.9 22.4-10.9c6.1-3.4 11.8-7.2 17.2-11.2c1.5-1.1 2.9-2.3 4.3-3.4l0 3.4 0 5.7 0 26.3zm32 0l0-32 0-25.9c19-4.2 36.5-9.5 52.1-16c16.3-6.8 31.5-15.2 43.9-25.5l0 35.4c0 10.5-5 21-14.9 30.9c-16.3 16.3-45 29.7-81.3 38.4c.1-1.7 .2-3.5 .2-5.3zM192 448c56.2 0 108.6-9.4 148.1-25.9c16.3-6.8 31.5-15.2 43.9-25.5l0 35.4c0 44.2-86 80-192 80S0 476.2 0 432l0-35.4c12.5 10.3 27.6 18.7 43.9 25.5C83.4 438.6 135.8 448 192 448z" />
                                                </svg>

                                            </div>
                                            <div class="ml-4">
                                                <p
                                                    class="text-xs uppercase tracking-wider text-neutral-500 font-main font-medium">
                                                    Budget</p>
                                                <p class="text-primary font-tertiary font-bold text-xl mt-1">
                                                    Ksh.{{ number_format($job->budget) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Job Description Content -->
                        <div class="px-5 py-2">
                            <div class="flex items-center mb-4">
                                <div class="h-px bg-neutral-200 flex-grow"></div>
                                <span
                                    class="px-4 text-sm font-main uppercase tracking-wider text-neutral-800 flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-secondary mr-2"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Job Description</span>
                                <div class="h-px bg-neutral-200 flex-grow"></div>
                            </div>

                            <!-- Markdown Content -->
                            <div class="prose prose-neutral max-w-none font-main">
                                <div
                                    class="text-neutral-700 [&>h1]:text-xl [&>h1]:font-tertiary [&>h1]:font-bold [&>h1]:text-primary [&>h1]:mt-6 [&>h1]:mb-4
                                        [&>h2]:text-lg [&>h2]:font-tertiary [&>h2]:font-semibold [&>h2]:text-primary/90 [&>h2]:mt-5 [&>h2]:mb-3
                                        [&>h3]:text-base [&>h3]:font-tertiary [&>h3]:font-medium [&>h3]:text-neutral-800 [&>h3]:mt-4 [&>h3]:mb-2
                                        [&>p]:text-base [&>p]:leading-relaxed [&>p]:text-neutral-700 [&>p]:mb-4
                                        [&>ul]:list-disc [&>ul]:pl-5 [&>ul]:mb-4 [&>ul]:text-neutral-700
                                        [&>ol]:list-decimal [&>ol]:pl-5 [&>ol]:mb-4 [&>ol]:text-neutral-700
                                        [&>li]:mb-2 [&>a]:text-secondary [&>a]:underline [&>a]:font-medium [&>p]:text-justify">
                                    {!! Str::markdown($job->description) !!}
                                </div>
                            </div>

                            <!-- Tags Section for Required Skills/Qualifications -->
                            @if (isset($job->tags) && count($job->tags) > 0)
                                <div class="mt-8">
                                    <div class="flex items-center mb-4">
                                        <div class="h-px bg-neutral-200 flex-grow"></div>
                                        <span
                                            class="px-4 text-xs font-main uppercase tracking-wider text-neutral-500">Required
                                            Skills</span>
                                        <div class="h-px bg-neutral-200 flex-grow"></div>
                                    </div>

                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($job->tags as $tag)
                                            <span
                                                class="px-3 py-1.5 bg-secondary/10 text-secondary rounded-lg text-sm font-main flex items-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                                {{ $tag }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Skills & Software Section with Tabs -->
                        <div class="px-6 sm:px-8">
                            <!-- Modern Tabs Navigation -->
                            <div class="px-6 sm:px-8 pt-6 pb-0">
                                <div class="flex space-x-1 border-b border-neutral-200">
                                    <button
                                        class="tab-button active flex items-center px-5 py-3 text-sm font-main font-medium text-primary border-b-2 border-primary relative -mb-px transition-all duration-200 focus:outline-none"
                                        data-tab="skills">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                        </svg>
                                        Required Skills
                                        <span
                                            class="absolute -top-1 -right-1 bg-accent text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                                            {{ count($job->skills ?? []) }}
                                        </span>
                                    </button>

                                    <button
                                        class="tab-button flex items-center px-5 py-3 text-sm font-main font-medium text-neutral-500 hover:text-neutral-700 border-b-2 border-transparent hover:border-neutral-300 relative -mb-px transition-all duration-200 focus:outline-none"
                                        data-tab="software">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                            fill="currentColor" class="h-4 w-4 mr-2">
                                            <path d="M16.5 7.5h-9v9h9v-9Z" />
                                            <path fill-rule="evenodd"
                                                d="M8.25 2.25A.75.75 0 0 1 9 3v.75h2.25V3a.75.75 0 0 1 1.5 0v.75H15V3a.75.75 0 0 1 1.5 0v.75h.75a3 3 0 0 1 3 3v.75H21A.75.75 0 0 1 21 9h-.75v2.25H21a.75.75 0 0 1 0 1.5h-.75V15H21a.75.75 0 0 1 0 1.5h-.75v.75a3 3 0 0 1-3 3h-.75V21a.75.75 0 0 1-1.5 0v-.75h-2.25V21a.75.75 0 0 1-1.5 0v-.75H9V21a.75.75 0 0 1-1.5 0v-.75h-.75a3 3 0 0 1-3-3v-.75H3A.75.75 0 0 1 3 15h.75v-2.25H3a.75.75 0 0 1 0-1.5h.75V9H3a.75.75 0 0 1 0-1.5h.75v-.75a3 3 0 0 1 3-3h.75V3a.75.75 0 0 1 .75-.75ZM6 6.75A.75.75 0 0 1 6.75 6h10.5a.75.75 0 0 1 .75.75v10.5a.75.75 0 0 1-.75.75H6.75a.75.75 0 0 1-.75-.75V6.75Z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        Required Software
                                        <span
                                            class="absolute -top-1 -right-1 bg-neutral-400 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                                            {{ count($job->software ?? []) }}
                                        </span>
                                    </button>
                                </div>
                            </div>

                            <!-- Tab Contents -->
                            <div class="p-6 sm:px-8 sm:pb-8">
                                <!-- Skills Content -->
                                <div class="tab-content skills-content" data-content="skills">
                                    @if (!empty($job->skills) && count($job->skills) > 0)
                                        <div class="grid grid-cols-2  md:grid-cols-4 lg:grid-cols-5 gap-3">
                                            @foreach ($job->skills as $skill)
                                                <div
                                                    class="group flex items-center px-4 py-3 bg-primary/5 hover:bg-primary/10 text-primary rounded-lg transition-all duration-200 border border-primary/10 hover:border-primary/20">
                                                    <span
                                                        class="text-sm font-medium font-secondary">{{ $skill }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div
                                            class="flex items-center justify-center p-8 bg-neutral-50 rounded-lg border border-neutral-100">
                                            <div class="text-center">
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="h-10 w-10 text-neutral-400 mx-auto mb-3" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                </svg>
                                                <p class="text-neutral-600 font-main">No specific skills mentioned for
                                                    this position</p>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <!-- Software Content -->
                                <div class="tab-content software-content hidden" data-content="software">
                                    @if (!empty($job->software) && count($job->software) > 0)
                                        <div class="grid grid-cols-2  md:grid-cols-4 lg:grid-cols-6 gap-3">
                                            @foreach ($job->software as $software)
                                                <div
                                                    class="group flex items-center px-4 py-3 bg-secondary/5 hover:bg-secondary/10 text-secondary rounded-lg transition-all duration-200 border border-secondary/10 hover:border-secondary/20">
                                                    <span
                                                        class="text-sm font-medium font-secondary">{{ $software }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div
                                            class="flex items-center justify-center p-8 bg-neutral-50 rounded-lg border border-neutral-100">
                                            <div class="text-center">
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="h-10 w-10 text-neutral-400 mx-auto mb-3" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                </svg>
                                                <p class="text-neutral-600 font-main">No specific software required for
                                                    this position</p>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <!-- Info Badge (Optional) -->
                                <div class="mt-6 flex items-center justify-center">
                                    <span
                                        class="px-4 py-2 bg-neutral-100 text-neutral-600 rounded-full text-xs font-main inline-flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="h-4 w-4 mr-1.5 text-neutral-500" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        These requirements are preferred but not mandatory for all applicants
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Preview Image -->
                        <div>
                            <!-- Section Header -->
                            <h2
                                class="text-neutral-800 font-tertiary font-bold text-xl flex items-center justify-center mx-auto">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-secondary mr-2"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                Project Preview
                            </h2>

                            <!-- Image Container -->
                            <div class="pt-2 sm:pt-4 px-4 pb-4 sm:pb-6 sm:px-6">
                                @if ($job->images || $job->jobImages->isNotEmpty())
                                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                        <!-- Main Preview Image -->
                                        @if ($job->images)
                                            <div class="relative group">
                                                <div
                                                    class="overflow-hidden border border-neutral-200 shadow-sm group-hover:shadow-md transition-all duration-300 w-full aspect-square rounded-lg">
                                                    <img src="{{ asset('storage/' . $job->images) }}"
                                                        alt="{{ $job->title }} preview"
                                                        class="w-full h-full object-cover transform group-hover:scale-[1.02] transition-transform duration-500">
                                                </div>

                                                <!-- Zoom Button -->
                                                <button
                                                    class="absolute top-2 right-2 p-1.5 bg-white/90 backdrop-blur-sm rounded-full shadow-sm border border-neutral-100 text-neutral-700 hover:text-primary transition-colors duration-200"
                                                    onclick="openImageModal('{{ asset('storage/' . $job->images) }}')">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
                                                    </svg>
                                                </button>

                                                <!-- Download Button -->
                                                <div class="mt-3">
                                                    <button
                                                        class="w-full flex items-center justify-center px-3 py-1.5 bg-primary text-white rounded-lg shadow-sm hover:bg-primary-dark transition-colors duration-200 text-sm"
                                                        onclick="downloadImage('{{ asset('storage/' . $job->images) }}')">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                        </svg>
                                                        Download Sample
                                                    </button>
                                                </div>
                                            </div>
                                        @endif

                                        <!-- Additional Images -->
                                        @foreach ($job->jobImages as $image)
                                            <div class="relative group">
                                                <div
                                                    class="overflow-hidden border border-neutral-200 shadow-sm group-hover:shadow-md transition-all duration-300 w-full aspect-square rounded-lg">
                                                    <img src="{{ asset('storage/' . $image->image_path) }}"
                                                        alt="Additional project image"
                                                        class="w-full h-full object-cover transform group-hover:scale-[1.02] transition-transform duration-500">
                                                </div>

                                                <!-- Zoom Button -->
                                                <button
                                                    class="absolute top-2 right-2 p-1.5 bg-white/90 backdrop-blur-sm rounded-full shadow-sm border border-neutral-100 text-neutral-700 hover:text-primary transition-colors duration-200"
                                                    onclick="openImageModal('{{ asset('storage/' . $image->image_path) }}')">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
                                                    </svg>
                                                </button>

                                                <!-- Download Button -->
                                                <div class="mt-3">
                                                    <button
                                                        class="w-full flex items-center justify-center px-3 py-1.5 bg-primary text-white rounded-lg shadow-sm hover:bg-primary-dark transition-colors duration-200 text-sm"
                                                        onclick="downloadImage('{{ asset('storage/' . $image->image_path) }}')">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                        </svg>
                                                        Download Image
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <!-- No Images Placeholder -->
                                    <div
                                        class="bg-neutral-100 rounded-lg border border-neutral-200 flex items-center justify-center p-6 w-48 h-48">
                                        <div class="text-center">
                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                class="h-10 w-10 text-neutral-400 mx-auto mb-2" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <p class="text-neutral-500 font-main text-sm mb-1">No preview</p>
                                            <p class="text-neutral-400 font-main text-xs">No project visuals</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Image Modal for Full-Screen View -->
                        <div id="imageModal"
                            class="fixed inset-0 bg-black/90 z-[9999] hidden flex items-center justify-center p-4 backdrop-blur-sm">
                            <!-- Close Button - Always visible -->
                            <button
                                class="fixed top-4 right-4 z-50 p-2 text-white hover:text-accent transition-colors duration-200 bg-black/50 rounded-full backdrop-blur-sm"
                                onclick="closeImageModal()">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                <span class="sr-only">Close modal</span>
                            </button>

                            <!-- Image Container -->
                            <div class="relative w-full h-full flex justify-center items-center">
                                <div class="max-w-full max-h-full overflow-y-auto rounded-lg shadow-2xl">
                                    <img id="modalImage" src="" alt="Full size preview"
                                        class="block mx-auto object-contain p-4 bg-white" style="max-height: 95vh;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Application Form -->
                <div class="bg-white rounded-xl shadow-lg p-8 border border-neutral-100">
                    <h2 class="text-2xl font-bold text-primary mb-6 font-tertiary flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3 text-secondary" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                        Submit Your Proposal
                    </h2>

                    <form id="job-application-form" class="space-y-8" action="{{ route('applications.store') }}"
                        method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="job_id" value="{{ $job->id }}">
                        <input type="hidden" name="poster_id" value="{{ $job->user_id }}">
                        <input type="hidden" name="applicant_id" value="{{ auth()->id() }}">
                        <input type="hidden" name="status" value="submitted">

                        <div class="flex flex-col md:flex-row md:space-x-6">
                            <!-- Your Offer -->
                            <div class="w-full md:w-1/2 relative">
                                <label for="offer"
                                    class="block text-sm font-medium text-primary mb-2 flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-secondary"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Your Offer Amount
                                </label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-neutral-500 sm:text-sm">Ksh</span>
                                    </div>
                                    <input type="number" id="offer" name="offer"
                                        class="pl-10 block w-full rounded-lg border-neutral-300 shadow-sm focus:ring-secondary focus:border-secondary transition duration-150 ease-in-out text-neutral-700"
                                        placeholder="{{ $job->budget }}">
                                </div>
                            </div>

                            <!-- Your Earnings -->
                            <div
                                class="w-full md:w-1/2 mt-6 md:mt-0 bg-gradient-to-r from-secondary/5 to-primary/5 p-6 rounded-xl border border-neutral-200">
                                <h3 class="text-sm font-semibold text-primary mb-4 flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-secondary"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                    Earnings Breakdown
                                </h3>
                                <div class="space-y-3">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-neutral-600">Your offer</span>
                                        <span id="yourOfferAmount" class="font-semibold text-neutral-800"></span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-neutral-600 flex items-center">
                                            Service fee
                                            <span
                                                class="inline-flex items-center justify-center ml-1 w-4 h-4 rounded-full bg-neutral-200 text-xs"
                                                title="Service fee applied to all projects">?</span>
                                        </span>
                                        <span id="serviceFee" class="font-semibold text-red-500"></span>
                                    </div>
                                    <div class="flex justify-between font-bold mt-4 pt-4 border-t border-neutral-200">
                                        <span class="text-primary">You'll receive</span>
                                        <span id="youllReceive" class="text-secondary text-lg"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Your Proposal -->
                        <div>
                            <label for="proposal" class="block text-sm font-medium text-primary mb-2">Your
                                Proposal</label>
                            <div class="relative">
                                <textarea id="proposal" name="proposal" rows="6"
                                    class="block w-full rounded-lg border-neutral-300 shadow-sm focus:ring-secondary focus:border-secondary resize-none transition duration-150 ease-in-out"
                                    placeholder="Be specific about your skills, experience and proposal here . . . "></textarea>
                                <div class="absolute bottom-3 right-3 text-xs text-neutral-400 h-">
                                    <span id="characters-count">0</span>/2500
                                </div>
                            </div>
                        </div>

                        <!-- Portfolio Upload -->
                        <div>
                            <label class="block text-sm font-medium text-primary mb-2">Portfolio Samples</label>
                            <div
                                class="mt-1 border-2 border-dashed border-neutral-300 rounded-lg bg-neutral-50 transition-all duration-200 ease-in-out hover:bg-neutral-100 hover:border-secondary/50">
                                <div class="flex flex-col items-center justify-center py-6 px-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-secondary/60"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                    <p class="mt-3 text-sm text-neutral-700">
                                        <span class="font-medium text-secondary">Drop files here</span> or
                                        <label for="file-upload" class="relative cursor-pointer">
                                            <span class="font-medium text-secondary underline">browse</span>
                                            <input id="file-upload" name="portfolio[]" type="file"
                                                class="sr-only" multiple accept="image/*">
                                        </label>
                                    </p>
                                    <p class="mt-1 text-xs text-neutral-500">
                                        JPG, JPEG and PNG (max. 5MB per file, max. 5 files)
                                    </p>

                                    <!-- Preview area -->
                                    <div id="file-preview" class="w-full mt-4 hidden">
                                        <div id="preview-container"
                                            class="flex flex-wrap gap-4 p-4 bg-white rounded border border-neutral-200">
                                            <!-- File previews will be added here dynamically -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Terms & Conditions -->
                        <div>
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input id="terms" name="terms" type="checkbox"
                                        class="h-5 w-5 text-secondary rounded border-neutral-300 focus:ring-secondary">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="terms" class="text-neutral-700 font-medium">
                                        I agree to the <a href="#" class="text-secondary hover:underline">Terms
                                            of Service</a> and <a href="#"
                                            class="text-secondary hover:underline">Privacy Policy</a>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex flex-col justify-end sm:flex-row gap-3 pt-2">
                            <button type="submit" name="action" value="draft"
                                class="sm:flex-initial bg-white border border-neutral-300 text-neutral-700 hover:bg-neutral-50 font-medium px-3 py-1.5 rounded-lg shadow-sm transition duration-150 ease-in-out">
                                Save Draft
                            </button>
                            <button type="submit" name="action" value="submitted"
                                class="bg-secondary hover:bg-secondary/90 focus:ring-2 focus:ring-offset-2 focus:ring-secondary text-white font-medium px-3 py-1.5 rounded-lg shadow-sm transition duration-150 ease-in-out flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                                </svg>
                                Submit Application
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Similar Jobs -->
            @if ($similarJobs->isNotEmpty())
                <section class="pt-20 pb-12 border-t border-neutral-200">
                    <div class="container mx-auto">
                        <!-- Section Header -->
                        <div class="flex justify-between items-center mb-8">
                            <h2 class="text-2xl font-bold text-primary font-tertiary flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-secondary"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                Similar Projects
                            </h2>
                        </div>

                        <!-- Projects Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                            @foreach ($similarJobs as $similarJob)
                                <div
                                    class="bg-white rounded-2xl shadow-sm hover:shadow-md border border-neutral-100 hover:border-secondary overflow-hidden transition-all duration-300 flex flex-col h-full">
                                    <!-- Image -->
                                    <div class="relative aspect-[4/3] overflow-hidden">
                                        @if ($similarJob->images)
                                            <img src="{{ asset('storage/' . $similarJob->images) }}"
                                                alt="{{ $similarJob->title }}" class="w-full h-full object-cover">
                                        @else
                                            <div
                                                class="bg-gradient-to-br from-neutral-50 to-neutral-100 w-full h-full flex items-center justify-center">
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="h-12 w-12 text-neutral-300" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="1.5"
                                                        d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                                </svg>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Content -->
                                    <div class="p-5 flex flex-col flex-grow">
                                        <!-- Stats Row -->
                                        <div class="flex items-center justify-between mb-2 text-xs">
                                            <!-- Posted Date -->
                                            <div class="flex items-center text-neutral-500 font-tertiary font-medium">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-1"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <span>Posted {{ $similarJob->created_at->diffForHumans() }}</span>
                                            </div>

                                            <!-- Applicants -->
                                            <span
                                                class="bg-primary/10 text-primary px-2 py-0.5 rounded-full font-medium font-tertiary">
                                                {{ $similarJob->applicants_count }} applied
                                            </span>
                                        </div>

                                        <!-- Title -->
                                        <h3
                                            class="font-main font-semibold text-neutral-800 text-lg mb-2 line-clamp-1 hover:text-primary transition-colors">
                                            {{ $similarJob->title }}
                                        </h3>

                                        <!-- Budget -->
                                        <div class="mb-2">
                                            <span class="text-secondary font-semibold font-tertiary text-sm">
                                                Ksh.{{ number_format($similarJob->budget, 0) }}
                                            </span>
                                        </div>

                                        <!-- Deadline -->
                                        <div class="flex items-center text-sm text-neutral-600 mt-auto mb-4">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-tertiary"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            @if ($similarJob->no_deadline)
                                                <span class="text-neutral-500 font-tertiary font-medium">No
                                                    Deadline</span>
                                            @else
                                                <span
                                                    class="{{ $similarJob->deadline->isPast() ? 'text-red-500' : '' }} text-neutral-500 font-tertiary font-medium">
                                                    Deadline: {{ $similarJob->deadline->format('M j, Y') }}
                                                </span>
                                            @endif
                                        </div>

                                        <!-- Action Button -->
                                        <a href="{{ route('jobs.apply', $similarJob->slug) }}"
                                            class="w-full flex items-center justify-center px-4 py-3 bg-white border-2 border-secondary text-secondary hover:bg-secondary hover:text-white rounded-xl transition-all duration-300 font-medium">
                                            View Details
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-2"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif
        </div>

        <!-- Footer -->
        @include('partials\footer-secondary')

        <!-- Disable submission -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Get the elements
                const offerInput = document.getElementById('offer');
                const termsCheckbox = document.getElementById('terms');
                const submitButton = document.querySelector('button[value="submitted"]');

                // Initially disable the submit button
                submitButton.disabled = true;
                submitButton.classList.add('opacity-50', 'cursor-not-allowed');

                // Function to check if form is valid
                function validateForm() {
                    // Check if offer has a value and terms is checked
                    if (offerInput.value.trim() !== '' && termsCheckbox.checked) {
                        submitButton.disabled = false;
                        submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
                    } else {
                        submitButton.disabled = true;
                        submitButton.classList.add('opacity-50', 'cursor-not-allowed');
                    }
                }

                // Add event listeners to both fields
                offerInput.addEventListener('input', validateForm);
                termsCheckbox.addEventListener('change', validateForm);

                // Initial validation check
                validateForm();
            });
        </script>

        <!-- Switch Skills and Softwares -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Tab switching functionality with improved animations
                const tabButtons = document.querySelectorAll('.tab-button');
                const tabContents = document.querySelectorAll('.tab-content');

                tabButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const targetTab = this.getAttribute('data-tab');

                        // Remove active class from all buttons
                        tabButtons.forEach(btn => {
                            btn.classList.remove('active', 'text-primary', 'border-primary');
                            btn.classList.add('text-neutral-500', 'border-transparent');
                        });

                        // Add active class to clicked button
                        this.classList.add('active', 'text-primary', 'border-primary');
                        this.classList.remove('text-neutral-500', 'border-transparent');

                        // Update badge colors
                        tabButtons.forEach(btn => {
                            const badge = btn.querySelector('span');
                            if (badge) {
                                if (btn.classList.contains('active')) {
                                    badge.classList.remove('bg-neutral-400');
                                    badge.classList.add('bg-accent');
                                } else {
                                    badge.classList.remove('bg-accent');
                                    badge.classList.add('bg-neutral-400');
                                }
                            }
                        });

                        // Hide all content with fade effect
                        tabContents.forEach(content => {
                            content.classList.add('hidden');
                        });

                        // Show target content
                        const targetContent = document.querySelector(
                            `.tab-content[data-content="${targetTab}"]`);
                        if (targetContent) {
                            targetContent.classList.remove('hidden');

                            // Add subtle entrance animation
                            targetContent.style.opacity = '0';
                            targetContent.style.transform = 'translateY(10px)';

                            setTimeout(() => {
                                targetContent.style.transition =
                                    'opacity 0.3s ease, transform 0.3s ease';
                                targetContent.style.opacity = '1';
                                targetContent.style.transform = 'translateY(0)';
                            }, 50);
                        }
                    });
                });
            });
        </script>

        <!-- Simple modal functionality for image preview -->
        <script>
            function openImageModal(imageSrc) {
                const modal = document.getElementById('imageModal');
                const modalImage = document.getElementById('modalImage');

                modalImage.src = imageSrc;
                modal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');

                // Focus the modal for better keyboard navigation
                modal.focus();
            }

            function closeImageModal() {
                const modal = document.getElementById('imageModal');
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }

            // Close modal on escape key
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    closeImageModal();
                }
            });

            // Close modal on outside click
            document.getElementById('imageModal').addEventListener('click', function(event) {
                if (event.target === this) {
                    closeImageModal();
                }
            });
        </script>

        <!-- Download Image -->
        <script>
            function downloadImage(imageUrl) {
                // Create a temporary link
                const link = document.createElement('a');
                link.href = imageUrl;
                link.download = imageUrl.split('/').pop(); // Extract filename from URL

                // Append to body and trigger click
                document.body.appendChild(link);
                link.click();

                // Clean up
                document.body.removeChild(link);
            }
        </script>

        <!-- Portfolio Uploads -->
        <script>
            // Function to show SweetAlert for file limit exceeded
            function showMaxFilesAlert() {
                Swal.fire({
                    icon: 'error',
                    title: 'File Limit Exceeded',
                    text: `You can upload a maximum of ${MAX_FILES} files.`,
                });
            }

            // Function to show SweetAlert for file size exceeded
            function showFileSizeAlert() {
                Swal.fire({
                    icon: 'error',
                    title: 'File Size Limit Exceeded',
                    text: `File size should not exceed ${MAX_FILE_SIZE / (1024 * 1024)}MB.`,
                });
            }

            // Function to show SweetAlert for invalid file type
            function showFileTypeAlert() {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid File Type',
                    text: 'Only JPG, JPEG, PNG, and PDF files are allowed.',
                });
            }

            // Maximum number of files allowed
            const MAX_FILES = 5;
            // Maximum file size in bytes (10MB)
            const MAX_FILE_SIZE = 10 * 1024 * 1024;

            // Get references to the elements
            const fileInput = document.getElementById('file-upload');
            const previewContainer = document.getElementById('preview-container');
            const filePreview = document.getElementById('file-preview');

            // Function to handle file selection
            function handleFileSelect(event) {
                const files = event.target.files;
                const currentFiles = previewContainer.querySelectorAll('.file-item');

                // Check if adding new files will exceed the maximum limit
                if (currentFiles.length + files.length > MAX_FILES) {
                    showMaxFilesAlert();
                    return;
                }

                // Process each selected file
                for (let i = 0; i < files.length; i++) {
                    const file = files[i];

                    // Check file type
                    if (!file.type.match('image/jpeg') && !file.type.match('image/png') && !file.type.match(
                            'application/pdf')) {
                        showFileTypeAlert();
                        continue;
                    }

                    // Check file size
                    if (file.size > MAX_FILE_SIZE) {
                        showFileSizeAlert();
                        continue;
                    }

                    // Create a file preview item
                    const fileItem = document.createElement('div');
                    fileItem.className = 'file-item flex items-center p-2 bg-white rounded border border-neutral-200';
                    fileItem.innerHTML = `
                            <div class="w-12 h-12 rounded overflow-hidden mr-3">
                                <img src="" alt="Preview" class="w-full h-full object-cover">
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-neutral-700 truncate">${file.name}</p>
                                <p class="text-xs text-neutral-500">Size ${formatFileSize(file.size)}</p>
                            </div>
                            <button type="button" class="text-neutral-400 hover:text-red-500 ml-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        `;

                    // Add remove functionality
                    const removeButton = fileItem.querySelector('button');
                    removeButton.addEventListener('click', function() {
                        fileItem.remove();
                        if (previewContainer.children.length === 0) {
                            filePreview.classList.add('hidden');
                        }
                    });

                    // Add to preview container
                    previewContainer.appendChild(fileItem);

                    // Create image preview
                    const img = fileItem.querySelector('img');
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        img.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }

                // Show preview area if there are files
                if (previewContainer.children.length > 0) {
                    filePreview.classList.remove('hidden');
                }
            }

            // Function to format file size
            function formatFileSize(bytes) {
                if (bytes < 1024) return `${bytes} B`;
                if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
                return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
            }

            // Add event listener to file input
            fileInput.addEventListener('change', handleFileSelect);
        </script>
        <!-- Tracking Proposal Characters -->
        <script>
            // Maximum character limit
            const maxCharacters = 2500;

            // Get references to the elements
            const proposalTextarea = document.getElementById('proposal');
            const charactersCount = document.getElementById('characters-count');

            // Function to update the character count
            function updateCharacterCount() {
                const currentLength = proposalTextarea.value.length;
                const remainingCharacters = maxCharacters - currentLength;

                // Update the character count display
                charactersCount.textContent = currentLength;

                // Add warning styling when approaching the limit
                if (remainingCharacters <= 500) {
                    charactersCount.classList.add('text-red-500');
                } else {
                    charactersCount.classList.remove('text-red-500');
                }

                // Prevent further input when reaching the limit
                if (currentLength >= maxCharacters) {
                    proposalTextarea.value = proposalTextarea.value.substring(0, maxCharacters);
                    updateCharacterCount(); // Update the count after truncation
                }
            }

            // Initialize the character count
            updateCharacterCount();

            // Listen for input changes
            proposalTextarea.addEventListener('input', updateCharacterCount);
        </script>

        <!-- Calculating Service Fee & Payout -->
        <script>
            // Service fee percentage (configurable)
            const serviceFeePercentage = 0.10; // 10%

            // Get references to the elements
            const offerInput = document.getElementById('offer');
            const yourOfferAmount = document.getElementById('yourOfferAmount');
            const serviceFee = document.getElementById('serviceFee');
            const youllReceive = document.getElementById('youllReceive');

            // Function to update the earnings breakdown
            function updateEarnings() {
                const offer = parseFloat(offerInput.value) || 0;

                // Calculate service fee
                const fee = offer * serviceFeePercentage;

                // Calculate net amount
                const netAmount = offer - fee;

                // Update the DOM with formatted values
                yourOfferAmount.textContent = formatCurrency(offer);
                serviceFee.textContent = formatCurrency(fee, true);
                youllReceive.textContent = formatCurrency(netAmount);
            }

            // Function to format currency
            function formatCurrency(amount, isNegative = false) {
                const symbol = isNegative ? '-' : '';
                return `${symbol}Ksh${amount.toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
                })}`;
            }

            // Initialize with default values
            updateEarnings();

            // Listen for input changes
            offerInput.addEventListener('input', updateEarnings);
        </script>


    </section>
</x-app-layout>
