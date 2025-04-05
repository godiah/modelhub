<x-app-layout>
    <section class="bg-neutral-50">
        <div class="container mx-auto max-w-5xl px-4 py-8">
            <!-- Back Navigation -->
            <a href="{{ route('my.applications') }}"
                class="inline-flex items-center text-tertiary hover:text-primary transition-colors mb-6 font-main">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to My Applications
            </a>

            <!-- Application Status Banner -->
            <div
                class="mb-6 rounded-lg p-4 {{ $application->status === 'submitted'
                    ? 'bg-green-100 border-l-4 border-green-500'
                    : ($application->status === 'accepted'
                        ? 'bg-blue-100 border-l-4 border-blue-500'
                        : ($application->status === 'rejected'
                            ? 'bg-red-100 border-l-4 border-red-500'
                            : 'bg-neutral-100 border-l-4 border-neutral-500')) }}">
                <div class="flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="h-6 w-6 mr-3 {{ $application->status === 'submitted'
                            ? 'text-green-500'
                            : ($application->status === 'accepted'
                                ? 'text-blue-500'
                                : ($application->status === 'rejected'
                                    ? 'text-red-500'
                                    : 'text-neutral-500')) }}"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <p class="font-medium text-neutral-800">
                            Application Status: <span
                                class="font-bold {{ $application->status === 'submitted'
                                    ? 'text-green-700'
                                    : ($application->status === 'accepted'
                                        ? 'text-blue-700'
                                        : ($application->status === 'rejected'
                                            ? 'text-red-700'
                                            : 'text-neutral-700')) }}">{{ ucfirst($application->status) }}</span>
                        </p>
                        <p class="text-sm text-neutral-600">
                            Applied on {{ $application->created_at->format('F d, Y') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Job and Application Details -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden border border-neutral-200">
                <!-- Job Header Section -->
                <div class="md:flex">
                    <!-- Job Image -->
                    <div class="md:w-1/3 bg-neutral-100">
                        <div class="h-full w-full aspect-video flex items-center justify-center overflow-hidden">
                            @if ($application->job->images)
                                <img src="{{ asset('storage/' . $application->job->images) }}"
                                    alt="{{ $application->job->title }}" class="h-full w-full object-cover">
                            @else
                                <div class="flex flex-col items-center justify-center text-neutral-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <p class="mt-2">No Image Available</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Job Meta -->
                    <div class="md:w-2/3 p-6">
                        <h1 class="text-2xl font-bold text-neutral-800 font-tertiary">{{ $application->job->title }}
                        </h1>

                        <div class="flex items-center mt-3 text-tertiary text-sm">
                            <span class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                Posted on {{ $application->job->created_at->format('M d, Y') }}
                            </span>
                            <span class="mx-3">•</span>
                            <span class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                By {{ $application->poster->name }}
                            </span>
                        </div>

                        <div class="mt-4 flex">
                            <div class="bg-neutral-100 rounded-lg px-4 py-3 text-center">
                                <p class="text-sm text-neutral-500">Job Budget</p>
                                <p class="text-base font-bold text-primary mt-1">
                                    Ksh{{ number_format($application->job->budget) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-t border-neutral-200"></div>

                <!-- Financial Details Section -->
                <div class="p-6 bg-neutral-50 border-b border-neutral-200">
                    <h2 class="text-lg font-semibold text-neutral-800 mb-4 font-secondary">Financial Details</h2>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-white rounded-lg p-4 border border-neutral-200">
                            <p class="text-sm text-neutral-500 mb-1">Your Offer</p>
                            <p class="text-xl font-bold text-primary">
                                Ksh{{ number_format($application->offer_amount, 2) }}</p>
                        </div>

                        <div class="bg-white rounded-lg p-4 border border-neutral-200">
                            <p class="text-sm text-neutral-500 mb-1">Service Fee</p>
                            <p class="text-xl font-bold text-tertiary">
                                Ksh{{ number_format($application->service_fee, 2) }}</p>
                        </div>

                        <div class="bg-white rounded-lg p-4 border border-neutral-200">
                            <p class="text-sm text-neutral-500 mb-1">You'll Receive</p>
                            <p class="text-xl font-bold text-accent">
                                Ksh{{ number_format($application->net_amount, 2) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Proposal Section -->
                <div class="p-6 border-b border-neutral-200">
                    <h2 class="text-lg font-semibold text-neutral-800 mb-4 font-secondary">Your Proposal</h2>

                    @if ($application->proposal)
                        <div class="bg-white rounded-lg p-4 border border-neutral-200">
                            <div class="prose max-w-none">
                                {!! nl2br(e($application->proposal)) !!}
                            </div>
                        </div>
                    @else
                        <div class="bg-white rounded-lg p-4 border border-neutral-200 flex items-center gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-neutral-400" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div class="text-neutral-500 text-sm">
                                No proposal was provided for this application.
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Portfolio Section -->
                @if (is_array($application->portfolio) && count($application->portfolio) > 0)
                    <div class="p-6">
                        <h2 class="text-lg font-semibold text-neutral-800 mb-4 font-secondary">Portfolio & Attachments
                        </h2>

                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                            @foreach ($application->portfolio as $item)
                                @php
                                    $extension = pathinfo($item, PATHINFO_EXTENSION);
                                    $isImage = in_array(strtolower($extension), [
                                        'jpg',
                                        'jpeg',
                                        'png',
                                        'gif',
                                        'webp',
                                        'svg',
                                    ]);
                                    $isDocument = in_array(strtolower($extension), [
                                        'pdf',
                                        'doc',
                                        'docx',
                                        'txt',
                                        'rtf',
                                    ]);
                                @endphp

                                @if ($isImage)
                                    <!-- Image Preview -->
                                    <div class="bg-white rounded-lg border border-neutral-200 overflow-hidden group">
                                        <div class="aspect-square w-full overflow-hidden bg-neutral-100">
                                            <img src="{{ asset('storage/' . $item) }}" alt="Portfolio image"
                                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                        </div>
                                        <div class="p-3 flex justify-between items-center">
                                            <span
                                                class="text-sm text-neutral-500 truncate">{{ basename($item) }}</span>
                                            <a href="{{ asset('storage/' . $item) }}" target="_blank"
                                                class="text-primary hover:text-primary/80">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                @elseif($isDocument)
                                    <!-- Document Preview -->
                                    <div class="bg-white rounded-lg border border-neutral-200 overflow-hidden">
                                        <div
                                            class="aspect-square w-full flex items-center justify-center bg-neutral-50 p-4">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-neutral-300"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <div class="p-3 flex justify-between items-center">
                                            <span
                                                class="text-sm text-neutral-500 truncate">{{ basename($item) }}</span>
                                            <a href="{{ asset('storage/' . $item) }}" target="_blank"
                                                class="text-primary hover:text-primary/80">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                @else
                                    <!-- Other File Types -->
                                    <div class="bg-white rounded-lg border border-neutral-200 overflow-hidden">
                                        <div
                                            class="aspect-square w-full flex items-center justify-center bg-neutral-50 p-4">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-neutral-300"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <div class="p-3 flex justify-between items-center">
                                            <span
                                                class="text-sm text-neutral-500 truncate">{{ basename($item) }}</span>
                                            <a href="{{ asset('storage/' . $item) }}"
                                                class="text-primary hover:text-primary/80">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Actions Section -->
                <div class="p-6 bg-neutral-50 border-t border-neutral-200 flex justify-between items-center">
                    <div>
                        <p class="text-sm text-neutral-500">
                            Terms & Conditions:
                            <span
                                class="font-medium {{ $application->terms_accepted ? 'text-green-600' : 'text-red-600' }}">
                                {{ $application->terms_accepted ? 'Accepted' : 'Not Accepted' }}
                            </span>
                        </p>
                    </div>
                    <div class="flex space-x-4">
                        <a href="{{ route('jobs.apply', ['job' => $application->job->slug]) }}"
                            class="inline-flex items-center px-4 py-2 bg-neutral-100 text-neutral-800 border border-neutral-300 rounded-lg hover:bg-neutral-200 transition-colors">
                            View Job Details
                        </a>
                        @if ($application->status === 'submitted')
                            <a href="#"
                                class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Contact Client
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        @include('partials\footer-secondary')
    </section>
</x-app-layout>
