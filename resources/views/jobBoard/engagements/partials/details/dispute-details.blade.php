@use('App\Enums\DisputeStatus')
@if ($engagement->cancellation && $engagement->cancellation->dispute)
    @php $dispute = $engagement->cancellation->dispute; @endphp
    <div
        class="bg-gradient-to-br from-orange-50 to-amber-25 rounded-2xl shadow-lg border border-orange-200 overflow-hidden">
        <!-- Header Section -->
        <div class="bg-gradient-to-r from-orange-600 to-amber-600 px-8 py-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-white/20 rounded-lg backdrop-blur-sm">
                        <x-icon name="exclamation-triangle-2" class="w-6 h-6 text-white" />
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold text-white font-main">Dispute Information</h2>
                        <p class="text-orange-100 text-xs font-secondary">Dispute details and resolution status</p>
                    </div>
                </div>
                <!-- Status Indicator -->
                <div class="flex items-center space-x-2">
                    <span
                        class="bg-white/20 backdrop-blur-sm px-3 py-1 rounded-full text-white text-sm font-medium
                        @if ($dispute->status === DisputeStatus::Resolved) text-green-100 bg-white/30
                        @elseif($dispute->status === DisputeStatus::Pending) text-yellow-100 bg-white/30
                        @else text-orange-100 bg-white/30 @endif
                    ">
                        @if ($dispute->status === DisputeStatus::Resolved)
                            <x-icon name="check-solid" class="w-3 h-3 inline mr-1" />
                        @elseif($dispute->status === DisputeStatus::Pending)
                            <x-icon name="clock-solid" class="w-3 h-3 inline mr-1" />
                        @endif
                        {{ $dispute->status->label() }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Content Section -->
        <div class="p-6">
            <!-- Primary Information Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Disputed By -->
                <div class="bg-white rounded-lg p-4 border border-orange-100 shadow-sm">
                    <div class="flex items-start space-x-3">
                        <div class="p-2 bg-orange-100 rounded-lg">
                            <x-icon name="user" class="w-4 h-4 text-orange-600" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-xs font-semibold text-orange-800 font-main mb-1">Disputed By</h3>
                            <p class="text-sm text-orange-700 font-secondary font-medium">
                                {{ $dispute->disputedBy->name }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Current Status -->
                <div class="bg-white rounded-lg p-4 border border-orange-100 shadow-sm">
                    <div class="flex items-start space-x-3">
                        <div
                            class="p-2 rounded-lg
                            @if ($dispute->status === DisputeStatus::Resolved) bg-green-100
                            @elseif($dispute->status === DisputeStatus::Pending) bg-yellow-100
                            @else bg-orange-100 @endif
                        ">
                            @if ($dispute->status === DisputeStatus::Resolved)
                                <x-icon name="check-circle" class="w-4 h-4 text-green-600" />
                            @elseif($dispute->status === DisputeStatus::Pending)
                                <x-icon name="clock" class="w-4 h-4 text-yellow-600" />
                            @else
                                <x-icon name="exclamation-triangle-2" class="w-4 h-4 text-orange-600" />
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-xs font-semibold text-orange-800 font-main mb-1">Current Status</h3>
                            <p
                                class="text-sm font-medium font-secondary
                                @if ($dispute->status === DisputeStatus::Resolved) text-green-700
                                @elseif($dispute->status === DisputeStatus::Pending) text-yellow-700
                                @else text-orange-700 @endif
                            ">
                                {{ $dispute->status->label() }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Dispute Reason -->
                <div class="bg-white rounded-lg p-4 border border-orange-100 shadow-sm">
                    <div class="flex items-start space-x-3">
                        <div class="p-2 bg-orange-100 rounded-lg">
                            <x-icon name="question-mark-circle" class="w-4 h-4 text-orange-600" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-xs font-semibold text-orange-800 font-main mb-1">Dispute Reason</h3>
                            <p class="text-sm text-orange-700 font-secondary font-medium">
                                {{ $dispute->formatted_reason }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Resolution Amount (if exists) -->
                @if ($dispute->resolution_amount)
                    <div class="bg-white rounded-lg p-4 border border-green-100 shadow-sm">
                        <div class="flex items-start space-x-3">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1">
                                    </path>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-xs font-semibold text-green-800 font-main mb-1">Resolution Amount</h3>
                                <p class="text-lg font-bold text-green-700 font-main">
                                    Ksh{{ number_format($dispute->resolution_amount, 2) }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Timeline Section -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Dispute Created -->
                <div class="bg-white rounded-lg p-4 border border-orange-100 shadow-sm">
                    <div class="flex items-center space-x-3">
                        <div class="p-2 bg-orange-100 rounded-lg">
                            <x-icon name="clock" class="w-4 h-4 text-orange-600" />
                        </div>
                        <h3 class="text-xs font-semibold text-orange-800 font-main">Dispute Filed</h3>
                    </div>
                    <div class="ml-11 font-secondary">
                        <p class="text-sm text-orange-700">
                            {{ $dispute->created_at->format('F j, Y \a\t g:i A') }}
                        </p>
                        <p class="text-xs text-orange-500 mt-1">
                            {{ $dispute->created_at->diffForHumans() }}
                        </p>
                    </div>
                </div>

                <!-- Resolution Date (if resolved) -->
                @if ($dispute->status === DisputeStatus::Resolved && $dispute->resolved_at)
                    <div class="bg-white rounded-lg p-4 border border-green-100 shadow-sm">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <x-icon name="check-circle" class="w-4 h-4 text-green-600" />
                            </div>
                            <h3 class="text-xs font-semibold text-green-800 font-main">Resolved</h3>
                        </div>
                        <div class="ml-11 font-secondary">
                            <p class="text-sm text-green-700">
                                {{ $dispute->resolved_at->format('F j, Y \a\t g:i A') }}
                            </p>
                            <p class="text-xs text-green-500 mt-1">
                                {{ $dispute->resolved_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Dispute Details (if provided) -->
            @if ($dispute->dispute_details)
                <div class="bg-white rounded-lg p-4 border border-orange-100 shadow-sm mb-6">
                    <div class="flex items-start space-x-3">
                        <div class="p-2 bg-orange-100 rounded-lg flex-shrink-0 mt-0.5">
                            <x-icon name="document-text" class="w-4 h-4 text-orange-600" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-xs font-semibold text-orange-800 font-main mb-2">Dispute Details</h3>
                            <div class="bg-orange-50 rounded-lg p-3 border border-orange-100">
                                <p class="text-sm text-orange-700 leading-relaxed font-secondary">
                                    {{ $dispute->dispute_details }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Resolution Notes (if provided) -->
            @if ($dispute->resolution_notes)
                <div class="bg-white rounded-lg p-4 border border-green-100 shadow-sm mb-6">
                    <div class="flex items-start space-x-3">
                        <div class="p-2 bg-green-100 rounded-lg flex-shrink-0 mt-0.5">
                            <x-icon name="clipboard-check-2" class="w-4 h-4 text-green-600" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-xs font-semibold text-green-800 font-main mb-2">Resolution Notes</h3>
                            <div class="bg-green-50 rounded-lg p-3 border border-green-100">
                                <p class="text-sm text-green-700 leading-relaxed font-secondary">
                                    {{ $dispute->resolution_notes }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Status Alert -->
            <div
                class="bg-gradient-to-r 
                @if ($dispute->status === DisputeStatus::Resolved) from-green-600 to-green-700
                @elseif($dispute->status === DisputeStatus::Pending) from-yellow-600 to-amber-600
                @else from-orange-600 to-red-600 @endif
                rounded-lg p-4
            ">
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        @if ($dispute->status === DisputeStatus::Resolved)
                            <x-icon name="check-circle-solid" class="w-5 h-5 text-white" />
                        @elseif($dispute->status === DisputeStatus::Pending)
                            <x-icon name="clock-solid" class="w-5 h-5 text-white" />
                        @else
                            <x-icon name="exclamation-circle-solid" class="w-5 h-5 text-white" />
                        @endif
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-white font-main">Dispute Status</h3>
                        <p class="text-white/90 text-sm font-secondary mt-0.5">
                            @if ($dispute->status === DisputeStatus::Resolved)
                                This dispute has been successfully resolved and closed.
                            @elseif($dispute->status === DisputeStatus::Pending)
                                This dispute is currently under review and pending resolution.
                            @else
                                This dispute is active and requires attention.
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
