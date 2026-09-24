<x-app-layout>

    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-tertiary font-bold text-xl text-primary leading-tight">
                    {{ __('Edit') }}: <span class="text-secondary">{{ $job->title }}</span>
                </h2>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('my-jobs.index') }}"
                    class="inline-flex items-center px-4 py-2 bg-secondary text-white rounded-lg hover:bg-secondary/90 transition-colors duration-200 font-main text-sm font-medium shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="h-5 w-5 mr-2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0 1 12 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 0 1-.673-.38m0 0A2.18 2.18 0 0 1 3 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 0 1 3.413-.387m7.5 0V5.25A2.25 2.25 0 0 0 13.5 3h-3a2.25 2.25 0 0 0-2.25 2.25v.894m7.5 0a48.667 48.667 0 0 0-7.5 0M12 12.75h.008v.008H12v-.008Z" />
                    </svg>
                    Back to Jobs
                </a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
        <div
            class="rounded-2xl shadow-xl overflow-hidden bg-white transition-all duration-300 hover:shadow-2xl border border-neutral-200">

            <x-jobs.form-header title="Edit 3D Design Project"
                subtitle="Update your project brief to refine your search for the ideal 3D design professional.">
                <x-slot:icon>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mr-3 text-accent" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                </x-slot:icon>
                <x-slot:decoration>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="0.5">
                        <path d="M12 20h9M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z" />
                    </svg>
                </x-slot:decoration>
            </x-jobs.form-header>

            <form method="POST" action="{{ route('jobs.update', $job) }}" class="p-8" enctype="multipart/form-data"
                id="projectForm">
                @csrf
                @method('PATCH')

                <x-jobs.budget-field class="mb-6" :value="intval($job->budget)" :step="1" />

                <x-jobs.deadline-field class="mb-6" :checked="is_null($job->deadline)"
                    :display-text="$job->deadline ? \Carbon\Carbon::parse($job->deadline)->format('F j, Y') : 'No Fixed Deadline'"
                    :date-value="$job->deadline ? $job->deadline->format('Y-m-d') : ''" :required="false"
                    :show-fallback-input="true" :dimmed="is_null($job->deadline)" />

                <!-- Status Toggle -->
                <div class="mb-6">
                    <div class="flex items-center mb-2">
                        <input type="checkbox" id="is_active" name="is_active" value="1"
                            class="h-4 w-4 text-secondary focus:ring-secondary border-neutral-300 rounded mr-2"
                            {{ $job->is_active ? 'checked' : '' }}>
                        <label for="is_active" class="block text-sm font-medium text-primary font-tertiary">
                            Active
                        </label>
                    </div>
                    @error('is_active')
                        <p class="text-red-500 text-xs italic">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-tertiary mt-2 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-secondary" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Uncheck to mark project as closed and hide it from public view
                    </p>
                </div>

                <!-- Submit button -->
                <div class="flex flex-col sm:flex-row justify-end gap-4 mt-8 border-t border-neutral-200 pt-6">
                    <button type="submit" id="submit-btn"
                        class="px-6 py-3 bg-secondary text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-secondary font-secondary transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center shadow-md hover:shadow-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Update Project Details
                    </button>
                </div>
            </form>
        </div>

        <script src="{{ asset('js/submit-btn-edit.js') }}"></script>
    </div>

    <!-- Footer -->
    @include('partials.footer-secondary')
</x-app-layout>
