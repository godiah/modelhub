@if ($engagement->partialPayments->count() > 0)
    <div class="relative bg-gradient-to-br from-neutral-50 to-neutral-100 rounded-2xl shadow-lg overflow-hidden">
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" viewBox="0 0 400 200" fill="currentColor">
                <defs>
                    <pattern id="hexagons" x="0" y="0" width="30" height="26" patternUnits="userSpaceOnUse">
                        <polygon points="15,2 25,8 25,18 15,24 5,18 5,8" fill="none" stroke="currentColor"
                            stroke-width="0.5" />
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#hexagons)" />
            </svg>
        </div>

        <!-- Header -->
        <div class="relative px-8 py-6 border-b border-neutral-200 bg-gradient-to-r from-primary to-primary/90">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-white/20 backdrop-blur-sm rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-white font-main">Partial Payment</h2>
            </div>
        </div>

        <!-- Payment List -->
        <div class="px-6 py-4 space-y-4">
            @foreach ($engagement->partialPayments as $payment)
                <div
                    class="relative bg-white rounded-lg border border-neutral-200 group hover:shadow-md transition-all duration-200">
                    <!-- Subtle Gradient Border Effect -->
                    <div
                        class="absolute inset-0 rounded-lg bg-gradient-to-r from-secondary/20 to-primary/20 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                    </div>
                    <div class="relative p-4 flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-2">
                                <span
                                    class="text-lg font-medium text-neutral-900 font-main">Ksh{{ number_format($payment->amount, 2) }}</span>
                                @if ($payment->final_amount && $payment->final_amount != $payment->amount)
                                    <span class="text-sm text-neutral-500 font-secondary">(Final:
                                        Ksh{{ number_format($payment->final_amount, 2) }})</span>
                                @endif
                            </div>
                            @if ($payment->notes)
                                <p class="text-sm text-neutral-600 mt-1 font-secondary">{{ $payment->notes }}</p>
                            @endif
                            <div class="flex items-center mt-2 text-xs text-neutral-500 font-secondary space-x-4">
                                <span>Processed: {{ $payment->processed_at->format('M j, Y g:i A') }}</span>
                                @if ($payment->accepted_at)
                                    <span>Accepted: {{ $payment->accepted_at->format('M j, Y g:i A') }}</span>
                                @endif
                            </div>
                        </div>
                        <span
                            class="ml-3 flex items-center px-3 py-1 text-xs font-medium font-tertiary rounded-full
                                @switch($payment->status)
                                    @case('pending')
                                        bg-gradient-to-r from-accent/20 to-accent/40 text-accent
                                        @break
                                    @case('accepted')
                                        bg-gradient-to-r from-green-100 to-green-200 text-green-800
                                        @break
                                    @case('disputed')
                                        bg-gradient-to-r from-red-100 to-red-200 text-red-800
                                        @break
                                    @case('finalized')
                                        bg-gradient-to-r from-blue-100 to-blue-200 text-blue-800
                                        @break
                                    @default
                                        bg-gradient-to-r from-neutral-100 to-neutral-200 text-neutral-800
                                @endswitch
                            ">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                @switch($payment->status)
                                    @case('pending')
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v4a1 1 0 001 1h4a1 1 0 100-2h-3V7z"
                                            clip-rule="evenodd" />
                                    @break

                                    @case('accepted')
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                            clip-rule="evenodd" />
                                    @break

                                    @case('disputed')
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                            clip-rule="evenodd" />
                                    @break

                                    @case('finalized')
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                            clip-rule="evenodd" />
                                    @break

                                    @default
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v4a1 1 0 001 1h4a1 1 0 100-2h-3V7z"
                                            clip-rule="evenodd" />
                                @endswitch
                            </svg>
                            {{ ucfirst($payment->status) }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif
