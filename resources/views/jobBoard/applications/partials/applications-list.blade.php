@if ($applications->isEmpty() && $activeFilters)
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-neutral-200">
        <div class="p-12 flex flex-col items-center justify-center text-center font-main">
            <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="w-8 h-8 text-primary">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-neutral-800 mb-2">No Current Applications Found</h3>
            <p class="text-neutral-600 mb-6 max-w-md">
                We couldn't find any present applications matching your current search criteria. Try adjusting your
                filters.
            </p>
        </div>
    </div>
@else
    <div class="grid gap-6 md:grid-cols-1">
        @foreach ($applications as $application)
            @php
                $isDisabled = $application->hasOtherEngagement() || $application->status === 'rejected';
            @endphp
            <div
                class="bg-white rounded-xl shadow-sm hover:shadow-md transition-all duration-200 border border-neutral-200 overflow-hidden group
                {{ $isDisabled ? 'opacity-50' : '' }}">
                <!-- Job Title -->
                <div class="p-6 flex flex-col md:flex-row gap-6 items-start md:items-center">
                    <div class="flex items-center flex-grow {{ $isDisabled ? 'opacity-50 pointer-events-none' : '' }}">
                        <div
                            class="h-16 w-16 rounded-xl bg-neutral-50 flex items-center justify-center overflow-hidden mr-4 border border-neutral-100 shadow-sm">
                            @if ($application->job->images)
                                <img src="{{ asset('storage/' . $application->job->images) }}"
                                    alt="{{ $application->job->slug }}" class="h-full w-full object-cover">
                            @else
                                <div
                                    class="bg-gradient-to-br from-neutral-50 to-neutral-100 h-full w-full flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-secondary"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            @endif
                        </div>
                        <div>
                            <div class="flex items-center">
                                <h2
                                    class="text-lg font-bold text-neutral-800 font-tertiary group-hover:text-primary transition-colors">
                                    <a href="{{ route('applications.show', ['job' => $application->job->slug]) }}">
                                        {{ $application->job->title }}
                                    </a>
                                </h2>
                            </div>
                            <div class="flex flex-wrap items-center gap-3 mt-2">
                                <p class="text-sm text-tertiary font-main">
                                    <span class="inline-flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-secondary"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        Applied {{ $application->created_at->format('M d, Y') }}
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Status Badge & Actions -->
                    <div class="flex flex-col md:flex-row items-start md:items-center gap-3">
                        @php
                            $statusClasses = [
                                'submitted' => 'bg-yellow-100 text-yellow-700 border-yellow-200',
                                'reviewed' => 'bg-blue-50 text-blue-700 border-blue-200',
                                'hired' => 'bg-green-50 text-green-700 border-green-200',
                                'rejected' => 'bg-red-50 text-red-700 border-red-200',
                                'withdrawn' => 'bg-rose-100 text-rose-800',
                            ];

                            $dotClasses = [
                                'submitted' => 'bg-yellow-500',
                                'reviewed' => 'bg-blue-500',
                                'hired' => 'bg-green-500',
                                'rejected' => 'bg-red-500',
                                'withdrawn' => 'bg-rose-500',
                            ];

                            $statusClass =
                                $statusClasses[$application->status] ??
                                'bg-neutral-100 text-neutral-700 border-neutral-200';
                            $dotClass = $dotClasses[$application->status] ?? 'bg-neutral-500';
                        @endphp

                        @if ($application->hasOwnEngagement())
                            <span
                                class="px-4 py-2 inline-flex items-center text-sm font-medium rounded-full border bg-green-50 text-green-700">
                                <span class="h-2 w-2 rounded-full bg-green-500 mr-2 pulse-animation"></span>
                                Hired
                            </span>
                        @elseif ($isDisabled && $application->status !== 'rejected')
                            <span
                                class="px-4 py-2 inline-flex items-center text-sm font-medium rounded-full border bg-gray-100 text-gray-700">
                                <span class="h-2 w-2 rounded-full bg-gray-500 mr-2 pulse-animation"></span>
                                Position Filled
                            </span>
                        @else
                            <span
                                class="px-4 py-2 inline-flex items-center text-sm font-medium rounded-full border {{ $statusClass }}">
                                <span class="h-2 w-2 rounded-full {{ $dotClass }} mr-2 pulse-animation"></span>
                                {{ ucfirst($application->status) }}
                            </span>
                        @endif

                        <div class="flex space-x-2">
                            <a href="{{ route('applications.show', ['job' => $application->job->slug]) }}"
                                class="p-2 text-tertiary hover:text-primary bg-neutral-50 hover:bg-neutral-100 rounded-full transition-colors"
                                title="View Application">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </a>

                            @if ($application->canBeArchived())
                                <form action="{{ route('applications.archive', $application) }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                        class="p-2 text-tertiary hover:text-primary bg-neutral-50 hover:bg-neutral-100 rounded-full transition-colors"
                                        title="Archive Application">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                        </svg>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Pagination with Modern Style -->
    <div class="mt-8">
        {{ $applications->links() }}
    </div>
@endif
