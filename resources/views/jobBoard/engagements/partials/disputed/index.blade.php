<!-- Disputed Engagement Status Section -->
<div class="bg-gradient-to-br from-amber-50 via-orange-50 to-red-50 p-6 relative overflow-hidden font-main">
    <!-- Background Pattern -->
    {{-- <div class="absolute inset-0 opacity-5">
        <svg class="w-full h-full" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
            <pattern id="dispute-pattern" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse">
                <path d="M10 2L18 10L10 18L2 10Z" stroke="currentColor" stroke-width="0.5" fill="none" />
            </pattern>
            <rect width="100" height="100" fill="url(#dispute-pattern)" />
        </svg>
    </div> --}}

    <!-- Main Content Container -->
    <div class="relative z-10">
        <!-- Header Section -->
        <div class="flex items-start justify-between mb-4">
            <div class="flex items-center space-x-3">
                <!-- Dispute Icon with Animation -->
                <div class="relative">
                    <div class="absolute inset-0 bg-accent/20 rounded-full animate-ping"></div>
                    <div class="relative bg-accent/10 backdrop-blur-sm rounded-full p-3 border border-accent/20">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-accent" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                    </div>
                </div>

                <!-- Title and Status -->
                <div>
                    <h3 class="font-main font-medium text-lg text-neutral-800 mb-1">
                        Engagement Under Dispute
                    </h3>
                    <div class="flex items-center space-x-2">
                        @php
                            $status = $engagement->cancellation->dispute->status ?? null;

                            $badgeConfig = [
                                'pending' => [
                                    'label' => 'Pending',
                                    'bg' => 'bg-yellow-100',
                                    'text' => 'text-yellow-800',
                                    'dot' => 'bg-yellow-500',
                                ],
                                'under_review' => [
                                    'label' => 'Under Review',
                                    'bg' => 'bg-blue-100',
                                    'text' => 'text-blue-800',
                                    'dot' => 'bg-blue-500',
                                ],
                                'resolved' => [
                                    'label' => 'Resolved',
                                    'bg' => 'bg-green-100',
                                    'text' => 'text-green-800',
                                    'dot' => 'bg-green-500',
                                ],
                                'default' => [
                                    'label' => 'Unknown',
                                    'bg' => 'bg-gray-100',
                                    'text' => 'text-gray-800',
                                    'dot' => 'bg-gray-400',
                                ],
                            ];

                            $config = $badgeConfig[$status] ?? $badgeConfig['default'];
                        @endphp

                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $config['bg'] }} {{ $config['text'] }} border border-neutral-200">
                            <div class="w-1.5 h-1.5 {{ $config['dot'] }} rounded-full mr-1.5"></div>
                            {{ $config['label'] }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alert Message -->
        <div class="bg-white/70 backdrop-blur-sm rounded-lg p-4 border border-neutral-200/50 mb-4">
            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0 mt-0.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-accent" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <h4 class="font-tertiary font-semibold text-neutral-800 mb-2">Job Temporarily Frozen</h4>
                    <p class="text-sm text-neutral-600 leading-relaxed">
                        During the dispute resolution process, this engagement has been completely frozen. No further
                        actions, payments, or deliverables can be processed until the dispute is resolved by our
                        mediation team.
                    </p>
                </div>
            </div>
        </div>

        <!-- Impact Notice -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-5">
            <div class="bg-white/50 backdrop-blur-sm rounded-lg p-3 border border-neutral-200/30">
                <div class="flex items-center space-x-2 mb-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-secondary" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                    </svg>
                    <span class="text-xs font-bold text-neutral-600 font-tertiary">Payments</span>
                </div>
                <p class="text-sm text-neutral-500 font-main">Suspended</p>
            </div>

            <div class="bg-white/50 backdrop-blur-sm rounded-lg p-3 border border-neutral-200/30">
                <div class="flex items-center space-x-2 mb-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-secondary" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span class="text-xs font-bold text-neutral-600 font-tertiary">Deliverables</span>
                </div>
                <p class="text-sm text-neutral-500 font-main">On Hold</p>
            </div>

            <div class="bg-white/50 backdrop-blur-sm rounded-lg p-3 border border-neutral-200/30">
                <div class="flex items-center space-x-2 mb-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-secondary" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                    <span class="text-xs font-bold text-neutral-600 font-tertiary">Communication</span>
                </div>
                <p class="text-sm text-neutral-500 font-main">Limited</p>
            </div>
        </div>

        <!-- Action Section -->
        <div
            class="flex flex-col sm:flex-row items-start sm:items-center justify-between space-y-3 sm:space-y-0 sm:space-x-4">
            <!-- View Dispute Details Button -->
            <a href="{{ route('engagements.show-disputed', $engagement->id) }}"
                class="group inline-flex items-center px-5 py-2.5 bg-secondary text-white text-sm font-medium font-main rounded-lg shadow-sm hover:bg-secondary/90 transition-all duration-200 hover:shadow-md transform hover:-translate-y-0.5 focus:ring-2 focus:ring-secondary/20 focus:outline-none">
                <svg xmlns="http://www.w3.org/2000/svg"
                    class="h-4 w-4 mr-2 group-hover:scale-110 transition-transform duration-200" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                View Dispute Details
                <svg xmlns="http://www.w3.org/2000/svg"
                    class="h-4 w-4 ml-1 group-hover:translate-x-0.5 transition-transform duration-200" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
            </a>

            <!-- Support Contact -->
            <div class="flex items-center space-x-2 text-sm text-neutral-600 font-main">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-secondary" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                </svg>
                <span>Need help? <a href="#"
                        class="text-secondary hover:text-secondary/80 underline underline-offset-2">Contact
                        Support</a></span>
            </div>
        </div>

        <!-- Timeline Indicator -->
        <div class="mt-4 pt-4 border-t border-neutral-200/50">
            <div class="flex items-center justify-between text-xs text-neutral-500 font-tertiary">
                <span>Dispute initiated:
                    {{ optional($engagement->cancellation->dispute)->created_at
                        ? $engagement->cancellation->dispute->created_at->format('M d, Y')
                        : 'Recently' }}
                </span>

            </div>
        </div>
    </div>
</div>
