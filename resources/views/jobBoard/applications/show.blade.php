@use('App\Enums\ApplicationStatus')
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-tertiary font-bold text-xl text-primary leading-tight">
                Application details
            </h2>
            <a href="{{ route('applications.my') }}"
                class="flex items-center px-4 py-2 bg-neutral-100 rounded-md text-sm font-main text-primary hover:bg-neutral-200 transition shadow-sm">
                <x-icon name="arrow-left" class="h-5 w-5 mr-2" />
                Back to Applications
            </a>
        </div>
    </x-slot>

    <section class="bg-neutral-50">
        <div class="container mx-auto max-w-7xl px-4 py-8">
            <!-- Application Status Banner -->
            <div
                class="mb-6 rounded-lg p-4 {{ $application->status === ApplicationStatus::Submitted
                    ? 'bg-yellow-100 border-l-4 border-yellow-500'
                    : ($application->status === ApplicationStatus::Reviewed
                        ? 'bg-blue-100 border-l-4 border-blue-500'
                        : ($application->status === ApplicationStatus::Rejected
                            ? 'bg-red-100 border-l-4 border-red-500'
                            : ($application->status === ApplicationStatus::Withdrawn
                                ? 'bg-rose-100 border-l-4 border-rose-500'
                                : 'bg-green-100 border-l-4 border-green-500'))) }}">
                <div class="flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="h-6 w-6 mr-3 {{ $application->status === ApplicationStatus::Submitted
                            ? 'text-yellow-500'
                            : ($application->status === ApplicationStatus::Reviewed
                                ? 'text-blue-500'
                                : ($application->status === ApplicationStatus::Rejected
                                    ? 'text-red-500'
                                    : ($application->status === ApplicationStatus::Withdrawn
                                        ? 'text-rose-500'
                                        : 'text-green-500'))) }}"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <p class="font-medium text-neutral-800">
                            Application Status: <span
                                class="font-bold {{ $application->status === ApplicationStatus::Submitted
                                    ? 'text-yellow-700'
                                    : ($application->status === ApplicationStatus::Reviewed
                                        ? 'text-blue-700'
                                        : ($application->status === ApplicationStatus::Rejected
                                            ? 'text-red-700'
                                            : ($application->status === ApplicationStatus::Withdrawn
                                                ? 'text-rose-700'
                                                : 'text-green-700'))) }}">
                                {{ $application->status->label() }}</span>
                        </p>
                        <p class="text-sm text-neutral-600">
                            Applied on <x-date :date="$application->created_at" format="F d, Y" />
                        </p>
                    </div>
                </div>
            </div>

            <!-- Job and Application Details -->
            <x-card shadow="md" clip>
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
                                    <x-icon name="photo" class="h-16 w-16" />
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
                                <x-icon name="calendar" class="h-4 w-4 mr-1" />
                                Posted on <x-date :date="$application->job->created_at" format="M d, Y" />
                            </span>
                            <span class="mx-3">•</span>
                            <span class="flex items-center">
                                <x-icon name="user" class="h-4 w-4 mr-1" />
                                By {{ $application->poster->name }}
                            </span>
                        </div>

                        <div class="mt-4 flex">
                            <div class="bg-neutral-100 rounded-lg px-5 py-3 text-center">
                                <p class="text-sm text-neutral-500">Job Budget</p>
                                <p class="text-base font-bold text-primary mt-1">
                                    <x-money :amount="$application->job->budget" :decimals="0" /></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-t border-neutral-200"></div>

                <!-- Financial Details Section -->
                <div class="p-6 bg-neutral-50 border-b border-neutral-200">
                    <h2 class="text-lg font-semibold text-neutral-800 mb-4 font-secondary">Financial Details</h2>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <x-card rounded="lg" shadow="none" class="p-4">
                            <p class="text-sm text-neutral-500 mb-1">Your Offer</p>
                            <p class="text-xl font-bold text-primary">
                                <x-money :amount="$application->offer_amount" /></p>
                        </x-card>

                        <x-card rounded="lg" shadow="none" class="p-4">
                            <p class="text-sm text-neutral-500 mb-1">Service Fee</p>
                            <p class="text-xl font-bold text-tertiary">
                                <x-money :amount="$application->service_fee" /></p>
                        </x-card>

                        <x-card rounded="lg" shadow="none" class="p-4">
                            <p class="text-sm text-neutral-500 mb-1">You'll Receive</p>
                            <p class="text-xl font-bold text-secondary">
                                <x-money :amount="$application->net_amount" />
                            </p>
                        </x-card>
                    </div>
                </div>

                <!-- Proposal Section -->
                <div class="p-6 border-b border-neutral-200">
                    <h2 class="text-lg font-semibold text-neutral-800 mb-4 font-secondary">Your Proposal</h2>

                    @if ($application->proposal)
                        <div class="bg-neutral-50 rounded-lg p-4 border border-neutral-100 text-justify">
                            <div class="prose prose-sm max-w-none text-neutral-700">
                                {!! nl2br(e($application->proposal)) !!}
                            </div>
                        </div>
                    @else
                        <div class="bg-neutral-50 rounded-lg p-4 border border-neutral-100 flex items-center gap-3">
                            <x-icon name="exclamation-circle" class="h-6 w-6 text-neutral-400" />
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
                                    <x-card rounded="lg" shadow="none" clip class="group">
                                        <div class="aspect-square w-full overflow-hidden bg-neutral-100">
                                            <img src="{{ asset('storage/' . $item) }}" alt="Portfolio image"
                                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                        </div>
                                        <div class="p-3 flex justify-between items-center">
                                            <span
                                                class="text-sm text-neutral-500 truncate">{{ basename($item) }}</span>
                                            <a href="{{ asset('storage/' . $item) }}" target="_blank"
                                                class="text-primary hover:text-primary/80">
                                                <x-icon name="arrow-top-right-on-square" class="h-5 w-5" />
                                            </a>
                                        </div>
                                    </x-card>
                                @elseif($isDocument)
                                    <!-- Document Preview -->
                                    <x-card rounded="lg" shadow="none" clip>
                                        <div
                                            class="aspect-square w-full flex items-center justify-center bg-neutral-50 p-4">
                                            <x-icon name="document-text" class="h-16 w-16 text-neutral-300" />
                                        </div>
                                        <div class="p-3 flex justify-between items-center">
                                            <span
                                                class="text-sm text-neutral-500 truncate">{{ basename($item) }}</span>
                                            <a href="{{ asset('storage/' . $item) }}" target="_blank"
                                                class="text-primary hover:text-primary/80">
                                                <x-icon name="cloud-arrow-down" class="h-5 w-5" />
                                            </a>
                                        </div>
                                    </x-card>
                                @else
                                    <!-- Other File Types -->
                                    <x-card rounded="lg" shadow="none" clip>
                                        <div
                                            class="aspect-square w-full flex items-center justify-center bg-neutral-50 p-4">
                                            <x-icon name="folder-open" class="h-16 w-16 text-neutral-300" />
                                        </div>
                                        <div class="p-3 flex justify-between items-center">
                                            <span
                                                class="text-sm text-neutral-500 truncate">{{ basename($item) }}</span>
                                            <a href="{{ asset('storage/' . $item) }}"
                                                class="text-primary hover:text-primary/80">
                                                <x-icon name="cloud-arrow-down" class="h-5 w-5" />
                                            </a>
                                        </div>
                                    </x-card>
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
                    {{-- <div class="flex space-x-4">
                        <a href="{{ route('jobs.apply', ['job' => $application->job->slug]) }}"
                            class="inline-flex items-center px-4 py-2 bg-neutral-100 text-neutral-800 border border-neutral-300 rounded-lg hover:bg-neutral-200 transition-colors">
                            View Job Details
                        </a>
                        @if ($application->status === ApplicationStatus::Submitted)
                            <a href="#"
                                class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                                <x-icon name="document-text" class="h-5 w-5 mr-2" />
                                Contact Client
                            </a>
                        @endif
                    </div> --}}
                </div>
            </x-card>
        </div>

        <!-- Footer -->
        @include('partials.footer-secondary')
    </section>
</x-app-layout>
