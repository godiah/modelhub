@use('App\Enums\ApplicationStatus')

@if ($applications->isEmpty() && $hasFilters)
    <x-empty-state icon="magnifying-glass" title="No Applications Found" description="We couldn't find any applications matching your current search criteria. Try adjusting your filters or search terms.">
        <x-button href="#" id="clearFilters">
            <x-icon name="arrow-path" class="w-4 h-4 mr-2" />
            Clear All Filters
        </x-button>
    </x-empty-state>
@else
    <div class="space-y-6">
        @foreach ($applications as $application)
            <div
                class="border border-neutral-200 rounded-lg p-6 hover:shadow-md transition bg-gradient-to-r from-white to-neutral-50/50">
                <!-- Applicant Info -->
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start">
                    <div class="flex items-center">
                        <!-- Applicant Avatar -->
                        <div
                            class="font-main flex items-center justify-center h-10 w-10 rounded-full bg-primary/10 text-primary font-medium mr-3 border border-primary/20">
                            {{ $application->applicant->getInitials() }}
                        </div>

                        <div>
                            <h4 class="font-secondary font-medium text-neutral-900">
                                {{ $application->applicant->name }}
                            </h4>
                            <p class="text-sm text-neutral-500 flex items-center font-secondary">
                                <x-icon name="clock" class="h-4 w-4 mr-1" />
                                Applied {{ $application->created_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>

                    <!-- Application Status -->
                    <div class="flex items-center mt-4 sm:mt-0 font-main">
                        <span class="mr-3 flex items-center">
                            @if ($application->job->hasAcceptedEngagement() && !$application->job->is_active && $application->status !== ApplicationStatus::Hired)
                                <x-badge tone="amber" class="px-3 py-1 text-sm font-medium border border-amber-200">
                                    <span class="h-2 w-2 rounded-full bg-amber-500 mr-2"></span>
                                    Position Filled
                                </x-badge>
                            @elseif ($application->status === ApplicationStatus::Submitted)
                                <x-badge tone="yellow" class="px-3 py-1 text-sm font-medium border border-yellow-200">
                                    <span class="h-2 w-2 rounded-full bg-yellow-500 mr-2"></span>
                                    New Application
                                </x-badge>
                            @elseif ($application->status === ApplicationStatus::Reviewed)
                                <x-badge tone="blue" class="px-3 py-1 text-sm font-medium border border-blue-200">
                                    <span class="h-2 w-2 rounded-full bg-blue-500 mr-2"></span>
                                    Reviewed
                                </x-badge>
                            @elseif ($application->status === ApplicationStatus::Hired)
                                <x-badge tone="green" class="px-3 py-1 text-sm font-medium border border-green-200">
                                    <span class="h-2 w-2 rounded-full bg-green-600 mr-2"></span>
                                    Hired
                                </x-badge>
                            @elseif ($application->status === ApplicationStatus::Rejected)
                                <x-badge tone="red" class="px-3 py-1 text-sm font-medium border border-red-200">
                                    <span class="h-2 w-2 rounded-full bg-red-500 mr-2"></span>
                                    Rejected
                                </x-badge>
                            @elseif($application->status === ApplicationStatus::Withdrawn)
                                <span
                                    class="px-3 py-1 inline-flex items-center text-sm font-medium rounded-full bg-neutral-100 text-rose-800 border border-neutral-200">
                                    <span class="h-2 w-2 rounded-full bg-rose-500 mr-2"></span>
                                    Withdrawn
                                </span>
                            @else
                                <span
                                    class="px-3 py-1 inline-flex items-center text-sm font-medium rounded-full bg-neutral-100 text-neutral-800 border border-neutral-200">
                                    <span class="h-2 w-2 rounded-full bg-neutral-500 mr-2"></span>
                                    {{ $application->status->label() }}
                                </span>
                            @endif
                        </span>
                        @if (!$application->job->hasAcceptedEngagement() && $application->job->is_active && $application->status !== ApplicationStatus::Hired)
                            <div x-data="{ open: false }" class="relative">
                                <button @click="open = !open" class="p-1 rounded-full hover:bg-neutral-100">
                                    <svg class="h-5 w-5 text-neutral-400 hover:text-primary" fill="currentColor"
                                        viewBox="0 0 20 20">
                                        <path
                                            d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z">
                                        </path>
                                    </svg>
                                </button>

                                <div x-show="open" @click.away="open = false"
                                    class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border border-neutral-200">
                                    <div class="py-1">
                                        <form method="POST"
                                            action="{{ route('my-jobs.applications.update-status', $application->id) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="reviewed">
                                            <button type="submit"
                                                class="block w-full text-left px-4 py-2 text-sm text-neutral-700 hover:bg-neutral-50 hover:text-primary">
                                                <span class="flex items-center">
                                                    <x-icon name="check-circle" class="h-4 w-4 mr-2 text-blue-500" />
                                                    Mark as Reviewed
                                                </span>
                                            </button>
                                        </form>

                                        <form method="POST"
                                            action="{{ route('my-jobs.applications.update-status', $application->id) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="hired">
                                            <button type="submit"
                                                class="block w-full text-left px-4 py-2 text-sm text-neutral-700 hover:bg-neutral-50 hover:text-primary">
                                                <span class="flex items-center">
                                                    <x-icon name="check-circle" class="h-4 w-4 mr-2 text-green-500" />
                                                    Mark as Hired
                                                </span>
                                            </button>
                                        </form>

                                        <form method="POST"
                                            action="{{ route('my-jobs.applications.update-status', $application->id) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="rejected">
                                            <button type="submit"
                                                class="block w-full text-left px-4 py-2 text-sm text-neutral-700 hover:bg-neutral-50 hover:text-primary">
                                                <span class="flex items-center">
                                                    <x-icon name="x-mark" class="h-4 w-4 mr-2 text-red-500" />
                                                    Mark as Rejected
                                                </span>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 mt-4">
                    <!-- Proposal -->
                    <div class="md:col-span-9">
                        <x-card rounded="lg" shadow="none" class="p-4">
                            <h4 class="text-sm font-secondary font-medium text-neutral-800 flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-secondary"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                </svg>
                                Proposal
                            </h4>
                            <div class="mt-2 mb-2 text-sm font-main">
                                @if ($application->proposal)
                                    <div
                                        class="text-neutral-700 text-justify bg-neutral-50 p-4 rounded-md border border-neutral-100 max-h-32 overflow-y-auto">
                                        {!! nl2br(e($application->proposal)) !!}
                                    </div>
                                @else
                                    <div
                                        class="flex items-center justify-center py-6 text-neutral-400 bg-neutral-50 rounded-md border border-neutral-100">
                                        <x-icon name="exclamation-triangle-3" class="h-6 w-6 mr-2" />
                                        <span class="text-sm">No proposal provided by the
                                            applicant</span>
                                    </div>
                                @endif
                            </div>
                        </x-card>
                    </div>

                    <!-- Bid Amount & Actions -->
                    <div class="md:col-span-3">
                        <x-card rounded="lg" shadow="none" class="p-4 h-full flex flex-col justify-between">
                            <!-- Bid Amount -->
                            @if (isset($application->offer_amount))
                                <div>
                                    <h4 class="text-sm font-secondary font-medium text-neutral-800 flex items-center">
                                        <x-icon name="coins" class="h-4 w-4 mr-1.5 text-accent" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        Bid Amount
                                    </h4>
                                    <p class="mt-1 text-xl font-semibold font-main text-primary">
                                        <x-money :amount="$application->offer_amount" />
                                    </p>
                                    <p class="text-xs text-neutral-500 font-secondary">
                                        @php
                                            $offerAmount = $application->offer_amount;
                                            $budget = $job->budget;
                                            $comparison = '';

                                            if ($budget == 0) {
                                                $comparison = 'Budget is zero, cannot calculate percentage difference';
                                            } elseif ($offerAmount == 0) {
                                                $comparison =
                                                    'Offer amount is zero, cannot calculate percentage difference';
                                            } else {
                                                if ($offerAmount > $budget) {
                                                    $percentageDifference = ($offerAmount / $budget) * 100 - 100;
                                                    $roundedDifference = round($percentageDifference);
                                                    $comparison = $roundedDifference . '% above budget';
                                                } else {
                                                    $percentageDifference = ($budget / $offerAmount) * 100 - 100;
                                                    $roundedDifference = round($percentageDifference);
                                                    $comparison = $roundedDifference . '% below budget';
                                                }
                                            }
                                        @endphp
                                        {{ $comparison }}
                                    </p>
                                </div>
                            @else
                                <div>
                                    <h4 class="text-sm font-secondary font-medium text-neutral-800 flex items-center">
                                        <x-icon name="currency-dollar" class="h-4 w-4 mr-1 text-accent" />
                                        Bid Amount
                                    </h4>
                                    <p class="text-sm text-neutral-500 italic font-main">
                                        No bid provided
                                    </p>
                                </div>
                            @endif

                            <!-- Actions -->
                            <div class="mt-3 font-main">
                                @if ($application->job->hasAcceptedEngagement() && !$application->job->is_active)
                                    @if ($application->status === ApplicationStatus::Hired)
                                        <a href="{{ route('my-jobs.applications.show', ['application' => $application->id]) }}"
                                            class="block w-full text-center px-4 py-2 text-sm font-medium bg-primary text-white rounded hover:bg-primary/90 transition shadow-sm">
                                            View Full Details
                                        </a>
                                        <a href="#"
                                            class="block w-full text-center px-4 py-2 text-sm font-medium bg-white text-primary border border-primary rounded mt-2 hover:bg-primary/5 transition">
                                            Contact Freelancer
                                        </a>
                                    @else
                                        <button disabled
                                            class="block w-full text-center px-4 py-2 text-sm font-medium bg-neutral-100 text-neutral-400 rounded cursor-not-allowed border border-neutral-200">
                                            Position Filled
                                        </button>
                                    @endif
                                @else
                                    <a href="{{ route('my-jobs.applications.show', ['application' => $application->id]) }}"
                                        class="block w-full text-center px-4 py-2 text-sm font-medium bg-primary text-white rounded hover:bg-primary/90 transition shadow-sm">
                                        View Full Details
                                    </a>
                                    <a href="#"
                                        class="block w-full text-center px-4 py-2 text-sm font-medium bg-white text-primary border border-primary rounded mt-2 hover:bg-primary/5 transition">
                                        Contact Applicant
                                    </a>
                                @endif
                            </div>
                        </x-card>
                    </div>
                </div>
            </div>
        @endforeach

        <!-- Pagination -->
        <div class="mt-8">
            {{ $applications->links() }}
        </div>
    </div>
@endif
