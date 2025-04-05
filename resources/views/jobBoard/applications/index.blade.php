<x-app-layout>
    <section class="bg-neutral-50">
        <div class="container mx-auto max-w-7xl px-4 py-8 min-h-screen">
            <!-- Header Section -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-primary font-tertiary">My Applications</h1>
                    <p class="text-tertiary mt-2 font-main">Track and manage your job applications</p>
                </div>
                <div>
                    <a href="{{ route('jobs.browse') }}"
                        class="inline-flex items-center px-4 py-2 bg-secondary text-white rounded-lg hover:bg-secondary/90 transition-colors font-main text-sm font-medium">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        Browse More Jobs
                    </a>
                </div>
            </div>

            <!-- Applications Container -->
            @if ($applications->isEmpty())
                <div class="bg-white p-8 rounded-xl shadow-lg border border-neutral-200">
                    <div class="text-center py-12">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-neutral-300 mb-4"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="text-xl font-semibold text-neutral-800 mb-2 font-secondary">No Applications Yet</h3>
                        <p class="text-neutral-500 mb-6 max-w-md mx-auto font-secondary">You haven't submitted any
                            applications yet.
                            Start your career journey by exploring available opportunities.</p>
                        <a href="{{ route('jobs.browse') }}"
                            class="inline-flex items-center px-5 py-3 bg-accent text-white rounded-lg hover:bg-accent/90 transition-colors font-main font-medium">
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
                <div class="grid gap-6 md:grid-cols-1">
                    @foreach ($applications as $application)
                        <div
                            class="bg-white rounded-xl shadow-md hover:shadow-lg transition-shadow border border-neutral-200 overflow-hidden group">
                            <div class="p-6 flex flex-col md:flex-row gap-6 items-start md:items-center">
                                <!-- Job Image & Title -->
                                <div class="flex items-center flex-grow">
                                    <div
                                        class="h-16 w-16 rounded-lg bg-neutral-100 flex items-center justify-center overflow-hidden mr-4 border border-neutral-200">
                                        @if ($application->job->images)
                                            <img src="{{ asset('storage/' . $application->job->images) }}"
                                                alt="{{ $application->job->slug }}" class="h-full w-full object-cover">
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-neutral-400"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                        @endif
                                    </div>
                                    <div>
                                        <h2
                                            class="text-lg font-semibold text-neutral-800 font-secondary group-hover:text-primary transition-colors">
                                            <a
                                                href="{{ route('applications.show', ['job' => $application->job->slug]) }}">
                                                {{ $application->job->title }}
                                            </a>
                                        </h2>
                                        <p class="text-sm text-neutral-500 font-main mt-1">
                                            Applied on <span
                                                class="font-medium">{{ $application->created_at->format('M d, Y') }}</span>
                                        </p>
                                    </div>
                                </div>

                                <!-- Status Badge -->
                                <div class="flex items-center space-x-2">
                                    @if ($application->status === 'submitted')
                                        <span
                                            class="px-3 py-1 inline-flex items-center text-sm font-medium rounded-full bg-green-100 text-green-800">
                                            <span class="h-2 w-2 rounded-full bg-green-500 mr-2"></span>
                                            Submitted
                                        </span>
                                    @elseif($application->status === 'reviewed')
                                        <span
                                            class="px-3 py-1 inline-flex items-center text-sm font-medium rounded-full bg-blue-100 text-blue-800">
                                            <span class="h-2 w-2 rounded-full bg-blue-500 mr-2"></span>
                                            Reviewed
                                        </span>
                                    @elseif($application->status === 'hired')
                                        <span
                                            class="px-3 py-1 inline-flex items-center text-sm font-medium rounded-full bg-primary bg-opacity-10 text-primary">
                                            <span class="h-2 w-2 rounded-full bg-primary mr-2"></span>
                                            Hired
                                        </span>
                                    @elseif($application->status === 'rejected')
                                        <span
                                            class="px-3 py-1 inline-flex items-center text-sm font-medium rounded-full bg-red-100 text-red-800">
                                            <span class="h-2 w-2 rounded-full bg-red-500 mr-2"></span>
                                            Rejected
                                        </span>
                                    @else
                                        <span
                                            class="px-3 py-1 inline-flex items-center text-sm font-medium rounded-full bg-neutral-100 text-neutral-800">
                                            <span class="h-2 w-2 rounded-full bg-neutral-500 mr-2"></span>
                                            {{ ucfirst($application->status) }}
                                        </span>
                                    @endif

                                    <a href="{{ route('applications.show', ['job' => $application->job->slug]) }}"
                                        class="p-2 text-tertiary hover:text-primary transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                </div>
                            </div>

                            <!-- Progress Bar -->
                            @if ($application->status === 'submitted')
                                <div class="h-1 bg-neutral-100">
                                    <div class="h-full bg-green-500" style="width: 20%"></div>
                                </div>
                            @elseif($application->status === 'reviewed')
                                <div class="h-1 bg-neutral-100">
                                    <div class="h-full bg-blue-500" style="width: 40%"></div>
                                </div>
                            @elseif($application->status === 'interviewing')
                                <div class="h-1 bg-neutral-100">
                                    <div class="h-full bg-purple-500" style="width: 60%"></div>
                                </div>
                            @elseif($application->status === 'hired')
                                <div class="h-1 bg-neutral-100">
                                    <div class="h-full bg-primary" style="width: 100%"></div>
                                </div>
                            @elseif($application->status === 'rejected')
                                <div class="h-1 bg-neutral-100">
                                    <div class="h-full bg-red-500" style="width: 100%"></div>
                                </div>
                            @else
                                <div class="h-1 bg-neutral-100">
                                    <div class="h-full bg-neutral-300" style="width: 10%"></div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <!-- Pagination - If you have pagination -->
                <div class="mt-8">
                    {{ $applications->links() }}
                </div>
            @endif
        </div>

        <!-- Footer -->
        @include('partials\footer-secondary')
    </section>
</x-app-layout>
