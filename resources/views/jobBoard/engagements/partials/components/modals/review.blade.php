<!-- Review Modal -->
<div x-data="{ open: false, rating: 0, reviewText: '', tags: [], isPublic: true, engagement: null }" x-on:open-review-modal.window="engagement = $event.detail; open = true" class="relative z-50"
    x-cloak>

    <!-- Modal Backdrop -->
    <div x-show="open" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-neutral-900/70 backdrop-blur-sm" @click="open = false">
    </div>

    <!-- Modal Content -->
    <div x-show="open" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-4" class="fixed inset-0 flex items-center justify-center p-4">

        <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl mx-auto overflow-hidden max-h-[90vh] overflow-y-auto"
            @click.outside="open = false">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-primary to-primary/90 p-5">
                <div class="flex justify-between items-center">
                    <h3 class="text-xl font-tertiary font-bold text-white flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
                        </svg>
                        Leave a Review
                    </h3>
                    <button @click="open = false" class="text-white hover:text-neutral-200 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="p-6 font-main">
                <form id="reviewForm" x-bind:action="'/engagements/' + engagement.id + '/review'" method="POST">
                    @csrf

                    <template x-if="engagement.status === 'cancelled'">
                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-yellow-400"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
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
                        <label for="review" class="block text-neutral-700 font-medium mb-1">Review</label>
                        <textarea id="review" name="review" x-model="reviewText" rows="4"
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
                    <div class="flex justify-end space-x-3 mt-4">
                        <button type="button" @click="open = false"
                            class="px-5 py-2.5 border border-neutral-300 rounded-lg text-sm font-medium text-neutral-700 hover:bg-neutral-100 transition-colors">
                            Cancel
                        </button>
                        {{-- <button type="submit" @click="submitForm()"
                            :disabled="rating === 0 || reviewText.length < 10"
                            :class="{
                                'opacity-50 cursor-not-allowed': rating === 0 || reviewText
                                    .length < 10
                            }"
                            class="inline-flex items-center px-5 py-2.5 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-gradient-to-r from-primary to-primary/90 hover:from-primary/90 hover:to-primary transition-all duration-300">
                            Submit Review
                        </button> --}}
                        <button type="submit"
                            class="px-4 py-2 bg-gradient-to-r from-primary to-primary/90 hover:from-primary/90 hover:to-primary text-white rounded-lg transition-colors">
                            Submit Review
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
