<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-tertiary font-bold text-2xl text-primary leading-tight flex items-center">
                {{ $engagement->job->title }}
            </h2>
            <a href="{{ route('engagements.archived') }}"
                class="inline-flex items-center px-4 py-2 bg-secondary text-white rounded-lg hover:bg-secondary/90 transition-colors duration-200 font-main text-sm font-medium shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="h-5 w-5 mr-2">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                </svg>
                Back to Archived Engagements
            </a>
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
                    <div class="bg-white rounded-lg shadow border">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900">
                                Deliverables
                                <span
                                    class="text-sm font-normal text-gray-500">({{ $engagement->deliverables->count() }})</span>
                            </h2>
                        </div>
                        <div class="px-6 py-4">
                            @if ($engagement->deliverables->count() > 0)
                                <div class="space-y-3">
                                    @foreach ($engagement->deliverables as $deliverable)
                                        <div class="border border-gray-200 rounded-lg p-4">
                                            <div class="flex items-start justify-between">
                                                <div class="flex-1">
                                                    <h4 class="text-sm font-medium text-gray-900">
                                                        {{ $deliverable->title }}</h4>
                                                    @if ($deliverable->description)
                                                        <p class="text-sm text-gray-600 mt-1">
                                                            {{ $deliverable->description }}</p>
                                                    @endif
                                                    <div class="flex items-center mt-2 text-xs text-gray-500">
                                                        <span>Submitted:
                                                            {{ $deliverable->created_at->format('M j, Y g:i A') }}</span>
                                                    </div>
                                                </div>
                                                @if ($deliverable->status)
                                                    <span
                                                        class="ml-3 px-2 py-1 text-xs font-medium rounded-full
                                                        @if ($deliverable->status === 'approved') bg-green-100 text-green-800
                                                        @elseif($deliverable->status === 'rejected')
                                                            bg-red-100 text-red-800
                                                        @else
                                                            bg-yellow-100 text-yellow-800 @endif
                                                    ">
                                                        {{ ucfirst($deliverable->status) }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-gray-500 text-sm">No deliverables submitted yet.</p>
                            @endif
                        </div>
                    </div>

                    <!-- Cancellation Details (if cancelled) -->
                    @if ($engagement->cancelled_at && $engagement->cancellation)
                        <div class="bg-red-50 rounded-lg shadow border border-red-200">
                            <div class="px-6 py-4 border-b border-red-200">
                                <h2 class="text-lg font-semibold text-red-900">Cancellation Details</h2>
                            </div>
                            <div class="px-6 py-4">
                                <div class="space-y-4">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <span class="text-sm font-medium text-red-700">Initiated By</span>
                                            <p class="text-sm text-red-900">
                                                {{ $engagement->cancellation->initiator->name }}</p>
                                        </div>
                                        <div>
                                            <span class="text-sm font-medium text-red-700">Type</span>
                                            <p class="text-sm text-red-900">
                                                {{ ucfirst(str_replace('_', ' ', $engagement->cancellation->cancellation_type)) }}
                                            </p>
                                        </div>
                                        <div>
                                            <span class="text-sm font-medium text-red-700">Category</span>
                                            <p class="text-sm text-red-900">
                                                {{ ucfirst(str_replace('_', ' ', $engagement->cancellation->reason_category)) }}
                                            </p>
                                        </div>
                                        @if ($engagement->cancellation->partial_payment_amount)
                                            <div>
                                                <span class="text-sm font-medium text-red-700">Partial Payment</span>
                                                <p class="text-sm text-red-900">
                                                    ${{ number_format($engagement->cancellation->partial_payment_amount, 2) }}
                                                </p>
                                            </div>
                                        @endif
                                    </div>

                                    @if ($engagement->cancellation->reason_details)
                                        <div>
                                            <span class="text-sm font-medium text-red-700">Details</span>
                                            <p class="text-sm text-red-900 mt-1">
                                                {{ $engagement->cancellation->reason_details }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Dispute Details (if disputed) -->
                    @if ($engagement->cancellation && $engagement->cancellation->dispute)
                        <div class="bg-orange-50 rounded-lg shadow border border-orange-200">
                            <div class="px-6 py-4 border-b border-orange-200">
                                <h2 class="text-lg font-semibold text-orange-900">Dispute Details</h2>
                            </div>
                            <div class="px-6 py-4">
                                @php $dispute = $engagement->cancellation->dispute; @endphp
                                <div class="space-y-4">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <span class="text-sm font-medium text-orange-700">Disputed By</span>
                                            <p class="text-sm text-orange-900">{{ $dispute->disputedBy->name }}</p>
                                        </div>
                                        <div>
                                            <span class="text-sm font-medium text-orange-700">Status</span>
                                            <p class="text-sm text-orange-900">
                                                {{ ucfirst(str_replace('_', ' ', $dispute->status)) }}</p>
                                        </div>
                                        <div>
                                            <span class="text-sm font-medium text-orange-700">Reason</span>
                                            <p class="text-sm text-orange-900">{{ $dispute->formatted_reason }}</p>
                                        </div>
                                        @if ($dispute->resolution_amount)
                                            <div>
                                                <span class="text-sm font-medium text-orange-700">Resolution
                                                    Amount</span>
                                                <p class="text-sm text-orange-900">
                                                    ${{ number_format($dispute->resolution_amount, 2) }}</p>
                                            </div>
                                        @endif
                                    </div>

                                    @if ($dispute->dispute_details)
                                        <div>
                                            <span class="text-sm font-medium text-orange-700">Dispute Details</span>
                                            <p class="text-sm text-orange-900 mt-1">{{ $dispute->dispute_details }}
                                            </p>
                                        </div>
                                    @endif

                                    @if ($dispute->resolution_notes)
                                        <div>
                                            <span class="text-sm font-medium text-orange-700">Resolution Notes</span>
                                            <p class="text-sm text-orange-900 mt-1">{{ $dispute->resolution_notes }}
                                            </p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Partial Payments -->
                    @if ($engagement->partialPayments->count() > 0)
                        <div class="bg-white rounded-lg shadow border">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h2 class="text-lg font-semibold text-gray-900">
                                    Partial Payments
                                    <span
                                        class="text-sm font-normal text-gray-500">({{ $engagement->partialPayments->count() }})</span>
                                </h2>
                            </div>
                            <div class="px-6 py-4">
                                <div class="space-y-3">
                                    @foreach ($engagement->partialPayments as $payment)
                                        <div class="border border-gray-200 rounded-lg p-4">
                                            <div class="flex items-start justify-between">
                                                <div class="flex-1">
                                                    <div class="flex items-center">
                                                        <span
                                                            class="text-lg font-medium text-gray-900">${{ number_format($payment->amount, 2) }}</span>
                                                        @if ($payment->final_amount && $payment->final_amount != $payment->amount)
                                                            <span class="ml-2 text-sm text-gray-500">(Final:
                                                                ${{ number_format($payment->final_amount, 2) }})</span>
                                                        @endif
                                                    </div>
                                                    @if ($payment->notes)
                                                        <p class="text-sm text-gray-600 mt-1">{{ $payment->notes }}
                                                        </p>
                                                    @endif
                                                    <div class="flex items-center mt-2 text-xs text-gray-500 space-x-4">
                                                        <span>Processed:
                                                            {{ $payment->processed_at->format('M j, Y g:i A') }}</span>
                                                        @if ($payment->accepted_at)
                                                            <span>Accepted:
                                                                {{ $payment->accepted_at->format('M j, Y g:i A') }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                                <span
                                                    class="ml-3 px-2 py-1 text-xs font-medium rounded-full
                                                    @switch($payment->status)
                                                        @case('pending')
                                                            bg-yellow-100 text-yellow-800
                                                            @break
                                                        @case('accepted')
                                                            bg-green-100 text-green-800
                                                            @break
                                                        @case('disputed')
                                                            bg-red-100 text-red-800
                                                            @break
                                                        @case('finalized')
                                                            bg-blue-100 text-blue-800
                                                            @break
                                                        @default
                                                            bg-gray-100 text-gray-800
                                                    @endswitch
                                                ">
                                                    {{ ucfirst($payment->status) }}
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Poster Information -->
                    <div class="bg-white rounded-lg shadow border">
                        <div class="px-4 py-3 border-b border-gray-200">
                            <h3 class="text-sm font-semibold text-gray-900">Job Poster</h3>
                        </div>
                        <div class="px-4 py-3">
                            <div class="flex items-center">
                                @if ($engagement->application->poster->avatar)
                                    <img class="h-10 w-10 rounded-full"
                                        src="{{ $engagement->application->poster->avatar }}" alt="">
                                @else
                                    <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                        <span
                                            class="text-sm font-medium text-gray-700">{{ substr($engagement->application->poster->name, 0, 1) }}</span>
                                    </div>
                                @endif
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ $engagement->application->poster->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $engagement->application->poster->email }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Applicant Information -->
                    <div class="bg-white rounded-lg shadow border">
                        <div class="px-4 py-3 border-b border-gray-200">
                            <h3 class="text-sm font-semibold text-gray-900">Freelancer</h3>
                        </div>
                        <div class="px-4 py-3">
                            <div class="flex items-center">
                                @if ($engagement->application->applicant->avatar)
                                    <img class="h-10 w-10 rounded-full"
                                        src="{{ $engagement->application->applicant->avatar }}" alt="">
                                @else
                                    <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                        <span
                                            class="text-sm font-medium text-gray-700">{{ substr($engagement->application->applicant->name, 0, 1) }}</span>
                                    </div>
                                @endif
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ $engagement->application->applicant->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $engagement->application->applicant->email }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Summary -->
                    <div class="bg-white rounded-lg shadow border">
                        <div class="px-4 py-3 border-b border-gray-200">
                            <h3 class="text-sm font-semibold text-gray-900">Financial Summary</h3>
                        </div>
                        <div class="px-4 py-3 space-y-3">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Agreed Amount</span>
                                <span
                                    class="text-sm font-medium text-gray-900">${{ number_format($engagement->agreed_amount, 2) }}</span>
                            </div>

                            @if ($engagement->service_fee)
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Service Fee</span>
                                    <span
                                        class="text-sm font-medium text-gray-900">${{ number_format($engagement->service_fee, 2) }}</span>
                                </div>
                            @endif

                            @if ($engagement->net_amount)
                                <div class="flex justify-between border-t border-gray-200 pt-3">
                                    <span class="text-sm font-medium text-gray-900">Net Amount</span>
                                    <span
                                        class="text-sm font-medium text-gray-900">${{ number_format($engagement->net_amount, 2) }}</span>
                                </div>
                            @endif

                            <!-- Payment Status -->
                            <div class="pt-3 border-t border-gray-200">
                                @if ($engagement->payment_escrowed_at)
                                    <div class="flex items-center text-xs text-green-600 mb-1">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        Payment Escrowed
                                    </div>
                                @endif

                                @if ($engagement->payment_released_at)
                                    <div class="flex items-center text-xs text-green-600">
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
                        <div class="bg-white rounded-lg shadow border">
                            <div class="px-4 py-3 border-b border-gray-200">
                                <h3 class="text-sm font-semibold text-gray-900">Notes</h3>
                            </div>
                            <div class="px-4 py-3">
                                <p class="text-sm text-gray-700">{{ $engagement->notes }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
