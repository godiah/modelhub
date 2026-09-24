<div class="font-main rounded-2xl shadow-lg border border-neutral-200">
    <!-- Card Container -->
    <div class="bg-white rounded-xl shadow-md border border-neutral-200 overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-primary to-primary/90 p-6 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <h2 class="font-tertiary font-semibold text-lg text-white">Project Deliverables</h2>
            </div>
            <div class="flex items-center gap-2">
                <div class="h-8 w-8 rounded-full bg-white/20 flex items-center justify-center text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <span class="text-sm font-medium text-white">{{ $engagement->getCompletedDeliverablesCount() }}
                    of {{ $engagement->getTotalDeliverablesCount() }} completed</span>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="px-6 py-3 bg-neutral-50 border-b border-neutral-200">
            <div class="w-full bg-neutral-200 rounded-full h-2.5">
                <div class="bg-secondary h-2.5 rounded-full"
                    style="width: {{ ($engagement->getCompletedDeliverablesCount() / $engagement->getTotalDeliverablesCount()) * 100 }}%">
                </div>
            </div>
        </div>

        <!-- Deliverables List -->
        <div class="p-4 space-y-4">
            @foreach ($engagement->deliverables as $deliverable)
                <div class="border border-neutral-200 rounded-lg hover:shadow-md transition duration-200">
                    <div class="flex justify-between items-start p-4 bg-white">
                        <div class="flex-1">
                            <div class="flex items-center gap-3">
                                @switch($deliverable->status)
                                    @case('approved')
                                        <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600"
                                                viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd"
                                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    @break

                                    @case('submitted')
                                        <div class="h-10 w-10 rounded-full bg-yellow-100 flex items-center justify-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-yellow-600"
                                                viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd"
                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    @break

                                    @case('rejected')
                                        <div class="h-10 w-10 rounded-full bg-red-100 flex items-center justify-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600"
                                                viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd"
                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    @break

                                    @default
                                        <div class="h-10 w-10 rounded-full bg-neutral-100 flex items-center justify-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-neutral-600"
                                                viewBox="0 0 20 20" fill="currentColor">
                                                <path
                                                    d="M10 3.5a1.5 1.5 0 013 0V4a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-.5a1.5 1.5 0 000 3h.5a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-.5a1.5 1.5 0 00-3 0v.5a1 1 0 01-1 1H6a1 1 0 01-1-1v-3a1 1 0 00-1-1h-.5a1.5 1.5 0 010-3H4a1 1 0 001-1V6a1 1 0 011-1h3a1 1 0 001-1v-.5z" />
                                            </svg>
                                        </div>
                                @endswitch

                                <div>
                                    <h4 class="font-medium text-neutral-800 text-lg">
                                        {{ $deliverable->title }}</h4>
                                    <div class="flex items-center gap-4 mt-1">
                                        <div class="flex items-center gap-1 text-sm text-neutral-500">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <span>Due:
                                                {{ \Carbon\Carbon::parse($deliverable->due_date)->toFormattedDateString() }}</span>
                                        </div>

                                        @switch($deliverable->status)
                                            @case('approved')
                                                <span
                                                    class="text-xs font-medium text-green-700 bg-green-100 px-3 py-1 rounded-full">Approved</span>
                                            @break

                                            @case('submitted')
                                                <span
                                                    class="text-xs font-medium text-yellow-700 bg-yellow-100 px-3 py-1 rounded-full">Pending
                                                    Approval</span>
                                            @break

                                            @case('rejected')
                                                <span
                                                    class="text-xs font-medium text-red-700 bg-red-100 px-3 py-1 rounded-full">Rejected</span>
                                            @break

                                            @default
                                                <span
                                                    class="text-xs font-medium text-neutral-700 bg-neutral-100 px-3 py-1 rounded-full">Not
                                                    Submitted</span>
                                        @endswitch
                                    </div>
                                </div>
                            </div>

                            @if (auth()->id() === $engagement->application->poster_id && $deliverable->status === 'submitted')
                                <div class="flex gap-3 mt-4 ml-12">
                                    <form method="POST"
                                        action="{{ route('engagements.deliverables.approve', $deliverable->id) }}">
                                        @csrf
                                        <button
                                            class="inline-flex items-center gap-1 bg-green-100 hover:bg-green-200 text-green-700 font-medium px-4 py-2 rounded-md transition duration-200">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                            Approve
                                        </button>
                                    </form>
                                    <form method="POST"
                                        action="{{ route('engagements.deliverables.reject', $deliverable->id) }}">
                                        @csrf
                                        <button
                                            class="inline-flex items-center gap-1 bg-red-100 hover:bg-red-200 text-red-700 font-medium px-4 py-2 rounded-md transition duration-200">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                            Reject
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>

                        <button class="text-neutral-400 hover:text-primary transition duration-200 p-1"
                            aria-label="Toggle details">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                    clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>

                    <!-- Expandable Content -->
                    <div class="p-4 border-t border-neutral-200 bg-neutral-50 text-sm text-neutral-600">
                        <div class="ml-12 space-y-4">
                            <div>
                                <h5 class="font-medium text-neutral-800 mb-1">Description</h5>
                                <p class="text-neutral-600">{{ $deliverable->description }}</p>
                            </div>

                            @if ($deliverable->submission_notes)
                                <div>
                                    <h5 class="font-medium text-neutral-800 mb-1">Submission Notes
                                    </h5>
                                    <p class="text-neutral-600">
                                        {{ $deliverable->submission_notes }}</p>
                                </div>
                            @endif

                            @if ($deliverable->submission_files)
                                <div>
                                    <h5 class="font-medium text-neutral-800 mb-1">Attached Files
                                    </h5>
                                    <ul class="space-y-2">
                                        @foreach ($deliverable->submission_files as $file)
                                            <li class="flex items-center gap-2">
                                                <div
                                                    class="h-8 w-8 bg-neutral-100 rounded flex items-center justify-center">
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="h-4 w-4 text-neutral-600" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <a href="{{ Storage::url($file['path']) }}" target="_blank"
                                                        class="text-primary hover:text-primary/80 font-medium hover:underline transition duration-200">
                                                        {{ $file['name'] }}
                                                    </a>
                                                    <span
                                                        class="text-xs text-neutral-500 ml-2">({{ round($file['size'] / 1024, 1) }}
                                                        KB)</span>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if ($deliverable->feedback)
                                <div>
                                    <h5 class="font-medium text-neutral-800 mb-1">Feedback</h5>
                                    <div class="p-3 bg-white border border-neutral-200 rounded-md">
                                        {{ $deliverable->feedback }}
                                    </div>
                                </div>
                            @endif

                            <div
                                class="pt-2 border-t border-neutral-200 flex justify-between text-xs text-neutral-500">
                                <div>
                                    <p>Created:
                                        {{ $deliverable->created_at->format('M d, Y h:i A') }}</p>
                                    @if ($deliverable->submitted_at)
                                        <p>Submitted:
                                            {{ \Carbon\Carbon::parse($deliverable->submitted_at)->format('M d, Y h:i A') }}
                                        </p>
                                    @endif
                                </div>
                                <div class="text-right">
                                    @if ($deliverable->approved_at)
                                        <p class="text-green-600 font-medium">Approved:
                                            {{ \Carbon\Carbon::parse($deliverable->approved_at)->format('M d, Y h:i A') }}
                                        </p>
                                    @endif
                                    @if ($deliverable->rejected_at)
                                        <p class="text-red-600 font-medium">Rejected:
                                            {{ \Carbon\Carbon::parse($deliverable->rejected_at)->format('M d, Y h:i A') }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<script>
    // Simple toggle functionality for expanding/collapsing deliverable details
    document.addEventListener('DOMContentLoaded', function() {
        const toggleButtons = document.querySelectorAll('[aria-label="Toggle details"]');

        toggleButtons.forEach(button => {
            button.addEventListener('click', function() {
                const detailsSection = this.closest('div').nextElementSibling;
                const isExpanded = detailsSection.style.display !== 'none';

                if (isExpanded) {
                    detailsSection.style.display = 'none';
                    this.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M15.707 12.707a1 1 0 01-1.414 0L10 8.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0l5 5a1 1 0 010 1.414z" clip-rule="evenodd" />
            </svg>`;
                } else {
                    detailsSection.style.display = 'block';
                    this.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>`;
                }
            });
        });
    });
</script>
