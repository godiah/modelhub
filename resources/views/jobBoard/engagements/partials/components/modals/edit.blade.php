<!-- Edit Deliverable Modal -->
@foreach ($engagement->deliverables as $deliverable)
    <x-modal name="edit-deliverable-{{ $deliverable->id }}" focusable>
        <form method="POST" action="{{ route('engagements.deliverables.update', $deliverable->id) }}"
            x-data="{
                title: @js($deliverable->title),
                description: @js((string) $deliverable->description),
                dueDate: @js($deliverable->due_date?->format('Y-m-d') ?? '')
            }">
            @csrf
            @method('PATCH')

            <x-modal.header title="Edit Deliverable" icon="pencil-square" />

            <div class="p-6">
                <p class="mb-5 text-sm text-neutral-600">Update the details for this deliverable</p>

                <div class="mb-5">
                    <x-form.label class="mb-2" for="title-{{ $deliverable->id }}">Title</x-form.label>
                    <input type="text" id="title-{{ $deliverable->id }}" name="title" x-model="title"
                        class="w-full rounded-lg border-neutral-300 shadow-sm focus:border-primary" required>
                </div>

                <div class="mb-5">
                    <x-form.label class="mb-2" for="description-{{ $deliverable->id }}">Description</x-form.label>
                    <textarea id="description-{{ $deliverable->id }}" name="description" x-model="description" rows="4"
                        class="w-full resize-none rounded-lg border-neutral-300 shadow-sm focus:border-primary"></textarea>
                </div>

                <div>
                    <x-form.label class="mb-2" for="due_date-{{ $deliverable->id }}">Due Date</x-form.label>
                    <div class="relative">
                        <input type="date" id="due_date-{{ $deliverable->id }}" name="due_date" x-model="dueDate"
                            class="w-full rounded-lg border-neutral-300 shadow-sm">
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                            <x-icon name="calendar" class="h-5 w-5 text-neutral-400" />
                        </div>
                    </div>
                    <p class="mt-1 text-xs text-neutral-500">Please select a future date</p>
                </div>
            </div>

            <x-modal.footer>
                <x-btn type="button" variant="secondary" x-on:click="dismiss()">Cancel</x-btn>
                <x-btn type="submit">
                    <x-icon name="check" class="h-4 w-4" />
                    Save Changes
                </x-btn>
            </x-modal.footer>
        </form>
    </x-modal>
@endforeach
