@use('App\Enums\EngagementStatus')
@if ($engagements->isEmpty() && $hasFilters)
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-neutral-200">
        <div class="p-12 flex flex-col items-center justify-center text-center font-main">
            <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center mb-6">
                <x-icon name="chat-bubble-text" class="w-8 h-8 text-primary" />
            </div>

            <h3 class="text-lg font-semibold text-neutral-800 mb-2">No Engagements Found</h3>
            <p class="text-neutral-600 mb-6 max-w-md">
                We couldn't find any present engagements matching your current search criteria. Try adjusting your
                filters or
                search terms.
            </p>

            <a href="#" id="clearEngagementFilters"
                class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors duration-150">
                <x-icon name="arrow-path" class="w-4 h-4 mr-2" />
                Clear All Filters
            </a>
        </div>
    </div>
@else
    <div class="space-y-6">
        @foreach ($engagements as $engagement)
            @php
                $currentUser = Auth::user();
                $isApplicant = $engagement->application->applicant_id === $currentUser->id;
                $isPoster = $engagement->application->poster_id === $currentUser->id;

                $totalDeliverables = $engagement->deliverables->count();
                $completedDeliverables = $engagement->deliverables->where('submitted_at', '!=', null)->count();
                $progressPercentage = $totalDeliverables > 0 ? ($completedDeliverables / $totalDeliverables) * 100 : 0;
            @endphp

            <div x-data="{ open: false }"
                class="bg-white border border-neutral-200 rounded-xl shadow-sm overflow-hidden transition-all duration-200 hover:shadow-md">
                <!-- Engagement Header - Always visible -->
                <div @click="open = !open" class="cursor-pointer">
                    <div class="p-6 flex flex-col sm:flex-row sm:items-start justify-between">
                        <div class="flex-1">
                            <div class="flex flex-col sm:flex-row sm:items-center">
                                <h3 class="font-tertiary font-bold text-lg text-primary flex items-center">
                                    <x-icon name="document-text" class="h-5 w-5 mr-2 flex-shrink-0" />
                                    {{ $engagement->application->job->title }}
                                </h3>
                                <span
                                    class="sm:ml-3 mt-1 sm:mt-0 inline-flex items-center px-3 py-1 text-xs font-medium font-main rounded-full border {{ $engagement->getStatusClasses()['bg'] }} {{ $engagement->getStatusClasses()['text'] }} {{ $engagement->getStatusClasses()['border'] }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-1" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        {!! $engagement->statusIconPath !!}
                                    </svg>
                                    {{ $engagement->statusLabel }}
                                </span>
                            </div>
                        </div>

                        <div class="sm:ml-4 flex font-main">
                            @if ($isApplicant && $engagement->status === EngagementStatus::EmployerAccepted)
                                <a href="{{ route('engagements.response-form', ['applicationId' => $engagement->application_id]) }}"
                                    class="mr-3 inline-flex items-center px-3 py-1 text-xs font-medium text-primary bg-primary/10 rounded-full border border-primary/20 hover:bg-primary/20 transition-colors">
                                    <x-icon name="check" class="h-3.5 w-3.5 mr-1" />
                                    Respond
                                </a>
                            @endif

                            <button class="flex items-center text-neutral-500 hover:text-neutral-700" x-cloak>
                                <span class="text-sm mr-1" x-text="open ? 'Hide Details' : 'View Details'"></span>
                                <x-icon name="chevron-down" class="h-5 w-5 transition-transform duration-200" ::class="{ 'transform rotate-180': open }" />
                            </button>
                        </div>
                    </div>

                    <!-- Progress bar -->
                    <div class="px-6 pb-4 font-main" x-cloak>
                        <div class="h-1.5 w-full bg-neutral-100 rounded-full overflow-hidden">
                            <div class="h-full bg-secondary rounded-full" style="width: {{ $progressPercentage }}%">
                            </div>
                        </div>
                        <div class="flex justify-between mt-1 text-xs text-neutral-500">
                            <span>Progress</span>
                            <span>{{ $completedDeliverables }}/{{ $totalDeliverables }} Deliverables</span>
                        </div>
                    </div>
                </div>

                <!-- Expandable Content -->
                <div x-show="open" x-collapse x-cloak class="border-t border-neutral-200 bg-neutral-50">
                    <!-- Engagement Details Row -->
                    @include('jobBoard.engagements.partials.components.engagement-details')

                    @if ($engagement->status !== EngagementStatus::Disputed)
                        @if ($isApplicant)
                            <div class="p-6">
                                <h4 class="font-tertiary font-semibold text-primary mb-4 flex items-center">
                                    <x-icon name="clipboard-list" class="h-5 w-5 mr-2" />
                                    Project Deliverables
                                </h4>

                                <div class="grid grid-cols-1 gap-4">
                                    @forelse($engagement->deliverables as $deliverable)
                                        <div x-data="{ expanded: false, fileSubmissionOpen: false }"
                                            class="bg-white p-5 border border-neutral-200 rounded-lg shadow-sm hover:shadow transition-all duration-200 mb-4">

                                            <div @click="expanded = !expanded" class="pb-2 cursor-pointer">
                                                <div class="flex justify-between items-start">
                                                    <div class="flex items-center">
                                                        <div
                                                            class="rounded-full p-1.5 mr-3
                                                                @if ($deliverable->approved_at) bg-green-100 text-green-800 
                                                                @elseif ($deliverable->rejected_at) bg-red-100 text-red-800
                                                                @elseif ($deliverable->submitted_at) bg-secondary/10 text-secondary
                                                                @elseif($deliverable->due_date && now()->gt($deliverable->due_date)) bg-red-100 text-red-600
                                                                @else bg-neutral-200 text-neutral-500 @endif">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                                                                fill="none" viewBox="0 0 24 24"
                                                                stroke="currentColor">
                                                                @if ($deliverable->approved_at)
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2"
                                                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                @elseif ($deliverable->rejected_at)
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round" stroke-width="2"
                                                                        d="M6 18L18 6M6 6l12 12" />
                                                                @elseif ($deliverable->submitted_at)
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round" stroke-width="2"
                                                                        d="M5 13l4 4L19 7" />
                                                                @elseif($deliverable->due_date && now()->gt($deliverable->due_date))
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round" stroke-width="2"
                                                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                                @else
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round" stroke-width="2"
                                                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                                                @endif
                                                            </svg>
                                                        </div>
                                                        <h5 class="font-tertiary font-bold text-neutral-800">
                                                            {{ $deliverable->title }}
                                                        </h5>
                                                    </div>
                                                    <span
                                                        class="px-2.5 py-1 text-xs font-medium font-tertiary rounded-full flex items-center 
                                                            @if ($deliverable->approved_at) bg-green-100 text-green-800
                                                            @elseif($deliverable->rejected_at)
                                                                bg-red-100 text-red-800
                                                            @elseif($deliverable->submitted_at)
                                                                bg-secondary/10 text-secondary
                                                            @elseif ($deliverable->isOverdue())
                                                                bg-red-100 text-red-600
                                                            @else
                                                                bg-neutral-200 text-neutral-600 @endif">
                                                        @if ($deliverable->approved_at)
                                                            <x-icon name="check-circle" class="h-3 w-3 mr-1" />
                                                            Approved
                                                        @elseif($deliverable->rejected_at)
                                                            <x-icon name="x-mark" class="h-3 w-3 mr-1" />
                                                            Rejected
                                                        @elseif($deliverable->submitted_at)
                                                            <x-icon name="check" class="h-3 w-3 mr-1" />
                                                            Submitted
                                                        @elseif($deliverable->isOverdue())
                                                            <x-icon name="exclamation-triangle" class="h-4 w-4 mr-1" />
                                                            Overdue
                                                        @else
                                                            Pending
                                                        @endif
                                                    </span>
                                                </div>

                                                <p class="font-main text-justify text-sm text-neutral-600 mt-3 ml-9">
                                                    {{ $deliverable->description }}</p>

                                                <div
                                                    class="mt-3 ml-9 flex flex-wrap gap-y-2 gap-x-4 text-xs font-main">
                                                    @if ($deliverable->due_date)
                                                        <div
                                                            class="flex items-center {{ $deliverable->isOverdue() ? 'text-red-500' : 'text-secondary' }}">
                                                            <x-icon name="calendar" class="h-3.5 w-3.5 mr-1" />
                                                            Due:
                                                            <x-date :date="$deliverable->due_date" />
                                                            @if ($deliverable->isOverdue())
                                                                <span class="ml-1 text-xs">(Overdue)</span>
                                                            @endif
                                                        </div>
                                                    @else
                                                        <div class="flex items-center text-secondary">
                                                            <x-icon name="calendar" class="h-3.5 w-3.5 mr-1" />
                                                            Due Date Not Set
                                                        </div>
                                                    @endif

                                                    @if ($deliverable->submitted_at)
                                                        <div class="flex items-center text-secondary">
                                                            <x-icon name="check-circle" class="h-3.5 w-3.5 mr-1" />
                                                            Completed:
                                                            <x-date :date="$deliverable->submitted_at" />
                                                        </div>
                                                    @endif

                                                    @if ($deliverable->isApproved())
                                                        <div class="flex items-center text-secondary">
                                                            <x-icon name="check-circle" class="h-3.5 w-3.5 mr-1" />
                                                            Approved:
                                                            <x-date :date="$deliverable->approved_at" />
                                                        </div>
                                                    @endif

                                                    @if ($deliverable->rejected_at)
                                                        <div class="flex items-center text-red-800">
                                                            <x-icon name="x-mark" class="h-3.5 w-3.5 mr-1" />
                                                            Rejected:
                                                            <x-date :date="$deliverable->rejected_at" />
                                                        </div>
                                                    @endif

                                                    @if ($deliverable->feedback)
                                                        <div class="flex items-center text-blue-800 cursor-pointer">
                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                                viewBox="0 0 24 24" stroke-width="1.5"
                                                                stroke="currentColor" class="h-3.5 w-3.5 mr-1">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
                                                            </svg>
                                                            View Feedback
                                                        </div>
                                                    @endif

                                                    @if (!$deliverable->submitted_at && !$deliverable->approved_at && !$deliverable->rejected_at)
                                                        <div @click.stop="fileSubmissionOpen = !fileSubmissionOpen"
                                                            class="flex items-center text-primary hover:text-primary/80 cursor-pointer ml-auto">
                                                            <x-icon name="cloud-arrow-up" class="h-3.5 w-3.5 mr-1" />
                                                            Submit Deliverable
                                                        </div>
                                                    @elseif ($deliverable->submission_files)
                                                        <div class="flex items-center text-primary">
                                                            <x-icon name="document-text" class="h-3.5 w-3.5 mr-1" />
                                                            {{ count($deliverable->submission_files) }}
                                                            {{ Str::plural('File', count($deliverable->submission_files)) }}
                                                            Submitted
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>

                                            @if ($deliverable->feedback)
                                                <div x-show="expanded" x-collapse
                                                    class="border-t border-neutral-200 p-5 mt-2 bg-neutral-50 rounded-b-md">
                                                    <h6
                                                        class="font-tertiary font-semibold text-sm text-neutral-700 mb-2">
                                                        Feedback:</h6>
                                                    <p class="text-neutral-600 text-sm font-main">
                                                        {{ $deliverable->feedback }}</p>
                                                </div>
                                            @endif

                                            @if ($deliverable->rejected_at)
                                                <div x-show="expanded" x-collapse
                                                    class="border-neutral-200 px-5 pb-5 bg-neutral-50 rounded-b-md">
                                                    <button
                                                        x-on:click="$dispatch('open-modal', 'resubmit-deliverable-{{ $deliverable->id }}')"
                                                        type="button"
                                                        class="px-4 py-2 bg-secondary hover:bg-secondary/90 text-white font-tertiary font-medium text-sm rounded-md shadow-sm transition-colors duration-200 flex items-center">
                                                        <x-icon name="cloud-arrow-up" class="h-4 w-4 mr-1.5" />
                                                        Resubmit Deliverable
                                                    </button>
                                                </div>
                                            @endif

                                            <!-- File Submission Section -->
                                            @if ((!$deliverable->submitted_at && !$deliverable->approved_at) || $deliverable->rejected_at)
                                                <div x-show="fileSubmissionOpen" x-collapse
                                                    class="border-t border-neutral-200 p-5 mt-2 bg-gradient-to-br from-neutral-50 to-neutral-100 rounded-b-md">

                                                    <!-- Rejection Notice (Only shown when rejected) -->
                                                    @if ($deliverable->rejected_at)
                                                        <div
                                                            class="mb-4 p-3 bg-accent/10 border border-accent/20 rounded-md">
                                                            <div class="flex items-start">
                                                                <div class="flex-shrink-0">
                                                                    <x-icon name="exclamation-triangle" class="h-5 w-5 text-accent" />
                                                                </div>
                                                                <div class="ml-3">
                                                                    <h3
                                                                        class="font-tertiary font-semibold text-sm text-neutral-800">
                                                                        Deliverable Rejected</h3>
                                                                    <div
                                                                        class="mt-1 text-sm text-neutral-600 font-main">
                                                                        <p>This deliverable has been rejected.
                                                                            Please resubmit with improved files.</p>
                                                                        @if ($deliverable->feedback)
                                                                            <p class="mt-2 italic">Reason:
                                                                                "{{ $deliverable->feedback }}"
                                                                            </p>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif

                                                    <form
                                                        action="{{ route('engagements.deliverables.submit', $deliverable) }}"
                                                        method="POST" enctype="multipart/form-data"
                                                        class="space-y-4">
                                                        @csrf

                                                        <div class="space-y-2">
                                                            <h6
                                                                class="font-tertiary font-semibold text-sm text-neutral-700 flex items-center">
                                                                <x-icon name="cloud-arrow-up" class="h-4 w-4 mr-1.5 text-secondary" />
                                                                {{ $deliverable->rejected_at ? 'Resubmit Files' : 'Upload Files' }}
                                                            </h6>

                                                            <div x-data="{
                                                                files: [],
                                                                dragOver: false,
                                                                removeFile(index) {
                                                                    this.files.splice(index, 1);
                                                                    if (this.$refs.fileInput.files) {
                                                                        // Create a new DataTransfer object
                                                                        const dt = new DataTransfer();
                                                            
                                                                        // Add all files except the one we want to remove
                                                                        Array.from(this.$refs.fileInput.files)
                                                                            .filter((_, i) => i !== index)
                                                                            .forEach(file => dt.items.add(file));
                                                            
                                                                        // Set the new FileList to the file input
                                                                        this.$refs.fileInput.files = dt.files;
                                                                    }
                                                                }
                                                            }" class="relative">
                                                                <div @dragover.prevent="dragOver = true"
                                                                    @dragleave.prevent="dragOver = false"
                                                                    @drop.prevent="
                                                                        dragOver = false;
                                                                        const maxFiles = 5;
                                                                        let droppedFiles = [...$event.dataTransfer.files];

                                                                        if (files.length + droppedFiles.length > maxFiles) {
                                                                            alert('You can only upload a maximum of ' + maxFiles + ' files.');
                                                                        } else {
                                                                            files = [...files, ...droppedFiles];

                                                                            // Create a new DataTransfer object to combine existing files with dropped files
                                                                            const dt = new DataTransfer();

                                                                            // Add existing files
                                                                            if ($refs.fileInput.files) {
                                                                                Array.from($refs.fileInput.files).forEach(file => dt.items.add(file));
                                                                            }

                                                                            // Add dropped files
                                                                            droppedFiles.forEach(file => dt.items.add(file));

                                                                            // Set the new FileList to the file input
                                                                            $refs.fileInput.files = dt.files;
                                                                        }
                                                                    "
                                                                    :class="{ 'border-secondary bg-secondary/5': dragOver }"
                                                                    class="border-2 border-dashed border-neutral-300 rounded-lg p-6 text-center transition-all duration-200 hover:border-secondary hover:bg-secondary/5 cursor-pointer"
                                                                    @click="$refs.fileInput.click()">

                                                                    <div
                                                                        class="flex flex-col items-center justify-center space-y-2">
                                                                        <x-icon name="cloud-arrow-up" class="h-10 w-10 text-secondary" />

                                                                        <div class="space-y-1">
                                                                            <p
                                                                                class="font-tertiary font-semibold text-sm text-neutral-700">
                                                                                {{ $deliverable->rejected_at ? 'Drag and drop updated files or click to replace' : 'Drag and drop files here or click to browse' }}
                                                                            </p>
                                                                            <p
                                                                                class="text-xs text-neutral-500 font-main">
                                                                                Support for common file types (PDF,
                                                                                DOC, XLS, JPG, PNG, etc.)
                                                                            </p>
                                                                            <p
                                                                                class="text-xs text-neutral-500 font-main">
                                                                                Upload up to 5 files (PDF, DOC, XLS,
                                                                                JPG, PNG, etc.)
                                                                            </p>
                                                                        </div>
                                                                    </div>

                                                                    <input type="file" name="submission_files[]"
                                                                        multiple x-ref="fileInput" class="hidden"
                                                                        @change="
                                                                        const maxFiles = 5;
                                                                        const newFiles = [...$event.target.files];
                                                                                                                
                                                                        if (files.length + newFiles.length > maxFiles) {
                                                                            alert('You can only upload a maximum of ' + maxFiles + ' files.');
                                                                            // Reset the file input
                                                                            $event.target.value = null;
                                                                        } else {
                                                                            // Create a new DataTransfer object
                                                                            const dt = new DataTransfer();
                                                                            
                                                                            // Add existing files that are already in our files array
                                                                            files.forEach(file => dt.items.add(file));
                                                                            
                                                                            // Add new files
                                                                            newFiles.forEach(file => dt.items.add(file));
                                                                            
                                                                            // Update the files array
                                                                            files = [...files, ...newFiles];
                                                                            
                                                                            // Set the new FileList to the file input
                                                                            $refs.fileInput.files = dt.files;
                                                                        }
                                                                    ">
                                                                </div>

                                                                <!-- File Preview -->
                                                                <div x-show="files.length > 0" class="mt-3">
                                                                    <p class="text-xs text-neutral-600 font-main mb-2">
                                                                        <span x-text="files.length"></span>/5 files
                                                                        selected
                                                                        <span x-show="files.length >= 5"
                                                                            class="text-red-500 ml-1">Maximum limit
                                                                            reached</span>
                                                                    </p>
                                                                    <template x-for="(file, index) in files"
                                                                        :key="index">
                                                                        <div
                                                                            class="flex items-center justify-between bg-white p-2 rounded-md border border-neutral-200 mb-2">
                                                                            <div class="flex items-center space-x-2">
                                                                                <x-icon name="document-text" class="h-5 w-5 text-neutral-500" />
                                                                                <span
                                                                                    class="text-sm text-neutral-700 font-main truncate max-w-sm"
                                                                                    x-text="file.name"></span>
                                                                                <span
                                                                                    class="text-xs text-neutral-500 font-main"
                                                                                    x-text="(file.size / 1024).toFixed(1) + ' KB'"></span>
                                                                            </div>
                                                                            <button type="button"
                                                                                @click="removeFile(index)"
                                                                                class="text-red-500 hover:text-red-700">
                                                                                <x-icon name="x-mark" class="h-4 w-4" />
                                                                            </button>
                                                                        </div>
                                                                    </template>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="space-y-2">
                                                            <h6
                                                                class="font-tertiary font-semibold text-sm text-neutral-700 flex items-center">
                                                                <x-icon name="pencil-square" class="h-4 w-4 mr-1.5 text-secondary" />
                                                                {{ $deliverable->rejected_at ? 'Updated Notes (Optional)' : 'Submission Notes (Optional)' }}
                                                            </h6>

                                                            <textarea name="submission_notes" rows="3"
                                                                class="w-full px-3 py-2 text-sm font-main text-neutral-700 border border-neutral-300 rounded-md focus:ring-2 focus:ring-secondary/30 focus:border-secondary transition-colors"
                                                                placeholder="{{ $deliverable->rejected_at ? 'Add notes about your updated submission here...' : 'Add any notes about your submission here...' }}"></textarea>
                                                        </div>

                                                        <!-- Previous submission (only show when rejected) -->
                                                        @if ($deliverable->rejected_at && ($deliverable->submission_files || $deliverable->submission_notes))
                                                            <div
                                                                class="p-4 bg-neutral-100 rounded-md border border-neutral-200">
                                                                <div class="flex items-center mb-3">
                                                                    <x-icon name="clock" class="h-5 w-5 text-neutral-500 mr-2" />
                                                                    <h6
                                                                        class="font-tertiary font-semibold text-sm text-neutral-700">
                                                                        Previous Submission</h6>
                                                                </div>

                                                                @if ($deliverable->submission_notes)
                                                                    <div class="mb-3">
                                                                        <h6
                                                                            class="text-xs font-tertiary font-semibold text-neutral-600 mb-1">
                                                                            Notes:</h6>
                                                                        <p
                                                                            class="text-xs text-neutral-600 font-main bg-white p-2 rounded border border-neutral-200">
                                                                            {{ $deliverable->submission_notes }}
                                                                        </p>
                                                                    </div>
                                                                @endif

                                                                @if ($deliverable->submission_files && count($deliverable->submission_files) > 0)
                                                                    <div>
                                                                        <h6
                                                                            class="text-xs font-tertiary font-semibold text-neutral-600 mb-1">
                                                                            Files:</h6>
                                                                        <div
                                                                            class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                                            @foreach ($deliverable->submission_files as $index => $file)
                                                                                <a href="{{ route('engagements.deliverables.download-file', [$deliverable->id, $index]) }}"
                                                                                    class="flex items-center p-1.5 bg-white border border-neutral-200 rounded-md hover:bg-neutral-50 transition-colors">
                                                                                    <div
                                                                                        class="rounded-md bg-neutral-100 p-1 mr-2">
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

                                                        <div class="flex justify-end">
                                                            <button type="submit"
                                                                class="px-4 py-2 bg-secondary hover:bg-secondary/90 text-white font-tertiary font-medium text-sm rounded-md shadow-sm transition-colors duration-200 flex items-center">
                                                                <x-icon name="check" class="h-4 w-4 mr-1.5" />
                                                                {{ $deliverable->rejected_at ? 'Resubmit Deliverable' : 'Submit Deliverable' }}
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            @endif

                                            <!-- Display submission information if already submitted -->
                                            @if (
                                                $deliverable->submitted_at &&
                                                    !$deliverable->rejected_at &&
                                                    ($deliverable->submission_files || $deliverable->submission_notes))
                                                <div x-show="expanded" x-collapse
                                                    class="border-t border-neutral-200 p-5 mt-2 bg-neutral-50 rounded-b-md">
                                                    <div class="mb-3 flex items-center">
                                                        <div class="rounded-full bg-secondary/10 p-1 mr-2">
                                                            <x-icon name="check" class="h-4 w-4 text-secondary" />
                                                        </div>
                                                        <span
                                                            class="text-sm font-tertiary font-medium text-neutral-700">
                                                            Submitted
                                                            {{ $deliverable->submitted_at->diffForHumans() }}
                                                        </span>
                                                        @if ($deliverable->approved_at)
                                                            <div
                                                                class="ml-2 rounded-full bg-green-100 px-2 py-0.5 flex items-center">
                                                                <x-icon name="check-circle" class="h-3 w-3 text-green-600 mr-1" />
                                                                <span
                                                                    class="text-xs font-tertiary font-medium text-green-600">Approved</span>
                                                            </div>
                                                        @endif
                                                    </div>

                                                    @if ($deliverable->submission_notes)
                                                        <div class="mb-4">
                                                            <h6
                                                                class="font-tertiary font-semibold text-sm text-neutral-700 mb-2 flex items-center">
                                                                <x-icon name="pencil-square" class="h-4 w-4 mr-1.5 text-secondary" />
                                                                Submission Notes:
                                                            </h6>
                                                            <p
                                                                class="text-neutral-600 text-sm font-main ml-6 p-3 bg-white rounded-md border border-neutral-200">
                                                                {{ $deliverable->submission_notes }}
                                                            </p>
                                                        </div>
                                                    @endif

                                                    @if ($deliverable->submission_files && count($deliverable->submission_files) > 0)
                                                        <div>
                                                            <h6
                                                                class="font-tertiary font-semibold text-sm text-neutral-700 mb-2 flex items-center">
                                                                <x-icon name="document-text" class="h-4 w-4 mr-1.5 text-secondary" />
                                                                Submitted Files:
                                                            </h6>
                                                            <div class="ml-6 grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                                @foreach ($deliverable->submission_files as $index => $file)
                                                                    <a href="{{ route('engagements.deliverables.download-file', [$deliverable->id, $index]) }}"
                                                                        class="flex items-center p-2 bg-white border border-neutral-200 rounded-md hover:bg-neutral-50 transition-colors group">
                                                                        <div
                                                                            class="rounded-md bg-secondary/10 p-1.5 mr-2 group-hover:bg-secondary/20 transition-colors">
                                                                            <x-icon name="arrow-uturn-down" class="h-4 w-4 text-secondary" />
                                                                        </div>
                                                                        <div class="truncate flex-grow">
                                                                            <span
                                                                                class="text-xs font-main text-neutral-700">{{ $file['name'] }}</span>
                                                                        </div>
                                                                        <span
                                                                            class="text-xs text-neutral-500 font-main ml-2">{{ round($file['size'] / 1024, 1) }}
                                                                            KB</span>
                                                                    </a>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    @empty
                                        <div
                                            class="col-span-2 bg-white p-6 border border-neutral-200 rounded-lg text-center">
                                            <x-icon name="clipboard-list" class="h-8 w-8 text-neutral-300 mx-auto mb-2" stroke-width="1.5" />
                                            <p class="text-neutral-500 italic font-tertiary">No deliverables
                                                specified for this
                                                project yet.</p>
                                        </div>
                                    @endforelse
                                </div>

                                <!-- Project Progress Overview -->
                                @if ($engagement->deliverables->isNotEmpty())
                                    <div class="mt-8 bg-white border border-neutral-200 rounded-lg shadow-sm p-5">
                                        <h5 class="font-tertiary font-medium text-neutral-800 mb-4 flex items-center">
                                            <x-icon name="chart-bar" class="h-5 w-5 mr-2 text-primary" />
                                            Project Progress
                                        </h5>

                                        <div class="flex flex-col md:flex-row gap-6">
                                            <!-- Progress Bar -->
                                            <div class="flex-1">
                                                <div class="flex justify-between text-sm mb-1 font-main">
                                                    <span
                                                        class="font-medium text-neutral-700">{{ round($engagement->completionPercentage()) }}%
                                                        Complete</span>
                                                    <span class="text-neutral-500">
                                                        {{ $engagement->deliverables->where('status', 'approved')->count() }}/{{ $engagement->deliverables->count() }}
                                                        deliverables
                                                    </span>
                                                </div>
                                                <div class="h-2 bg-neutral-200 rounded-full overflow-hidden">
                                                    <div class="h-full bg-secondary rounded-full"
                                                        style="width: {{ $engagement->completionPercentage() }}%">
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Stats -->
                                            <div class="flex divide-x divide-neutral-200 font-main">
                                                <div class="px-4 first:pl-0 last:pr-0">
                                                    <div class="text-xs text-neutral-500 mb-1">Pending</div>
                                                    <div class="text-lg text-center font-medium text-neutral-800">
                                                        {{ $engagement->deliverables->where('status', 'pending')->count() }}
                                                    </div>
                                                </div>
                                                <div class="px-4">
                                                    <div class="text-xs text-neutral-500 mb-1">Submitted</div>
                                                    <div class="text-lg text-center font-medium text-accent">
                                                        {{ $engagement->deliverables->where('status', 'submitted')->count() }}
                                                    </div>
                                                </div>
                                                <div class="px-4">
                                                    <div class="text-xs text-neutral-500 mb-1">Approved</div>
                                                    <div class="text-lg text-center font-medium text-secondary">
                                                        {{ $engagement->deliverables->where('status', 'approved')->count() }}
                                                    </div>
                                                </div>
                                                <div class="px-4 last:pr-0">
                                                    <div class="text-xs text-neutral-500 mb-1">Rejected</div>
                                                    <div class="text-lg text-center font-medium text-red-800">
                                                        {{ $engagement->deliverables->where('status', 'rejected')->count() }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- Resubmit Deliverable -->
                            @include('jobBoard.engagements.partials.components.resubmit-deliverable')
                        @endif

                        @if ($isPoster)
                            <div class="p-6">
                                <div class="mb-6 flex justify-between">
                                    <h4 class="font-tertiary font-semibold text-primary flex items-center">
                                        <x-icon name="clipboard-list" class="h-5 w-5 mr-2" />
                                        Project Deliverables
                                    </h4>

                                    <!-- Add Deliverable Button -->
                                    @if (!$engagement->isCompleted() && !$engagement->isCancelled())
                                        <button id="openDeliverableModal-{{ $engagement->id }}"
                                            class="font-tertiary inline-flex items-center px-4 py-2 rounded-lg text-white bg-secondary hover:bg-secondary/90 transition-colors shadow-sm">
                                            <x-icon name="plus" class="h-5 w-5 mr-2" />
                                            Add Deliverable
                                        </button>
                                    @endif

                                </div>

                                <!-- Deliverables List -->
                                <div class="grid grid-cols-1 gap-6">
                                    @forelse($engagement->deliverables as $deliverable)
                                        <div x-data="{ expanded: false }"
                                            class="bg-white border border-neutral-200 rounded-lg shadow-sm hover:shadow transition-all overflow-hidden">
                                            <!-- Deliverable Header -->
                                            <div @click="expanded = !expanded"
                                                class="p-5 flex justify-between items-start cursor-pointer">
                                                <div class="flex items-center">
                                                    <div
                                                        class="rounded-full p-1.5 mr-3 flex-shrink-0
                                                            @if ($deliverable->status === 'approved') bg-green-100 text-green-800
                                                            @elseif($deliverable->status === 'rejected')
                                                                bg-red-100 text-red-800
                                                            @elseif($deliverable->status === 'submitted')
                                                                bg-secondary/10 text-secondary
                                                            @else
                                                                bg-neutral-200 text-neutral-500 @endif">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            @if ($deliverable->status === 'approved')
                                                                <!-- Check icon -->
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2" d="M5 13l4 4L19 7" />
                                                            @elseif($deliverable->status === 'rejected')
                                                                <!-- X icon -->
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                            @elseif($deliverable->status === 'submitted')
                                                                <!-- Circle check icon -->
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                            @else
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                                            @endif
                                                        </svg>
                                                    </div>
                                                    <div>
                                                        <h5 class="font-tertiary font-bold text-neutral-800">
                                                            {{ $deliverable->title }}</h5>
                                                        <p
                                                            class="font-main text-sm text-justify text-neutral-600 mt-2">
                                                            {{ $deliverable->description }}</p>
                                                        <div
                                                            class="mt-3 flex flex-wrap gap-y-2 gap-x-4 text-xs font-main">
                                                            @if ($deliverable->due_date)
                                                                <div
                                                                    class="flex items-center {{ now()->gt($deliverable->due_date) ? 'text-red-500' : 'text-secondary' }}">
                                                                    <x-icon name="calendar" class="h-3.5 w-3.5 mr-1" />
                                                                    Due:
                                                                    <x-date :date="$deliverable->due_date" />
                                                                    @if ($deliverable->isOverdue())
                                                                        <span class="ml-1 text-xs">(Overdue)</span>
                                                                    @endif
                                                                </div>
                                                            @else
                                                                <div class="flex items-center text-secondary">
                                                                    <x-icon name="calendar" class="h-3.5 w-3.5 mr-1" />
                                                                    Due Date Not Set
                                                                </div>
                                                            @endif

                                                            @if ($deliverable->submitted_at)
                                                                <div class="flex items-center text-secondary">
                                                                    <x-icon name="check-circle" class="h-3.5 w-3.5 mr-1" />
                                                                    Completed:
                                                                    <x-date :date="$deliverable->submitted_at" />
                                                                </div>
                                                            @endif

                                                            @if ($deliverable->approved_at)
                                                                <div class="flex items-center text-secondary">
                                                                    <x-icon name="check-circle" class="h-3.5 w-3.5 mr-1" />
                                                                    Approved:
                                                                    <x-date :date="$deliverable->approved_at" />
                                                                </div>
                                                            @endif

                                                            @if ($deliverable->rejected_at)
                                                                <div class="flex items-center text-red-800">
                                                                    <x-icon name="x-mark" class="h-3.5 w-3.5 mr-1" />
                                                                    Rejected:
                                                                    <x-date :date="$deliverable->rejected_at" />
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="flex items-center">
                                                    <span
                                                        class="px-2.5 py-1 text-xs font-medium rounded-full mr-3 flex items-center 
                                                            @if ($deliverable->status === 'approved') bg-green-100 text-green-800
                                                            @elseif($deliverable->status === 'rejected')
                                                                bg-red-100 text-red-800
                                                            @elseif($deliverable->status === 'submitted')
                                                                bg-secondary/10 text-secondary
                                                            @elseif($deliverable->isOverdue())
                                                                bg-red-100 text-red-800
                                                            @else
                                                                bg-neutral-200 text-neutral-600 @endif">

                                                        @if ($deliverable->status === 'approved')
                                                            <x-icon name="check" class="h-3 w-3 mr-1" />
                                                            Approved
                                                        @elseif($deliverable->status === 'rejected')
                                                            <x-icon name="x-mark" class="h-3 w-3 mr-1" />
                                                            Rejected
                                                        @elseif($deliverable->status === 'submitted')
                                                            <x-icon name="document-text" class="h-3 w-3 mr-1" />
                                                            Submitted
                                                        @elseif($deliverable->isOverdue())
                                                            <x-icon name="exclamation-triangle" class="h-4 w-4 mr-1" />
                                                            Overdue
                                                        @else
                                                            Pending
                                                        @endif
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Deliverable Details (Expandable) -->
                                            <div x-show="expanded" x-collapse
                                                class="border-t border-neutral-200 p-5 bg-neutral-50">

                                                <!-- Submission Details (when submitted) -->
                                                @if ($deliverable->status === 'submitted' || $deliverable->status === 'approved' || $deliverable->status === 'rejected')
                                                    <div
                                                        class="bg-white border border-neutral-200 rounded-lg p-4 mb-4">
                                                        <!-- Submission Notes -->
                                                        <h6 class="font-medium font-tertiary text-primary mb-1">
                                                            Submission Notes
                                                        </h6>
                                                        @if ($deliverable->submission_notes)
                                                            <p
                                                                class="text-neutral-600 text-sm mb-3 font-main text-justify p-3 bg-neutral-50 rounded-lg">
                                                                {{ $deliverable->submission_notes }}
                                                            </p>
                                                        @else
                                                            <div
                                                                class="flex items-center p-3 bg-neutral-50 rounded-lg mb-3">
                                                                <x-icon name="document-text" class="h-10 w-10 mr-2 text-neutral-400" />
                                                                <span class="text-sm text-neutral-500 font-main">No
                                                                    provided notes</span>
                                                            </div>
                                                        @endif

                                                        <h6 class="font-medium font-tertiary text-primary mb-1">
                                                            Submitted Files
                                                        </h6>

                                                        <!-- Submitted Files -->
                                                        @if (!empty($deliverable->submission_files))
                                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                                                @foreach ($deliverable->submission_files as $index => $file)
                                                                    <a href="{{ route('engagements.deliverables.download-file', [$deliverable->id, $index]) }}"
                                                                        class="flex items-center p-2 border border-neutral-200 rounded-md hover:bg-neutral-50 transition-colors">
                                                                        <x-icon name="document" class="h-5 w-5 mr-2 text-primary" />
                                                                        <div
                                                                            class="flex flex-col w-full overflow-hidden">
                                                                            <span
                                                                                class="text-sm truncate font-medium">{{ $file['name'] }}</span>
                                                                            <span
                                                                                class="text-xs text-neutral-500">{{ number_format($file['size'] / 1024, 1) }}
                                                                                KB</span>
                                                                        </div>
                                                                    </a>
                                                                @endforeach
                                                            </div>
                                                        @else
                                                            <div
                                                                class="flex items-center justify-center mx-auto p-3 bg-neutral-50 rounded-lg">
                                                                <x-icon name="document-text-2" class="h-10 w-10 mr-2 text-neutral-400" />
                                                                <span class="text-sm text-neutral-500 font-main">No
                                                                    files uploaded</span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif

                                                <!-- Feedback -->
                                                @if (in_array($deliverable->status, ['approved', 'rejected']) && $deliverable->feedback)
                                                    <div
                                                        class="bg-{{ $deliverable->status === 'approved' ? 'green' : 'red' }}-50 border border-{{ $deliverable->status === 'approved' ? 'green' : 'red' }}-200 rounded-lg p-4 mb-4">
                                                        <h6
                                                            class="font-tertiary font-medium text-{{ $deliverable->status === 'approved' ? 'green' : 'red' }}-700 mb-2 flex items-center">
                                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                                class="h-4 w-4 mr-1.5" fill="none"
                                                                viewBox="0 0 24 24" stroke="currentColor">
                                                                @if ($deliverable->status === 'approved')
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round" stroke-width="2"
                                                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                @else
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round" stroke-width="2"
                                                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                                @endif
                                                            </svg>
                                                            {{ $deliverable->status === 'approved' ? 'Approval Notes' : 'Rejection Feedback' }}
                                                        </h6>
                                                        <p
                                                            class="font-main text-{{ $deliverable->status === 'approved' ? 'green' : 'red' }}-800 text-sm">
                                                            {{ $deliverable->feedback }}
                                                        </p>
                                                    </div>
                                                @endif

                                                <!-- Action Buttons -->
                                                <div class="flex flex-wrap items-center gap-3 mt-2">
                                                    <!-- Approve/Reject Deliverable -->
                                                    @if ($deliverable->status === 'submitted' && $engagement->status === EngagementStatus::Active)
                                                        <button x-data
                                                            x-on:click="$dispatch('open-modal', 'approve-deliverable-{{ $deliverable->id }}')"
                                                            class="inline-flex items-center px-3.5 py-2 text-sm font-medium rounded-md text-white bg-secondary hover:bg-secondary/90 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-secondary">
                                                            <x-icon name="check" class="h-4 w-4 mr-1.5" />
                                                            Approve
                                                        </button>

                                                        <button x-data
                                                            x-on:click="$dispatch('open-modal', 'reject-deliverable-{{ $deliverable->id }}')"
                                                            class="inline-flex items-center px-3.5 py-2 text-sm font-medium rounded-md text-accent border border-accent/30 bg-white hover:bg-accent/5 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-accent">
                                                            <x-icon name="x-mark" class="h-4 w-4 mr-1.5" />
                                                            Request Changes
                                                        </button>
                                                    @endif

                                                    <!-- Edit/Delete Deliverable -->
                                                    @if ($deliverable->status === 'pending' && $engagement->status === EngagementStatus::Active)
                                                        <button x-data
                                                            x-on:click="$dispatch('open-modal', 'edit-deliverable-{{ $deliverable->id }}')"
                                                            class="inline-flex items-center px-3.5 py-2 text-sm font-medium rounded-md text-primary border border-primary/30 bg-primary/5 hover:bg-primary/10 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary/40">
                                                            <x-icon name="pencil-square" class="h-4 w-4 mr-1.5" />
                                                            Edit
                                                        </button>
                                                        <button x-data
                                                            x-on:click="$dispatch('open-modal', 'remove-deliverable-{{ $deliverable->id }}')"
                                                            class="inline-flex items-center px-3.5 py-2 text-sm font-medium rounded-md text-red-600 border border-red-200 bg-red-50 hover:bg-red-100 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500/40">
                                                            <x-icon name="trash" class="h-4 w-4 mr-1.5" />
                                                            Remove
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="bg-white p-8 border border-neutral-200 rounded-lg text-center">
                                            <div class="bg-primary/5 inline-flex rounded-full p-4 mb-4">
                                                <x-icon name="clipboard-list" class="h-8 w-8 text-primary" stroke-width="1.5" />
                                            </div>
                                            <h5 class="font-medium text-lg text-neutral-800 mb-2 font-tertiary">No
                                                Deliverables
                                                Yet</h5>
                                            <p class="text-neutral-600 mb-6 font-tertiary text-sm">Define project
                                                deliverables to track
                                                progress and ensure clear expectations.</p>
                                        </div>
                                    @endforelse
                                </div>

                                <!-- Project Progress Overview -->
                                @if ($engagement->deliverables->isNotEmpty())
                                    <div class="mt-8 bg-white border border-neutral-200 rounded-lg shadow-sm p-5">
                                        <h5 class="font-tertiary font-medium text-neutral-800 mb-4 flex items-center">
                                            <x-icon name="chart-bar" class="h-5 w-5 mr-2 text-primary" />
                                            Project Progress
                                        </h5>

                                        <div class="flex flex-col md:flex-row gap-6">
                                            <!-- Progress Bar -->
                                            <div class="flex-1">
                                                <div class="flex justify-between text-sm mb-1 font-main">
                                                    <span
                                                        class="font-medium text-neutral-700">{{ round($engagement->completionPercentage()) }}%
                                                        Complete</span>
                                                    <span class="text-neutral-500">
                                                        {{ $engagement->deliverables->where('status', 'approved')->count() }}/{{ $engagement->deliverables->count() }}
                                                        deliverables
                                                    </span>
                                                </div>
                                                <div class="h-2 bg-neutral-200 rounded-full overflow-hidden">
                                                    <div class="h-full bg-secondary rounded-full"
                                                        style="width: {{ $engagement->completionPercentage() }}%">
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Stats -->
                                            <div class="flex divide-x divide-neutral-200 font-main">
                                                <div class="px-4 first:pl-0 last:pr-0">
                                                    <div class="text-xs text-neutral-500 mb-1">Pending</div>
                                                    <div class="text-lg text-center font-medium text-neutral-800">
                                                        {{ $engagement->deliverables->where('status', 'pending')->count() }}
                                                    </div>
                                                </div>
                                                <div class="px-4">
                                                    <div class="text-xs text-neutral-500 mb-1">Submitted</div>
                                                    <div class="text-lg text-center font-medium text-accent">
                                                        {{ $engagement->deliverables->where('status', 'submitted')->count() }}
                                                    </div>
                                                </div>
                                                <div class="px-4">
                                                    <div class="text-xs text-neutral-500 mb-1">Approved</div>
                                                    <div class="text-lg text-center font-medium text-secondary">
                                                        {{ $engagement->deliverables->where('status', 'approved')->count() }}
                                                    </div>
                                                </div>
                                                <div class="px-4 last:pr-0">
                                                    <div class="text-xs text-neutral-500 mb-1">Rejected</div>
                                                    <div class="text-lg text-center font-medium text-red-800">
                                                        {{ $engagement->deliverables->where('status', 'rejected')->count() }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- Modals -->
                            <!-- Approve Deliverable Modal -->
                            @include('jobBoard.engagements.partials.components.modals.approve')

                            <!-- Reject Deliverable Modal -->
                            @include('jobBoard.engagements.partials.components.modals.reject')

                            <!-- Edit Deliverable Modal -->
                            @include('jobBoard.engagements.partials.components.modals.edit')

                            <!-- Delete Deliverable Modal -->
                            @include('jobBoard.engagements.partials.components.modals.delete')

                            <!-- Add Deliverable Modal -->
                            @include('jobBoard.engagements.partials.components.modals.add', [
                                'engagement' => $engagement,
                            ])
                        @endif
                    @else
                        @include('jobBoard.engagements.partials.disputed.index')
                    @endif
                </div>

                <!-- Card Footer -->
                @include('jobBoard.engagements.partials.components.card-footer')

                <!-- Archive Engagement Modal -->
                @include('jobBoard.engagements.partials.components.modals.archive')

                <!-- Review Modal -->
                @include('jobBoard.engagements.partials.components.modals.review')

                <!-- Cancellation Modal -->
                @include('jobBoard.engagements.partials.components.modals.cancellation')
            </div>
        @endforeach
    </div>

    <!-- Message Modal -->
    <div x-data="messageModal()" x-on:open-message-modal.window="openModal($event.detail.engagementId)"
        @keydown.escape.window="closeModal()" x-show="open" x-cloak class="fixed inset-0 z-50 overflow-y-auto"
        aria-labelledby="message-modal" role="dialog" aria-modal="true">

        <!-- Overlay -->
        <div x-show="open" @click="closeModal()"
            class="fixed inset-0 bg-neutral-900/50 backdrop-blur-sm transition-opacity"></div>

        <!-- Modal Content -->
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div @click.outside="closeModal()"
                class="bg-white rounded-xl overflow-hidden shadow-2xl w-full max-w-2xl transition-all">

                <!-- Header -->
                <div class="bg-primary px-6 py-4 flex justify-between items-center">
                    <div class="flex items-center">
                        <div
                            class="w-10 h-10 rounded-full bg-secondary flex items-center justify-center text-white font-bold">
                            <x-icon name="chat-bubble-left-right" class="size-6" />

                        </div>
                        <div class="ml-3">
                            <h3 class="text-lg font-tertiary text-white"
                                x-text="engagement.job_title || 'Loading...'"></h3>
                        </div>
                    </div>
                    <button @click="closeModal()" class="text-white hover:text-white/80 focus:outline-none">
                        <x-icon name="x-mark" class="h-6 w-6" />
                    </button>
                </div>

                <!-- Message List -->
                <div class="bg-neutral-50 px-4 py-4 h-80 overflow-y-auto space-y-3" id="messages-container">
                    <!-- Loading State -->
                    <div x-show="loading"
                        class="flex flex-col items-center justify-center h-full text-neutral-400 text-sm">
                        <svg class="animate-spin h-8 w-8 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        Loading messages...
                    </div>

                    <!-- Messages -->
                    <div x-show="!loading" id="messages-list">
                        <!-- Rendered dynamically by renderMessages() -->
                    </div>
                </div>

                <!-- Footer / Input -->
                <div class="bg-white px-6 py-4 border-t border-neutral-200">
                    <div x-show="canMessage">
                        <form @submit.prevent="sendMessage()" id="message-form">
                            <div class="flex items-center">
                                <textarea x-model="newMessage" rows="2" required @keydown.enter.prevent="sendMessage()"
                                    class="flex-grow rounded-md border-neutral-300 shadow-sm text-sm font-main resize-none focus:border-secondary focus:ring-secondary"
                                    placeholder="Type your message..."></textarea>
                                <button type="submit" :disabled="sending || !newMessage.trim()"
                                    class="ml-3 inline-flex items-center justify-center rounded-md px-4 py-2 bg-secondary text-white hover:bg-secondary/90 disabled:opacity-50">
                                    <x-icon name="paper-airplane" x-show="!sending" class="h-5 w-5" />
                                    <svg x-show="sending" class="animate-spin h-5 w-5"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                            stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                </button>
                            </div>
                        </form>
                    </div>
                    <div x-show="!canMessage" class="text-sm text-red-600 text-center py-2 font-main">
                        Messaging is only available for active or cancelled engagements.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function messageModal() {
            return {
                open: false,
                loading: false,
                sending: false,
                currentEngagementId: null,
                engagement: {
                    job_title: '',
                    other_user_name: '',
                    other_user_initials: '', // Assuming backend provides this
                    status: ''
                },
                messages: [],
                newMessage: '',
                canMessage: false,

                async openModal(engagementId) {
                    this.currentEngagementId = engagementId;
                    this.open = true;
                    this.loading = true;
                    try {
                        await this.loadEngagementData();
                        await this.markMessagesAsRead();
                    } catch (error) {
                        console.error('Error loading engagement data:', error);
                        this.showError('Failed to load messages');
                    } finally {
                        this.loading = false;
                    }
                },

                closeModal() {
                    this.open = false;
                    this.currentEngagementId = null;
                    this.messages = [];
                    this.newMessage = '';
                    this.engagement = {
                        job_title: '',
                        other_user_name: '',
                        other_user_initials: '',
                        status: ''
                    };
                },

                async loadEngagementData() {
                    try {
                        const response = await fetch(`/chat/engagements/${this.currentEngagementId}/data`, {
                            method: 'GET',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                    'content')
                            }
                        });

                        if (!response.ok) {
                            const errorText = await response.text();
                            console.error('Server response:', errorText);
                            throw new Error(`HTTP ${response.status}: ${errorText}`);
                        }

                        const data = await response.json();
                        this.engagement = data.engagement;
                        this.messages = Array.isArray(data.messages) ? data.messages : [];
                        this.canMessage = ['active', 'cancelled'].includes(data.engagement.status);
                        this.renderMessages();
                    } catch (error) {
                        console.error('Detailed error:', error);
                        throw error;
                    }
                },

                async markMessagesAsRead() {
                    try {
                        const response = await fetch(`/chat/engagements/${this.currentEngagementId}/messages/read`, {
                            method: 'PATCH',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                    'content'),
                                'Content-Type': 'application/json'
                            }
                        });

                        if (response.ok) {
                            const unreadBadge = document.getElementById(`unread-count-${this.currentEngagementId}`);
                            if (unreadBadge) {
                                unreadBadge.style.display = 'none';
                            }
                        }
                    } catch (error) {
                        console.error('Error marking messages as read:', error);
                    }
                },

                renderMessages() {
                    const container = document.getElementById('messages-list');
                    if (!container) {
                        console.error('Messages container not found');
                        return;
                    }

                    container.innerHTML = '';

                    if (this.messages.length === 0) {
                        const placeholderHtml = `
                            <div class="flex flex-col items-center justify-center h-full text-neutral-400 text-sm py-8">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mb-3 text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                                <p class="text-center text-neutral-600 font-main">No messages yet</p>
                                <p class="text-xs text-neutral-500 mt-1 font-secondary">Start the conversation by sending a message!</p>
                            </div>
                        `;
                        container.innerHTML = placeholderHtml;
                        this.scrollToBottom();
                        return;
                    }

                    const messagesHtml = this.messages.map((message) => {
                        const isOwn = message.is_own || false;
                        const messageTime = this.formatMessageTime(message.created_at);
                        const content = message.content || '[Empty message]';
                        const senderName = message.sender_name || 'Unknown';
                        return `
                            <div class="flex ${isOwn ? 'justify-end' : 'justify-start'} mb-3">
                                <div class="max-w-[70%] px-4 py-3 rounded-2xl shadow-sm ${
                                    isOwn
                                        ? 'bg-primary text-white rounded-br-none'
                                        : 'bg-neutral-100 text-neutral-900 border border-neutral-200 rounded-bl-none'
                                }">
                                    <div class="flex justify-between items-center">
                                        <span class="font-medium text-xs ${
                                            isOwn ? 'text-white/90' : 'text-secondary'
                                        }">
                                            ${isOwn ? 'You' : senderName}
                                        </span>
                                        <span class="text-xs font-secondary ${
                                            isOwn ? 'text-white/70' : 'text-neutral-500'
                                        } ml-2">
                                            ${messageTime}
                                        </span>
                                    </div>
                                    <div class="whitespace-pre-line break-words text-sm font-main leading-relaxed">
                                        ${this.escapeHtml(content)}
                                    </div>
                                    ${message.read_at && isOwn ? `
                                                            <div class="text-xs text-white/60 mt-1 text-right font-secondary">
                                                                Read
                                                            </div>
                                                        ` : ''}
                                </div>
                            </div>
                        `;
                    }).join('');

                    container.innerHTML = messagesHtml;
                    this.$nextTick(() => {
                        this.scrollToBottom();
                    });
                },

                scrollToBottom() {
                    setTimeout(() => {
                        const messagesContainer = document.getElementById('messages-container');
                        if (messagesContainer) {
                            messagesContainer.scrollTop = messagesContainer.scrollHeight;
                        }
                    }, 50);
                },

                escapeHtml(text) {
                    const div = document.createElement('div');
                    div.textContent = text;
                    return div.innerHTML;
                },

                async sendMessage() {
                    if (!this.newMessage.trim() || this.sending) return;
                    this.sending = true;
                    const messageContent = this.newMessage.trim();

                    try {
                        const response = await fetch(`/chat/engagements/${this.currentEngagementId}/messages`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                    'content'),
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                content: messageContent
                            })
                        });

                        if (!response.ok) {
                            const errorData = await response.json();
                            throw new Error(errorData.error || 'Failed to send message');
                        }

                        const result = await response.json();
                        this.messages.push(result.message);
                        this.newMessage = '';
                        this.renderMessages();
                    } catch (error) {
                        console.error('Error sending message:', error);
                        this.showError('Failed to send message: ' + error.message);
                    } finally {
                        this.sending = false;
                    }
                },

                showError(message) {
                    alert(message);
                },

                isToday(date) {
                    const today = new Date();
                    return date.getDate() === today.getDate() &&
                        date.getMonth() === today.getMonth() &&
                        date.getFullYear() === today.getFullYear();
                },

                isYesterday(date) {
                    const yesterday = new Date();
                    yesterday.setDate(yesterday.getDate() - 1);
                    return date.getDate() === yesterday.getDate() &&
                        date.getMonth() === yesterday.getMonth() &&
                        date.getFullYear() === yesterday.getFullYear();
                },

                formatMessageTime(createdAt) {
                    const messageDate = new Date(createdAt || Date.now());
                    if (this.isToday(messageDate)) {
                        return messageDate.toLocaleTimeString('en-US', {
                            hour: '2-digit',
                            minute: '2-digit',
                            hour12: true
                        });
                    } else if (this.isYesterday(messageDate)) {
                        return 'Yesterday, ' + messageDate.toLocaleTimeString('en-US', {
                            hour: '2-digit',
                            minute: '2-digit',
                            hour12: true
                        });
                    } else {
                        return messageDate.toLocaleString('en-US', {
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit',
                            hour12: true
                        });
                    }
                },

            };
        }
    </script>
@endif
