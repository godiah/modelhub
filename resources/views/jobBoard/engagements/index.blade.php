<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-tertiary font-bold text-2xl text-primary leading-tight flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="h-6 w-6 mr-2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
                </svg>
                Job Engagements
            </h2>
            <div class="flex items-center text-sm font-tertiary font-medium text-neutral-500">
                <span class="hidden md:inline-flex items-center mr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-secondary" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Active: {{ $engagements->where('status', 'active')->count() }}
                </span>
                <span class="hidden md:inline-flex items-center mr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-accent" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                    </svg>
                    Pending: {{ $engagements->where('status', 'employer_accepted')->count() }}
                </span>
                <span class="hidden md:inline-flex items-center mr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-green-800" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Completed: {{ $engagements->where('status', 'completed')->count() }}
                </span>
                <span class="hidden md:inline-flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="h-4 w-4 mr-1 text-red-800">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    </svg>
                    Withdrawn: {{ $engagements->where('status', 'cancelled')->count() }}
                </span>
            </div>
        </div>
    </x-slot>

    <div class="container mx-auto max-w-7xl px-4 py-8 pb-24">
        <!-- Search & Filter -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="relative flex-grow max-w-md">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-neutral-400" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" id="search"
                    class="font-tertiary text-sm block w-full pl-10 pr-3 py-2.5 border border-neutral-300 rounded-lg focus:ring-primary focus:border-primary"
                    placeholder="Search engagements...">
            </div>

            <div class="flex gap-3">
                <select
                    class="font-tertiary block w-full border-neutral-300 rounded-lg focus:ring-primary focus:border-primary py-2.5 pl-3 pr-10 text-sm">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="pending">Pending</option>
                </select>

                <button
                    class="bg-primary text-white px-4 py-2.5 rounded-lg flex items-center justify-center hover:bg-primary/90 transition-colors shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                </button>
            </div>
        </div>

        @if ($engagements->isEmpty())
            <div class="bg-white border border-neutral-200 rounded-xl p-12 text-center shadow-sm">
                <div class="bg-neutral-100 h-24 w-24 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="h-12 w-12 text-neutral-400">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
                    </svg>
                </div>
                <h3 class="font-tertiary font-semibold text-xl text-neutral-700 mb-3">No Job Engagements Found</h3>
                <p class="text-neutral-500 font-main max-w-md mx-auto mb-6">You don't have any active job engagements at
                    the moment. Apply to job posts to receive offers.</p>
                <a href="{{ route('jobs.browse') }}"
                    class="inline-flex items-center px-5 py-3 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-gradient-to-r from-primary to-primary/90 hover:from-primary/90 hover:to-primary/80 transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    Browse Available Jobs
                </a>
            </div>
        @else
            <div class="space-y-6">
                @foreach ($engagements as $engagement)
                    @php
                        $currentUser = Auth::user();
                        $isApplicant = $engagement->application->applicant_id === $currentUser->id;
                        $isPoster = $engagement->application->poster_id === $currentUser->id;

                        $totalDeliverables = $engagement->deliverables->count();
                        $completedDeliverables = $engagement->deliverables->where('submitted_at', '!=', null)->count();
                        $progressPercentage =
                            $totalDeliverables > 0 ? ($completedDeliverables / $totalDeliverables) * 100 : 0;
                    @endphp

                    <div x-data="{ open: false }"
                        class="bg-white border border-neutral-200 rounded-xl shadow-sm overflow-hidden transition-all duration-200 hover:shadow-md">
                        <!-- Engagement Header - Always visible -->
                        <div @click="open = !open" class="cursor-pointer">
                            <div class="p-6 flex flex-col sm:flex-row sm:items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex flex-col sm:flex-row sm:items-center">
                                        <h3 class="font-tertiary font-bold text-lg text-primary flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 flex-shrink-0"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            {{ $engagement->application->job->title }}
                                        </h3>
                                        <span
                                            class="sm:ml-3 mt-1 sm:mt-0 inline-flex items-center px-3 py-1 text-xs font-medium font-main rounded-full border 
                                                @if ($engagement->status === 'active') bg-secondary/10 text-secondary border-secondary/20
                                                @elseif($engagement->status === 'completed')
                                                    bg-green-100 text-green-800 border-green-200
                                                @elseif($engagement->status === 'cancelled')
                                                    bg-red-100 text-red-800 border-red-200
                                                @else
                                                    bg-accent/10 text-accent border-accent/20 @endif">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-1"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                @if ($engagement->status === 'employer_accepted')
                                                    <!-- Bell icon -->
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                                @elseif($engagement->status === 'active')
                                                    <!-- Clock icon -->
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                @elseif($engagement->status === 'completed')
                                                    <!-- Check icon -->
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                @elseif($engagement->status === 'cancelled')
                                                    <!-- Exclamation icon -->
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                @endif
                                            </svg>
                                            {{ match ($engagement->status) {
                                                'employer_accepted' => 'Pending',
                                                'active' => 'Active',
                                                'completed' => 'Completed',
                                                'cancelled' => 'Withdrawn',
                                                default => 'Unknown',
                                            } }}
                                        </span>
                                    </div>
                                </div>

                                <div class="sm:ml-4 flex font-main">
                                    @if ($isApplicant && $engagement->status === 'employer_accepted')
                                        <a href="{{ route('engagements.response-form', ['applicationId' => $engagement->application_id]) }}"
                                            class="mr-3 inline-flex items-center px-3 py-1 text-xs font-medium text-primary bg-primary/10 rounded-full border border-primary/20 hover:bg-primary/20 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-1"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                            Respond
                                        </a>
                                    @endif

                                    <button class="flex items-center text-neutral-500 hover:text-neutral-700" x-cloak>
                                        <span class="text-sm mr-1"
                                            x-text="open ? 'Hide Details' : 'View Details'"></span>
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="h-5 w-5 transition-transform duration-200"
                                            :class="{ 'transform rotate-180': open }" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Progress bar -->
                            <div class="px-6 pb-4 font-main" x-cloak>
                                <div class="h-1.5 w-full bg-neutral-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-secondary rounded-full"
                                        style="width: {{ $progressPercentage }}%"></div>
                                </div>
                                <div class="flex justify-between mt-1 text-xs text-neutral-500">
                                    <span>Progress</span>
                                    <span>{{ $completedDeliverables }}/{{ $totalDeliverables }} Deliverables</span>
                                </div>
                            </div>
                        </div>

                        <!-- Expandable Content -->
                        <div x-show="open" x-collapse x-cloak class="border-t border-neutral-200 bg-neutral-50">
                            @if ($isApplicant)
                                <div class="p-6">
                                    <h4 class="font-tertiary font-semibold text-primary mb-4 flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                        Project Deliverables
                                    </h4>

                                    <div class="grid grid-cols-1 gap-4">
                                        @forelse($engagement->deliverables as $deliverable)
                                            <div x-data="{ expanded: false }"
                                                class="bg-white p-5 border border-neutral-200 rounded-lg shadow-sm hover:shadow transition-all duration-200">

                                                <div @click="expanded = !expanded" class="pb-2">
                                                    <div class="flex justify-between items-start">
                                                        <div class="flex items-center">
                                                            <div
                                                                class="rounded-full p-1.5 mr-3
                                                                @if ($deliverable->approved_at) bg-green-100 text-green-800 
                                                                @elseif ($deliverable->rejected_at) bg-red-100 text-red-800
                                                                @elseif ($deliverable->submitted_at) bg-secondary/10 text-secondary
                                                                @elseif($deliverable->due_date && now()->gt($deliverable->due_date)) bg-red-100 text-red-600
                                                                @else bg-neutral-200 text-neutral-500 @endif">
                                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                                    class="h-5 w-5" fill="none"
                                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                                    @if ($deliverable->approved_at)
                                                                        <path stroke-linecap="round"
                                                                            stroke-linejoin="round" stroke-width="2"
                                                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                    @elseif ($deliverable->rejected_at)
                                                                        <path stroke-linecap="round"
                                                                            stroke-linejoin="round" stroke-width="2"
                                                                            d="M6 18L18 6M6 6l12 12" />
                                                                    @elseif ($deliverable->submitted_at)
                                                                        <path stroke-linecap="round"
                                                                            stroke-linejoin="round" stroke-width="2"
                                                                            d="M5 13l4 4L19 7" />
                                                                    @elseif($deliverable->due_date && now()->gt($deliverable->due_date))
                                                                        <path stroke-linecap="round"
                                                                            stroke-linejoin="round" stroke-width="2"
                                                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                                    @else
                                                                        <path stroke-linecap="round"
                                                                            stroke-linejoin="round" stroke-width="2"
                                                                            d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                                                    @endif
                                                                </svg>
                                                            </div>
                                                            <h5 class="font-tertiary font-bold text-neutral-800">
                                                                {{ $deliverable->title }}
                                                            </h5>
                                                        </div>
                                                        <span
                                                            class="px-2.5 py-1 text-xs font-medium font-tertiary rounded-full flex items-center 
                                                            @if ($deliverable->approved_at) bg-green-100 text-green-800
                                                            @elseif($deliverable->rejected_at)
                                                                bg-red-100 text-red-800
                                                            @elseif($deliverable->submitted_at)
                                                                bg-secondary/10 text-secondary
                                                            @elseif ($deliverable->isOverdue())
                                                                bg-red-100 text-red-600
                                                            @else
                                                                bg-neutral-200 text-neutral-600 @endif">
                                                            @if ($deliverable->approved_at)
                                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                                    class="h-3 w-3 mr-1" fill="none"
                                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round" stroke-width="2"
                                                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                </svg>
                                                                Approved
                                                            @elseif($deliverable->rejected_at)
                                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                                    class="h-3 w-3 mr-1" fill="none"
                                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round" stroke-width="2"
                                                                        d="M6 18L18 6M6 6l12 12" />
                                                                </svg>
                                                                Rejected
                                                            @elseif($deliverable->submitted_at)
                                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                                    class="h-3 w-3 mr-1" fill="none"
                                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round" stroke-width="2"
                                                                        d="M5 13l4 4L19 7" />
                                                                </svg>
                                                                Submitted
                                                            @elseif($deliverable->isOverdue())
                                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                                    class="h-4 w-4 mr-1" fill="none"
                                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round" stroke-width="2"
                                                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                                </svg>
                                                                Overdue
                                                            @else
                                                                Pending
                                                            @endif
                                                        </span>
                                                    </div>

                                                    <p
                                                        class="font-main text-justify text-sm text-neutral-600 mt-3 ml-9">
                                                        {{ $deliverable->description }}</p>

                                                    <div
                                                        class="mt-3 ml-9 flex flex-wrap gap-y-2 gap-x-4 text-xs font-main">
                                                        @if ($deliverable->due_date)
                                                            <div
                                                                class="flex items-center {{ $deliverable->isOverdue() ? 'text-red-500' : 'text-secondary' }}">
                                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                                    class="h-3.5 w-3.5 mr-1" fill="none"
                                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round" stroke-width="2"
                                                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                                </svg>
                                                                Due:
                                                                {{ $deliverable->due_date->format('M j, Y') }}
                                                                @if ($deliverable->isOverdue())
                                                                    <span class="ml-1 text-xs">(Overdue)</span>
                                                                @endif
                                                            </div>
                                                        @else
                                                            <div class="flex items-center text-secondary">
                                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                                    class="h-3.5 w-3.5 mr-1" fill="none"
                                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round" stroke-width="2"
                                                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                                </svg>
                                                                Due Date Not Set
                                                            </div>
                                                        @endif

                                                        @if ($deliverable->submitted_at)
                                                            <div class="flex items-center text-secondary">
                                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                                    class="h-3.5 w-3.5 mr-1" fill="none"
                                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round" stroke-width="2"
                                                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                </svg>
                                                                Completed:
                                                                {{ $deliverable->submitted_at->format('M j, Y') }}
                                                            </div>
                                                        @endif

                                                        @if ($deliverable->approved_at)
                                                            <div class="flex items-center text-secondary">
                                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                                    class="h-3.5 w-3.5 mr-1" fill="none"
                                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round" stroke-width="2"
                                                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                </svg>
                                                                Approved:
                                                                {{ $deliverable->approved_at->format('M j, Y') }}
                                                            </div>
                                                        @endif

                                                        @if ($deliverable->rejected_at)
                                                            <div class="flex items-center text-red-800">
                                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                                    class="h-3.5 w-3.5 mr-1" fill="none"
                                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round" stroke-width="2"
                                                                        d="M6 18L18 6M6 6l12 12" />
                                                                </svg>
                                                                Rejected:
                                                                {{ $deliverable->rejected_at->format('M j, Y') }}
                                                            </div>
                                                        @endif

                                                        @if ($deliverable->feedback)
                                                            <div
                                                                class="flex items-center text-blue-800 cursor-pointer">
                                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                                    viewBox="0 0 24 24" stroke-width="1.5"
                                                                    stroke="currentColor" class="h-3.5 w-3.5 mr-1">
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round"
                                                                        d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
                                                                </svg>

                                                                View Feedback
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>

                                                @if ($deliverable->feedback)
                                                    <div x-show="expanded" x-collapse
                                                        class="border-t border-neutral-200 p-5 mt-2 bg-neutral-50">
                                                        <p class="text-neutral-600 text-sm font-main">
                                                            {{ $deliverable->feedback }}</p>
                                                    </div>
                                                @endif
                                            </div>
                                        @empty
                                            <div
                                                class="col-span-2 bg-white p-6 border border-neutral-200 rounded-lg text-center">
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="h-8 w-8 text-neutral-300 mx-auto mb-2" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="1.5"
                                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                                </svg>
                                                <p class="text-neutral-500 italic font-tertiary">No deliverables
                                                    specified for this
                                                    project yet.</p>
                                            </div>
                                        @endforelse
                                    </div>

                                    <!-- Project Progress Overview -->
                                    @if ($engagement->deliverables->isNotEmpty())
                                        <div class="mt-8 bg-white border border-neutral-200 rounded-lg shadow-sm p-5">
                                            <h5
                                                class="font-tertiary font-medium text-neutral-800 mb-4 flex items-center">
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="h-5 w-5 mr-2 text-primary" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                                </svg>
                                                Project Progress
                                            </h5>

                                            <div class="flex flex-col md:flex-row gap-6">
                                                <!-- Progress Bar -->
                                                <div class="flex-1">
                                                    <div class="flex justify-between text-sm mb-1 font-main">
                                                        <span
                                                            class="font-medium text-neutral-700">{{ round($engagement->completionPercentage()) }}%
                                                            Complete</span>
                                                        <span class="text-neutral-500">
                                                            {{ $engagement->deliverables->where('status', 'approved')->count() }}/{{ $engagement->deliverables->count() }}
                                                            deliverables
                                                        </span>
                                                    </div>
                                                    <div class="h-2 bg-neutral-200 rounded-full overflow-hidden">
                                                        <div class="h-full bg-secondary rounded-full"
                                                            style="width: {{ $engagement->completionPercentage() }}%">
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Stats -->
                                                <div class="flex divide-x divide-neutral-200 font-main">
                                                    <div class="px-4 first:pl-0 last:pr-0">
                                                        <div class="text-xs text-neutral-500 mb-1">Pending</div>
                                                        <div class="text-lg text-center font-medium text-neutral-800">
                                                            {{ $engagement->deliverables->where('status', 'pending')->count() }}
                                                        </div>
                                                    </div>
                                                    <div class="px-4">
                                                        <div class="text-xs text-neutral-500 mb-1">Submitted</div>
                                                        <div class="text-lg text-center font-medium text-accent">
                                                            {{ $engagement->deliverables->where('status', 'submitted')->count() }}
                                                        </div>
                                                    </div>
                                                    <div class="px-4">
                                                        <div class="text-xs text-neutral-500 mb-1">Approved</div>
                                                        <div class="text-lg text-center font-medium text-secondary">
                                                            {{ $engagement->deliverables->where('status', 'approved')->count() }}
                                                        </div>
                                                    </div>
                                                    <div class="px-4 last:pr-0">
                                                        <div class="text-xs text-neutral-500 mb-1">Rejected</div>
                                                        <div class="text-lg text-center font-medium text-red-800">
                                                            {{ $engagement->deliverables->where('status', 'rejected')->count() }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            @if ($isPoster)
                                <div class="p-6">
                                    <div class="mb-6 flex justify-between">
                                        <h4 class="font-tertiary font-semibold text-primary flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                            </svg>
                                            Project Deliverables
                                        </h4>

                                        <!-- Add Deliverable Button -->
                                        <button id="openDeliverableModal"
                                            class="font-tertiary inline-flex items-center px-4 py-2 rounded-lg text-white bg-secondary hover:bg-secondary/90 transition-colors shadow-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 4v16m8-8H4" />
                                            </svg>
                                            Add Deliverable
                                        </button>

                                    </div>

                                    <!-- Deliverables List -->
                                    <div class="grid grid-cols-1 gap-6">
                                        @forelse($engagement->deliverables as $deliverable)
                                            <div x-data="{ expanded: false }"
                                                class="bg-white border border-neutral-200 rounded-lg shadow-sm hover:shadow transition-all overflow-hidden">
                                                <!-- Deliverable Header -->
                                                <div @click="expanded = !expanded"
                                                    class="p-5 flex justify-between items-start cursor-pointer">
                                                    <div class="flex items-center">
                                                        <div
                                                            class="rounded-full p-1.5 mr-3 flex-shrink-0
                                                                @if ($deliverable->status === 'approved') bg-green-100 text-green-800
                                                                @elseif($deliverable->status === 'rejected')
                                                                    bg-red-100 text-red-800
                                                                @elseif($deliverable->status === 'submitted')
                                                                    bg-secondary/10 text-secondary
                                                                @else
                                                                    bg-neutral-200 text-neutral-500 @endif">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                                                                fill="none" viewBox="0 0 24 24"
                                                                stroke="currentColor">
                                                                @if ($deliverable->status === 'approved')
                                                                    <!-- Check icon -->
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round" stroke-width="2"
                                                                        d="M5 13l4 4L19 7" />
                                                                @elseif($deliverable->status === 'rejected')
                                                                    <!-- X icon -->
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round" stroke-width="2"
                                                                        d="M6 18L18 6M6 6l12 12" />
                                                                @elseif($deliverable->status === 'submitted')
                                                                    <!-- Circle check icon -->
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round" stroke-width="2"
                                                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                @else
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round" stroke-width="2"
                                                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                                                @endif
                                                            </svg>
                                                        </div>
                                                        <div>
                                                            <h5 class="font-tertiary font-bold text-neutral-800">
                                                                {{ $deliverable->title }}</h5>
                                                            <p
                                                                class="font-main text-sm text-justify text-neutral-600 mt-2">
                                                                {{ $deliverable->description }}</p>
                                                            <div
                                                                class="mt-3 flex flex-wrap gap-y-2 gap-x-4 text-xs font-main">
                                                                @if ($deliverable->due_date)
                                                                    <div
                                                                        class="flex items-center {{ now()->gt($deliverable->due_date) ? 'text-red-500' : 'text-secondary' }}">
                                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                                            class="h-3.5 w-3.5 mr-1" fill="none"
                                                                            viewBox="0 0 24 24" stroke="currentColor">
                                                                            <path stroke-linecap="round"
                                                                                stroke-linejoin="round"
                                                                                stroke-width="2"
                                                                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                                        </svg>
                                                                        Due:
                                                                        {{ $deliverable->due_date->format('M j, Y') }}
                                                                        @if ($deliverable->isOverdue())
                                                                            <span class="ml-1 text-xs">(Overdue)</span>
                                                                        @endif
                                                                    </div>
                                                                @else
                                                                    <div class="flex items-center text-secondary">
                                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                                            class="h-3.5 w-3.5 mr-1" fill="none"
                                                                            viewBox="0 0 24 24" stroke="currentColor">
                                                                            <path stroke-linecap="round"
                                                                                stroke-linejoin="round"
                                                                                stroke-width="2"
                                                                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                                        </svg>
                                                                        Due Date Not Set
                                                                    </div>
                                                                @endif

                                                                @if ($deliverable->submitted_at)
                                                                    <div class="flex items-center text-secondary">
                                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                                            class="h-3.5 w-3.5 mr-1" fill="none"
                                                                            viewBox="0 0 24 24" stroke="currentColor">
                                                                            <path stroke-linecap="round"
                                                                                stroke-linejoin="round"
                                                                                stroke-width="2"
                                                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                        </svg>
                                                                        Completed:
                                                                        {{ $deliverable->submitted_at->format('M j, Y') }}
                                                                    </div>
                                                                @endif

                                                                @if ($deliverable->approved_at)
                                                                    <div class="flex items-center text-secondary">
                                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                                            class="h-3.5 w-3.5 mr-1" fill="none"
                                                                            viewBox="0 0 24 24" stroke="currentColor">
                                                                            <path stroke-linecap="round"
                                                                                stroke-linejoin="round"
                                                                                stroke-width="2"
                                                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                        </svg>
                                                                        Approved:
                                                                        {{ $deliverable->approved_at->format('M j, Y') }}
                                                                    </div>
                                                                @endif

                                                                @if ($deliverable->rejected_at)
                                                                    <div class="flex items-center text-red-800">
                                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                                            class="h-3.5 w-3.5 mr-1" fill="none"
                                                                            viewBox="0 0 24 24" stroke="currentColor">
                                                                            <path stroke-linecap="round"
                                                                                stroke-linejoin="round"
                                                                                stroke-width="2"
                                                                                d="M6 18L18 6M6 6l12 12" />
                                                                        </svg>
                                                                        Rejected:
                                                                        {{ $deliverable->rejected_at->format('M j, Y') }}
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="flex items-center">
                                                        <span
                                                            class="px-2.5 py-1 text-xs font-medium rounded-full mr-3 flex items-center 
                                                                @if ($deliverable->status === 'approved') bg-green-100 text-green-800
                                                                @elseif($deliverable->status === 'rejected')
                                                                    bg-red-100 text-red-800
                                                                @elseif($deliverable->status === 'submitted')
                                                                    bg-secondary/10 text-secondary
                                                                @elseif($deliverable->isOverdue())
                                                                    bg-red-100 text-red-800
                                                                @else
                                                                    bg-neutral-200 text-neutral-600 @endif">

                                                            @if ($deliverable->status === 'approved')
                                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                                    class="h-3 w-3 mr-1" fill="none"
                                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round" stroke-width="2"
                                                                        d="M5 13l4 4L19 7" />
                                                                </svg>
                                                                Approved
                                                            @elseif($deliverable->status === 'rejected')
                                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                                    class="h-3 w-3 mr-1" fill="none"
                                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round" stroke-width="2"
                                                                        d="M6 18L18 6M6 6l12 12" />
                                                                </svg>
                                                                Rejected
                                                            @elseif($deliverable->status === 'submitted')
                                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                                    class="h-3 w-3 mr-1" fill="none"
                                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round" stroke-width="2"
                                                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                                </svg>
                                                                Submitted
                                                            @elseif($deliverable->isOverdue())
                                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                                    class="h-4 w-4 mr-1" fill="none"
                                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round" stroke-width="2"
                                                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                                </svg>
                                                                Overdue
                                                            @else
                                                                Pending
                                                            @endif
                                                        </span>
                                                    </div>
                                                </div>

                                                <!-- Deliverable Details (Expandable) -->
                                                <div x-show="expanded" x-collapse
                                                    class="border-t border-neutral-200 p-5 bg-neutral-50">

                                                    <!-- Submission Details (when submitted) -->
                                                    @if ($deliverable->status === 'submitted' || $deliverable->status === 'approved' || $deliverable->status === 'rejected')
                                                        <div
                                                            class="bg-white border border-neutral-200 rounded-lg p-4 mb-4">
                                                            <!-- Submission Notes -->
                                                            <h6 class="font-medium font-tertiary  text-primary mb-1">
                                                                Submission
                                                                Notes</h6>
                                                            @if ($deliverable->submission_notes)
                                                                <p
                                                                    class="text-neutral-600 text-sm mb-3 font-main text-justify p-3 bg-neutral-50 rounded-lg">
                                                                    {{ $deliverable->submission_notes }}
                                                                </p>
                                                            @else
                                                                <div
                                                                    class="flex items-center p-3 bg-neutral-50 rounded-lg mb-3">
                                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                                        class="h-10 w-10 mr-2 text-neutral-400"
                                                                        fill="none" viewBox="0 0 24 24"
                                                                        stroke="currentColor">
                                                                        <path stroke-linecap="round"
                                                                            stroke-linejoin="round" stroke-width="2"
                                                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                                    </svg>
                                                                    <span class="text-sm text-neutral-500 font-main">No
                                                                        provided notes</span>
                                                                </div>
                                                            @endif

                                                            <h6 class="font-medium font-tertiary  text-primary mb-1">
                                                                Submitted Files</h6>

                                                            <!-- Submitted Files -->
                                                            @if (!empty($deliverable->submission_files))
                                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                                                    @foreach ($deliverable->submission_files as $file)
                                                                        <a href="{{ $file['url'] }}" target="_blank"
                                                                            class="flex items-center p-2 border border-neutral-200 rounded-md hover:bg-neutral-50 transition-colors">
                                                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                                                class="h-5 w-5 mr-2 text-primary"
                                                                                fill="none" viewBox="0 0 24 24"
                                                                                stroke="currentColor">
                                                                                <path stroke-linecap="round"
                                                                                    stroke-linejoin="round"
                                                                                    stroke-width="2"
                                                                                    d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                                            </svg>
                                                                            <span
                                                                                class="text-sm truncate">{{ $file['name'] }}</span>
                                                                        </a>
                                                                    @endforeach
                                                                </div>
                                                            @else
                                                                <div
                                                                    class="flex items-center justify-center mx-auto p-3 bg-neutral-50 rounded-lg">
                                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                                        fill="none" viewBox="0 0 24 24"
                                                                        stroke-width="1.5" stroke="currentColor"
                                                                        class="h-10 w-10 mr-2 text-neutral-400">
                                                                        <path stroke-linecap="round"
                                                                            stroke-linejoin="round"
                                                                            d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                                                    </svg>
                                                                    <span class="text-sm text-neutral-500 font-main">No
                                                                        files
                                                                        uploaded</span>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @endif

                                                    <!-- Feedback -->
                                                    @if (in_array($deliverable->status, ['approved', 'rejected']) && $deliverable->feedback)
                                                        <div
                                                            class="bg-{{ $deliverable->status === 'approved' ? 'green' : 'red' }}-50 border border-{{ $deliverable->status === 'approved' ? 'green' : 'red' }}-200 rounded-lg p-4 mb-4">
                                                            <h6
                                                                class="font-tertiary font-medium text-{{ $deliverable->status === 'approved' ? 'green' : 'red' }}-700 mb-2 flex items-center">
                                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                                    class="h-4 w-4 mr-1.5" fill="none"
                                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                                    @if ($deliverable->status === 'approved')
                                                                        <path stroke-linecap="round"
                                                                            stroke-linejoin="round" stroke-width="2"
                                                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                    @else
                                                                        <path stroke-linecap="round"
                                                                            stroke-linejoin="round" stroke-width="2"
                                                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                                    @endif
                                                                </svg>
                                                                {{ $deliverable->status === 'approved' ? 'Approval Notes' : 'Rejection Feedback' }}
                                                            </h6>
                                                            <p
                                                                class="font-main text-{{ $deliverable->status === 'approved' ? 'green' : 'red' }}-800 text-sm">
                                                                {{ $deliverable->feedback }}
                                                            </p>
                                                        </div>
                                                    @endif

                                                    <!-- Action Buttons -->
                                                    <div class="flex flex-wrap items-center gap-3 mt-2">
                                                        <!-- Approve/Reject Deliverable -->
                                                        @if ($deliverable->status === 'submitted' && $engagement->status === 'active')
                                                            <button x-data
                                                                x-on:click="$dispatch('open-modal', 'approve-deliverable-{{ $deliverable->id }}')"
                                                                class="inline-flex items-center px-3.5 py-2 text-sm font-medium rounded-md text-white bg-secondary hover:bg-secondary/90 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-secondary">
                                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                                    class="h-4 w-4 mr-1.5" fill="none"
                                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round" stroke-width="2"
                                                                        d="M5 13l4 4L19 7" />
                                                                </svg>
                                                                Approve
                                                            </button>

                                                            <button x-data
                                                                x-on:click="$dispatch('open-modal', 'reject-deliverable-{{ $deliverable->id }}')"
                                                                class="inline-flex items-center px-3.5 py-2 text-sm font-medium rounded-md text-accent border border-accent/30 bg-white hover:bg-accent/5 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-accent">
                                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                                    class="h-4 w-4 mr-1.5" fill="none"
                                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round" stroke-width="2"
                                                                        d="M6 18L18 6M6 6l12 12" />
                                                                </svg>
                                                                Request Changes
                                                            </button>
                                                        @endif

                                                        <!-- Edit/Delete Deliverable -->
                                                        @if ($deliverable->status === 'pending' && $engagement->status === 'active')
                                                            <button x-data
                                                                x-on:click="$dispatch('open-modal', 'edit-deliverable-{{ $deliverable->id }}')"
                                                                class="inline-flex items-center px-3.5 py-2 text-sm font-medium rounded-md text-primary border border-primary/30 bg-primary/5 hover:bg-primary/10 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary/40">
                                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                                    class="h-4 w-4 mr-1.5" fill="none"
                                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round" stroke-width="2"
                                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                                </svg>
                                                                Edit
                                                            </button>
                                                            <button x-data
                                                                x-on:click="$dispatch('open-modal', 'remove-deliverable-{{ $deliverable->id }}')"
                                                                class="inline-flex items-center px-3.5 py-2 text-sm font-medium rounded-md text-red-600 border border-red-200 bg-red-50 hover:bg-red-100 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500/40">
                                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                                    class="h-4 w-4 mr-1.5" fill="none"
                                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round" stroke-width="2"
                                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                </svg>
                                                                Remove
                                                            </button>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="bg-white p-8 border border-neutral-200 rounded-lg text-center">
                                                <div class="bg-primary/5 inline-flex rounded-full p-4 mb-4">
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="h-8 w-8 text-primary" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="1.5"
                                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                                    </svg>
                                                </div>
                                                <h5 class="font-medium text-lg text-neutral-800 mb-2 font-tertiary">No
                                                    Deliverables
                                                    Yet</h5>
                                                <p class="text-neutral-600 mb-6 font-tertiary text-sm">Define project
                                                    deliverables to track
                                                    progress and ensure clear expectations.</p>
                                                <button id="openDeliverableModal"
                                                    class="font-tertiary inline-flex items-center px-4 py-2 rounded-lg text-white bg-secondary hover:bg-secondary/90 transition-colors shadow-sm">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2"
                                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M12 4v16m8-8H4" />
                                                    </svg>
                                                    Add Deliverable
                                                </button>
                                            </div>
                                        @endforelse
                                    </div>

                                    <!-- Project Progress Overview -->
                                    @if ($engagement->deliverables->isNotEmpty())
                                        <div class="mt-8 bg-white border border-neutral-200 rounded-lg shadow-sm p-5">
                                            <h5
                                                class="font-tertiary font-medium text-neutral-800 mb-4 flex items-center">
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="h-5 w-5 mr-2 text-primary" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                                </svg>
                                                Project Progress
                                            </h5>

                                            <div class="flex flex-col md:flex-row gap-6">
                                                <!-- Progress Bar -->
                                                <div class="flex-1">
                                                    <div class="flex justify-between text-sm mb-1 font-main">
                                                        <span
                                                            class="font-medium text-neutral-700">{{ round($engagement->completionPercentage()) }}%
                                                            Complete</span>
                                                        <span class="text-neutral-500">
                                                            {{ $engagement->deliverables->where('status', 'approved')->count() }}/{{ $engagement->deliverables->count() }}
                                                            deliverables
                                                        </span>
                                                    </div>
                                                    <div class="h-2 bg-neutral-200 rounded-full overflow-hidden">
                                                        <div class="h-full bg-secondary rounded-full"
                                                            style="width: {{ $engagement->completionPercentage() }}%">
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Stats -->
                                                <div class="flex divide-x divide-neutral-200 font-main">
                                                    <div class="px-4 first:pl-0 last:pr-0">
                                                        <div class="text-xs text-neutral-500 mb-1">Pending</div>
                                                        <div class="text-lg text-center font-medium text-neutral-800">
                                                            {{ $engagement->deliverables->where('status', 'pending')->count() }}
                                                        </div>
                                                    </div>
                                                    <div class="px-4">
                                                        <div class="text-xs text-neutral-500 mb-1">Submitted</div>
                                                        <div class="text-lg text-center font-medium text-accent">
                                                            {{ $engagement->deliverables->where('status', 'submitted')->count() }}
                                                        </div>
                                                    </div>
                                                    <div class="px-4">
                                                        <div class="text-xs text-neutral-500 mb-1">Approved</div>
                                                        <div class="text-lg text-center font-medium text-secondary">
                                                            {{ $engagement->deliverables->where('status', 'approved')->count() }}
                                                        </div>
                                                    </div>
                                                    <div class="px-4 last:pr-0">
                                                        <div class="text-xs text-neutral-500 mb-1">Rejected</div>
                                                        <div class="text-lg text-center font-medium text-red-800">
                                                            {{ $engagement->deliverables->where('status', 'rejected')->count() }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <!-- Modals -->
                                <!-- 1. Approve Deliverable Modal -->
                                @foreach ($engagement->deliverables as $deliverable)
                                    @if ($deliverable->status === 'submitted')
                                        <div x-data="{ feedback: '' }">
                                            <x-modal name="approve-deliverable-{{ $deliverable->id }}"
                                                :show="false" max-width="2xl" focusable>
                                                <div class="p-8">
                                                    <!-- Header -->
                                                    <div class="text-center mb-6">
                                                        <div
                                                            class="flex items-center justify-center text-secondary mb-4">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16"
                                                                viewBox="0 0 24 24" fill="none">
                                                                <circle cx="12" cy="12" r="10"
                                                                    class="stroke-secondary/20" stroke-width="2">
                                                                </circle>
                                                                <path class="stroke-secondary" stroke-width="2"
                                                                    stroke-linecap="round" stroke-linejoin="round"
                                                                    d="M8 12l3 3 6-6"></path>
                                                            </svg>
                                                        </div>
                                                        <h2 class="text-xl font-bold text-neutral-800">Approve
                                                            Deliverable</h2>
                                                        <p class="text-neutral-600 mt-2">
                                                            You're approving "<span
                                                                class="font-medium">{{ $deliverable->title }}</span>"
                                                        </p>
                                                    </div>

                                                    <!-- Divider -->
                                                    <div class="border-t border-neutral-200 my-6"></div>

                                                    <!-- Form -->
                                                    <form method="POST"
                                                        action="{{ route('engagements.deliverables.approve', $deliverable->id) }}">
                                                        @csrf
                                                        <div class="mb-6">
                                                            <label for="feedback-{{ $deliverable->id }}"
                                                                class="block text-sm font-medium text-neutral-700 mb-2">
                                                                Feedback <span
                                                                    class="text-neutral-500 text-xs">(Optional)</span>
                                                            </label>
                                                            <div class="relative">
                                                                <textarea id="feedback-{{ $deliverable->id }}" x-model="feedback" rows="4" name="feedback"
                                                                    class="w-full rounded-lg border-neutral-300 shadow-sm focus:border-secondary focus:ring focus:ring-secondary/20 text-neutral-700 resize-none"
                                                                    placeholder="Share any comments or feedback about this deliverable..."></textarea>
                                                                <div class="absolute right-3 bottom-3 text-xs text-neutral-500"
                                                                    x-text="feedback.length + ' characters'"></div>
                                                            </div>
                                                        </div>

                                                        <div class="flex justify-between items-center">
                                                            <div class="text-sm text-neutral-500">
                                                                <div class="flex items-center">
                                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                                        class="h-4 w-4 mr-1" fill="none"
                                                                        viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path stroke-linecap="round"
                                                                            stroke-linejoin="round" stroke-width="2"
                                                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                    </svg>
                                                                    This action cannot be undone
                                                                </div>
                                                            </div>

                                                            <div class="flex gap-3">
                                                                <button type="button"
                                                                    @click="$dispatch('close-modal', 'approve-deliverable-{{ $deliverable->id }}')"
                                                                    class="px-4 py-2 text-sm font-medium text-neutral-700 bg-white border border-neutral-300 rounded-md hover:bg-neutral-50 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500">
                                                                    Cancel
                                                                </button>
                                                                <button type="submit"
                                                                    class="px-4 py-2 text-sm font-medium text-white bg-secondary rounded-md hover:bg-secondary/90 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-secondary">
                                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                                        class="h-4 w-4 mr-1.5 inline" fill="none"
                                                                        viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path stroke-linecap="round"
                                                                            stroke-linejoin="round" stroke-width="2"
                                                                            d="M5 13l4 4L19 7" />
                                                                    </svg>
                                                                    Approve Deliverable
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </x-modal>
                                        </div>
                                    @endif
                                @endforeach

                                <!-- 2. Reject Deliverable Modal -->
                                @foreach ($engagement->deliverables as $deliverable)
                                    @if ($deliverable->status === 'submitted')
                                        <div x-data="{ feedback: '' }">
                                            <x-modal name="reject-deliverable-{{ $deliverable->id }}"
                                                :show="false" max-width="2xl" focusable>
                                                <div class="p-8">
                                                    <!-- Header -->
                                                    <div class="text-center mb-6">
                                                        <div class="flex items-center justify-center text-accent mb-4">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16"
                                                                viewBox="0 0 24 24" fill="none">
                                                                <circle cx="12" cy="12" r="10"
                                                                    class="stroke-accent/20" stroke-width="2">
                                                                </circle>
                                                                <path class="stroke-accent" stroke-width="2"
                                                                    stroke-linecap="round" stroke-linejoin="round"
                                                                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                                                                </path>
                                                            </svg>
                                                        </div>
                                                        <h2 class="text-xl font-bold text-neutral-800">Reject
                                                            Deliverable</h2>
                                                        <p class="text-neutral-600 mt-2">
                                                            You're rejecting "<span
                                                                class="font-medium">{{ $deliverable->title }}</span>"
                                                        </p>
                                                        <p class="text-neutral-500 text-sm mt-1">
                                                            Please include detailed feedback to help the freelancer
                                                            understand why the work was rejected
                                                        </p>
                                                    </div>

                                                    <!-- Divider -->
                                                    <div class="border-t border-neutral-200 my-6"></div>

                                                    <!-- Form -->
                                                    <form method="POST"
                                                        action="{{ route('engagements.deliverables.reject', $deliverable->id) }}">
                                                        @csrf
                                                        <div class="mb-6">
                                                            <label for="reject-feedback-{{ $deliverable->id }}"
                                                                class="block text-sm font-medium text-neutral-700 mb-2">
                                                                Feedback <span class="text-accent font-medium">*</span>
                                                            </label>
                                                            <div class="relative">
                                                                <textarea id="reject-feedback-{{ $deliverable->id }}" x-model="feedback" rows="4" name="feedback"
                                                                    class="w-full rounded-lg border-neutral-300 shadow-sm focus:border-accent focus:ring focus:ring-accent/20 text-neutral-700 resize-none"
                                                                    placeholder="Explain what needs to be improved or corrected..." required></textarea>
                                                                <div class="absolute right-3 bottom-3 text-xs"
                                                                    :class="{
                                                                        'text-neutral-500': feedback.length >
                                                                            0,
                                                                        'text-accent': feedback.length === 0
                                                                    }"
                                                                    x-text="feedback.length + ' characters'"></div>
                                                            </div>
                                                            <p class="mt-1 text-xs text-neutral-500">Your feedback will
                                                                be shared with the freelancer so they can make necessary
                                                                improvements.</p>
                                                        </div>

                                                        <div class="flex justify-between items-center">
                                                            <div class="text-sm text-neutral-500">
                                                                <div class="flex items-center">
                                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                                        class="h-4 w-4 mr-1" fill="none"
                                                                        viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path stroke-linecap="round"
                                                                            stroke-linejoin="round" stroke-width="2"
                                                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                    </svg>
                                                                    This action cannot be undone
                                                                </div>
                                                            </div>

                                                            <div class="flex gap-3">
                                                                <button type="button"
                                                                    @click="$dispatch('close-modal', 'reject-deliverable-{{ $deliverable->id }}')"
                                                                    class="px-4 py-2 text-sm font-medium text-neutral-700 bg-white border border-neutral-300 rounded-md hover:bg-neutral-50 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500">
                                                                    Cancel
                                                                </button>
                                                                <button type="submit"
                                                                    class="px-4 py-2 text-sm font-medium text-white bg-accent rounded-md hover:bg-accent/90 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-accent"
                                                                    :disabled="feedback.length === 0"
                                                                    :class="{
                                                                        'opacity-50 cursor-not-allowed': feedback
                                                                            .length === 0
                                                                    }">
                                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                                        class="h-4 w-4 mr-1.5 inline" fill="none"
                                                                        viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path stroke-linecap="round"
                                                                            stroke-linejoin="round" stroke-width="2"
                                                                            d="M6 18L18 6M6 6l12 12" />
                                                                    </svg>
                                                                    Reject Deliverable
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </x-modal>
                                        </div>
                                    @endif
                                @endforeach

                                <!-- 3. Edit Deliverable Modal -->
                                @foreach ($engagement->deliverables as $deliverable)
                                    <div x-data="{
                                        title: '{{ $deliverable->title }}',
                                        description: '{{ $deliverable->description }}',
                                        dueDate: '{{ $deliverable->due_date ? $deliverable->due_date->format('Y-m-d') : '' }}'
                                    }">
                                        <x-modal name="edit-deliverable-{{ $deliverable->id }}" :show="false"
                                            max-width="2xl" focusable>
                                            <div class="p-8">
                                                <!-- Header -->
                                                <div class="text-center mb-6">
                                                    <div class="flex items-center justify-center text-primary mb-4">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16"
                                                            viewBox="0 0 24 24" fill="none">
                                                            <circle cx="12" cy="12" r="10"
                                                                class="stroke-primary/20" stroke-width="2"></circle>
                                                            <path class="stroke-primary" stroke-width="2"
                                                                stroke-linecap="round" stroke-linejoin="round"
                                                                d="M8 12h8m-4-4v8"></path>
                                                        </svg>
                                                    </div>
                                                    <h2 class="text-xl font-bold text-neutral-800">Edit Deliverable
                                                    </h2>
                                                    <p class="text-neutral-600 mt-2">
                                                        Update the details for this deliverable
                                                    </p>
                                                </div>

                                                <!-- Divider -->
                                                <div class="border-t border-neutral-200 my-6"></div>

                                                <!-- Form -->
                                                <form method="POST"
                                                    action="{{ route('engagements.deliverables.update', $deliverable->id) }}">
                                                    @csrf
                                                    @method('PATCH')

                                                    <!-- Title Field -->
                                                    <div class="mb-5">
                                                        <label for="title-{{ $deliverable->id }}"
                                                            class="block text-sm font-medium text-neutral-700 mb-2">
                                                            Title
                                                        </label>
                                                        <input type="text" id="title-{{ $deliverable->id }}"
                                                            name="title" x-model="title"
                                                            class="w-full rounded-lg border-neutral-300 shadow-sm focus:border-primary"
                                                            required>
                                                    </div>

                                                    <!-- Description Field -->
                                                    <div class="mb-5">
                                                        <label for="description-{{ $deliverable->id }}"
                                                            class="block text-sm font-medium text-neutral-700 mb-2">
                                                            Description
                                                        </label>
                                                        <textarea id="description-{{ $deliverable->id }}" name="description" x-model="description" rows="4"
                                                            class="w-full rounded-lg border-neutral-300 shadow-sm focus:border-primary resize-none"></textarea>
                                                    </div>

                                                    <!-- Due Date Field -->
                                                    <div class="mb-6">
                                                        <label for="due_date-{{ $deliverable->id }}"
                                                            class="block text-sm font-medium text-neutral-700 mb-2">
                                                            Due Date
                                                        </label>
                                                        <div class="relative">
                                                            <input type="date"
                                                                id="due_date-{{ $deliverable->id }}" name="due_date"
                                                                x-model="dueDate"
                                                                class="w-full rounded-lg border-neutral-300 shadow-sm ">
                                                            <div
                                                                class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                                    class="h-5 w-5 text-neutral-400" fill="none"
                                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round" stroke-width="2"
                                                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                                </svg>
                                                            </div>
                                                        </div>
                                                        <p class="mt-1 text-xs text-neutral-500">Please select a future
                                                            date</p>
                                                    </div>

                                                    <!-- Actions -->
                                                    <div class="flex justify-end gap-3">
                                                        <button type="button"
                                                            @click="$dispatch('close-modal', 'edit-deliverable-{{ $deliverable->id }}')"
                                                            class="px-4 py-2 text-sm font-medium text-neutral-700 bg-white border border-neutral-300 rounded-md hover:bg-neutral-50 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500">
                                                            Cancel
                                                        </button>
                                                        <button type="submit"
                                                            class="px-4 py-2 text-sm font-medium text-white bg-primary rounded-md hover:bg-primary/90 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                                class="h-4 w-4 mr-1.5 inline" fill="none"
                                                                viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2" d="M5 13l4 4L19 7" />
                                                            </svg>
                                                            Save Changes
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </x-modal>
                                    </div>
                                @endforeach

                                <!-- 4. Delete Deliverable Modal -->
                                @foreach ($engagement->deliverables as $deliverable)
                                    <div>
                                        <x-modal name="remove-deliverable-{{ $deliverable->id }}" :show="false"
                                            max-width="2xl" focusable>
                                            <div class="p-8">
                                                <!-- Header -->
                                                <div class="text-center mb-6">
                                                    <div class="flex items-center justify-center text-red-500 mb-4">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16"
                                                            viewBox="0 0 24 24" fill="none">
                                                            <circle cx="12" cy="12" r="10"
                                                                class="stroke-red-100" stroke-width="2"></circle>
                                                            <path class="stroke-red-500" stroke-width="2"
                                                                stroke-linecap="round" stroke-linejoin="round"
                                                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                                                            </path>
                                                        </svg>
                                                    </div>
                                                    <h2 class="text-xl font-bold text-neutral-800">Remove Deliverable
                                                    </h2>
                                                    <p class="text-neutral-600 mt-2">
                                                        Are you sure you want to remove "<span
                                                            class="font-medium">{{ $deliverable->title }}</span>"?
                                                    </p>
                                                    <p class="text-neutral-500 text-sm mt-1">
                                                        This action cannot be undone.
                                                    </p>
                                                </div>

                                                <!-- Divider -->
                                                <div class="border-t border-neutral-200 my-6"></div>

                                                <!-- Form -->
                                                <form method="POST"
                                                    action="{{ route('engagements.deliverables.destroy', $deliverable->id) }}">
                                                    @csrf
                                                    @method('DELETE')

                                                    <div class="bg-red-50 border border-red-100 rounded-lg p-4 mb-6">
                                                        <div class="flex">
                                                            <div class="flex-shrink-0">
                                                                <svg class="h-5 w-5 text-red-400"
                                                                    xmlns="http://www.w3.org/2000/svg"
                                                                    viewBox="0 0 20 20" fill="currentColor">
                                                                    <path fill-rule="evenodd"
                                                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                                                        clip-rule="evenodd" />
                                                                </svg>
                                                            </div>
                                                            <div class="ml-3">
                                                                <p class="text-sm text-red-700">
                                                                    Removing this deliverable will permanently delete it
                                                                    from the system. Any submitted work or feedback
                                                                    associated with it will be lost.
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="flex justify-end gap-3">
                                                        <button type="button"
                                                            @click="$dispatch('close-modal', 'remove-deliverable-{{ $deliverable->id }}')"
                                                            class="px-4 py-2 text-sm font-medium text-neutral-700 bg-white border border-neutral-300 rounded-md hover:bg-neutral-50 transition-colors focus:outline-none">
                                                            Cancel
                                                        </button>
                                                        <button type="submit"
                                                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 transition-colors shadow-sm focus:outline-none">
                                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                                class="h-4 w-4 mr-1.5 inline" fill="none"
                                                                viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
                                                            Permanently Remove
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </x-modal>
                                    </div>
                                @endforeach

                                <!-- 5. Add Deliverable Modal using x-modal component -->
                                <div id="deliverableModalBackdrop"
                                    class="fixed inset-0 bg-neutral-900/70 z-40 hidden">
                                    <!-- Modal Container -->
                                    <div id="deliverableModal"
                                        class="fixed left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-2xl bg-white rounded-xl shadow-xl z-50">
                                        <!-- Modal Header -->
                                        <div class="bg-primary px-6 py-4 flex justify-between items-center">
                                            <h3 class="text-xl font-tertiary font-bold text-white flex items-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                                </svg>
                                                Add New Deliverable
                                            </h3>
                                            <button type="button" id="closeDeliverableModal"
                                                class="text-white hover:text-neutral-200 transition-colors">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>

                                        <!-- Modal Body -->
                                        <div class="p-6">
                                            <form
                                                action="{{ route('engagements.deliverables.store', ['engagement' => $engagement->id]) }}"
                                                method="POST">
                                                @csrf

                                                <!-- Title Field -->
                                                <div class="mb-4">
                                                    <label for="title"
                                                        class="block font-tertiary font-medium text-neutral-700 mb-1">
                                                        <span class="flex items-center">
                                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                                class="h-5 w-5 mr-1 text-secondary" fill="none"
                                                                viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                            </svg>
                                                            Title
                                                        </span>
                                                    </label>
                                                    <input type="text" id="del_title" name="del_title" required
                                                        class="w-full px-3 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary focus:border-transparent"
                                                        placeholder="Enter deliverable title">
                                                </div>

                                                <!-- Description Field -->
                                                <div class="mb-4">
                                                    <label for="description"
                                                        class="block font-tertiary font-medium text-neutral-700 mb-1">
                                                        <span class="flex items-center">
                                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                                class="h-5 w-5 mr-1 text-secondary" fill="none"
                                                                viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                                                            </svg>
                                                            Description
                                                        </span>
                                                    </label>
                                                    <textarea id="del_description" name="del_description" rows="4"
                                                        class="w-full px-3 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary focus:border-transparent"
                                                        placeholder="Describe what needs to be delivered"></textarea>
                                                </div>

                                                <!-- Due Date Field -->
                                                <div class="mb-6">
                                                    <label for="due_date"
                                                        class="block font-tertiary font-medium text-neutral-700 mb-1">
                                                        <span class="flex items-center">
                                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                                class="h-5 w-5 mr-1 text-secondary" fill="none"
                                                                viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                            </svg>
                                                            Due Date
                                                        </span>
                                                    </label>
                                                    <input type="date" id="due_date" name="due_date"
                                                        class="w-full px-3 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary focus:border-transparent">
                                                </div>

                                                <!-- Action Buttons -->
                                                <div class="flex justify-end gap-3">
                                                    <button type="button" id="cancelDeliverableBtn"
                                                        class="px-4 py-2 border border-neutral-300 text-neutral-700 font-tertiary rounded-lg hover:bg-neutral-100 transition-colors">
                                                        Cancel
                                                    </button>
                                                    <button type="submit"
                                                        class="px-4 py-2 bg-secondary text-white font-tertiary font-medium rounded-lg hover:bg-secondary/90 transition-colors flex items-center">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                        Save Deliverable
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Card Footer -->
                        <div class="bg-white border-t border-neutral-200">
                            <div class="py-2 px-4">
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 font-main">
                                    <div
                                        class="flex items-start bg-neutral-50 p-4 rounded-lg transition-all hover:shadow-sm">
                                        <div class="bg-secondary/10 p-2 rounded-lg mr-3">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-secondary"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-xs text-neutral-500 mb-1">Job Posted</p>
                                            <p class="text-sm font-medium text-neutral-700">
                                                {{ $engagement->application->job->created_at->format('M j, Y') }}
                                            </p>
                                        </div>
                                    </div>

                                    @if ($isApplicant)
                                        <div
                                            class="flex items-start bg-neutral-50 p-4 rounded-lg transition-all hover:shadow-sm">
                                            <div class="bg-primary/10 p-2 rounded-lg mr-3">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-xs text-neutral-500 mb-1">Job Poster</p>
                                                <p class="text-sm font-medium text-neutral-700">
                                                    {{ $engagement->application->poster->name }}</p>
                                            </div>
                                        </div>
                                    @endif

                                    @if ($isPoster)
                                        <div
                                            class="flex items-start bg-neutral-50 p-4 rounded-lg transition-all hover:shadow-sm">
                                            <div class="bg-primary/10 p-2 rounded-lg mr-3">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-xs text-neutral-500 mb-1">Applicant</p>
                                                <p class="text-sm font-medium text-neutral-700">
                                                    {{ $engagement->application->applicant->name }}</p>
                                            </div>
                                        </div>
                                    @endif

                                    <div
                                        class="flex items-start bg-neutral-50 p-4 rounded-lg transition-all hover:shadow-sm">
                                        <div class="bg-secondary/10 p-2 rounded-lg mr-3">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-secondary"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-xs text-neutral-500 mb-1">
                                                {{ $isApplicant ? 'Application Date' : 'Applied On' }}
                                            </p>
                                            <p class="text-sm font-medium text-neutral-700">
                                                {{ $engagement->application->created_at->format('M j, Y') }}</p>
                                        </div>
                                    </div>

                                    <div
                                        class="flex items-start bg-neutral-50 p-4 rounded-lg transition-all hover:shadow-sm">
                                        <div class="bg-primary/10 p-2 rounded-lg mr-3">
                                            <svg xmlns="http://www.w3.org/2000/svg" stroke="currentColor"
                                                fill="currentColor" class="h-5 w-5  text-primary"
                                                viewBox="0 0 512 512">
                                                <path
                                                    d="M512 80c0 18-14.3 34.6-38.4 48c-29.1 16.1-72.5 27.5-122.3 30.9c-3.7-1.8-7.4-3.5-11.3-5C300.6 137.4 248.2 128 192 128c-8.3 0-16.4 .2-24.5 .6l-1.1-.6C142.3 114.6 128 98 128 80c0-44.2 86-80 192-80S512 35.8 512 80zM160.7 161.1c10.2-.7 20.7-1.1 31.3-1.1c62.2 0 117.4 12.3 152.5 31.4C369.3 204.9 384 221.7 384 240c0 4-.7 7.9-2.1 11.7c-4.6 13.2-17 25.3-35 35.5c0 0 0 0 0 0c-.1 .1-.3 .1-.4 .2c0 0 0 0 0 0s0 0 0 0c-.3 .2-.6 .3-.9 .5c-35 19.4-90.8 32-153.6 32c-59.6 0-112.9-11.3-148.2-29.1c-1.9-.9-3.7-1.9-5.5-2.9C14.3 274.6 0 258 0 240c0-34.8 53.4-64.5 128-75.4c10.5-1.5 21.4-2.7 32.7-3.5zM416 240c0-21.9-10.6-39.9-24.1-53.4c28.3-4.4 54.2-11.4 76.2-20.5c16.3-6.8 31.5-15.2 43.9-25.5l0 35.4c0 19.3-16.5 37.1-43.8 50.9c-14.6 7.4-32.4 13.7-52.4 18.5c.1-1.8 .2-3.5 .2-5.3zm-32 96c0 18-14.3 34.6-38.4 48c-1.8 1-3.6 1.9-5.5 2.9C304.9 404.7 251.6 416 192 416c-62.8 0-118.6-12.6-153.6-32C14.3 370.6 0 354 0 336l0-35.4c12.5 10.3 27.6 18.7 43.9 25.5C83.4 342.6 135.8 352 192 352s108.6-9.4 148.1-25.9c7.8-3.2 15.3-6.9 22.4-10.9c6.1-3.4 11.8-7.2 17.2-11.2c1.5-1.1 2.9-2.3 4.3-3.4l0 3.4 0 5.7 0 26.3zm32 0l0-32 0-25.9c19-4.2 36.5-9.5 52.1-16c16.3-6.8 31.5-15.2 43.9-25.5l0 35.4c0 10.5-5 21-14.9 30.9c-16.3 16.3-45 29.7-81.3 38.4c.1-1.7 .2-3.5 .2-5.3zM192 448c56.2 0 108.6-9.4 148.1-25.9c16.3-6.8 31.5-15.2 43.9-25.5l0 35.4c0 44.2-86 80-192 80S0 476.2 0 432l0-35.4c12.5 10.3 27.6 18.7 43.9 25.5C83.4 438.6 135.8 448 192 448z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-xs text-neutral-500 mb-1">Budget</p>
                                            <p class="text-sm font-medium text-neutral-700">
                                                Ksh{{ number_format($engagement->agreed_amount, 2) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action buttons -->
                                @if ($isApplicant && $engagement->status === 'employer_accepted')
                                    <div class="pt-2 flex justify-end">
                                        <a href="{{ route('engagements.response-form', ['applicationId' => $engagement->application_id]) }}"
                                            class="inline-flex items-center px-5 py-2.5 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-gradient-to-r from-primary to-primary/90 hover:from-primary/90 hover:to-primary transition-all duration-300">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
                                            </svg>
                                            Respond to Offer
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Create new deliverable script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const openModalBtn = document.getElementById('openDeliverableModal');
            const closeModalBtn = document.getElementById('closeDeliverableModal');
            const cancelBtn = document.getElementById('cancelDeliverableBtn');
            const modal = document.getElementById('deliverableModal');
            const backdrop = document.getElementById('deliverableModalBackdrop');

            // Set minimum date for due_date to tomorrow
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            const dueDateInput = document.getElementById('due_date');
            dueDateInput.min = tomorrow.toISOString().split('T')[0];

            // Open modal function
            function openModal() {
                backdrop.classList.remove('hidden');
                document.body.style.overflow = 'hidden'; // Prevent scrolling
            }

            // Close modal function
            function closeModal() {
                backdrop.classList.add('hidden');
                document.body.style.overflow = ''; // Enable scrolling
            }

            // Event listeners
            openModalBtn.addEventListener('click', openModal);
            closeModalBtn.addEventListener('click', closeModal);
            cancelBtn.addEventListener('click', closeModal);

            // Close modal when clicking outside
            backdrop.addEventListener('click', function(event) {
                if (event.target === backdrop) {
                    closeModal();
                }
            });

            // Close modal with ESC key
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape' && !backdrop.classList.contains('hidden')) {
                    closeModal();
                }
            });

            // Show the modal automatically if there are validation errors
            @if ($errors->any())
                openModal();
            @endif

            // Show the modal automatically if returning after form submission with an error
            @if (session('error'))
                openModal();
            @endif
        });
    </script>
</x-app-layout>
