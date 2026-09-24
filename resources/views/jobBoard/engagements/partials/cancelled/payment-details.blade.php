<div class="space-y-6">
    {{-- Payment Summary Card --}}
    <div class="bg-white rounded-2xl shadow-lg border border-neutral-200 overflow-hidden">
        <!-- Header with gradient background -->
        <div class="bg-gradient-to-r from-accent to-accent/80 p-6 border-b border-neutral-200 relative">
            <!-- Abstract visual element -->
            <div class="absolute right-0 bottom-0 transform translate-y-1/3 translate-x-1/6 opacity-20">
                <svg width="120" height="120" viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M60 30C77.3 30 91 43.7 91 61C91 78.3 77.3 92 60 92C42.7 92 29 78.3 29 61C29 43.7 42.7 30 60 30Z"
                        fill="none" stroke="#FFFFFF" stroke-width="4" />
                    <path d="M60 42V80M52 48H68M52 74H68" stroke="#FFFFFF" stroke-width="4" stroke-linecap="round" />
                </svg>
            </div>
            <div class="flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white mr-2" width="24" height="24"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <h2 class="font-tertiary font-semibold text-lg text-white">Payment Summary</h2>
            </div>
        </div>
        <div class="p-6">
            <dl class="space-y-5">
                <div class="flex justify-between items-center">
                    <dt class="text-sm font-medium text-neutral-500 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-tertiary mr-2" width="24"
                            height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <path d="m9 15 2 2 4-4"></path>
                        </svg>
                        Original Agreement
                    </dt>
                    <dd class="text-sm text-neutral-900 font-main">
                        Ksh<span>{{ number_format($engagement->application->offer_amount, 2) }}</span>
                    </dd>
                </div>
                <div class="flex justify-between items-center">
                    <dt class="text-sm font-medium text-neutral-500 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-secondary mr-2" width="24"
                            height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                        Approved Deliverables
                    </dt>
                    <dd class="text-sm text-neutral-900 font-main">
                        {{ $engagement->getCompletedDeliverablesCount() }} of
                        {{ $engagement->getTotalDeliverablesCount() }} completed
                    </dd>
                </div>
                <div class="pt-5 border-t border-neutral-200">
                    <dt class="text-sm font-bold text-neutral-800 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" stroke="currentColor" fill="#f59e0b"
                            class="h-4 w-4 text-accent mr-2" viewBox="0 0 512 512">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M512 80c0 18-14.3 34.6-38.4 48c-29.1 16.1-72.5 27.5-122.3 30.9c-3.7-1.8-7.4-3.5-11.3-5C300.6 137.4 248.2 128 192 128c-8.3 0-16.4 .2-24.5 .6l-1.1-.6C142.3 114.6 128 98 128 80c0-44.2 86-80 192-80S512 35.8 512 80zM160.7 161.1c10.2-.7 20.7-1.1 31.3-1.1c62.2 0 117.4 12.3 152.5 31.4C369.3 204.9 384 221.7 384 240c0 4-.7 7.9-2.1 11.7c-4.6 13.2-17 25.3-35 35.5c0 0 0 0 0 0c-.1 .1-.3 .1-.4 .2c0 0 0 0 0 0s0 0 0 0c-.3 .2-.6 .3-.9 .5c-35 19.4-90.8 32-153.6 32c-59.6 0-112.9-11.3-148.2-29.1c-1.9-.9-3.7-1.9-5.5-2.9C14.3 274.6 0 258 0 240c0-34.8 53.4-64.5 128-75.4c10.5-1.5 21.4-2.7 32.7-3.5zM416 240c0-21.9-10.6-39.9-24.1-53.4c28.3-4.4 54.2-11.4 76.2-20.5c16.3-6.8 31.5-15.2 43.9-25.5l0 35.4c0 19.3-16.5 37.1-43.8 50.9c-14.6 7.4-32.4 13.7-52.4 18.5c.1-1.8 .2-3.5 .2-5.3zm-32 96c0 18-14.3 34.6-38.4 48c-1.8 1-3.6 1.9-5.5 2.9C304.9 404.7 251.6 416 192 416c-62.8 0-118.6-12.6-153.6-32C14.3 370.6 0 354 0 336l0-35.4c12.5 10.3 27.6 18.7 43.9 25.5C83.4 342.6 135.8 352 192 352s108.6-9.4 148.1-25.9c7.8-3.2 15.3-6.9 22.4-10.9c6.1-3.4 11.8-7.2 17.2-11.2c1.5-1.1 2.9-2.3 4.3-3.4l0 3.4 0 5.7 0 26.3zm32 0l0-32 0-25.9c19-4.2 36.5-9.5 52.1-16c16.3-6.8 31.5-15.2 43.9-25.5l0 35.4c0 10.5-5 21-14.9 30.9c-16.3 16.3-45 29.7-81.3 38.4c.1-1.7 .2-3.5 .2-5.3zM192 448c56.2 0 108.6-9.4 148.1-25.9c16.3-6.8 31.5-15.2 43.9-25.5l0 35.4c0 44.2-86 80-192 80S0 476.2 0 432l0-35.4c12.5 10.3 27.6 18.7 43.9 25.5C83.4 438.6 135.8 448 192 448z" />
                        </svg>
                        Payable Amount
                    </dt>
                    <dd class="mt-1 text-lg font-bold text-primary font-tertiary">
                        Ksh<span>{{ number_format($engagement->calculatePartialPaymentAmount(), 2) }}</span>
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- Payment Status Card --}}
    <div class="bg-white rounded-2xl shadow-lg border border-neutral-200 overflow-hidden">
        <div class="bg-gradient-to-r from-accent to-accent/80 p-6 border-b border-neutral-200 relative">
            <!-- Abstract visual element for summary -->
            <div class="absolute right-0 bottom-0 transform translate-y-1/3 translate-x-1/6 opacity-20">
                <svg width="120" height="120" viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg">
                    <rect x="30" y="30" width="20" height="60" fill="#FFFFFF" rx="2" />
                    <rect x="60" y="45" width="20" height="45" fill="#FFFFFF" rx="2" />
                    <rect x="90" y="20" width="20" height="70" fill="#FFFFFF" rx="2" />
                </svg>
            </div>
            <div class="flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white mr-2" width="24" height="24"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                <h2 class="font-tertiary font-semibold text-lg text-white">Payment Status</h2>
            </div>
        </div>

        <div class="p-6 space-y-4">
            {{-- Status Alert --}}
            @if ($payment)
                <div
                    class="mb-5 p-4 rounded-lg border-l-4 {{ $payment->status === 'pending' ? 'bg-amber-50 border-accent' : 'bg-green-50 border-secondary' }} shadow-sm">
                    <div class="flex items-start">
                        <div
                            class="{{ $payment->status === 'pending' ? 'text-accent' : 'text-secondary' }} flex-shrink-0 mr-3">
                            @if ($payment->status === 'pending')
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" width="24"
                                    height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" width="24"
                                    height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                </svg>
                            @endif
                        </div>
                        <div class="flex-1">
                            <p
                                class="text-sm font-medium {{ $payment->status === 'pending' ? 'text-amber-800' : 'text-green-800' }}">
                                @if (Auth::user()->id === $engagement->application->poster_id)
                                    @if ($payment->status === 'pending')
                                        Payment processed and awaiting freelancer response.
                                    @elseif($payment->status === 'accepted')
                                        Payment has been accepted by the freelancer.
                                    @elseif($payment->status === 'disputed')
                                        Payment has been disputed by the freelancer.
                                    @elseif($payment->status === 'finalized')
                                        Payment has been finalized.
                                    @endif
                                @elseif(Auth::user()->id === $engagement->application->applicant_id)
                                    @if ($payment->status === 'pending')
                                        Payment has been processed. Please review and respond.
                                    @elseif($payment->status === 'accepted')
                                        You have accepted this payment.
                                    @elseif($payment->status === 'disputed')
                                        You have disputed this payment.
                                    @elseif($payment->status === 'finalized')
                                        This payment has been finalized.
                                    @endif
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Payment Details Card --}}
                <div class="p-5 border border-neutral-200 rounded-lg mb-5 bg-neutral-50">
                    <dl class="grid grid-cols-1 gap-4 text-sm">
                        <div class="p-3 bg-white rounded-lg shadow-sm">
                            <dt class="font-medium text-neutral-500 flex items-center mb-1 text-xs">
                                <svg xmlns="http://www.w3.org/2000/svg" stroke="currentColor" fill="#1e3a8a"
                                    class="h-4 w-4 text-primary mr-2" viewBox="0 0 512 512">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M512 80c0 18-14.3 34.6-38.4 48c-29.1 16.1-72.5 27.5-122.3 30.9c-3.7-1.8-7.4-3.5-11.3-5C300.6 137.4 248.2 128 192 128c-8.3 0-16.4 .2-24.5 .6l-1.1-.6C142.3 114.6 128 98 128 80c0-44.2 86-80 192-80S512 35.8 512 80zM160.7 161.1c10.2-.7 20.7-1.1 31.3-1.1c62.2 0 117.4 12.3 152.5 31.4C369.3 204.9 384 221.7 384 240c0 4-.7 7.9-2.1 11.7c-4.6 13.2-17 25.3-35 35.5c0 0 0 0 0 0c-.1 .1-.3 .1-.4 .2c0 0 0 0 0 0s0 0 0 0c-.3 .2-.6 .3-.9 .5c-35 19.4-90.8 32-153.6 32c-59.6 0-112.9-11.3-148.2-29.1c-1.9-.9-3.7-1.9-5.5-2.9C14.3 274.6 0 258 0 240c0-34.8 53.4-64.5 128-75.4c10.5-1.5 21.4-2.7 32.7-3.5zM416 240c0-21.9-10.6-39.9-24.1-53.4c28.3-4.4 54.2-11.4 76.2-20.5c16.3-6.8 31.5-15.2 43.9-25.5l0 35.4c0 19.3-16.5 37.1-43.8 50.9c-14.6 7.4-32.4 13.7-52.4 18.5c.1-1.8 .2-3.5 .2-5.3zm-32 96c0 18-14.3 34.6-38.4 48c-1.8 1-3.6 1.9-5.5 2.9C304.9 404.7 251.6 416 192 416c-62.8 0-118.6-12.6-153.6-32C14.3 370.6 0 354 0 336l0-35.4c12.5 10.3 27.6 18.7 43.9 25.5C83.4 342.6 135.8 352 192 352s108.6-9.4 148.1-25.9c7.8-3.2 15.3-6.9 22.4-10.9c6.1-3.4 11.8-7.2 17.2-11.2c1.5-1.1 2.9-2.3 4.3-3.4l0 3.4 0 5.7 0 26.3zm32 0l0-32 0-25.9c19-4.2 36.5-9.5 52.1-16c16.3-6.8 31.5-15.2 43.9-25.5l0 35.4c0 10.5-5 21-14.9 30.9c-16.3 16.3-45 29.7-81.3 38.4c.1-1.7 .2-3.5 .2-5.3zM192 448c56.2 0 108.6-9.4 148.1-25.9c16.3-6.8 31.5-15.2 43.9-25.5l0 35.4c0 44.2-86 80-192 80S0 476.2 0 432l0-35.4c12.5 10.3 27.6 18.7 43.9 25.5C83.4 438.6 135.8 448 192 448z" />
                                </svg>
                                Amount
                            </dt>
                            <dd class="text-neutral-900">Ksh{{ number_format($payment->amount, 2) }}</dd>
                        </div>
                        <div class="p-3 bg-white rounded-lg shadow-sm">
                            <dt class="font-medium text-neutral-500 flex items-center mb-1 text-xs">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-primary mr-1"
                                    width="24" height="24" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                Status
                            </dt>
                            <dd class="text-neutral-900">
                                @switch($payment->status)
                                    @case('pending')
                                        <span
                                            class="px-2 py-1 bg-amber-100 text-amber-800 rounded-full text-xs font-medium inline-flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" width="24"
                                                height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <polyline points="12 6 12 12 16 14"></polyline>
                                            </svg>
                                            Pending
                                        </span>
                                    @break

                                    @case('accepted')
                                        <span
                                            class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium inline-flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" width="24"
                                                height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                            </svg>
                                            Accepted
                                        </span>
                                    @break

                                    @case('disputed')
                                        <span
                                            class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs font-medium inline-flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" width="24"
                                                height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path
                                                    d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z">
                                                </path>
                                                <line x1="12" y1="9" x2="12" y2="13"></line>
                                                <line x1="12" y1="17" x2="12.01" y2="17"></line>
                                            </svg>
                                            Disputed
                                        </span>
                                    @break

                                    @case('finalized')
                                        <span
                                            class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium inline-flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" width="24"
                                                height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                            </svg>
                                            Finalized
                                        </span>
                                    @break

                                    @default
                                        <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded-full text-xs font-medium">
                                            {{ $payment->status }}
                                        </span>
                                @endswitch
                            </dd>
                        </div>
                        <div class="p-3 bg-white rounded-lg shadow-sm">
                            <dt class="font-medium text-neutral-500 flex items-center mb-1 text-xs">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-primary mr-1"
                                    width="24" height="24" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2">
                                    </rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                                Processed Date
                            </dt>
                            <dd class="text-neutral-900">
                                {{ $payment->processed_at ? date('M d, Y', strtotime($payment->processed_at)) : 'Not processed' }}
                            </dd>
                        </div>
                        @if ($payment->accepted_at)
                            <div class="p-3 bg-white rounded-lg shadow-sm">
                                <dt class="font-medium text-neutral-500 flex items-center mb-1 text-xs">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-primary mr-1"
                                        width="24" height="24" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <rect x="3" y="4" width="18" height="18" rx="2"
                                            ry="2">
                                        </rect>
                                        <line x1="16" y1="2" x2="16" y2="6"></line>
                                        <line x1="8" y1="2" x2="8" y2="6"></line>
                                        <line x1="3" y1="10" x2="21" y2="10"></line>
                                    </svg>
                                    Accepted Date
                                </dt>
                                <dd class="text-neutral-900">
                                    {{ $payment->accepted_at ? date('M d, Y', strtotime($payment->accepted_at)) : 'Pending' }}
                                </dd>
                            </div>
                        @endif
                    </dl>
                </div>

                {{-- Freelancer Actions --}}
                @if (Auth::user()->id === $engagement->application->applicant_id && $payment->status === 'pending')
                    <div class="mt-6">
                        <div class="flex flex-col sm:flex-row gap-2">
                            <form action="{{ route('engagements.accept-partial-payment', $payment->id) }}"
                                method="POST" class="">
                                @csrf
                                <button type="submit"
                                    class="w-full py-2.5 px-4 bg-secondary hover:bg-secondary/90 text-white font-medium rounded-lg text-sm transition-colors duration-200 flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" width="24"
                                        height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                    </svg>
                                    Accept Payment
                                </button>
                            </form>
                            <button type="button" onclick="openDisputeModal()"
                                class="w-full py-2.5 px-4 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg text-sm transition-colors duration-200 flex items-center justify-center flex-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" width="24"
                                    height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z">
                                    </path>
                                    <line x1="12" y1="9" x2="12" y2="13"></line>
                                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                                </svg>
                                Dispute Payment
                            </button>
                        </div>
                    </div>
                @endif
            @else
                {{-- No payment exists yet --}}
                @if ($canProcess)
                    <div class="mb-5 p-4 bg-green-50 border border-green-200 rounded-lg shadow-sm">
                        <div class="flex">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-secondary mr-2 flex-shrink-0"
                                width="24" height="24" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                            <p class="text-sm text-green-700">{{ $paymentInfo['message'] }}</p>
                        </div>
                    </div>

                    @if (Auth::user()->id === $engagement->application->poster_id || Auth::user()->hasRole('admin'))
                        <form action="{{ route('engagements.process-partial-payment', $engagement->id) }}"
                            method="POST">
                            @csrf
                            <button type="submit"
                                class="w-full py-2.5 px-4 bg-primary hover:bg-primary/90 text-white font-medium rounded-lg text-sm transition-colors duration-200 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="h-5 w-5 mr-2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15.042 21.672 13.684 16.6m0 0-2.51 2.225.569-9.47 5.227 7.917-3.286-.672Zm-7.518-.267A8.25 8.25 0 1 1 20.25 10.5M8.288 14.212A5.25 5.25 0 1 1 17.25 10.5" />
                                </svg>
                                Process Payment
                            </button>
                        </form>
                    @elseif(Auth::user()->id === $engagement->application->applicant_id)
                        <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg shadow-sm">
                            <div class="flex">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-accent mr-2 flex-shrink-0"
                                    width="24" height="24" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                <p class="text-sm text-amber-700">Payment has not been processed by the client yet.</p>
                            </div>
                        </div>
                    @endif
                @else
                    <div class="mb-4 p-4 bg-amber-50 border border-amber-200 rounded-lg shadow-sm">
                        <div class="flex">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-accent mr-2 flex-shrink-0"
                                width="24" height="24" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z">
                                </path>
                                <line x1="12" y1="9" x2="12" y2="13"></line>
                                <line x1="12" y1="17" x2="12.01" y2="17"></line>
                            </svg>
                            <p class="text-sm text-amber-700">{{ $paymentInfo['message'] }}</p>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
