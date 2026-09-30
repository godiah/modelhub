@use('App\Enums\EngagementStatus')
<!-- Engagement Details Row -->
<div
    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 p-4 mx-auto bg-gradient-to-r from-neutral-50 to-neutral-100 border border-neutral-200">
    <!-- Started Date -->
    <div class="flex items-center space-x-3 bg-white p-3 rounded-lg shadow-sm border border-neutral-100">
        <div class="flex-shrink-0 h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center">
            <x-icon name="calendar" class="h-5 w-5 text-primary" />
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
            <x-icon name="banknotes" class="h-5 w-5 text-secondary" stroke-width="1.5" />
        </div>
        <div>
            <p class="text-xs text-neutral-500 font-main">Agreed amount</p>
            <p class="font-medium text-neutral-800">
                <x-money :amount="$engagement->net_amount" /></p>
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
            @if ($engagement->status === EngagementStatus::Active || $engagement->status === EngagementStatus::Cancelled)
                <button @click="$dispatch('open-message-modal', { engagementId: {{ $engagement->id }} })"
                    class="relative mt-1 inline-flex items-center px-3 py-1 text-xs font-medium rounded-full text-accent bg-accent/10 hover:bg-accent/20 transition-colors border border-accent/20">
                    <x-icon name="plus-2" class="h-3 w-3 mr-1" />
                    <span class="font-secondary">Message</span>
                    @php
                        $unreadCount = $engagement
                            ->messages()
                            ->where('sender_id', '!=', auth()->id())
                            ->whereNull('read_at')
                            ->count();
                    @endphp
                    @if ($unreadCount > 0)
                        <span
                            class="absolute -top-1 -right-2 bg-accent font-secondary text-white text-xs w-5 h-5 flex items-center justify-center rounded-full shadow-sm"
                            id="unread-count-{{ $engagement->id }}">{{ $unreadCount }}</span>
                    @endif
                </button>
            @else
                <p class="font-medium text-neutral-600">Not available</p>
            @endif
        </div>
    </div>
</div>
