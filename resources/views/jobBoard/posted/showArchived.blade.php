<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-tertiary font-bold text-xl text-primary leading-tight">
                {{ __('Archived Job') }}: <span class="text-secondary">{{ $job->title }}</span>
            </h2>

            <div class="flex space-x-3">
                <a href="{{ route('my-jobs.archived.posted-jobs') }}"
                    class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors duration-200 font-main text-sm font-medium shadow-sm">
                    <x-icon name="archive-box-2" class="h-5 w-5 mr-2" />
                    Archived Jobs
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-10 bg-gradient-to-b from-neutral-50 to-white min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Job Information Card -->
            <x-card clip class="mb-8">
                <div class="px-6 py-6 space-y-4">
                    <!-- Job Header -->
                    <div class="flex flex-col md:flex-row md:items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-primary font-main mb-2">{{ $job->title }}</h1>
                            <div class="flex flex-wrap items-center gap-4 text-sm font-secondary">
                                <div class="flex items-center text-tertiary">
                                    <x-icon name="calendar-solid" class="h-4 w-4 mr-1" />
                                    Posted: <x-date :date="$job->created_at" format="M d, Y" />
                                </div>
                                <div class="flex items-center text-tertiary">
                                    <x-icon name="users-solid" class="h-4 w-4 mr-1" />
                                    Applications: {{ $job->applications_count }}
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 md:mt-0">
                            <span
                                class="px-4 py-2 inline-flex items-center text-sm font-medium font-secondary rounded-full bg-neutral-100 text-neutral-800 border border-neutral-200">
                                <x-icon name="archive-box-2" class="h-4 w-4 mr-2" />
                                Archived Job
                            </span>
                        </div>
                    </div>

                    <!-- Job Description Preview -->
                    @if ($job->description)
                        <div x-data="{ expanded: false, shouldShowMore: false }" x-init="$nextTick(() => {
                            const el = $refs.content;
                            shouldShowMore = el.scrollHeight > (window.innerWidth < 768 ? 80 : 40);
                        })"
                            class="mt-4 bg-neutral-50 rounded-lg p-4 border border-neutral-100">

                            <!-- Markdown content div with reference -->
                            <div x-ref="content"
                                class="prose prose-sm max-w-none text-neutral-700 text-sm font-main transition-all duration-300 overflow-hidden
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
                                class="mt-2 text-secondary text-sm font-medium font-main hover:text-primary transition"
                                x-text="expanded ? 'Show less' : 'Read more'"></button>
                        </div>
                    @endif
                </div>
            </x-card>

            <!-- Applications Section -->
            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-bold text-primary font-main">Job Applications</h2>
                    <div class="flex items-center text-tertiary text-sm font-main">
                        <x-icon name="users-solid" class="h-5 w-5 mr-2" />
                        {{ $job->applications_count }} Total Applicants
                    </div>
                </div>

                @forelse($job->applications as $application)
                    <!-- Individual Application Card -->
                    <x-card shadow="lg" clip class="transform transition-all duration-300 hover:shadow-xl">
                        <!-- Applicant Header -->
                        <div class="relative px-6 pt-6 pb-4 bg-gradient-to-r from-neutral-50 to-white">
                            <div class="flex items-center gap-4">
                                <div class="h-16 w-16 relative">
                                    <div
                                        class="absolute inset-0 bg-gradient-to-br from-primary to-secondary rounded-full opacity-10">
                                    </div>
                                    <div
                                        class="absolute inset-1 bg-white rounded-full flex items-center justify-center">
                                        <x-icon name="user-2" class="h-8 w-8 text-primary" />
                                    </div>
                                </div>

                                <div class="flex-1">
                                    <h3 class="text-xl font-bold text-neutral-800 font-main">
                                        {{ $application->applicant->name }}
                                    </h3>
                                    <div class="flex flex-wrap items-center gap-3 mt-1 font-secondary">
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
                                            <x-icon name="calendar-solid" class="h-4 w-4 mr-1" />
                                            <span class="text-sm">Applied
                                                <x-date :date="$application->created_at" format="M d, Y" /></span>
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
                                                <div class="text-xs text-tertiary mb-1 font-main">Offer Amount</div>
                                                <div class="text-lg font-bold text-primary font-secondary">
                                                    @if ($application->offer_amount)
                                                        <x-money :amount="$application->offer_amount" />
                                                    @else
                                                        <span class="text-tertiary text-base font-normal">Not
                                                            specified</span>
                                                    @endif
                                                </div>
                                            </div>
                                            @if ($application->net_amount)
                                                <div>
                                                    <div class="text-xs text-tertiary mb-1 font-main">Net Amount</div>
                                                    <div class="text-lg font-bold text-blue-700 font-secondary">
                                                        <x-money :amount="$application->net_amount" />
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Proposal -->
                                <div class="md:col-span-2">
                                    <x-card rounded="lg" shadow="none" class="p-4">
                                        <h4
                                            class="text-sm font-semibold text-tertiary mb-3 font-main flex items-center">
                                            <x-icon name="document-text-solid" class="h-4 w-4 mr-2 text-secondary" />
                                            Proposal
                                        </h4>
                                        @if ($application->proposal)
                                            <div
                                                class="text-neutral-700 text-justify text-sm font-main bg-neutral-50 p-4 rounded-md border border-neutral-100 max-h-32 overflow-y-auto">
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
                                    </x-card>
                                </div>
                            </div>

                            <!-- Portfolio Section -->
                            @if (is_array($application->portfolio) && count($application->portfolio) > 0)
                                <div class="mt-6">
                                    <h4 class="text-sm font-semibold text-tertiary mb-3 font-main flex items-center">
                                        <x-icon name="photo-solid" class="h-4 w-4 mr-2 text-secondary" />
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
                                                <x-card rounded="lg" clip class="group relative transition-all hover:shadow-md aspect-square">
                                                    <img src="{{ asset('storage/' . $item) }}" alt="Portfolio image"
                                                        class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
                                                    <div
                                                        class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/0 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end">
                                                        <div class="p-2 w-full flex justify-end">
                                                            <a href="{{ asset('storage/' . $item) }}" target="_blank"
                                                                class="bg-white/90 p-1.5 rounded-full text-primary hover:text-secondary transition-colors">
                                                                <x-icon name="arrow-top-right-on-square" class="h-4 w-4" />
                                                            </a>
                                                        </div>
                                                    </div>
                                                </x-card>
                                            @else
                                                <x-card rounded="lg" clip class="group transition-all hover:shadow-md aspect-square">
                                                    <div
                                                        class="h-full bg-gradient-to-br from-neutral-50 to-neutral-100 flex items-center justify-center">
                                                        <div class="text-center p-2">
                                                            <x-icon name="document" class="h-8 w-8 mx-auto text-tertiary/50 mb-2" />
                                                            <p class="text-xs text-tertiary truncate">
                                                                {{ basename($item) }}</p>
                                                        </div>
                                                    </div>
                                                    <a href="{{ asset('storage/' . $item) }}" target="_blank"
                                                        class="absolute inset-0 flex items-center justify-center bg-neutral-800/80 text-white opacity-0 group-hover:opacity-100 transition-opacity">
                                                        <span class="text-sm font-medium">Download</span>
                                                    </a>
                                                </x-card>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </x-card>
                @empty
                    <!-- No Applications Found -->
                    <x-card class="p-12 font-main">
                        <div class="text-center">
                            <x-icon name="document-text" class="h-16 w-16 mx-auto text-neutral-300 mb-4" />
                            <h3 class="text-lg font-medium text-neutral-900">No Applications Found</h3>
                            <p class="mt-2 text-sm text-tertiary">This archived job has not received any applications.
                            </p>
                        </div>
                    </x-card>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
