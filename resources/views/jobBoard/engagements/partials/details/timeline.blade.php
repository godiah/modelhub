<!-- Engagement Timeline -->
<div class="bg-white rounded-2xl shadow-lg border border-neutral-100 overflow-hidden">
    <!-- Header Section -->
    <div class="relative bg-gradient-to-r from-tertiary to-tertiary/90 px-8 py-6">
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" viewBox="0 0 400 200" fill="currentColor">
                <defs>
                    <pattern id="timeline-pattern" x="0" y="0" width="40" height="40" patternUnits="userSpaceOnUse">
                        <circle cx="20" cy="20" r="1" fill="currentColor" />
                        <circle cx="5" cy="5" r="0.5" fill="currentColor" />
                        <circle cx="35" cy="5" r="0.5" fill="currentColor" />
                        <circle cx="5" cy="35" r="0.5" fill="currentColor" />
                        <circle cx="35" cy="35" r="0.5" fill="currentColor" />
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#timeline-pattern)" />
            </svg>
        </div>

        <!-- Header Content -->
        <div class="relative flex items-center">
            <div class="w-10 h-10 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center mr-4">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
            </div>
            <h2 class="text-xl font-bold text-white font-main">Project Timeline</h2>
        </div>
    </div>

    <!-- Timeline Content -->
    <div class="px-8 py-8">
        <div class="relative">
            <!-- Timeline Line -->
            <div
                class="absolute left-6 top-0 bottom-0 w-0.5 bg-gradient-to-b from-neutral-200 via-neutral-300 to-neutral-200">
            </div>

            <div class="space-y-6">
                @if ($engagement->employer_accepted_at)
                    <div class="relative flex items-start group">
                        <!-- Timeline Dot -->
                        <div
                            class="relative z-10 flex items-center justify-center w-12 h-12 bg-gradient-to-br from-secondary to-secondary/80 rounded-full shadow-lg border-4 border-white">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                        </div>

                        <!-- Content -->
                        <div
                            class="ml-6 flex-1 bg-gradient-to-br from-secondary/5 to-secondary/10 rounded-xl p-5 border border-secondary/20 group-hover:shadow-md transition-all duration-200">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="font-semibold text-secondary font-main flex items-center">
                                    <span class="w-2 h-2 bg-secondary rounded-full mr-2"></span>
                                    Application Accepted
                                </h3>
                                <span
                                    class="inline-flex items-center px-3 py-1 text-xs font-medium font-secondary bg-secondary/20 text-secondary rounded-full">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    Approved
                                </span>
                            </div>
                            <p class="text-sm text-neutral-600 font-secondary">
                                {{ $engagement->employer_accepted_at->format('F j, Y') }} at
                                {{ $engagement->employer_accepted_at->format('g:i A') }}
                            </p>
                        </div>
                    </div>
                @endif

                @if ($engagement->started_at)
                    <div class="relative flex items-start group">
                        <!-- Timeline Dot -->
                        <div
                            class="relative z-10 flex items-center justify-center w-12 h-12 bg-gradient-to-br from-primary to-primary/80 rounded-full shadow-lg border-4 border-white">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h8m-9 4h10a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>

                        <!-- Content -->
                        <div
                            class="ml-6 flex-1 bg-gradient-to-br from-primary/5 to-primary/10 rounded-xl p-5 border border-primary/20 group-hover:shadow-md transition-all duration-200">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="font-semibold text-primary font-main flex items-center">
                                    <span class="w-2 h-2 bg-primary rounded-full mr-2"></span>
                                    Project Started
                                </h3>
                                <span
                                    class="inline-flex items-center px-3 py-1 text-xs font-medium font-secondary bg-primary/20 text-primary rounded-full">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm.707-10.293a1 1 0 00-1.414-1.414l-3 3a1 1 0 000 1.414l3 3a1 1 0 001.414-1.414L9.414 11H13a1 1 0 100-2H9.414l1.293-1.293z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    In Progress
                                </span>
                            </div>
                            <p class="text-sm text-neutral-600 font-secondary">
                                {{ $engagement->started_at->format('F j, Y') }} at
                                {{ $engagement->started_at->format('g:i A') }}
                            </p>
                        </div>
                    </div>
                @endif

                @if ($engagement->completed_at)
                    <div class="relative flex items-start group">
                        <!-- Timeline Dot -->
                        <div
                            class="relative z-10 flex items-center justify-center w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-full shadow-lg border-4 border-white">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>

                        <!-- Content -->
                        <div
                            class="ml-6 flex-1 bg-gradient-to-br from-green-50 to-green-100/80 rounded-xl p-5 border border-green-200 group-hover:shadow-md transition-all duration-200">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="font-semibold text-green-700 font-main flex items-center">
                                    <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                                    Project Completed
                                </h3>
                                <span
                                    class="inline-flex items-center px-3 py-1 text-xs font-medium font-secondary bg-green-200 text-green-700 rounded-full">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    Finished
                                </span>
                            </div>
                            <p class="text-sm text-neutral-600 font-secondary">
                                {{ $engagement->completed_at->format('F j, Y') }} at
                                {{ $engagement->completed_at->format('g:i A') }}
                            </p>
                        </div>
                    </div>
                @endif

                @if ($engagement->cancelled_at)
                    <div class="relative flex items-start group">
                        <!-- Timeline Dot -->
                        <div
                            class="relative z-10 flex items-center justify-center w-12 h-12 bg-gradient-to-br from-red-500 to-red-600 rounded-full shadow-lg border-4 border-white">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </div>

                        <!-- Content -->
                        <div
                            class="ml-6 flex-1 bg-gradient-to-br from-red-50 to-red-100/80 rounded-xl p-5 border border-red-200 group-hover:shadow-md transition-all duration-200">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="font-semibold text-red-700 font-main flex items-center">
                                    <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                                    Project Cancelled
                                </h3>
                                <span
                                    class="inline-flex items-center px-3 py-1 text-xs font-medium font-secondary bg-red-200 text-red-700 rounded-full">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    Terminated
                                </span>
                            </div>
                            <p class="text-sm text-neutral-600 font-secondary">
                                {{ $engagement->cancelled_at->format('F j, Y') }} at
                                {{ $engagement->cancelled_at->format('g:i A') }}
                            </p>
                        </div>
                    </div>
                @endif

                @if (
                    $engagement->cancellation &&
                        $engagement->cancellation->freelancer_accepted_payment &&
                        $engagement->cancellation->freelancer_accepted_at)
                    <div class="relative flex items-start group">
                        <!-- Timeline Dot -->
                        <div
                            class="relative z-10 flex items-center justify-center w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full shadow-lg border-4 border-white">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>

                        <!-- Content -->
                        <div
                            class="ml-6 flex-1 bg-gradient-to-br from-blue-50 to-blue-100/80 rounded-xl p-5 border border-blue-200 group-hover:shadow-md transition-all duration-200">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="font-semibold text-blue-700 font-main flex items-center">
                                    <span class="w-2 h-2 bg-blue-500 rounded-full mr-2"></span>
                                    Project Settled
                                </h3>
                                <span
                                    class="inline-flex items-center px-3 py-1 text-xs font-medium font-secondary bg-blue-200 text-blue-700 rounded-full">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    Settled
                                </span>
                            </div>
                            <p class="text-sm text-neutral-600 font-secondary">
                                {{ $engagement->cancellation->freelancer_accepted_at->format('F j, Y') }} at
                                {{ $engagement->cancellation->freelancer_accepted_at->format('g:i A') }}
                            </p>
                        </div>
                    </div>
                @endif

                @if ($engagement->cancellation && $engagement->cancellation->dispute && $engagement->cancellation->dispute->resolved_at)
                    <div class="relative flex items-start group">
                        <!-- Timeline Dot -->
                        <div
                            class="relative z-10 flex items-center justify-center w-12 h-12 bg-gradient-to-br from-purple-500 to-purple-600 rounded-full shadow-lg border-4 border-white">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>

                        <!-- Content -->
                        <div
                            class="ml-6 flex-1 bg-gradient-to-br from-purple-50 to-purple-100/80 rounded-xl p-5 border border-purple-200 group-hover:shadow-md transition-all duration-200">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="font-semibold text-purple-700 font-main flex items-center">
                                    <span class="w-2 h-2 bg-purple-500 rounded-full mr-2"></span>
                                    Dispute Resolved
                                </h3>
                                <span
                                    class="inline-flex items-center px-3 py-1 text-xs font-medium font-secondary bg-purple-200 text-purple-700 rounded-full">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    Resolved
                                </span>
                            </div>
                            <p class="text-sm text-neutral-600 font-secondary">
                                {{ $engagement->cancellation->dispute->resolved_at->format('F j, Y') }} at
                                {{ $engagement->cancellation->dispute->resolved_at->format('g:i A') }}
                            </p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
