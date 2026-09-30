<!-- Review Modal (single instance; opened with $dispatch('open-modal', { name: 'review-engagement', id, status })) -->
<x-modal name="review-engagement" focusable>
    <x-modal.header variant="brand" title="Leave a Review" icon="chat-bubble-text" />

    <div class="p-6 font-main" x-data="{ rating: 0, reviewText: '', tags: [], isPublic: true }">
        <form x-bind:action="'/engagements/' + payload.id + '/review'" method="POST">
                    @csrf

                    <template x-if="payload.status === 'cancelled'">
                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <x-icon name="information-circle" class="h-5 w-5 text-yellow-400" />
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-yellow-700">
                                        Although this engagement was cancelled, your feedback helps
                                        maintain a high-quality marketplace for everyone. Reviews are a
                                        vital part of our community's trust system.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Rating Section -->
                    <div class="mb-3">
                        <label class="block text-neutral-700 font-medium mb-1">Your
                            Rating</label>
                        <div class="flex items-center justify-center">
                            <template x-for="i in 5" :key="i">
                                <button type="button" @click="rating = i"
                                    class="focus:outline-none mx-1 transition-transform hover:scale-110"
                                    :class="{ 'transform scale-110': rating >= i }">
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        :class="rating >= i ? 'text-accent' : 'text-neutral-300'"
                                        class="h-8 w-8 transition-colors duration-200" fill="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                                    </svg>
                                </button>
                            </template>
                        </div>
                        <input type="hidden" name="rating" :value="rating">
                        @error('rating')
                            <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Review Text -->
                    <div class="mb-3">
                        <label for="engagement-review" class="block text-neutral-700 font-medium mb-1">Review</label>
                        <textarea id="engagement-review" name="review" x-model="reviewText" rows="4"
                            class="w-full px-4 py-3 border border-neutral-300 rounded-lg focus:ring-2 focus:ring-secondary focus:border-secondary transition-colors"
                            placeholder="Share your experience working on this project..." required></textarea>
                        <div class="text-xs text-neutral-500 mt-1 flex justify-between">
                            <span>Minimum 10 characters</span>
                            <span x-text="reviewText.length + ' characters'"></span>
                        </div>
                        @error('review')
                            <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Tags Section -->
                    <div class="mb-3">
                        <label class="block text-neutral-700 font-medium mb-1">Highlight
                            Skills/Qualities</label>
                        <div class="flex flex-wrap gap-2">
                            @foreach (['Communication', 'Quality', 'Expertise', 'Timeliness', 'Collaboration', 'Problem-solving'] as $tag)
                                <label
                                    class="inline-flex items-center px-3 py-1.5 rounded-full cursor-pointer transition-all duration-200"
                                    :class="tags.includes('{{ $tag }}') ?
                                        'bg-secondary/20 border-secondary text-secondary' :
                                        'bg-neutral-100 border-neutral-200 text-neutral-600'">
                                    <input type="checkbox" name="tags[]" value="{{ $tag }}" class="hidden"
                                        x-on:change="tags.includes('{{ $tag }}') ? tags = tags.filter(t => t !== '{{ $tag }}') : tags.push('{{ $tag }}')">
                                    <span>{{ $tag }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Visibility Toggle -->
                    <div class="mb-3">
                        <label class="flex items-center">
                            <input type="checkbox" name="is_public" value="1" :checked="isPublic"
                                @change="isPublic = !isPublic"
                                class="rounded text-secondary focus:ring-secondary h-5 w-5">
                            <span class="ml-2 text-neutral-700">Make this review public</span>
                        </label>
                        <p class="text-xs text-neutral-500 mt-1 ml-7">Public reviews are
                            visible to all platform users</p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-4 flex justify-end gap-3">
                        <x-button type="button" variant="neutral" x-on:click="dismiss()">Cancel</x-button>
                        <x-button type="submit">Submit Review</x-button>
                    </div>
                </form>
    </div>
</x-modal>
