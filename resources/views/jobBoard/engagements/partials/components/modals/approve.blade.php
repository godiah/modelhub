<!-- Approve Deliverable Modal -->
@foreach ($engagement->deliverables as $deliverable)
    @if ($deliverable->status === 'submitted')
        <x-confirm-dialog name="approve-deliverable-{{ $deliverable->id }}" title="Approve Deliverable"
            :message="'You\'re approving “' . $deliverable->title . '”. This action cannot be undone.'"
            icon="check-circle" tone="success" state="feedback: ''" confirm-icon="check" confirm-label="Approve Deliverable"
            :action="route('engagements.deliverables.approve', $deliverable->id)">
            <div class="mt-4">
                <x-form.label class="mb-2" for="feedback-{{ $deliverable->id }}">
                    Feedback <span class="text-xs text-neutral-500">(Optional)</span>
                </x-form.label>
                <div class="relative">
                    <textarea id="feedback-{{ $deliverable->id }}" x-model="feedback" rows="4" name="feedback"
                        class="w-full resize-none rounded-lg border-neutral-300 text-neutral-700 shadow-sm focus:border-secondary focus:ring focus:ring-secondary/20"
                        placeholder="Share any comments or feedback about this deliverable..."></textarea>
                    <div class="absolute bottom-3 right-3 text-xs text-neutral-500"
                        x-text="feedback.length + ' characters'"></div>
                </div>
            </div>
        </x-confirm-dialog>
    @endif
@endforeach
