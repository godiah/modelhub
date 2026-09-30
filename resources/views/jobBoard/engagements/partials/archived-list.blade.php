@if ($archivedEngagements->isEmpty())
    <!-- Empty State -->
    <div class="text-center py-16 px-6">
        <!-- Icon Container -->
        <div class="relative inline-flex items-center justify-center w-24 h-24 mb-6">
            <div class="absolute inset-0 bg-gradient-to-r from-primary/10 to-secondary/10 rounded-full">
            </div>
            <div
                class="relative flex items-center justify-center w-16 h-16 bg-gradient-to-r from-primary to-secondary rounded-full shadow-lg">
                <x-icon name="archive-box" class="w-8 h-8 text-white" />
            </div>
        </div>
        <h3 class="text-xl font-semibold font-main text-neutral-900 mb-2">No Current Engagements Found</h3>
    </div>
@else
    <div class="p-6">
        <div class="grid gap-6" id="engagements-grid">
            @forelse ($archivedEngagements as $engagement)
                <div
                    class="group bg-gradient-to-r from-white to-neutral-50 rounded-xl border border-neutral-200 hover:border-secondary/30 hover:shadow-lg transition-all duration-300 overflow-hidden">
                    <!-- Card Header -->
                    <div class="p-6 pb-4">
                        <div class="flex items-start justify-between">
                            <!-- Job Information -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center space-x-3 mb-3">
                                    <div class="flex-shrink-0">
                                        <div
                                            class="w-12 h-12 bg-gradient-to-br from-primary/10 to-secondary/10 rounded-lg flex items-center justify-center">
                                            <x-icon name="briefcase" class="w-6 h-6 text-primary" />
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h3
                                            class="text-lg font-semibold font-main text-neutral-900 truncate group-hover:text-primary transition-colors">
                                            {{ $engagement->application->job->title }}
                                        </h3>
                                    </div>
                                </div>
                            </div>

                            <!-- Status Badge -->
                            <div class="flex-shrink-0 ml-4">
                                <x-engagement.status-badge :status="$engagement->status" class="px-3 py-1 text-xs font-semibold font-secondary" />
                            </div>
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="px-6 pb-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- User Information -->
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0">
                                    @php
                                        $user =
                                            Auth::user()->id === $engagement->application->poster_id
                                                ? $engagement->application->applicant
                                                : $engagement->application->poster;
                                    @endphp
                                    <div
                                        class="w-10 h-10 rounded-full ring-2 ring-neutral-200 bg-gradient-to-br from-primary to-secondary flex items-center justify-center">
                                        <span class="text-white font-semibold font-main text-sm">
                                            {{ $user->getInitials() }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium font-secondary text-neutral-900">
                                        @if (Auth::user()->id === $engagement->application->poster_id)
                                            {{ $engagement->application->applicant->name }}
                                        @else
                                            {{ $engagement->application->poster->name }}
                                        @endif
                                    </p>
                                    <p class="text-xs text-neutral-500 font-secondary">
                                        @if (Auth::user()->id === $engagement->application->poster_id)
                                            Freelancer
                                        @else
                                            Client
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <!-- Amount -->
                            <div class="flex items-center space-x-2">
                                <div
                                    class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-accent/10 to-accent/5 rounded-lg flex items-center justify-center">
                                    <x-icon name="banknotes" class="w-4 h-4 text-accent" />
                                </div>
                                <div>
                                    <p class="text-lg font-bold font-main text-neutral-900">
                                        <x-money :amount="$engagement->agreed_amount" /></p>
                                    <p class="text-xs text-neutral-500 font-secondary">Total Amount</p>
                                </div>
                            </div>

                            <!-- Date -->
                            <div class="flex items-center space-x-2">
                                <div
                                    class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-tertiary/10 to-tertiary/5 rounded-lg flex items-center justify-center">
                                    <x-icon name="calendar" class="w-4 h-4 text-tertiary" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium font-secondary text-neutral-900">
                                        <x-date :date="$engagement->updated_at" format="M d, Y" /></p>
                                    <p class="text-xs text-neutral-500 font-secondary">Archived Date
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card Footer -->
                    <div class="px-6 py-4 bg-gradient-to-r from-neutral-50 to-white border-t border-neutral-100">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-1 text-xs text-neutral-500 font-secondary">
                                <x-icon name="clock" class="w-4 h-4" />
                                <span>Last updated
                                    {{ $engagement->updated_at->diffForHumans() }}</span>
                            </div>

                            <div class="flex items-center space-x-3">
                                <x-btn variant="secondary" href="{{ route('engagements.archived-details', $engagement->id) }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    View Details
                                </x-btn>

                                <form action="{{ route('engagements.restore') }}" method="POST" class="inline">
                                    @csrf
                                    <input type="hidden" name="engagement_id" value="{{ $engagement->id }}">
                                    <x-btn variant="secondary" type="submit">
                                        <x-icon name="arrow-path" class="w-4 h-4" />
                                        Restore
                                    </x-btn>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-8">
                    <p class="text-neutral-500">No engagements found for the selected filter.</p>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        <div class="mt-6 font-secondary" id="pagination-container">
            {{ $archivedEngagements->appends(request()->query())->links() }}
        </div>
    </div>
@endif
