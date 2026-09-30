<!-- Add Deliverable Modal -->
<x-modal name="add-deliverable-{{ $engagement->id }}" :show="$errors->hasAny(['del_title', 'del_description', 'due_date'])" focusable>
    <form action="{{ route('engagements.deliverables.store', ['engagement' => $engagement->id]) }}" method="POST">
        @csrf

        <x-modal.header title="Add New Deliverable" icon="clipboard-list" />

        <div class="p-6">
            <div class="mb-4">
                <label for="del_title-{{ $engagement->id }}" class="mb-1 block font-tertiary font-medium text-neutral-700">
                    <span class="flex items-center">
                        <x-icon name="information-circle" class="mr-1 h-5 w-5 text-secondary" />
                        Title
                    </span>
                </label>
                <input type="text" id="del_title-{{ $engagement->id }}" name="del_title" required
                    class="w-full rounded-lg border border-neutral-300 px-3 py-2 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-secondary"
                    placeholder="Enter deliverable title">
            </div>

            <div class="mb-4">
                <label for="del_description-{{ $engagement->id }}" class="mb-1 block font-tertiary font-medium text-neutral-700">
                    <span class="flex items-center">
                        <x-icon name="list-bullet" class="mr-1 h-5 w-5 text-secondary" />
                        Description
                    </span>
                </label>
                <textarea id="del_description-{{ $engagement->id }}" name="del_description" rows="4"
                    class="w-full rounded-lg border border-neutral-300 px-3 py-2 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-secondary"
                    placeholder="Describe what needs to be delivered"></textarea>
            </div>

            <div>
                <label for="due_date-{{ $engagement->id }}" class="mb-1 block font-tertiary font-medium text-neutral-700">
                    <span class="flex items-center">
                        <x-icon name="calendar" class="mr-1 h-5 w-5 text-secondary" />
                        Due Date
                    </span>
                </label>
                <input type="date" id="due_date-{{ $engagement->id }}" name="due_date"
                    class="w-full rounded-lg border border-neutral-300 px-3 py-2 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-secondary">
            </div>
        </div>

        <x-modal.footer>
            <x-btn type="button" variant="secondary" x-on:click="dismiss()">Cancel</x-btn>
            <x-btn type="submit">
                <x-icon name="check" class="h-5 w-5" />
                Save Deliverable
            </x-btn>
        </x-modal.footer>
    </form>
</x-modal>
