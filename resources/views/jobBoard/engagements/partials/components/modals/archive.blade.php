<!-- Archive Engagement Modal (single instance; opened with $dispatch('open-modal', { name: 'archive-engagement', id })) -->
<x-confirm-dialog name="archive-engagement" title="Archive Engagement" icon="archive-box-2" tone="primary"
    confirm-label="Archive Engagement" :action="route('engagements.archive')"
    message="You're about to archive this engagement. Archived engagements will be moved to your archive section and can be retrieved later if needed.">
    <input type="hidden" name="engagement_id" :value="payload.id">

    <div class="mt-4 flex items-start rounded-lg border border-accent/20 bg-accent/10 p-3">
        <x-icon name="information-circle" class="mr-2 mt-0.5 h-5 w-5 flex-shrink-0 text-accent" />
        <p class="font-secondary text-sm font-medium text-neutral-700">
            Note: The other party will still have access to view this engagement.
        </p>
    </div>
</x-confirm-dialog>
