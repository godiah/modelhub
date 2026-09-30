<!-- resources/views/jobBoard/engagements/respond.blade.php -->
@use('App\Enums\EngagementStatus')
<x-app-layout crumb="Respond to offer">
    <div class="container mx-auto max-w-6xl px-4 pt-10 pb-24 font-main">
        <x-card shadow="lg" clip>
            <!-- Header with gradient background -->
            <div class="bg-gradient-to-r from-primary to-primary/80 p-6 text-white">
                <div class="flex flex-col md:flex-row md:justify-between md:items-center">
                    <div>
                        <h3 class="font-tertiary font-bold text-2xl">{{ $application->job->title }}</h3>
                        <div class="flex items-center mt-2 text-white/80">
                            <x-icon name="user" class="h-4 w-4 mr-1" />
                            <span>{{ $application->poster->name }}</span>
                            <span class="mx-2">•</span>
                            <x-icon name="calendar" class="h-4 w-4 mr-1" />
                            <span><x-date :date="$application->job->created_at" /></span>
                        </div>
                    </div>
                    <div
                        class="mt-4 md:mt-0 flex items-center bg-white/20 px-4 py-2 rounded-full text-sm font-semibold backdrop-blur-sm">
                        <x-icon name="check-circle" class="h-5 w-5 mr-2" />
                        Offer Received
                    </div>
                </div>
            </div>

            <!-- Job Summary Card -->
            <div class="p-6 bg-neutral-50 border-b border-neutral-200">
                <h4 class="font-tertiary font-semibold text-lg text-primary mb-4 flex items-center">
                    <x-icon name="clipboard-list" class="h-5 w-5 mr-2" />
                    Offer Details
                </h4>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-card rounded="lg" class="p-4 transition-transform hover:scale-[1.01]">
                        <div class="text-neutral-500 text-sm mb-1">Application Submitted</div>
                        <div class="flex items-center">
                            <x-icon name="calendar" class="h-5 w-5 text-primary mr-2" />
                            <p class="font-medium text-neutral-800"><x-date :date="$application->created_at" format="F j, Y" /></p>
                        </div>
                    </x-card>

                    <x-card rounded="lg" class="p-4 transition-transform hover:scale-[1.01]">
                        <div class="text-neutral-500 text-sm mb-1">Offer Amount</div>
                        <div class="flex items-center">
                            <x-icon name="coins" class="h-5 w-5 text-accent mr-2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            <p class="font-medium text-lg text-neutral-800">
                                <x-money :amount="$engagement->agreed_amount" /></p>
                        </div>
                    </x-card>

                    <x-card rounded="lg" class="p-4 transition-transform hover:scale-[1.01]">
                        <div class="text-neutral-500 text-sm mb-1">Service Fee</div>
                        <div class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-rose-500 mr-2" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z" />
                            </svg>
                            <p class="font-medium text-neutral-800"><x-money :amount="$engagement->service_fee" />
                            </p>
                        </div>
                    </x-card>

                    <x-card rounded="lg" class="p-4 transition-transform hover:scale-[1.01]">
                        <div class="text-neutral-500 text-sm mb-1">Your Net Earnings</div>
                        <div class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-secondary mr-2" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                            </svg>
                            <p class="font-medium text-lg text-secondary">
                                <x-money :amount="$engagement->net_amount" /></p>
                        </div>
                    </x-card>
                </div>
            </div>

            <!-- Deliverables Section -->
            <div class="p-6 border-b border-neutral-200">
                <h4 class="font-tertiary font-semibold text-lg text-primary mb-4 flex items-center">
                    <x-icon name="clipboard-list" class="h-5 w-5 mr-2" />
                    Project Deliverables
                </h4>

                <div class="space-y-4">
                    @forelse($engagement->deliverables as $deliverable)
                        <x-card rounded="lg" class="p-5 hover:shadow transition-all duration-200">
                            <div class="flex flex-wrap gap-2 justify-between items-start mb-3">
                                <div class="flex items-center">
                                    <div class="bg-secondary/10 rounded-full p-2 mr-3">
                                        <x-icon name="check" class="h-5 w-5 text-secondary" />
                                    </div>
                                    <h5 class="font-secondary font-semibold text-neutral-800">{{ $deliverable->title }}
                                    </h5>
                                </div>
                                @if ($deliverable->due_date)
                                    <div
                                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-accent/10 text-accent border border-accent/20">
                                        <x-icon name="calendar" class="h-4 w-4 mr-1" />
                                        Due: <x-date :date="$deliverable->due_date" />
                                    </div>
                                @else
                                    <div
                                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-accent/10 text-accent border border-accent/20">
                                        <x-icon name="calendar" class="h-4 w-4 mr-1" />
                                        No Due Date Set
                                    </div>
                                @endif
                            </div>
                            <p class="text-neutral-600 ml-10 text-sm text-justify">{{ $deliverable->description }}</p>
                        </x-card>
                    @empty
                        <div class="bg-neutral-50 rounded-lg p-8 text-center">
                            <x-icon name="clipboard-list" class="h-12 w-12 text-neutral-300 mx-auto mb-3" />
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
                    @if ($engagement->status !== EngagementStatus::EmployerAccepted)
                        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-4">
                            <div class="flex items-center">
                                <x-icon name="information-circle" class="h-5 w-5 text-amber-500 mr-2" />
                                <p class="text-amber-800 font-medium">
                                    You have already {{ $engagement->status === EngagementStatus::Active ? 'accepted' : 'declined' }}
                                    this offer on
                                    {{ $engagement->status === EngagementStatus::Active ? $engagement->started_at->format('M d, Y') : $engagement->cancelled_at->format('M d, Y') }}.
                                </p>
                            </div>
                        </div>
                    @endif

                    <div>
                        <label class="flex items-center font-tertiary font-semibold text-lg text-primary mb-4">
                            <x-icon name="check" class="h-5 w-5 mr-2" />
                            Your Response
                        </label>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <label
                                class="relative flex {{ $engagement->status !== EngagementStatus::EmployerAccepted ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer' }} rounded-xl border border-neutral-200 bg-white p-5 shadow-sm hover:bg-neutral-50 transition duration-200">
                                <input type="radio" name="response" value="accepted" class="sr-only peer"
                                    {{ $engagement->status === EngagementStatus::Active ? 'checked' : '' }}
                                    {{ $engagement->status !== EngagementStatus::EmployerAccepted ? 'disabled' : '' }}>
                                <div class="flex w-full items-center">
                                    <div class="flex-shrink-0 mr-4">
                                        <div
                                            class="w-10 h-10 rounded-full bg-secondary/10 flex items-center justify-center peer-checked:bg-secondary text-secondary peer-checked:text-white transition-all duration-200">
                                            <x-icon name="check" class="h-6 w-6" />
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
                                class="relative flex {{ $engagement->status !== EngagementStatus::EmployerAccepted ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer' }} rounded-xl border border-neutral-200 bg-white p-5 shadow-sm hover:bg-neutral-50 transition duration-200">
                                <input type="radio" name="response" value="declined" class="sr-only peer"
                                    {{ $engagement->status === EngagementStatus::Cancelled ? 'checked' : '' }}
                                    {{ $engagement->status !== EngagementStatus::EmployerAccepted ? 'disabled' : '' }}>
                                <div class="flex w-full items-center">
                                    <div class="flex-shrink-0 mr-4">
                                        <div
                                            class="w-10 h-10 rounded-full bg-neutral-100 flex items-center justify-center peer-checked:bg-neutral-700 text-neutral-400 peer-checked:text-white transition-all duration-200">
                                            <x-icon name="x-mark" class="h-6 w-6 text-rose-500" />
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
                            <x-icon name="pencil-square" class="h-5 w-5 mr-2 text-tertiary" />
                            {{ $engagement->status !== EngagementStatus::EmployerAccepted ? 'Your Notes' : 'Additional Notes' }}
                            <span
                                class="text-xs font-normal text-neutral-500 ml-2">{{ $engagement->status === EngagementStatus::EmployerAccepted ? '(Optional)' : '' }}</span>
                        </label>
                        <textarea id="notes" name="notes" rows="4"
                            class="block w-full rounded-lg border-neutral-300 shadow-sm focus:border-secondary focus:ring-secondary focus:ring-opacity-50 transition duration-200 resize-none {{ $engagement->status !== EngagementStatus::EmployerAccepted ? 'bg-neutral-100' : '' }}"
                            placeholder="{{ $engagement->status === EngagementStatus::EmployerAccepted ? 'Type your message to the job poster here...' : '' }}"
                            {{ $engagement->status !== EngagementStatus::EmployerAccepted ? 'readonly' : '' }}>{{ $engagement->notes ?? '' }}</textarea>
                    </div>

                    <div class="flex justify-end pt-4">
                        <a href="{{ route('engagements.index') }}"
                            class="mr-4 inline-flex justify-center items-center py-2.5 px-6 border border-neutral-300 rounded-lg text-sm font-medium text-neutral-700 bg-white hover:bg-neutral-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500 shadow-sm transition duration-150">
                            <x-icon name="x-mark" class="h-4 w-4 mr-2" />
                            {{ $engagement->status !== EngagementStatus::EmployerAccepted ? 'Back' : 'Cancel' }}
                        </a>

                        @if ($engagement->status === EngagementStatus::EmployerAccepted)
                            <button type="submit" id="submitResponseBtn"
                                class="inline-flex justify-center items-center py-2.5 px-6 border border-transparent rounded-lg text-sm font-medium text-white bg-gradient-to-r from-primary to-primary/90 hover:from-primary/90 hover:to-primary/80 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary shadow-md transition duration-150 opacity-50 cursor-not-allowed"
                                disabled>
                                <x-icon name="check" class="h-4 w-4 mr-2" />
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
        </x-card>
    </div>
</x-app-layout>
