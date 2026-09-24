<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-tertiary font-bold text-2xl text-primary leading-tight flex items-center">
                {{ $engagement->job->title }}
            </h2>
        </div>
    </x-slot>

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-7xl mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Job Details Card -->
                    @include('jobBoard.engagements.partials.details.job-details')

                    <!-- Application Details -->
                    @include('jobBoard.engagements.partials.details.application-details')

                    <!-- Engagement Timeline -->
                    @include('jobBoard.engagements.partials.details.timeline')

                    <!-- Deliverables -->
                    @include('jobBoard.engagements.partials.details.deliverable-details')

                    <!-- Cancellation Details (if cancelled) -->
                    @include('jobBoard.engagements.partials.details.cancelled-details')

                    <!-- Dispute Details (if disputed) -->
                    @include('jobBoard.engagements.partials.details.dispute-details')

                    <!-- Partial Payments -->
                    @include('jobBoard.engagements.partials.details.partial-payment-details')
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Poster Information -->
                    <div
                        class="relative bg-gradient-to-br from-neutral-50 to-neutral-100  rounded-2xl shadow-lg overflow-hidden">
                        <!-- Header -->
                        <div
                            class="relative px-6 py-4 border-b border-neutral-200 bg-gradient-to-r from-primary to-primary/90">
                            <div class="flex items-center space-x-3">
                                <div
                                    class="w-8 h-8 bg-white/20 backdrop-blur-sm rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-bold text-white font-main">Client</h3>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="px-6 py-4">
                            <div class="flex items-center group">
                                <div
                                    class="h-10 w-10 rounded-full bg-neutral-300 flex items-center justify-center group-hover:ring-2 group-hover:ring-secondary/50 transition-all duration-200">
                                    <span
                                        class="text-sm font-medium text-neutral-700 font-main">{{ $engagement->application->poster->getInitials() }}</span>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-neutral-900 font-main">
                                        {{ $engagement->application->poster->name }}</p>
                                    <p class="text-xs text-neutral-500 font-secondary">
                                        {{ $engagement->application->poster->email }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Applicant Information -->
                    <div
                        class="relative bg-gradient-to-br from-neutral-50 to-neutral-100  rounded-2xl shadow-lg overflow-hidden">

                        <!-- Header -->
                        <div
                            class="relative px-6 py-4 border-b border-neutral-200 bg-gradient-to-r from-primary to-primary/90">
                            <div class="flex items-center space-x-3">
                                <div
                                    class="w-8 h-8 bg-white/20 backdrop-blur-sm rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v1m-7 0H5v-2a3 3 0 015.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20a3 3 0 015.356-1.857M12 14a4 4 0 100-8 4 4 0 000 8z" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-bold text-white font-main">Freelancer</h3>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="px-6 py-4">
                            <div class="flex items-center group">

                                <div
                                    class="h-10 w-10 rounded-full bg-neutral-300 flex items-center justify-center group-hover:ring-2 group-hover:ring-secondary/50 transition-all duration-200">
                                    <span
                                        class="text-sm font-medium text-neutral-700 font-main">{{ $engagement->application->applicant->getInitials() }}</span>
                                </div>

                                <div class="ml-3">
                                    <p class="text-sm font-medium text-neutral-900 font-main">
                                        {{ $engagement->application->applicant->name }}</p>
                                    <p class="text-xs text-neutral-500 font-secondary">
                                        {{ $engagement->application->applicant->email }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Summary -->
                    <div
                        class="relative bg-gradient-to-br from-neutral-50 to-neutral-100  rounded-2xl shadow-lg overflow-hidden">
                        <!-- Header -->
                        <div
                            class="relative px-6 py-4 border-b border-neutral-200 bg-gradient-to-r from-primary to-primary/90">
                            <div class="flex items-center space-x-3">
                                <div
                                    class="w-8 h-8 bg-white/20 backdrop-blur-sm rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-bold text-white font-main">Financial Summary</h3>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="px-6 py-4 space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-neutral-600 font-secondary">Agreed Amount</span>
                                <span
                                    class="text-sm font-medium text-neutral-900 font-main">Ksh{{ number_format($engagement->agreed_amount, 2) }}</span>
                            </div>
                            @if ($engagement->service_fee)
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-neutral-600 font-secondary">Service Fee</span>
                                    <span
                                        class="text-sm font-medium text-neutral-900 font-main">Ksh{{ number_format($engagement->service_fee, 2) }}</span>
                                </div>
                            @endif
                            @if ($engagement->net_amount)
                                <div class="flex justify-between items-center pt-3 border-t border-neutral-200">
                                    <span class="text-sm font-medium text-neutral-900 font-main">Net Amount</span>
                                    <span
                                        class="text-sm font-medium text-neutral-900 font-main">Ksh{{ number_format($engagement->net_amount, 2) }}</span>
                                </div>
                            @endif
                            <!-- Payment Status -->
                            <div class="pt-3 border-t border-neutral-200 space-y-2">
                                @if ($engagement->payment_escrowed_at)
                                    <div
                                        class="flex items-center text-xs font-tertiary bg-gradient-to-r from-green-100 to-green-200 text-green-800 px-3 py-1 rounded-full">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        Payment Escrowed
                                    </div>
                                @endif
                                @if ($engagement->payment_released_at)
                                    <div
                                        class="flex items-center text-xs font-tertiary bg-gradient-to-r from-green-100 to-green-200 text-green-800 px-3 py-1 rounded-full">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        Payment Released
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    @if ($engagement->notes)
                        <div
                            class="relative bg-gradient-to-br from-neutral-50 to-neutral-100  rounded-2xl shadow-lg overflow-hidden">

                            <!-- Header -->
                            <div
                                class="relative px-6 py-4 border-b border-neutral-200 bg-gradient-to-r from-primary to-primary/90">
                                <div class="flex items-center space-x-3">
                                    <div
                                        class="w-8 h-8 bg-white/20 backdrop-blur-sm rounded-lg flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </div>
                                    <h3 class="text-lg font-bold text-white font-main">Notes</h3>
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="px-6 py-4">
                                <p class="text-sm text-neutral-700 font-secondary">{{ $engagement->notes }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
