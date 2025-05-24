<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-tertiary font-bold text-2xl text-primary leading-tight flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="h-6 w-6 mr-2">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                </svg>
                Engagement Dispute
            </h2>
        </div>
    </x-slot>

    <!--  Payment Dispute Form -->
    <div class="min-h-screen bg-gradient-to-br from-primary/5 to-neutral-100 font-main py-12">
        <div class="container mx-auto px-4">
            <div class="max-w-5xl mx-auto bg-white rounded-xl shadow-lg overflow-hidden">
                <!-- Header Section with Gradient Background -->
                <div class="bg-gradient-to-r from-primary to-primary/80 p-6 text-white">
                    <div class="flex items-center space-x-4">
                        <div class="p-3 bg-white/20 rounded-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold font-tertiary tracking-tight">Payment Dispute</h1>
                            <p class="text-white/80 mt-1 font-secondary">
                                For payment amount: Ksh{{ number_format($payment->amount, 2) }} for job
                                "{{ $engagement->application->job->title }}"
                            </p>
                        </div>
                    </div>
                </div>

                <div class="p-8">
                    <!-- Alert Notice -->
                    <div class="flex items-start space-x-4 bg-accent/10 border-l-4 border-accent rounded-r-lg p-4 mb-8">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-accent flex-shrink-0"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path
                                d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z">
                            </path>
                            <line x1="12" y1="9" x2="12" y2="13"></line>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                        <p class="text-sm text-neutral-700">
                            Once submitted, this dispute will be reviewed by platform administrators. Please provide
                            accurate and detailed information.
                        </p>
                    </div>

                    <!-- Form Section -->
                    <form action="{{ route('engagements.process-dispute-partial-payment', $payment->id) }}"
                        method="POST" enctype="multipart/form-data" class="space-y-8">
                        @csrf

                        <!-- Reason for Dispute -->
                        <div class="space-y-2">
                            <label for="reason" class="block text-sm font-medium text-neutral-700 flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-secondary"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                                    <polyline points="15 3 21 3 21 9"></polyline>
                                    <line x1="10" y1="14" x2="21" y2="3"></line>
                                </svg>
                                Reason for Dispute
                            </label>
                            <div class="relative">
                                <select id="reason" name="reason"
                                    class="appearance-none block w-full px-4 py-3 border border-neutral-300 rounded-lg shadow-sm focus:ring-2 focus:ring-secondary focus:border-secondary transition duration-200 text-neutral-700 bg-white"
                                    required>
                                    <option value="">Select a reason</option>
                                    <option value="incorrect_amount">Incorrect Amount</option>
                                    <option value="work_not_considered">Work Completed Not Considered</option>
                                    <option value="agreement_violated">Agreement Terms Violated</option>
                                    <option value="cancellation_dispute">Cancellation Terms Dispute</option>
                                    <option value="other">Other (please specify)</option>
                                </select>
                            </div>
                            @error('reason')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Detailed Explanation -->
                        <div class="space-y-2">
                            <label for="details" class="block text-sm font-medium text-neutral-700 flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-secondary"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                    <line x1="16" y1="13" x2="8" y2="13"></line>
                                    <line x1="16" y1="17" x2="8" y2="17"></line>
                                    <polyline points="10 9 9 9 8 9"></polyline>
                                </svg>
                                Detailed Explanation
                            </label>
                            <textarea id="details" name="details" rows="6"
                                class="shadow-sm block w-full px-4 py-3 border border-neutral-300 rounded-lg focus:ring-2 focus:ring-secondary focus:border-secondary transition duration-200"
                                placeholder="Please provide a detailed explanation of your dispute, including any relevant timeline, agreements, or information that supports your case."
                                required>{{ old('details') }}</textarea>
                            @error('details')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Supporting Evidence -->
                        <div class="space-y-2">
                            <label for="evidence"
                                class="block text-sm font-medium text-neutral-700 flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-secondary"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                                    <polyline points="13 2 13 9 20 9"></polyline>
                                </svg>
                                Supporting Evidence (Optional)
                            </label>
                            <div
                                class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-neutral-300 border-dashed rounded-lg bg-neutral-50 hover:bg-neutral-100 transition duration-200">
                                <div class="space-y-1 text-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-neutral-400"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="17 8 12 3 7 8"></polyline>
                                        <line x1="12" y1="3" x2="12" y2="15"></line>
                                    </svg>
                                    <div class="flex text-sm text-neutral-600 justify-center">
                                        <label for="file-upload"
                                            class="relative cursor-pointer rounded-md font-medium text-secondary hover:text-secondary/80 focus-within:outline-none">
                                            <span>Upload a file</span>
                                            <input id="file-upload" name="evidence" type="file" class="sr-only">
                                        </label>
                                        <p class="pl-1">or drag and drop</p>
                                    </div>
                                    <p class="text-xs text-neutral-500">
                                        PNG, JPG, PDF, DOC up to 10MB
                                    </p>
                                </div>
                            </div>
                            <p class="text-xs text-neutral-500 flex items-start mt-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-neutral-400 mt-0.5"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="16" x2="12" y2="12"></line>
                                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                </svg>
                                Recommended: screenshots, communication logs, delivered work samples, or any other
                                relevant documentation.
                            </p>
                            @error('evidence')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Acknowledgment Checkbox -->
                        <div class="flex items-start space-x-4 bg-neutral-50 p-4 rounded-lg border border-neutral-200">
                            <div class="flex-shrink-0 mt-0.5">
                                <input id="acknowledgment" name="acknowledgment" type="checkbox"
                                    class="h-5 w-5 rounded border-neutral-300 text-secondary focus:ring-secondary"
                                    required>
                            </div>
                            <div class="flex-1">
                                <label for="acknowledgment" class="font-medium text-neutral-700 text-sm">I acknowledge
                                    that</label>
                                <p class="text-neutral-600 text-sm mt-1">I have provided accurate information, and I
                                    understand that filing frivolous disputes may affect my account standing.</p>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="pt-4 flex justify-between items-center border-t border-neutral-200">
                            <a href="{{ route('engagements.show-cancelled', $engagement->id) }}"
                                class="px-5 py-2.5 border border-neutral-300 rounded-lg shadow-sm text-sm font-medium text-neutral-700 bg-white hover:bg-neutral-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500 transition duration-200">
                                Cancel
                            </a>
                            <button type="submit"
                                class="px-5 py-2.5 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-secondary hover:bg-secondary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-secondary transition duration-200 flex items-center">
                                <span>Submit Dispute</span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-2" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                    <polyline points="12 5 19 12 12 19"></polyline>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Decorative design element -->
            <div class="flex justify-center mt-8">
                <div class="flex space-x-2">
                    <div class="h-2 w-2 rounded-full bg-secondary"></div>
                    <div class="h-2 w-2 rounded-full bg-primary"></div>
                    <div class="h-2 w-2 rounded-full bg-accent"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Enhanced File Upload Preview Script with File Storage
        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.getElementById('file-upload');
            const filePreviewContainer = document.createElement('div');
            filePreviewContainer.className = 'file-preview-container mt-3 space-y-2';

            // Insert the preview container after the upload area
            const uploadArea = fileInput.closest('div').parentElement.parentElement;
            uploadArea.parentNode.insertBefore(filePreviewContainer, uploadArea.nextSibling);

            // Store uploaded files for form submission
            const uploadedFiles = new Map();

            // File size formatter
            function formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';

                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));

                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }

            // Create file preview element
            function createFilePreview(file) {
                const fileId = 'file-' + Date.now().toString(36) + Math.random().toString(36).substr(2);

                // Store the file for later use
                uploadedFiles.set(fileId, file);

                // Create preview element
                const previewElement = document.createElement('div');
                previewElement.id = fileId;
                previewElement.className =
                    'flex items-center justify-between p-3 bg-white border border-neutral-200 rounded-lg shadow-sm';

                // Left side with file icon and info
                const fileInfo = document.createElement('div');
                fileInfo.className = 'flex items-center space-x-3';

                // Determine file icon based on type
                const fileIconSVG = getFileIconSVG(file.type);

                fileInfo.innerHTML = `
            <div class="text-neutral-500">
                ${fileIconSVG}
            </div>
            <div>
                <p class="text-sm font-medium text-neutral-700 truncate max-w-xs">${file.name}</p>
                <p class="text-xs text-neutral-500">${formatFileSize(file.size)}</p>
            </div>
        `;

                // Remove button
                const removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.className = 'text-neutral-400 hover:text-red-500 transition-colors duration-200';
                removeButton.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        `;

                // Add remove event listener
                removeButton.addEventListener('click', function() {
                    // Remove file from storage
                    uploadedFiles.delete(fileId);
                    previewElement.remove();

                    // Show or hide status message
                    updateStatusMessage();
                });

                // Assemble preview element
                previewElement.appendChild(fileInfo);
                previewElement.appendChild(removeButton);

                return previewElement;
            }

            // Get appropriate icon SVG based on file type
            function getFileIconSVG(fileType) {
                if (fileType.startsWith('image/')) {
                    return `
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                    <polyline points="21 15 16 10 5 21"></polyline>
                </svg>
            `;
                } else if (fileType === 'application/pdf') {
                    return `
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <path d="M9 15v-2h6v2"></path>
                    <path d="M12 13v5"></path>
                </svg>
            `;
                } else if (fileType === 'application/msword' || fileType ===
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
                    return `
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
            `;
                } else {
                    return `
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                    <polyline points="13 2 13 9 20 9"></polyline>
                </svg>
            `;
                }
            }

            // Add status message container
            const statusMessageContainer = document.createElement('div');
            statusMessageContainer.className = 'text-sm text-secondary mt-2 hidden';
            statusMessageContainer.innerHTML =
                '<span class="flex items-center"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg> File ready for upload</span>';
            filePreviewContainer.parentNode.insertBefore(statusMessageContainer, filePreviewContainer.nextSibling);

            // Update status message
            function updateStatusMessage() {
                if (uploadedFiles.size > 0) {
                    statusMessageContainer.classList.remove('hidden');
                } else {
                    statusMessageContainer.classList.add('hidden');
                }
            }

            // Check file size and type validation
            function validateFile(file) {
                const maxSize = 10 * 1024 * 1024; // 10MB
                const allowedTypes = ['image/jpeg', 'image/png', 'application/pdf', 'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                ];

                if (file.size > maxSize) {
                    showError('File is too large. Maximum size is 10MB.');
                    return false;
                }

                if (!allowedTypes.includes(file.type)) {
                    showError('Invalid file type. Allowed types: JPG, PNG, PDF, DOC, DOCX.');
                    return false;
                }

                return true;
            }

            // Show error message
            function showError(message) {
                const errorElement = document.createElement('div');
                errorElement.className = 'text-sm text-red-600 mt-2 flex items-center';
                errorElement.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            ${message}
        `;

                // Insert error message and remove after 3 seconds
                filePreviewContainer.parentNode.insertBefore(errorElement, filePreviewContainer.nextSibling);

                setTimeout(() => {
                    errorElement.remove();
                }, 3000);
            }

            // Handle file selection
            fileInput.addEventListener('change', function(e) {
                const files = e.target.files;

                if (files.length > 0) {
                    const file = files[0];

                    // Validate file
                    if (validateFile(file)) {
                        // Create preview for the newly selected file
                        const filePreview = createFilePreview(file);
                        filePreviewContainer.appendChild(filePreview);

                        // Show status message
                        updateStatusMessage();
                    }

                    // Clear the file input to allow for handling the files manually
                    e.target.value = '';
                }
            });

            // Handle drag and drop
            const dropArea = fileInput.parentElement.parentElement.parentElement;

            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropArea.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            ['dragenter', 'dragover'].forEach(eventName => {
                dropArea.addEventListener(eventName, highlight, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropArea.addEventListener(eventName, unhighlight, false);
            });

            function highlight() {
                dropArea.classList.add('border-secondary', 'bg-secondary/5');
                dropArea.classList.remove('border-neutral-300', 'bg-neutral-50');
            }

            function unhighlight() {
                dropArea.classList.remove('border-secondary', 'bg-secondary/5');
                dropArea.classList.add('border-neutral-300', 'bg-neutral-50');
            }

            dropArea.addEventListener('drop', handleDrop, false);

            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;

                if (files.length > 0) {
                    const file = files[0];

                    // Validate file
                    if (validateFile(file)) {
                        // Create preview for the dropped file
                        const filePreview = createFilePreview(file);
                        filePreviewContainer.appendChild(filePreview);

                        // Show status message
                        updateStatusMessage();
                    }
                }
            }

            // Handle form submission
            const form = fileInput.closest('form');
            form.addEventListener('submit', handleFormSubmission);

            function handleFormSubmission(e) {
                // Only intercept if we have files
                if (uploadedFiles.size > 0) {
                    e.preventDefault();

                    // Create a FormData object and append all form fields
                    const formData = new FormData(form);

                    // Remove the original file input data
                    formData.delete('evidence');

                    // Get the first file (since we only allow one)
                    const fileEntry = uploadedFiles.entries().next().value;
                    if (fileEntry) {
                        const file = fileEntry[1];
                        formData.append('evidence', file);
                    }

                    // Submit the form with fetch
                    fetch(form.action, {
                        method: form.method,
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                'content')
                        }
                    }).then(response => {
                        if (response.redirected) {
                            window.location.href = response.url;
                        } else {
                            return response.json();
                        }
                    }).then(data => {
                        if (data && !data.success) {
                            // Handle validation errors
                            if (data.errors) {
                                Object.keys(data.errors).forEach(key => {
                                    showError(data.errors[key][0]);
                                });
                            } else {
                                showError('An error occurred. Please try again.');
                            }
                        }
                    }).catch(error => {
                        console.error('Error:', error);
                        showError('An error occurred while submitting the form.');
                    });
                }
                // If no files, let the form submit normally
            }
        });
    </script>
</x-app-layout>
