<!-- Reject Deliverable Modal -->
@foreach ($engagement->deliverables as $deliverable)
    @if ($deliverable->status === 'submitted')
        <x-confirm-dialog name="reject-deliverable-{{ $deliverable->id }}" title="Reject Deliverable"
            :message="'You\'re rejecting “' . $deliverable->title . '”. Please include detailed feedback to help the freelancer understand why the work was rejected.'"
            icon="exclamation-triangle" tone="danger" confirm-icon="x-mark" confirm-label="Reject Deliverable"
            disabled-when="feedback.length === 0" state="feedback: ''"
            :action="route('engagements.deliverables.reject', $deliverable->id)">
            <div class="mt-4">
                <x-form.label class="mb-2" for="reject-feedback-{{ $deliverable->id }}">
                    Feedback <span class="font-medium text-accent">*</span>
                </x-form.label>
                <div class="relative">
                    <textarea id="reject-feedback-{{ $deliverable->id }}" x-model="feedback" rows="4" name="feedback"
                        class="w-full resize-none rounded-lg border-neutral-300 text-neutral-700 shadow-sm focus:border-accent focus:ring focus:ring-accent/20"
                        placeholder="Explain what needs to be improved or corrected..." required></textarea>
                    <div class="absolute bottom-3 right-3 text-xs"
                        :class="feedback.length > 0 ? 'text-neutral-500' : 'text-accent'"
                        x-text="feedback.length + ' characters'"></div>
                </div>
                <p class="mt-1 text-xs text-neutral-500">Your feedback will be shared with the freelancer so they can
                    make necessary improvements.</p>
            </div>
        </x-confirm-dialog>
    @endif
@endforeach
