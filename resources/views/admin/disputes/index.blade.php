<x-app-layout>
    <!-- Main Container with Background Pattern -->
    <div class="min-h-screen bg-gradient-to-br from-neutral-50 via-white to-neutral-100 relative overflow-hidden">
        <!-- Background Pattern -->
        {{-- <div class="absolute inset-0 opacity-5">
            <svg class="w-full h-full" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                <pattern id="admin-grid" x="0" y="0" width="10" height="10" patternUnits="userSpaceOnUse">
                    <path d="M 10 0 L 0 0 0 10" fill="none" stroke="currentColor" stroke-width="0.5" />
                </pattern>
                <rect width="100" height="100" fill="url(#admin-grid)" />
            </svg>
        </div> --}}

        <div class="relative z-10 max-w-7xl mx-auto p-8">
            <!-- Header Section -->
            <div class="mb-8">
                <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl border border-neutral-200/50 p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <!-- Admin Icon -->
                            <div class="bg-gradient-to-br from-primary to-primary/80 rounded-xl p-3 shadow-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.031 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-2xl font-bold font-main text-neutral-800 mb-1">Disputed Engagements</h1>
                                <p class="text-neutral-600 font-secondary">Manage and resolve engagement disputes</p>
                            </div>
                        </div>

                        <!-- Stats Summary -->
                        <div class="hidden lg:flex items-center space-x-6">
                            <div class="text-center">
                                <div class="text-2xl font-bold font-main text-accent">
                                    {{ $statusCounts['pending'] ?? 0 }}</div>
                                <div class="text-xs font-secondary text-neutral-500 uppercase tracking-wide">Pending
                                </div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold font-main text-secondary">
                                    {{ $statusCounts['under_review'] ?? 0 }}</div>
                                <div class="text-xs font-secondary text-neutral-500 uppercase tracking-wide">Under
                                    Review</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold font-main text-primary">{{ $statusCounts['all'] ?? 0 }}
                                </div>
                                <div class="text-xs font-secondary text-neutral-500 uppercase tracking-wide">Total</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation Bar -->
            <div class="mb-8">
                <!-- Enhanced Tab Navigation -->
                <div
                    class="bg-white backdrop-blur-sm rounded-xl shadow-lg border border-neutral-200/50 overflow-hidden">
                    <!-- Tab Navigation Bar -->
                    <div class="border-b border-neutral-200 bg-gradient-to-r from-neutral-50 to-white">
                        <nav class="flex" aria-label="Dispute Status Tabs">
                            <!-- All Disputes Tab -->
                            <a href="{{ route('admin.disputes.index', ['status' => 'all']) }}"
                                class="group relative flex-1 flex items-center justify-center px-6 py-4 text-sm font-medium font-tertiary transition-all duration-300 hover:bg-neutral-50
                                      @if ($status === 'all') bg-white text-primary border-b-3 border-primary shadow-sm
                                      @else 
                                          text-tertiary hover:text-primary border-b-3 border-transparent @endif">

                                <!-- Tab Icon -->
                                <div
                                    class="flex items-center justify-center w-8 h-8 rounded-lg mr-3 transition-all duration-300
                                           @if ($status === 'all') bg-primary/10 text-primary
                                           @else 
                                               bg-neutral-100 text-tertiary group-hover:bg-primary/10 group-hover:text-primary @endif">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                </div>

                                <div class="flex flex-col items-start">
                                    <span class="font-semibold">All Disputes</span>
                                    <div class="flex items-center space-x-2 mt-1">
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold
                                                     @if ($status === 'all') bg-primary text-white
                                                     @else 
                                                         bg-neutral-200 text-neutral-700 group-hover:bg-primary/20 group-hover:text-primary @endif
                                                     transition-all duration-300">
                                            {{ $statusCounts['all'] ?? 0 }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Active Tab Indicator -->
                                @if ($status === 'all')
                                    <div
                                        class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-primary to-secondary rounded-t-full">
                                    </div>
                                @endif
                            </a>

                            <!-- Pending Tab -->
                            <a href="{{ route('admin.disputes.index', ['status' => 'pending']) }}"
                                class="group relative flex-1 flex items-center justify-center px-6 py-4 text-sm font-medium font-tertiary transition-all duration-300 hover:bg-neutral-50
                                      @if ($status === 'pending') bg-white text-accent border-b-3 border-accent shadow-sm
                                      @else 
                                          text-tertiary hover:text-accent border-b-3 border-transparent @endif">

                                <!-- Tab Icon with Animation -->
                                <div
                                    class="flex items-center justify-center w-8 h-8 rounded-lg mr-3 transition-all duration-300
                                           @if ($status === 'pending') bg-accent/10 text-accent animate-pulse
                                           @else 
                                               bg-neutral-100 text-tertiary group-hover:bg-accent/10 group-hover:text-accent @endif">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>

                                <div class="flex flex-col items-start">
                                    <span class="font-semibold">Pending</span>
                                    <div class="flex items-center space-x-2 mt-1">
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold
                                                     @if ($status === 'pending') bg-accent text-white
                                                     @else 
                                                         bg-amber-100 text-amber-800 group-hover:bg-accent/20 group-hover:text-accent @endif
                                                     transition-all duration-300">
                                            {{ $statusCounts['pending'] ?? 0 }}
                                        </span>
                                        @if (($statusCounts['pending'] ?? 0) > 0)
                                            <div class="w-2 h-2 bg-accent rounded-full animate-pulse"></div>
                                        @endif
                                    </div>
                                </div>

                                @if ($status === 'pending')
                                    <div
                                        class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-accent to-amber-400 rounded-t-full">
                                    </div>
                                @endif
                            </a>

                            <!-- Under Review Tab -->
                            <a href="{{ route('admin.disputes.index', ['status' => 'under_review']) }}"
                                class="group relative flex-1 flex items-center justify-center px-6 py-4 text-sm font-medium font-tertiary transition-all duration-300 hover:bg-neutral-50
                                      @if ($status === 'under_review') bg-white text-secondary border-b-3 border-secondary shadow-sm
                                      @else 
                                          text-tertiary hover:text-secondary border-b-3 border-transparent @endif">

                                <!-- Tab Icon -->
                                <div
                                    class="flex items-center justify-center w-8 h-8 rounded-lg mr-3 transition-all duration-300
                                           @if ($status === 'under_review') bg-secondary/10 text-secondary
                                           @else 
                                               bg-neutral-100 text-tertiary group-hover:bg-secondary/10 group-hover:text-secondary @endif">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                    </svg>
                                </div>

                                <div class="flex flex-col items-start">
                                    <span class="font-semibold">Under Review</span>
                                    <div class="flex items-center space-x-2 mt-1">
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold
                                                     @if ($status === 'under_review') bg-secondary text-white
                                                     @else 
                                                         bg-teal-100 text-teal-800 group-hover:bg-secondary/20 group-hover:text-secondary @endif
                                                     transition-all duration-300">
                                            {{ $statusCounts['under_review'] ?? 0 }}
                                        </span>
                                    </div>
                                </div>

                                @if ($status === 'under_review')
                                    <div
                                        class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-secondary to-teal-400 rounded-t-full">
                                    </div>
                                @endif
                            </a>

                            <!-- Resolved Tab -->
                            <a href="{{ route('admin.disputes.index', ['status' => 'resolved']) }}"
                                class="group relative flex-1 flex items-center justify-center px-6 py-4 text-sm font-medium font-tertiary transition-all duration-300 hover:bg-neutral-50
                                      @if ($status === 'resolved') bg-white text-green-600 border-b-3 border-green-600 shadow-sm
                                      @else 
                                          text-tertiary hover:text-green-600 border-b-3 border-transparent @endif">

                                <!-- Tab Icon -->
                                <div
                                    class="flex items-center justify-center w-8 h-8 rounded-lg mr-3 transition-all duration-300
                                           @if ($status === 'resolved') bg-green-100 text-green-600
                                           @else 
                                               bg-neutral-100 text-tertiary group-hover:bg-green-100 group-hover:text-green-600 @endif">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>

                                <div class="flex flex-col items-start">
                                    <span class="font-semibold">Resolved</span>
                                    <div class="flex items-center space-x-2 mt-1">
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold
                                                     @if ($status === 'resolved') bg-green-600 text-white
                                                     @else 
                                                         bg-green-100 text-green-800 group-hover:bg-green-200 group-hover:text-green-700 @endif
                                                     transition-all duration-300">
                                            {{ $statusCounts['resolved'] ?? 0 }}
                                        </span>
                                    </div>
                                </div>

                                @if ($status === 'resolved')
                                    <div
                                        class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-green-600 to-green-400 rounded-t-full">
                                    </div>
                                @endif
                            </a>
                        </nav>
                    </div>

                    <!-- Tab Content Summary Bar -->
                    <div class="bg-gradient-to-r from-neutral-50 to-white px-6 py-4 border-b border-neutral-100">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="flex items-center space-x-2 text-sm text-tertiary font-secondary">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.707A1 1 0 013 7V4z" />
                                    </svg>
                                    <span>
                                        @if ($status === 'all')
                                            Showing all dispute records
                                        @else
                                            Filtered by: <span
                                                class="font-semibold capitalize">{{ str_replace('_', ' ', $status) }}</span>
                                        @endif
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-center space-x-3">
                                <!-- Quick Stats -->
                                <div class="hidden lg:flex items-center space-x-4 text-xs font-secondary">
                                    <div class="flex items-center space-x-1">
                                        <div class="w-2 h-2 bg-accent rounded-full"></div>
                                        <span class="text-tertiary">{{ $statusCounts['pending'] ?? 0 }} Urgent</span>
                                    </div>
                                    <div class="flex items-center space-x-1">
                                        <div class="w-2 h-2 bg-secondary rounded-full"></div>
                                        <span class="text-tertiary">{{ $statusCounts['under_review'] ?? 0 }} In
                                            Progress</span>
                                    </div>
                                    <div class="flex items-center space-x-1">
                                        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                        <span class="text-tertiary">{{ $statusCounts['resolved'] ?? 0 }}
                                            Completed</span>
                                    </div>
                                </div>

                                <!-- Action Button -->
                                {{-- <button
                                    class="inline-flex items-center px-3 py-1.5 bg-primary text-white text-xs font-medium rounded-lg hover:bg-primary/90 transition-colors duration-200 shadow-sm">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                    </svg>
                                    Export
                                </button> --}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional CSS for enhanced visual effects -->
            <style>
                @keyframes slideIn {
                    from {
                        transform: translateY(-10px);
                        opacity: 0;
                    }

                    to {
                        transform: translateY(0);
                        opacity: 1;
                    }
                }

                .border-b-3 {
                    border-bottom-width: 3px;
                }

                /* Smooth gradient animation for active tabs */
                .group:hover .animate-pulse {
                    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
                }

                /* Custom backdrop blur for modern glass effect */
                .backdrop-blur-sm {
                    backdrop-filter: blur(4px);
                }
            </style>

            <!-- Disputes List -->
            <div class="space-y-6">
                @forelse($disputes as $dispute)
                    <div
                        class="bg-white/90 backdrop-blur-sm rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 border border-neutral-200/50 overflow-hidden group">
                        <!-- Status Bar -->
                        <div
                            class="h-1 bg-gradient-to-r 
                            @if ($dispute->status === 'pending') from-accent to-accent/70
                            @elseif($dispute->status === 'under_review') from-secondary to-secondary/70 @endif">
                        </div>

                        <div class="p-6">
                            <!-- Header Row -->
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-center space-x-4">
                                    <!-- Status Icon -->
                                    <div class="relative">
                                        @if ($dispute->status === 'pending')
                                            <div class="absolute inset-0 bg-accent/20 rounded-full"></div>
                                            <div
                                                class="relative bg-accent/10 backdrop-blur-sm rounded-full p-3 border border-accent/30">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-accent"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                    stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                        @elseif($dispute->status === 'under_review')
                                            <div
                                                class="bg-secondary/10 backdrop-blur-sm rounded-full p-3 border border-secondary/30">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-secondary"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                    stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0 1 18 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v9.375m-8.25-3 1.5 1.5 3-3.75" />
                                                </svg>
                                            </div>
                                        @endif
                                    </div>

                                    <div>
                                        <h3 class="text-xl font-bold font-main text-neutral-800 mb-1">
                                            Engagement #{{ $dispute->cancellation->engagement_id }}
                                        </h3>
                                        <div class="flex items-center space-x-3">
                                            <span
                                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium font-tertiary
                                                @if ($dispute->status === 'pending') bg-accent/10 text-accent border border-accent/20
                                                @elseif($dispute->status === 'under_review') bg-secondary/10 text-secondary border border-secondary/20 
                                                @else bg-green-50 text-green-600 border border-green-500 @endif">
                                                <div
                                                    class="w-1.5 h-1.5 
                                                    @if ($dispute->status === 'pending') bg-accent 
                                                    @elseif($dispute->status === 'under_review') bg-secondary 
                                                    @else bg-green-600 @endif rounded-full mr-2">
                                                </div>
                                                {{ ucfirst(str_replace('_', ' ', $dispute->status)) }}
                                            </span>
                                            <span class="text-neutral-400">•</span>
                                            <span class="text-sm text-neutral-500 font-secondary">
                                                Filed {{ $dispute->created_at->diffForHumans() }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Priority Indicator -->
                                <div class="flex items-center space-x-2">
                                    @if ($dispute->status === 'pending')
                                        <div
                                            class="bg-accent/10 text-accent px-2 py-1 rounded-lg text-xs font-medium font-tertiary">
                                            High Priority
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Details Grid -->
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                                <!-- Left Column -->
                                <div class="space-y-4">
                                    <!-- Dispute Reason -->
                                    <div class="bg-neutral-50/80 rounded-lg p-4 border border-neutral-200/50">
                                        <div class="flex items-center space-x-1 mb-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-neutral-500"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span class="text-sm font-medium text-neutral-700 font-tertiary">Dispute
                                                Reason</span>
                                        </div>
                                        <p class="text-neutral-800 font-secondary">
                                            {{ ucwords(str_replace('_', ' ', $dispute->dispute_reason)) }}
                                        </p>
                                    </div>

                                    <!-- Filed By -->
                                    <div class="bg-neutral-50/80 rounded-lg p-4 border border-neutral-200/50">
                                        <div class="flex items-center space-x-1 mb-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-neutral-500"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            <span class="text-sm font-medium text-neutral-700 font-tertiary">Filed
                                                By</span>
                                        </div>
                                        <div class="space-y-1">
                                            <p class="text-neutral-800 font-secondary font-medium">
                                                {{ $dispute->disputedBy->name ?? 'N/A' }}
                                            </p>
                                            <p class="text-sm text-neutral-600 font-secondary">
                                                {{ $dispute->disputedBy->email ?? 'N/A' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right Column -->
                                <div class="space-y-4">
                                    <!-- Dispute Details -->
                                    <div class="bg-neutral-50/80 rounded-lg p-4 border border-neutral-200/50">
                                        <div class="flex items-center space-x-1 mb-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-neutral-500"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <span
                                                class="text-sm font-medium text-neutral-700 font-tertiary">Details</span>
                                        </div>
                                        <p
                                            class="text-neutral-800 font-secondary leading-relaxed line-clamp-1 text-ellipsis">
                                            {{ $dispute->dispute_details }}
                                        </p>
                                    </div>

                                    <!-- Assigned Admin -->
                                    <div class="bg-neutral-50/80 rounded-lg p-4 border border-neutral-200/50">
                                        <div class="flex items-center space-x-1 mb-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-neutral-500"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.031 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                            </svg>
                                            <span class="text-sm font-medium text-neutral-700 font-tertiary">Assigned
                                                Admin</span>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            @if ($dispute->assignedAdmin)
                                                {{-- <div
                                                    class="w-10 h-10 bg-gradient-to-br from-primary to-primary/80 rounded-full flex items-center justify-center">
                                                    <span class="text-white text-xs font-bold font-main">
                                                        {{ substr($dispute->assignedAdmin->name ?? $dispute->assignedAdmin->email, 0, 1) }}
                                                    </span>
                                                </div> --}}
                                                <span class="text-neutral-800 font-secondary font-medium">
                                                    {{ $dispute->assignedAdmin->email }}
                                                </span>
                                            @else
                                                <div class="flex items-center space-x-2 text-neutral-500">
                                                    <div
                                                        class="w-8 h-8 bg-neutral-200 rounded-full flex items-center justify-center">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                            stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                                        </svg>
                                                    </div>
                                                    <span class="font-secondary">Unassigned</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex items-center justify-between pt-4 border-t border-neutral-200/50">
                                <div class="flex items-center space-x-3">
                                    {{-- Only show button if assigned and current admin is the assignee --}}
                                    @if ($dispute->admin_assigned && auth()->id() === $dispute->admin_assigned)
                                        <a href="{{ route('engagements.show-disputed', $dispute->cancellation->engagement_id) }}"
                                            class="group inline-flex items-center px-5 py-2.5 bg-secondary text-white text-sm font-medium font-main rounded-lg shadow-sm hover:bg-secondary/90 transition-all duration-200 hover:shadow-md transform hover:-translate-y-0.5 focus:ring-2 focus:ring-secondary/20 focus:outline-none">
                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                class="h-4 w-4 mr-2 group-hover:scale-110 transition-transform duration-200"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            View Dispute Details
                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                class="h-4 w-4 ml-1 group-hover:translate-x-0.5 transition-transform duration-200"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M9 5l7 7-7 7" />
                                            </svg>
                                        </a>
                                    @endif

                                    @if (!$dispute->assignedAdmin)
                                        <form action="{{ route('admin.disputes.assign', $dispute->id) }}"
                                            method="POST">
                                            @csrf
                                            <button type="submit"
                                                class="group inline-flex items-center px-5 py-2.5 bg-primary text-white text-sm font-medium font-main rounded-lg shadow-sm hover:bg-primary/90 transition-all duration-200 hover:shadow-md transform hover:-translate-y-0.5 focus:ring-2 focus:ring-primary/20 focus:outline-none">
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="h-4 w-4 mr-2 group-hover:scale-110 transition-transform duration-200"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                    stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                                </svg>
                                                Assign to Me
                                            </button>
                                        </form>
                                    @endif
                                </div>

                                <!-- Quick Info -->
                                <div class="flex items-center space-x-4 text-xs text-neutral-500 font-secondary">
                                    <span class="flex items-center space-x-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-secondary"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>ID: {{ $dispute->id }}</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <!-- Empty State -->
                    <div
                        class="bg-white/90 backdrop-blur-sm rounded-xl shadow-lg border border-neutral-200/50 p-12 text-center">
                        <div class="max-w-md mx-auto">
                            <div
                                class="bg-gradient-to-br from-neutral-100 to-neutral-200 rounded-full p-6 w-24 h-24 mx-auto mb-6 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-neutral-400"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-neutral-800 font-main mb-2">No Disputed Engagements</h3>
                            <p class="text-neutral-600 font-secondary">
                                @if ($status === 'all')
                                    All engagements are running smoothly. There are
                                    no disputes requiring your attention at this time.
                                @else
                                    No {{ str_replace('_', ' ', $status) }} disputes found.
                                @endif
                            </p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Pagination -->
        <div class="mt-8">
            {{ $disputes->links() }}
        </div>
    </div>
</x-app-layout>
