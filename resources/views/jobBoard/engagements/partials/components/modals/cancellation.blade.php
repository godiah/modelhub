<!-- Cancellation Modal -->
<x-modal name="cancel-engagement-{{ $engagement->id }}" max-width="3xl" focusable>
    <x-modal.header title="Cancel Engagement" />

    <div class="p-6">
        <div class="bg-amber-50 border-l-4 border-accent p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <x-icon name="exclamation-triangle" class="h-5 w-5 text-accent" />
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
                    <x-form.label class="mb-1" for="cancellation_type">Cancellation
                        Type</x-form.label>
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
                    <x-form.label class="mb-1" for="reason_category">Reason
                        Category</x-form.label>
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
                    <x-form.label class="mb-1" for="cancellation_reason">Please
                        provide
                        details</x-form.label>
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

            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <x-btn type="button" variant="secondary" x-on:click="dismiss()" class="justify-center">
                    Keep Engagement Active
                </x-btn>
                <x-btn type="submit" variant="danger" class="justify-center">
                    Confirm Cancellation
                </x-btn>
            </div>
        </form>
    </div>
</x-modal>
