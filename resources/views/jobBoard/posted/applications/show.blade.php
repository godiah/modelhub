<x-app-layout>
    <style>
        /* Hide x-cloak elements until Alpine.js loads */
        [x-cloak] {
            display: none !important;
        }
    </style>
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

    <section class="bg-neutral-50 min-h-screen py-10">
        <div class="container max-w-7xl mx-auto px-4">
            <!-- Application Details Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column - Applicant Information -->
                <div class="font-main lg:col-span-2 space-y-6">
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
                                @elseif($application->status === 'withdrawn')
                                    <span
                                        class="px-3 py-1 inline-flex items-center text-sm font-medium rounded-full bg-neutral-100 text-rose-800 border border-neutral-200">
                                        <span class="h-2 w-2 rounded-full bg-rose-500 mr-2"></span>
                                        Withdrawn
                                    </span>
                                @else
                                    <span
                                        class="px-3 py-1 inline-flex items-center text-sm font-medium rounded-full bg-neutral-100 text-rose-800 border border-neutral-200">
                                        <span class="h-2 w-2 rounded-full bg-rose-500 mr-2"></span>
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
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="h-8 w-8 text-primary">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                        </svg>

                                    </div>
                                </div>

                                <div class="flex-1">
                                    <h1 class="text-2xl font-bold text-neutral-800 font-main">
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
                            <h2 class="flex items-center text-lg font-semibold text-neutral-800 font-main">
                                <svg xmlns="http://www.w3.org/2000/svg" stroke="currentColor" fill="#14b8a6"
                                    class="h-5 w-5 mr-2 text-secondary" viewBox="0 0 512 512">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M512 80c0 18-14.3 34.6-38.4 48c-29.1 16.1-72.5 27.5-122.3 30.9c-3.7-1.8-7.4-3.5-11.3-5C300.6 137.4 248.2 128 192 128c-8.3 0-16.4 .2-24.5 .6l-1.1-.6C142.3 114.6 128 98 128 80c0-44.2 86-80 192-80S512 35.8 512 80zM160.7 161.1c10.2-.7 20.7-1.1 31.3-1.1c62.2 0 117.4 12.3 152.5 31.4C369.3 204.9 384 221.7 384 240c0 4-.7 7.9-2.1 11.7c-4.6 13.2-17 25.3-35 35.5c0 0 0 0 0 0c-.1 .1-.3 .1-.4 .2c0 0 0 0 0 0s0 0 0 0c-.3 .2-.6 .3-.9 .5c-35 19.4-90.8 32-153.6 32c-59.6 0-112.9-11.3-148.2-29.1c-1.9-.9-3.7-1.9-5.5-2.9C14.3 274.6 0 258 0 240c0-34.8 53.4-64.5 128-75.4c10.5-1.5 21.4-2.7 32.7-3.5zM416 240c0-21.9-10.6-39.9-24.1-53.4c28.3-4.4 54.2-11.4 76.2-20.5c16.3-6.8 31.5-15.2 43.9-25.5l0 35.4c0 19.3-16.5 37.1-43.8 50.9c-14.6 7.4-32.4 13.7-52.4 18.5c.1-1.8 .2-3.5 .2-5.3zm-32 96c0 18-14.3 34.6-38.4 48c-1.8 1-3.6 1.9-5.5 2.9C304.9 404.7 251.6 416 192 416c-62.8 0-118.6-12.6-153.6-32C14.3 370.6 0 354 0 336l0-35.4c12.5 10.3 27.6 18.7 43.9 25.5C83.4 342.6 135.8 352 192 352s108.6-9.4 148.1-25.9c7.8-3.2 15.3-6.9 22.4-10.9c6.1-3.4 11.8-7.2 17.2-11.2c1.5-1.1 2.9-2.3 4.3-3.4l0 3.4 0 5.7 0 26.3zm32 0l0-32 0-25.9c19-4.2 36.5-9.5 52.1-16c16.3-6.8 31.5-15.2 43.9-25.5l0 35.4c0 10.5-5 21-14.9 30.9c-16.3 16.3-45 29.7-81.3 38.4c.1-1.7 .2-3.5 .2-5.3zM192 448c56.2 0 108.6-9.4 148.1-25.9c16.3-6.8 31.5-15.2 43.9-25.5l0 35.4c0 44.2-86 80-192 80S0 476.2 0 432l0-35.4c12.5 10.3 27.6 18.7 43.9 25.5C83.4 438.6 135.8 448 192 448z" />
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
                                            Ksh{{ number_format($application->service_fee, 2) }}
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
                                            Ksh{{ number_format($application->net_amount, 2) }}
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
                            <h2 class="flex items-center text-lg font-semibold text-neutral-800 font-main">
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
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor"
                                        class="h-12 w-12 text-neutral-300 mb-2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                    </svg>
                                    <p class="text-neutral-500 font-main">No proposal provided by applicant</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Portfolio Section -->
                    <div class="bg-white rounded-xl shadow-sm border border-neutral-200 overflow-hidden">
                        <div class="px-6 py-5">
                            <h2 class="flex items-center text-lg font-semibold text-neutral-800 font-main">
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
                                    <p class="text-neutral-500 font-main">No portfolio items or attachments
                                        provided</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Reviews Section -->
                    <div class="bg-white rounded-xl shadow-sm border border-neutral-200 overflow-hidden">
                        <div class="px-6 py-5 border-b border-neutral-200">
                            <h2 class="flex items-center text-lg font-semibold text-neutral-800 font-main">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-secondary"
                                    viewBox="0 0 20 20" fill="currentColor">
                                    <path
                                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                                Applicant Reviews
                            </h2>
                        </div>

                        <!-- Summary Stats -->
                        <div class="bg-neutral-50 px-6 py-4">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <!-- Average Rating -->
                                <div
                                    class="flex flex-col items-center justify-center p-4 bg-white rounded-lg shadow-sm">
                                    <span class="text-neutral-500 text-sm mb-1">Average Rating</span>
                                    <div class="flex items-center">
                                        <span
                                            class="text-3xl font-bold text-neutral-800 mr-2">{{ number_format($averageRating, 1) }}</span>
                                        <x-jobs.star-rating :rating="$averageRating" size="md" :showNumber="false" />
                                    </div>
                                </div>

                                <!-- Total Reviews -->
                                <div
                                    class="flex flex-col items-center justify-center p-4 bg-white rounded-lg shadow-sm">
                                    <span class="text-neutral-500 text-sm mb-1">Total Reviews</span>
                                    <div class="flex items-center">
                                        <span class="text-3xl font-bold text-neutral-800">{{ $totalReviews }}</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor"
                                            class="h-7 w-7 ml-2 text-accent">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                                        </svg>

                                    </div>
                                </div>

                                <!-- Top Skill -->
                                <div
                                    class="flex flex-col items-center justify-center p-4 bg-white rounded-lg shadow-sm">
                                    <span class="text-neutral-500 text-sm mb-1">Most Recognized For</span>
                                    <div class="flex items-center justify-center">
                                        @if ($topSkill)
                                            <span
                                                class="px-3 py-1.5 bg-secondary/10 text-secondary font-medium rounded-full text-sm">{{ $topSkill }}</span>
                                        @else
                                            <span class="text-neutral-500 italic text-sm">No tags yet</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Reviews List -->
                        <div class="px-6 py-5 bg-neutral-50">
                            @if ($reviews->count() > 0)
                                <div class="space-y-4">
                                    @foreach ($reviews as $review)
                                        <x-jobs.review-card :review="$review" />
                                    @endforeach
                                </div>

                                <!-- Pagination -->
                                <div class="mt-6">
                                    {{ $reviews->links() }}
                                </div>
                            @else
                                <div class="text-center py-12">
                                    <div
                                        class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-neutral-100 mb-4">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-accent"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                        </svg>
                                    </div>
                                    <h3 class="text-lg font-medium text-neutral-700">No Reviews Yet</h3>
                                    <p class="text-neutral-500 mt-1">This applicant hasn't received any reviews yet.
                                    </p>
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
                                        <h3 class="font-semibold text-sm text-neutral-800 font-secondary line-clamp-2">
                                            {{ $application->job->title }}
                                        </h3>
                                        <div class="flex items-center mt-1.5">
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary/10 text-primary">
                                                <svg xmlns="http://www.w3.org/2000/svg" stroke="currentColor"
                                                    fill="#1e3a8a" class="h-3.5 w-3.5 mr-1" viewBox="0 0 512 512">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M512 80c0 18-14.3 34.6-38.4 48c-29.1 16.1-72.5 27.5-122.3 30.9c-3.7-1.8-7.4-3.5-11.3-5C300.6 137.4 248.2 128 192 128c-8.3 0-16.4 .2-24.5 .6l-1.1-.6C142.3 114.6 128 98 128 80c0-44.2 86-80 192-80S512 35.8 512 80zM160.7 161.1c10.2-.7 20.7-1.1 31.3-1.1c62.2 0 117.4 12.3 152.5 31.4C369.3 204.9 384 221.7 384 240c0 4-.7 7.9-2.1 11.7c-4.6 13.2-17 25.3-35 35.5c0 0 0 0 0 0c-.1 .1-.3 .1-.4 .2c0 0 0 0 0 0s0 0 0 0c-.3 .2-.6 .3-.9 .5c-35 19.4-90.8 32-153.6 32c-59.6 0-112.9-11.3-148.2-29.1c-1.9-.9-3.7-1.9-5.5-2.9C14.3 274.6 0 258 0 240c0-34.8 53.4-64.5 128-75.4c10.5-1.5 21.4-2.7 32.7-3.5zM416 240c0-21.9-10.6-39.9-24.1-53.4c28.3-4.4 54.2-11.4 76.2-20.5c16.3-6.8 31.5-15.2 43.9-25.5l0 35.4c0 19.3-16.5 37.1-43.8 50.9c-14.6 7.4-32.4 13.7-52.4 18.5c.1-1.8 .2-3.5 .2-5.3zm-32 96c0 18-14.3 34.6-38.4 48c-1.8 1-3.6 1.9-5.5 2.9C304.9 404.7 251.6 416 192 416c-62.8 0-118.6-12.6-153.6-32C14.3 370.6 0 354 0 336l0-35.4c12.5 10.3 27.6 18.7 43.9 25.5C83.4 342.6 135.8 352 192 352s108.6-9.4 148.1-25.9c7.8-3.2 15.3-6.9 22.4-10.9c6.1-3.4 11.8-7.2 17.2-11.2c1.5-1.1 2.9-2.3 4.3-3.4l0 3.4 0 5.7 0 26.3zm32 0l0-32 0-25.9c19-4.2 36.5-9.5 52.1-16c16.3-6.8 31.5-15.2 43.9-25.5l0 35.4c0 10.5-5 21-14.9 30.9c-16.3 16.3-45 29.7-81.3 38.4c.1-1.7 .2-3.5 .2-5.3zM192 448c56.2 0 108.6-9.4 148.1-25.9c16.3-6.8 31.5-15.2 43.9-25.5l0 35.4c0 44.2-86 80-192 80S0 476.2 0 432l0-35.4c12.5 10.3 27.6 18.7 43.9 25.5C83.4 438.6 135.8 448 192 448z" />
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
                                            {{ $application->job->applicants_count }}</div>
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

                            <div x-data="{
                                selectedStatus: '{{ $application->status }}',
                                showHireModal: false,
                                showDeliverablesForm: false,
                                deliverables: [],
                            
                                // Submit deliverables function
                                submitDeliverables() {
                                    // Get the hire form
                                    const hireForm = document.getElementById('hireForm');
                                    const inputContainer = document.getElementById('deliverables-input-container');
                            
                                    // Clear previous inputs
                                    inputContainer.innerHTML = '';
                            
                                    // Add each deliverable as hidden inputs
                                    this.deliverables.forEach((deliverable, index) => {
                                        const titleInput = document.createElement('input');
                                        titleInput.type = 'hidden';
                                        titleInput.name = `deliverables[${index}][title]`;
                                        titleInput.value = deliverable.title;
                            
                                        const descInput = document.createElement('input');
                                        descInput.type = 'hidden';
                                        descInput.name = `deliverables[${index}][description]`;
                                        descInput.value = deliverable.description || '';
                            
                                        const dateInput = document.createElement('input');
                                        dateInput.type = 'hidden';
                                        dateInput.name = `deliverables[${index}][due_date]`;
                                        dateInput.value = deliverable.due_date || '';
                            
                                        inputContainer.appendChild(titleInput);
                                        inputContainer.appendChild(descInput);
                                        inputContainer.appendChild(dateInput);
                                    });
                            
                                    // Add a debug message to console
                                    console.log('Submitting form with deliverables:', this.deliverables);
                            
                                    // Submit the form
                                    hireForm.submit();
                                    this.showDeliverablesForm = false;
                                }
                            }" x-cloak>
                                <!-- Main Status Update Form -->
                                <form id="statusUpdateForm"
                                    action="{{ route('my-jobs.applications.update-status', ['application' => $application->id]) }}"
                                    method="POST" @if ($application->job->hasAcceptedEngagement() && !$application->job->is_active) disabled @endif>
                                    @csrf
                                    @method('PATCH')

                                    <div class="space-y-5">
                                        <!-- Position Filled Notice -->
                                        @if ($application->job->hasAcceptedEngagement() && !$application->job->is_active)
                                            <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-4">
                                                <div class="flex">
                                                    <div class="flex-shrink-0">
                                                        <svg class="h-5 w-5 text-amber-400"
                                                            xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                                            fill="currentColor">
                                                            <path fill-rule="evenodd"
                                                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2h-1V9a1 1 0 00-1-1z"
                                                                clip-rule="evenodd" />
                                                        </svg>
                                                    </div>
                                                    <div class="ml-3">
                                                        <h3 class="text-sm font-medium text-amber-800">Position Filled
                                                        </h3>
                                                        <div class="mt-2 text-sm text-amber-700">
                                                            <p>This position has been filled. Status updates are no
                                                                longer available.</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif

                                        <!-- Current Status Display -->
                                        <div class="flex items-center mb-4">
                                            <div class="mr-2 text-sm font-medium text-neutral-600">Current Status:
                                            </div>
                                            @php
                                                $statusColors = [
                                                    'submitted' => 'bg-yellow-100 text-yellow-800',
                                                    'reviewed' => 'bg-blue-100 text-blue-800',
                                                    'hired' => 'bg-green-100 text-green-800',
                                                    'rejected' => 'bg-red-100 text-red-800',
                                                    'withdrawn' => 'bg-rose-100 text-rose-800',
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
                                                class="block text-sm font-medium text-neutral-700 mb-2 font-main">
                                                Update Status
                                            </label>
                                            <div class="relative">
                                                <select name="status" id="status" x-model="selectedStatus"
                                                    @change="if(selectedStatus === 'hired') { showHireModal = true; } else { showHireModal = false; }"
                                                    class="text-sm w-full py-3 px-4 border border-neutral-300 rounded-lg shadow-sm focus:ring-2 focus:ring-primary focus:border-primary font-main appearance-none"
                                                    @if ($application->job->hasAcceptedEngagement() && !$application->job->is_active) disabled @endif>
                                                    <option value="submitted"
                                                        :selected="selectedStatus === 'submitted'">Submitted</option>
                                                    <option value="reviewed"
                                                        :selected="selectedStatus === 'reviewed'">Reviewed</option>
                                                    <option value="hired" :selected="selectedStatus === 'hired'">
                                                        Hired</option>
                                                    <option value="rejected"
                                                        :selected="selectedStatus === 'rejected'">Rejected</option>
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
                                                class="block text-sm font-medium text-neutral-700 mb-2 font-main">
                                                Internal Notes
                                            </label>
                                            <textarea id="notes" name="notes" rows="3"
                                                class="text-sm w-full py-3 px-4 border border-neutral-300 rounded-lg shadow-sm focus:ring-2 focus:ring-primary focus:border-primary font-main resize-none"
                                                placeholder="Add your private notes about this applicant..." @if ($application->job->hasAcceptedEngagement() && !$application->job->is_active) disabled @endif>{{ $application->additional_notes ?? '' }}</textarea>
                                        </div>

                                        <!-- Submit Button -->
                                        <button type="submit" x-show="selectedStatus !== 'hired'"
                                            class="w-full py-3 px-4 bg-primary text-white rounded-lg font-medium hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 transition-colors flex items-center justify-center 
                      @if ($application->job->hasAcceptedEngagement() && !$application->job->is_active) opacity-50 cursor-not-allowed @endif"
                                            @if ($application->job->hasAcceptedEngagement() && !$application->job->is_active) disabled @endif>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                            Update Status
                                        </button>
                                    </div>
                                </form>

                                <!-- Hidden Hire Confirmation Form -->
                                <form id="hireForm"
                                    action="{{ route('my-jobs.applications.confirm-hire', ['application' => $application->id]) }}"
                                    method="POST" class="hidden">
                                    @csrf
                                    <!-- This will be populated by the deliverables form -->
                                    <div id="deliverables-input-container"></div>
                                </form>

                                <!-- Hire Confirmation Modal -->
                                <div x-show="showHireModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto"
                                    x-transition:enter="transition ease-out duration-300"
                                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                    x-transition:leave="transition ease-in duration-200"
                                    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

                                    <!-- Modal backdrop -->
                                    <div
                                        class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm transition-opacity">
                                    </div>

                                    <!-- Modal container -->
                                    <div class="flex min-h-screen items-center justify-center p-4">
                                        <div class="relative w-full max-w-2xl transform overflow-hidden rounded-xl bg-white shadow-xl transition-all"
                                            x-transition:enter="transition ease-out duration-300"
                                            x-transition:enter-start="opacity-0 translate-y-4"
                                            x-transition:enter-end="opacity-100 translate-y-0"
                                            x-transition:leave="transition ease-in duration-200"
                                            x-transition:leave-start="opacity-100 translate-y-0"
                                            x-transition:leave-end="opacity-0 translate-y-4">

                                            <!-- Close button -->
                                            <button
                                                @click="showHireModal = false; selectedStatus = '{{ $application->status }}'"
                                                class="absolute top-4 right-4 flex h-8 w-8 items-center justify-center rounded-full bg-neutral-100 text-neutral-600 hover:bg-neutral-200 focus:outline-none focus:ring-2 focus:ring-primary">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                                                    viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd"
                                                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            </button>

                                            <!-- Modal content -->
                                            <div class="p-6">
                                                <div class="flex items-center mb-4">
                                                    <div
                                                        class="mr-3 flex-shrink-0 rounded-full bg-primary bg-opacity-10 p-2">
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="h-6 w-6 text-primary" fill="none"
                                                            viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                        </svg>
                                                    </div>
                                                    <h3 class="text-xl font-bold text-neutral-800 font-tertiary">
                                                        Confirm Hiring</h3>
                                                </div>

                                                <p class="mb-8 text-neutral-600 font-main">
                                                    You're about to hire this applicant. This will notify them and
                                                    create an engagement between you and the freelancer.
                                                </p>

                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                    <button @click="showHireModal = false; showDeliverablesForm = true"
                                                        class="flex items-center justify-center py-3 px-4 bg-primary text-white rounded-lg font-medium hover:bg-primary-dark transition-colors focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 shadow-sm">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                                        </svg>
                                                        Setup Deliverables
                                                    </button>
                                                    <button
                                                        @click="showHireModal = false; document.getElementById('hireForm').submit();"
                                                        class="flex items-center justify-center py-3 px-4 bg-secondary text-white rounded-lg font-medium hover:bg-teal-700 transition-colors focus:outline-none focus:ring-2 focus:ring-secondary focus:ring-offset-2 shadow-sm">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                        Hire Without Deliverables
                                                    </button>
                                                </div>
                                                <div class="mt-4">
                                                    <button
                                                        @click="showHireModal = false; selectedStatus = '{{ $application->status }}'"
                                                        class="w-full py-2 px-4 bg-neutral-100 text-neutral-700 rounded-lg font-medium hover:bg-neutral-200 transition-colors focus:outline-none focus:ring-2 focus:ring-neutral-200 focus:ring-offset-2">
                                                        Cancel
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Deliverables Form Modal -->
                                <div x-show="showDeliverablesForm" x-cloak class="fixed inset-0 z-50 overflow-y-auto"
                                    x-transition:enter="transition ease-out duration-300"
                                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                    x-transition:leave="transition ease-in duration-200"
                                    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

                                    <!-- Modal backdrop -->
                                    <div
                                        class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm transition-opacity">
                                    </div>

                                    <!-- Modal container -->
                                    <div class="flex min-h-screen items-center justify-center p-4">
                                        <div class="relative w-full max-w-3xl transform overflow-hidden rounded-xl bg-white shadow-xl transition-all"
                                            x-transition:enter="transition ease-out duration-300"
                                            x-transition:enter-start="opacity-0 translate-y-4"
                                            x-transition:enter-end="opacity-100 translate-y-0"
                                            x-transition:leave="transition ease-in duration-200"
                                            x-transition:leave-start="opacity-100 translate-y-0"
                                            x-transition:leave-end="opacity-0 translate-y-4">

                                            <!-- Modal header -->
                                            <div class="border-b border-neutral-200 bg-neutral-50 px-6 py-4">
                                                <div class="flex items-center justify-between">
                                                    <div class="flex items-center">
                                                        <div
                                                            class="mr-3 flex-shrink-0 rounded-full bg-secondary bg-opacity-10 p-2">
                                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                                class="h-6 w-6 text-secondary" fill="none"
                                                                viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                                            </svg>
                                                        </div>
                                                        <h3 class="text-xl font-bold text-neutral-800 font-tertiary">
                                                            Set Project Deliverables</h3>
                                                    </div>

                                                    <!-- Close button -->
                                                    <button
                                                        @click="showDeliverablesForm = false; selectedStatus = '{{ $application->status }}'"
                                                        class="flex h-8 w-8 items-center justify-center rounded-full bg-neutral-100 text-neutral-600 hover:bg-neutral-200 focus:outline-none focus:ring-2 focus:ring-primary">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                                                            viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd"
                                                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                                                clip-rule="evenodd" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Modal content -->
                                            <div class="px-6 py-4 max-h-[calc(100vh-200px)] overflow-y-auto">
                                                <p class="mb-6 text-neutral-600 font-main">
                                                    Define clear deliverables for this project. These will help track
                                                    progress and set expectations with the freelancer.
                                                </p>

                                                <!-- Empty state when no deliverables are added -->
                                                <div x-show="deliverables.length === 0"
                                                    class="bg-neutral-50 rounded-lg border border-dashed border-neutral-300 p-8 mb-6 text-center">
                                                    <div class="mb-3 flex justify-center">
                                                        <div class="rounded-full bg-neutral-100 p-3">
                                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                                class="h-8 w-8 text-neutral-400" fill="none"
                                                                viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                                            </svg>
                                                        </div>
                                                    </div>
                                                    <h4 class="text-lg font-medium text-neutral-700 mb-2">No
                                                        deliverables added yet</h4>
                                                    <p class="text-neutral-500 mb-4">Add deliverables to create clear
                                                        milestones for this project</p>
                                                    <button
                                                        @click.prevent="deliverables.push({title: '', description: '', due_date: ''})"
                                                        class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                                        </svg>
                                                        Add First Deliverable
                                                    </button>
                                                </div>

                                                <!-- Deliverables list -->
                                                <div id="deliverables-container" class="space-y-4 mb-6"
                                                    x-show="deliverables.length > 0">
                                                    <template x-for="(deliverable, index) in deliverables"
                                                        :key="index">
                                                        <div
                                                            class="deliverable-item rounded-lg border border-neutral-200 bg-white shadow-sm overflow-hidden">
                                                            <!-- Deliverable header -->
                                                            <div
                                                                class="bg-neutral-50 px-4 py-3 border-b border-neutral-200">
                                                                <div class="flex items-center justify-between">
                                                                    <h4 class="font-medium text-neutral-800">
                                                                        Deliverable #<span x-text="index + 1"></span>
                                                                    </h4>
                                                                    <button
                                                                        @click.prevent="deliverables.splice(index, 1)"
                                                                        class="text-neutral-500 hover:text-red-600 flex items-center text-sm">
                                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                                            class="h-4 w-4 mr-1" fill="none"
                                                                            viewBox="0 0 24 24" stroke="currentColor">
                                                                            <path stroke-linecap="round"
                                                                                stroke-linejoin="round"
                                                                                stroke-width="2"
                                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                        </svg>
                                                                        Remove
                                                                    </button>
                                                                </div>
                                                            </div>

                                                            <!-- Deliverable content -->
                                                            <div class="p-4">
                                                                <div
                                                                    class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                                                    <div class="md:col-span-2">
                                                                        <label
                                                                            class="block text-sm font-medium text-neutral-700 mb-1">Title<span
                                                                                class="text-red-500">*</span></label>
                                                                        <input type="text"
                                                                            x-model="deliverable.title"
                                                                            class="w-full py-2 px-3 border border-neutral-300 rounded-md shadow-sm focus:ring-primary focus:border-primary"
                                                                            placeholder="What needs to be delivered?"
                                                                            required>
                                                                    </div>
                                                                    <div>
                                                                        <label
                                                                            class="block text-sm font-medium text-neutral-700 mb-1">Due
                                                                            Date</label>
                                                                        <input type="date"
                                                                            x-model="deliverable.due_date"
                                                                            class="w-full py-2 px-3 border border-neutral-300 rounded-md shadow-sm focus:ring-primary focus:border-primary">
                                                                    </div>
                                                                </div>
                                                                <div>
                                                                    <label
                                                                        class="block text-sm font-medium text-neutral-700 mb-1">Description</label>
                                                                    <textarea x-model="deliverable.description"
                                                                        class="w-full py-2 px-3 border border-neutral-300 rounded-md shadow-sm focus:ring-primary focus:border-primary"
                                                                        rows="2" placeholder="Add details, specifications, or acceptance criteria..."></textarea>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>

                                                <!-- Add button when deliverables exist -->
                                                <button x-show="deliverables.length > 0"
                                                    @click.prevent="deliverables.push({title: '', description: '', due_date: ''})"
                                                    class="mb-6 flex items-center text-primary hover:text-primary-dark font-medium">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1"
                                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                                    </svg>
                                                    Add Another Deliverable
                                                </button>
                                            </div>

                                            <!-- Modal footer -->
                                            <div class="border-t border-neutral-200 bg-neutral-50 px-6 py-4">
                                                <div class="flex justify-end space-x-4">
                                                    <button
                                                        @click="showDeliverablesForm = false; selectedStatus = '{{ $application->status }}'"
                                                        class="py-2 px-4 bg-white border border-neutral-300 text-neutral-700 rounded-lg font-medium hover:bg-neutral-50 transition-colors focus:outline-none focus:ring-2 focus:ring-neutral-200 focus:ring-offset-2">
                                                        Cancel
                                                    </button>
                                                    <button @click="submitDeliverables()"
                                                        :disabled="deliverables.length === 0 || deliverables.some(d => !d.title)"
                                                        :class="{
                                                            'opacity-50 cursor-not-allowed': deliverables.length ===
                                                                0 ||
                                                                deliverables.some(d => !d.title)
                                                        }"
                                                        class="py-2 px-4 bg-primary text-white rounded-lg font-medium hover:bg-primary-dark transition-colors focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 flex items-center">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                        Submit and Hire
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>


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
                                    <div class="flex mb-2">
                                        <button type="button" id="showTemplates"
                                            class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-full bg-secondary/10 text-secondary hover:bg-secondary/20 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-1"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                                <rect x="3" y="3" width="18" height="18" rx="2"
                                                    ry="2" />
                                                <line x1="3" y1="9" x2="21" y2="9" />
                                                <line x1="9" y1="21" x2="9" y2="9" />
                                                <path d="M13 13h4" />
                                                <path d="M13 17h4" />
                                            </svg>
                                            <span class="ml-1">Quick Templates</span>
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
                                                class="text-sm w-full py-3 pl-10 pr-4 border border-neutral-300 rounded-lg shadow-sm focus:ring-2 focus:ring-secondary focus:border-secondary font-main">
                                        </div>
                                    </div>

                                    <!-- Message Field -->
                                    <div>
                                        <label for="message"
                                            class="block text-sm font-medium text-neutral-700 mb-2 font-main">Message</label>
                                        <textarea id="message" name="message" rows="4" placeholder="Write your message to the applicant..."
                                            class="text-sm w-full py-3 px-4 border border-neutral-300 rounded-lg shadow-sm focus:ring-2 focus:ring-secondary focus:border-secondary font-main"></textarea>
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
                                        <div class="h-4 w-4 rounded-full bg-yellow-500 border-2 border-white"></div>
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
                                            <div class="h-4 w-4 rounded-full bg-blue-500 border-2 border-white">
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
