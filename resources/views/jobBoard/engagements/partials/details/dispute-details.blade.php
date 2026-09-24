@if ($engagement->cancellation && $engagement->cancellation->dispute)
    @php $dispute = $engagement->cancellation->dispute; @endphp
    <div
        class="bg-gradient-to-br from-orange-50 to-amber-25 rounded-2xl shadow-lg border border-orange-200 overflow-hidden">
        <!-- Header Section -->
        <div class="bg-gradient-to-r from-orange-600 to-amber-600 px-8 py-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-white/20 rounded-lg backdrop-blur-sm">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z">
                            </path>
                        </svg>
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
                        @if ($dispute->status === 'resolved') text-green-100 bg-white/30
                        @elseif($dispute->status === 'pending') text-yellow-100 bg-white/30
                        @else text-orange-100 bg-white/30 @endif
                    ">
                        @if ($dispute->status === 'resolved')
                            <svg class="w-3 h-3 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"></path>
                            </svg>
                        @elseif($dispute->status === 'pending')
                            <svg class="w-3 h-3 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                                    clip-rule="evenodd"></path>
                            </svg>
                        @endif
                        {{ ucfirst(str_replace('_', ' ', $dispute->status)) }}
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
                            <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
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
                            @if ($dispute->status === 'resolved') bg-green-100
                            @elseif($dispute->status === 'pending') bg-yellow-100
                            @else bg-orange-100 @endif
                        ">
                            @if ($dispute->status === 'resolved')
                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            @elseif($dispute->status === 'pending')
                                <svg class="w-4 h-4 text-yellow-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            @else
                                <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z">
                                    </path>
                                </svg>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-xs font-semibold text-orange-800 font-main mb-1">Current Status</h3>
                            <p
                                class="text-sm font-medium font-secondary
                                @if ($dispute->status === 'resolved') text-green-700
                                @elseif($dispute->status === 'pending') text-yellow-700
                                @else text-orange-700 @endif
                            ">
                                {{ ucfirst(str_replace('_', ' ', $dispute->status)) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Dispute Reason -->
                <div class="bg-white rounded-lg p-4 border border-orange-100 shadow-sm">
                    <div class="flex items-start space-x-3">
                        <div class="p-2 bg-orange-100 rounded-lg">
                            <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                                </path>
                            </svg>
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
                                    ${{ number_format($dispute->resolution_amount, 2) }}
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
                            <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
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
                @if ($dispute->status === 'resolved' && $dispute->resolved_at)
                    <div class="bg-white rounded-lg p-4 border border-green-100 shadow-sm">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
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
                            <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
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
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4">
                                </path>
                            </svg>
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
                @if ($dispute->status === 'resolved') from-green-600 to-green-700
                @elseif($dispute->status === 'pending') from-yellow-600 to-amber-600
                @else from-orange-600 to-red-600 @endif
                rounded-lg p-4
            ">
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        @if ($dispute->status === 'resolved')
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd"></path>
                            </svg>
                        @elseif($dispute->status === 'pending')
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                                    clip-rule="evenodd"></path>
                            </svg>
                        @else
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd"></path>
                            </svg>
                        @endif
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-white font-main">Dispute Status</h3>
                        <p class="text-white/90 text-sm font-secondary mt-0.5">
                            @if ($dispute->status === 'resolved')
                                This dispute has been successfully resolved and closed.
                            @elseif($dispute->status === 'pending')
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
