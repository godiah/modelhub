@if ($engagements->isEmpty())
    <div class="text-center py-12 font-main">
        <div
            class="w-20 h-20 bg-gradient-to-br from-neutral-100 to-neutral-200 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-10 h-10 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0 1 12 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 0 1-.673-.38m0 0A2.18 2.18 0 0 1 3 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 0 1 3.413-.387m7.5 0V5.25A2.25 2.25 0 0 0 13.5 3h-3a2.25 2.25 0 0 0-2.25 2.25v.894m7.5 0a48.667 48.667 0 0 0-7.5 0M12 12.75h.008v.008H12v-.008Z" />
            </svg>
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
                                    @php
                                        $statusClasses = $engagement->getStatusClasses();
                                    @endphp
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $statusClasses['bg'] }} {{ $statusClasses['text'] }} {{ $statusClasses['border'] }} border">
                                        <svg class="w-3 h-3 mr-1.5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            {!! $engagement->getStatusIconPathAttribute() !!}
                                        </svg>
                                        {{ $engagement->getStatusLabelAttribute() }}
                                    </span>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-semibold text-neutral-900 font-main">
                                    Ksh{{ number_format($engagement->agreed_amount, 2) }}</p>
                                <p class="text-sm text-neutral-500 font-secondary">Agreed Amount</p>
                            </div>
                        </div>

                        <!-- Project Details -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4 font-secondary">
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4 text-neutral-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z">
                                    </path>
                                </svg>
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
                                <svg class="w-4 h-4 text-neutral-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                    </path>
                                </svg>
                                <span class="text-sm text-neutral-600">
                                    Started:
                                    {{ $engagement->started_at ? $engagement->started_at->format('M j, Y') : 'Not started' }}
                                </span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4 text-neutral-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4">
                                    </path>
                                </svg>
                                <span class="text-sm text-neutral-600">
                                    Deliverables:
                                    {{ $engagement->getCompletedDeliverablesCount() }}/{{ $engagement->getTotalDeliverablesCount() }}
                                </span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4 text-neutral-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
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
                                <a href="{{ route('engagements.archived-details', $engagement->id) }}"
                                    class="inline-flex items-center px-4 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-primary/90 transition-colors">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z">
                                        </path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                        </path>
                                    </svg>
                                    View Details
                                </a>

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
