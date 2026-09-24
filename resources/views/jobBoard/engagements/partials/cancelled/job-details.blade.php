<div class="bg-white rounded-2xl shadow-lg border border-neutral-200 overflow-hidden relative">
    <!-- Subtle background pattern -->
    <div class="absolute inset-0 opacity-5">
        <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
            <pattern id="grid" width="20" height="20" patternUnits="userSpaceOnUse">
                <path d="M 20 0 L 0 0 0 20" fill="none" stroke="#1E3A8A" stroke-width="0.5" />
            </pattern>
            <rect width="100%" height="100%" fill="url(#grid)" />
        </svg>
    </div>

    <!-- Header section -->
    <div class="bg-gradient-to-r from-primary to-primary/80 p-6 relative">
        <!-- Abstract curved shape for visual interest -->
        <div class="absolute right-0 bottom-0 transform translate-y-1/4 translate-x-1/4 opacity-20">
            <svg width="120" height="120" viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg">
                <circle cx="60" cy="60" r="50" fill="#FFFFFF" />
            </svg>
        </div>

        <div class="flex items-start justify-between">
            <div class="flex-grow">
                <h2 class="font-tertiary font-bold text-2xl text-white">{{ $engagement->job->title }}
                </h2>
                <div class="flex mt-2 space-x-4 text-white/90">
                    <div class="flex items-center text-sm">
                        <svg class="h-4 w-4 mr-1" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 8V12L15 15" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2" />
                        </svg>
                        <span>Posted {{ $engagement->job->created_at->format('M d, Y') }}</span>
                    </div>
                    <div class="flex items-center text-sm">
                        <svg class="h-4 w-4 mr-2" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M9 16H15M12 12V8M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <span>Applied
                            {{ $engagement->application->created_at->format('M d, Y') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Financial information section -->
    <div class="bg-neutral-50 p-4 border-b border-neutral-200">
        <div class="grid grid-cols-2 gap-4">
            <div class="bg-white rounded-lg p-3 border border-neutral-200 shadow-sm">
                <div class="text-xs text-neutral-500 uppercase tracking-wider font-medium">Budget</div>
                <div class="text-lg font-secondary font-bold text-neutral-800">
                    <span class="text-primary">Ksh{{ number_format($engagement->job->budget, 2) }}</span>
                </div>
            </div>
            <div class="bg-white rounded-lg p-3 border border-neutral-200 shadow-sm">
                <div class="text-xs text-neutral-500 uppercase tracking-wider font-medium">Your Offer
                </div>
                <div class="text-lg font-secondary font-bold text-neutral-800">
                    <span
                        class="text-secondary">Ksh{{ number_format($engagement->application->offer_amount, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- People involved section -->
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Freelancer Details -->
            <div class="bg-neutral-50 rounded-xl p-4 border border-neutral-200">
                <h3 class="font-secondary text-lg font-semibold text-neutral-700 mb-3 flex items-center">
                    <svg class="h-5 w-5 mr-2 text-secondary" viewBox="0 0 24 24" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M16 7C16 9.20914 14.2091 11 12 11C9.79086 11 8 9.20914 8 7C8 4.79086 9.79086 3 12 3C14.2091 3 16 4.79086 16 7Z"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M12 14C8.13401 14 5 17.134 5 21H19C19 17.134 15.866 14 12 14Z" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    Freelancer
                </h3>
                <div class="flex items-center bg-white p-3 rounded-lg border border-neutral-200 shadow-sm">
                    <div
                        class="flex items-center justify-center h-12 w-12 rounded-full bg-gradient-to-br from-secondary to-primary text-white font-medium mr-3">
                        {{ $engagement->applicant->getInitials() }}
                    </div>
                    <div>
                        <h4 class="font-medium text-neutral-800">{{ $engagement->applicant->name }}
                        </h4>
                        <div class="text-sm text-neutral-500">Freelancer</div>
                    </div>
                </div>
            </div>

            <!-- Client Details -->
            <div class="bg-neutral-50 rounded-xl p-4 border border-neutral-200">
                <h3 class="font-secondary text-lg font-semibold text-neutral-700 mb-3 flex items-center">
                    <svg class="h-5 w-5 mr-2 text-accent" viewBox="0 0 24 24" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M12 4C9.79086 4 8 5.79086 8 8C8 10.2091 9.79086 12 12 12C14.2091 12 16 10.2091 16 8C16 5.79086 14.2091 4 12 4Z"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M5 20V19C5 16.2386 7.23858 14 10 14H14C16.7614 14 19 16.2386 19 19V20"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    Client
                </h3>
                <div class="flex items-center bg-white p-3 rounded-lg border border-neutral-200 shadow-sm">
                    <div
                        class="flex items-center justify-center h-12 w-12 rounded-full bg-gradient-to-br from-accent to-accent/70 text-white font-medium mr-3">
                        {{ $engagement->poster->getInitials() }}
                    </div>
                    <div>
                        <h4 class="font-medium text-neutral-800">{{ $engagement->poster->name }}</h4>
                        <div class="text-sm text-neutral-500">Client</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
