<x-app-layout>

    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-tertiary font-bold text-2xl text-primary leading-tight">
                    {{ __('New Project') }}
                </h2>
            </div>
            <div class="flex space-x-3">
                <x-button variant="secondary" class="text-sm shadow-sm" href="{{ route('my-jobs.index') }}">
                    <x-icon name="briefcase" class="h-5 w-5 mr-2" stroke-width="1.5" />
                    My Jobs
                </x-button>
            </div>
        </div>
    </x-slot>

    <section class="font-main">
        <!-- Main Container -->
        <div class="container mx-auto max-w-7xl">
            <!-- Create Job Form -->
            <div class="py-14 px-4 sm:px-6 lg:px-8">
                <x-card rounded="2xl" shadow="xl" clip class="transition-all duration-300 hover:shadow-2xl">
                    <x-jobs.form-header title="Hire 3D Designer"
                        subtitle="Create a comprehensive project brief to find the perfect 3D design professional for your needs.">
                        <x-slot:icon>
                            <x-icon name="document-text" class="h-8 w-8 mr-3 text-accent" />
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
                                            <x-icon name="pencil-square" class="h-5 w-5 text-neutral-400" />
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
                                        <x-icon name="information-circle" class="h-4 w-4 mr-1 text-secondary" />
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
                                                    <x-icon name="magnifying-glass" class="h-5 w-5 ml-2 text-neutral-400" />
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
                                                    <x-icon name="magnifying-glass" class="h-5 w-5 ml-2 text-neutral-400" />
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
                                <x-icon name="information-circle" class="h-4 w-4 mr-1 text-secondary" />
                                <p class="text-sm text-tertiary"><sup class="text-red-500">*</sup> Required fields
                                </p>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex flex-col sm:flex-row justify-end gap-4 mt-8 border-t border-neutral-200 pt-6">
                            <button type="reset"
                                class="px-6 py-3 border border-neutral-300 rounded-lg text-neutral-700 hover:bg-neutral-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-secondary font-secondary transition-all duration-300 flex items-center justify-center">
                                <x-icon name="x-mark" class="h-5 w-5 mr-2 text-neutral-500" />
                                Cancel
                            </button>
                            <button type="submit" id="submit-btn"
                                class="px-6 py-3 bg-secondary text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-secondary font-secondary transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center shadow-md hover:shadow-lg">
                                <x-icon name="bolt" class="h-5 w-5 mr-2" />
                                Start Project
                            </button>
                        </div>

                        <!-- Additional Images -->
                        <input type="file" id="additionalImages" name="additional_images[]"
                            accept="image/jpeg,image/png,image/jpg" multiple class="hidden">
                    </form>
                </x-card>
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
