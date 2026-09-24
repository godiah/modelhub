<!-- Edit Deliverable Modal -->

@foreach ($engagement->deliverables as $deliverable)
    <div x-data="{
        title: '{{ $deliverable->title }}',
        description: '{{ $deliverable->description }}',
        dueDate: '{{ $deliverable->due_date ? $deliverable->due_date->format('Y-m-d') : '' }}'
    }">
        <x-modal name="edit-deliverable-{{ $deliverable->id }}" :show="false" max-width="2xl" focusable>
            <div class="p-8">
                <!-- Header -->
                <div class="text-center mb-6">
                    <div class="flex items-center justify-center text-primary mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16" viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="10" class="stroke-primary/20" stroke-width="2">
                            </circle>
                            <path class="stroke-primary" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                d="M8 12h8m-4-4v8"></path>
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-neutral-800">Edit Deliverable
                    </h2>
                    <p class="text-neutral-600 mt-2">
                        Update the details for this deliverable
                    </p>
                </div>

                <!-- Divider -->
                <div class="border-t border-neutral-200 my-6"></div>

                <!-- Form -->
                <form method="POST" action="{{ route('engagements.deliverables.update', $deliverable->id) }}">
                    @csrf
                    @method('PATCH')

                    <!-- Title Field -->
                    <div class="mb-5">
                        <label for="title-{{ $deliverable->id }}"
                            class="block text-sm font-medium text-neutral-700 mb-2">
                            Title
                        </label>
                        <input type="text" id="title-{{ $deliverable->id }}" name="title" x-model="title"
                            class="w-full rounded-lg border-neutral-300 shadow-sm focus:border-primary" required>
                    </div>

                    <!-- Description Field -->
                    <div class="mb-5">
                        <label for="description-{{ $deliverable->id }}"
                            class="block text-sm font-medium text-neutral-700 mb-2">
                            Description
                        </label>
                        <textarea id="description-{{ $deliverable->id }}" name="description" x-model="description" rows="4"
                            class="w-full rounded-lg border-neutral-300 shadow-sm focus:border-primary resize-none"></textarea>
                    </div>

                    <!-- Due Date Field -->
                    <div class="mb-6">
                        <label for="due_date-{{ $deliverable->id }}"
                            class="block text-sm font-medium text-neutral-700 mb-2">
                            Due Date
                        </label>
                        <div class="relative">
                            <input type="date" id="due_date-{{ $deliverable->id }}" name="due_date"
                                x-model="dueDate" class="w-full rounded-lg border-neutral-300 shadow-sm ">
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-neutral-400" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-neutral-500">Please select a
                            future
                            date</p>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3">
                        <button type="button"
                            @click="$dispatch('close-modal', 'edit-deliverable-{{ $deliverable->id }}')"
                            class="px-4 py-2 text-sm font-medium text-neutral-700 bg-white border border-neutral-300 rounded-md hover:bg-neutral-50 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-primary rounded-md hover:bg-primary/90 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5 inline" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </x-modal>
    </div>
@endforeach
