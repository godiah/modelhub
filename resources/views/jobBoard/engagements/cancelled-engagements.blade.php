<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-tertiary font-bold text-xl text-primary leading-tight">
                    {{ $engagement->job->title }}
                </h2>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('engagements.index') }}"
                    class="inline-flex items-center px-4 py-2 bg-secondary text-white rounded-lg hover:bg-secondary/90 transition-colors duration-200 font-main text-sm font-medium shadow-sm">
                    <x-icon name="chat-bubble-text" class="h-5 w-5 mr-2" stroke-width="1.5" />
                    My Engagements
                </a>
            </div>
        </div>
    </x-slot>

    <div class="container mx-auto max-w-7xl px-4 py-8 pb-24 font-main text-neutral-800" x-data="cancelledEngagement()">
        <!--  Status Banner -->
        <div class="mb-8 relative overflow-hidden">
            <!-- Background Pattern -->
            <div class="absolute inset-0 bg-gradient-to-r from-accent/5 to-accent/10 z-0">
                <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg" class="opacity-20">
                    <pattern id="diagonalPattern" width="10" height="10" patternUnits="userSpaceOnUse"
                        patternTransform="rotate(45)">
                        <line x1="0" y1="0" x2="0" y2="10" stroke="#F59E0B"
                            stroke-width="1" />
                    </pattern>
                    <rect width="100%" height="100%" fill="url(#diagonalPattern)" />
                </svg>
            </div>

            <!-- Main Banner Content -->
            <div class="relative z-10 rounded-2xl border border-accent/30 shadow-lg overflow-hidden">
                <!-- Colored Top Border -->
                <div class="h-1.5 bg-gradient-to-r from-accent to-accent/80 w-full"></div>

                <!-- Banner Content -->
                <div class="bg-white p-5 flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <!-- Alert Icon with Animated Pulse -->
                        <div class="relative">
                            <div class="absolute -inset-1 bg-accent/20 rounded-full animate-pulse opacity-75"></div>
                            <div class="relative bg-accent/10 text-accent rounded-full p-2">
                                <x-icon name="exclamation-triangle-3" class="w-10 h-10" />
                            </div>
                        </div>

                        <!-- Status Information -->
                        <div>
                            <div class="flex items-center space-x-2">
                                <h3 class="font-tertiary font-bold text-lg text-neutral-800">Engagement Cancelled</h3>
                                @if ($engagement->cancellation && $engagement->cancellation->cancellation_type === 'dispute')
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 border border-red-200">
                                        <span class="w-1.5 h-1.5 mr-1 bg-red-500 rounded-full animate-pulse"></span>
                                        In Dispute
                                    </span>
                                @else
                                    @php
                                        $cancellationTypes = [
                                            'mutual' => [
                                                'label' => 'Mutual Agreement',
                                                'class' => 'bg-blue-50 text-blue-700 border-blue-200',
                                            ],
                                            'client_initiated' => [
                                                'label' => 'Client Initiated',
                                                'class' => 'bg-purple-50 text-purple-700 border-purple-200',
                                            ],
                                            'freelancer_initiated' => [
                                                'label' => 'Freelancer Initiated',
                                                'class' => 'bg-green-50 text-green-700 border-green-200',
                                            ],
                                            'dispute' => [
                                                'label' => 'In Dispute',
                                                'class' => 'bg-red-50 text-red-700 border-red-200',
                                            ],
                                        ];
                                        $typeInfo = $cancellationTypes[
                                            $engagement->cancellation->cancellation_type
                                        ] ?? [
                                            'label' => ucfirst($engagement->cancellation->cancellation_type),
                                            'class' => 'bg-neutral-50 text-neutral-700 border-neutral-200',
                                        ];
                                    @endphp
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $typeInfo['class'] }} border">
                                        {{ $typeInfo['label'] }}
                                    </span>
                                @endif
                            </div>
                            <p class="text-sm text-neutral-600 mt-1 flex items-center">
                                <x-icon name="calendar-2" class="w-4 h-4 mr-1.5 text-neutral-500" />
                                Cancelled on <span
                                    class="font-medium text-neutral-700 ml-1">{{ $engagement->cancelled_at->format('M d, Y') }}</span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Progress Bar / Timeline (visible only for specific statuses) -->
                @if ($engagement->cancellation->cancellation_type === 'dispute')
                    <div class="bg-neutral-50 p-4 border-t border-neutral-200">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-medium text-neutral-500">Dispute Resolution Progress</span>
                            <span class="text-xs font-medium text-neutral-700">Review in Progress</span>
                        </div>
                        <div class="relative w-full h-2 bg-neutral-200 rounded-full overflow-hidden">
                            <div class="absolute top-0 left-0 h-full bg-accent rounded-full" style="width: 35%"></div>
                        </div>
                        <div class="flex justify-between mt-2 text-xs text-neutral-500">
                            <span>Submitted</span>
                            <span>Under Review</span>
                            <span>Decision</span>
                            <span>Closed</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Main Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column (2/3 width) -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Job Information Card -->
                @include('jobBoard.engagements.partials.cancelled.job-details')

                <!-- Project Deliverables Component -->
                @include('jobBoard.engagements.partials.cancelled.deliverables-details')
            </div>

            <!-- Right Column (1/3 width) -->
            <div class="space-y-6">
                <!--  Cancellation Details -->
                @include('jobBoard.engagements.partials.cancelled.cancellation-details')

                <!-- Payment Proccessing -->
                @include('jobBoard.engagements.partials.cancelled.payment-details')

                <!-- Deliverables Timeline -->
                @include('jobBoard.engagements.partials.cancelled.audit-details')
            </div>

        </div>
    </div>

    <!-- Dispute Warning Modal -->
    <div id="disputeWarningModal"
        class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-lg shadow-lg p-6 max-w-5xl w-full">
            <div class="mb-5 flex flex-row items-center justify-center text-center bg-red-100 rounded-full">
                <div class="p-3">
                    <x-icon name="exclamation-triangle-4" class="w-7 h-7 text-red-600" />
                </div>
                <h3 class="text-xl font-semibold text-gray-900 font-main">
                    Important: Before Disputing Payment
                </h3>
            </div>

            <div class="mb-6 text-sm font-main text-gray-600 space-y-3">
                <p>
                    <strong>Please read before proceeding:</strong>
                </p>
                <!-- Alert box about job being frozen -->
                <div class="bg-amber-50 border-l-4 border-amber-500 p-4 mb-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <x-icon name="exclamation-triangle-solid" class="h-5 w-5 text-amber-600" />
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-amber-800 font-medium">
                                <strong>Job Will Be Frozen</strong>: During the dispute resolution process, this job
                                will be completely
                                frozen. No further actions, payments, or deliverables can be processed until the dispute
                                is resolved.
                            </p>
                        </div>
                    </div>
                </div>
                <p>
                    Disputing a payment is a serious action that will involve platform administrators in the resolution
                    process. Please be aware of the following:
                </p>
                <ul class="list-disc pl-5 space-y-2">
                    <li>Once submitted, you cannot withdraw a dispute without admin approval.</li>
                    <li>You will need to provide a valid reason and detailed explanation for your dispute.</li>
                    <li>Supporting evidence may strengthen your case (screenshots, communications, delivered work).</li>
                    <li>Disputes are reviewed by administrators and can take 3-5 business days to resolve.</li>
                    <li>Filing frivolous disputes may affect your account standing on the platform.</li>
                    <li>While under review, the disputed amount will be held in escrow.</li>
                </ul>
                <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mt-2">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <x-icon name="information-circle-solid" class="h-5 w-5 text-blue-600" />
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                We strongly recommend attempting to resolve payment issues directly with the client
                                before
                                initiating a dispute.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-between font-main">
                <button type="button"
                    class="py-2 px-4 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium rounded-lg text-sm"
                    onclick="closeDisputeModal()">
                    Cancel
                </button>
                @if ($payment)
                    <a href="{{ route('engagements.dispute-form', $payment->id) }}"
                        class="py-2 px-4 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg text-sm">
                        Continue
                    </a>
                @else
                    <span class="text-sm text-gray-500 italic">
                        Dispute unavailable until payment is processed.
                    </span>
                @endif

            </div>
        </div>
    </div>

    <!-- JavaScript for modal functionality -->
    <script>
        function openDisputeModal() {
            document.getElementById('disputeWarningModal').classList.remove('hidden');
        }

        function closeDisputeModal() {
            document.getElementById('disputeWarningModal').classList.add('hidden');
        }
    </script>

</x-app-layout>
