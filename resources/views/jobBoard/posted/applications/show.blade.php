<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-tertiary font-bold text-xl text-primary leading-tight">
                Application details
            </h2>
            <a href="{{ route('my-jobs.applications.index', ['slug' => $job->slug]) }}"
                class="flex items-center px-4 py-2 bg-neutral-100 rounded-md text-sm font-main text-primary hover:bg-neutral-200 transition shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Applications
            </a>
        </div>
    </x-slot>

    <section class="bg-neutral-50 min-h-screen py-12">
        <div class="container max-w-7xl mx-auto px-4">
            <!-- Application Details Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column - Applicant Information -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Applicant Profile Card -->
                    <div class="bg-white rounded-xl shadow-sm border border-neutral-200 overflow-hidden">
                        <div class="relative px-6 pt-6 pb-4">
                            <div class="absolute top-0 right-0 mt-4 mr-4">
                                @if ($application->status === 'submitted')
                                    <span
                                        class="px-3 py-1 inline-flex items-center text-sm font-medium rounded-full bg-amber-100 text-amber-800 border border-amber-200">
                                        <span class="h-2 w-2 rounded-full bg-amber-500 mr-2 animate-pulse"></span>
                                        Submitted
                                    </span>
                                @elseif($application->status === 'reviewed')
                                    <span
                                        class="px-3 py-1 inline-flex items-center text-sm font-medium rounded-full bg-blue-100 text-blue-800 border border-blue-200">
                                        <span class="h-2 w-2 rounded-full bg-blue-500 mr-2"></span>
                                        Reviewed
                                    </span>
                                @elseif($application->status === 'hired')
                                    <span
                                        class="px-3 py-1 inline-flex items-center text-sm font-medium rounded-full bg-green-100 text-green-800 border border-green-200">
                                        <span class="h-2 w-2 rounded-full bg-green-500 mr-2"></span>
                                        Hired
                                    </span>
                                @elseif($application->status === 'rejected')
                                    <span
                                        class="px-3 py-1 inline-flex items-center text-sm font-medium rounded-full bg-red-100 text-red-800 border border-red-200">
                                        <span class="h-2 w-2 rounded-full bg-red-500 mr-2"></span>
                                        Rejected
                                    </span>
                                @else
                                    <span
                                        class="px-3 py-1 inline-flex items-center text-sm font-medium rounded-full bg-neutral-100 text-neutral-800 border border-neutral-200">
                                        <span class="h-2 w-2 rounded-full bg-neutral-500 mr-2"></span>
                                        {{ ucfirst($application->status) }}
                                    </span>
                                @endif
                            </div>

                            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                                <div class="h-16 w-16 relative">
                                    <div
                                        class="absolute inset-0 bg-gradient-to-br from-primary to-secondary rounded-full opacity-10">
                                    </div>
                                    <div
                                        class="absolute inset-1 bg-white rounded-full flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-primary"
                                            viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </div>

                                <div class="flex-1">
                                    <h1 class="text-2xl font-bold text-neutral-800 font-secondary">
                                        {{ $application->applicant->name }}
                                    </h1>
                                    <div class="flex flex-wrap items-center gap-3 mt-1">
                                        <div class="flex items-center text-tertiary">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1"
                                                viewBox="0 0 20 20" fill="currentColor">
                                                <path
                                                    d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                                            </svg>
                                            <span class="text-sm">{{ $application->applicant->email }}</span>
                                        </div>

                                        @if ($application->phone)
                                            <div class="flex items-center text-tertiary">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1"
                                                    viewBox="0 0 20 20" fill="currentColor">
                                                    <path
                                                        d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                                                </svg>
                                                <span class="text-sm">{{ $application->phone }}</span>
                                            </div>
                                        @endif

                                        <div class="flex items-center text-tertiary">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1"
                                                viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd"
                                                    d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            <span class="text-sm">Applied
                                                {{ $application->created_at->format('M d, Y') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Card -->
                    <div class="bg-white rounded-xl shadow-sm border border-neutral-200 overflow-hidden">
                        <div class="px-6 py-5">
                            <h2 class="flex items-center text-lg font-semibold text-neutral-800 font-secondary">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-secondary"
                                    viewBox="0 0 20 20" fill="currentColor">
                                    <path
                                        d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z" />
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z"
                                        clip-rule="evenodd" />
                                </svg>
                                Financial Details
                            </h2>

                            <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div
                                    class="bg-gradient-to-br from-neutral-50 to-neutral-100 rounded-lg p-4 border border-neutral-200">
                                    <div class="text-sm text-tertiary mb-1 font-main">Offer Amount</div>
                                    <div class="text-xl font-bold text-primary font-secondary">
                                        @if ($application->offer_amount)
                                            Ksh{{ number_format($application->offer_amount, 2) }}
                                        @else
                                            <span class="text-tertiary text-base font-normal">Not specified</span>
                                        @endif
                                    </div>
                                </div>

                                <div
                                    class="bg-gradient-to-br from-neutral-50 to-neutral-100 rounded-lg p-4 border border-neutral-200">
                                    <div class="text-sm text-tertiary mb-1 font-main">Service Fee (10%)</div>
                                    <div class="text-xl font-bold text-red-500 font-secondary">
                                        @if ($application->service_fee)
                                            Ksh{{ number_format($application->service_fee) }}
                                        @else
                                            <span class="text-tertiary text-base font-normal">Not applicable</span>
                                        @endif
                                    </div>
                                </div>

                                <div
                                    class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4 border border-blue-200">
                                    <div class="text-sm text-blue-700 mb-1 font-main">Net Amount</div>
                                    <div class="text-xl font-bold text-blue-700 font-secondary">
                                        @if ($application->net_amount)
                                            Ksh{{ number_format($application->net_amount) }}
                                        @else
                                            <span class="text-blue-400 text-base font-normal">Not applicable</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Proposal Section -->
                    <div class="bg-white rounded-xl shadow-sm border border-neutral-200 overflow-hidden">
                        <div class="px-6 py-5">
                            <h2 class="flex items-center text-lg font-semibold text-neutral-800 font-secondary">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-secondary"
                                    viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z"
                                        clip-rule="evenodd" />
                                </svg>
                                Proposal
                            </h2>

                            @if ($application->proposal)
                                <div
                                    class="mt-4 bg-gradient-to-br from-neutral-50 to-neutral-100 rounded-lg p-5 border border-neutral-200">
                                    <div class="prose max-w-none font-main text-neutral-700 text-sm text-justify">
                                        {!! nl2br(e($application->proposal)) !!}
                                    </div>
                                </div>
                            @else
                                <div
                                    class="mt-4 flex flex-col items-center justify-center py-8 bg-neutral-50 rounded-lg border border-dashed border-neutral-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-neutral-300 mb-2"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <p class="text-neutral-500 font-secondary">No proposal provided</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Portfolio Section -->
                    <div class="bg-white rounded-xl shadow-sm border border-neutral-200 overflow-hidden">
                        <div class="px-6 py-5">
                            <h2 class="flex items-center text-lg font-semibold text-neutral-800 font-secondary">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-secondary"
                                    viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z"
                                        clip-rule="evenodd" />
                                </svg>
                                Portfolio & Attachments
                            </h2>

                            @if (is_array($application->portfolio) && count($application->portfolio) > 0)
                                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
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
                                            <div
                                                class="group relative bg-white rounded-lg shadow-sm border border-neutral-200 overflow-hidden transition-all hover:shadow-md">
                                                <div class="aspect-square overflow-hidden bg-neutral-100">
                                                    <img src="{{ asset('storage/' . $item) }}" alt="Portfolio image"
                                                        class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
                                                </div>
                                                <div
                                                    class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/0 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end">
                                                    <div class="p-3 w-full flex justify-between items-center">
                                                        <span
                                                            class="text-sm text-white truncate">{{ basename($item) }}</span>
                                                        <a href="{{ asset('storage/' . $item) }}" target="_blank"
                                                            class="bg-white/90 p-1.5 rounded-full text-primary hover:text-secondary transition-colors">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                                fill="none" viewBox="0 0 24 24"
                                                                stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                            </svg>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        @elseif($isDocument)
                                            <!-- Document Preview -->
                                            <div
                                                class="group bg-white rounded-lg shadow-sm border border-neutral-200 overflow-hidden transition-all hover:shadow-md">
                                                <div
                                                    class="aspect-square bg-gradient-to-br from-primary/5 to-neutral-100 p-5 flex items-center justify-center">
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="h-16 w-16 text-primary/30" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                </div>
                                                <div
                                                    class="p-3 flex justify-between items-center bg-white border-t border-neutral-200">
                                                    <span
                                                        class="text-sm text-neutral-600 truncate">{{ basename($item) }}</span>
                                                    <a href="{{ asset('storage/' . $item) }}" target="_blank"
                                                        class="text-primary hover:text-secondary transition-colors">
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
                                            <div
                                                class="group bg-white rounded-lg shadow-sm border border-neutral-200 overflow-hidden transition-all hover:shadow-md">
                                                <div
                                                    class="aspect-square bg-gradient-to-br from-neutral-50 to-neutral-100 p-5 flex items-center justify-center">
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="h-16 w-16 text-tertiary/30" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z" />
                                                    </svg>
                                                </div>
                                                <div
                                                    class="p-3 flex justify-between items-center bg-white border-t border-neutral-200">
                                                    <span
                                                        class="text-sm text-neutral-600 truncate">{{ basename($item) }}</span>
                                                    <a href="{{ asset('storage/' . $item) }}"
                                                        class="text-primary hover:text-secondary transition-colors">
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
                            @else
                                <div
                                    class="mt-4 flex flex-col items-center justify-center py-8 bg-neutral-50 rounded-lg border border-dashed border-neutral-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-neutral-300 mb-2"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <p class="text-neutral-500 font-secondary">No portfolio items or attachments
                                        provided</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Right Column - Application Management -->
                <div class="space-y-6">
                    <!-- Job Details Card -->
                    <div class="bg-white rounded-xl shadow-md border border-neutral-200 overflow-hidden">
                        <div class="relative">
                            <!-- Decorative gradient background with subtle pattern -->
                            <div class="absolute inset-0 bg-gradient-to-br from-primary/10 to-secondary/5">
                                <div class="absolute inset-0 opacity-5"
                                    style="background-image: url("data:image/svg+xml,%3Csvg width='60'
                                    height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg
                                    fill='none' fill-rule='evenodd'%3E%3Cg fill='%231E3A8A'
                                    fill-opacity='0.2'%3E%3Cpath
                                    d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'
                                    /%3E%3C/g%3E%3C/g%3E%3C/svg%3E")"></div>
                            </div>

                            <!-- Job Details Content -->
                            <div class="relative px-6 py-5">
                                <h2
                                    class="flex items-center text-lg font-semibold text-neutral-800 font-secondary mb-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-secondary"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                    Job Details
                                </h2>

                                <div
                                    class="flex items-center mb-3 bg-white/70 rounded-lg p-4 border border-neutral-200/70 shadow-sm">
                                    <div
                                        class="h-14 w-14 rounded-lg bg-primary/10 flex items-center justify-center overflow-hidden mr-4 border border-primary/20 shadow-sm">
                                        @if ($application->job->images)
                                            <img src="{{ asset('storage/' . $application->job->images) }}"
                                                alt="{{ $application->job->title }}"
                                                class="h-full w-full object-cover">
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-primary"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                        @endif
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-lg text-neutral-800 font-secondary">
                                            {{ $application->job->title }}
                                        </h3>
                                        <div class="flex items-center mt-1.5">
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary/10 text-primary">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-1"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                Ksh {{ number_format($application->job->budget) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Job Quick Stats -->
                                <div class="grid grid-cols-2 gap-3 mt-4">
                                    <div class="bg-neutral-50 rounded-lg p-3 border border-neutral-200/60">
                                        <div class="flex items-center text-tertiary text-sm mb-1 font-main">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            Posted Date
                                        </div>
                                        <div class="font-medium text-neutral-700">
                                            {{ $application->job->created_at->format('M d, Y') }}</div>
                                    </div>
                                    <div class="bg-neutral-50 rounded-lg p-3 border border-neutral-200/60">
                                        <div class="flex items-center text-tertiary text-sm mb-1 font-main">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                            Applicants
                                        </div>
                                        <div class="font-medium text-neutral-700">
                                            {{ $application->job->applications_count ?? '3' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Update Application Status -->
                    <div class="bg-white rounded-xl shadow-md border border-neutral-200 overflow-hidden">
                        <div class="px-6 py-5">
                            <div class="flex items-center mb-5">
                                <div class="h-9 w-9 rounded-full bg-primary/10 flex items-center justify-center mr-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <h2 class="text-lg font-semibold text-neutral-800 font-secondary">Application Status
                                </h2>
                            </div>

                            <form
                                action="{{ route('my-jobs.applications.update-status', ['application' => $application->id]) }}"
                                method="POST">
                                @csrf
                                @method('PATCH')

                                <div class="space-y-5">
                                    <!-- Current Status Display -->
                                    <div class="flex items-center mb-4">
                                        <div class="mr-2 text-sm font-medium text-neutral-600">Current Status:</div>
                                        @php
                                            $statusColors = [
                                                'submitted' => 'bg-blue-100 text-blue-800',
                                                'reviewed' => 'bg-purple-100 text-purple-800',
                                                'hired' => 'bg-green-100 text-green-800',
                                                'rejected' => 'bg-red-100 text-red-800',
                                            ];
                                            $statusColor =
                                                $statusColors[$application->status] ??
                                                'bg-neutral-100 text-neutral-800';
                                        @endphp
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColor }}">
                                            {{ ucfirst($application->status) }}
                                        </span>
                                    </div>

                                    <!-- Status Selection -->
                                    <div>
                                        <label for="status"
                                            class="block text-sm font-medium text-neutral-700 mb-2 font-main">Update
                                            Status</label>
                                        <div class="relative">
                                            <select name="status" id="status"
                                                class="w-full py-3 px-4 border border-neutral-300 rounded-lg shadow-sm focus:ring-2 focus:ring-primary focus:border-primary font-main appearance-none">
                                                <option value="submitted"
                                                    {{ $application->status === 'submitted' ? 'selected' : '' }}>
                                                    Submitted</option>
                                                <option value="reviewed"
                                                    {{ $application->status === 'reviewed' ? 'selected' : '' }}>
                                                    Reviewed</option>
                                                <option value="hired"
                                                    {{ $application->status === 'hired' ? 'selected' : '' }}>Hired
                                                </option>
                                                <option value="rejected"
                                                    {{ $application->status === 'rejected' ? 'selected' : '' }}>
                                                    Rejected</option>
                                            </select>
                                            <div
                                                class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
                                                <svg class="h-5 w-5 text-neutral-400"
                                                    xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                                    fill="currentColor">
                                                    <path fill-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Notes Field -->
                                    <div>
                                        <label for="notes"
                                            class="block text-sm font-medium text-neutral-700 mb-2 font-main">Internal
                                            Notes</label>
                                        <textarea id="notes" name="notes" rows="3"
                                            class="w-full py-3 px-4 border border-neutral-300 rounded-lg shadow-sm focus:ring-2 focus:ring-primary focus:border-primary font-main resize-none"
                                            placeholder="Add your private notes about this applicant...">{{ $application->additional_notes ?? '' }}</textarea>
                                    </div>

                                    <!-- Submit Button -->
                                    <button type="submit"
                                        class="w-full py-3 px-4 bg-primary text-white rounded-lg font-medium hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 transition-colors flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                        Update Status
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Contact Applicant -->
                    <div class="bg-white rounded-xl shadow-md border border-neutral-200 overflow-hidden">
                        <div class="px-6 py-5">
                            <div class="flex items-center mb-5">
                                <div
                                    class="h-9 w-9 rounded-full bg-secondary/10 flex items-center justify-center mr-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-secondary"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                    </svg>
                                </div>
                                <h2 class="text-lg font-semibold text-neutral-800 font-secondary">Contact Applicant
                                </h2>
                            </div>

                            <form
                                action="{{ route('my-jobs.applications.send-message', ['application' => $application->id]) }}"
                                method="POST">
                                @csrf

                                <div class="space-y-5">
                                    <!-- Quick Templates Button -->
                                    <div class="flex mb-3">
                                        <button type="button" id="showTemplates"
                                            class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-full bg-secondary/10 text-secondary hover:bg-secondary/20 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-1"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2" />
                                            </svg>
                                            Quick Templates
                                        </button>
                                    </div>

                                    <!-- Subject Field -->
                                    <div>
                                        <label for="subject"
                                            class="block text-sm font-medium text-neutral-700 mb-2 font-main">Subject</label>
                                        <div class="relative">
                                            <div
                                                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="h-5 w-5 text-neutral-400" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                            <input type="text" id="subject" name="subject"
                                                placeholder="Re: Your application for {{ $application->job->title }}"
                                                class="w-full py-3 pl-10 pr-4 border border-neutral-300 rounded-lg shadow-sm focus:ring-2 focus:ring-secondary focus:border-secondary font-main">
                                        </div>
                                    </div>

                                    <!-- Message Field -->
                                    <div>
                                        <label for="message"
                                            class="block text-sm font-medium text-neutral-700 mb-2 font-main">Message</label>
                                        <textarea id="message" name="message" rows="4" placeholder="Write your message to the applicant..."
                                            class="w-full py-3 px-4 border border-neutral-300 rounded-lg shadow-sm focus:ring-2 focus:ring-secondary focus:border-secondary font-main"></textarea>
                                    </div>

                                    <!-- Submit Button -->
                                    <button type="submit"
                                        class="w-full py-3 px-4 bg-secondary text-white rounded-lg font-medium hover:bg-secondary-dark focus:outline-none focus:ring-2 focus:ring-secondary focus:ring-offset-2 transition-colors flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                        </svg>
                                        Send Message
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Applicant Timeline -->
                    <div class="bg-white rounded-xl shadow-md border border-neutral-200 overflow-hidden">
                        <div class="px-6 py-5">
                            <div class="flex items-center mb-5">
                                <div class="h-9 w-9 rounded-full bg-accent/10 flex items-center justify-center mr-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-accent"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <h2 class="text-lg font-semibold text-neutral-800 font-secondary">Application Timeline
                                </h2>
                            </div>

                            <div class="ml-4 border-l-2 border-neutral-200 pl-5 space-y-6 py-2">
                                <!-- Timeline Items -->
                                <div class="relative">
                                    <div class="absolute -left-7 mt-0.5">
                                        <div class="h-4 w-4 rounded-full bg-primary border-2 border-white"></div>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-neutral-800">Application Submitted</p>
                                        <p class="text-xs text-neutral-500 mt-0.5">
                                            {{ $application->created_at->format('M d, Y - h:i A') }}</p>
                                    </div>
                                </div>

                                @if ($application->status !== 'submitted')
                                    <div class="relative">
                                        <div class="absolute -left-7 mt-0.5">
                                            <div class="h-4 w-4 rounded-full bg-purple-500 border-2 border-white">
                                            </div>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-neutral-800">Application Reviewed</p>
                                            <p class="text-xs text-neutral-500 mt-0.5">
                                                {{ $application->updated_at->format('M d, Y - h:i A') }}</p>
                                        </div>
                                    </div>
                                @endif

                                @if ($application->status === 'hired')
                                    <div class="relative">
                                        <div class="absolute -left-7 mt-0.5">
                                            <div class="h-4 w-4 rounded-full bg-green-500 border-2 border-white"></div>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-neutral-800">Applicant Hired</p>
                                            <p class="text-xs text-neutral-500 mt-0.5">
                                                {{ $application->updated_at->format('M d, Y - h:i A') }}</p>
                                        </div>
                                    </div>
                                @endif

                                @if ($application->status === 'rejected')
                                    <div class="relative">
                                        <div class="absolute -left-7 mt-0.5">
                                            <div class="h-4 w-4 rounded-full bg-red-500 border-2 border-white"></div>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-neutral-800">Application Rejected</p>
                                            <p class="text-xs text-neutral-500 mt-0.5">
                                                {{ $application->updated_at->format('M d, Y - h:i A') }}</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
