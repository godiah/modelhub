<!-- Application Details -->
<div class="bg-white rounded-2xl shadow-lg border border-neutral-100 overflow-hidden">
    <!-- Header Section -->
    <div class="relative bg-gradient-to-r from-secondary to-secondary/90 px-8 py-6">
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
                <x-icon name="document-text-solid" class="w-6 h-6 text-white" />
            </div>
            <h2 class="text-xl font-bold text-white font-main">Application Details</h2>
        </div>
    </div>

    <!-- Proposal Section -->
    <div class="px-8 py-6">
        <div
            class="bg-gradient-to-br from-neutral-50 to-neutral-100/50 rounded-xl p-6 border border-neutral-200 relative overflow-hidden">
            <!-- Decorative Elements -->
            <div class="absolute top-0 right-0 w-20 h-20 opacity-5">
                <svg viewBox="0 0 24 24" fill="currentColor" class="w-full h-full text-secondary">
                    <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z" />
                </svg>
            </div>

            <div class="relative">
                <div class="flex items-center mb-4">
                    <div class="w-1 h-6 bg-gradient-to-b from-secondary to-secondary/60 rounded-full mr-3"></div>
                    <h3 class="text-lg font-semibold text-neutral-800 font-main flex items-center">
                        <x-icon name="document-text-solid" class="w-5 h-5 mr-2 text-secondary" />
                        Proposal
                    </h3>
                </div>

                @if ($engagement->application->proposal)
                    <div class="bg-white rounded-lg p-6 border border-neutral-200 shadow-sm">
                        <div class="prose max-w-none font-main text-neutral-700 text-sm leading-relaxed">
                            {!! nl2br(e($engagement->application->proposal)) !!}
                        </div>
                    </div>
                @else
                    <div
                        class="flex flex-col items-center justify-center py-6 bg-white rounded-lg border-2 border-dashed border-neutral-300">
                        <div class="w-12 h-12 bg-neutral-100 rounded-full flex items-center justify-center mb-4">
                            <x-icon name="exclamation-triangle-3" class="w-6 h-6 text-neutral-400" />
                        </div>
                        <p class="text-neutral-500 font-main text-center">
                            <span class="block font-medium text-sm">No proposal provided</span>
                            <span class="text-xs">The applicant did not submit a detailed proposal.</span>
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Application Info Grid -->
    <div class="px-8 py-6 bg-gradient-to-r from-neutral-50/50 to-transparent border-t border-neutral-100">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Proposed Offer -->
            <div class="bg-gradient-to-br from-accent/5 to-accent/10 rounded-xl p-5 border border-accent/20">
                <div class="flex items-center mb-2">
                    <div class="w-10 h-10 bg-accent/20 rounded-lg flex items-center justify-center mr-3">
                        <x-icon name="cash" class="w-5 h-5 text-accent" />
                    </div>
                    <div>
                        <span class="block text-sm font-medium text-neutral-600 font-secondary">Proposed Offer</span>
                    </div>
                </div>
                <p class="text-lg font-bold text-accent font-main">
                    <x-money :amount="$engagement->application->offer_amount" />
                </p>
            </div>

            <!-- Applied On -->
            <div class="bg-gradient-to-br from-primary/5 to-primary/10 rounded-xl p-5 border border-primary/20">
                <div class="flex items-center mb-2">
                    <div class="w-10 h-10 bg-primary/20 rounded-lg flex items-center justify-center mr-3">
                        <x-icon name="calendar" class="w-5 h-5 text-primary" />
                    </div>
                    <div>
                        <span class="block text-sm font-medium text-neutral-600 font-secondary">Applied On</span>
                    </div>
                </div>
                <p class="text-lg font-bold text-primary font-main">
                    <x-date :date="$engagement->application->created_at" format="M j, Y g:i A" />
                </p>
            </div>
        </div>
    </div>
</div>
