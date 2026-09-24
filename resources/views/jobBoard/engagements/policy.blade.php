<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-tertiary font-bold text-2xl text-primary leading-tight flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="h-6 w-6 mr-2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
                Cancellation & Payment Policy
            </h2>
        </div>
    </x-slot>

    <div class="py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Introduction Card with Gradient Background -->
            <div class="mb-8 bg-gradient-to-r from-primary/90 to-primary rounded-lg shadow-lg overflow-hidden">
                <div class="px-6 py-8 text-white">
                    <div class="flex items-center mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="w-8 h-8 mr-3">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                        </svg>
                        <h3 class="text-xl font-tertiary font-bold">{{ config('app.name') }} Cancellation & Payment
                            Policy</h3>
                    </div>
                    <p class="font-main text-neutral-100">This policy governs cancellations, payment processing, and
                        dispute resolution for engagements between Clients and Freelancers on {{ config('app.name') }}.
                    </p>
                    <div class="mt-4 flex items-center text-sm">
                        <div class="mr-6 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-1">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 9v7.5" />
                            </svg>
                            <span>Effective Date: May 15, 2025</span>
                        </div>
                        <div class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-1">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                            </svg>
                            <span>Last Updated: May 15, 2025</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table of Contents -->
            <div class="mb-8 bg-white rounded-lg shadow-md overflow-hidden border border-neutral-200">
                <div class="px-6 py-5 border-b border-neutral-200 bg-neutral-50">
                    <div class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="w-5 h-5 text-secondary mr-2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                        </svg>
                        <h3 class="text-lg font-tertiary font-bold text-primary">Quick Navigation</h3>
                    </div>
                </div>
                <div class="px-6 py-4">
                    <ul class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 font-main">
                        <li class="flex items-center">
                            <span
                                class="flex-shrink-0 w-6 h-6 flex items-center justify-center rounded-full bg-primary text-white text-xs mr-2">1</span>
                            <a href="#cancellation-overview"
                                class="text-primary hover:text-secondary transition-colors duration-200">Cancellation
                                Overview</a>
                        </li>
                        <li class="flex items-center">
                            <span
                                class="flex-shrink-0 w-6 h-6 flex items-center justify-center rounded-full bg-primary text-white text-xs mr-2">2</span>
                            <a href="#handling-deliverables"
                                class="text-primary hover:text-secondary transition-colors duration-200">Handling
                                Deliverables</a>
                        </li>
                        <li class="flex items-center">
                            <span
                                class="flex-shrink-0 w-6 h-6 flex items-center justify-center rounded-full bg-primary text-white text-xs mr-2">3</span>
                            <a href="#payment-eligibility"
                                class="text-primary hover:text-secondary transition-colors duration-200">Payment
                                Eligibility</a>
                        </li>
                        <li class="flex items-center">
                            <span
                                class="flex-shrink-0 w-6 h-6 flex items-center justify-center rounded-full bg-primary text-white text-xs mr-2">4</span>
                            <a href="#payment-processing"
                                class="text-primary hover:text-secondary transition-colors duration-200">Payment
                                Processing Flow</a>
                        </li>
                        <li class="flex items-center">
                            <span
                                class="flex-shrink-0 w-6 h-6 flex items-center justify-center rounded-full bg-primary text-white text-xs mr-2">5</span>
                            <a href="#escrow"
                                class="text-primary hover:text-secondary transition-colors duration-200">Escrow and Fund
                                Release</a>
                        </li>
                        <li class="flex items-center">
                            <span
                                class="flex-shrink-0 w-6 h-6 flex items-center justify-center rounded-full bg-primary text-white text-xs mr-2">6</span>
                            <a href="#dispute"
                                class="text-primary hover:text-secondary transition-colors duration-200">Dispute
                                Resolution</a>
                        </li>
                        <li class="flex items-center">
                            <span
                                class="flex-shrink-0 w-6 h-6 flex items-center justify-center rounded-full bg-primary text-white text-xs mr-2">7</span>
                            <a href="#platform-rights"
                                class="text-primary hover:text-secondary transition-colors duration-200">Platform
                                Rights</a>
                        </li>
                        <li class="flex items-center">
                            <span
                                class="flex-shrink-0 w-6 h-6 flex items-center justify-center rounded-full bg-primary text-white text-xs mr-2">8</span>
                            <a href="#communication"
                                class="text-primary hover:text-secondary transition-colors duration-200">Communication
                                and Notifications</a>
                        </li>
                        <li class="flex items-center">
                            <span
                                class="flex-shrink-0 w-6 h-6 flex items-center justify-center rounded-full bg-primary text-white text-xs mr-2">9</span>
                            <a href="#policy-updates"
                                class="text-primary hover:text-secondary transition-colors duration-200">Policy
                                Updates</a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content Sections -->
            <div class="space-y-6">
                <!-- Section 1: Cancellation Overview -->
                <section id="cancellation-overview" class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="border-l-4 border-secondary">
                        <div class="px-6 py-5 bg-gradient-to-r from-secondary/10 to-white border-b border-neutral-200">
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-secondary mr-2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <h3 class="text-lg font-tertiary font-bold text-primary">1. Cancellation Overview</h3>
                            </div>
                        </div>
                        <div class="px-6 py-5 font-main text-neutral-700">
                            <p class="mb-4">Either the Client or the Freelancer may cancel an engagement at any time
                                for any reason. All cancellations must include:</p>

                            <ul class="mt-4 space-y-3">
                                <li class="flex items-start">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor"
                                        class="w-5 h-5 text-secondary mt-0.5 mr-2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>A stated reason for cancellation.</span>
                                </li>
                                <li class="flex items-start">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor"
                                        class="w-5 h-5 text-secondary mt-0.5 mr-2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>Selection of a cancellation type (e.g., mutual, early termination,
                                        dispute).</span>
                                </li>
                                <li class="flex items-start">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor"
                                        class="w-5 h-5 text-secondary mt-0.5 mr-2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>A review of submitted deliverables (if any).</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </section>

                <!-- Section 2: Handling Deliverables -->
                <section id="handling-deliverables" class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="border-l-4 border-secondary">
                        <div class="px-6 py-5 bg-gradient-to-r from-secondary/10 to-white border-b border-neutral-200">
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-secondary mr-2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25-2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                                </svg>
                                <h3 class="text-lg font-tertiary font-bold text-primary">2. Handling Deliverables Upon
                                    Cancellation</h3>
                            </div>
                        </div>
                        <div class="px-6 py-5 font-main text-neutral-700">
                            <p class="mb-4">Upon cancellation, the platform will identify whether any deliverables
                                have been:</p>

                            <ul class="mt-4 space-y-3">
                                <li class="flex items-start">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor"
                                        class="w-5 h-5 text-secondary mt-0.5 mr-2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>Submitted but not approved.</span>
                                </li>
                                <li class="flex items-start">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor"
                                        class="w-5 h-5 text-secondary mt-0.5 mr-2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>Approved by the Client.</span>
                                </li>
                            </ul>

                            <div class="mt-5 bg-neutral-50 p-4 rounded-lg border border-neutral-200">
                                <div class="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-accent mr-2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                    </svg>
                                    <p class="font-medium text-sm">Deliverables that have already been approved are
                                        considered
                                        final and eligible for payment.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Section 3: Payment Eligibility -->
                <section id="payment-eligibility" class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="border-l-4 border-secondary">
                        <div class="px-6 py-5 bg-gradient-to-r from-secondary/10 to-white border-b border-neutral-200">
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-secondary mr-2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                                </svg>
                                <h3 class="text-lg font-tertiary font-bold text-primary">3. Payment Eligibility</h3>
                            </div>
                        </div>
                        <div class="px-6 py-5 font-main text-neutral-700">
                            <div class="mb-6">
                                <h4 class="text-md font-tertiary font-semibold mb-3 text-primary">A. Approved
                                    Deliverables</h4>
                                <ul class="space-y-3">
                                    <li class="flex items-start">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor"
                                            class="w-5 h-5 text-secondary mt-0.5 mr-2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>Approved deliverables will be considered for payment regardless of who
                                            initiated the cancellation.</span>
                                    </li>
                                    <li class="flex items-start">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor"
                                            class="w-5 h-5 text-secondary mt-0.5 mr-2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>The Freelancer is eligible to receive payment for approved work.</span>
                                    </li>
                                    <li class="flex items-start">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor"
                                            class="w-5 h-5 text-secondary mt-0.5 mr-2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>Funds will be released from escrow or requested from the Client if not
                                            already funded.</span>
                                    </li>
                                </ul>
                            </div>

                            <div>
                                <h4 class="text-md font-tertiary font-semibold mb-3 text-primary">B. Unapproved
                                    Deliverables</h4>
                                <ul class="space-y-3">
                                    <li class="flex items-start">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor"
                                            class="w-5 h-5 text-secondary mt-0.5 mr-2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>Clients may choose to approve submitted work at the time of
                                            cancellation.</span>
                                    </li>
                                    <li class="flex items-start">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor"
                                            class="w-5 h-5 text-secondary mt-0.5 mr-2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>If no approval is granted, no payment will be processed for unapproved
                                            work unless the dispute process is triggered.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Section 4: Payment Processing Flow  -->
                <section id="payment-processing" class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="border-l-4 border-secondary">
                        <div class="px-6 py-5 bg-gradient-to-r from-secondary/10 to-white border-b border-neutral-200">
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-secondary mr-2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5m.75-9l3-3 2.148 2.148A12.061 12.061 0 0116.5 7.605" />
                                </svg>
                                <h3 class="text-lg font-tertiary font-bold text-primary">4. Payment Processing Flow
                                </h3>
                            </div>
                        </div>
                        <div class="px-6 py-5 font-main text-neutral-700">
                            <div class="mb-6">
                                <div
                                    class="inline-flex items-center px-3 py-1 rounded-full bg-accent/10 text-accent font-medium text-sm mb-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 00-3.7-3.7 48.678 48.678 0 00-7.324 0 4.006 4.006 0 00-3.7 3.7c-.017.22-.032.441-.046.662M19.5 12l3-3m-3 3l-3-3m-12 3c0 1.232.046 2.453.138 3.662a4.006 4.006 0 003.7 3.7 48.656 48.656 0 007.324 0 4.006 4.006 0 003.7-3.7c.017-.22.032-.441.046-.662M4.5 12l3 3m-3-3l-3 3" />
                                    </svg>
                                    If the Client Cancels
                                </div>
                                <ul class="space-y-3">
                                    <li class="flex items-start">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor"
                                            class="w-5 h-5 text-secondary mt-0.5 mr-2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                        </svg>
                                        <span>The Freelancer will be shown a breakdown of deliverables that were
                                            approved and the corresponding payable amount.</span>
                                    </li>
                                    <li class="flex items-start">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor"
                                            class="w-5 h-5 text-secondary mt-0.5 mr-2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                        </svg>
                                        <span>The Freelancer may accept the payment or request a dispute if they believe
                                            the payment is insufficient.</span>
                                    </li>
                                </ul>
                            </div>

                            <div class="mb-4">
                                <div
                                    class="inline-flex items-center px-3 py-1 rounded-full bg-secondary/10 text-secondary font-medium text-sm mb-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 00-3.7-3.7 48.678 48.678 0 00-7.324 0 4.006 4.006 0 00-3.7 3.7c-.017.22-.032.441-.046.662M19.5 12l3-3m-3 3l-3-3m-12 3c0 1.232.046 2.453.138 3.662a4.006 4.006 0 003.7 3.7 48.656 48.656 0 007.324 0 4.006 4.006 0 003.7-3.7c.017-.22.032-.441.046-.662M4.5 12l3 3m-3-3l-3 3" />
                                    </svg>
                                    If the Freelancer Cancels
                                </div>
                                <ul class="space-y-3">
                                    <li class="flex items-start">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor"
                                            class="w-5 h-5 text-secondary mt-0.5 mr-2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                        </svg>
                                        <span>The Client will be prompted to review submitted deliverables and approve
                                            those they find satisfactory.</span>
                                    </li>
                                    <li class="flex items-start">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor"
                                            class="w-5 h-5 text-secondary mt-0.5 mr-2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                        </svg>
                                        <span>The platform will calculate the payment due based on approved work.</span>
                                    </li>
                                    <li class="flex items-start">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor"
                                            class="w-5 h-5 text-secondary mt-0.5 mr-2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                        </svg>
                                        <span>The Freelancer will then be notified of the payment status.</span>
                                    </li>
                                </ul>
                            </div>

                            <div class="mt-6 flex items-center p-4 bg-primary/5 rounded-lg border border-primary/10">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor"
                                    class="flex-shrink-0 w-6 h-6 text-primary mr-3">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                </svg>
                                <p class="text-sm">Both parties will receive a detailed cancellation summary that
                                    outlines the approved deliverables, payment amounts, and next steps.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Section 5: Escrow and Fund Release -->
                <section id="escrow" class="bg-white rounded-lg shadow-md overflow-hidden mt-6">
                    <div class="border-l-4 border-secondary">
                        <div class="px-6 py-5 bg-gradient-to-r from-secondary/10 to-white border-b border-neutral-200">
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-secondary mr-2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v3" />
                                </svg>
                                <h3 class="text-lg font-tertiary font-bold text-primary">5. Escrow and Fund Release
                                </h3>
                            </div>
                        </div>
                        <div class="px-6 py-5 font-main text-neutral-700">
                            <ul class="space-y-3">
                                <li class="flex items-start">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor"
                                        class="w-5 h-5 text-secondary mt-0.5 mr-2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>Funds held in escrow will only be released for approved deliverables.</span>
                                </li>
                                <li class="flex items-start">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor"
                                        class="w-5 h-5 text-secondary mt-0.5 mr-2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>If no deliverables are approved, the full escrowed amount will be refunded to
                                        the Client.</span>
                                </li>
                                <li class="flex items-start">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor"
                                        class="w-5 h-5 text-secondary mt-0.5 mr-2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>For milestone-based engagements, only the current milestone amount is
                                        affected.</span>
                                </li>
                            </ul>

                            <div class="flex flex-col md:flex-row gap-4 mt-6">
                                <div class="flex-1 p-4 rounded-lg border border-neutral-200 bg-neutral-50">
                                    <div class="flex items-center mb-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor"
                                            class="w-5 h-5 text-accent mr-2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <h4 class="font-tertiary font-semibold text-sm text-primary">Processing Time
                                        </h4>
                                    </div>
                                    <p class="text-sm text-neutral-600">Escrow funds are typically processed within 3-5
                                        business days after approval.</p>
                                </div>
                                <div class="flex-1 p-4 rounded-lg border border-secondary/20 bg-secondary/5">
                                    <div class="flex items-center mb-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor"
                                            class="w-5 h-5 text-secondary mr-2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                                        </svg>
                                        <h4 class="font-tertiary font-semibold text-sm text-primary">Fund Security</h4>
                                    </div>
                                    <p class="text-sm text-neutral-600">All escrowed funds are held in secure,
                                        third-party accounts separate from platform operating funds.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Section 6: Dispute Resolution -->
                <section id="dispute" class="bg-white rounded-lg shadow-md overflow-hidden mt-6">
                    <div class="border-l-4 border-secondary">
                        <div class="px-6 py-5 bg-gradient-to-r from-secondary/10 to-white border-b border-neutral-200">
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-secondary mr-2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 12.75c1.148 0 2.278.08 3.383.237 1.037.146 1.866.966 1.866 2.013 0 3.728-2.35 6.75-5.25 6.75S6.75 18.728 6.75 15c0-1.046.83-1.867 1.866-2.013A24.204 24.204 0 0112 12.75zm0 0c2.883 0 5.647.508 8.207 1.44a23.91 23.91 0 01-1.152 6.06M12 12.75c-2.883 0-5.647.508-8.208 1.44.125 2.104.52 4.136 1.153 6.06M12 12.75a2.25 2.25 0 002.248-2.354M12 12.75a2.25 2.25 0 01-2.248-2.354M12 8.25c.995 0 1.971-.08 2.922-.236.403-.066.74-.358.795-.762a3.778 3.778 0 00-.399-2.25M12 8.25c-.995 0-1.97-.08-2.922-.236-.402-.066-.74-.358-.795-.762a3.734 3.734 0 01.4-2.253M12 8.25a2.25 2.25 0 00-2.248 2.146M12 8.25a2.25 2.25 0 012.248 2.146M8.683 5a6.032 6.032 0 01-1.155-1.002c.07-.63.27-1.222.574-1.747m.581 2.749A3.75 3.75 0 0115.318 5m0 0c.427-.283.815-.62 1.155-.999a4.471 4.471 0 00-.575-1.752M4.921 6a24.048 24.048 0 00-.392 3.314c1.668.546 3.416.914 5.223 1.082M19.08 6c.205 1.08.337 2.187.392 3.314a23.882 23.882 0 01-5.223 1.082" />
                                </svg>
                                <h3 class="text-lg font-tertiary font-bold text-primary">6. Dispute Resolution</h3>
                            </div>
                        </div>
                        <div class="px-6 py-5 font-main text-neutral-700">
                            <p class="mb-4">If either party disagrees with the payment outcome:</p>

                            <ul class="space-y-3">
                                <li class="flex items-start">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor"
                                        class="w-5 h-5 text-secondary mt-0.5 mr-2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>A dispute can be raised by selecting "Dispute this decision" within 5 days of
                                        the cancellation notice.</span>
                                </li>
                                <li class="flex items-start">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor"
                                        class="w-5 h-5 text-secondary mt-0.5 mr-2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>Our admin team will review engagement history, deliverables, and communication
                                        logs.</span>
                                </li>
                                <li class="flex items-start">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor"
                                        class="w-5 h-5 text-secondary mt-0.5 mr-2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>A final resolution will be provided within 7–14 business days.</span>
                                </li>
                            </ul>

                            <div class="mt-6">
                                <div class="relative">
                                    <div class="absolute inset-0 flex items-center" aria-hidden="true">
                                        <div class="w-full border-t border-neutral-200"></div>
                                    </div>
                                    <div class="relative flex justify-center">
                                        <span class="bg-white px-3 text-sm text-neutral-500 font-medium">Dispute
                                            Process</span>
                                    </div>
                                </div>

                                <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="border border-neutral-200 rounded-lg p-4 relative">
                                        <div
                                            class="absolute -top-3 left-4 bg-white px-2 text-sm font-medium text-primary">
                                            Step 1</div>
                                        <div class="mt-1 flex flex-col items-center text-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                class="w-8 h-8 text-accent">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 01-1.44-4.282m3.102.069a18.03 18.03 0 01-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 018.835 2.535M10.34 6.66a23.847 23.847 0 008.835-2.535m0 0A23.74 23.74 0 0018.795 3m.38 1.125a23.91 23.91 0 011.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 001.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 010 3.46" />
                                            </svg>
                                            <h4 class="text-sm font-semibold mt-2">File Dispute</h4>
                                            <p class="text-xs text-neutral-500 mt-1">Submit detailed reasoning for the
                                                dispute with any supporting evidence.</p>
                                        </div>
                                    </div>
                                    <div class="border border-neutral-200 rounded-lg p-4 relative">
                                        <div
                                            class="absolute -top-3 left-4 bg-white px-2 text-sm font-medium text-primary">
                                            Step 2</div>
                                        <div class="mt-1 flex flex-col items-center text-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                class="w-8 h-8 text-accent">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m5.231 13.481L15 17.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v16.5c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9zm3.75 11.625a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                                            </svg>
                                            <h4 class="text-sm font-semibold mt-2">Review Process</h4>
                                            <p class="text-xs text-neutral-500 mt-1">Admin team evaluates all project
                                                communications, deliverables and contract terms.</p>
                                        </div>
                                    </div>
                                    <div class="border border-neutral-200 rounded-lg p-4 relative">
                                        <div
                                            class="absolute -top-3 left-4 bg-white px-2 text-sm font-medium text-primary">
                                            Step 3</div>
                                        <div class="mt-1 flex flex-col items-center text-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                class="w-8 h-8 text-accent">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <h4 class="text-sm font-semibold mt-2">Resolution</h4>
                                            <p class="text-xs text-neutral-500 mt-1">Final decision with detailed
                                                explanation and fund distribution instructions.</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Dispute Resolution Tips Box -->
                                <div class="mt-6 bg-neutral-50 rounded-lg border border-neutral-200 p-4">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                class="w-6 h-6 text-secondary">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <h4 class="text-base font-semibold text-primary">Tips for Successful
                                                Dispute Resolution</h4>
                                            <ul class="mt-2 text-sm text-neutral-600 space-y-1">
                                                <li class="flex items-start">
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="h-3.5 w-3.5 text-secondary mr-1 mt-0.5" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                    <span>Provide clear, factual evidence related to your
                                                        claim</span>
                                                </li>
                                                <li class="flex items-start">
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="h-3.5 w-3.5 text-secondary mr-1 mt-0.5" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                    <span>Reference specific contract terms or project
                                                        milestones</span>
                                                </li>
                                                <li class="flex items-start">
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="h-3.5 w-3.5 text-secondary mr-1 mt-0.5" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                    <span>Maintain professional communication throughout the
                                                        process</span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Section 7: Platform Rights -->
                <section id="platform-rights" class="bg-white rounded-lg shadow-md overflow-hidden mt-6">
                    <div class="border-l-4 border-secondary">
                        <div class="px-6 py-5 bg-gradient-to-r from-secondary/10 to-white border-b border-neutral-200">
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-secondary mr-2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" />
                                </svg>
                                <h3 class="text-lg font-tertiary font-bold text-primary">7. Platform Rights</h3>
                            </div>
                        </div>
                        <div class="px-6 py-5 font-main text-neutral-700">
                            <p class="mb-4">{{ config('app.name') }} reserves the right to:</p>

                            <ul class="space-y-3">
                                <li class="flex items-start">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor"
                                        class="w-5 h-5 text-secondary mt-0.5 mr-2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>Withhold or reverse payment in the event of fraud or policy violations.</span>
                                </li>
                                <li class="flex items-start">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor"
                                        class="w-5 h-5 text-secondary mt-0.5 mr-2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>Suspend accounts involved in repeated or malicious cancellations.</span>
                                </li>
                                <li class="flex items-start">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor"
                                        class="w-5 h-5 text-secondary mt-0.5 mr-2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>Use discretion in resolving disputes where platform policy or deliverable
                                        clarity is in question.</span>
                                </li>
                            </ul>

                            <!-- Platform Rights Visual Element -->
                            <div
                                class="mt-6 bg-gradient-to-br from-primary/5 to-secondary/5 rounded-lg p-5 border border-neutral-200">
                                <div class="flex flex-col md:flex-row items-center">
                                    <div class="flex-shrink-0 mb-4 md:mb-0 md:mr-6">
                                        <!-- Shield Icon with Platform Protection -->
                                        <div class="relative">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                                fill="none" stroke="currentColor" stroke-width="1.5"
                                                class="w-6 h-6 text-secondary">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="text-sm font-tertiary font-semibold text-primary mb-2">Platform
                                            Protection Measures</h4>
                                        <p class="text-sm text-neutral-600">
                                            {{ config('app.name') }} employs these rights to maintain platform integrity
                                            and protect all users from potential fraud and abuse. These measures help
                                            ensure fair transactions and maintain trust within our marketplace
                                            ecosystem.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Section 8: Communication and Notifications -->
                <section id="communication" class="bg-white rounded-lg shadow-md overflow-hidden mt-6">
                    <div class="border-l-4 border-secondary">
                        <div class="px-6 py-5 bg-gradient-to-r from-secondary/10 to-white border-b border-neutral-200">
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-secondary mr-2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
                                </svg>
                                <h3 class="text-lg font-tertiary font-bold text-primary">8. Communication and
                                    Notifications</h3>
                            </div>
                        </div>
                        <div class="px-6 py-5 font-main text-neutral-700">
                            <p class="mb-4">All actions in the cancellation flow will trigger platform notifications
                                and email alerts to both parties to ensure transparency and timely resolution.</p>

                            <!-- Communication Methods Illustration -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-5">
                                <div
                                    class="bg-white shadow-sm rounded-lg p-4 border border-neutral-200 flex flex-col items-center text-center">
                                    <div class="rounded-full bg-primary/10 p-3 mb-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-primary">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                                        </svg>
                                    </div>
                                    <h4 class="text-sm font-semibold text-primary">In-App Notifications</h4>
                                    <p class="text-xs text-neutral-500 mt-2">Real-time updates directly within your
                                        dashboard for immediate awareness.</p>
                                </div>

                                <div
                                    class="bg-white shadow-sm rounded-lg p-4 border border-neutral-200 flex flex-col items-center text-center">
                                    <div class="rounded-full bg-secondary/10 p-3 mb-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-secondary">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                                        </svg>
                                    </div>
                                    <h4 class="text-sm font-semibold text-primary">Email Alerts</h4>
                                    <p class="text-xs text-neutral-500 mt-2">Detailed notifications sent to your
                                        registered email address with actionable information.</p>
                                </div>

                                <div
                                    class="bg-white shadow-sm rounded-lg p-4 border border-neutral-200 flex flex-col items-center text-center">
                                    <div class="rounded-full bg-accent/10 p-3 mb-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-accent">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M8.625 9.75a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 01.778-.332 48.294 48.294 0 005.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
                                        </svg>
                                    </div>
                                    <h4 class="text-sm font-semibold text-primary">Activity Timeline</h4>
                                    <p class="text-xs text-neutral-500 mt-2">Chronological record of all
                                        cancellation-related events for complete transparency.</p>
                                </div>
                            </div>

                            <!-- Communication Settings Panel -->
                            <div class="mt-6 bg-neutral-50 rounded-lg border border-neutral-200 p-4">
                                <div class="flex items-center mb-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-secondary mr-2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <h4 class="text-sm font-semibold text-primary">Communication Preferences</h4>
                                </div>
                                <p class="text-xs text-neutral-600 mb-3">
                                    You can customize your notification preferences in your account settings to control
                                    how you receive cancellation and payment updates.
                                </p>
                                <a href="#"
                                    class="inline-flex items-center text-xs font-medium text-secondary hover:text-primary transition-colors duration-200">
                                    <span>Manage notification settings</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 ml-1" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Section 9: Policy Updates -->
                <section id="policy-updates" class="bg-white rounded-lg shadow-md overflow-hidden mt-6">
                    <div class="border-l-4 border-secondary">
                        <div class="px-6 py-5 bg-gradient-to-r from-secondary/10 to-white border-b border-neutral-200">
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-secondary mr-2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                                </svg>
                                <h3 class="text-lg font-tertiary font-bold text-primary">9. Policy Updates</h3>
                            </div>
                        </div>
                        <div class="px-6 py-5 font-main text-neutral-700">
                            <p>This policy is subject to change. Users will be notified of significant changes via email
                                and platform notification.</p>

                            <!-- Policy Update Details -->
                            <div class="mt-5 bg-primary/5 rounded-lg p-4 border border-primary/10">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-primary">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h4 class="text-sm font-semibold text-primary">Policy Version Control</h4>
                                        <div class="mt-2 space-y-2 text-sm">
                                            <div class="flex justify-between text-xs">
                                                <span class="text-neutral-600">Effective Date:</span>
                                                <span class="font-medium text-neutral-800">January 15, 2025</span>
                                            </div>
                                            <div class="flex justify-between text-xs">
                                                <span class="text-neutral-600">Last Updated:</span>
                                                <span class="font-medium text-neutral-800">May 1, 2025</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Policy Update Notification Pattern -->
                            <div class="mt-5 relative">
                                <div class="absolute inset-0 flex items-center" aria-hidden="true">
                                    <div class="w-full border-t border-neutral-200"></div>
                                </div>
                                <div class="relative flex justify-start">
                                    <span class="bg-white pr-3 text-xs text-neutral-500 font-medium">Update
                                        Process</span>
                                </div>
                            </div>

                            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div class="flex items-center justify-center">
                                    <div class="flex flex-col items-center text-center">
                                        <div
                                            class="rounded-full bg-primary/10 h-12 w-12 flex items-center justify-center mb-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                class="w-6 h-6 text-primary">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" />
                                            </svg>
                                        </div>
                                        <p class="text-xs font-medium text-neutral-700">Policy Review</p>
                                        <span class="text-xs text-neutral-500 mt-1">Regular evaluation of policy
                                            effectiveness</span>
                                    </div>
                                </div>
                                <div class="flex items-center justify-center">
                                    <div class="flex flex-col items-center text-center">
                                        <div
                                            class="rounded-full bg-primary/10 h-12 w-12 flex items-center justify-center mb-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                class="w-6 h-6 text-primary">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <p class="text-xs font-medium text-neutral-700">Policy Update</p>
                                        <span class="text-xs text-neutral-500 mt-1">Changes implemented based on
                                            platform needs</span>
                                    </div>
                                </div>
                                <div class="flex items-center justify-center">
                                    <div class="flex flex-col items-center text-center">
                                        <div
                                            class="rounded-full bg-primary/10 h-12 w-12 flex items-center justify-center mb-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                class="w-6 h-6 text-primary">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
                                            </svg>
                                        </div>
                                        <p class="text-xs font-medium text-neutral-700">User Notification</p>
                                        <span class="text-xs text-neutral-500 mt-1">Transparent communication of
                                            changes</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Notification Types -->
                            <div class="mt-6">
                                <div class="relative">
                                    <div class="absolute inset-0 flex items-center" aria-hidden="true">
                                        <div class="w-full border-t border-neutral-200"></div>
                                    </div>
                                    <div class="relative flex justify-start">
                                        <span class="bg-white pr-3 text-xs text-neutral-500 font-medium">Notification
                                            Methods</span>
                                    </div>
                                </div>

                                <div class="mt-4 space-y-3">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                class="w-5 h-5 text-secondary mt-0.5">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-neutral-800">Email Notifications</p>
                                            <p class="text-xs text-neutral-600 mt-1">Direct emails sent to users
                                                detailing significant policy changes with summary of key updates.</p>
                                        </div>
                                    </div>

                                    <div class="flex items-start">
                                        <div class="flex-shrink-0">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                class="w-5 h-5 text-secondary mt-0.5">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-neutral-800">Platform Notifications</p>
                                            <p class="text-xs text-neutral-600 mt-1">In-app alerts highlighting policy
                                                changes with links to detailed documentation.</p>
                                        </div>
                                    </div>

                                    <div class="flex items-start">
                                        <div class="flex-shrink-0">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                class="w-5 h-5 text-secondary mt-0.5">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-neutral-800">Documentation Updates</p>
                                            <p class="text-xs text-neutral-600 mt-1">Versioned policy documents
                                                accessible in user dashboard with change tracking.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- User acknowledgment -->
                            <div class="mt-6 bg-neutral-50 rounded-lg p-4 border border-neutral-200">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-primary">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h4 class="text-sm font-semibold text-primary">User Acknowledgment</h4>
                                        <p class="mt-1 text-xs text-neutral-600">For substantial policy changes that
                                            affect user rights or obligations, users may be required to acknowledge the
                                            updated terms before continuing to use the platform.</p>

                                        {{-- <div class="mt-3 flex items-center">
                                            <a href="#"
                                                class="inline-flex items-center text-xs font-medium text-secondary hover:text-primary transition-colors duration-200">
                                                <span>Learn more about policy acknowledgment</span>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 ml-1"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </a>
                                        </div> --}}
                                    </div>
                                </div>
                            </div>

                            <!-- Policy Change Feedback -->
                            <div class="mt-6">
                                <div class="relative">
                                    <div class="absolute inset-0 flex items-center" aria-hidden="true">
                                        <div class="w-full border-t border-neutral-200"></div>
                                    </div>
                                    <div class="relative flex justify-start">
                                        <span class="bg-white pr-3 text-xs text-neutral-500 font-medium">User
                                            Feedback</span>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <p class="text-sm text-neutral-700">We value your input on our policies. If you
                                        have questions or suggestions regarding policy updates, please contact our
                                        support team.</p>

                                    <div class="mt-4">
                                        <a href="#"
                                            class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-secondary hover:bg-secondary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-secondary">
                                            Submit Feedback
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Final footer -->
                            <div class="mt-8 pt-6 border-t border-neutral-200 text-center">
                                <p class="text-xs text-neutral-500">By continuing to use the platform after policy
                                    updates,
                                    users agree to abide by the modified terms.</p>
                            </div>
                        </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
