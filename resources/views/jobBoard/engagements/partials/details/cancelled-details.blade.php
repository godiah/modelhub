@if ($engagement->cancelled_at && $engagement->cancellation)
    <div class="bg-gradient-to-br from-red-50 to-red-25  rounded-2xl shadow-lg border border-red-200 overflow-hidden">
        <!-- Header Section -->
        <div class="relative bg-gradient-to-r from-red-600 to-red-700 px-8 py-6">
            <!-- Background Pattern -->
            <div class="absolute inset-0 opacity-10">
                <svg class="w-full h-full" viewBox="0 0 400 200" fill="currentColor">
                    <defs>
                        <pattern id="hexagons" x="0" y="0" width="30" height="26" patternUnits="userSpaceOnUse">
                            <polygon points="15,2 25,8 25,18 15,24 5,18 5,8" fill="none" stroke="currentColor"
                                stroke-width="0.5" />
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#hexagons)" />
                </svg>
            </div>

            <!-- Header Content -->
            <div class="relative flex items-center">
                <div class="w-10 h-10 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center mr-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-white font-main">Cancelled Engagement Details</h2>
            </div>
        </div>

        <!-- Content Section -->
        <div class="p-6">
            <!-- Main Details Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Initiated By -->
                <div class="bg-white rounded-lg p-4 border border-red-100 shadow-sm">
                    <div class="flex items-start space-x-3">
                        <div class="p-2 bg-red-100 rounded-lg">
                            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-xs font-semibold text-red-800 font-main mb-1">Initiated By</h3>
                            <p class="text-sm text-red-700 font-secondary font-medium">
                                {{ $engagement->cancellation->initiator->name }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Cancellation Type -->
                <div class="bg-white rounded-lg p-4 border border-red-100 shadow-sm">
                    <div class="flex items-start space-x-3">
                        <div class="p-2 bg-red-100 rounded-lg">
                            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z">
                                </path>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-xs font-semibold text-red-800 font-main mb-1">Cancellation Type</h3>
                            <p class="text-sm text-red-700 font-secondary font-medium">
                                {{ ucfirst(str_replace('_', ' ', $engagement->cancellation->cancellation_type)) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Category -->
                <div class="bg-white rounded-lg p-4 border border-red-100 shadow-sm">
                    <div class="flex items-start space-x-3">
                        <div class="p-2 bg-red-100 rounded-lg">
                            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                                </path>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-xs font-semibold text-red-800 font-main mb-1">Category</h3>
                            <p class="text-sm text-red-700 font-secondary font-medium">
                                {{ ucfirst(str_replace('_', ' ', $engagement->cancellation->reason_category)) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Partial Payment -->
                @if ($engagement->cancellation->partial_payment_amount)
                    <div class="bg-white rounded-lg p-4 border border-red-100 shadow-sm">
                        <div class="flex items-start space-x-3">
                            <div class="p-2 bg-red-100 rounded-lg">
                                <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-xs font-semibold text-red-800 font-main mb-1">Partial Payment</h3>
                                <p class="text-sm font-bold text-red-700 font-main">
                                    Ksh{{ number_format($engagement->cancellation->partial_payment_amount, 2) }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Cancellation Timeline -->
            <div class="bg-white rounded-lg p-4 border border-red-100 shadow-sm mb-6">
                <div class="flex items-center space-x-3 mb-1">
                    <div class="p-2 bg-red-100 rounded-lg">
                        <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-red-800 font-main">Cancellation Date</h3>
                </div>
                <div class="ml-11">
                    <p class="text-sm text-red-700 font-secondary">
                        {{ $engagement->cancelled_at->format('F j, Y \a\t g:i A') }}
                    </p>
                    <p class="text-xs text-red-500 mt-1 font-secondary">
                        {{ $engagement->cancelled_at->diffForHumans() }}
                    </p>
                </div>
            </div>

            <!-- Reason Details (if provided) -->
            @if ($engagement->cancellation->reason_details)
                <div class="bg-white rounded-lg p-4 border border-red-100 shadow-sm">
                    <div class="flex items-start space-x-3">
                        <div class="p-2 bg-red-100 rounded-lg flex-shrink-0 mt-0.5">
                            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-sm font-semibold text-red-800 font-main mb-2">Cancellation Reason By
                                Initiator</h3>
                            <div class="bg-red-50 rounded-lg p-3 border border-red-100">
                                <p class="text-sm text-red-700 leading-relaxed font-secondary">
                                    {{ $engagement->cancellation->reason_details }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Status Alert -->
            <div class="mt-6 bg-red-600 rounded-lg p-4">
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-white font-main">Engagement Status</h3>
                        <p class="text-red-100 text-sm font-secondary mt-0.5">
                            This engagement was permanently cancelled and cannot be reactivated.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
