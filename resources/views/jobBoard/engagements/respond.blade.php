<!-- resources/views/jobBoard/engagements/respond.blade.php -->
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-tertiary font-bold text-xl text-primary leading-tight">
                Respond to Job Offer
            </h2>
            <a href="{{ route('engagements.index') }}"
                class="flex items-center px-4 py-2 bg-neutral-100 rounded-md text-sm font-main text-primary hover:bg-neutral-200 transition shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Engagements
            </a>
        </div>
    </x-slot>

    <div class="container mx-auto max-w-6xl px-4 pt-10 pb-24 font-main">
        <div class="bg-white border border-neutral-200 rounded-xl shadow-lg overflow-hidden">
            <!-- Header with gradient background -->
            <div class="bg-gradient-to-r from-primary to-primary/80 p-6 text-white">
                <div class="flex flex-col md:flex-row md:justify-between md:items-center">
                    <div>
                        <h3 class="font-tertiary font-bold text-2xl">{{ $application->job->title }}</h3>
                        <div class="flex items-center mt-2 text-white/80">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <span>{{ $application->poster->name }}</span>
                            <span class="mx-2">•</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span>{{ $application->job->created_at->format('M j, Y') }}</span>
                        </div>
                    </div>
                    <div
                        class="mt-4 md:mt-0 flex items-center bg-white/20 px-4 py-2 rounded-full text-sm font-semibold backdrop-blur-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Offer Received
                    </div>
                </div>
            </div>

            <!-- Job Summary Card -->
            <div class="p-6 bg-neutral-50 border-b border-neutral-200">
                <h4 class="font-tertiary font-semibold text-lg text-primary mb-4 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    Offer Details
                </h4>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div
                        class="bg-white rounded-lg p-4 border border-neutral-200 shadow-sm transition-transform hover:scale-[1.01]">
                        <div class="text-neutral-500 text-sm mb-1">Application Submitted</div>
                        <div class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary mr-2" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <p class="font-medium text-neutral-800">{{ $application->created_at->format('F j, Y') }}</p>
                        </div>
                    </div>

                    <div
                        class="bg-white rounded-lg p-4 border border-neutral-200 shadow-sm transition-transform hover:scale-[1.01]">
                        <div class="text-neutral-500 text-sm mb-1">Offer Amount</div>
                        <div class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" stroke="currentColor" fill="#f59e0b"
                                class="h-5 w-5 text-accent mr-2" viewBox="0 0 512 512">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M512 80c0 18-14.3 34.6-38.4 48c-29.1 16.1-72.5 27.5-122.3 30.9c-3.7-1.8-7.4-3.5-11.3-5C300.6 137.4 248.2 128 192 128c-8.3 0-16.4 .2-24.5 .6l-1.1-.6C142.3 114.6 128 98 128 80c0-44.2 86-80 192-80S512 35.8 512 80zM160.7 161.1c10.2-.7 20.7-1.1 31.3-1.1c62.2 0 117.4 12.3 152.5 31.4C369.3 204.9 384 221.7 384 240c0 4-.7 7.9-2.1 11.7c-4.6 13.2-17 25.3-35 35.5c0 0 0 0 0 0c-.1 .1-.3 .1-.4 .2c0 0 0 0 0 0s0 0 0 0c-.3 .2-.6 .3-.9 .5c-35 19.4-90.8 32-153.6 32c-59.6 0-112.9-11.3-148.2-29.1c-1.9-.9-3.7-1.9-5.5-2.9C14.3 274.6 0 258 0 240c0-34.8 53.4-64.5 128-75.4c10.5-1.5 21.4-2.7 32.7-3.5zM416 240c0-21.9-10.6-39.9-24.1-53.4c28.3-4.4 54.2-11.4 76.2-20.5c16.3-6.8 31.5-15.2 43.9-25.5l0 35.4c0 19.3-16.5 37.1-43.8 50.9c-14.6 7.4-32.4 13.7-52.4 18.5c.1-1.8 .2-3.5 .2-5.3zm-32 96c0 18-14.3 34.6-38.4 48c-1.8 1-3.6 1.9-5.5 2.9C304.9 404.7 251.6 416 192 416c-62.8 0-118.6-12.6-153.6-32C14.3 370.6 0 354 0 336l0-35.4c12.5 10.3 27.6 18.7 43.9 25.5C83.4 342.6 135.8 352 192 352s108.6-9.4 148.1-25.9c7.8-3.2 15.3-6.9 22.4-10.9c6.1-3.4 11.8-7.2 17.2-11.2c1.5-1.1 2.9-2.3 4.3-3.4l0 3.4 0 5.7 0 26.3zm32 0l0-32 0-25.9c19-4.2 36.5-9.5 52.1-16c16.3-6.8 31.5-15.2 43.9-25.5l0 35.4c0 10.5-5 21-14.9 30.9c-16.3 16.3-45 29.7-81.3 38.4c.1-1.7 .2-3.5 .2-5.3zM192 448c56.2 0 108.6-9.4 148.1-25.9c16.3-6.8 31.5-15.2 43.9-25.5l0 35.4c0 44.2-86 80-192 80S0 476.2 0 432l0-35.4c12.5 10.3 27.6 18.7 43.9 25.5C83.4 438.6 135.8 448 192 448z" />
                            </svg>
                            <p class="font-medium text-lg text-neutral-800">
                                Ksh{{ number_format($engagement->agreed_amount, 2) }}</p>
                        </div>
                    </div>

                    <div
                        class="bg-white rounded-lg p-4 border border-neutral-200 shadow-sm transition-transform hover:scale-[1.01]">
                        <div class="text-neutral-500 text-sm mb-1">Service Fee</div>
                        <div class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-rose-500 mr-2" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z" />
                            </svg>
                            <p class="font-medium text-neutral-800">Ksh{{ number_format($engagement->service_fee, 2) }}
                            </p>
                        </div>
                    </div>

                    <div
                        class="bg-white rounded-lg p-4 border border-neutral-200 shadow-sm transition-transform hover:scale-[1.01]">
                        <div class="text-neutral-500 text-sm mb-1">Your Net Earnings</div>
                        <div class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-secondary mr-2" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                            </svg>
                            <p class="font-medium text-lg text-secondary">
                                Ksh{{ number_format($engagement->net_amount, 2) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Deliverables Section -->
            <div class="p-6 border-b border-neutral-200">
                <h4 class="font-tertiary font-semibold text-lg text-primary mb-4 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    Project Deliverables
                </h4>

                <div class="space-y-4">
                    @forelse($engagement->deliverables as $deliverable)
                        <div
                            class="bg-white border border-neutral-200 rounded-lg p-5 shadow-sm hover:shadow transition-all duration-200">
                            <div class="flex flex-wrap gap-2 justify-between items-start mb-3">
                                <div class="flex items-center">
                                    <div class="bg-secondary/10 rounded-full p-2 mr-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-secondary"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <h5 class="font-secondary font-semibold text-neutral-800">{{ $deliverable->title }}
                                    </h5>
                                </div>
                                @if ($deliverable->due_date)
                                    <div
                                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-accent/10 text-accent border border-accent/20">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        Due: {{ $deliverable->due_date->format('M j, Y') }}
                                    </div>
                                @else
                                    <div
                                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-accent/10 text-accent border border-accent/20">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        No Due Date Set
                                    </div>
                                @endif
                            </div>
                            <p class="text-neutral-600 ml-10 text-sm text-justify">{{ $deliverable->description }}</p>
                        </div>
                    @empty
                        <div class="bg-neutral-50 rounded-lg p-8 text-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-neutral-300 mx-auto mb-3"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            <p class="text-neutral-500 italic">No deliverables have been specified for this project
                                yet.
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Response Form -->
            <form method="POST" action="{{ route('engagements.respond', ['engagement' => $engagement]) }}"
                class="p-6">
                @csrf

                <div class="space-y-6">
                    @if ($engagement->status !== 'employer_accepted')
                        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-4">
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-500 mr-2"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-amber-800 font-medium">
                                    You have already {{ $engagement->status === 'active' ? 'accepted' : 'declined' }}
                                    this offer on
                                    {{ $engagement->status === 'active' ? $engagement->started_at->format('M d, Y') : $engagement->cancelled_at->format('M d, Y') }}.
                                </p>
                            </div>
                        </div>
                    @endif

                    <div>
                        <label class="flex items-center font-tertiary font-semibold text-lg text-primary mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                            Your Response
                        </label>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <label
                                class="relative flex {{ $engagement->status !== 'employer_accepted' ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer' }} rounded-xl border border-neutral-200 bg-white p-5 shadow-sm hover:bg-neutral-50 transition duration-200">
                                <input type="radio" name="response" value="accepted" class="sr-only peer"
                                    {{ $engagement->status === 'active' ? 'checked' : '' }}
                                    {{ $engagement->status !== 'employer_accepted' ? 'disabled' : '' }}>
                                <div class="flex w-full items-center">
                                    <div class="flex-shrink-0 mr-4">
                                        <div
                                            class="w-10 h-10 rounded-full bg-secondary/10 flex items-center justify-center peer-checked:bg-secondary text-secondary peer-checked:text-white transition-all duration-200">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="flex-grow">
                                        <p class="font-secondary font-medium text-neutral-900">Accept Offer</p>
                                        <p class="text-neutral-500 text-sm">I agree to all terms and conditions</p>
                                    </div>
                                </div>
                                <div class="absolute -inset-px rounded-xl border-2 pointer-events-none peer-checked:border-secondary opacity-0 peer-checked:opacity-100 transition-opacity"
                                    aria-hidden="true"></div>
                            </label>

                            <label
                                class="relative flex {{ $engagement->status !== 'employer_accepted' ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer' }} rounded-xl border border-neutral-200 bg-white p-5 shadow-sm hover:bg-neutral-50 transition duration-200">
                                <input type="radio" name="response" value="declined" class="sr-only peer"
                                    {{ $engagement->status === 'cancelled' ? 'checked' : '' }}
                                    {{ $engagement->status !== 'employer_accepted' ? 'disabled' : '' }}>
                                <div class="flex w-full items-center">
                                    <div class="flex-shrink-0 mr-4">
                                        <div
                                            class="w-10 h-10 rounded-full bg-neutral-100 flex items-center justify-center peer-checked:bg-neutral-700 text-neutral-400 peer-checked:text-white transition-all duration-200">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-rose-500"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="flex-grow">
                                        <p class="font-secondary font-medium text-neutral-900">Decline Offer</p>
                                        <p class="text-neutral-500 text-sm">This job isn't right for me</p>
                                    </div>
                                </div>
                                <div class="absolute -inset-px rounded-xl border-2 pointer-events-none peer-checked:border-rose-500 opacity-0 peer-checked:opacity-100 transition-opacity"
                                    aria-hidden="true"></div>
                            </label>
                        </div>
                    </div>

                    <div class="bg-neutral-50 rounded-xl p-5 border border-neutral-200">
                        <label for="notes"
                            class="block font-secondary font-medium text-neutral-700 mb-2 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-tertiary" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            {{ $engagement->status !== 'employer_accepted' ? 'Your Notes' : 'Additional Notes' }}
                            <span
                                class="text-xs font-normal text-neutral-500 ml-2">{{ $engagement->status === 'employer_accepted' ? '(Optional)' : '' }}</span>
                        </label>
                        <textarea id="notes" name="notes" rows="4"
                            class="block w-full rounded-lg border-neutral-300 shadow-sm focus:border-secondary focus:ring-secondary focus:ring-opacity-50 transition duration-200 resize-none {{ $engagement->status !== 'pending' ? 'bg-neutral-100' : '' }}"
                            placeholder="{{ $engagement->status === 'employer_accepted' ? 'Type your message to the job poster here...' : '' }}"
                            {{ $engagement->status !== 'employer_accepted' ? 'readonly' : '' }}>{{ $engagement->notes ?? '' }}</textarea>
                    </div>

                    <div class="flex justify-end pt-4">
                        <a href="{{ route('engagements.index') }}"
                            class="mr-4 inline-flex justify-center items-center py-2.5 px-6 border border-neutral-300 rounded-lg text-sm font-medium text-neutral-700 bg-white hover:bg-neutral-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500 shadow-sm transition duration-150">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            {{ $engagement->status !== 'employer_accepted' ? 'Back' : 'Cancel' }}
                        </a>

                        @if ($engagement->status === 'employer_accepted')
                            <button type="submit" id="submitResponseBtn"
                                class="inline-flex justify-center items-center py-2.5 px-6 border border-transparent rounded-lg text-sm font-medium text-white bg-gradient-to-r from-primary to-primary/90 hover:from-primary/90 hover:to-primary/80 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary shadow-md transition duration-150 opacity-50 cursor-not-allowed"
                                disabled>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                                Submit Response
                            </button>

                            <script>
                                // Check if any response option is selected on page load
                                document.addEventListener('DOMContentLoaded', function() {
                                    checkResponseSelection();

                                    // Add event listeners to radio buttons
                                    const radioButtons = document.querySelectorAll('input[name="response"]');
                                    radioButtons.forEach(function(radio) {
                                        radio.addEventListener('change', checkResponseSelection);
                                    });
                                });

                                // Function to check if a response is selected and enable/disable button accordingly
                                function checkResponseSelection() {
                                    const responseSelected = document.querySelector('input[name="response"]:checked') !== null;
                                    const submitButton = document.getElementById('submitResponseBtn');

                                    if (responseSelected) {
                                        submitButton.disabled = false;
                                        submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
                                    } else {
                                        submitButton.disabled = true;
                                        submitButton.classList.add('opacity-50', 'cursor-not-allowed');
                                    }
                                }
                            </script>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
