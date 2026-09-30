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
                                <x-icon name="information-circle-solid-2" class="h-5 w-5 text-red-400" />
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
                            <x-icon name="trash" class="h-4 w-4 mr-1.5 inline" />
                            Permanently Remove
                        </button>
                    </div>
                </form>
            </div>
        </x-modal>
    </div>
@endforeach
