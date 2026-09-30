@if ($engagements->isEmpty())
    <div class="text-center py-12 font-main">
        <div
            class="w-20 h-20 bg-gradient-to-br from-neutral-100 to-neutral-200 rounded-full flex items-center justify-center mx-auto mb-4">
            <x-icon name="briefcase" class="w-10 h-10 text-neutral-400" stroke-width="2" />
        </div>
        <h3 class="text-lg font-medium text-neutral-900 mb-2">
            No {{ ucfirst($tab) }} projects found
        </h3>
        <p class="text-neutral-500 text-sm">
            @switch($tab)
                @case('all')
                    You don't have any projects at the moment.
                @break

                @case('active')
                    You don't have any active projects at the moment.
                @break

                @case('pending')
                    You don't have any pending projects awaiting a response.
                @break

                @case('completed')
                    You haven't completed any projects yet.
                @break

                @case('cancelled')
                    You don't have any cancelled projects.
                @break

                @case('disputed')
                    You don't have any disputed projects.
                @break

                @case('settled')
                    You don't have any settled projects.
                @break

                @case('archived')
                    You haven't archived any projects yet.
                @break

                @default
                    You don't have any projects in this category.
            @endswitch
        </p>
    </div>
@else
    <div class="space-y-4">
        @foreach ($engagements as $engagement)
            <div
                class="bg-gradient-to-r from-white to-neutral-50 rounded-lg border border-neutral-200 hover:border-neutral-300 hover:shadow-md transition-all duration-200 p-6">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <!-- Project Title and Status -->
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-neutral-900 mb-1 font-main">
                                    {{ $engagement->application->job->title ?? 'Project Title' }}
                                </h3>
                                <div class="flex items-center space-x-3 font-secondary">
                                    <x-engagement.status-badge :status="$engagement->status" class="px-3 py-1 text-xs font-medium" icon-class="w-3 h-3 mr-1.5" />
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-semibold text-neutral-900 font-main">
                                    <x-money :amount="$engagement->agreed_amount" /></p>
                                <p class="text-sm text-neutral-500 font-secondary">Agreed Amount</p>
                            </div>
                        </div>

                        <!-- Project Details -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4 font-secondary">
                            <div class="flex items-center space-x-2">
                                <x-icon name="user" class="w-4 h-4 text-neutral-400" />
                                <span class="text-sm text-neutral-600">
                                    @if (auth()->id() === $engagement->application->applicant_id)
                                        Client: {{ $engagement->application->poster->name ?? 'N/A' }}
                                    @else
                                        Freelancer:
                                        {{ $engagement->application->applicant->name ?? 'N/A' }}
                                    @endif
                                </span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <x-icon name="calendar" class="w-4 h-4 text-neutral-400" />
                                <span class="text-sm text-neutral-600">
                                    Started:
                                    {{ $engagement->started_at ? $engagement->started_at->format('M j, Y') : 'Not started' }}
                                </span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <x-icon name="clipboard-check" class="w-4 h-4 text-neutral-400" stroke-width="2" />
                                <span class="text-sm text-neutral-600">
                                    Deliverables:
                                    {{ $engagement->getCompletedDeliverablesCount() }}/{{ $engagement->getTotalDeliverablesCount() }}
                                </span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <x-icon name="bolt" class="w-4 h-4 text-neutral-400" />
                                <span class="text-sm text-neutral-600">
                                    Progress:
                                    {{ number_format($engagement->completionPercentage(), 0) }}%
                                </span>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        @if ($engagement->getTotalDeliverablesCount() > 0)
                            <div class="mb-4 font-secondary">
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-neutral-600">Project Progress</span>
                                    <span
                                        class="text-neutral-900 font-medium">{{ number_format($engagement->completionPercentage(), 0) }}%</span>
                                </div>
                                <div class="w-full bg-neutral-200 rounded-full h-2">
                                    <div class="bg-gradient-to-r from-secondary to-primary h-2 rounded-full transition-all duration-300"
                                        style="width: {{ $engagement->completionPercentage() }}%">
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Action Buttons -->
                        <div class="flex items-center justify-between font-main">
                            <div class="flex items-center space-x-3">
                                <x-btn class="text-sm" href="{{ route('engagements.archived-details', $engagement->id) }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z">
                                        </path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                        </path>
                                    </svg>
                                    View Details
                                </x-btn>

                            </div>

                            <div class="flex items-center space-x-2">
                                @if ($tab !== 'archived')
                                    @if ($engagement->hasBeenReviewedByUser())
                                        <form action="{{ route('engagements.archive') }}" method="POST"
                                            class="inline">
                                            @csrf
                                            <input type="hidden" name="engagement_id" value="{{ $engagement->id }}">
                                            <button type="submit"
                                                class="inline-flex items-center text-xs p-2 text-neutral-400 hover:text-neutral-600 hover:bg-neutral-100 rounded-lg transition-colors"
                                                title="Archive">
                                                Archive
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M5 8l6 6 6-6"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                @else
                                    <form action="{{ route('engagements.restore') }}" method="POST" class="inline">
                                        @csrf
                                        <input type="hidden" name="engagement_id" value="{{ $engagement->id }}">
                                        <button type="submit"
                                            class="inline-flex items-center text-xs p-2 text-neutral-400 hover:text-neutral-600 hover:bg-neutral-100 rounded-lg transition-colors"
                                            title="Restore">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 14l-7-7m0 0l-7 7m7-7v18">
                                                </path>
                                            </svg>
                                            <span class="ml-0.5">Restore</span>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
