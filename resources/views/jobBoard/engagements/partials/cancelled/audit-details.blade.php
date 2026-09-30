<!-- Redesigned Deliverables Timeline -->
<div class="bg-white rounded-2xl shadow-lg border border-neutral-200 overflow-hidden">
    <!-- Header  -->
    <div class="bg-gradient-to-r from-accent to-accent/80 p-6 relative">
        <!-- Abstract clipboard visual element -->
        <div class="absolute right-0 bottom-0 transform translate-y-1/3 translate-x-1/6 opacity-20">
            <svg width="120" height="120" viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg">
                <!-- Clipboard board -->
                <rect x="30" y="25" width="60" height="80" rx="3" fill="none" stroke="#FFFFFF"
                    stroke-width="4" />
                <!-- Clipboard top clamp -->
                <rect x="45" y="15" width="30" height="12" rx="4" fill="#FFFFFF" />
                <rect x="45" y="15" width="30" height="12" rx="4" fill="none" stroke="#FFFFFF"
                    stroke-width="3" />
                <!-- Lines representing list items -->
                <line x1="40" y1="40" x2="80" y2="40" stroke="#FFFFFF" stroke-width="3" />
                <line x1="40" y1="55" x2="80" y2="55" stroke="#FFFFFF" stroke-width="3" />
                <line x1="40" y1="70" x2="80" y2="70" stroke="#FFFFFF" stroke-width="3" />
                <line x1="40" y1="85" x2="65" y2="85" stroke="#FFFFFF" stroke-width="3" />
            </svg>
        </div>
        <div class="flex items-center">
            <x-icon name="clipboard-list" class="h-6 w-6 text-white mr-2" />
            <h2 class="font-tertiary font-semibold text-lg text-white">Project Deliverables</h2>
        </div>
    </div>

    <div class="p-5">
        @foreach ($engagement->deliverables as $deliverable)
            <div
                class="mb-5 bg-white rounded-xl shadow-sm border border-neutral-200 overflow-hidden hover:shadow-md transition-shadow duration-300">
                <div class="px-4 py-3 bg-gradient-to-r from-primary/5 to-transparent border-b border-neutral-100">
                    <h4 class="font-secondary font-semibold text-neutral-800 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-primary" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                            <path fill-rule="evenodd"
                                d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z"
                                clip-rule="evenodd" />
                        </svg>
                        {{ $deliverable->title }}
                    </h4>
                </div>

                <div class="p-4">
                    <div class="relative timeline-container">
                        <!-- Created -->
                        <div class="flex items-start mb-4 relative">
                            <div
                                class="absolute h-full w-0.5 bg-gradient-to-b from-primary via-secondary to-neutral-300 left-3 top-3 -z-10">
                            </div>
                            <div class="flex-shrink-0 w-6 h-6 rounded-full bg-primary flex items-center justify-center shadow-sm"
                                title="Created on {{ $deliverable->created_at->format('M d, Y h:i A') }}">
                                <x-icon name="plus-solid" class="h-3 w-3 text-white" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-neutral-800">Created</p>
                                <p class="text-xs text-neutral-500 flex items-center">
                                    <x-icon name="clock-solid" class="h-3 w-3 mr-1" />
                                    {{ $deliverable->created_at->format('M d, Y h:i A') }}
                                </p>
                            </div>
                        </div>

                        <!-- Submitted -->
                        @if ($deliverable->submitted_at)
                            <div class="flex items-start mb-4 relative">
                                <div
                                    class="absolute h-full w-0.5 bg-gradient-to-b from-secondary via-accent to-neutral-300 left-3 top-3 -z-10">
                                </div>
                                <div class="flex-shrink-0 w-6 h-6 rounded-full bg-secondary flex items-center justify-center shadow-sm"
                                    title="Submitted on {{ $deliverable->submitted_at->format('M d, Y h:i A') }}">
                                    <x-icon name="check-solid" class="h-3 w-3 text-white" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-neutral-800">Submitted</p>
                                    <p class="text-xs text-neutral-500 flex items-center">
                                        <x-icon name="clock-solid" class="h-3 w-3 mr-1" />
                                        {{ $deliverable->submitted_at->format('M d, Y h:i A') }}
                                    </p>
                                </div>
                            </div>
                        @endif

                        <!-- Approved -->
                        @if ($deliverable->approved_at)
                            <div class="flex items-start mb-4 relative">
                                @if (!$loop->last || $deliverable->rejected_at)
                                    <div
                                        class="absolute h-full w-0.5 bg-gradient-to-b from-green-500 via-green-400 to-neutral-300 left-3 top-3 -z-10">
                                    </div>
                                @endif
                                <div class="flex-shrink-0 w-6 h-6 rounded-full bg-green-500 flex items-center justify-center shadow-sm"
                                    title="Approved on {{ $deliverable->approved_at->format('M d, Y h:i A') }}">
                                    <x-icon name="check-solid" class="h-3 w-3 text-white" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-green-700">Approved</p>
                                    <p class="text-xs text-neutral-500 flex items-center">
                                        <x-icon name="clock-solid" class="h-3 w-3 mr-1" />
                                        {{ $deliverable->approved_at->format('M d, Y h:i A') }}
                                    </p>
                                </div>
                            </div>
                        @endif

                        <!-- Rejected -->
                        @if ($deliverable->rejected_at)
                            <div class="flex items-start relative">
                                <div class="flex-shrink-0 w-6 h-6 rounded-full bg-red-500 flex items-center justify-center shadow-sm"
                                    title="Rejected on {{ $deliverable->rejected_at->format('M d, Y h:i A') }}">
                                    <x-icon name="x-mark-solid" class="h-3 w-3 text-white" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-red-700">Rejected</p>
                                    <p class="text-xs text-neutral-500 flex items-center">
                                        <x-icon name="clock-solid" class="h-3 w-3 mr-1" />
                                        {{ $deliverable->rejected_at->format('M d, Y h:i A') }}
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach

        <!-- Empty state if no deliverables -->
        @if ($engagement->deliverables->isEmpty())
            <div class="p-8 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-neutral-100 mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-neutral-400" viewBox="0 0 20 20"
                        fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M5 4a3 3 0 00-3 3v6a3 3 0 003 3h10a3 3 0 003-3V7a3 3 0 00-3-3H5zm-1 9v-1h5v2H5a1 1 0 01-1-1zm7 1h4a1 1 0 001-1v-1h-5v2zm0-4h5V8h-5v2zM9 8H4v2h5V8z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <p class="text-neutral-500 font-medium">No deliverables found</p>
                <p class="text-neutral-400 text-sm mt-1">No deliverables were created for this engagement</p>
            </div>
        @endif
    </div>
</div>

<style>
    /* Custom CSS for additional styling */
    .timeline-container>div:last-child .absolute.h-full {
        height: 0;
    }
</style>
