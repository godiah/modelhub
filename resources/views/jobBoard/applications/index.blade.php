<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-tertiary font-bold text-2xl text-primary leading-tight">
                    {{ __('My Job Applications') }}
                </h2>
                {{-- <p class="text-tertiary mt-2 font-main text-xs">Track and manage your job applications</p> --}}
            </div>
            <a href="{{ route('jobs.browse') }}"
                class="inline-flex items-center px-4 py-2 bg-secondary text-white rounded-lg hover:bg-secondary/90 transition-colors duration-200 font-main text-sm font-medium shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                Browse More Jobs
            </a>
        </div>
    </x-slot>

    <section class="bg-gradient-to-br from-neutral-50 to-neutral-100">
        <div class="container mx-auto max-w-7xl px-4 py-12 min-h-screen">
            <!-- Applications Container -->
            @if ($applications->isEmpty())
                <div class="bg-white p-8 rounded-2xl shadow-lg border border-neutral-200 relative overflow-hidden">
                    <!-- Background Pattern -->
                    <div class="absolute inset-0 opacity-5">
                        <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
                            <pattern id="dots" width="20" height="20" patternUnits="userSpaceOnUse">
                                <circle cx="10" cy="10" r="2" fill="currentColor" class="text-primary" />
                            </pattern>
                            <rect width="100%" height="100%" fill="url(#dots)" />
                        </svg>
                    </div>

                    <div class="text-center py-16 relative z-10">
                        <div
                            class="bg-neutral-100 h-24 w-24 mx-auto rounded-full flex items-center justify-center mb-6">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-tertiary" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-neutral-800 mb-3 font-tertiary">No Applications Yet</h3>
                        <p class="text-tertiary mb-8 max-w-lg mx-auto font-secondary text-lg">You haven't submitted any
                            applications yet. Start your career journey by exploring available opportunities.</p>
                        <a href="{{ route('jobs.browse') }}"
                            class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-accent to-accent/90 text-white rounded-lg hover:shadow-md transition-all duration-300 font-main font-medium">
                            Find Jobs
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </a>
                    </div>
                </div>
            @else
                <!-- Filters/Stats Bar -->
                <div
                    class="bg-white rounded-xl shadow-sm mb-6 p-4 border border-neutral-200 flex flex-wrap items-center justify-between">
                    <div class="flex items-center space-x-2 text-tertiary font-main">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-secondary" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <span>Total: <strong>{{ $applications->total() }}</strong> Applications</span>
                    </div>

                    <div class="flex mt-2 md:mt-0">
                        <select
                            class="rounded-lg border-neutral-200 text-sm font-main focus:border-secondary focus:ring focus:ring-secondary/20 mr-2">
                            <option>All Statuses</option>
                            <option>Submitted</option>
                            <option>Reviewed</option>
                            <option>Hired</option>
                            <option>Rejected</option>
                        </select>
                        <select
                            class="rounded-lg border-neutral-200 text-sm font-main focus:border-secondary focus:ring focus:ring-secondary/20">
                            <option>Sort by Date</option>
                            <option>Sort by Status</option>
                        </select>
                    </div>
                </div>

                <div class="grid gap-6 md:grid-cols-1">
                    @foreach ($applications as $application)
                        <div
                            class="bg-white rounded-xl shadow-sm hover:shadow-md transition-all duration-200 border border-neutral-200 overflow-hidden group">
                            <!-- Job Title -->
                            <div class="p-6 flex flex-col md:flex-row gap-6 items-start md:items-center">
                                <div class="flex items-center flex-grow">
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
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="flex items-center">
                                            <h2
                                                class="text-lg font-bold text-neutral-800 font-tertiary group-hover:text-primary transition-colors">
                                                <a
                                                    href="{{ route('applications.show', ['job' => $application->job->slug]) }}">
                                                    {{ $application->job->title }}
                                                </a>
                                            </h2>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-3 mt-2">
                                            <p class="text-sm text-tertiary font-main">
                                                <span class="inline-flex items-center">
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="h-4 w-4 mr-1 text-secondary" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
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

                                    <span
                                        class="px-4 py-2 inline-flex items-center text-sm font-medium rounded-full border {{ $statusClass }}">
                                        <span
                                            class="h-2 w-2 rounded-full {{ $dotClass }} mr-2 pulse-animation"></span>
                                        {{ ucfirst($application->status) }}
                                    </span>

                                    <div class="flex space-x-1">
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
                                        <button type="button"
                                            class="p-2 text-tertiary hover:text-primary bg-neutral-50 hover:bg-neutral-100 rounded-full transition-colors"
                                            title="Message Hiring Manager">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                            </svg>
                                        </button>
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
        </div>

        <!-- Footer -->
        @include('partials\footer-secondary')
    </section>

    <style>
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(20, 184, 166, 0.4);
            }

            70% {
                box-shadow: 0 0 0 6px rgba(20, 184, 166, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(20, 184, 166, 0);
            }
        }

        .pulse-animation {
            animation: pulse 2s infinite;
        }
    </style>
</x-app-layout>
