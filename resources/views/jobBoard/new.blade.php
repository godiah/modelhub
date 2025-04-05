<x-app-layout>
    <section class="font-main">
        <!-- Main Container -->
        <div class="container mx-auto max-w-7xl">
            <!-- Breadcrumb -->
            <nav class="flex max-w-xl  p-8" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-2">
                    <!-- First Link -->
                    <li class="inline-flex items-center">
                        <a href="{{ route('home') }}"
                            class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-700">
                            <svg class="w-3 h-3 mr-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M19.707 9.293l-2-2-7-7a1 1 0 00-1.414 0l-7 7-2 2a1 1 0 001.414 1.414L2 10.414V18a2 2 0 002 2h3a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1h3a2 2 0 002-2v-7.586l.293.293a1 1 0 001.414-1.414z" />
                            </svg>
                            ModelHub
                        </a>
                    </li>
                    <!-- Second Link -->
                    <li>
                        <div class="flex items-center">
                            <svg class="rtl:rotate-180 w-3 h-3 text-gray-400 mx-1" aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2" d="M1 9l4-4-4-4" />
                            </svg>
                            <a href="{{ route('job.home') }}"
                                class="ml-1 text-sm font-medium text-gray-500 hover:text-gray-700 md:ml-2">
                                Modelling Jobs
                            </a>
                        </div>
                    </li>
                    <!-- Active Link -->
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg class="rtl:rotate-180 w-3 h-3 text-gray-400 mx-1" aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2" d="M1 9l4-4-4-4" />
                            </svg>
                            <span class="ml-1 text-sm font-medium text-black md:ml-2">
                                Start a Project
                            </span>
                        </div>
                    </li>
                </ol>
            </nav>

            <!-- Create Job Form -->
            <div class="pb-14">
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
                        <div
                            class="absolute top-0 right-0 w-64 h-64 transform translate-x-16 -translate-y-16 opacity-10">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="0.5">
                                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path>
                            </svg>
                        </div>
                    </div>

                    <form method="POST" action="/jobs/create" class="p-8" enctype="multipart/form-data"
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
                                    <input type="file" name="image" id="imageUpload" accept="image/*"
                                        class="hidden">
                                    <input type="hidden" name="old_image" id="old-image-input"
                                        value="{{ old('old_image', session('old_image')) }}">
                                    <div id="dropzone"
                                        class="border-2 border-dashed border-neutral-300 rounded-lg p-6 text-center cursor-pointer hover:border-secondary transition-all duration-300 relative min-h-[180px] flex flex-col items-center justify-center bg-neutral-50">
                                        <div id="dropzone-text"
                                            class="text-neutral-500 flex flex-col items-center space-y-2">
                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                class="h-12 w-12 text-secondary/50 mb-2" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    stroke-width="1.5"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <p class="text-sm font-medium text-neutral-600">Drag and drop your
                                                image here</p>
                                            <p class="text-xs text-neutral-500">or</p>
                                            <span
                                                class="px-4 py-2 bg-secondary/10 text-secondary rounded-lg text-sm font-medium hover:bg-secondary/20 transition-colors">
                                                Browse Files
                                            </span>
                                            <p class="text-xs text-neutral-500 mt-2">Supported formats: JPG, PNG,
                                                GIF (Max 5MB)</p>
                                        </div>
                                    </div>

                                    <!-- Separate Image Preview Container -->
                                    <div id="image-preview"
                                        class="mt-4 border border-neutral-200 rounded-lg p-4 bg-white hidden">
                                        <div class="flex items-center justify-between mb-2">
                                            <h3 class="text-sm font-medium text-neutral-700">Preview</h3>
                                        </div>
                                        <div id="preview-container" class="flex items-center justify-center">
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
                            <!-- Deadline -->
                            <div class="mb-2">
                                <div class="flex items-center justify-between mb-2">
                                    <label for="deadline"
                                        class="text-sm font-medium text-primary after:content-['*'] after:ml-1 after:text-red-500 font-tertiary">
                                        Deadline
                                    </label>
                                    <div class="flex items-center">
                                        <input type="checkbox" id="no_deadline" name="no_deadline" value="1"
                                            class="h-4 w-4 text-secondary focus:ring-secondary border-neutral-300 rounded mr-2"
                                            {{ old('no_deadline') ? 'checked' : '' }}>
                                        <label for="no_deadline" class="text-xs text-neutral-600">No Fixed
                                            Deadline</label>
                                    </div>
                                </div>
                                @error('deadline')
                                    <p class="text-red-500 text-xs italic">{{ $message }}</p>
                                @enderror
                                <div class="relative">
                                    <div id="deadline-display"
                                        class="w-full px-4 py-3 border border-neutral-300 rounded-lg cursor-pointer flex items-center justify-between transition-all duration-300 hover:border-secondary focus:ring-2 focus:ring-secondary/50 bg-white">
                                        <span id="selected-date-text" class="text-neutral-600 text-sm">
                                            {{ old('deadline') ? old('deadline') : 'Select Deadline' }}
                                        </span>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-secondary"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>

                                    <input type="date" id="deadline" name="deadline"
                                        class="absolute inset-0 opacity-0 cursor-pointer"
                                        value="{{ old('deadline') }}">

                                    <div id="calendar-container"
                                        class="absolute z-50 mt-2 bg-white border border-neutral-300 rounded-lg shadow-lg p-4 hidden">
                                        <div id="calendar" class="grid grid-cols-7 gap-2 text-center"></div>
                                    </div>
                                </div>
                                <p class="text-xs text-tertiary mt-2 flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-secondary"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Specify when you need this project completed
                                </p>
                            </div>

                            <!-- Budget -->
                            <div class="mb-2">
                                <label for="budget"
                                    class="block text-sm font-medium text-primary mb-2 after:content-['*'] after:ml-1 after:text-red-500 font-tertiary">
                                    Budget
                                </label>
                                @error('budget')
                                    <p class="text-red-500 text-xs italic">{{ $message }}</p>
                                @enderror
                                <div class="relative">
                                    <div
                                        class="absolute inset-y-0 left-0 flex items-center pr-3 pl-3 pointer-events-none bg-neutral-100 rounded-l-lg border-r border-neutral-200">
                                        <span class="text-neutral-600 font-medium">Ksh.</span>
                                    </div>
                                    <input type="number" id="budget" name="budget" required min="0"
                                        placeholder="Enter project budget"
                                        class="w-full pl-16 px-4 py-3 border border-neutral-300 rounded-lg text-sm focus:ring-2 focus:ring-secondary/50 focus:border-secondary transition-all duration-200 font-secondary appearance-none"
                                        value="{{ old('budget') }}">
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-neutral-400"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                </div>
                                <p class="text-xs text-tertiary mt-2 flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-secondary"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Enter budget in Kenyan Shillings
                                </p>
                            </div>
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
        @include('partials\footer-secondary')
    </section>

</x-app-layout>
