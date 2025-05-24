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
                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z"
                        clip-rule="evenodd" />
                </svg>
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
                        <svg class="w-5 h-5 mr-2 text-secondary" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z"
                                clip-rule="evenodd" />
                        </svg>
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
                            <svg class="w-6 h-6 text-neutral-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                            </svg>
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
                        <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div>
                        <span class="block text-sm font-medium text-neutral-600 font-secondary">Proposed Offer</span>
                    </div>
                </div>
                <p class="text-lg font-bold text-accent font-main">
                    Ksh{{ number_format($engagement->application->offer_amount, 2) }}
                </p>
            </div>

            <!-- Applied On -->
            <div class="bg-gradient-to-br from-primary/5 to-primary/10 rounded-xl p-5 border border-primary/20">
                <div class="flex items-center mb-2">
                    <div class="w-10 h-10 bg-primary/20 rounded-lg flex items-center justify-center mr-3">
                        <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <span class="block text-sm font-medium text-neutral-600 font-secondary">Applied On</span>
                    </div>
                </div>
                <p class="text-lg font-bold text-primary font-main">
                    {{ $engagement->application->created_at->format('M j, Y g:i A') }}
                </p>
            </div>
        </div>
    </div>
</div>
