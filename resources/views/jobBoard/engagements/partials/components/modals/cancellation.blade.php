<!-- Cancellation Modal -->
<div id="cancelEngagementModal-{{ $engagement->id }}"
    class="fixed inset-0 bg-neutral-900 bg-opacity-75 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-neutral-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-neutral-800 font-tertiary">Cancel Engagement
                </h3>
                <button
                    onclick="document.getElementById('cancelEngagementModal-{{ $engagement->id }}').classList.add('hidden')"
                    class="text-neutral-500 hover:text-neutral-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <div class="p-6">
            <div class="bg-amber-50 border-l-4 border-accent p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-accent" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="ml-3 font-main">
                        <p class="text-sm text-amber-800 font-medium">Important Information</p>
                        <ul class="mt-2 text-sm text-amber-700 list-disc list-inside">
                            <li>Cancellation may affect future opportunities on the platform</li>
                            <li>Partial work completed may still be eligible for payment</li>
                            <li>All submitted deliverables will remain accessible</li>
                            <li>In the event it is a dispute, a review will be undertaken promptly</li>
                        </ul>
                    </div>
                </div>
            </div>

            <form action="{{ route('engagements.cancel', $engagement->id) }}" method="POST">
                @csrf

                <div class="space-y-6">
                    <!-- Cancellation Type -->
                    <div>
                        <label for="cancellation_type"
                            class="block text-sm font-medium text-neutral-700 mb-1">Cancellation
                            Type</label>
                        <select id="cancellation_type" name="cancellation_type" required
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-sm border-neutral-300 focus:outline-none focus:ring-secondary focus:border-secondary rounded-md shadow-sm font-main">
                            <option value="mutual">Mutual Agreement</option>
                            <option value="client_initiated">Client Initiated</option>
                            <option value="freelancer_initiated">Freelancer Initiated</option>
                            <option value="dispute">Dispute (Requires Review)</option>
                        </select>
                    </div>

                    <!-- Reason Category -->
                    <div>
                        <label for="reason_category" class="block text-sm font-medium text-neutral-700 mb-1">Reason
                            Category</label>
                        <select id="reason_category" name="reason_category" required
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-sm border-neutral-300 focus:outline-none focus:ring-secondary focus:border-secondary rounded-md shadow-sm font-main">
                            <option value="schedule_conflict">Schedule Conflict</option>
                            <option value="project_scope_change">Project Scope Changed</option>
                            <option value="communication_issues">Communication Issues</option>
                            <option value="quality_concerns">Quality Concerns</option>
                            <option value="financial_reasons">Financial Reasons</option>
                            <option value="personal_reasons">Personal Reasons</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <!-- Detailed Reason -->
                    <div>
                        <label for="cancellation_reason" class="block text-sm font-medium text-neutral-700 mb-1">Please
                            provide
                            details</label>
                        <textarea id="cancellation_reason" name="cancellation_reason" rows="4" required
                            class="shadow-sm block w-full focus:ring-secondary focus:border-secondary border-neutral-300 rounded-md font-main"
                            placeholder="Please explain your reasons for cancellation in detail..."></textarea>
                    </div>

                    @if (
                        $engagement->deliverables->where('status', 'approved')->count() > 0 ||
                            $engagement->deliverables->where('status', 'submitted')->count() > 0)
                        <!-- Payment for Partial Work -->
                        <div class="bg-neutral-50 p-4 rounded-md border border-neutral-200 font-main text-sm">
                            <h4 class="font-medium text-neutral-800 mb-2">Payment for Completed Work
                            </h4>
                            <p class="text-sm text-neutral-600 mb-3">Some work has been submitted or
                                approved. Please indicate if you want to process payment for completed
                                work:</p>

                            <div class="flex items-center">
                                <input id="process_payment" name="process_payment" type="checkbox" value="1"
                                    class="h-4 w-4 text-secondary focus:ring-secondary border-neutral-300 rounded">
                                <label for="process_payment" class="ml-2 block text-sm text-neutral-700">
                                    Process payment for completed deliverables
                                </label>
                            </div>
                        </div>
                    @endif

                    <!-- Terms Acceptance -->
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input id="terms" name="terms" type="checkbox" required value="1"
                                class="h-4 w-4 text-secondary focus:ring-secondary border-neutral-300 rounded">
                        </div>
                        <div class="ml-3 text-sm font-main">
                            <label for="terms" class="font-medium text-neutral-700">I understand
                                and agree</label>
                            <p class="text-neutral-500">I have read and understand the <a
                                    href="{{ route('engagements.policy') }}"
                                    class="text-secondary hover:text-primary underline">cancellation &
                                    payment
                                    policy</a>.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex flex-col sm:flex-row sm:space-x-4">
                    <button type="button"
                        onclick="document.getElementById('cancelEngagementModal-{{ $engagement->id }}').classList.add('hidden')"
                        class="w-full sm:w-auto mb-3 sm:mb-0 inline-flex justify-center items-center px-4 py-2 border border-neutral-300 shadow-sm text-sm font-medium rounded-md text-neutral-700 bg-white hover:bg-neutral-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500">
                        Keep Engagement Active
                    </button>
                    <button type="submit"
                        class="w-full sm:w-auto inline-flex justify-center items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        Confirm Cancellation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
