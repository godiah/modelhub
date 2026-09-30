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
                    <x-icon name="exclamation-triangle-2" class="w-6 h-6 text-white" />
                </div>
                <h2 class="text-xl font-bold text-white font-main">Cancelled Engagement Details</h2>
            </div>
        </div>

        <!-- Content Section -->
        <div class="p-6">
            <!-- Main Details Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Initiated By -->
                <x-card rounded="lg" border="red-100" class="p-4">
                    <div class="flex items-start space-x-3">
                        <div class="p-2 bg-red-100 rounded-lg">
                            <x-icon name="user" class="w-4 h-4 text-red-600" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-xs font-semibold text-red-800 font-main mb-1">Initiated By</h3>
                            <p class="text-sm text-red-700 font-secondary font-medium">
                                {{ $engagement->cancellation->initiator->name }}
                            </p>
                        </div>
                    </div>
                </x-card>

                <!-- Cancellation Type -->
                <x-card rounded="lg" border="red-100" class="p-4">
                    <div class="flex items-start space-x-3">
                        <div class="p-2 bg-red-100 rounded-lg">
                            <x-icon name="tag" class="w-4 h-4 text-red-600" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-xs font-semibold text-red-800 font-main mb-1">Cancellation Type</h3>
                            <p class="text-sm text-red-700 font-secondary font-medium">
                                {{ ucfirst(str_replace('_', ' ', $engagement->cancellation->cancellation_type)) }}
                            </p>
                        </div>
                    </div>
                </x-card>

                <!-- Category -->
                <x-card rounded="lg" border="red-100" class="p-4">
                    <div class="flex items-start space-x-3">
                        <div class="p-2 bg-red-100 rounded-lg">
                            <x-icon name="inbox" class="w-4 h-4 text-red-600" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-xs font-semibold text-red-800 font-main mb-1">Category</h3>
                            <p class="text-sm text-red-700 font-secondary font-medium">
                                {{ ucfirst(str_replace('_', ' ', $engagement->cancellation->reason_category)) }}
                            </p>
                        </div>
                    </div>
                </x-card>

                <!-- Partial Payment -->
                @if ($engagement->cancellation->partial_payment_amount)
                    <x-card rounded="lg" border="red-100" class="p-4">
                        <div class="flex items-start space-x-3">
                            <div class="p-2 bg-red-100 rounded-lg">
                                <x-icon name="cash" class="w-4 h-4 text-red-600" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-xs font-semibold text-red-800 font-main mb-1">Partial Payment</h3>
                                <p class="text-sm font-bold text-red-700 font-main">
                                    <x-money :amount="$engagement->cancellation->partial_payment_amount" />
                                </p>
                            </div>
                        </div>
                    </x-card>
                @endif
            </div>

            <!-- Cancellation Timeline -->
            <x-card rounded="lg" border="red-100" class="p-4 mb-6">
                <div class="flex items-center space-x-3 mb-1">
                    <div class="p-2 bg-red-100 rounded-lg">
                        <x-icon name="clock" class="w-4 h-4 text-red-600" />
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
            </x-card>

            <!-- Reason Details (if provided) -->
            @if ($engagement->cancellation->reason_details)
                <x-card rounded="lg" border="red-100" class="p-4">
                    <div class="flex items-start space-x-3">
                        <div class="p-2 bg-red-100 rounded-lg flex-shrink-0 mt-0.5">
                            <x-icon name="chat-bubble-left-right" class="w-4 h-4 text-red-600" stroke-width="2" />
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
                </x-card>
            @endif

            <!-- Status Alert -->
            <div class="mt-6 bg-red-600 rounded-lg p-4">
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        <x-icon name="exclamation-circle-solid" class="w-5 h-5 text-white" />
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
