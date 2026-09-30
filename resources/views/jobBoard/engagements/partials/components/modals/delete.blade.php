<!-- Delete Deliverable Modal -->
@foreach ($engagement->deliverables as $deliverable)
    <x-confirm-dialog name="remove-deliverable-{{ $deliverable->id }}" title="Remove Deliverable"
        :message="'Are you sure you want to remove “' . $deliverable->title . '”? This action cannot be undone.'"
        icon="trash" confirm-icon="trash" confirm-label="Permanently Remove" method="DELETE"
        :action="route('engagements.deliverables.destroy', $deliverable->id)">
        <div class="mt-4 rounded-lg border border-red-100 bg-red-50 p-4">
            <p class="text-sm text-red-700">
                Removing this deliverable will permanently delete it from the system. Any submitted work or
                feedback associated with it will be lost.
            </p>
        </div>
    </x-confirm-dialog>
@endforeach
