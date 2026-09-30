<x-app-layout :crumb="'Edit: '.$job->title">

    <div class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
        <x-card rounded="2xl" shadow="xl" clip class="transition-all duration-300 hover:shadow-2xl">

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
                    <x-form.error name="is_active" variant="plain" />
                    <p class="text-xs text-tertiary mt-2 flex items-center">
                        <x-icon name="information-circle" class="h-4 w-4 mr-1 text-secondary" />
                        Uncheck to mark project as closed and hide it from public view
                    </p>
                </div>

                <!-- Submit button -->
                <div class="flex flex-col sm:flex-row justify-end gap-4 mt-8 border-t border-neutral-200 pt-6">
                    <x-btn size="lg" type="submit" id="submit-btn">
                        <x-icon name="arrow-path" class="h-5 w-5" />
                        Update Project Details
                    </x-btn>
                </div>
            </form>
        </x-card>

        <script src="{{ asset('js/submit-btn-edit.js') }}"></script>
    </div>

</x-app-layout>
