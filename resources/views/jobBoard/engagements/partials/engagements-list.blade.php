@if ($engagements->isEmpty() && $hasFilters)
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-neutral-200">
        <div class="p-12 flex flex-col items-center justify-center text-center font-main">
            <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center mb-6">
                <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
                </svg>
            </div>

            <h3 class="text-lg font-semibold text-neutral-800 mb-2">No Engagements Found</h3>
            <p class="text-neutral-600 mb-6 max-w-md">
                We couldn't find any present engagements matching your current search criteria. Try adjusting your
                filters or
                search terms.
            </p>

            <a href="#" id="clearEngagementFilters"
                class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors duration-150">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                    </path>
                </svg>
                Clear All Filters
            </a>
        </div>
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
                $progressPercentage = $totalDeliverables > 0 ? ($completedDeliverables / $totalDeliverables) * 100 : 0;
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
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-1" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        @if ($engagement->status === 'employer_accepted')
                                            <!-- Bell icon -->
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                        @elseif($engagement->status === 'active')
                                            <!-- Clock icon -->
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        @elseif($engagement->status === 'completed')
                                            <!-- Check icon -->
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        @elseif($engagement->status === 'cancelled')
                                            <!-- Exclamation icon -->
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
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
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-1" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                    Respond
                                </a>
                            @endif

                            <button class="flex items-center text-neutral-500 hover:text-neutral-700" x-cloak>
                                <span class="text-sm mr-1" x-text="open ? 'Hide Details' : 'View Details'"></span>
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 transition-transform duration-200"
                                    :class="{ 'transform rotate-180': open }" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Progress bar -->
                    <div class="px-6 pb-4 font-main" x-cloak>
                        <div class="h-1.5 w-full bg-neutral-100 rounded-full overflow-hidden">
                            <div class="h-full bg-secondary rounded-full" style="width: {{ $progressPercentage }}%">
                            </div>
                        </div>
                        <div class="flex justify-between mt-1 text-xs text-neutral-500">
                            <span>Progress</span>
                            <span>{{ $completedDeliverables }}/{{ $totalDeliverables }} Deliverables</span>
                        </div>
                    </div>
                </div>

                <!-- Expandable Content -->
                <div x-show="open" x-collapse x-cloak class="border-t border-neutral-200 bg-neutral-50">
                    <!-- Engagement Details Row -->
                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 p-4 mx-auto bg-gradient-to-r from-neutral-50 to-neutral-100 border border-neutral-200">
                        <!-- Started Date -->
                        <div
                            class="flex items-center space-x-3 bg-white p-3 rounded-lg shadow-sm border border-neutral-100">
                            <div
                                class="flex-shrink-0 h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-neutral-500 font-main">Started on</p>
                                <p class="font-medium text-neutral-800">
                                    {{ $engagement->started_at ? $engagement->started_at->format('M d, Y') : 'Not started yet' }}
                                </p>
                            </div>
                        </div>

                        <!-- Agreed Amount -->
                        <div
                            class="flex items-center space-x-3 bg-white p-3 rounded-lg shadow-sm border border-neutral-100">
                            <div
                                class="flex-shrink-0 h-10 w-10 rounded-full bg-secondary/10 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="h-5 w-5 text-secondary">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-neutral-500 font-main">Agreed amount</p>
                                <p class="font-medium text-neutral-800">
                                    Ksh{{ number_format($engagement->net_amount, 2) }}</p>
                            </div>
                        </div>

                        <!-- Payment Status -->
                        <div
                            class="flex items-center space-x-3 bg-white p-3 rounded-lg shadow-sm border border-neutral-100">
                            <div
                                class="flex-shrink-0 h-10 w-10 rounded-full 
                                    @if ($engagement->isPaymentEscrowed()) bg-secondary/10 
                                    @elseif($engagement->payment_released_at) 
                                        bg-green-100 
                                    @else 
                                        bg-neutral-200 @endif
                                    flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 
                                        @if ($engagement->isPaymentEscrowed()) text-secondary 
                                        @elseif($engagement->payment_released_at) 
                                            text-green-600 
                                        @else 
                                        text-neutral-500 @endif"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-neutral-500 font-main">Payment status</p>
                                <p
                                    class="font-medium 
                                        @if ($engagement->isPaymentEscrowed()) text-secondary 
                                        @elseif($engagement->payment_released_at) 
                                            text-green-600 
                                        @else 
                                            text-neutral-600 @endif">
                                    @if ($engagement->isPaymentEscrowed())
                                        Escrowed
                                    @elseif($engagement->payment_released_at)
                                        Released
                                    @else
                                        Not processed
                                    @endif
                                </p>
                            </div>
                        </div>

                        <!-- Message Action -->
                        <div
                            class="flex items-center space-x-3 bg-white p-3 rounded-lg shadow-sm border border-neutral-100">
                            <div
                                class="flex-shrink-0 h-10 w-10 rounded-full bg-accent/10 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-accent" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-4l-4 4z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-neutral-500 font-main">Communication</p>
                                @if ($engagement->status === 'active')
                                    <button
                                        class="mt-1 inline-flex items-center px-3 py-1 text-xs font-medium rounded-full text-accent bg-accent/10 hover:bg-accent/20 transition-colors border border-accent/20">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                        </svg>
                                        Message
                                    </button>
                                @else
                                    <p class="font-medium text-neutral-600">Not available</p>
                                @endif
                            </div>
                        </div>
                    </div>

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
                                    <div x-data="{ expanded: false, fileSubmissionOpen: false }"
                                        class="bg-white p-5 border border-neutral-200 rounded-lg shadow-sm hover:shadow transition-all duration-200 mb-4">

                                        <div @click="expanded = !expanded" class="pb-2 cursor-pointer">
                                            <div class="flex justify-between items-start">
                                                <div class="flex items-center">
                                                    <div
                                                        class="rounded-full p-1.5 mr-3
                                                                    @if ($deliverable->approved_at) bg-green-100 text-green-800 
                                                                    @elseif ($deliverable->rejected_at) bg-red-100 text-red-800
                                                                    @elseif ($deliverable->submitted_at) bg-secondary/10 text-secondary
                                                                    @elseif($deliverable->due_date && now()->gt($deliverable->due_date)) bg-red-100 text-red-600
                                                                    @else bg-neutral-200 text-neutral-500 @endif">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            @if ($deliverable->approved_at)
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                            @elseif ($deliverable->rejected_at)
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                            @elseif ($deliverable->submitted_at)
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2" d="M5 13l4 4L19 7" />
                                                            @elseif($deliverable->due_date && now()->gt($deliverable->due_date))
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                            @else
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
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
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        Approved
                                                    @elseif($deliverable->rejected_at)
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                        Rejected
                                                    @elseif($deliverable->submitted_at)
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                        Submitted
                                                    @elseif($deliverable->isOverdue())
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                        </svg>
                                                        Overdue
                                                    @else
                                                        Pending
                                                    @endif
                                                </span>
                                            </div>

                                            <p class="font-main text-justify text-sm text-neutral-600 mt-3 ml-9">
                                                {{ $deliverable->description }}</p>

                                            <div class="mt-3 ml-9 flex flex-wrap gap-y-2 gap-x-4 text-xs font-main">
                                                @if ($deliverable->due_date)
                                                    <div
                                                        class="flex items-center {{ $deliverable->isOverdue() ? 'text-red-500' : 'text-secondary' }}">
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="h-3.5 w-3.5 mr-1" fill="none"
                                                            viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
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
                                                            <path stroke-linecap="round" stroke-linejoin="round"
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
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        Completed:
                                                        {{ $deliverable->submitted_at->format('M j, Y') }}
                                                    </div>
                                                @endif

                                                @if ($deliverable->isApproved())
                                                    <div class="flex items-center text-secondary">
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="h-3.5 w-3.5 mr-1" fill="none"
                                                            viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
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
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                        Rejected:
                                                        {{ $deliverable->rejected_at->format('M j, Y') }}
                                                    </div>
                                                @endif

                                                @if ($deliverable->feedback)
                                                    <div class="flex items-center text-blue-800 cursor-pointer">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                            viewBox="0 0 24 24" stroke-width="1.5"
                                                            stroke="currentColor" class="h-3.5 w-3.5 mr-1">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
                                                        </svg>
                                                        View Feedback
                                                    </div>
                                                @endif

                                                @if (!$deliverable->submitted_at && !$deliverable->approved_at && !$deliverable->rejected_at)
                                                    <div @click.stop="fileSubmissionOpen = !fileSubmissionOpen"
                                                        class="flex items-center text-primary hover:text-primary/80 cursor-pointer ml-auto">
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="h-3.5 w-3.5 mr-1" fill="none"
                                                            viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                                        </svg>
                                                        Submit Deliverable
                                                    </div>
                                                @elseif ($deliverable->submission_files)
                                                    <div class="flex items-center text-primary">
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="h-3.5 w-3.5 mr-1" fill="none"
                                                            viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                        </svg>
                                                        {{ count($deliverable->submission_files) }}
                                                        {{ Str::plural('File', count($deliverable->submission_files)) }}
                                                        Submitted
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        @if ($deliverable->feedback)
                                            <div x-show="expanded" x-collapse
                                                class="border-t border-neutral-200 p-5 mt-2 bg-neutral-50 rounded-b-md">
                                                <h6 class="font-tertiary font-semibold text-sm text-neutral-700 mb-2">
                                                    Feedback:</h6>
                                                <p class="text-neutral-600 text-sm font-main">
                                                    {{ $deliverable->feedback }}</p>
                                            </div>
                                        @endif

                                        @if ($deliverable->rejected_at)
                                            <div x-show="expanded" x-collapse
                                                class="border-neutral-200 px-5 pb-5 bg-neutral-50 rounded-b-md">
                                                <button
                                                    x-on:click="$dispatch('open-modal', 'resubmit-deliverable-{{ $deliverable->id }}')"
                                                    type="button"
                                                    class="px-4 py-2 bg-secondary hover:bg-secondary/90 text-white font-tertiary font-medium text-sm rounded-md shadow-sm transition-colors duration-200 flex items-center">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5"
                                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                                    </svg>
                                                    Resubmit Deliverable
                                                </button>
                                            </div>
                                        @endif

                                        <!-- File Submission Section -->
                                        @if ((!$deliverable->submitted_at && !$deliverable->approved_at) || $deliverable->rejected_at)
                                            <div x-show="fileSubmissionOpen" x-collapse
                                                class="border-t border-neutral-200 p-5 mt-2 bg-gradient-to-br from-neutral-50 to-neutral-100 rounded-b-md">

                                                <!-- Rejection Notice (Only shown when rejected) -->
                                                @if ($deliverable->rejected_at)
                                                    <div
                                                        class="mb-4 p-3 bg-accent/10 border border-accent/20 rounded-md">
                                                        <div class="flex items-start">
                                                            <div class="flex-shrink-0">
                                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                                    class="h-5 w-5 text-accent" fill="none"
                                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round" stroke-width="2"
                                                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                                </svg>
                                                            </div>
                                                            <div class="ml-3">
                                                                <h3
                                                                    class="font-tertiary font-semibold text-sm text-neutral-800">
                                                                    Deliverable Rejected</h3>
                                                                <div class="mt-1 text-sm text-neutral-600 font-main">
                                                                    <p>This deliverable has been rejected.
                                                                        Please resubmit with improved files.</p>
                                                                    @if ($deliverable->feedback)
                                                                        <p class="mt-2 italic">Reason:
                                                                            "{{ $deliverable->feedback }}"
                                                                        </p>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif

                                                <form
                                                    action="{{ route('engagements.deliverables.submit', $deliverable) }}"
                                                    method="POST" enctype="multipart/form-data" class="space-y-4">
                                                    @csrf

                                                    <div class="space-y-2">
                                                        <h6
                                                            class="font-tertiary font-semibold text-sm text-neutral-700 flex items-center">
                                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                                class="h-4 w-4 mr-1.5 text-secondary" fill="none"
                                                                viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                                            </svg>
                                                            {{ $deliverable->rejected_at ? 'Resubmit Files' : 'Upload Files' }}
                                                        </h6>

                                                        <div x-data="{
                                                            files: [],
                                                            dragOver: false,
                                                            removeFile(index) {
                                                                this.files.splice(index, 1);
                                                                if (this.$refs.fileInput.files) {
                                                                    // Create a new DataTransfer object
                                                                    const dt = new DataTransfer();
                                                        
                                                                    // Add all files except the one we want to remove
                                                                    Array.from(this.$refs.fileInput.files)
                                                                        .filter((_, i) => i !== index)
                                                                        .forEach(file => dt.items.add(file));
                                                        
                                                                    // Set the new FileList to the file input
                                                                    this.$refs.fileInput.files = dt.files;
                                                                }
                                                            }
                                                        }" class="relative">
                                                            <div @dragover.prevent="dragOver = true"
                                                                @dragleave.prevent="dragOver = false"
                                                                @drop.prevent="
                                                                            dragOver = false;
                                                                            const maxFiles = 5;
                                                                            let droppedFiles = [...$event.dataTransfer.files];

                                                                            if (files.length + droppedFiles.length > maxFiles) {
                                                                                alert('You can only upload a maximum of ' + maxFiles + ' files.');
                                                                            } else {
                                                                                files = [...files, ...droppedFiles];

                                                                                // Create a new DataTransfer object to combine existing files with dropped files
                                                                                const dt = new DataTransfer();

                                                                                // Add existing files
                                                                                if ($refs.fileInput.files) {
                                                                                    Array.from($refs.fileInput.files).forEach(file => dt.items.add(file));
                                                                                }

                                                                                // Add dropped files
                                                                                droppedFiles.forEach(file => dt.items.add(file));

                                                                                // Set the new FileList to the file input
                                                                                $refs.fileInput.files = dt.files;
                                                                            }
                                                                        "
                                                                :class="{ 'border-secondary bg-secondary/5': dragOver }"
                                                                class="border-2 border-dashed border-neutral-300 rounded-lg p-6 text-center transition-all duration-200 hover:border-secondary hover:bg-secondary/5 cursor-pointer"
                                                                @click="$refs.fileInput.click()">

                                                                <div
                                                                    class="flex flex-col items-center justify-center space-y-2">
                                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                                        class="h-10 w-10 text-secondary"
                                                                        fill="none" viewBox="0 0 24 24"
                                                                        stroke="currentColor">
                                                                        <path stroke-linecap="round"
                                                                            stroke-linejoin="round" stroke-width="2"
                                                                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                                                    </svg>

                                                                    <div class="space-y-1">
                                                                        <p
                                                                            class="font-tertiary font-semibold text-sm text-neutral-700">
                                                                            {{ $deliverable->rejected_at ? 'Drag and drop updated files or click to replace' : 'Drag and drop files here or click to browse' }}
                                                                        </p>
                                                                        <p class="text-xs text-neutral-500 font-main">
                                                                            Support for common file types (PDF,
                                                                            DOC, XLS, JPG, PNG, etc.)
                                                                        </p>
                                                                        <p class="text-xs text-neutral-500 font-main">
                                                                            Upload up to 5 files (PDF, DOC, XLS,
                                                                            JPG, PNG, etc.)
                                                                        </p>
                                                                    </div>
                                                                </div>

                                                                <input type="file" name="submission_files[]"
                                                                    multiple x-ref="fileInput" class="hidden"
                                                                    @change="
                                                                            const maxFiles = 5;
                                                                            const newFiles = [...$event.target.files];
                                                                                                                    
                                                                            if (files.length + newFiles.length > maxFiles) {
                                                                                alert('You can only upload a maximum of ' + maxFiles + ' files.');
                                                                                // Reset the file input
                                                                                $event.target.value = null;
                                                                            } else {
                                                                                // Create a new DataTransfer object
                                                                                const dt = new DataTransfer();
                                                                                
                                                                                // Add existing files that are already in our files array
                                                                                files.forEach(file => dt.items.add(file));
                                                                                
                                                                                // Add new files
                                                                                newFiles.forEach(file => dt.items.add(file));
                                                                                
                                                                                // Update the files array
                                                                                files = [...files, ...newFiles];
                                                                                
                                                                                // Set the new FileList to the file input
                                                                                $refs.fileInput.files = dt.files;
                                                                            }
                                                                        ">
                                                            </div>

                                                            <!-- File Preview -->
                                                            <div x-show="files.length > 0" class="mt-3">
                                                                <p class="text-xs text-neutral-600 font-main mb-2">
                                                                    <span x-text="files.length"></span>/5 files
                                                                    selected
                                                                    <span x-show="files.length >= 5"
                                                                        class="text-red-500 ml-1">Maximum limit
                                                                        reached</span>
                                                                </p>
                                                                <template x-for="(file, index) in files"
                                                                    :key="index">
                                                                    <div
                                                                        class="flex items-center justify-between bg-white p-2 rounded-md border border-neutral-200 mb-2">
                                                                        <div class="flex items-center space-x-2">
                                                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                                                class="h-5 w-5 text-neutral-500"
                                                                                fill="none" viewBox="0 0 24 24"
                                                                                stroke="currentColor">
                                                                                <path stroke-linecap="round"
                                                                                    stroke-linejoin="round"
                                                                                    stroke-width="2"
                                                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                                            </svg>
                                                                            <span
                                                                                class="text-sm text-neutral-700 font-main truncate max-w-sm"
                                                                                x-text="file.name"></span>
                                                                            <span
                                                                                class="text-xs text-neutral-500 font-main"
                                                                                x-text="(file.size / 1024).toFixed(1) + ' KB'"></span>
                                                                        </div>
                                                                        <button type="button"
                                                                            @click="removeFile(index)"
                                                                            class="text-red-500 hover:text-red-700">
                                                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                                                class="h-4 w-4" fill="none"
                                                                                viewBox="0 0 24 24"
                                                                                stroke="currentColor">
                                                                                <path stroke-linecap="round"
                                                                                    stroke-linejoin="round"
                                                                                    stroke-width="2"
                                                                                    d="M6 18L18 6M6 6l12 12" />
                                                                            </svg>
                                                                        </button>
                                                                    </div>
                                                                </template>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="space-y-2">
                                                        <h6
                                                            class="font-tertiary font-semibold text-sm text-neutral-700 flex items-center">
                                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                                class="h-4 w-4 mr-1.5 text-secondary" fill="none"
                                                                viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                            </svg>
                                                            {{ $deliverable->rejected_at ? 'Updated Notes (Optional)' : 'Submission Notes (Optional)' }}
                                                        </h6>

                                                        <textarea name="submission_notes" rows="3"
                                                            class="w-full px-3 py-2 text-sm font-main text-neutral-700 border border-neutral-300 rounded-md focus:ring-2 focus:ring-secondary/30 focus:border-secondary transition-colors"
                                                            placeholder="{{ $deliverable->rejected_at ? 'Add notes about your updated submission here...' : 'Add any notes about your submission here...' }}"></textarea>
                                                    </div>

                                                    <!-- Previous submission (only show when rejected) -->
                                                    @if ($deliverable->rejected_at && ($deliverable->submission_files || $deliverable->submission_notes))
                                                        <div
                                                            class="p-4 bg-neutral-100 rounded-md border border-neutral-200">
                                                            <div class="flex items-center mb-3">
                                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                                    class="h-5 w-5 text-neutral-500 mr-2"
                                                                    fill="none" viewBox="0 0 24 24"
                                                                    stroke="currentColor">
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round" stroke-width="2"
                                                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                </svg>
                                                                <h6
                                                                    class="font-tertiary font-semibold text-sm text-neutral-700">
                                                                    Previous Submission</h6>
                                                            </div>

                                                            @if ($deliverable->submission_notes)
                                                                <div class="mb-3">
                                                                    <h6
                                                                        class="text-xs font-tertiary font-semibold text-neutral-600 mb-1">
                                                                        Notes:</h6>
                                                                    <p
                                                                        class="text-xs text-neutral-600 font-main bg-white p-2 rounded border border-neutral-200">
                                                                        {{ $deliverable->submission_notes }}
                                                                    </p>
                                                                </div>
                                                            @endif

                                                            @if ($deliverable->submission_files && count($deliverable->submission_files) > 0)
                                                                <div>
                                                                    <h6
                                                                        class="text-xs font-tertiary font-semibold text-neutral-600 mb-1">
                                                                        Files:</h6>
                                                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                                        @foreach ($deliverable->submission_files as $file)
                                                                            <a href="{{ Storage::url($file['path']) }}"
                                                                                target="_blank"
                                                                                class="flex items-center p-1.5 bg-white border border-neutral-200 rounded-md hover:bg-neutral-50 transition-colors">
                                                                                <div
                                                                                    class="rounded-md bg-neutral-100 p-1 mr-2">
                                                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                                                        class="h-3 w-3 text-neutral-500"
                                                                                        fill="none"
                                                                                        viewBox="0 0 24 24"
                                                                                        stroke="currentColor">
                                                                                        <path stroke-linecap="round"
                                                                                            stroke-linejoin="round"
                                                                                            stroke-width="2"
                                                                                            d="M15 15l-6 6m0 0l-6-6m6 6V9a6 6 0 0112 0v3" />
                                                                                    </svg>
                                                                                </div>
                                                                                <div class="truncate">
                                                                                    <span
                                                                                        class="text-xs font-main text-neutral-600">{{ $file['name'] }}</span>
                                                                                </div>
                                                                            </a>
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @endif

                                                    <div class="flex justify-end">
                                                        <button type="submit"
                                                            class="px-4 py-2 bg-secondary hover:bg-secondary/90 text-white font-tertiary font-medium text-sm rounded-md shadow-sm transition-colors duration-200 flex items-center">
                                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                                class="h-4 w-4 mr-1.5" fill="none"
                                                                viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2" d="M5 13l4 4L19 7" />
                                                            </svg>
                                                            {{ $deliverable->rejected_at ? 'Resubmit Deliverable' : 'Submit Deliverable' }}
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        @endif

                                        <!-- Display submission information if already submitted -->
                                        @if (
                                            $deliverable->submitted_at &&
                                                !$deliverable->rejected_at &&
                                                ($deliverable->submission_files || $deliverable->submission_notes))
                                            <div x-show="expanded" x-collapse
                                                class="border-t border-neutral-200 p-5 mt-2 bg-neutral-50 rounded-b-md">
                                                <div class="mb-3 flex items-center">
                                                    <div class="rounded-full bg-secondary/10 p-1 mr-2">
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="h-4 w-4 text-secondary" fill="none"
                                                            viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                    </div>
                                                    <span class="text-sm font-tertiary font-medium text-neutral-700">
                                                        Submitted
                                                        {{ $deliverable->submitted_at->diffForHumans() }}
                                                    </span>
                                                    @if ($deliverable->approved_at)
                                                        <div
                                                            class="ml-2 rounded-full bg-green-100 px-2 py-0.5 flex items-center">
                                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                                class="h-3 w-3 text-green-600 mr-1" fill="none"
                                                                viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                            </svg>
                                                            <span
                                                                class="text-xs font-tertiary font-medium text-green-600">Approved</span>
                                                        </div>
                                                    @endif
                                                </div>

                                                @if ($deliverable->submission_notes)
                                                    <div class="mb-4">
                                                        <h6
                                                            class="font-tertiary font-semibold text-sm text-neutral-700 mb-2 flex items-center">
                                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                                class="h-4 w-4 mr-1.5 text-secondary" fill="none"
                                                                viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                            </svg>
                                                            Submission Notes:
                                                        </h6>
                                                        <p
                                                            class="text-neutral-600 text-sm font-main ml-6 p-3 bg-white rounded-md border border-neutral-200">
                                                            {{ $deliverable->submission_notes }}
                                                        </p>
                                                    </div>
                                                @endif

                                                @if ($deliverable->submission_files && count($deliverable->submission_files) > 0)
                                                    <div>
                                                        <h6
                                                            class="font-tertiary font-semibold text-sm text-neutral-700 mb-2 flex items-center">
                                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                                class="h-4 w-4 mr-1.5 text-secondary" fill="none"
                                                                viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                            </svg>
                                                            Submitted Files:
                                                        </h6>
                                                        <div class="ml-6 grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                            @foreach ($deliverable->submission_files as $file)
                                                                <a href="{{ Storage::url($file['path']) }}"
                                                                    target="_blank"
                                                                    class="flex items-center p-2 bg-white border border-neutral-200 rounded-md hover:bg-neutral-50 transition-colors group">
                                                                    <div
                                                                        class="rounded-md bg-secondary/10 p-1.5 mr-2 group-hover:bg-secondary/20 transition-colors">
                                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                                            class="h-4 w-4 text-secondary"
                                                                            fill="none" viewBox="0 0 24 24"
                                                                            stroke="currentColor">
                                                                            <path stroke-linecap="round"
                                                                                stroke-linejoin="round"
                                                                                stroke-width="2"
                                                                                d="M15 15l-6 6m0 0l-6-6m6 6V9a6 6 0 0112 0v3" />
                                                                        </svg>
                                                                    </div>
                                                                    <div class="truncate flex-grow">
                                                                        <span
                                                                            class="text-xs font-main text-neutral-700">{{ $file['name'] }}</span>
                                                                    </div>
                                                                    <span
                                                                        class="text-xs text-neutral-500 font-main ml-2">{{ round($file['size'] / 1024, 1) }}
                                                                        KB</span>
                                                                </a>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                @empty
                                    <div
                                        class="col-span-2 bg-white p-6 border border-neutral-200 rounded-lg text-center">
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="h-8 w-8 text-neutral-300 mx-auto mb-2" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
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
                                    <h5 class="font-tertiary font-medium text-neutral-800 mb-4 flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-primary"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
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

                        <!-- Resubmit Deliverable -->
                        @foreach ($engagement->deliverables as $deliverable)
                            <x-modal name="resubmit-deliverable-{{ $deliverable->id }}" :show="false"
                                max-width="2xl" focusable>
                                <div class="p-8 font-main">
                                    <!-- Header -->
                                    <div class="text-center mb-6">
                                        <div class="flex items-center justify-center text-secondary mb-4">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16"
                                                viewBox="0 0 24 24" fill="none">
                                                <path
                                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round" />
                                            </svg>
                                        </div>
                                        <h2 class="text-xl font-bold text-neutral-800">Resubmit Deliverable
                                        </h2>
                                        <p class="text-neutral-600 mt-2">Upload updated files and notes for
                                            the
                                            deliverable <br> <span
                                                class="font-medium underline">{{ $deliverable->title }}</span>
                                        </p>
                                    </div>

                                    <!-- Divider -->
                                    <div class="border-t border-neutral-200 my-6"></div>

                                    <!-- Form -->
                                    <form action="{{ route('engagements.deliverables.submit', $deliverable) }}"
                                        method="POST" enctype="multipart/form-data">
                                        @csrf

                                        <!-- Rejection Notice -->
                                        @if ($deliverable->rejected_at)
                                            <div class="mb-4 p-3 bg-accent/10 border border-accent/20 rounded-md">
                                                <div class="flex items-start">
                                                    <div class="flex-shrink-0">
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="h-5 w-5 text-accent" fill="none"
                                                            viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                        </svg>
                                                    </div>
                                                    <div class="ml-3">
                                                        <h3
                                                            class="font-tertiary font-semibold text-sm text-neutral-800">
                                                            Deliverable Rejected</h3>
                                                        <div class="mt-1 text-sm text-neutral-600 font-main">
                                                            <p>This deliverable has been rejected. Please
                                                                resubmit
                                                                with improved files.</p>
                                                            @if ($deliverable->rejection_reason)
                                                                <p class="mt-2 italic">Reason:
                                                                    "{{ $deliverable->rejection_reason }}"</p>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif

                                        <!-- File Upload Section -->
                                        <div class="mb-5">
                                            <label for="submission_files"
                                                class="block text-sm font-medium text-neutral-700 mb-2">
                                                {{ $deliverable->rejected_at ? 'Resubmit Files' : 'Upload Files' }}
                                            </label>
                                            <div x-data="{
                                                files: [],
                                                previewImages: [],
                                                removeFile(index) {
                                                    this.files.splice(index, 1);
                                                    this.previewImages.splice(index, 1);
                                                    if (this.$refs.fileInput.files) {
                                                        const dt = new DataTransfer();
                                                        Array.from(this.$refs.fileInput.files)
                                                            .filter((_, i) => i !== index)
                                                            .forEach(file => dt.items.add(file));
                                                        this.$refs.fileInput.files = dt.files;
                                                    }
                                                },
                                                processFiles(newFiles) {
                                                    const maxFiles = 5;
                                                    if (this.files.length + newFiles.length > maxFiles) {
                                                        alert('You can only upload a maximum of ' + maxFiles + ' files.');
                                                        return false;
                                                    }
                                            
                                                    // Process new files and create previews
                                                    newFiles.forEach(file => {
                                                        this.files.push(file);
                                            
                                                        // Generate preview for images
                                                        if (file.type.startsWith('image/')) {
                                                            const reader = new FileReader();
                                                            reader.onload = (e) => {
                                                                this.previewImages.push({
                                                                    index: this.previewImages.length,
                                                                    src: e.target.result,
                                                                    isImage: true,
                                                                    file: file
                                                                });
                                                            };
                                                            reader.readAsDataURL(file);
                                                        } else {
                                                            // Non-image file
                                                            this.previewImages.push({
                                                                index: this.previewImages.length,
                                                                src: null,
                                                                isImage: false,
                                                                file: file
                                                            });
                                                        }
                                                    });
                                            
                                                    return true;
                                                }
                                            }"
                                                class="border-2 rounded-lg p-6 text-center transition-all duration-200 border-neutral-300 bg-neutral-50">
                                                <div class="flex flex-col items-center justify-center space-y-4">
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="h-10 w-10 text-secondary" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                                    </svg>
                                                    <div class="space-y-2">
                                                        <p
                                                            class="font-tertiary font-semibold text-sm text-neutral-700">
                                                            {{ $deliverable->rejected_at ? 'Select files to upload' : 'Select files to upload' }}
                                                        </p>
                                                        <p class="text-xs text-neutral-500 font-main">
                                                            Support for common file types (PDF, DOC, XLS, JPG,
                                                            PNG, etc.)
                                                        </p>
                                                        <p class="text-xs text-neutral-500 font-main">Upload up
                                                            to 5 files</p>

                                                        <button type="button" @click="$refs.fileInput.click()"
                                                            class="mt-2 px-4 py-2 bg-white border border-neutral-300 rounded-md hover:bg-neutral-50 text-sm font-medium text-neutral-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-secondary">
                                                            Browse Files
                                                        </button>
                                                    </div>
                                                </div>
                                                <input type="file" name="submission_files[]" multiple
                                                    x-ref="fileInput" class="hidden"
                                                    @change="
                                                                    const newFiles = [...$event.target.files];
                                                                    if (processFiles(newFiles)) {
                                                                        // Keep the input files synchronized
                                                                        const dt = new DataTransfer();
                                                                        files.forEach(file => dt.items.add(file));
                                                                        $refs.fileInput.files = dt.files;
                                                                    } else {
                                                                        $event.target.value = null;
                                                                    }
                                                                ">

                                                <!-- File Preview -->
                                                <div x-show="files.length > 0" class="mt-4">
                                                    <p class="text-xs text-neutral-600 font-main mb-2">
                                                        <span x-text="files.length"></span>/5 files selected
                                                        <span x-show="files.length >= 5"
                                                            class="text-red-500 ml-1">Maximum limit
                                                            reached</span>
                                                    </p>

                                                    <!-- Image Preview Grid -->
                                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-3"
                                                        x-show="previewImages.some(item => item.isImage)">
                                                        <template x-for="(item, index) in previewImages"
                                                            :key="index">
                                                            <div x-show="item.isImage"
                                                                class="relative aspect-square rounded-md overflow-hidden border border-neutral-200 bg-white">
                                                                <img :src="item.src"
                                                                    class="w-full h-full object-cover">
                                                                <button type="button" @click="removeFile(index)"
                                                                    class="absolute top-1 right-1 bg-black/50 text-white rounded-full p-1 hover:bg-black/80">
                                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                                        class="h-4 w-4" fill="none"
                                                                        viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path stroke-linecap="round"
                                                                            stroke-linejoin="round" stroke-width="2"
                                                                            d="M6 18L18 6M6 6l12 12" />
                                                                    </svg>
                                                                </button>
                                                            </div>
                                                        </template>
                                                    </div>

                                                    <!-- File List -->
                                                    <div class="space-y-2">
                                                        <template x-for="(item, index) in previewImages"
                                                            :key="index">
                                                            <div
                                                                class="flex items-center justify-between bg-white p-2.5 rounded-md border border-neutral-200">
                                                                <div class="flex items-center space-x-2">
                                                                    <!-- Icon based on file type -->
                                                                    <div class="flex-shrink-0">
                                                                        <svg x-show="item.isImage"
                                                                            xmlns="http://www.w3.org/2000/svg"
                                                                            class="h-5 w-5 text-blue-500"
                                                                            fill="none" viewBox="0 0 24 24"
                                                                            stroke="currentColor">
                                                                            <path stroke-linecap="round"
                                                                                stroke-linejoin="round"
                                                                                stroke-width="2"
                                                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                                        </svg>
                                                                        <svg x-show="!item.isImage"
                                                                            xmlns="http://www.w3.org/2000/svg"
                                                                            class="h-5 w-5 text-neutral-500"
                                                                            fill="none" viewBox="0 0 24 24"
                                                                            stroke="currentColor">
                                                                            <path stroke-linecap="round"
                                                                                stroke-linejoin="round"
                                                                                stroke-width="2"
                                                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                                        </svg>
                                                                    </div>

                                                                    <!-- File info -->
                                                                    <div class="flex-1 min-w-0">
                                                                        <p class="text-sm text-neutral-700 font-main truncate"
                                                                            x-text="item.file.name"></p>
                                                                        <p class="text-xs text-neutral-500 font-main"
                                                                            x-text="(item.file.size / 1024).toFixed(1) + ' KB'">
                                                                        </p>
                                                                    </div>
                                                                </div>

                                                                <!-- Remove button -->
                                                                <button type="button" @click="removeFile(index)"
                                                                    class="ml-2 text-red-500 hover:text-red-700">
                                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                                        class="h-4 w-4" fill="none"
                                                                        viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path stroke-linecap="round"
                                                                            stroke-linejoin="round" stroke-width="2"
                                                                            d="M6 18L18 6M6 6l12 12" />
                                                                    </svg>
                                                                </button>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Notes Field -->
                                        <div class="mb-6">
                                            <label for="submission_notes"
                                                class="block text-sm font-medium text-neutral-700 mb-2">
                                                {{ $deliverable->rejected_at ? 'Updated Notes (Optional)' : 'Submission Notes (Optional)' }}
                                            </label>
                                            <textarea id="submission_notes" name="submission_notes" rows="4"
                                                class="w-full rounded-lg border-neutral-300 shadow-sm focus:border-secondary resize-none"
                                                placeholder="{{ $deliverable->rejected_at ? 'Add notes about your updated submission here...' : 'Add any notes about your submission here...' }}"></textarea>
                                        </div>

                                        <!-- Previous Submission -->
                                        @if ($deliverable->rejected_at && ($deliverable->submission_files || $deliverable->submission_notes))
                                            <div class="p-4 bg-neutral-100 rounded-md border border-neutral-200 mb-6">
                                                <div class="flex items-center mb-3">
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="h-5 w-5 text-neutral-500 mr-2" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    <h6 class="font-tertiary font-semibold text-sm text-neutral-700">
                                                        Previous Submission</h6>
                                                </div>
                                                @if ($deliverable->submission_notes)
                                                    <div class="mb-3">
                                                        <h6
                                                            class="text-xs font-tertiary font-semibold text-neutral-600 mb-1">
                                                            Notes:</h6>
                                                        <p
                                                            class="text-xs text-neutral-600 font-main bg-white p-2 rounded border border-neutral-200">
                                                            {{ $deliverable->submission_notes }}</p>
                                                    </div>
                                                @endif
                                                @if ($deliverable->submission_files && count($deliverable->submission_files) > 0)
                                                    <div>
                                                        <h6
                                                            class="text-xs font-tertiary font-semibold text-neutral-600 mb-1">
                                                            Files:</h6>
                                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                            @foreach ($deliverable->submission_files as $file)
                                                                <a href="{{ Storage::url($file['path']) }}"
                                                                    target="_blank"
                                                                    class="flex items-center p-1.5 bg-white border border-neutral-200 rounded-md hover:bg-neutral-50 transition-colors">
                                                                    <div class="rounded-md bg-neutral-100 p-1 mr-2">
                                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                                            class="h-3 w-3 text-neutral-500"
                                                                            fill="none" viewBox="0 0 24 24"
                                                                            stroke="currentColor">
                                                                            <path stroke-linecap="round"
                                                                                stroke-linejoin="round"
                                                                                stroke-width="2"
                                                                                d="M15 15l-6 6m0 0l-6-6m6 6V9a6 6 0 0112 0v3" />
                                                                        </svg>
                                                                    </div>
                                                                    <div class="truncate">
                                                                        <span
                                                                            class="text-xs font-main text-neutral-600">{{ $file['name'] }}</span>
                                                                    </div>
                                                                </a>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif

                                        <!-- Actions -->
                                        <div class="flex justify-end gap-3">
                                            <button type="button"
                                                @click="$dispatch('close-modal', 'resubmit-deliverable-{{ $deliverable->id }}')"
                                                class="px-4 py-2 text-sm font-medium text-neutral-700 bg-white border border-neutral-300 rounded-md hover:bg-neutral-50 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500">
                                                Cancel
                                            </button>
                                            <button type="submit"
                                                class="px-4 py-2 text-sm font-medium text-white bg-secondary rounded-md hover:bg-secondary/90 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-secondary">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5 inline"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                                Resubmit Deliverable
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </x-modal>
                        @endforeach
                    @endif

                    @if ($isPoster)
                        <div class="p-6">
                            <div class="mb-6 flex justify-between">
                                <h4 class="font-tertiary font-semibold text-primary flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                    Project Deliverables
                                </h4>

                                <!-- Add Deliverable Button -->
                                @if (!$engagement->isCompleted())
                                    <button id="openDeliverableModal"
                                        class="font-tertiary inline-flex items-center px-4 py-2 rounded-lg text-white bg-secondary hover:bg-secondary/90 transition-colors shadow-sm">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 4v16m8-8H4" />
                                        </svg>
                                        Add Deliverable
                                    </button>
                                @endif

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
                                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        @if ($deliverable->status === 'approved')
                                                            <!-- Check icon -->
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M5 13l4 4L19 7" />
                                                        @elseif($deliverable->status === 'rejected')
                                                            <!-- X icon -->
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                        @elseif($deliverable->status === 'submitted')
                                                            <!-- Circle check icon -->
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        @else
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                                        @endif
                                                    </svg>
                                                </div>
                                                <div>
                                                    <h5 class="font-tertiary font-bold text-neutral-800">
                                                        {{ $deliverable->title }}</h5>
                                                    <p class="font-main text-sm text-justify text-neutral-600 mt-2">
                                                        {{ $deliverable->description }}</p>
                                                    <div class="mt-3 flex flex-wrap gap-y-2 gap-x-4 text-xs font-main">
                                                        @if ($deliverable->due_date)
                                                            <div
                                                                class="flex items-center {{ now()->gt($deliverable->due_date) ? 'text-red-500' : 'text-secondary' }}">
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
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                        Approved
                                                    @elseif($deliverable->status === 'rejected')
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                        Rejected
                                                    @elseif($deliverable->status === 'submitted')
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                        </svg>
                                                        Submitted
                                                    @elseif($deliverable->isOverdue())
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
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
                                                <div class="bg-white border border-neutral-200 rounded-lg p-4 mb-4">
                                                    <!-- Submission Notes -->
                                                    <h6 class="font-medium font-tertiary text-primary mb-1">
                                                        Submission Notes
                                                    </h6>
                                                    @if ($deliverable->submission_notes)
                                                        <p
                                                            class="text-neutral-600 text-sm mb-3 font-main text-justify p-3 bg-neutral-50 rounded-lg">
                                                            {{ $deliverable->submission_notes }}
                                                        </p>
                                                    @else
                                                        <div
                                                            class="flex items-center p-3 bg-neutral-50 rounded-lg mb-3">
                                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                                class="h-10 w-10 mr-2 text-neutral-400" fill="none"
                                                                viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                            </svg>
                                                            <span class="text-sm text-neutral-500 font-main">No
                                                                provided notes</span>
                                                        </div>
                                                    @endif

                                                    <h6 class="font-medium font-tertiary text-primary mb-1">
                                                        Submitted Files
                                                    </h6>

                                                    <!-- Submitted Files -->
                                                    @if (!empty($deliverable->submission_files))
                                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                                            @foreach ($deliverable->submission_files as $file)
                                                                <a href="{{ Storage::url($file['path']) }}"
                                                                    target="_blank"
                                                                    class="flex items-center p-2 border border-neutral-200 rounded-md hover:bg-neutral-50 transition-colors">
                                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                                        class="h-5 w-5 mr-2 text-primary"
                                                                        fill="none" viewBox="0 0 24 24"
                                                                        stroke="currentColor">
                                                                        <path stroke-linecap="round"
                                                                            stroke-linejoin="round" stroke-width="2"
                                                                            d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                                    </svg>
                                                                    <div class="flex flex-col w-full overflow-hidden">
                                                                        <span
                                                                            class="text-sm truncate font-medium">{{ $file['name'] }}</span>
                                                                        <span
                                                                            class="text-xs text-neutral-500">{{ number_format($file['size'] / 1024, 1) }}
                                                                            KB</span>
                                                                    </div>
                                                                </a>
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        <div
                                                            class="flex items-center justify-center mx-auto p-3 bg-neutral-50 rounded-lg">
                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                                viewBox="0 0 24 24" stroke-width="1.5"
                                                                stroke="currentColor"
                                                                class="h-10 w-10 mr-2 text-neutral-400">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                                            </svg>
                                                            <span class="text-sm text-neutral-500 font-main">No
                                                                files uploaded</span>
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
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            @if ($deliverable->status === 'approved')
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                            @else
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
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
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                        Approve
                                                    </button>

                                                    <button x-data
                                                        x-on:click="$dispatch('open-modal', 'reject-deliverable-{{ $deliverable->id }}')"
                                                        class="inline-flex items-center px-3.5 py-2 text-sm font-medium rounded-md text-accent border border-accent/30 bg-white hover:bg-accent/5 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-accent">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                        Request Changes
                                                    </button>
                                                @endif

                                                <!-- Edit/Delete Deliverable -->
                                                @if ($deliverable->status === 'pending' && $engagement->status === 'active')
                                                    <button x-data
                                                        x-on:click="$dispatch('open-modal', 'edit-deliverable-{{ $deliverable->id }}')"
                                                        class="inline-flex items-center px-3.5 py-2 text-sm font-medium rounded-md text-primary border border-primary/30 bg-primary/5 hover:bg-primary/10 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary/40">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                        </svg>
                                                        Edit
                                                    </button>
                                                    <button x-data
                                                        x-on:click="$dispatch('open-modal', 'remove-deliverable-{{ $deliverable->id }}')"
                                                        class="inline-flex items-center px-3.5 py-2 text-sm font-medium rounded-md text-red-600 border border-red-200 bg-red-50 hover:bg-red-100 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500/40">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
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
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-primary"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 4v16m8-8H4" />
                                            </svg>
                                            Add Deliverable
                                        </button>
                                    </div>
                                @endforelse
                            </div>

                            <!-- Project Progress Overview -->
                            @if ($engagement->deliverables->isNotEmpty())
                                <div class="mt-8 bg-white border border-neutral-200 rounded-lg shadow-sm p-5">
                                    <h5 class="font-tertiary font-medium text-neutral-800 mb-4 flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-primary"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
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
                                    <x-modal name="approve-deliverable-{{ $deliverable->id }}" :show="false"
                                        max-width="2xl" focusable>
                                        <div class="p-8">
                                            <!-- Header -->
                                            <div class="text-center mb-6">
                                                <div class="flex items-center justify-center text-secondary mb-4">
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
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
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
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2" d="M5 13l4 4L19 7" />
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
                                    <x-modal name="reject-deliverable-{{ $deliverable->id }}" :show="false"
                                        max-width="2xl" focusable>
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
                                                    <p class="mt-1 text-xs text-neutral-500">Your feedback
                                                        will
                                                        be shared with the freelancer so they can make necessary
                                                        improvements.</p>
                                                </div>

                                                <div class="flex justify-between items-center">
                                                    <div class="text-sm text-neutral-500">
                                                        <div class="flex items-center">
                                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                                class="h-4 w-4 mr-1" fill="none"
                                                                viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
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
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2" d="M6 18L18 6M6 6l12 12" />
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
                                                    <input type="date" id="due_date-{{ $deliverable->id }}"
                                                        name="due_date" x-model="dueDate"
                                                        class="w-full rounded-lg border-neutral-300 shadow-sm ">
                                                    <div
                                                        class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="h-5 w-5 text-neutral-400" fill="none"
                                                            viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                        </svg>
                                                    </div>
                                                </div>
                                                <p class="mt-1 text-xs text-neutral-500">Please select a
                                                    future
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
                                                            xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                                            fill="currentColor">
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
                        <div id="deliverableModalBackdrop" class="fixed inset-0 bg-neutral-900/70 z-40 hidden">
                            <!-- Modal Container -->
                            <div id="deliverableModal"
                                class="fixed left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-2xl bg-white rounded-xl shadow-xl z-50">
                                <!-- Modal Header -->
                                <div class="bg-primary px-6 py-4 flex justify-between items-center">
                                    <h3 class="text-xl font-tertiary font-bold text-white flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                        Add New Deliverable
                                    </h3>
                                    <button type="button" id="closeDeliverableModal"
                                        class="text-white hover:text-neutral-200 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12" />
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
                            <div class="flex items-start bg-neutral-50 p-4 rounded-lg transition-all hover:shadow-sm">
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
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
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
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
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

                            <div class="flex items-start bg-neutral-50 p-4 rounded-lg transition-all hover:shadow-sm">
                                <div class="bg-secondary/10 p-2 rounded-lg mr-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-secondary"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
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

                            <!-- Cancel Engagement or Budget Card -->
                            @if ($engagement->status !== 'completed' && $engagement->status !== 'cancelled')
                                <!-- Cancel Engagement Card (Redesigned) -->
                                <div
                                    class="flex items-start bg-red-50 p-4 rounded-lg transition-all hover:shadow-md border border-red-100">
                                    <div class="bg-red-100 p-2 rounded-lg mr-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs text-red-500 mb-1">Engagement Actions</p>
                                        <button type="button"
                                            class="text-sm font-medium text-red-600 flex items-center hover:text-red-800 transition-colors"
                                            onclick="document.getElementById('cancelEngagementModal').classList.remove('hidden')">
                                            <span>Cancel Engagement</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            @else
                                <!-- Budget Card -->
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
                            @endif
                        </div>

                        <!-- Action buttons -->
                        @if ($isApplicant && $engagement->status === 'employer_accepted')
                            <div class="pt-2 flex justify-end">
                                <a href="{{ route('engagements.response-form', ['applicationId' => $engagement->application_id]) }}"
                                    class="inline-flex items-center px-5 py-2.5 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-gradient-to-r from-primary to-primary/90 hover:from-primary/90 hover:to-primary transition-all duration-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
                                    </svg>
                                    Respond to Offer
                                </a>
                            </div>
                        @endif

                        <div class="pt-2 flex justify-end gap-2">
                            <!-- Leave Review Button -->
                            @if ($engagement->isCompleted() || $engagement->isCancelled())
                                @if (!$engagement->hasBeenReviewedByCurrentUser())
                                    <button
                                        @click="$dispatch('open-review-modal', { 
                                          id: {{ $engagement->id }}, 
                                          status: '{{ $engagement->status }}'
                                        })"
                                        class="inline-flex items-center px-5 py-2.5 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-gradient-to-r from-primary to-primary/90 hover:from-primary/90 hover:to-primary transition-all duration-300">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
                                        </svg>
                                        Leave a Review
                                    </button>
                                @else
                                    <div class="w-full flex justify-between items-center">
                                        <!-- Archive Button -->
                                        <button
                                            @click="$dispatch('open-archive-modal', { id: {{ $engagement->id }} })"
                                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition duration-200">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                class="h-5 w-5 mr-1">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                                            </svg>
                                            Archive
                                        </button>

                                        <span
                                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-green-700 bg-green-100 rounded-lg">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                class="h-5 w-5 mr-1">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
                                            </svg>
                                            Already Reviewed
                                        </span>
                                    </div>
                                @endif
                            @endif
                            <!-- Reopen Job Section (for clients only) -->
                            @if ($engagement->isCancelled() && Auth::id() === $engagement->application->poster_id && !$engagement->job->is_active)
                                <form action="{{ route('engagements.reopen-job', $engagement) }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                        class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium  text-sm shadow-sm rounded-lg transition duration-200 inline-flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2"
                                            viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        Reopen Job
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>

                <div x-data="{
                    open: false,
                    engagementId: null,
                    loading: false
                }"
                    x-on:open-archive-modal.window="
                        open = true;
                        engagementId = $event.detail.id;
                    "
                    @keydown.escape.window="open = false" x-show="open" x-cloak
                    class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="archive-modal" role="dialog"
                    aria-modal="true" style="display: none;">

                    <!-- Overlay -->
                    <div x-show="open" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0" @click="open = false"
                        class="fixed inset-0 bg-neutral-900/50 backdrop-blur-sm">
                    </div>

                    <!-- Modal Content -->
                    <div class="fixed inset-0 flex items-center justify-center p-4">
                        <div x-show="open" x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-200"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95" @click.outside="open = false"
                            class="relative bg-white dark:bg-neutral-800 rounded-xl overflow-hidden shadow-2xl w-full max-w-xl transform transition-all">

                            <!-- Modal Pattern Background -->
                            <div class="absolute inset-0 opacity-5">
                                <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
                                    <defs>
                                        <pattern id="archivePattern" x="0" y="0" width="20" height="20"
                                            patternUnits="userSpaceOnUse">
                                            <path d="M0 10 L10 0 L20 10 L10 20 Z" fill="currentColor" />
                                        </pattern>
                                    </defs>
                                    <rect width="100%" height="100%" fill="url(#archivePattern)" />
                                </svg>
                            </div>

                            <!-- Modal Header -->
                            <div class="px-6 py-4 border-b border-neutral-200 dark:border-neutral-700 relative">
                                <div class="flex items-center">
                                    <div
                                        class="flex-shrink-0 p-2 rounded-full bg-gradient-to-br from-accent to-accent/70">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                                        </svg>
                                    </div>
                                    <h3
                                        class="ml-3 text-lg font-semibold font-tertiary text-neutral-800 dark:text-white">
                                        Archive Engagement
                                    </h3>
                                </div>

                                <!-- Close Button -->
                                <button @click="open = false"
                                    class="absolute top-4 right-4 text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-200 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Modal Body -->
                            <div class="px-6 py-4 relative">
                                <p class="text-neutral-600 dark:text-neutral-300 text-sm font-main">
                                    You're about to archive this engagement. Archived engagements will be moved to your
                                    archive section and can be retrieved later if needed. This helps keep your active
                                    engagements organized and your dashboard clean.
                                </p>

                                <div
                                    class="mt-4 p-4 bg-gradient-to-r from-accent/10 to-accent/5 rounded-lg border border-accent/20">
                                    <div class="flex items-start">
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="h-5 w-5 text-accent flex-shrink-0 mr-2 mt-0.5" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <p class="text-sm text-white font-medium font-secondary">
                                            Note: The other party will still have access to view this engagement in
                                            their archive section.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal Footer -->
                            <div
                                class="px-6 py-4 bg-neutral-50 dark:bg-neutral-800/50 flex flex-col sm:flex-row-reverse gap-2 relative">
                                <form action="{{ route('engagements.archive') }}" method="POST"
                                    x-data="{ loading: false, engagementId: null }"
                                    x-on:open-archive-modal.window="engagementId = $event.detail.id"
                                    x-on:submit="loading = true">
                                    @csrf
                                    <input type="hidden" name="engagement_id" :value="engagementId" />

                                    <button type="submit" :disabled="loading"
                                        class="inline-flex justify-center items-center rounded-lg px-4 py-2.5 bg-gradient-to-r from-accent to-accent/80 text-white font-medium text-sm shadow transition-all hover:from-accent/90 hover:to-accent/70 focus:ring-2 focus:ring-accent/50 focus:ring-offset-2 disabled:opacity-70 disabled:cursor-not-allowed">
                                        <span x-show="!loading">Archive Engagement</span>
                                        <span x-show="loading" class="inline-flex items-center">
                                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                                                xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                                    stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor"
                                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                </path>
                                            </svg>
                                            Processing...
                                        </span>
                                    </button>
                                </form>

                                <button type="button" @click="open = false"
                                    class="w-full sm:w-auto inline-flex justify-center items-center rounded-lg px-4 py-2.5 border border-neutral-300 dark:border-neutral-600 bg-white dark:bg-neutral-700 text-neutral-700 dark:text-neutral-200 font-medium text-sm shadow-sm hover:bg-neutral-50 dark:hover:bg-neutral-600 focus:ring-2 focus:ring-primary/30 focus:ring-offset-2 transition-all">
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Review Modal -->
                <div x-data="{ open: false, rating: 0, reviewText: '', tags: [], isPublic: true, engagement: null }"
                    x-on:open-review-modal.window="engagement = $event.detail; open = true" class="relative z-50"
                    x-cloak>

                    <!-- Modal Backdrop -->
                    <div x-show="open" x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0" class="fixed inset-0 bg-neutral-900/70 backdrop-blur-sm"
                        @click="open = false">
                    </div>

                    <!-- Modal Content -->
                    <div x-show="open" x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-4"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 translate-y-4"
                        class="fixed inset-0 flex items-center justify-center p-4">

                        <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl mx-auto overflow-hidden max-h-[90vh] overflow-y-auto"
                            @click.outside="open = false">
                            <!-- Modal Header -->
                            <div class="bg-gradient-to-r from-primary to-primary/90 p-5">
                                <div class="flex justify-between items-center">
                                    <h3 class="text-xl font-tertiary font-bold text-white flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
                                        </svg>
                                        Leave a Review
                                    </h3>
                                    <button @click="open = false"
                                        class="text-white hover:text-neutral-200 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Modal Body -->
                            <div class="p-6 font-main">
                                <form id="reviewForm" action="{{ route('engagements.review', $engagement) }}"
                                    method="POST">
                                    @csrf

                                    <template x-if="engagement.status === 'cancelled'">
                                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                                            <div class="flex">
                                                <div class="flex-shrink-0">
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="h-5 w-5 text-yellow-400" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </div>
                                                <div class="ml-3">
                                                    <p class="text-sm text-yellow-700">
                                                        Although this engagement was cancelled, your feedback helps
                                                        maintain a high-quality marketplace for everyone. Reviews are a
                                                        vital part of our community's trust system.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Rating Section -->
                                    <div class="mb-3">
                                        <label class="block text-neutral-700 font-medium mb-1">Your
                                            Rating</label>
                                        <div class="flex items-center justify-center">
                                            <template x-for="i in 5" :key="i">
                                                <button type="button" @click="rating = i"
                                                    class="focus:outline-none mx-1 transition-transform hover:scale-110"
                                                    :class="{ 'transform scale-110': rating >= i }">
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        :class="rating >= i ? 'text-accent' : 'text-neutral-300'"
                                                        class="h-8 w-8 transition-colors duration-200"
                                                        fill="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                                                    </svg>
                                                </button>
                                            </template>
                                        </div>
                                        <input type="hidden" name="rating" :value="rating">
                                        @error('rating')
                                            <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <!-- Review Text -->
                                    <div class="mb-3">
                                        <label for="review"
                                            class="block text-neutral-700 font-medium mb-1">Review</label>
                                        <textarea id="review" name="review" x-model="reviewText" rows="4"
                                            class="w-full px-4 py-3 border border-neutral-300 rounded-lg focus:ring-2 focus:ring-secondary focus:border-secondary transition-colors"
                                            placeholder="Share your experience working on this project..." required></textarea>
                                        <div class="text-xs text-neutral-500 mt-1 flex justify-between">
                                            <span>Minimum 10 characters</span>
                                            <span x-text="reviewText.length + ' characters'"></span>
                                        </div>
                                        @error('review')
                                            <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <!-- Tags Section -->
                                    <div class="mb-3">
                                        <label class="block text-neutral-700 font-medium mb-1">Highlight
                                            Skills/Qualities</label>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach (['Communication', 'Quality', 'Expertise', 'Timeliness', 'Collaboration', 'Problem-solving'] as $tag)
                                                <label
                                                    class="inline-flex items-center px-3 py-1.5 rounded-full cursor-pointer transition-all duration-200"
                                                    :class="tags.includes('{{ $tag }}') ?
                                                        'bg-secondary/20 border-secondary text-secondary' :
                                                        'bg-neutral-100 border-neutral-200 text-neutral-600'">
                                                    <input type="checkbox" name="tags[]"
                                                        value="{{ $tag }}" class="hidden"
                                                        x-on:change="tags.includes('{{ $tag }}') ? tags = tags.filter(t => t !== '{{ $tag }}') : tags.push('{{ $tag }}')">
                                                    <span>{{ $tag }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>

                                    <!-- Visibility Toggle -->
                                    <div class="mb-3">
                                        <label class="flex items-center">
                                            <input type="checkbox" name="is_public" value="1"
                                                :checked="isPublic" @change="isPublic = !isPublic"
                                                class="rounded text-secondary focus:ring-secondary h-5 w-5">
                                            <span class="ml-2 text-neutral-700">Make this review public</span>
                                        </label>
                                        <p class="text-xs text-neutral-500 mt-1 ml-7">Public reviews are
                                            visible to all platform users</p>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="flex justify-end space-x-3 mt-4">
                                        <button type="button" @click="open = false"
                                            class="px-5 py-2.5 border border-neutral-300 rounded-lg text-sm font-medium text-neutral-700 hover:bg-neutral-100 transition-colors">
                                            Cancel
                                        </button>
                                        <button type="submit" @click="submitForm()"
                                            :disabled="rating === 0 || reviewText.length < 10"
                                            :class="{
                                                'opacity-50 cursor-not-allowed': rating === 0 || reviewText
                                                    .length < 10
                                            }"
                                            class="inline-flex items-center px-5 py-2.5 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-gradient-to-r from-primary to-primary/90 hover:from-primary/90 hover:to-primary transition-all duration-300">
                                            Submit Review
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cancellation Modal -->
                <div id="cancelEngagementModal"
                    class="fixed inset-0 bg-neutral-900 bg-opacity-75 flex items-center justify-center z-50 hidden">
                    <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                        <div class="px-6 py-4 border-b border-neutral-200">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-neutral-800 font-tertiary">Cancel Engagement
                                </h3>
                                <button
                                    onclick="document.getElementById('cancelEngagementModal').classList.add('hidden')"
                                    class="text-neutral-500 hover:text-neutral-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="p-6">
                            <div class="bg-amber-50 border-l-4 border-accent p-4 mb-6">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-accent"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-amber-800 font-medium">Important Information</p>
                                        <ul class="mt-2 text-sm text-amber-700 list-disc list-inside">
                                            <li>Cancellation may affect future opportunities on the platform</li>
                                            <li>Partial work completed may still be eligible for payment</li>
                                            <li>All submitted deliverables will remain accessible</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <form action="{{ route('engagements.cancel', $engagement) }}" method="POST">
                                @csrf

                                <div class="space-y-6">
                                    <!-- Cancellation Type -->
                                    <div>
                                        <label for="cancellation_type"
                                            class="block text-sm font-medium text-neutral-700 mb-1">Cancellation
                                            Type</label>
                                        <select id="cancellation_type" name="cancellation_type" required
                                            class="mt-1 block w-full pl-3 pr-10 py-2 text-sm border-neutral-300 focus:outline-none focus:ring-secondary focus:border-secondary rounded-md shadow-sm font-main">
                                            <option value="mutual">Mutual Agreement</option>
                                            <option value="client_initiated">Client Initiated</option>
                                            <option value="freelancer_initiated">Freelancer Initiated</option>
                                            <option value="dispute">Dispute (Requires Review)</option>
                                        </select>
                                    </div>

                                    <!-- Reason Category -->
                                    <div>
                                        <label for="reason_category"
                                            class="block text-sm font-medium text-neutral-700 mb-1">Reason
                                            Category</label>
                                        <select id="reason_category" name="reason_category" required
                                            class="mt-1 block w-full pl-3 pr-10 py-2 text-sm border-neutral-300 focus:outline-none focus:ring-secondary focus:border-secondary rounded-md shadow-sm font-main">
                                            <option value="schedule_conflict">Schedule Conflict</option>
                                            <option value="project_scope_change">Project Scope Changed</option>
                                            <option value="communication_issues">Communication Issues</option>
                                            <option value="quality_concerns">Quality Concerns</option>
                                            <option value="financial_reasons">Financial Reasons</option>
                                            <option value="personal_reasons">Personal Reasons</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>

                                    <!-- Detailed Reason -->
                                    <div>
                                        <label for="cancellation_reason"
                                            class="block text-sm font-medium text-neutral-700 mb-1">Please provide
                                            details</label>
                                        <textarea id="cancellation_reason" name="cancellation_reason" rows="4" required
                                            class="shadow-sm block w-full focus:ring-secondary focus:border-secondary border-neutral-300 rounded-md font-main"
                                            placeholder="Please explain your reasons for cancellation in detail..."></textarea>
                                    </div>

                                    @if (
                                        $engagement->deliverables->where('status', 'approved')->count() > 0 ||
                                            $engagement->deliverables->where('status', 'submitted')->count() > 0)
                                        <!-- Payment for Partial Work -->
                                        <div
                                            class="bg-neutral-50 p-4 rounded-md border border-neutral-200 font-main text-sm">
                                            <h4 class="font-medium text-neutral-800 mb-2">Payment for Completed Work
                                            </h4>
                                            <p class="text-sm text-neutral-600 mb-3">Some work has been submitted or
                                                approved. Please indicate if you want to process payment for completed
                                                work:</p>

                                            <div class="flex items-center">
                                                <input id="process_payment" name="process_payment" type="checkbox"
                                                    value="1"
                                                    class="h-4 w-4 text-secondary focus:ring-secondary border-neutral-300 rounded">
                                                <label for="process_payment"
                                                    class="ml-2 block text-sm text-neutral-700">
                                                    Process payment for completed deliverables
                                                </label>
                                            </div>
                                        </div>
                                    @endif

                                    <!-- Terms Acceptance -->
                                    <div class="flex items-start">
                                        <div class="flex items-center h-5">
                                            <input id="terms" name="terms" type="checkbox" required
                                                value="1"
                                                class="h-4 w-4 text-secondary focus:ring-secondary border-neutral-300 rounded">
                                        </div>
                                        <div class="ml-3 text-sm font-main">
                                            <label for="terms" class="font-medium text-neutral-700">I understand
                                                and agree</label>
                                            <p class="text-neutral-500">I have read and understand the <a
                                                    href="#"
                                                    class="text-secondary hover:text-primary underline">cancellation
                                                    policy</a> and terms of service.</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-6 flex flex-col sm:flex-row sm:space-x-4">
                                    <button type="button"
                                        onclick="document.getElementById('cancelEngagementModal').classList.add('hidden')"
                                        class="w-full sm:w-auto mb-3 sm:mb-0 inline-flex justify-center items-center px-4 py-2 border border-neutral-300 shadow-sm text-sm font-medium rounded-md text-neutral-700 bg-white hover:bg-neutral-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500">
                                        Keep Engagement Active
                                    </button>
                                    <button type="submit"
                                        class="w-full sm:w-auto inline-flex justify-center items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                        Confirm Cancellation
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
