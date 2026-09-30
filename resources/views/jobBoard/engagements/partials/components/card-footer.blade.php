@use('App\Enums\EngagementStatus')
<!-- Engagement Card Footer -->
<div class="bg-white border-t border-neutral-200">
    <div class="py-2 px-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 font-main">
            <div class="flex items-start bg-neutral-50 p-4 rounded-lg transition-all hover:shadow-sm">
                <div class="bg-secondary/10 p-2 rounded-lg mr-3">
                    <x-icon name="calendar" class="h-5 w-5 text-secondary" />
                </div>
                <div>
                    <p class="text-xs text-neutral-500 mb-1">Job Posted</p>
                    <p class="text-sm font-medium text-neutral-700">
                        <x-date :date="$engagement->application->job->created_at" />
                    </p>
                </div>
            </div>

            @if ($isApplicant)
                <div class="flex items-start bg-neutral-50 p-4 rounded-lg transition-all hover:shadow-sm">
                    <div class="bg-primary/10 p-2 rounded-lg mr-3">
                        <x-icon name="user" class="h-5 w-5 text-primary" />
                    </div>
                    <div>
                        <p class="text-xs text-neutral-500 mb-1">Job Poster</p>
                        <p class="text-sm font-medium text-neutral-700">
                            {{ $engagement->application->poster->name }}</p>
                    </div>
                </div>
            @endif

            @if ($isPoster)
                <div class="flex items-start bg-neutral-50 p-4 rounded-lg transition-all hover:shadow-sm">
                    <div class="bg-primary/10 p-2 rounded-lg mr-3">
                        <x-icon name="user" class="h-5 w-5 text-primary" />
                    </div>
                    <div>
                        <p class="text-xs text-neutral-500 mb-1">Applicant</p>
                        <p class="text-sm font-medium text-neutral-700">
                            {{ $engagement->application->applicant->name }}</p>
                    </div>
                </div>
            @endif

            <div class="flex items-start bg-neutral-50 p-4 rounded-lg transition-all hover:shadow-sm">
                <div class="bg-secondary/10 p-2 rounded-lg mr-3">
                    <x-icon name="envelope" class="h-5 w-5 text-secondary" />
                </div>
                <div>
                    <p class="text-xs text-neutral-500 mb-1">
                        {{ $isApplicant ? 'Application Date' : 'Applied On' }}
                    </p>
                    <p class="text-sm font-medium text-neutral-700">
                        <x-date :date="$engagement->application->created_at" /></p>
                </div>
            </div>

            <!-- Cancel Engagement or Budget Card -->
            @if (
                $engagement->status !== EngagementStatus::Completed &&
                    $engagement->status !== EngagementStatus::Cancelled &&
                    $engagement->status !== EngagementStatus::Settled &&
                    $engagement->status !== EngagementStatus::Disputed)
                <!-- Cancel Engagement Card  -->
                <div
                    class="flex items-start bg-red-50 p-4 rounded-lg transition-all hover:shadow-md border border-red-100">
                    <div class="bg-red-100 p-2 rounded-lg mr-3">
                        <x-icon name="x-mark" class="h-5 w-5 text-red-600" />
                    </div>
                    <div>
                        <p class="text-xs text-red-500 mb-1">Engagement Actions</p>
                        <button type="button" x-data
                            class="text-sm font-medium text-red-600 flex items-center hover:text-red-800 transition-colors"
                            x-on:click="$dispatch('open-modal', 'cancel-engagement-{{ $engagement->id }}')">
                            <span>Cancel Engagement</span>
                            <x-icon name="chevron-right" class="h-4 w-4 ml-1" />
                        </button>
                    </div>
                </div>
            @else
                <!-- Budget Card -->
                <div class="flex items-start bg-neutral-50 p-4 rounded-lg transition-all hover:shadow-sm">
                    <div class="bg-primary/10 p-2 rounded-lg mr-3">
                        <x-icon name="coins" class="h-5 w-5  text-primary" fill="currentColor" />
                    </div>
                    <div>
                        <p class="text-xs text-neutral-500 mb-1">Budget</p>
                        <p class="text-sm font-medium text-neutral-700">
                            <x-money :amount="$engagement->agreed_amount" />
                        </p>
                    </div>
                </div>
            @endif
        </div>

        <!-- Action buttons -->
        @if ($isApplicant && $engagement->status === EngagementStatus::EmployerAccepted)
            <div class="pt-2 flex justify-end">
                <a href="{{ route('engagements.response-form', ['applicationId' => $engagement->application_id]) }}"
                    class="inline-flex items-center px-5 py-2.5 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-gradient-to-r from-primary to-primary/90 hover:from-primary/90 hover:to-primary transition-all duration-300">
                    <x-icon name="chat-bubble-text" class="h-4 w-4 mr-2" stroke-width="1.5" />
                    Respond to Offer
                </a>
            </div>
        @endif

        <div class="pt-2 flex justify-end gap-2">
            {{-- Settle Engagement (only if cancelled and has relevant deliverables) --}}
            @if ($engagement->isCancelled() && $engagement->hasSubmittedOrApprovedDeliverables())
                <a href="{{ route('engagements.show-cancelled', $engagement->id) }}"
                    class="px-5 py-2.5 bg-secondary text-white text-sm font-medium rounded-lg shadow-sm hover:bg-secondary/90 transition-colors inline-flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="h-4 w-4 mr-2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M6.633 10.25c.806 0 1.533-.446 2.031-1.08a9.041 9.041 0 0 1 2.861-2.4c.723-.384 1.35-.956 1.653-1.715a4.498 4.498 0 0 0 .322-1.672V2.75a.75.75 0 0 1 .75-.75 2.25 2.25 0 0 1 2.25 2.25c0 1.152-.26 2.243-.723 3.218-.266.558.107 1.282.725 1.282m0 0h3.126c1.026 0 1.945.694 2.054 1.715.045.422.068.85.068 1.285a11.95 11.95 0 0 1-2.649 7.521c-.388.482-.987.729-1.605.729H13.48c-.483 0-.964-.078-1.423-.23l-3.114-1.04a4.501 4.501 0 0 0-1.423-.23H5.904m10.598-9.75H14.25M5.904 18.5c.083.205.173.405.27.602.197.4-.078.898-.523.898h-.908c-.889 0-1.713-.518-1.972-1.368a12 12 0 0 1-.521-3.507c0-1.553.295-3.036.831-4.398C3.387 9.953 4.167 9.5 5 9.5h1.053c.472 0 .745.556.5.96a8.958 8.958 0 0 0-1.302 4.665c0 1.194.232 2.333.654 3.375Z" />
                    </svg>
                    Settle Engagement
                </a>
            @endif
            <!-- Leave Review Button -->
            @if ($engagement->isCompleted() || $engagement->isCancelled() || $engagement->isSettled())
                @if (!$engagement->hasBeenReviewedByUser())
                    <button
                        @click="$dispatch('open-modal', { name: 'review-engagement', id: {{ $engagement->id }}, status: '{{ $engagement->status->value }}' })"
                        class="inline-flex items-center px-5 py-2.5 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-gradient-to-r from-primary to-primary/90 hover:from-primary/90 hover:to-primary transition-all duration-300">
                        <x-icon name="chat-bubble-text" class="h-4 w-4 mr-2" stroke-width="1.5" />
                        Leave a Review
                    </button>
                @else
                    <div class="w-full flex justify-between items-center">
                        <!-- Archive Button -->
                        <button @click="$dispatch('open-modal', { name: 'archive-engagement', id: {{ $engagement->id }} })"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition duration-200">
                            <x-icon name="archive-box-3" class="h-5 w-5 mr-1" stroke-width="1.5" />
                            Archive
                        </button>

                        <span
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-green-700 bg-green-100 rounded-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="h-5 w-5 mr-1">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
                            </svg>
                            Already Reviewed
                        </span>
                    </div>
                @endif
            @endif
            <!-- Reopen Job Section (for clients only) -->
            @if (in_array($engagement->status, [EngagementStatus::Cancelled, EngagementStatus::Settled]) &&
                    Auth::id() === $engagement->application->poster_id &&
                    !$engagement->application->job->is_active)
                <form action="{{ route('engagements.reopen-job', $engagement) }}" method="POST">
                    @csrf
                    <button type="submit"
                        class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium  text-sm shadow-sm rounded-lg transition duration-200 inline-flex items-center whitespace-nowrap">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z"
                                clip-rule="evenodd" />
                        </svg>
                        Reopen Job
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
