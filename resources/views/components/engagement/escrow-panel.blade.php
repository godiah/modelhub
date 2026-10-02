@props(['engagement'])

{{--
    What the client paid into a job's escrow and where it has gone, for both sides, with what happens to the rest once a job is cancelled.
    Shown only for a job that was funded through the platform.
--}}
@php
    use App\Support\Money;

    $escrow = app(\App\Services\Payments\EscrowService::class)->summary($engagement);
    $isClient = auth()->id() === $engagement->application->poster_id;
    $funding = $escrow['funded'] ? $engagement->payments()->where('purpose', 'escrow')->where('status', \App\Enums\PaymentStatus::Succeeded)->latest('id')->first() : null;
    $rows = array_filter([
        [__('Paid in'), $escrow['amount'], null],
        [$isClient ? __('Released to the freelancer') : __('Paid to you'), $escrow['released_net'], null],
        [__('Platform service fee'), $escrow['released_fee'], null],
        $escrow['refunded'] > 0 ? [$isClient ? __('Returned to you') : __('Returned to the client'), $escrow['refunded'], null] : null,
    ]);
@endphp

@if ($escrow['funded'])
    <x-panel :title="__('Escrow')" :description="$funding?->completed_at ? __('Funded :date', ['date' => $funding->completed_at->format('M j, Y')]).($funding->receipt ? ' · '.__('M-Pesa receipt :r', ['r' => $funding->receipt]) : '') : null">
        <dl class="space-y-2.5 text-sm">
            @foreach ($rows as [$label, $amount])
                <div class="flex items-center justify-between gap-4"><dt class="text-tertiary">{{ $label }}</dt><dd class="font-medium tabular-nums text-neutral-900">{{ Money::formatMinor($amount) }}</dd></div>
            @endforeach
            <div class="flex items-center justify-between gap-4 border-t border-neutral-100 pt-2.5"><dt class="font-medium text-neutral-800">{{ __('Still in escrow') }}</dt><dd class="font-semibold tabular-nums text-neutral-900">{{ Money::formatMinor($escrow['remaining']) }}</dd></div>
        </dl>

        @if ($escrow['state'] === 'waiting')
            <p class="mt-4 rounded-xl bg-neutral-50 px-3 py-2.5 text-sm text-neutral-700">
                {{ $isClient
                    ? __('You can still pay for work that was not approved until :date. After that the rest, :amount, goes back to you.', ['date' => $escrow['refund_due_at']->format('M j, Y'), 'amount' => Money::formatMinor($escrow['remaining'])])
                    : __('The client has until :date to pay for any work that was not approved. After that the rest goes back to them.', ['date' => $escrow['refund_due_at']->format('M j, Y')]) }}
            </p>
        @elseif ($escrow['state'] === 'due')
            <p class="mt-4 rounded-xl bg-amber-50 px-3 py-2.5 text-sm text-amber-900">
                {{ $isClient
                    ? __(':amount is being returned to you, by M-Pesa to the number you paid from.', ['amount' => Money::formatMinor($escrow['remaining'])])
                    : __('The rest of the escrow, :amount, is being returned to the client.', ['amount' => Money::formatMinor($escrow['remaining'])]) }}
            </p>
        @elseif ($escrow['state'] === 'held' && in_array($engagement->status->value, ['cancelled', 'disputed'], true))
            <p class="mt-4 rounded-xl bg-neutral-50 px-3 py-2.5 text-sm text-neutral-700">{{ __('A payment or dispute is still open, so the rest stays in escrow until it is settled.') }}</p>
        @endif
    </x-panel>
@endif
