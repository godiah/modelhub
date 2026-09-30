@use('App\Enums\ApplicationStatus')
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
                <x-icon name="arrow-left" class="h-4 w-4 mr-2" />
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
                    <x-card clip>
                        <div class="relative px-6 pt-6 pb-4">
                            <div class="absolute top-0 right-0 mt-4 mr-4">
                                @if ($application->status === ApplicationStatus::Submitted)
                                    <span
                                        class="px-3 py-1 inline-flex items-center text-sm font-medium rounded-full bg-amber-100 text-amber-800 border border-amber-200">
                                        <span class="h-2 w-2 rounded-full bg-amber-500 mr-2 animate-pulse"></span>
                                        Submitted
                                    </span>
                                @elseif($application->status === ApplicationStatus::Reviewed)
                                    <span
                                        class="px-3 py-1 inline-flex items-center text-sm font-medium rounded-full bg-blue-100 text-blue-800 border border-blue-200">
                                        <span class="h-2 w-2 rounded-full bg-blue-500 mr-2"></span>
                                        Reviewed
                                    </span>
                                @elseif($application->status === ApplicationStatus::Hired)
                                    <span
                                        class="px-3 py-1 inline-flex items-center text-sm font-medium rounded-full bg-green-100 text-green-800 border border-green-200">
                                        <span class="h-2 w-2 rounded-full bg-green-500 mr-2"></span>
                                        Hired
                                    </span>
                                @elseif($application->status === ApplicationStatus::Rejected)
                                    <span
                                        class="px-3 py-1 inline-flex items-center text-sm font-medium rounded-full bg-red-100 text-red-800 border border-red-200">
                                        <span class="h-2 w-2 rounded-full bg-red-500 mr-2"></span>
                                        Rejected
                                    </span>
                                @elseif($application->status === ApplicationStatus::Withdrawn)
                                    <span
                                        class="px-3 py-1 inline-flex items-center text-sm font-medium rounded-full bg-neutral-100 text-rose-800 border border-neutral-200">
                                        <span class="h-2 w-2 rounded-full bg-rose-500 mr-2"></span>
                                        Withdrawn
                                    </span>
                                @else
                                    <span
                                        class="px-3 py-1 inline-flex items-center text-sm font-medium rounded-full bg-neutral-100 text-rose-800 border border-neutral-200">
                                        <span class="h-2 w-2 rounded-full bg-rose-500 mr-2"></span>
                                        {{ $application->status->label() }}
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
                                        <x-icon name="user-2" class="h-8 w-8 text-primary" />

                                    </div>
                                </div>

                                <div class="flex-1">
                                    <h1 class="text-2xl font-bold text-neutral-800 font-main">
                                        {{ $application->applicant->name }}
                                    </h1>
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
                    </x-card>

                    <!-- Financial Card -->
                    <x-card clip>
                        <div class="px-6 py-5">
                            <h2 class="flex items-center text-lg font-semibold text-neutral-800 font-main">
                                <x-icon name="coins" class="h-5 w-5 mr-2 text-secondary" fill="#14b8a6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                Financial Details
                            </h2>

                            <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div
                                    class="bg-gradient-to-br from-neutral-50 to-neutral-100 rounded-lg p-4 border border-neutral-200">
                                    <div class="text-sm text-tertiary mb-1 font-main">Offer Amount</div>
                                    <div class="text-xl font-bold text-primary font-secondary">
                                        @if ($application->offer_amount)
                                            <x-money :amount="$application->offer_amount" />
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
                                            <x-money :amount="$application->service_fee" />
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
                                            <x-money :amount="$application->net_amount" />
                                        @else
                                            <span class="text-blue-400 text-base font-normal">Not applicable</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </x-card>

                    <!-- Proposal Section -->
                    <x-card clip>
                        <div class="px-6 py-5">
                            <h2 class="flex items-center text-lg font-semibold text-neutral-800 font-main">
                                <x-icon name="document-text-solid" class="h-5 w-5 mr-2 text-secondary" />
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
                                    <x-icon name="exclamation-triangle-3" class="h-12 w-12 text-neutral-300 mb-2" />
                                    <p class="text-neutral-500 font-main">No proposal provided by applicant</p>
                                </div>
                            @endif
                        </div>
                    </x-card>

                    <!-- Portfolio Section -->
                    <x-card clip>
                        <div class="px-6 py-5">
                            <h2 class="flex items-center text-lg font-semibold text-neutral-800 font-main">
                                <x-icon name="photo-solid" class="h-5 w-5 mr-2 text-secondary" />
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
                                            <x-card rounded="lg" clip class="group relative transition-all hover:shadow-md">
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
                                                            <x-icon name="arrow-top-right-on-square" class="h-4 w-4" />
                                                        </a>
                                                    </div>
                                                </div>
                                            </x-card>
                                        @elseif($isDocument)
                                            <!-- Document Preview -->
                                            <x-card rounded="lg" clip class="group transition-all hover:shadow-md">
                                                <div
                                                    class="aspect-square bg-gradient-to-br from-primary/5 to-neutral-100 p-5 flex items-center justify-center">
                                                    <x-icon name="document-text" class="h-16 w-16 text-primary/30" />
                                                </div>
                                                <div
                                                    class="p-3 flex justify-between items-center bg-white border-t border-neutral-200">
                                                    <span
                                                        class="text-sm text-neutral-600 truncate">{{ basename($item) }}</span>
                                                    <a href="{{ asset('storage/' . $item) }}" target="_blank"
                                                        class="text-primary hover:text-secondary transition-colors">
                                                        <x-icon name="cloud-arrow-down" class="h-5 w-5" />
                                                    </a>
                                                </div>
                                            </x-card>
                                        @else
                                            <!-- Other File Types -->
                                            <x-card rounded="lg" clip class="group transition-all hover:shadow-md">
                                                <div
                                                    class="aspect-square bg-gradient-to-br from-neutral-50 to-neutral-100 p-5 flex items-center justify-center">
                                                    <x-icon name="folder-open" class="h-16 w-16 text-tertiary/30" />
                                                </div>
                                                <div
                                                    class="p-3 flex justify-between items-center bg-white border-t border-neutral-200">
                                                    <span
                                                        class="text-sm text-neutral-600 truncate">{{ basename($item) }}</span>
                                                    <a href="{{ asset('storage/' . $item) }}"
                                                        class="text-primary hover:text-secondary transition-colors">
                                                        <x-icon name="cloud-arrow-down" class="h-5 w-5" />
                                                    </a>
                                                </div>
                                            </x-card>
                                        @endif
                                    @endforeach
                                </div>
                            @else
                                <div
                                    class="mt-4 flex flex-col items-center justify-center py-8 bg-neutral-50 rounded-lg border border-dashed border-neutral-300">
                                    <x-icon name="photo" class="h-12 w-12 text-neutral-300 mb-2" />
                                    <p class="text-neutral-500 font-main">No portfolio items or attachments
                                        provided</p>
                                </div>
                            @endif
                        </div>
                    </x-card>

                    <!-- Reviews Section -->
                    <x-card clip>
                        <div class="px-6 py-5 border-b border-neutral-200">
                            <h2 class="flex items-center text-lg font-semibold text-neutral-800 font-main">
                                <x-icon name="star-solid" class="h-5 w-5 mr-2 text-secondary" />
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
                                        <x-icon name="chat-bubble-dots" class="h-8 w-8 text-accent" stroke-width="1.5" />
                                    </div>
                                    <h3 class="text-lg font-medium text-neutral-700">No Reviews Yet</h3>
                                    <p class="text-neutral-500 mt-1">This applicant hasn't received any reviews yet.
                                    </p>
                                </div>
                            @endif
                        </div>
                    </x-card>
                </div>

                <!-- Right Column - Application Management -->
                <div class="space-y-6">
                    <!-- Job Details Card -->
                    <x-card shadow="md" clip>
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
                                    <x-icon name="briefcase-2" class="h-5 w-5 mr-2 text-secondary" />
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
                                            <x-icon name="briefcase-2" class="h-7 w-7 text-primary" />
                                        @endif
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-sm text-neutral-800 font-secondary line-clamp-2">
                                            {{ $application->job->title }}
                                        </h3>
                                        <div class="flex items-center mt-1.5">
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary/10 text-primary">
                                                <x-icon name="coins" class="h-3.5 w-3.5 mr-1" fill="#1e3a8a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                <x-money :amount="$application->job->budget" />
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Job Quick Stats -->
                                <div class="grid grid-cols-2 gap-3 mt-4">
                                    <div class="bg-neutral-50 rounded-lg p-3 border border-neutral-200/60">
                                        <div class="flex items-center text-tertiary text-sm mb-1 font-main">
                                            <x-icon name="calendar" class="h-4 w-4 mr-1.5" />
                                            Posted Date
                                        </div>
                                        <div class="font-medium text-neutral-700">
                                            <x-date :date="$application->job->created_at" format="M d, Y" /></div>
                                    </div>
                                    <div class="bg-neutral-50 rounded-lg p-3 border border-neutral-200/60">
                                        <div class="flex items-center text-tertiary text-sm mb-1 font-main">
                                            <x-icon name="users" class="h-4 w-4 mr-1.5" />
                                            Applicants
                                        </div>
                                        <div class="font-medium text-neutral-700">
                                            {{ $application->job->applicants_count }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </x-card>

                    <!-- Update Application Status -->
                    <x-card shadow="md" clip>
                        <div class="px-6 py-5">
                            <div class="flex items-center mb-5">
                                <div class="h-9 w-9 rounded-full bg-primary/10 flex items-center justify-center mr-3">
                                    <x-icon name="check-circle" class="h-5 w-5 text-primary" />
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
                                <!-- Withdrawn Application Notice -->
                                @if ($application->status === ApplicationStatus::Withdrawn)
                                    <div class="bg-red-100 border border-red-200 rounded-lg p-4 mb-4 font-main">
                                        <div class="flex">
                                            <div class="flex-shrink-0">
                                                <x-icon name="x-circle-solid" class="h-5 w-5 text-red-800" />
                                            </div>
                                            <div class="ml-3">
                                                <h3 class="text-sm font-medium text-gray-800">Application
                                                    Withdrawn</h3>
                                                <div class="mt-2 text-sm text-gray-700">
                                                    <p>This applicant has withdrawn their application. Status
                                                        updates are no longer available.</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <!-- Main Status Update Form -->
                                    <form id="statusUpdateForm"
                                        action="{{ route('my-jobs.applications.update-status', ['application' => $application->id]) }}"
                                        method="POST" @if ($application->job->hasAcceptedEngagement() && !$application->job->is_active) disabled @endif>
                                        @csrf
                                        @method('PATCH')

                                        <div class="space-y-5">
                                            <!-- Position Filled Notice -->
                                            @if ($application->job->hasAcceptedEngagement() && !$application->job->is_active)
                                                <div
                                                    class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-4 font-main">
                                                    <div class="flex">
                                                        <div class="flex-shrink-0">
                                                            <x-icon name="information-circle-solid" class="h-5 w-5 text-amber-400" />
                                                        </div>
                                                        <div class="ml-3">
                                                            <h3 class="text-sm font-medium text-amber-800">Position
                                                                Filled
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
                                            <div class="flex items-center mb-4 font-secondary">
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
                                                        $statusColors[$application->status->value] ??
                                                        'bg-neutral-100 text-neutral-800';
                                                @endphp
                                                <span
                                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColor }}">
                                                    {{ $application->status->label() }}
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
                                                            :selected="selectedStatus === 'submitted'">Submitted
                                                        </option>
                                                        <option value="reviewed"
                                                            :selected="selectedStatus === 'reviewed'">Reviewed</option>
                                                        <option value="hired"
                                                            :selected="selectedStatus === 'hired'">
                                                            Hired</option>
                                                        <option value="rejected"
                                                            :selected="selectedStatus === 'rejected'">Rejected</option>
                                                    </select>
                                                    <div
                                                        class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
                                                        <x-icon name="chevron-down-solid" class="h-5 w-5 text-neutral-400" />
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
                                                <x-icon name="check" class="h-5 w-5 mr-2" />
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
                                                    <x-icon name="x-mark-solid" class="h-5 w-5" />
                                                </button>

                                                <!-- Modal content -->
                                                <div class="p-6">
                                                    <div class="flex items-center mb-4">
                                                        <div
                                                            class="mr-3 flex-shrink-0 rounded-full bg-primary bg-opacity-10 p-2">
                                                            <x-icon name="user" class="h-6 w-6 text-primary" />
                                                        </div>
                                                        <h3 class="text-xl font-bold text-neutral-800 font-tertiary">
                                                            Confirm Hiring</h3>
                                                    </div>

                                                    <p class="mb-8 text-neutral-600 font-main">
                                                        You're about to hire this applicant. This will notify them and
                                                        create an engagement between you and the freelancer.
                                                    </p>

                                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                        <button
                                                            @click="showHireModal = false; showDeliverablesForm = true"
                                                            class="flex items-center justify-center py-3 px-4 bg-primary text-white rounded-lg font-medium hover:bg-primary-dark transition-colors focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 shadow-sm">
                                                            <x-icon name="clipboard-list" class="h-5 w-5 mr-2" />
                                                            Setup Deliverables
                                                        </button>
                                                        <button
                                                            @click="showHireModal = false; document.getElementById('hireForm').submit();"
                                                            class="flex items-center justify-center py-3 px-4 bg-secondary text-white rounded-lg font-medium hover:bg-teal-700 transition-colors focus:outline-none focus:ring-2 focus:ring-secondary focus:ring-offset-2 shadow-sm">
                                                            <x-icon name="check" class="h-5 w-5 mr-2" />
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
                                    <div x-show="showDeliverablesForm" x-cloak
                                        class="fixed inset-0 z-50 overflow-y-auto"
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
                                                                <x-icon name="clipboard-list" class="h-6 w-6 text-secondary" />
                                                            </div>
                                                            <h3
                                                                class="text-xl font-bold text-neutral-800 font-tertiary">
                                                                Set Project Deliverables</h3>
                                                        </div>

                                                        <!-- Close button -->
                                                        <button
                                                            @click="showDeliverablesForm = false; selectedStatus = '{{ $application->status }}'"
                                                            class="flex h-8 w-8 items-center justify-center rounded-full bg-neutral-100 text-neutral-600 hover:bg-neutral-200 focus:outline-none focus:ring-2 focus:ring-primary">
                                                            <x-icon name="x-mark-solid" class="h-5 w-5" />
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Modal content -->
                                                <div class="px-6 py-4 max-h-[calc(100vh-200px)] overflow-y-auto">
                                                    <p class="mb-6 text-neutral-600 font-main">
                                                        Define clear deliverables for this project. These will help
                                                        track
                                                        progress and set expectations with the freelancer.
                                                    </p>

                                                    <!-- Empty state when no deliverables are added -->
                                                    <div x-show="deliverables.length === 0"
                                                        class="bg-neutral-50 rounded-lg border border-dashed border-neutral-300 p-8 mb-6 text-center">
                                                        <div class="mb-3 flex justify-center">
                                                            <div class="rounded-full bg-neutral-100 p-3">
                                                                <x-icon name="clipboard-list" class="h-8 w-8 text-neutral-400" />
                                                            </div>
                                                        </div>
                                                        <h4 class="text-lg font-medium text-neutral-700 mb-2">No
                                                            deliverables added yet</h4>
                                                        <p class="text-neutral-500 mb-4">Add deliverables to create
                                                            clear
                                                            milestones for this project</p>
                                                        <button
                                                            @click.prevent="deliverables.push({title: '', description: '', due_date: ''})"
                                                            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                                                            <x-icon name="plus-2" class="h-5 w-5 mr-2" />
                                                            Add First Deliverable
                                                        </button>
                                                    </div>

                                                    <!-- Deliverables list -->
                                                    <div id="deliverables-container" class="space-y-4 mb-6"
                                                        x-show="deliverables.length > 0">
                                                        <template x-for="(deliverable, index) in deliverables"
                                                            :key="index">
                                                            <x-card rounded="lg" clip class="deliverable-item">
                                                                <!-- Deliverable header -->
                                                                <div
                                                                    class="bg-neutral-50 px-4 py-3 border-b border-neutral-200">
                                                                    <div class="flex items-center justify-between">
                                                                        <h4 class="font-medium text-neutral-800">
                                                                            Deliverable #<span
                                                                                x-text="index + 1"></span>
                                                                        </h4>
                                                                        <button
                                                                            @click.prevent="deliverables.splice(index, 1)"
                                                                            class="text-neutral-500 hover:text-red-600 flex items-center text-sm">
                                                                            <x-icon name="trash" class="h-4 w-4 mr-1" />
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
                                                            </x-card>
                                                        </template>
                                                    </div>

                                                    <!-- Add button when deliverables exist -->
                                                    <button x-show="deliverables.length > 0"
                                                        @click.prevent="deliverables.push({title: '', description: '', due_date: ''})"
                                                        class="mb-6 flex items-center text-primary hover:text-primary-dark font-medium">
                                                        <x-icon name="plus-2" class="h-5 w-5 mr-1" />
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
                                                            :disabled="deliverables.length === 0 || deliverables.some(d => !d
                                                                .title)"
                                                            :class="{
                                                                'opacity-50 cursor-not-allowed': deliverables.length ===
                                                                    0 ||
                                                                    deliverables.some(d => !d.title)
                                                            }"
                                                            class="py-2 px-4 bg-primary text-white rounded-lg font-medium hover:bg-primary-dark transition-colors focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 flex items-center">
                                                            <x-icon name="check" class="h-5 w-5 mr-2" />
                                                            Submit and Hire
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </x-card>

                    <!-- Contact Applicant -->
                    <x-card shadow="md" clip>
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
                                                <x-icon name="envelope" class="h-5 w-5 text-neutral-400" />
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
                                        <x-icon name="paper-airplane" class="h-5 w-5 mr-2" />
                                        Send Message
                                    </button>
                                </div>
                            </form>
                        </div>
                    </x-card>

                    <!-- Applicant Timeline -->
                    <x-card shadow="md" clip>
                        <div class="px-6 py-5">
                            <div class="flex items-center mb-5">
                                <div class="h-9 w-9 rounded-full bg-accent/10 flex items-center justify-center mr-3">
                                    <x-icon name="clock" class="h-5 w-5 text-accent" />
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
                                        <p class="text-sm font-medium text-neutral-800 font-secondary">Application
                                            Submitted</p>
                                        <p class="text-xs text-neutral-500 mt-0.5 font-main">
                                            <x-date :date="$application->created_at" format="M d, Y - h:i A" /></p>
                                    </div>
                                </div>

                                @if ($application->status !== ApplicationStatus::Submitted)
                                    <div class="relative">
                                        <div class="absolute -left-7 mt-0.5">
                                            <div class="h-4 w-4 rounded-full bg-blue-500 border-2 border-white">
                                            </div>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-neutral-800 font-secondary">Application
                                                Reviewed</p>
                                            <p class="text-xs text-neutral-500 mt-0.5 font-main">
                                                <x-date :date="$application->updated_at" format="M d, Y - h:i A" /></p>
                                        </div>
                                    </div>
                                @endif

                                @if ($application->status === ApplicationStatus::Hired)
                                    <div class="relative">
                                        <div class="absolute -left-7 mt-0.5">
                                            <div class="h-4 w-4 rounded-full bg-green-500 border-2 border-white"></div>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-neutral-800 font-secondary">Applicant
                                                Hired</p>
                                            <p class="text-xs text-neutral-500 mt-0.5 font-main">
                                                <x-date :date="$application->updated_at" format="M d, Y - h:i A" /></p>
                                        </div>
                                    </div>
                                @endif

                                @if ($application->status === ApplicationStatus::Rejected)
                                    <div class="relative">
                                        <div class="absolute -left-7 mt-0.5">
                                            <div class="h-4 w-4 rounded-full bg-red-500 border-2 border-white"></div>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-neutral-800 font-secondary">Application
                                                Rejected</p>
                                            <p class="text-xs text-neutral-500 mt-0.5 font-main">
                                                <x-date :date="$application->updated_at" format="M d, Y - h:i A" /></p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </x-card>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
