<!-- Approve Deliverable Modal -->

@foreach ($engagement->deliverables as $deliverable)
    @if ($deliverable->status === 'submitted')
        <div x-data="{ feedback: '' }">
            <x-modal name="approve-deliverable-{{ $deliverable->id }}" :show="false" max-width="2xl" focusable>
                <div class="p-8">
                    <!-- Header -->
                    <div class="text-center mb-6">
                        <div class="flex items-center justify-center text-secondary mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16" viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="12" r="10" class="stroke-secondary/20"
                                    stroke-width="2">
                                </circle>
                                <path class="stroke-secondary" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" d="M8 12l3 3 6-6"></path>
                            </svg>
                        </div>
                        <h2 class="text-xl font-bold text-neutral-800">Approve
                            Deliverable</h2>
                        <p class="text-neutral-600 mt-2">
                            You're approving "<span class="font-medium">{{ $deliverable->title }}</span>"
                        </p>
                    </div>

                    <!-- Divider -->
                    <div class="border-t border-neutral-200 my-6"></div>

                    <!-- Form -->
                    <form method="POST" action="{{ route('engagements.deliverables.approve', $deliverable->id) }}">
                        @csrf
                        <div class="mb-6">
                            <label for="feedback-{{ $deliverable->id }}"
                                class="block text-sm font-medium text-neutral-700 mb-2">
                                Feedback <span class="text-neutral-500 text-xs">(Optional)</span>
                            </label>
                            <div class="relative">
                                <textarea id="feedback-{{ $deliverable->id }}" x-model="feedback" rows="4" name="feedback"
                                    class="w-full rounded-lg border-neutral-300 shadow-sm focus:border-secondary focus:ring focus:ring-secondary/20 text-neutral-700 resize-none"
                                    placeholder="Share any comments or feedback about this deliverable..."></textarea>
                                <div class="absolute right-3 bottom-3 text-xs text-neutral-500"
                                    x-text="feedback.length + ' characters'"></div>
                            </div>
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
                                    @click="$dispatch('close-modal', 'approve-deliverable-{{ $deliverable->id }}')"
                                    class="px-4 py-2 text-sm font-medium text-neutral-700 bg-white border border-neutral-300 rounded-md hover:bg-neutral-50 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500">
                                    Cancel
                                </button>
                                <button type="submit"
                                    class="px-4 py-2 text-sm font-medium text-white bg-secondary rounded-md hover:bg-secondary/90 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-secondary">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5 inline" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                    Approve Deliverable
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </x-modal>
        </div>
    @endif
@endforeach
