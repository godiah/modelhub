<!-- Delete Deliverable Modal -->

@foreach ($engagement->deliverables as $deliverable)
    <div>
        <x-modal name="remove-deliverable-{{ $deliverable->id }}" :show="false" max-width="2xl" focusable>
            <div class="p-8">
                <!-- Header -->
                <div class="text-center mb-6">
                    <div class="flex items-center justify-center text-red-500 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16" viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="10" class="stroke-red-100" stroke-width="2">
                            </circle>
                            <path class="stroke-red-500" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                            </path>
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-neutral-800">Remove Deliverable
                    </h2>
                    <p class="text-neutral-600 mt-2">
                        Are you sure you want to remove "<span class="font-medium">{{ $deliverable->title }}</span>"?
                    </p>
                    <p class="text-neutral-500 text-sm mt-1">
                        This action cannot be undone.
                    </p>
                </div>

                <!-- Divider -->
                <div class="border-t border-neutral-200 my-6"></div>

                <!-- Form -->
                <form method="POST" action="{{ route('engagements.deliverables.destroy', $deliverable->id) }}">
                    @csrf
                    @method('DELETE')

                    <div class="bg-red-50 border border-red-100 rounded-lg p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-red-700">
                                    Removing this deliverable will permanently delete it
                                    from the system. Any submitted work or feedback
                                    associated with it will be lost.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button type="button"
                            @click="$dispatch('close-modal', 'remove-deliverable-{{ $deliverable->id }}')"
                            class="px-4 py-2 text-sm font-medium text-neutral-700 bg-white border border-neutral-300 rounded-md hover:bg-neutral-50 transition-colors focus:outline-none">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 transition-colors shadow-sm focus:outline-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5 inline" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Permanently Remove
                        </button>
                    </div>
                </form>
            </div>
        </x-modal>
    </div>
@endforeach
