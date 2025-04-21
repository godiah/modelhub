<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-tertiary font-bold text-xl text-primary leading-tight">
                {{ __('Archived Job') }}: <span class="text-secondary">{{ $job->title }}</span>
            </h2>

            <div class="flex space-x-3">
                <a href="{{ route('my-jobs.archived.posted-jobs') }}"
                    class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors duration-200 font-main text-sm font-medium shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="h-5 w-5 mr-2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                    </svg>
                    Archived Jobs
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-10 bg-gradient-to-b from-neutral-50 to-white min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Job Information Card -->
            <div class="bg-white rounded-xl shadow-sm border border-neutral-200 overflow-hidden mb-8">
                <div class="px-6 py-6 space-y-4">
                    <!-- Job Header -->
                    <div class="flex flex-col md:flex-row md:items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-primary font-main mb-2">{{ $job->title }}</h1>
                            <div class="flex flex-wrap items-center gap-4 text-sm">
                                <div class="flex items-center text-tertiary">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20"
                                        fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    Posted: {{ $job->created_at->format('M d, Y') }}
                                </div>
                                <div class="flex items-center text-tertiary">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20"
                                        fill="currentColor">
                                        <path
                                            d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                                    </svg>
                                    Applications: {{ $job->applications_count }}
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 md:mt-0">
                            <span
                                class="px-4 py-2 inline-flex items-center text-sm font-medium rounded-full bg-neutral-100 text-neutral-800 border border-neutral-200">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="h-4 w-4 mr-2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                                </svg>
                                Archived Job
                            </span>
                        </div>
                    </div>

                    <!-- Job Description Preview -->
                    @if ($job->description)
                        <div x-data="{ expanded: false, shouldShowMore: false }" x-init="$nextTick(() => {
                            const el = $refs.content;
                            shouldShowMore = el.scrollHeight > (window.innerWidth < 768 ? 80 : 40); // Approximate 5rem/2.5rem in pixels
                        })"
                            class="mt-4 bg-neutral-50 rounded-lg p-4 border border-neutral-100">

                            <!-- Markdown content div with reference -->
                            <div x-ref="content"
                                class="prose prose-sm max-w-none text-neutral-700 text-sm transition-all duration-300 overflow-hidden
                                        [&>ul]:list-disc [&>ul]:pl-5 [&>ul]:mb-1 
                                        [&>ol]:list-decimal [&>ol]:pl-5 [&>ol]:mb-1
                                        [&>blockquote]:border-l-4 [&>blockquote]:border-neutral-200 [&>blockquote]:pl-4 [&>blockquote]:italic [&>blockquote]:my-2
                                        [&>h1]:text-lg [&>h1]:font-bold [&>h1]:mb-2 [&>h1]:mt-3
                                        [&>h2]:text-base [&>h2]:font-bold [&>h2]:mb-1.5 [&>h2]:mt-2.5
                                        [&>h3]:text-sm [&>h3]:font-bold [&>h3]:mb-1 [&>h3]:mt-2
                                        [&>h4,&>h5,&>h6]:text-sm [&>h4,&>h5,&>h6]:font-semibold [&>h4,&>h5,&>h6]:mb-1 [&>h4,&>h5,&>h6]:mt-2
                                        [&>p]:mb-1"
                                :class="expanded ? 'max-h-none' : 'max-h-[5rem] md:max-h-[2.5rem]'">
                                {!! Str::markdown($job->description) !!}
                            </div>

                            <!-- Toggle button that only shows when needed -->
                            <button x-show="shouldShowMore" x-on:click="expanded = !expanded"
                                class="mt-2 text-secondary text-sm font-medium hover:text-primary transition"
                                x-text="expanded ? 'Show less' : 'Read more'"></button>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Applications Section -->
            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-bold text-primary font-main">Job Applications</h2>
                    <div class="flex items-center text-tertiary text-sm font-main">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path
                                d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                        </svg>
                        {{ $job->applications_count }} Total Applicants
                    </div>
                </div>

                @forelse($job->applications as $application)
                    <!-- Individual Application Card -->
                    <div
                        class="bg-white rounded-xl shadow-lg border border-neutral-200 overflow-hidden transform transition-all duration-300 hover:shadow-xl">
                        <!-- Applicant Header -->
                        <div class="relative px-6 pt-6 pb-4 bg-gradient-to-r from-neutral-50 to-white">
                            <div class="flex items-center gap-4">
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
                                    <h3 class="text-xl font-bold text-neutral-800 font-main">
                                        {{ $application->applicant->name }}
                                    </h3>
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

                        <!-- Application Details -->
                        <div class="p-6 bg-white">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- Financial Information -->
                                <div class="md:col-span-1">
                                    <div
                                        class="bg-gradient-to-br from-neutral-50 to-neutral-100 rounded-lg p-4 border border-neutral-200">
                                        <h4 class="text-sm font-semibold text-tertiary mb-3 font-main">Financial
                                            Details</h4>
                                        <div class="space-y-3">
                                            <div>
                                                <div class="text-sm text-tertiary mb-1">Offer Amount</div>
                                                <div class="text-lg font-bold text-primary font-secondary">
                                                    @if ($application->offer_amount)
                                                        Ksh{{ number_format($application->offer_amount, 2) }}
                                                    @else
                                                        <span class="text-tertiary text-base font-normal">Not
                                                            specified</span>
                                                    @endif
                                                </div>
                                            </div>
                                            @if ($application->net_amount)
                                                <div>
                                                    <div class="text-sm text-tertiary mb-1">Net Amount</div>
                                                    <div class="text-lg font-bold text-blue-700 font-secondary">
                                                        Ksh{{ number_format($application->net_amount, 2) }}
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Proposal -->
                                <div class="md:col-span-2">
                                    <div class="bg-white border border-neutral-200 rounded-lg p-4">
                                        <h4
                                            class="text-sm font-semibold text-tertiary mb-3 font-main flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                class="h-4 w-4 mr-2 text-secondary" viewBox="0 0 20 20"
                                                fill="currentColor">
                                                <path fill-rule="evenodd"
                                                    d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            Proposal
                                        </h4>
                                        @if ($application->proposal)
                                            <div
                                                class="text-neutral-700 text-justify text-sm bg-neutral-50 p-4 rounded-md border border-neutral-100 max-h-32 overflow-y-auto">
                                                {!! nl2br(e($application->proposal)) !!}
                                            </div>
                                        @else
                                            <div
                                                class="flex items-center justify-center py-6 text-neutral-400 bg-neutral-50 rounded-md border border-neutral-100">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                    class="h-6 w-6 mr-2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                                </svg>
                                                <span class="text-sm">No proposal provided by the
                                                    applicant</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Portfolio Section -->
                            @if (is_array($application->portfolio) && count($application->portfolio) > 0)
                                <div class="mt-6">
                                    <h4 class="text-sm font-semibold text-tertiary mb-3 font-main flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-secondary"
                                            viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        Portfolio & Attachments
                                    </h4>
                                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                                        @foreach ($application->portfolio as $item)
                                            @php
                                                $extension = pathinfo($item, PATHINFO_EXTENSION);
                                                $isImage = in_array(strtolower($extension), [
                                                    'jpg',
                                                    'jpeg',
                                                    'png',
                                                    'gif',
                                                    'webp',
                                                ]);
                                            @endphp

                                            @if ($isImage)
                                                <div
                                                    class="group relative bg-white rounded-lg shadow-sm border border-neutral-200 overflow-hidden transition-all hover:shadow-md aspect-square">
                                                    <img src="{{ asset('storage/' . $item) }}" alt="Portfolio image"
                                                        class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
                                                    <div
                                                        class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/0 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end">
                                                        <div class="p-2 w-full flex justify-end">
                                                            <a href="{{ asset('storage/' . $item) }}" target="_blank"
                                                                class="bg-white/90 p-1.5 rounded-full text-primary hover:text-secondary transition-colors">
                                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                                    class="h-4 w-4" fill="none"
                                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round" stroke-width="2"
                                                                        d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                                </svg>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            @else
                                                <div
                                                    class="group bg-white rounded-lg shadow-sm border border-neutral-200 overflow-hidden transition-all hover:shadow-md aspect-square">
                                                    <div
                                                        class="h-full bg-gradient-to-br from-neutral-50 to-neutral-100 flex items-center justify-center">
                                                        <div class="text-center p-2">
                                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                                class="h-8 w-8 mx-auto text-tertiary/50 mb-2"
                                                                fill="none" viewBox="0 0 24 24"
                                                                stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                            </svg>
                                                            <p class="text-xs text-tertiary truncate">
                                                                {{ basename($item) }}</p>
                                                        </div>
                                                    </div>
                                                    <a href="{{ asset('storage/' . $item) }}" target="_blank"
                                                        class="absolute inset-0 flex items-center justify-center bg-neutral-800/80 text-white opacity-0 group-hover:opacity-100 transition-opacity">
                                                        <span class="text-sm font-medium">Download</span>
                                                    </a>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <!-- No Applications Found -->
                    <div class="bg-white rounded-xl shadow-sm border border-neutral-200 p-12">
                        <div class="text-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-neutral-300 mb-4"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h3 class="text-lg font-medium text-neutral-900">No Applications Found</h3>
                            <p class="mt-2 text-sm text-tertiary">This archived job has not received any applications.
                            </p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
