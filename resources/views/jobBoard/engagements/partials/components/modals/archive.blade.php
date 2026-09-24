<!-- Archive Engagement Modal -->
<div x-data="{
    open: false,
    engagementId: null,
    loading: false
}"
    x-on:open-archive-modal.window="
        open = true;
        engagementId = $event.detail.id;
    "
    @keydown.escape.window="open = false" x-show="open" x-cloak class="fixed inset-0 z-50 overflow-y-auto"
    aria-labelledby="archive-modal" role="dialog" aria-modal="true" style="display: none;">

    <!-- Overlay -->
    <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click="open = false"
        class="fixed inset-0 bg-neutral-900/50 backdrop-blur-sm">
    </div>

    <!-- Modal Content -->
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div x-show="open" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95" @click.outside="open = false"
            class="relative bg-white dark:bg-neutral-800 rounded-xl overflow-hidden shadow-2xl w-full max-w-xl transform transition-all">

            <!-- Modal Pattern Background -->
            <div class="absolute inset-0 opacity-5">
                <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <pattern id="archivePattern" x="0" y="0" width="20" height="20"
                            patternUnits="userSpaceOnUse">
                            <path d="M0 10 L10 0 L20 10 L10 20 Z" fill="currentColor" />
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#archivePattern)" />
                </svg>
            </div>

            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-neutral-200 dark:border-neutral-700 relative">
                <div class="flex items-center">
                    <div class="flex-shrink-0 p-2 rounded-full bg-gradient-to-br from-accent to-accent/70">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                        </svg>
                    </div>
                    <h3 class="ml-3 text-lg font-semibold font-tertiary text-neutral-800 dark:text-white">
                        Archive Engagement
                    </h3>
                </div>

                <!-- Close Button -->
                <button @click="open = false"
                    class="absolute top-4 right-4 text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-200 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="px-6 py-4 relative">
                <p class="text-neutral-600 dark:text-neutral-300 text-sm font-main">
                    You're about to archive this engagement. Archived engagements will be moved to your
                    archive section and can be retrieved later if needed. This helps keep your active
                    engagements organized and your dashboard clean.
                </p>

                <div class="mt-4 p-4 bg-gradient-to-r from-accent/10 to-accent/5 rounded-lg border border-accent/20">
                    <div class="flex items-start">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-accent flex-shrink-0 mr-2 mt-0.5"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-sm text-white font-medium font-secondary">
                            Note: The other party will still have access to view this engagement.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div
                class="px-6 py-4 bg-neutral-50 dark:bg-neutral-800/50 flex flex-col sm:flex-row-reverse gap-2 relative">
                <form action="{{ route('engagements.archive') }}" method="POST" x-data="{ loading: false, engagementId: null }"
                    x-on:open-archive-modal.window="engagementId = $event.detail.id" x-on:submit="loading = true">
                    @csrf
                    <input type="hidden" name="engagement_id" :value="engagementId" />

                    <button type="submit" :disabled="loading"
                        class="inline-flex justify-center items-center rounded-lg px-4 py-2.5 bg-gradient-to-r from-accent to-accent/80 text-white font-medium text-sm shadow transition-all hover:from-accent/90 hover:to-accent/70 focus:ring-2 focus:ring-accent/50 focus:ring-offset-2 disabled:opacity-70 disabled:cursor-not-allowed">
                        <span x-show="!loading">Archive Engagement</span>
                        <span x-show="loading" class="inline-flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            Processing...
                        </span>
                    </button>
                </form>

                <button type="button" @click="open = false"
                    class="w-full sm:w-auto inline-flex justify-center items-center rounded-lg px-4 py-2.5 border border-neutral-300 dark:border-neutral-600 bg-white dark:bg-neutral-700 text-neutral-700 dark:text-neutral-200 font-medium text-sm shadow-sm hover:bg-neutral-50 dark:hover:bg-neutral-600 focus:ring-2 focus:ring-primary/30 focus:ring-offset-2 transition-all">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>
