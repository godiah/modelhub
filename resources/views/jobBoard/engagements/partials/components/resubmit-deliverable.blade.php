<!-- Resubmit Deliverable -->
@foreach ($engagement->deliverables as $deliverable)
    <x-modal name="resubmit-deliverable-{{ $deliverable->id }}" :show="false" max-width="2xl" focusable>
        <div class="p-8 font-main">
            <!-- Header -->
            <div class="text-center mb-6">
                <div class="flex items-center justify-center text-secondary mb-4">
                    <x-icon name="cloud-arrow-up" class="h-16 w-16" />
                </div>
                <h2 class="text-xl font-bold text-neutral-800">Resubmit Deliverable
                </h2>
                <p class="text-neutral-600 mt-2">Upload updated files and notes for
                    the
                    deliverable <br> <span class="font-medium underline">{{ $deliverable->title }}</span>
                </p>
            </div>

            <!-- Divider -->
            <div class="border-t border-neutral-200 my-6"></div>

            <!-- Form -->
            <form action="{{ route('engagements.deliverables.submit', $deliverable) }}" method="POST"
                enctype="multipart/form-data">
                @csrf

                <!-- Rejection Notice -->
                @if ($deliverable->rejected_at)
                    <div class="mb-4 p-3 bg-accent/10 border border-accent/20 rounded-md">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <x-icon name="exclamation-triangle" class="h-5 w-5 text-accent" />
                            </div>
                            <div class="ml-3">
                                <h3 class="font-tertiary font-semibold text-sm text-neutral-800">
                                    Deliverable Rejected</h3>
                                <div class="mt-1 text-sm text-neutral-600 font-main">
                                    <p>This deliverable has been rejected. Please
                                        resubmit
                                        with improved files.</p>
                                    @if ($deliverable->rejection_reason)
                                        <p class="mt-2 italic">Reason:
                                            "{{ $deliverable->rejection_reason }}"</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- File Upload Section -->
                <div class="mb-5">
                    <label for="submission_files" class="block text-sm font-medium text-neutral-700 mb-2">
                        {{ $deliverable->rejected_at ? 'Resubmit Files' : 'Upload Files' }}
                    </label>
                    <div x-data="{
                        files: [],
                        previewImages: [],
                        removeFile(index) {
                            this.files.splice(index, 1);
                            this.previewImages.splice(index, 1);
                            if (this.$refs.fileInput.files) {
                                const dt = new DataTransfer();
                                Array.from(this.$refs.fileInput.files)
                                    .filter((_, i) => i !== index)
                                    .forEach(file => dt.items.add(file));
                                this.$refs.fileInput.files = dt.files;
                            }
                        },
                        processFiles(newFiles) {
                            const maxFiles = 5;
                            if (this.files.length + newFiles.length > maxFiles) {
                                alert('You can only upload a maximum of ' + maxFiles + ' files.');
                                return false;
                            }
                    
                            // Process new files and create previews
                            newFiles.forEach(file => {
                                this.files.push(file);
                    
                                // Generate preview for images
                                if (file.type.startsWith('image/')) {
                                    const reader = new FileReader();
                                    reader.onload = (e) => {
                                        this.previewImages.push({
                                            index: this.previewImages.length,
                                            src: e.target.result,
                                            isImage: true,
                                            file: file
                                        });
                                    };
                                    reader.readAsDataURL(file);
                                } else {
                                    // Non-image file
                                    this.previewImages.push({
                                        index: this.previewImages.length,
                                        src: null,
                                        isImage: false,
                                        file: file
                                    });
                                }
                            });
                    
                            return true;
                        }
                    }"
                        class="border-2 rounded-lg p-6 text-center transition-all duration-200 border-neutral-300 bg-neutral-50">
                        <div class="flex flex-col items-center justify-center space-y-4">
                            <x-icon name="cloud-arrow-up" class="h-10 w-10 text-secondary" />
                            <div class="space-y-2">
                                <p class="font-tertiary font-semibold text-sm text-neutral-700">
                                    {{ $deliverable->rejected_at ? 'Select files to upload' : 'Select files to upload' }}
                                </p>
                                <p class="text-xs text-neutral-500 font-main">
                                    Support for common file types (PDF, DOC, XLS, JPG,
                                    PNG, etc.)
                                </p>
                                <p class="text-xs text-neutral-500 font-main">Upload up
                                    to 5 files</p>

                                <button type="button" @click="$refs.fileInput.click()"
                                    class="mt-2 px-4 py-2 bg-white border border-neutral-300 rounded-md hover:bg-neutral-50 text-sm font-medium text-neutral-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-secondary">
                                    Browse Files
                                </button>
                            </div>
                        </div>
                        <input type="file" name="submission_files[]" multiple x-ref="fileInput" class="hidden"
                            @change="
                                                                    const newFiles = [...$event.target.files];
                                                                    if (processFiles(newFiles)) {
                                                                        // Keep the input files synchronized
                                                                        const dt = new DataTransfer();
                                                                        files.forEach(file => dt.items.add(file));
                                                                        $refs.fileInput.files = dt.files;
                                                                    } else {
                                                                        $event.target.value = null;
                                                                    }
                                                                ">

                        <!-- File Preview -->
                        <div x-show="files.length > 0" class="mt-4">
                            <p class="text-xs text-neutral-600 font-main mb-2">
                                <span x-text="files.length"></span>/5 files selected
                                <span x-show="files.length >= 5" class="text-red-500 ml-1">Maximum limit
                                    reached</span>
                            </p>

                            <!-- Image Preview Grid -->
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-3"
                                x-show="previewImages.some(item => item.isImage)">
                                <template x-for="(item, index) in previewImages" :key="index">
                                    <div x-show="item.isImage"
                                        class="relative aspect-square rounded-md overflow-hidden border border-neutral-200 bg-white">
                                        <img :src="item.src" class="w-full h-full object-cover">
                                        <button type="button" @click="removeFile(index)"
                                            class="absolute top-1 right-1 bg-black/50 text-white rounded-full p-1 hover:bg-black/80">
                                            <x-icon name="x-mark" class="h-4 w-4" />
                                        </button>
                                    </div>
                                </template>
                            </div>

                            <!-- File List -->
                            <div class="space-y-2">
                                <template x-for="(item, index) in previewImages" :key="index">
                                    <div
                                        class="flex items-center justify-between bg-white p-2.5 rounded-md border border-neutral-200">
                                        <div class="flex items-center space-x-2">
                                            <!-- Icon based on file type -->
                                            <div class="flex-shrink-0">
                                                <x-icon name="photo" x-show="item.isImage" class="h-5 w-5 text-blue-500" />
                                                <x-icon name="document-text" x-show="!item.isImage" class="h-5 w-5 text-neutral-500" />
                                            </div>

                                            <!-- File info -->
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm text-neutral-700 font-main truncate"
                                                    x-text="item.file.name"></p>
                                                <p class="text-xs text-neutral-500 font-main"
                                                    x-text="(item.file.size / 1024).toFixed(1) + ' KB'">
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Remove button -->
                                        <button type="button" @click="removeFile(index)"
                                            class="ml-2 text-red-500 hover:text-red-700">
                                            <x-icon name="x-mark" class="h-4 w-4" />
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notes Field -->
                <div class="mb-6">
                    <label for="submission_notes" class="block text-sm font-medium text-neutral-700 mb-2">
                        {{ $deliverable->rejected_at ? 'Updated Notes (Optional)' : 'Submission Notes (Optional)' }}
                    </label>
                    <textarea id="submission_notes" name="submission_notes" rows="4"
                        class="w-full rounded-lg border-neutral-300 shadow-sm focus:border-secondary resize-none"
                        placeholder="{{ $deliverable->rejected_at ? 'Add notes about your updated submission here...' : 'Add any notes about your submission here...' }}"></textarea>
                </div>

                <!-- Previous Submission -->
                @if ($deliverable->rejected_at && ($deliverable->submission_files || $deliverable->submission_notes))
                    <div class="p-4 bg-neutral-100 rounded-md border border-neutral-200 mb-6">
                        <div class="flex items-center mb-3">
                            <x-icon name="clock" class="h-5 w-5 text-neutral-500 mr-2" />
                            <h6 class="font-tertiary font-semibold text-sm text-neutral-700">
                                Previous Submission</h6>
                        </div>
                        @if ($deliverable->submission_notes)
                            <div class="mb-3">
                                <h6 class="text-xs font-tertiary font-semibold text-neutral-600 mb-1">
                                    Notes:</h6>
                                <p
                                    class="text-xs text-neutral-600 font-main bg-white p-2 rounded border border-neutral-200">
                                    {{ $deliverable->submission_notes }}</p>
                            </div>
                        @endif
                        @if ($deliverable->submission_files && count($deliverable->submission_files) > 0)
                            <div>
                                <h6 class="text-xs font-tertiary font-semibold text-neutral-600 mb-1">
                                    Files:</h6>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    @foreach ($deliverable->submission_files as $file)
                                        <a href="{{ Storage::url($file['path']) }}" target="_blank"
                                            class="flex items-center p-1.5 bg-white border border-neutral-200 rounded-md hover:bg-neutral-50 transition-colors">
                                            <div class="rounded-md bg-neutral-100 p-1 mr-2">
                                                <x-icon name="arrow-uturn-down" class="h-3 w-3 text-neutral-500" />
                                            </div>
                                            <div class="truncate">
                                                <span
                                                    class="text-xs font-main text-neutral-600">{{ $file['name'] }}</span>
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Actions -->
                <div class="flex justify-end gap-3">
                    <button type="button"
                        @click="$dispatch('close-modal', 'resubmit-deliverable-{{ $deliverable->id }}')"
                        class="px-4 py-2 text-sm font-medium text-neutral-700 bg-white border border-neutral-300 rounded-md hover:bg-neutral-50 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-secondary rounded-md hover:bg-secondary/90 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-secondary">
                        <x-icon name="check" class="h-4 w-4 mr-1.5 inline" />
                        Resubmit Deliverable
                    </button>
                </div>
            </form>
        </div>
    </x-modal>
@endforeach
