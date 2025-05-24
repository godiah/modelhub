<x-app-layout>
    <x-slot name="header">
        @if (Auth::user()->hasRole('admin'))
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-tertiary font-bold text-xl text-primary leading-tight">
                        {{ $engagement->job->title }}
                    </h2>
                </div>
                <div class="flex space-x-3">
                    <a href="{{ route('admin.disputes.index') }}"
                        class="inline-flex items-center px-4 py-2 bg-secondary text-white rounded-lg hover:bg-secondary/90 transition-colors duration-200 font-main text-sm font-medium shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="h-5 w-5 mr-2">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                        </svg>
                        Disputed Engagements
                    </a>
                </div>
            </div>
        @else
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-tertiary font-bold text-xl text-primary leading-tight">
                        {{ $engagement->job->title }}
                    </h2>
                </div>
                <div class="flex space-x-3">
                    <a href="{{ route('engagements.index') }}"
                        class="inline-flex items-center px-4 py-2 bg-secondary text-white rounded-lg hover:bg-secondary/90 transition-colors duration-200 font-main text-sm font-medium shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="h-5 w-5 mr-2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
                        </svg>
                        My Engagements
                    </a>
                </div>
            </div>
        @endif
    </x-slot>

    <div class="container mx-auto max-w-7xl px-4 py-8 pb-24 font-main text-neutral-800">
        <!--  Status Banner -->
        <div class="mb-8 relative overflow-hidden">
            <!-- Main Banner Content -->
            <div class="relative backdrop-blur-sm rounded-xl shadow-lg border border-neutral-200/50 overflow-hidden">
                <!-- Colored Top Border -->
                @if ($dispute->isResolved())
                    <div class="h-1.5 bg-gradient-to-r from-green-200 to-green-600 w-full"></div>
                @else
                    <div class="h-1.5 bg-gradient-to-r from-rose-200 to-rose-600 w-full"></div>
                @endif

                <!-- Banner Content -->
                <div class="bg-white p-5 flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            @php
                                $status = $dispute->status ?? 'pending';
                                $iconColor = $status === 'resolved' ? 'bg-green-300' : 'bg-rose-300';
                                $iconBg = $status === 'resolved' ? 'bg-green-50' : 'bg-rose-50';
                                $iconText = $status === 'resolved' ? 'text-green-500' : 'text-rose-500';
                                $pulseClass = $status === 'resolved' ? '' : 'animate-pulse';
                            @endphp

                            <div
                                class="absolute -inset-1 {{ $iconColor }} rounded-full {{ $pulseClass }} opacity-75">
                            </div>
                            <div class="relative {{ $iconBg }} {{ $iconText }} rounded-full p-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-10 h-10">
                                    @if ($dispute->isResolved())
                                        <!-- Clipboard with checkmark icon -->
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0 1 18 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v9.375m-8.25-3 1.5 1.5 3-3.75" />
                                    @else
                                        <!-- Alert icon -->
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                    @endif
                                </svg>
                            </div>
                        </div>

                        <!-- Status Information -->
                        <div>
                            <div class="flex items-center space-x-2">
                                <h3 class="font-tertiary font-bold text-lg text-neutral-800">Engagement Disputed</h3>
                            </div>
                            <p class="text-sm text-neutral-700 mt-1 flex items-center">
                                <svg class="w-4 h-4 mr-1.5 text-neutral-500" viewBox="0 0 24 24" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M8 7V3M16 7V3M7 11H17M5 21H19C20.1046 21 21 20.1046 21 19V7C21 5.89543 20.1046 5 19 5H5C3.89543 5 3 5.89543 3 7V19C3 20.1046 3.89543 21 5 21Z"
                                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                                <span class="font-medium text-neutral-700">Disputed on</span> <span
                                    class="font-medium text-neutral-700 ml-1">
                                    {{ optional(optional($engagement->cancellation)->dispute)->created_at?->format('M d, Y h:i A') ?? '—' }}
                                </span>

                                @if ($dispute->isResolved())
                                    <span class="mx-2">-</span>
                                    <svg class="w-4 h-4 mr-1.5 text-green-700" viewBox="0 0 24 24" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M8 7V3M16 7V3M7 11H17M5 21H19C20.1046 21 21 20.1046 21 19V7C21 5.89543 20.1046 5 19 5H5C3.89543 5 3 5.89543 3 7V19C3 20.1046 3.89543 21 5 21Z"
                                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                    </svg>
                                    <span class="font-medium text-green-700">Resolved on</span> <span
                                        class="font-medium text-green-700 ml-1">
                                        {{ $dispute->resolved_at->format('M j, Y \a\t g:i A') }}
                                    </span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Progress Bar / Timeline -->
                @if ($engagement->status === 'disputed' || $engagement->status === 'settled')
                    <div class="bg-neutral-50 p-4 border-t border-neutral-200">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-medium text-neutral-500">Dispute Resolution Progress</span>
                            @if ($dispute->isResolved())
                                <span class="text-xs font-medium text-green-600">Dispute Resolved</span>
                            @else
                                <span class="text-xs font-medium text-neutral-700">Review in Progress</span>
                            @endif
                        </div>
                        <div class="relative w-full h-2 bg-neutral-200 rounded-full overflow-hidden">
                            @php
                                $status = $engagement->cancellation->dispute->status ?? 'pending';
                                $progressPercent =
                                    [
                                        'pending' => '5%',
                                        'under_review' => '50%',
                                        'resolved' => '100%',
                                    ][$status] ?? '0%';
                                $progressColor =
                                    [
                                        'pending' => 'bg-rose-400',
                                        'under_review' => 'bg-rose-400',
                                        'resolved' => 'bg-green-600',
                                    ][$status] ?? 'bg-rose-400';
                            @endphp

                            <div class="absolute top-0 left-0 h-full {{ $progressColor }} rounded-full"
                                style="width: {{ $progressPercent }}"></div>
                        </div>
                        <div class="flex justify-between mt-2 text-xs text-neutral-500">
                            <span>Pending</span>
                            <span>Under Review</span>
                            <span>Resolved</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        @if ($dispute)
            <!-- Main Dispute Information -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <!-- Left Column - Primary Details -->
                <div class="space-y-6">
                    <!-- Dispute Reason Card -->
                    <div class="bg-white/90 backdrop-blur-sm rounded-xl shadow-lg border border-neutral-200/50 p-6">
                        <div class="flex items-center space-x-2 mb-2">
                            <div class="bg-accent/10 rounded-lg p-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-accent" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h3 class="font-bold font-main text-neutral-800">Dispute Reason</h3>
                        </div>
                        <div class="bg-neutral-50 rounded-lg p-4 border border-neutral-200/50">
                            <p class="text-neutral-700 font-secondary text-sm">
                                {{ $dispute->formatted_reason }}
                            </p>
                        </div>
                    </div>

                    <!-- Dispute Details Card -->
                    <div class="bg-white/90 backdrop-blur-sm rounded-xl shadow-lg border border-neutral-200/50 p-6">
                        <div class="flex items-center space-x-2 mb-2">
                            <div class="bg-primary/10 rounded-lg p-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <h3 class="font-bold font-main text-neutral-800">Detailed Description</h3>
                        </div>
                        <div class="bg-neutral-50 rounded-lg p-4 border border-neutral-200/50">
                            <p class="text-neutral-700 font-secondary leading-relaxed text-sm">
                                {{ $dispute->dispute_details }}
                            </p>
                        </div>
                    </div>

                    <!-- Supporting Evidence Card -->
                    @if ($dispute->supporting_evidence)
                        <div
                            class="bg-white/90 backdrop-blur-sm rounded-xl shadow-lg border border-neutral-200/50 p-6">
                            <div class="flex items-center space-x-2 mb-2">
                                <div class="bg-secondary/10 rounded-lg p-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-secondary"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                    </svg>
                                </div>
                                <h3 class="font-bold font-main text-neutral-800">Supporting Evidence</h3>
                            </div>
                            <div class="space-y-3">
                                @foreach ($dispute->supporting_evidence as $evidence)
                                    <div
                                        class="bg-neutral-50 rounded-lg p-4 border border-neutral-200/50 hover:bg-neutral-100 transition-colors">
                                        <a href="{{ asset('storage/' . $evidence) }}" target="_blank"
                                            class="flex items-center space-x-3 text-secondary hover:text-secondary/80 transition-colors group">
                                            <div
                                                class="bg-secondary/10 rounded-lg p-2 group-hover:bg-secondary/20 transition-colors">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                    stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                </svg>
                                            </div>
                                            <div>
                                                <span class="font-medium font-secondary text-sm">Evidence
                                                    {{ $loop->iteration }}</span>
                                                <p class="text-xs text-neutral-500 font-secondary">Click to view in new
                                                    tab</p>
                                            </div>
                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                class="h-4 w-4 ml-auto group-hover:translate-x-1 transition-transform"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M9 5l7 7-7 7" />
                                            </svg>
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Right Column - Meta Information -->
                <div class="space-y-6">
                    <!-- Filed By Card -->
                    <div class="bg-white/90 backdrop-blur-sm rounded-xl shadow-lg border border-neutral-200/50 p-6">
                        <div class="flex items-center space-x-2 mb-2">
                            <div class="bg-tertiary/10 rounded-lg p-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-tertiary" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <h3 class="font-bold font-main text-neutral-800">Filed By</h3>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div
                                class="w-12 h-12 bg-gradient-to-br from-primary to-primary/80 rounded-full flex items-center justify-center">
                                <span class="text-white text-lg font-bold font-main">
                                    {{ substr($dispute->disputedBy->name ?? 'N/A', 0, 1) }}
                                </span>
                            </div>
                            <div>
                                <p class="font-semibold font-secondary text-neutral-800 text-sm">
                                    {{ $dispute->disputedBy->name ?? 'N/A' }}
                                </p>
                                <p class="text-xs text-neutral-600 font-secondary">
                                    {{ $dispute->disputedBy->email ?? 'N/A' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Current Status Card -->
                    <div class="bg-white/90 backdrop-blur-sm rounded-xl shadow-lg border border-neutral-200/50 p-6">
                        <div class="flex items-center space-x-2 mb-2">
                            <div class="bg-accent/10 rounded-lg p-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-accent" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <h3 class="font-bold font-main text-neutral-800">Current Status</h3>
                        </div>
                        <div class="bg-neutral-50 rounded-lg p-4 border border-neutral-200/50">
                            <p class="text-sm font-semibold font-secondary text-neutral-800 mb-2">
                                {{ ucfirst(str_replace('_', ' ', $dispute->status)) }}
                            </p>
                            @if ($dispute->isResolved())
                                <div class="flex items-center space-x-2 text-green-600 mb-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="text-xs font-medium font-secondary">Dispute has been resolved</span>
                                </div>
                                @if ($dispute->resolution_amount)
                                    <div class="bg-white/60 rounded-lg p-4 border border-green-200/40">
                                        <h4 class="text-sm font-semibold text-neutral-700 font-main mb-2">
                                            Final Resolution Amount
                                        </h4>
                                        <div class="flex items-center space-x-2">
                                            <span class="text-sm text-neutral-600 font-secondary">Ksh</span>
                                            <span class="text-xl font-bold text-green-800 font-main">
                                                {{ number_format($dispute->resolution_amount, 2) }}
                                            </span>
                                        </div>
                                    </div>
                                @else
                                    <div class="bg-white/60 rounded-lg p-4 border border-green-200/40">
                                        <h4 class="text-sm font-semibold text-neutral-700 font-main mb-2">
                                            Agreed Payable Amount
                                        </h4>
                                        <div class="flex items-center space-x-2">
                                            <span class="text-sm text-neutral-600 font-secondary">Ksh</span>
                                            <span class="text-xl font-bold text-green-800 font-main">
                                                {{ number_format($engagement->net_amount, 2) }}
                                            </span>
                                        </div>
                                    </div>
                                @endif
                            @else
                                <div class="flex items-center space-x-2 text-accent">
                                    <div class="w-2 h-2 bg-accent rounded-full animate-pulse"></div>
                                    <span class="text-xs font-medium font-secondary">Resolution in progress</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Resolution Notes Card -->
                    <div class="bg-white/90 backdrop-blur-sm rounded-xl shadow-lg border border-neutral-200/50 p-6">
                        <div class="flex items-center space-x-2 mb-2">
                            <div class="bg-secondary/10 rounded-lg p-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-secondary" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </div>
                            <h3 class="font-bold font-main text-neutral-800">Resolution Notes</h3>
                        </div>
                        <div class="bg-neutral-50 rounded-lg p-4 border border-neutral-200/50">
                            @if ($dispute->resolution_notes)
                                <p class="text-neutral-700 font-secondary leading-relaxed text-sm">
                                    {{ $dispute->resolution_notes }}
                                </p>
                            @else
                                <div class="flex items-center space-x-2 text-neutral-500 text-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="font-secondary italic">Awaiting resolution</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Partial Payment Information -->
        @if ($partialPayment)
            <div class="mb-8">
                <div class="bg-white/90 backdrop-blur-sm rounded-xl shadow-lg border border-neutral-200/50 p-6">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="bg-green-100 rounded-lg p-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold font-main text-neutral-800">Partial Payment Information</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-neutral-50 rounded-lg p-4 border border-neutral-200/50">
                            <div class="flex items-center space-x-2 mb-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-neutral-500"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                                </svg>
                                <span class="text-sm font-medium text-neutral-600 font-tertiary">Amount</span>
                            </div>
                            <p class="text-xl font-bold text-green-600 font-main">
                                Ksh{{ number_format($partialPayment->amount, 2) }}
                            </p>
                        </div>

                        <div class="bg-neutral-50 rounded-lg p-4 border border-neutral-200/50">
                            <div class="flex items-center space-x-2 mb-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-neutral-500"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                                <span class="text-sm font-medium text-neutral-600 font-tertiary">Status</span>
                            </div>
                            <p class="text-lg font-semibold text-neutral-800 font-secondary">
                                {{ ucfirst($partialPayment->status) }}
                            </p>
                        </div>

                        <div class="bg-neutral-50 rounded-lg p-4 border border-neutral-200/50">
                            <div class="flex items-center space-x-2 mb-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-neutral-500"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                <span class="text-sm font-medium text-neutral-600 font-tertiary">Processed By</span>
                            </div>
                            <div>
                                <p class="font-semibold text-neutral-800 font-secondary">
                                    {{ $partialPayment->processor->name ?? 'N/A' }}
                                </p>
                                <p class="text-sm text-neutral-600 font-secondary">
                                    {{ $partialPayment->processor->email ?? 'N/A' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Admin Resolution Section -->
        @can('resolve disputes')
            <div
                class="bg-gradient-to-br from-primary/5 to-secondary/5 rounded-xl border-2 border-dashed border-primary/20 p-6">
                <div class="flex items-center space-x-3 mb-6">
                    <div class="bg-primary/10 rounded-lg p-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-primary" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.031 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold font-main text-neutral-800">Admin Resolution Panel</h3>
                    <span
                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-primary/10 text-primary border border-primary/20 font-tertiary">
                        Admin Only
                    </span>
                </div>

                @if (!$dispute->isResolved())
                    <form action="{{ route('admin.disputes.resolve', $dispute->id) }}" method="POST" class="space-y-6">
                        @csrf

                        <!-- Resolution Notes -->
                        <div>
                            <label for="resolution_notes"
                                class="block text-sm font-semibold text-neutral-700 font-main mb-2">
                                Resolution Notes <span class="text-red-500">*</span>
                            </label>
                            <textarea id="resolution_notes" name="resolution_notes" rows="4" required
                                class="w-full px-4 py-3 border-2 border-neutral-300 rounded-xl shadow-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all duration-200 font-secondary placeholder-neutral-400"
                                placeholder="Provide detailed resolution notes explaining the decision and any actions taken...">{{ old('resolution_notes') }}</textarea>
                        </div>

                        <!-- Resolution Amount -->
                        <div>
                            <label for="resolution_amount"
                                class="block text-sm font-semibold text-neutral-700 font-main mb-2">
                                Final Resolution Amount (Optional)
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-neutral-500 font-secondary">Ksh</span>
                                </div>
                                <input type="number" step="0.01" name="resolution_amount" id="resolution_amount"
                                    class="w-full pl-12 pr-4 py-3 border-2 border-neutral-300 rounded-xl shadow-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all duration-200 font-secondary placeholder-neutral-400"
                                    value="{{ old('resolution_amount') }}" placeholder="0.00">
                            </div>
                            <p class="mt-2 text-sm text-neutral-600 font-secondary">
                                Leave blank if no monetary resolution is required
                            </p>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex items-center justify-end space-x-4 pt-1 border-t border-neutral-200/50">
                            <button type="submit"
                                class="group inline-flex items-center px-6 py-3 bg-green-600 text-white text-sm font-bold font-main rounded-xl shadow-lg hover:bg-green-700 hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200 focus:ring-2 focus:ring-green-500/20 focus:outline-none">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 mr-2 group-hover:scale-110 transition-transform duration-200"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Finalize Resolution
                            </button>
                        </div>
                    </form>
                @else
                    <!-- Resolution Status Card -->
                    <div
                        class="bg-gradient-to-br from-green-50 to-emerald-50 border-2 border-green-200/60 rounded-xl p-6 shadow-sm">
                        <!-- Header with Status Icon -->
                        <div class="flex items-start space-x-4 mb-4">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-green-600 rounded-xl flex items-center justify-center shadow-md">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-lg font-bold text-green-800 font-main mb-1">
                                    Dispute Successfully Resolved
                                </h3>
                                <p class="text-sm text-green-700 font-secondary">
                                    This dispute has been finalized and closed
                                </p>
                            </div>
                        </div>

                        <!-- Resolution Details -->
                        <div class="space-y-4">
                            <!-- Resolution Notes -->
                            <div class="bg-white/60 rounded-lg p-4 border border-green-200/40">
                                <h4 class="text-sm font-semibold text-neutral-700 font-main mb-2">
                                    Resolution Notes
                                </h4>
                                <p class="text-neutral-800 font-secondary leading-relaxed">
                                    {{ $dispute->resolution_notes }}
                                </p>
                            </div>

                            <!-- Final Amount (if exists) -->
                            @if ($dispute->resolution_amount)
                                <div class="bg-white/60 rounded-lg p-4 border border-green-200/40">
                                    <h4 class="text-sm font-semibold text-neutral-700 font-main mb-2">
                                        Final Resolution Amount
                                    </h4>
                                    <div class="flex items-center space-x-2">
                                        <span class="text-sm text-neutral-600 font-secondary">Ksh</span>
                                        <span class="text-xl font-bold text-green-800 font-main">
                                            {{ number_format($dispute->resolution_amount, 2) }}
                                        </span>
                                    </div>
                                </div>
                            @else
                                <div class="bg-white/60 rounded-lg p-4 border border-green-200/40">
                                    <h4 class="text-sm font-semibold text-neutral-700 font-main mb-2">
                                        Agreed Payable Amount
                                    </h4>
                                    <div class="flex items-center space-x-2">
                                        <span class="text-sm text-neutral-600 font-secondary">Ksh</span>
                                        <span class="text-xl font-bold text-green-800 font-main">
                                            {{ number_format($engagement->net_amount, 2) }}
                                        </span>
                                    </div>
                                </div>
                            @endif

                            <!-- Resolution Timestamp -->
                            @if (isset($dispute->resolved_at))
                                <div class="flex items-center justify-between pt-3 border-t border-green-200/50">
                                    <span class="text-sm text-green-700 font-secondary">
                                        Resolved on {{ $dispute->resolved_at->format('M j, Y \a\t g:i A') }}
                                    </span>
                                    @if (isset($dispute->resolved_by))
                                        <span class="text-sm text-green-600 font-secondary">
                                            by {{ $dispute->resolvedBy->email }}
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        @endcan
    </div>

</x-app-layout>
