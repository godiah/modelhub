@use('App\Support\Money')
@use('App\Support\Phone')
@use('App\Support\Staff\Masking')
@php
    use App\Enums\PaymentStatus;
    $isReview = $payment->status === PaymentStatus::Review;
    $isRefunded = $payment->status === PaymentStatus::Refunded;
    $received = $payment->received_minor ?? $payment->amount_minor;
    $afterRelease = $payment->released_at !== null;
    // Only a payment that became a sale was split between the seller and the platform
    $hasSplit = $payment->status === PaymentStatus::Succeeded || ($isRefunded && $payment->purchase_id !== null);
@endphp
<x-staff-layout :title="$payment->reference">
    <div class="container mx-auto max-w-7xl space-y-6 px-4 py-8" x-data="{ refunding: false, reason: '' }">
        <a href="{{ route('admin.payments.index') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm font-medium text-teal-700 hover:underline"><x-icon name="arrow-left" class="h-4 w-4" />{{ __('All payments') }}</a>

        <x-staff.header class="!mb-0" :title="$payment->product?->title ?? __('A deleted model')">
            <x-slot:badges><x-badge :tone="$payment->status->tone()" class="px-2.5 py-0.5 text-xs font-medium tracking-normal">{{ __($payment->status->label()) }}</x-badge></x-slot:badges>
            <span class="font-mono">{{ $payment->reference }}</span> · {{ __(':tier licence', ['tier' => $payment->tier->label()]) }} · {{ $payment->created_at->format('F j, Y · g:i A') }}
        </x-staff.header>

        @if ($isReview)
            <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900" role="alert">
                <p class="font-semibold">{{ __('This payment arrived but could not become a licence.') }}</p>
                <p class="mt-1">{{ $payment->failure_reason }} {{ __(':amount is held as unallocated money until it is refunded.', ['amount' => Money::formatMinor($received)]) }}</p>
            </div>
        @endif

        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <x-panel :title="__('Payment')">
                    <dl class="grid gap-x-8 gap-y-4 text-sm sm:grid-cols-2">
                        <div><dt class="text-xs text-tertiary">{{ __('Buyer') }}</dt><dd class="mt-0.5 font-medium text-neutral-900">@can('view members')<a href="{{ route('admin.members.show', $payment->buyer) }}" class="hover:text-teal-700 hover:underline">{{ $payment->buyer->name }}</a>@else{{ $payment->buyer->name }}@endcan</dd></div>
                        <div><dt class="text-xs text-tertiary">{{ __('Seller') }}</dt><dd class="mt-0.5 font-medium text-neutral-900">{{ $payment->seller->name }}</dd></div>
                        <div><dt class="text-xs text-tertiary">{{ __('Asked for') }}</dt><dd class="mt-0.5 font-medium tabular-nums text-neutral-900">{{ Money::formatMinor($payment->amount_minor) }}</dd></div>
                        <div><dt class="text-xs text-tertiary">{{ __('Arrived') }}</dt><dd class="mt-0.5 font-medium tabular-nums text-neutral-900">{{ $payment->received_minor !== null ? Money::formatMinor($payment->received_minor) : '—' }}</dd></div>
                        <div><dt class="text-xs text-tertiary">{{ __('Paid from') }}</dt><dd class="mt-0.5 font-medium text-neutral-900">{{ $canRefund ? Phone::local($payment->msisdn) : Masking::phone($payment->msisdn, false) }}</dd></div>
                        <div><dt class="text-xs text-tertiary">{{ __('M-Pesa receipt') }}</dt><dd class="mt-0.5 font-mono text-neutral-900">{{ $payment->receipt ?? '—' }}</dd></div>
                        @if ($hasSplit)
                        <div><dt class="text-xs text-tertiary">{{ __('Commission') }}</dt><dd class="mt-0.5 tabular-nums text-neutral-900">{{ Money::formatMinor($payment->commission_minor) }} <span class="text-tertiary">({{ rtrim(rtrim(number_format($payment->commission_rate * 100, 2), '0'), '.') }}%)</span></dd></div>
                        <div><dt class="text-xs text-tertiary">{{ __('Seller\'s share') }}</dt><dd class="mt-0.5 tabular-nums text-neutral-900">{{ Money::formatMinor($payment->seller_share_minor) }}@if ($payment->status === PaymentStatus::Succeeded || $isRefunded) <span class="text-tertiary">· {{ $afterRelease ? __('released :date', ['date' => $payment->released_at->format('M j')]) : ($payment->release_at ? __('held until :date', ['date' => $payment->release_at->format('M j')]) : '') }}</span>@endif</dd></div>
                        @endif
                        <div><dt class="text-xs text-tertiary">{{ __('Gateway reference') }}</dt><dd class="mt-0.5 break-all font-mono text-xs text-neutral-700">{{ $payment->gateway_reference ?? '—' }} <span class="font-sans text-tertiary">({{ $payment->gateway }})</span></dd></div>
                        <div><dt class="text-xs text-tertiary">{{ __('Settled') }}</dt><dd class="mt-0.5 text-neutral-900">{{ $payment->completed_at?->format('M j, Y · g:i A') ?? '—' }}</dd></div>
                    </dl>
                    @if ($payment->failure_reason && ! $isReview)<p class="mt-4 rounded-lg bg-neutral-50 px-3 py-2 text-sm text-neutral-700">{{ $payment->failure_reason }}</p>@endif
                </x-panel>

                <x-panel :title="__('In the ledger')" :description="__('The postings this payment made. Nothing here can be edited: a refund adds an opposite posting.')" flush>
                    @forelse ($postings as $posting)
                        <div class="border-b border-neutral-100 px-6 py-4 last:border-b-0">
                            <p class="flex flex-wrap items-baseline justify-between gap-2 text-sm"><a href="{{ route('admin.ledger.show', $posting) }}" wire:navigate class="font-semibold text-neutral-900 hover:text-teal-700 hover:underline">{{ $posting->description }}</a><span class="text-xs text-tertiary">{{ $posting->occurred_at->format('M j, g:i A') }}</span></p>
                            <ul class="mt-2 space-y-1 text-sm">
                                @foreach ($posting->entries as $entry)
                                    <li class="flex justify-between gap-3"><span class="text-neutral-700"><span class="inline-block w-14 text-xs font-medium uppercase {{ $entry->direction === 'debit' ? 'text-teal-700' : 'text-amber-700' }}">{{ $entry->direction }}</span>{{ $entry->account->name }}</span><span class="tabular-nums text-neutral-900">{{ Money::formatMinor($entry->amount_minor) }}</span></li>
                                @endforeach
                            </ul>
                        </div>
                    @empty
                        <p class="px-6 py-8 text-center text-sm text-tertiary">{{ __('Nothing was posted: no money arrived.') }}</p>
                    @endforelse
                </x-panel>
            </div>

            <div class="space-y-6">
                <x-panel :title="__('Licence')">
                    @if ($licence)
                        <p class="font-mono text-sm text-neutral-900">{{ $licence->key }}</p>
                        <p class="mt-1 text-sm text-tertiary">{{ __(':tier licence', ['tier' => $licence->tier->label()]) }} · {{ $licence->isActive() ? __('active') : __('ended :date', ['date' => $licence->revoked_at->format('M j')]) }}</p>
                        <p class="mt-3 text-sm text-neutral-700">{{ trans_choice(':count file download|:count file downloads', $downloads, ['count' => $downloads]) }}</p>
                        @if ($downloads > 0 && ! $isRefunded)<p class="mt-1 text-xs text-amber-800">{{ __('Files were downloaded. A refund is for a broken file or one that is not as described.') }}</p>@endif
                    @else
                        <p class="text-sm text-tertiary">{{ __('No licence was issued for this payment.') }}</p>
                    @endif
                </x-panel>

                @if ($isRefunded)
                    <x-panel :title="__('Refunded')">
                        <p class="text-sm text-neutral-700">{{ __('Refunded :date by :name.', ['date' => $payment->refunded_at->format('M j, Y'), 'name' => $payment->refunder?->name ?? __('staff')]) }}</p>
                        <p class="mt-2 rounded-lg bg-neutral-50 px-3 py-2 text-sm text-neutral-700">{{ $payment->refund_reason }}</p>
                    </x-panel>
                @elseif ($canRefund && $refundable)
                    <x-panel :title="__('Refund')" :description="$isReview ? __('Return the parked money to the buyer.') : __('End the licence and give the buyer their money back.')">
                        <ul class="space-y-2 text-sm text-neutral-700">
                            @if ($isReview)
                                <li>{{ __(':amount is taken out of the unallocated account.', ['amount' => Money::formatMinor($received)]) }}</li>
                            @else
                                <li>{{ __('The licence ends and the files can no longer be downloaded.') }}</li>
                                <li>{{ __(':share is taken back from the seller\'s :where earnings, and the :commission commission is reversed.', ['share' => Money::formatMinor($payment->seller_share_minor), 'where' => $afterRelease ? __('available') : __('pending'), 'commission' => Money::formatMinor($payment->commission_minor)]) }}</li>
                                @if ($afterRelease)<li class="text-amber-800">{{ __('The hold has ended, so this only works if the seller has not withdrawn the money.') }}</li>@endif
                            @endif
                            <li>{{ __('Send the money back to the buyer from the M-Pesa portal (reverse receipt :receipt). Recording the refund here keeps the books right.', ['receipt' => $payment->receipt ?? '—']) }}</li>
                        </ul>
                        <x-btn type="button" variant="danger-outline" class="mt-4" @click="refunding = true">{{ __('Record a refund') }}</x-btn>
                    </x-panel>
                @endif
            </div>
        </div>

        @if ($canRefund && $refundable && ! $isRefunded)
            <x-confirm-dialog bind="refunding" title="Record this refund" confirm-label="Refund" state="reason: ''" disabledWhen="reason.trim().length < 5" :action="route('admin.payments.refund', $payment)" message="This cannot be undone. The buyer and, for a sale, the seller are told.">
                <label for="refund-reason" class="sr-only">{{ __('Reason') }}</label>
                <textarea id="refund-reason" name="reason" x-model="reason" rows="3" maxlength="255" required placeholder="{{ __('Why it is being refunded (the buyer is shown this)') }}" class="mt-3 block w-full resize-none rounded-xl border border-neutral-300 px-3 py-2 text-sm focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25"></textarea>
            </x-confirm-dialog>
        @endif
    </div>
</x-staff-layout>
