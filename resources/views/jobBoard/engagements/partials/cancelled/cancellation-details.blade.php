<div class="bg-white rounded-2xl shadow-lg border border-neutral-200 overflow-hidden relative">
    <!-- Subtle background pattern -->
    <div class="absolute inset-0 opacity-5">
        <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
            <pattern id="dots" width="20" height="20" patternUnits="userSpaceOnUse">
                <circle cx="3" cy="3" r="1" fill="#1E3A8A" />
            </pattern>
            <rect width="100%" height="100%" fill="url(#dots)" />
        </svg>
    </div>

    <!-- Header  -->
    <div class="bg-gradient-to-r from-accent to-accent/80 p-6 relative">
        <!-- Abstract shape for visual interest -->
        <div class="absolute right-0 bottom-0 transform translate-y-1/3 translate-x-1/6 opacity-20">
            <svg width="120" height="120" viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg">
                <path
                    d="M60 20C60 42.0914 77.9086 60 100 60C77.9086 60 60 77.9086 60 100C60 77.9086 42.0914 60 20 60C42.0914 60 60 42.0914 60 20Z"
                    fill="#FFFFFF" />
            </svg>
        </div>

        <div class="flex items-center">
            <svg class="h-7 w-7 mr-3 text-white" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path
                    d="M15 9L9 15M9 9L15 15M22 12C22 17.5228 17.5228 22 12 22C6.47715 22 2 17.5228 2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12Z"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <h2 class="font-tertiary font-semibold text-lg text-white">Cancellation Details</h2>
        </div>
    </div>

    <!-- Status banner -->
    <div class="bg-neutral-50 p-4 border-b border-neutral-200">
        <div class="flex justify-between items-center">
            <div class="flex items-center">
                <div class="w-3 h-3 rounded-full bg-accent mr-2"></div>
                <span class="text-neutral-700 font-medium text-sm">Engagement Cancelled</span>
            </div>
            <div class="text-sm text-neutral-500">
                {{ $engagement->cancelled_at->format('M d, Y h:i A') }}
            </div>
        </div>
    </div>

    <!-- Main content -->
    <div class="p-6">
        <div class="grid grid-cols-1 gap-6">
            <!-- Initiator Information -->
            <div class="bg-neutral-50 rounded-xl p-4 border border-neutral-200">
                <h3 class="font-secondary text-md font-semibold text-neutral-700 mb-3 flex items-center">
                    <svg class="h-5 w-5 mr-2 text-tertiary" viewBox="0 0 24 24" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M16 7C16 9.20914 14.2091 11 12 11C9.79086 11 8 9.20914 8 7C8 4.79086 9.79086 3 12 3C14.2091 3 16 4.79086 16 7Z"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M12 14C8.13401 14 5 17.134 5 21H19C19 17.134 15.866 14 12 14Z" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    Initiated by
                </h3>
                <div class="flex items-center bg-white p-3 rounded-lg border border-neutral-200 shadow-sm">

                    @if ($engagement->cancellation->initiator_id === $engagement->application->poster->id)
                        <div
                            class="flex items-center justify-center h-10 w-10 rounded-full bg-gradient-to-br from-accent to-accent/70 text-white font-medium mr-3">
                            {{ $engagement->application->poster->getInitials() }}</div>
                    @else
                        <div
                            class="flex items-center justify-center h-10 w-10 rounded-full bg-gradient-to-br from-secondary to-primary text-white font-medium mr-3">
                            {{ $engagement->application->applicant->getInitials() }}
                        </div>
                    @endif

                    <div>
                        <h4 class="font-medium text-neutral-800">
                            @if ($engagement->cancellation->initiator_id === $engagement->application->poster->id)
                                {{ $engagement->application->poster->name }}
                                <span
                                    class="inline-flex items-center ml-2 px-2 py-0.5 rounded-full text-xs font-medium bg-primary/10 text-primary">
                                    Client
                                </span>
                            @else
                                {{ $engagement->application->applicant->name }}
                                <span
                                    class="inline-flex items-center ml-2 px-2 py-0.5 rounded-full text-xs font-medium bg-secondary/10 text-secondary">
                                    Freelancer
                                </span>
                            @endif
                        </h4>
                    </div>
                </div>
            </div>

            <!-- Cancellation Type -->
            <div class="bg-neutral-50 rounded-xl p-4 border border-neutral-200">
                <h3 class="font-secondary text-md font-semibold text-neutral-700 mb-3 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="h-5 w-5 mr-2 text-tertiary">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M6.429 9.75 2.25 12l4.179 2.25m0-4.5 5.571 3 5.571-3m-11.142 0L2.25 7.5 12 2.25l9.75 5.25-4.179 2.25m0 0L21.75 12l-4.179 2.25m0 0 4.179 2.25L12 21.75 2.25 16.5l4.179-2.25m11.142 0-5.571 3-5.571-3" />
                    </svg>
                    Cancellation Type
                </h3>
                <div class="bg-white p-3 rounded-lg border border-neutral-200 shadow-sm">
                    @php
                        $cancellationTypes = [
                            'mutual' => 'Mutual Agreement',
                            'client_initiated' => 'Client Initiated',
                            'freelancer_initiated' => 'Freelancer Initiated',
                            'dispute' => 'Dispute (Requires Review)',
                        ];
                        $cancellationType =
                            $cancellationTypes[$engagement->cancellation->cancellation_type] ??
                            $engagement->cancellation->cancellation_type;

                        $typeColorClass = match ($engagement->cancellation->cancellation_type) {
                            'mutual' => 'bg-blue-50 text-blue-700',
                            'client_initiated' => 'bg-purple-50 text-purple-700',
                            'freelancer_initiated' => 'bg-green-50 text-green-700',
                            'dispute' => 'bg-red-50 text-red-700',
                            default => 'bg-gray-50 text-gray-700',
                        };
                    @endphp

                    <span
                        class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium {{ $typeColorClass }}">
                        {{ $cancellationType }}
                    </span>
                </div>
            </div>

            <!-- Reason Category -->
            <div class="bg-neutral-50 rounded-xl p-4 border border-neutral-200">
                <h3 class="font-secondary text-md font-semibold text-neutral-700 mb-3 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="h-5 w-5 mr-2 text-tertiary">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" />
                    </svg>
                    Reason
                </h3>
                <div class="bg-white p-4 rounded-lg border border-neutral-200 shadow-sm">
                    @php
                        $reasonCategories = [
                            'schedule_conflict' => 'Schedule Conflict',
                            'project_scope_change' => 'Project Scope Changed',
                            'communication_issues' => 'Communication Issues',
                            'quality_concerns' => 'Quality Concerns',
                            'financial_reasons' => 'Financial Reasons',
                            'personal_reasons' => 'Personal Reasons',
                            'other' => 'Other',
                        ];
                        $reasonCategory =
                            $reasonCategories[$engagement->cancellation->reason_category] ??
                            $engagement->cancellation->reason_category;
                    @endphp

                    <div class="mb-2">
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-md text-sm font-medium bg-neutral-100 text-neutral-800">
                            {{ $reasonCategory }}
                        </span>
                    </div>
                    <div class="text-neutral-700 text-sm mt-3 bg-neutral-50 p-3 rounded-lg border border-neutral-100">
                        {{ $engagement->cancellation->reason_details }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
