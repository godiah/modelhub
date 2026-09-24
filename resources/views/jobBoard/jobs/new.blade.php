<x-app-layout>

    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-tertiary font-bold text-2xl text-primary leading-tight">
                    {{ __('New Project') }}
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
                    My Jobs
                </a>
            </div>
        </div>
    </x-slot>

    <section class="font-main">
        <!-- Main Container -->
        <div class="container mx-auto max-w-7xl">
            <!-- Create Job Form -->
            <div class="py-14 px-4 sm:px-6 lg:px-8">
                <div
                    class="rounded-2xl shadow-xl overflow-hidden bg-white transition-all duration-300 hover:shadow-2xl border border-neutral-200">
                    <x-jobs.form-header title="Hire 3D Designer"
                        subtitle="Create a comprehensive project brief to find the perfect 3D design professional for your needs.">
                        <x-slot:icon>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mr-3 text-accent" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </x-slot:icon>
                        <x-slot:decoration>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="0.5">
                                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path>
                            </svg>
                        </x-slot:decoration>
                    </x-jobs.form-header>

                    <form method="POST" action="{{ route('jobs.store') }}" class="p-8" enctype="multipart/form-data"
                        id="projectForm">
                        @csrf
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-8">
                            <div>
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
                                        <div
                                            class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-neutral-400"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </div>
                                        <input type="text" id="title" name="title" required
                                            value="{{ old('title') }}" placeholder="Enter a title for your project"
                                            class="w-full pl-10 px-4 py-3 border border-neutral-300 rounded-lg text-sm focus:ring-2 focus:ring-secondary/50 focus:border-secondary transition-all duration-200 font-secondary">
                                    </div>
                                    <p id="title-validation-message" class="text-red-500 text-xs italic hidden"></p>
                                </div>

                                <!-- Project Description (Markdown) -->
                                <div class="mb-6">
                                    <label for="description"
                                        class="block text-sm font-medium text-primary mb-2 after:content-['*'] after:ml-1 after:text-red-500 font-tertiary">
                                        Project Description
                                    </label>
                                    @error('description')
                                        <p class="text-red-500 text-xs italic">{{ $message }}</p>
                                    @enderror
                                    <div class="border border-neutral-300 rounded-lg overflow-hidden">
                                        <textarea id="description" name="description" rows="6"
                                            class="w-full px-4 py-3 border-0 text-sm focus:ring-2 focus:ring-secondary/50 focus:border-secondary transition-all duration-200 font-secondary markdown-editor resize-none"
                                            placeholder="Describe your project in detail. Include project goals, timeline, specific requirements...">{{ old('description') }}</textarea>
                                    </div>
                                    <p class="text-xs text-tertiary mt-2 flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-secondary"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Markdown is supported. Use formatting to enhance your description.
                                    </p>
                                </div>
                            </div>

                            <div>
                                <!-- Preview Image Upload Section -->
                                <div class="mb-6">
                                    <label for="imageUpload"
                                        class="block text-sm font-medium text-primary mb-2 after:content-['*'] after:ml-1 after:text-red-500 font-tertiary">
                                        Upload Preview Image
                                    </label>

                                    @error('image')
                                        <p class="text-red-500 text-xs italic">{{ $message }}</p>
                                    @enderror

                                    <div class="mt-3 rounded-lg border border-neutral-200 p-4 bg-neutral-50">
                                        <div class="mb-3">
                                            <div class="flex justify-between items-center mb-2">
                                                <label for="imageUpload"
                                                    class="block text-sm font-medium text-primary font-tertiary">
                                                    Select Preview Image
                                                </label>
                                                <span id="previewFileStatus" class="text-xs text-neutral-500">No image
                                                    selected</span>
                                            </div>

                                            <div class="flex items-center">
                                                <button id="selectPreviewBtn" type="button"
                                                    class="mr-3 py-2 px-4 rounded-lg border border-dashed border-secondary 
                                        text-sm font-medium text-secondary hover:bg-secondary/5 focus:outline-none 
                                        transition-colors duration-200">
                                                    Select Image
                                                </button>
                                                <span class="text-xs text-neutral-500">
                                                    Accepted formats: JPG, PNG, JPEG (Max: 5MB)
                                                </span>
                                            </div>

                                            <input type="file" name="image" id="imageUpload" accept="image/*"
                                                class="hidden">
                                            <input type="hidden" name="old_image" id="old-image-input"
                                                value="{{ old('old_image', session('old_image')) }}">
                                        </div>

                                        <!-- Image preview container -->
                                        <div id="preview-container" class="mt-3">
                                            <!-- Preview thumbnail will be added here -->
                                        </div>

                                        <!-- Empty state message -->
                                        <div id="previewEmptyState"
                                            class="flex justify-center items-center h-24 border border-dashed border-neutral-300 rounded-lg bg-neutral-50 text-neutral-500 text-sm">
                                            No preview image selected
                                        </div>
                                    </div>
                                </div>

                                <!-- Additional Images Section -->
                                <div class="mb-4">
                                    <!-- Toggle Checkbox -->
                                    <div class="flex items-center space-x-2 mb-2">
                                        <input type="checkbox" id="toggleAdditional"
                                            class="form-checkbox h-4 w-4 text-secondary focus:ring-secondary rounded">
                                        <label for="toggleAdditional"
                                            class="text-sm text-neutral-700 font-medium flex items-center">
                                            <span>Add additional images</span>
                                            <span class="ml-1 text-xs text-neutral-500">(optional, max 5)</span>
                                        </label>
                                    </div>

                                    <!-- Additional Images Upload Section (initially hidden) -->
                                    <div id="additionalImagesSection"
                                        class="mt-3 rounded-lg border border-neutral-200 p-4 bg-neutral-50"
                                        style="display: none;">
                                        <div class="mb-3">
                                            <div class="flex justify-between items-center mb-2">
                                                <label for="additionalImages"
                                                    class="block text-sm font-medium text-primary font-tertiary">
                                                    Upload Additional Images
                                                </label>
                                                <span id="fileCounter" class="text-xs text-neutral-500">0/5
                                                    images</span>
                                            </div>

                                            <div class="flex items-center">
                                                <button id="selectImagesBtn" type="button"
                                                    class="mr-3 py-2 px-4 rounded-lg border border-dashed border-secondary 
                                                        text-sm font-medium text-secondary hover:bg-secondary/5 focus:outline-none 
                                                        transition-colors duration-200">
                                                    Select Images
                                                </button>
                                                <span class="text-xs text-neutral-500">
                                                    Accepted formats: JPG, PNG, JPEG(Max: 5MB each)
                                                </span>
                                            </div>

                                            <input type="file" id="additionalImages" name="additional_images[]"
                                                accept="image/jpeg,image/png,image/jpg" multiple class="hidden">
                                        </div>

                                        <!-- Image preview grid -->
                                        <div id="imagePreviewGrid"
                                            class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 mt-3">
                                            <!-- Thumbnails will be added here -->
                                        </div>

                                        <!-- Empty state message -->
                                        <div id="emptyStateMessage"
                                            class="flex justify-center items-center h-24 border border-dashed border-neutral-300 rounded-lg bg-neutral-50 text-neutral-500 text-sm">
                                            No images selected
                                        </div>
                                    </div>
                                </div>

                                <!-- 3D Skills (Multiselect Dropdown) -->
                                <div class="mb-6">
                                    <label
                                        class="block text-sm font-medium text-primary mb-2 after:content-['*'] after:ml-1 after:text-red-500 font-tertiary">
                                        Required 3D Skills
                                    </label>
                                    @error('skills')
                                        <p class="text-red-500 text-xs italic">{{ $message }}</p>
                                    @enderror
                                    <div class="relative">
                                        <div class="border border-neutral-300 rounded-lg overflow-hidden">
                                            <div class="p-3 bg-white relative">
                                                <div
                                                    class="absolute inset-y-0 left-0 pl-3  flex items-center pointer-events-none">
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="h-5 w-5 ml-2 text-neutral-400" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                    </svg>
                                                </div>
                                                <input type="text" id="searchInput"
                                                    placeholder="Search for 3D modeling skills..."
                                                    class="w-full pl-10 px-4 py-3 border border-neutral-300 rounded-lg text-sm focus:ring-2 focus:ring-secondary/50 focus:border-secondary transition-all duration-200 font-secondary">
                                            </div>

                                            <div id="selectedOptionsContainer"
                                                class="px-3 py-2 flex flex-wrap gap-2 bg-neutral-50 border-t border-neutral-200 empty:hidden">
                                                <!-- Selected options will be displayed here -->
                                            </div>

                                            <div id="skillsDropdown"
                                                class="max-h-60 overflow-y-auto border-t border-neutral-200 hidden">
                                                <div id="dropdownContent" class="p-2">
                                                    <!-- Dropdown items will be dynamically added here -->
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <select id="hiddenSelect" multiple class="hidden" name="skills[]">
                                    @foreach ($skills as $skill)
                                        <option value="{{ $skill->name }}"
                                            {{ is_array(old('skills')) && in_array($skill->name, old('skills')) ? 'selected' : '' }}>
                                            {{ $skill->name }}
                                        </option>
                                    @endforeach
                                </select>

                                <!-- 3D Software (Multiselect Dropdown) -->
                                <div class="mb-6">
                                    <label
                                        class="block text-sm font-medium text-primary mb-2 after:content-['*'] after:ml-1 after:text-red-500 font-tertiary">
                                        Required Software
                                    </label>
                                    @error('software')
                                        <p class="text-red-500 text-xs italic">{{ $message }}</p>
                                    @enderror
                                    <div class="relative" id="software-skills-dropdown">
                                        <div class="border border-neutral-300 rounded-lg overflow-hidden">
                                            <div class="p-3 bg-white relative">
                                                <div
                                                    class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="h-5 w-5 ml-2 text-neutral-400" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                    </svg>
                                                </div>
                                                <input type="text" id="searchInput"
                                                    placeholder="Search for 3D software..."
                                                    class="w-full pl-10 px-4 py-3 border border-neutral-300 rounded-lg text-sm focus:ring-2 focus:ring-secondary/50 focus:border-secondary transition-all duration-200 font-secondary">
                                            </div>

                                            <div id="selectedOptionsContainer"
                                                class="px-3 py-2 flex flex-wrap gap-2 bg-neutral-50 border-t border-neutral-200 empty:hidden">
                                                <!-- Selected options will be displayed here -->
                                            </div>

                                            <div id="skillsDropdown"
                                                class="max-h-60 overflow-y-auto border-t border-neutral-200 hidden">
                                                <div id="dropdownContent" class="p-2">
                                                    <!-- Dropdown items will be dynamically added here -->
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <select id="software-hidden-select" multiple class="hidden" name="software[]">
                                    @foreach ($software as $item)
                                        <option value="{{ $item->name }}"
                                            {{ is_array(old('software')) && in_array($item->name, old('software')) ? 'selected' : '' }}>
                                            {{ $item->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-6">
                            <x-jobs.deadline-field :checked="old('no_deadline') ? true : false"
                                :display-text="old('deadline') ?: 'Select Deadline'" :date-value="old('deadline')" />

                            <x-jobs.budget-field :value="old('budget')" :required="true" />
                        </div>

                        <!-- Helper Text -->
                        <div class="mb-4 mt-6">
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-secondary"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-sm text-tertiary"><sup class="text-red-500">*</sup> Required fields
                                </p>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex flex-col sm:flex-row justify-end gap-4 mt-8 border-t border-neutral-200 pt-6">
                            <button type="reset"
                                class="px-6 py-3 border border-neutral-300 rounded-lg text-neutral-700 hover:bg-neutral-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-secondary font-secondary transition-all duration-300 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-neutral-500"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Cancel
                            </button>
                            <button type="submit" id="submit-btn"
                                class="px-6 py-3 bg-secondary text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-secondary font-secondary transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center shadow-md hover:shadow-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                Start Project
                            </button>
                        </div>

                        <!-- Additional Images -->
                        <input type="file" id="additionalImages" name="additional_images[]"
                            accept="image/jpeg,image/png,image/jpg" multiple class="hidden">
                    </form>
                </div>
            </div>
        </div>

        <!-- Markdown Editor Script -->
        <script>
            document.querySelector('form').addEventListener('submit', function(e) {
                // Force the synchronization of the editor content to the textarea
                document.getElementById("description").value = simplemde.value();
            });
        </script>

        <!-- Footer -->
        @include('partials.footer-secondary')
    </section>

</x-app-layout>
