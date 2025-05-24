<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-tertiary font-bold text-2xl text-primary leading-tight flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="h-6 w-6 mr-2">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                </svg>
                {{ __('Archived Engagements') }}
            </h2>
            <a href="{{ route('engagements.index') }}"
                class="inline-flex items-center px-4 py-2 bg-secondary text-white rounded-lg hover:bg-secondary/90 transition-colors duration-200 font-main text-sm font-medium shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="h-5 w-5 mr-2">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M7 16l-4-4m0 0l4-4m-4 4h18" />
                </svg>
                Back to Active Engagements
            </a>
        </div>
    </x-slot>

    <div class="py-8 bg-gradient-to-br from-neutral-50 to-neutral-100 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Main Content Card -->
            <div class="bg-white rounded-2xl shadow-xl border border-neutral-200 overflow-hidden">
                @if ($archivedEngagements->isEmpty())
                    <!-- Empty State -->
                    <div class="text-center py-16 px-6">
                        <!-- Icon Container -->
                        <div class="relative inline-flex items-center justify-center w-24 h-24 mb-6">
                            <div class="absolute inset-0 bg-gradient-to-r from-primary/10 to-secondary/10 rounded-full">
                            </div>
                            <div
                                class="relative flex items-center justify-center w-16 h-16 bg-gradient-to-r from-primary to-secondary rounded-full shadow-lg">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                </svg>
                            </div>
                        </div>

                        <h3 class="text-xl font-semibold font-main text-neutral-900 mb-2">No Archived Engagements</h3>
                        <p class="text-neutral-500 font-secondary mb-6 max-w-md mx-auto">Your archived engagements will
                            appear here once you complete or archive your work projects.</p>
                    </div>
                @else
                    <!-- Content Header -->
                    <div class="bg-gradient-to-r from-primary/5 to-secondary/5 px-6 py-4 border-b border-neutral-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="flex items-center space-x-2">
                                    <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M3.75 6.75h16.5M3.75 12H12m-8.25 5.25h16.5" />
                                    </svg>
                                    <span
                                        class="text-sm font-medium font-secondary text-neutral-700">{{ $archivedEngagements->total() }}
                                        Total Archived</span>
                                </div>
                            </div>

                            <!-- Filter Options -->
                            <div class="flex items-center space-x-2">
                                <select
                                    class="text-sm border border-neutral-300 rounded-lg px-3 py-2 font-secondary focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                    <option>All Status</option>
                                    <option>Completed</option>
                                    <option>Settled</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Engagements Grid -->
                    <div class="p-6">
                        <div class="grid gap-6">
                            @foreach ($archivedEngagements as $engagement)
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
                                                            <svg class="w-6 h-6 text-primary" fill="none"
                                                                stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0 1 12 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 0 1-.673-.38m0 0A2.18 2.18 0 0 1 3 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 0 1 3.413-.387m7.5 0V5.25A2.25 2.25 0 0 0 13.5 3h-3a2.25 2.25 0 0 0-2.25 2.25v.894m7.5 0a48.667 48.667 0 0 0-7.5 0M12 12.75h.008v.008H12v-.008Z" />
                                                            </svg>
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
                                                @php
                                                    $statusClasses = $engagement->getStatusClasses();
                                                @endphp
                                                <span
                                                    class="inline-flex items-center px-3 py-1 text-xs font-semibold font-secondary rounded-full 
                                                {{ $statusClasses['bg'] }} {{ $statusClasses['text'] }} border {{ $statusClasses['border'] }}">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        {!! $engagement->statusIconPath !!}
                                                    </svg>
                                                    {{ $engagement->statusLabel }}
                                                </span>
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
                                                    <svg class="w-4 h-4 text-accent" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <p class="text-lg font-bold font-main text-neutral-900">
                                                        Ksh{{ number_format($engagement->agreed_amount, 2) }}</p>
                                                    <p class="text-xs text-neutral-500 font-secondary">Total Amount</p>
                                                </div>
                                            </div>

                                            <!-- Date -->
                                            <div class="flex items-center space-x-2">
                                                <div
                                                    class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-tertiary/10 to-tertiary/5 rounded-lg flex items-center justify-center">
                                                    <svg class="w-4 h-4 text-tertiary" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-medium font-secondary text-neutral-900">
                                                        {{ $engagement->updated_at->format('M d, Y') }}</p>
                                                    <p class="text-xs text-neutral-500 font-secondary">Archived Date
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Card Footer -->
                                    <div
                                        class="px-6 py-4 bg-gradient-to-r from-neutral-50 to-white border-t border-neutral-100">
                                        <div class="flex items-center justify-between">
                                            <div
                                                class="flex items-center space-x-1 text-xs text-neutral-500 font-secondary">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <span>Last updated
                                                    {{ $engagement->updated_at->diffForHumans() }}</span>
                                            </div>

                                            <div class="flex items-center space-x-3">
                                                <a href="{{ route('engagements.archived-details', $engagement->id) }}"
                                                    class="inline-flex items-center px-3 py-2 text-sm font-medium font-secondary text-primary bg-primary/5 border border-primary/20 rounded-lg hover:bg-primary/10 hover:border-primary/30 transition-all duration-200">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                    View Details
                                                </a>

                                                <form action="{{ route('engagements.restore') }}" method="POST"
                                                    class="inline">
                                                    @csrf
                                                    <input type="hidden" name="engagement_id"
                                                        value="{{ $engagement->id }}">
                                                    <button type="submit"
                                                        class="inline-flex items-center px-3 py-2 text-sm font-medium font-secondary text-secondary bg-secondary/5 border border-secondary/20 rounded-lg hover:bg-secondary/10 hover:border-secondary/30 transition-all duration-200">
                                                        <svg class="w-4 h-4 mr-1" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                        </svg>
                                                        Restore
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Pagination -->
                    @if ($archivedEngagements->hasPages())
                        <div
                            class="px-6 py-4 bg-gradient-to-r from-neutral-50 to-white border-t border-neutral-200 font-secondary">
                            {{ $archivedEngagements->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>

    <!-- Restore Confirmation Modal -->
    <div id="restore-modal" x-data="{ open: false, engagementId: null }" x-cloak>
        <!-- Modal backdrop -->
        <div x-show="open" class="fixed inset-0 z-40 bg-black bg-opacity-25" @click="open = false"></div>

        <!-- Modal content -->
        <div x-show="open" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Restore Engagement</h3>
                    <p class="text-gray-600 mb-4">
                        Are you sure you want to restore this engagement? It will be moved back to your active
                        engagements list.
                    </p>
                    <div class="flex justify-end space-x-3 mt-6">
                        <button @click="open = false"
                            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <form id="restore-form" action="{{ route('engagements.restore') }}" method="POST">
                            @csrf
                            <input type="hidden" name="engagement_id" x-bind:value="engagementId">
                            <button type="submit"
                                class="px-4 py-2 bg-green-600 border border-transparent rounded-md text-white hover:bg-green-700">
                                Restore
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
