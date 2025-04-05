<x-app-layout>
    <section>
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg p-6 shadow">
                <h1 class="text-2xl font-semibold text-primary mb-6">Continue Your Application</h1>

                <form id="job-application-form" class="space-y-8" action="{{ route('applications.store') }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf

                    <input type="hidden" name="job_id" value="{{ $job->id }}">
                    <input type="hidden" name="poster_id" value="{{ $job->user_id }}">
                    <input type="hidden" name="applicant_id" value="{{ auth()->id() }}">
                    <input type="hidden" name="status" value="submitted">

                    <!-- Your Offer -->
                    <div class="flex flex-col md:flex-row md:space-x-6">
                        <div class="w-full md:w-1/2 relative">
                            <label for="offer" class="block text-sm font-medium text-primary mb-2 flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-secondary"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Your Offer Amount
                            </label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-neutral-500 sm:text-sm">Ksh</span>
                                </div>
                                <input type="number" id="offer" name="offer"
                                    value="{{ old('offer', $application->offer_amount > 0 ? $application->offer_amount : '') }}"
                                    class="pl-10 block w-full rounded-lg border-neutral-300 shadow-sm focus:ring-secondary focus:border-secondary transition duration-150 ease-in-out text-neutral-700"
                                    placeholder="{{ $job->budget }}">
                            </div>
                        </div>

                        <!-- Your Earnings -->
                        <div
                            class="w-full md:w-1/2 mt-6 md:mt-0 bg-gradient-to-r from-secondary/5 to-primary/5 p-6 rounded-xl border border-neutral-200">
                            <h3 class="text-sm font-semibold text-primary mb-4 flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-secondary"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                                Earnings Breakdown
                            </h3>
                            <div class="space-y-3">
                                <div class="flex justify-between text-sm">
                                    <span class="text-neutral-600">Your offer</span>
                                    <span id="yourOfferAmount" class="font-semibold text-neutral-800">
                                        {{ old('offer', $application->offer_amount) ? 'Ksh' . number_format(old('offer', $application->offer_amount), 2) : '' }}
                                    </span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-neutral-600 flex items-center">
                                        Service fee
                                        <span
                                            class="inline-flex items-center justify-center ml-1 w-4 h-4 rounded-full bg-neutral-200 text-xs"
                                            title="Service fee applied to all projects">?</span>
                                    </span>
                                    <span id="serviceFee" class="font-semibold text-red-500">
                                        {{ old('offer', $application->offer_amount) ? 'Ksh' . number_format(old('offer', $application->offer_amount) * 0.1, 2) : '' }}
                                    </span>
                                </div>
                                <div class="flex justify-between font-bold mt-4 pt-4 border-t border-neutral-200">
                                    <span class="text-primary">You'll receive</span>
                                    <span id="youllReceive" class="text-secondary text-lg">
                                        {{ old('offer', $application->offer_amount) ? 'Ksh' . number_format(old('offer', $application->offer_amount) * 0.9, 2) : '' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Your Proposal -->
                    <div>
                        <label for="proposal" class="block text-sm font-medium text-primary mb-2">Your Proposal</label>
                        <div class="relative">
                            <textarea id="proposal" name="proposal" rows="6"
                                class="block w-full rounded-lg border-neutral-300 shadow-sm focus:ring-secondary focus:border-secondary resize-none transition duration-150 ease-in-out"
                                placeholder="Be specific about your skills, experience and proposal here . . . ">{{ old('proposal', $application->proposal) }}</textarea>
                            <div class="absolute bottom-3 right-3 text-xs text-neutral-400">
                                <span id="characters-count">0</span>/2500
                            </div>
                        </div>
                    </div>

                    <!-- Portfolio Upload -->
                    <div>
                        <label class="block text-sm font-medium text-primary mb-2">Portfolio Samples</label>
                        <div
                            class="mt-1 border-2 border-dashed border-neutral-300 rounded-lg bg-neutral-50 transition-all duration-200 ease-in-out hover:bg-neutral-100 hover:border-secondary/50">
                            <div class="flex flex-col items-center justify-center py-6 px-4">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-secondary/60"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                <p class="mt-3 text-sm text-neutral-700">
                                    <span class="font-medium text-secondary">Drop files here</span> or
                                    <label for="file-upload" class="relative cursor-pointer">
                                        <span class="font-medium text-secondary underline">browse</span>
                                        <input id="file-upload" name="portfolio[]" type="file" class="sr-only"
                                            multiple accept="image/*">
                                    </label>
                                </p>
                                <p class="mt-1 text-xs text-neutral-500">
                                    JPG, JPEG and PNG (max. 5MB per file, max. 5 files)
                                </p>

                                <!-- Preview area -->
                                <div id="file-preview"
                                    class="w-full mt-4 {{ isset($application) && !empty($application->portfolio) ? '' : 'hidden' }}">
                                    <div id="preview-container"
                                        class="flex flex-wrap gap-4 p-4 bg-white rounded border border-neutral-200">
                                        <!-- Existing portfolio files will be added here dynamically -->
                                        @if (isset($application) && !empty($application->portfolio))
                                            @foreach ($application->portfolio as $index => $filePath)
                                                <div
                                                    class="file-item flex items-center p-2 bg-white rounded border border-neutral-200">
                                                    <div class="w-12 h-12 rounded overflow-hidden mr-3">
                                                        @if (Str::endsWith($filePath, ['.jpg', '.jpeg', '.png']))
                                                            <img src="{{ Storage::url($filePath) }}" alt="Preview"
                                                                class="w-full h-full object-cover">
                                                        @else
                                                            <div
                                                                class="w-full h-full flex items-center justify-center bg-neutral-100">
                                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                                    class="h-6 w-6 text-neutral-500" fill="none"
                                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round" stroke-width="2"
                                                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                                </svg>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div class="flex-1 min-w-0">
                                                        <p class="text-sm text-neutral-700 truncate">
                                                            {{ basename($filePath) }}</p>
                                                    </div>
                                                    <button type="button"
                                                        class="text-neutral-400 hover:text-red-500 ml-2 remove-file"
                                                        data-file-path="{{ $filePath }}">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                    </button>
                                                    <input type="hidden" name="existing_portfolio[]"
                                                        value="{{ $filePath }}">
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Terms & Conditions -->
                    <div>
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input id="terms" name="terms" type="checkbox"
                                    class="h-5 w-5 text-secondary rounded border-neutral-300 focus:ring-secondary"
                                    {{ old('terms', $application->terms_accepted) ? 'checked' : '' }}>
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="terms" class="text-neutral-700 font-medium">
                                    I agree to the <a href="#" class="text-secondary hover:underline">Terms of
                                        Service</a> and <a href="#"
                                        class="text-secondary hover:underline">Privacy Policy</a>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col justify-end sm:flex-row gap-3 pt-2">
                        <button type="submit" name="action" value="submitted"
                            class="bg-secondary hover:bg-secondary/90 focus:ring-2 focus:ring-offset-2 focus:ring-secondary text-white font-medium px-3 py-1.5 rounded-lg shadow-sm transition duration-150 ease-in-out flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                            </svg>
                            Submit Application
                        </button>
                    </div>

                    <!-- Hidden field for tracking existing portfolio files -->
                    <div id="removed-files-container"></div>
                </form>
            </div>
        </div>

        <!-- Footer -->
        @include('partials\footer-secondary')

        <!-- Disable submission -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Get the elements
                const offerInput = document.getElementById('offer');
                const termsCheckbox = document.getElementById('terms');
                const submitButton = document.querySelector('button[value="submitted"]');

                // Initially disable the submit button
                submitButton.disabled = true;
                submitButton.classList.add('opacity-50', 'cursor-not-allowed');

                // Function to check if form is valid
                function validateForm() {
                    // Check if offer has a value and terms is checked
                    if (offerInput.value.trim() !== '' && termsCheckbox.checked) {
                        submitButton.disabled = false;
                        submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
                    } else {
                        submitButton.disabled = true;
                        submitButton.classList.add('opacity-50', 'cursor-not-allowed');
                    }
                }

                // Add event listeners to both fields
                offerInput.addEventListener('input', validateForm);
                termsCheckbox.addEventListener('change', validateForm);

                // Initial validation check
                validateForm();
            });
        </script>

        <!-- Tracking Proposal Characters -->
        <script>
            // Maximum character limit
            const maxCharacters = 2500;

            // Get references to the elements
            const proposalTextarea = document.getElementById('proposal');
            const charactersCount = document.getElementById('characters-count');

            // Function to update the character count
            function updateCharacterCount() {
                const currentLength = proposalTextarea.value.length;
                const remainingCharacters = maxCharacters - currentLength;

                // Update the character count display
                charactersCount.textContent = currentLength;

                // Add warning styling when approaching the limit
                if (remainingCharacters <= 500) {
                    charactersCount.classList.add('text-red-500');
                } else {
                    charactersCount.classList.remove('text-red-500');
                }

                // Prevent further input when reaching the limit
                if (currentLength >= maxCharacters) {
                    proposalTextarea.value = proposalTextarea.value.substring(0, maxCharacters);
                    updateCharacterCount(); // Update the count after truncation
                }
            }

            // Initialize the character count
            updateCharacterCount();

            // Listen for input changes
            proposalTextarea.addEventListener('input', updateCharacterCount);
        </script>

        <!-- Calculating Service Fee & Payout -->
        <script>
            // Service fee percentage (configurable)
            const serviceFeePercentage = 0.10; // 10%

            // Get references to the elements
            const offerInput = document.getElementById('offer');
            const yourOfferAmount = document.getElementById('yourOfferAmount');
            const serviceFee = document.getElementById('serviceFee');
            const youllReceive = document.getElementById('youllReceive');

            // Function to update the earnings breakdown
            function updateEarnings() {
                const offer = parseFloat(offerInput.value) || 0;

                // Calculate service fee
                const fee = offer * serviceFeePercentage;

                // Calculate net amount
                const netAmount = offer - fee;

                // Update the DOM with formatted values
                yourOfferAmount.textContent = formatCurrency(offer);
                serviceFee.textContent = formatCurrency(fee, true);
                youllReceive.textContent = formatCurrency(netAmount);
            }

            // Function to format currency
            function formatCurrency(amount, isNegative = false) {
                const symbol = isNegative ? '-' : '';
                return `${symbol}Ksh${amount.toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
                })}`;
            }

            // Initialize with default values
            updateEarnings();

            // Listen for input changes
            offerInput.addEventListener('input', updateEarnings);
        </script>

        <!-- Portfolio Uploads -->
        <script>
            // Function to show SweetAlert for file limit exceeded
            function showMaxFilesAlert() {
                Swal.fire({
                    icon: 'error',
                    title: 'File Limit Exceeded',
                    text: `You can upload a maximum of ${MAX_FILES} files.`,
                });
            }

            // Function to show SweetAlert for file size exceeded
            function showFileSizeAlert() {
                Swal.fire({
                    icon: 'error',
                    title: 'File Size Limit Exceeded',
                    text: `File size should not exceed ${MAX_FILE_SIZE / (1024 * 1024)}MB.`,
                });
            }

            // Function to show SweetAlert for invalid file type
            function showFileTypeAlert() {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid File Type',
                    text: 'Only JPG, JPEG, PNG, and PDF files are allowed.',
                });
            }

            // Maximum number of files allowed
            const MAX_FILES = 5;
            // Maximum file size in bytes (10MB)
            const MAX_FILE_SIZE = 10 * 1024 * 1024;

            // Get references to the elements
            const fileInput = document.getElementById('file-upload');
            const previewContainer = document.getElementById('preview-container');
            const filePreview = document.getElementById('file-preview');

            // Set up event handlers for removing existing files
            const removeButtons = document.querySelectorAll('.remove-file');
            removeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const fileItem = this.closest('.file-item');
                    const filePath = this.getAttribute('data-file-path');

                    // Create a hidden field to track removed files
                    const removedFileInput = document.createElement('input');
                    removedFileInput.type = 'hidden';
                    removedFileInput.name = 'removed_files[]';
                    removedFileInput.value = filePath;

                    // Add it to the container
                    document.getElementById('removed-files-container').appendChild(removedFileInput);

                    // Remove the file item from the UI
                    fileItem.remove();

                    // Hide the preview container if no files left
                    if (document.querySelectorAll('.file-item').length === 0) {
                        document.getElementById('file-preview').classList.add('hidden');
                    }
                });
            });

            // Function to handle file selection
            function handleFileSelect(event) {
                const files = event.target.files;
                const currentFiles = previewContainer.querySelectorAll('.file-item');

                // Get count of both existing files and new ones
                const existingFilesCount = document.querySelectorAll('input[name="existing_portfolio[]"]').length;
                const remainingSlots = MAX_FILES - existingFilesCount;

                // Check if adding new files will exceed the maximum limit
                if (files.length > remainingSlots) {
                    showMaxFilesAlert();
                    return;
                }

                // Process each selected file
                for (let i = 0; i < files.length; i++) {
                    const file = files[i];

                    // Check file type
                    if (!file.type.match('image/jpeg') && !file.type.match('image/png') && !file.type.match(
                            'application/pdf')) {
                        showFileTypeAlert();
                        continue;
                    }

                    // Check file size
                    if (file.size > MAX_FILE_SIZE) {
                        showFileSizeAlert();
                        continue;
                    }

                    // Create a file preview item
                    const fileItem = document.createElement('div');
                    fileItem.className = 'file-item flex items-center p-2 bg-white rounded border border-neutral-200';
                    fileItem.innerHTML = `
                            <div class="w-12 h-12 rounded overflow-hidden mr-3">
                                <img src="" alt="Preview" class="w-full h-full object-cover">
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-neutral-700 truncate">${file.name}</p>
                                <p class="text-xs text-neutral-500">Size ${formatFileSize(file.size)}</p>
                            </div>
                            <button type="button" class="text-neutral-400 hover:text-red-500 ml-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        `;

                    // Add remove functionality
                    const removeButton = fileItem.querySelector('button');
                    removeButton.addEventListener('click', function() {
                        fileItem.remove();
                        if (previewContainer.children.length === 0) {
                            filePreview.classList.add('hidden');
                        }
                    });

                    // Add to preview container
                    previewContainer.appendChild(fileItem);

                    // Create image preview
                    const img = fileItem.querySelector('img');
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        img.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }

                // Show preview area if there are files
                if (previewContainer.children.length > 0) {
                    filePreview.classList.remove('hidden');
                }
            }

            // Function to format file size
            function formatFileSize(bytes) {
                if (bytes < 1024) return `${bytes} B`;
                if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
                return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
            }

            // Add event listener to file input
            fileInput.addEventListener('change', handleFileSelect);
        </script>
    </section>
</x-app-layout>
