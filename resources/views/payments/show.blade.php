@php
    use App\Enums\PaymentStatus;
    use App\Support\Money;

    $tier = $payment->tier;
    $done = $payment->status->isFinal();
    $secondsLeft = max(0, now()->diffInSeconds($payment->expires_at, false));
@endphp
<x-app-layout title="Payment">
    <div class="container mx-auto max-w-lg px-4 py-10"
        x-data="{
            status: @js($payment->status->value), message: @js($payment->failure_reason), left: {{ (int) $secondsLeft }}, timer: null, poll: null,
            get pending() { return this.status === 'pending'; },
            tick() { this.left = Math.max(0, this.left - 1); },
            check() {
                fetch(@js(route('payments.status', $payment)), { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                    .then(r => r.ok ? r.json() : null)
                    .then(d => { if (! d) return; this.status = d.status; this.message = d.message; if (d.redirect) { window.location = d.redirect; } if (d.final) { this.stop(); } })
                    .catch(() => {});
            },
            stop() { clearInterval(this.timer); clearInterval(this.poll); },
            init() { if (this.pending) { this.timer = setInterval(() => this.tick(), 1000); this.poll = setInterval(() => this.check(), 3000); } },
        }">
        <x-card class="p-6 text-center sm:p-8">
            <p class="text-xs font-medium uppercase tracking-wide text-tertiary">{{ $payment->isEscrow() ? __('Fund the job') : __('Pay for') }}</p>
            <h1 class="mt-1 font-tertiary text-xl font-semibold text-neutral-900">{{ $payment->isEscrow() ? $payment->engagement?->application?->job?->title : ($payment->product?->title ?? __('A model')) }}</h1>
            <p class="mt-1 text-sm text-tertiary">@if ($payment->isEscrow()){{ __('Held in escrow until you approve the work') }}@else{{ __(':tier licence', ['tier' => $tier->label()]) }}@endif · <span class="font-semibold tabular-nums text-neutral-900">{{ Money::formatMinor($payment->amount_minor) }}</span></p>

            {{-- Waiting for the PIN --}}
            <div x-show="pending" @unless ($payment->isPending()) x-cloak @endunless class="mt-8" role="status" aria-live="polite">
                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-teal-50 text-teal-700"><x-icon name="phone" class="h-7 w-7" /></span>
                <p class="mt-5 font-semibold text-neutral-900">{{ __('Check your phone') }}</p>
                <p class="mt-1 text-sm text-tertiary">{{ __('We sent an M-Pesa prompt to :phone. Enter your PIN to pay.', ['phone' => \App\Support\Phone::local($payment->msisdn)]) }}</p>
                <p class="mt-4 text-xs text-tertiary">{{ __('This page updates by itself. The prompt expires in') }} <span class="font-medium tabular-nums text-neutral-700" x-text="Math.floor(left / 60) + ':' + String(left % 60).padStart(2, '0')"></span></p>
            </div>

            {{-- It did not go through --}}
            <div x-show="['failed', 'cancelled', 'expired'].includes(status)" @unless (in_array($payment->status, [PaymentStatus::Failed, PaymentStatus::Cancelled, PaymentStatus::Expired], true)) x-cloak @endunless class="mt-8" role="alert">
                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-red-50 text-red-600"><x-icon name="x-circle-solid" class="h-7 w-7" /></span>
                <p class="mt-5 font-semibold text-neutral-900">{{ __('The payment did not go through') }}</p>
                <p class="mt-1 text-sm text-tertiary" x-text="message || @js(__('Nothing was charged.'))">{{ $payment->failure_reason ?? __('Nothing was charged.') }}</p>
                @if ($payment->isEscrow())<x-btn class="mt-6" :href="route('engagements.show', $payment->engagement_id)">{{ __('Try again') }}</x-btn>@elseif ($payment->product)<x-btn class="mt-6" :href="route('models.show', $payment->product)">{{ __('Try again') }}</x-btn>@endif
            </div>

            {{-- Money arrived but needs a person to look at it --}}
            <div x-show="status === 'review'" @unless ($payment->status === PaymentStatus::Review) x-cloak @endunless class="mt-8" role="alert">
                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-amber-50 text-amber-600"><x-icon name="exclamation-triangle" class="h-7 w-7" /></span>
                <p class="mt-5 font-semibold text-neutral-900">{{ __('Your payment arrived, but we need to check it') }}</p>
                <p class="mt-1 text-sm text-tertiary">{{ __('We have your payment and will sort it out, or refund it. Please keep this reference:') }} <span class="font-mono font-medium text-neutral-800">{{ $payment->reference }}</span>@if ($payment->receipt) · {{ __('M-Pesa receipt') }} <span class="font-mono font-medium text-neutral-800">{{ $payment->receipt }}</span>@endif</p>
            </div>
        </x-card>

        <p class="mt-4 text-center text-xs text-tertiary">{{ __('Payment reference') }} <span class="font-mono">{{ $payment->reference }}</span> · <a href="{{ route('policies.payments') }}" class="font-medium text-teal-700 hover:underline">{{ __('How payments work') }}</a></p>
    </div>
</x-app-layout>
