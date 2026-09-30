<!-- Deliverables -->
<x-card rounded="2xl" shadow="lg" border="neutral-100" clip>
    <!-- Header Section -->
    <div class="bg-gradient-to-r from-primary to-primary/90 px-8 py-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center mr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                        stroke="currentColor" class="w-6 h-6 text-white">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                    </svg>

                </div>
                <h2 class="text-xl font-bold text-white font-main">Deliverables</h2>
            </div>
            <div class="flex items-center space-x-2">
                <span class="bg-white/20 backdrop-blur-sm px-3 py-1 rounded-full text-white text-sm font-medium">
                    {{ $engagement->deliverables->count() }}
                    {{ $engagement->deliverables->count() === 1 ? 'Deliverable' : 'Deliverables' }}
                </span>
            </div>
        </div>
    </div>

    <!-- Content Section -->
    <div class="p-6">
        @if ($engagement->deliverables->count() > 0)
            <div class="space-y-4">
                @foreach ($engagement->deliverables as $deliverable)
                    <x-card shadow="none" class="group relative p-5 hover:shadow-md hover:border-secondary/30 transition-all duration-300">
                        <!-- Status Indicator Line -->
                        <div
                            class="absolute left-0 top-0 bottom-0 w-1 rounded-l-xl
                            @if ($deliverable->status === 'approved') bg-gradient-to-b from-green-400 to-green-600
                            @elseif($deliverable->status === 'rejected') bg-gradient-to-b from-red-400 to-red-600
                            @else bg-gradient-to-b from-accent to-amber-600 @endif
                        ">
                        </div>

                        <div class="flex items-start justify-between ml-4">
                            <div class="flex-1 min-w-0">
                                <!-- Title and Icon -->
                                <div class="flex items-center space-x-3 mb-3">
                                    <div
                                        class="p-2 rounded-lg
                                        @if ($deliverable->status === 'approved') bg-green-50
                                        @elseif($deliverable->status === 'rejected') bg-red-50
                                        @else bg-amber-50 @endif
                                    ">
                                        @if ($deliverable->status === 'approved')
                                            <x-icon name="check-circle" class="w-4 h-4 text-green-600" />
                                        @elseif($deliverable->status === 'rejected')
                                            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z">
                                                </path>
                                            </svg>
                                        @else
                                            <x-icon name="clock" class="w-4 h-4 text-amber-600" />
                                        @endif
                                    </div>
                                    <h4
                                        class="text-base font-semibold text-neutral-900 font-main group-hover:text-primary transition-colors">
                                        {{ $deliverable->title }}
                                    </h4>
                                </div>

                                <!-- Description -->
                                @if ($deliverable->description)
                                    <p
                                        class="text-sm text-justify text-neutral-600 mb-4 leading-relaxed font-secondary ml-11">
                                        {{ $deliverable->description }}
                                    </p>
                                @endif

                                <!-- Timestamps -->
                                <div
                                    class="flex flex-wrap items-center gap-4 text-xs text-neutral-500 ml-11 font-secondary">
                                    <!-- Submitted Date -->
                                    <div class="flex items-center space-x-1.5">
                                        <x-icon name="cloud-arrow-up" class="w-3.5 h-3.5 text-neutral-400" />
                                        <span class="font-medium">Submitted:</span>
                                        <span><x-date :date="$deliverable->created_at" format="M j, Y g:i A" /></span>
                                    </div>

                                    <!-- Status Date -->
                                    @if ($deliverable->status && in_array($deliverable->status, ['approved', 'rejected']))
                                        <div class="flex items-center space-x-1.5">
                                            @if ($deliverable->status === 'approved')
                                                <x-icon name="check" class="w-3.5 h-3.5 text-green-500" />
                                                <span class="font-medium text-green-700">Approved:</span>
                                                <span
                                                    class="text-green-600">{{ $deliverable->approved_at ? $deliverable->approved_at->format('M j, Y g:i A') : 'N/A' }}</span>
                                            @else
                                                <x-icon name="x-mark" class="w-3.5 h-3.5 text-red-500" />
                                                <span class="font-medium text-red-700">Rejected:</span>
                                                <span
                                                    class="text-red-600">{{ $deliverable->rejected_at ? $deliverable->rejected_at->format('M j, Y g:i A') : 'N/A' }}</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Status Badge -->
                            @if ($deliverable->status)
                                <div class="flex-shrink-0 ml-4">
                                    <span
                                        class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-full shadow-sm font-secondary
                                        @if ($deliverable->status === 'approved') bg-gradient-to-r from-green-100 to-green-50 text-green-800 border border-green-200
                                        @elseif($deliverable->status === 'rejected')
                                            bg-gradient-to-r from-red-100 to-red-50 text-red-800 border border-red-200
                                        @else
                                            bg-gradient-to-r from-amber-100 to-amber-50 text-amber-800 border border-amber-200 @endif
                                    ">
                                        @if ($deliverable->status === 'approved')
                                            <x-icon name="check-solid" class="w-3 h-3 mr-1" />
                                        @elseif($deliverable->status === 'rejected')
                                            <x-icon name="x-mark-solid" class="w-3 h-3 mr-1" />
                                        @else
                                            <x-icon name="clock-solid" class="w-3 h-3 mr-1" />
                                        @endif
                                        {{ ucfirst($deliverable->status) }}
                                    </span>
                                </div>
                            @endif
                        </div>

                        <!-- Hover Effect Overlay -->
                        <div
                            class="absolute inset-0 bg-gradient-to-r from-secondary/5 to-primary/5 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl pointer-events-none">
                        </div>
                    </x-card>
                @endforeach
            </div>
        @else
            <!-- Empty State -->
            <div class="text-center py-12">
                <div class="mx-auto w-16 h-16 bg-neutral-100 rounded-full flex items-center justify-center mb-4">
                    <x-icon name="document-text" class="w-8 h-8 text-neutral-400" stroke-width="1.5" />
                </div>
                <h3 class="text-sm font-semibold text-neutral-900 mb-2 font-main">No deliverables yet</h3>
                <p class="text-sm text-neutral-500 font-secondary">Deliverables will appear here once they are
                    submitted.</p>
            </div>
        @endif
    </div>
</x-card>
