<x-app-layout>

    <section>
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div
                class="rounded-2xl shadow-xl overflow-hidden bg-white transition-all duration-300 hover:shadow-2xl border border-neutral-200">

                <!-- Form Header -->
                <div class="p-8 bg-primary text-white relative overflow-hidden">
                    <div class="relative z-10">
                        <h1 class="text-3xl font-bold font-tertiary mb-3 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mr-3 text-accent" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Hire 3D Designer
                        </h1>
                        <p class="text-sm text-neutral-100 font-secondary max-w-2xl">
                            Create a comprehensive project brief to find the perfect 3D design professional for
                            your needs.
                        </p>
                    </div>
                    <div class="absolute top-0 right-0 w-64 h-64 transform translate-x-16 -translate-y-16 opacity-10">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="0.5">
                            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path>
                        </svg>
                    </div>
                </div>

                <form method="PATCH" action="{{ route('jobs.update', $job) }}" class="p-8"
                    enctype="multipart/form-data" id="projectForm">
                    @csrf
                    @method('PATCH')

                    <!--  Project Title -->
                    <div class="mb-6">
                        <label for="title"
                            class="block text-sm font-medium text-primary mb-2 after:content-['*'] after:ml-1 after:text-red-500 font-tertiary">
                            Project Title
                        </label>
                        @error('title')
                            <p class="text-red-500 text-xs italic">{{ $message }}</p>
                        @enderror
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-neutral-400" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </div>
                            <input type="text" id="title" name="title" required
                                value="{{ old('title', $job->title) }}" placeholder="Enter a title for your project"
                                class="w-full pl-10 px-4 py-3 border border-neutral-300 rounded-lg text-sm focus:ring-2 focus:ring-secondary/50 focus:border-secondary transition-all duration-200 font-secondary">

                        </div>
                        <p id="title-validation-message" class="text-red-500 text-xs italic hidden"></p>
                    </div>

                    <!-- Description field with existing value -->
                    <textarea id="description" name="description" rows="6"
                        class="w-full px-4 py-3 border-0 text-sm focus:ring-2 focus:ring-secondary/50 focus:border-secondary transition-all duration-200 font-secondary markdown-editor resize-none"
                        placeholder="Describe your project in detail...">{{ old('description', $job->description) }}</textarea>

                    <!-- Image upload section with existing image -->
                    <input type="file" name="image" id="imageUpload" accept="image/*" class="hidden">
                    <input type="hidden" name="old_image" id="old-image-input"
                        value="{{ old('old_image', $job->images) }}">

                    <!-- Skills multiselect with existing selections -->
                    <select id="hiddenSelect" multiple class="hidden" name="skills[]">
                        @foreach ($skills as $skill)
                            <option value="{{ $skill->name }}"
                                {{ is_array(old('skills')) && in_array($skill->name, old('skills', $job->skills->pluck('name')->toArray())) ? 'selected' : '' }}>
                                {{ $skill->name }}
                            </option>
                        @endforeach
                    </select>

                    <!-- Software multiselect with existing selections -->
                    <select id="software-hidden-select" multiple class="hidden" name="software[]">
                        @foreach ($software as $item)
                            <option value="{{ $item->name }}"
                                {{ is_array(old('software')) && in_array($item->name, old('software', $job->software->pluck('name')->toArray())) ? 'selected' : '' }}>
                                {{ $item->name }}
                            </option>
                        @endforeach
                    </select>

                    <!-- Deadline field with existing value -->
                    <input type="date" id="deadline" name="deadline"
                        class="absolute inset-0 opacity-0 cursor-pointer"
                        value="{{ old('deadline', $job->deadline ? $job->deadline->format('Y-m-d') : null) }}">

                    <!-- No deadline checkbox -->
                    <input type="checkbox" id="no_deadline" name="no_deadline" value="1"
                        class="h-4 w-4 text-secondary focus:ring-secondary border-neutral-300 rounded mr-2"
                        {{ old('no_deadline', !$job->deadline) ? 'checked' : '' }}>

                    <!-- Budget field with existing value -->
                    <input type="number" id="budget" name="budget" required min="0"
                        placeholder="Enter project budget"
                        class="w-full pl-16 px-4 py-3 border border-neutral-300 rounded-lg text-sm focus:ring-2 focus:ring-secondary/50 focus:border-secondary transition-all duration-200 font-secondary appearance-none"
                        value="{{ old('budget', $job->budget) }}">

                    <!-- Submit button -->
                    <button type="submit" id="submit-btn"
                        class="px-6 py-3 bg-secondary text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-secondary font-secondary transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center shadow-md hover:shadow-lg">
                        <svg>...</svg>
                        Update Project
                    </button>
                </form>
            </div>
        </div>

        {{-- <script src="{{ asset('js/app.js') }}"></script> --}}
    </section>


</x-app-layout>
