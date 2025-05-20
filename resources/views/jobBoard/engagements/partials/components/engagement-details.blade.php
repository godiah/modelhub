<!-- Engagement Details Row -->
<div
    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 p-4 mx-auto bg-gradient-to-r from-neutral-50 to-neutral-100 border border-neutral-200">
    <!-- Started Date -->
    <div class="flex items-center space-x-3 bg-white p-3 rounded-lg shadow-sm border border-neutral-100">
        <div class="flex-shrink-0 h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
        </div>
        <div>
            <p class="text-xs text-neutral-500 font-main">Started on</p>
            <p class="font-medium text-neutral-800">
                {{ $engagement->started_at ? $engagement->started_at->format('M d, Y') : 'Not started yet' }}
            </p>
        </div>
    </div>

    <!-- Agreed Amount -->
    <div class="flex items-center space-x-3 bg-white p-3 rounded-lg shadow-sm border border-neutral-100">
        <div class="flex-shrink-0 h-10 w-10 rounded-full bg-secondary/10 flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="h-5 w-5 text-secondary">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
            </svg>
        </div>
        <div>
            <p class="text-xs text-neutral-500 font-main">Agreed amount</p>
            <p class="font-medium text-neutral-800">
                Ksh{{ number_format($engagement->net_amount, 2) }}</p>
        </div>
    </div>

    <!-- Payment Status -->
    <div class="flex items-center space-x-3 bg-white p-3 rounded-lg shadow-sm border border-neutral-100">
        <div
            class="flex-shrink-0 h-10 w-10 rounded-full 
                                    @if ($engagement->isPaymentEscrowed()) bg-secondary/10 
                                    @elseif($engagement->payment_released_at) 
                                        bg-green-100 
                                    @else 
                                        bg-neutral-200 @endif
                                    flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5 
                                        @if ($engagement->isPaymentEscrowed()) text-secondary 
                                        @elseif($engagement->payment_released_at) 
                                            text-green-600 
                                        @else 
                                        text-neutral-500 @endif"
                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <div>
            <p class="text-xs text-neutral-500 font-main">Payment status</p>
            <p
                class="font-medium 
                                        @if ($engagement->isPaymentEscrowed()) text-secondary 
                                        @elseif($engagement->payment_released_at) 
                                            text-green-600 
                                        @else 
                                            text-neutral-600 @endif">
                @if ($engagement->isPaymentEscrowed())
                    Escrowed
                @elseif($engagement->payment_released_at)
                    Released
                @else
                    Not processed
                @endif
            </p>
        </div>
    </div>

    <!-- Message Action -->
    <div class="flex items-center space-x-3 bg-white p-3 rounded-lg shadow-sm border border-neutral-100">
        <div class="flex-shrink-0 h-10 w-10 rounded-full bg-accent/10 flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-accent" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-4l-4 4z" />
            </svg>
        </div>
        <div>
            <p class="text-xs text-neutral-500 font-main">Communication</p>
            @if ($engagement->status === 'active')
                <button
                    class="mt-1 inline-flex items-center px-3 py-1 text-xs font-medium rounded-full text-accent bg-accent/10 hover:bg-accent/20 transition-colors border border-accent/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Message
                </button>
            @else
                <p class="font-medium text-neutral-600">Not available</p>
            @endif
        </div>
    </div>
</div>
