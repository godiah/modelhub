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
                    <x-icon name="briefcase" class="h-5 w-5 mr-2" stroke-width="1.5" />
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
                    <x-icon name="pencil" class="h-8 w-8 mr-3 text-accent" />
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
                        <x-icon name="information-circle" class="h-4 w-4 mr-1 text-secondary" />
                        Uncheck to mark project as closed and hide it from public view
                    </p>
                </div>

                <!-- Submit button -->
                <div class="flex flex-col sm:flex-row justify-end gap-4 mt-8 border-t border-neutral-200 pt-6">
                    <button type="submit" id="submit-btn"
                        class="px-6 py-3 bg-secondary text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-secondary font-secondary transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center shadow-md hover:shadow-lg">
                        <x-icon name="arrow-path" class="h-5 w-5 mr-2" />
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
