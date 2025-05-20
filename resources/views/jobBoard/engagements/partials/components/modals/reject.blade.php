<!-- Reject Deliverable Modal -->
@foreach ($engagement->deliverables as $deliverable)
    @if ($deliverable->status === 'submitted')
        <div x-data="{ feedback: '' }">
            <x-modal name="reject-deliverable-{{ $deliverable->id }}" :show="false" max-width="2xl" focusable>
                <div class="p-8">
                    <!-- Header -->
                    <div class="text-center mb-6">
                        <div class="flex items-center justify-center text-accent mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16" viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="12" r="10" class="stroke-accent/20" stroke-width="2">
                                </circle>
                                <path class="stroke-accent" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                                </path>
                            </svg>
                        </div>
                        <h2 class="text-xl font-bold text-neutral-800">Reject
                            Deliverable</h2>
                        <p class="text-neutral-600 mt-2">
                            You're rejecting "<span class="font-medium">{{ $deliverable->title }}</span>"
                        </p>
                        <p class="text-neutral-500 text-sm mt-1">
                            Please include detailed feedback to help the freelancer
                            understand why the work was rejected
                        </p>
                    </div>

                    <!-- Divider -->
                    <div class="border-t border-neutral-200 my-6"></div>

                    <!-- Form -->
                    <form method="POST" action="{{ route('engagements.deliverables.reject', $deliverable->id) }}">
                        @csrf
                        <div class="mb-6">
                            <label for="reject-feedback-{{ $deliverable->id }}"
                                class="block text-sm font-medium text-neutral-700 mb-2">
                                Feedback <span class="text-accent font-medium">*</span>
                            </label>
                            <div class="relative">
                                <textarea id="reject-feedback-{{ $deliverable->id }}" x-model="feedback" rows="4" name="feedback"
                                    class="w-full rounded-lg border-neutral-300 shadow-sm focus:border-accent focus:ring focus:ring-accent/20 text-neutral-700 resize-none"
                                    placeholder="Explain what needs to be improved or corrected..." required></textarea>
                                <div class="absolute right-3 bottom-3 text-xs"
                                    :class="{
                                        'text-neutral-500': feedback.length >
                                            0,
                                        'text-accent': feedback.length === 0
                                    }"
                                    x-text="feedback.length + ' characters'"></div>
                            </div>
                            <p class="mt-1 text-xs text-neutral-500">Your feedback
                                will
                                be shared with the freelancer so they can make necessary
                                improvements.</p>
                        </div>

                        <div class="flex justify-between items-center">
                            <div class="text-sm text-neutral-500">
                                <div class="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    This action cannot be undone
                                </div>
                            </div>

                            <div class="flex gap-3">
                                <button type="button"
                                    @click="$dispatch('close-modal', 'reject-deliverable-{{ $deliverable->id }}')"
                                    class="px-4 py-2 text-sm font-medium text-neutral-700 bg-white border border-neutral-300 rounded-md hover:bg-neutral-50 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500">
                                    Cancel
                                </button>
                                <button type="submit"
                                    class="px-4 py-2 text-sm font-medium text-white bg-accent rounded-md hover:bg-accent/90 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-accent"
                                    :disabled="feedback.length === 0"
                                    :class="{
                                        'opacity-50 cursor-not-allowed': feedback
                                            .length === 0
                                    }">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5 inline" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    Reject Deliverable
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </x-modal>
        </div>
    @endif
@endforeach
