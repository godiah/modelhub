@php
    use App\Enums\PayoutStatus;
    use App\Support\Money;
    use App\Support\Phone;
    use App\Support\Settings\FeePolicy;

    $min = FeePolicy::minPayoutMinor();
    $fee = FeePolicy::payoutFeeMinor();
    $available = $summary['available'];
    $canWithdraw = ! $open && $available >= $min;
    $phone = old('phone', auth()->user()->profile?->telephone_number);
@endphp
<x-app-layout title="Earnings">
    <div class="container mx-auto max-w-7xl space-y-6 px-4 py-8">
        <div>
            <h1 class="font-tertiary text-2xl font-bold tracking-tight text-neutral-900 sm:text-3xl">{{ __('Earnings') }}</h1>
            <p class="mt-1 max-w-2xl text-sm text-tertiary">{{ $showSales ? __('What your models and your jobs have earned, after the platform\'s commission and service fee. A sale\'s share is held for a few days before you can withdraw it; what a job pays you is yours as soon as the client approves the work.') : __('What your jobs have paid you, after the platform\'s service fee. It is yours to withdraw as soon as the client approves the work.') }}</p>
        </div>

        <section aria-label="{{ __('Balances') }}" class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <x-stat-tile :label="__('Available to withdraw')" :value="Money::formatMinor($summary['available'])" :hint="__('Ready to take out')" icon="banknotes" />
            <x-stat-tile :label="__('In the hold period')" :value="Money::formatMinor($summary['pending'])" :hint="__('Becomes available soon')" icon="clock" tone="amber" />
            <x-stat-tile :label="__('Withdrawn')" :value="Money::formatMinor($summary['withdrawn'])" :hint="$summary['in_progress'] > 0 ? __(':amount on its way', ['amount' => Money::formatMinor($summary['in_progress'])]) : __('Sent to your M-Pesa')" icon="cloud-arrow-up" />
            <x-stat-tile :label="__('Earned in total')" :value="Money::formatMinor($summary['earned'])" :hint="__('Your share of every sale and job')" icon="cash" />
        </section>

        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                @if ($showSales)
                <x-panel :title="__('Your sales')" flush>
                    @if ($sales->isEmpty())
                        <x-empty-state icon="banknotes" :title="__('No sales yet')" :description="__('When someone buys one of your models, your share appears here.')" />
                    @else
                        <ul class="divide-y divide-neutral-100">
                            @foreach ($sales as $sale)
                                <li class="flex flex-wrap items-center gap-x-4 gap-y-1 px-5 py-3.5">
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-semibold text-neutral-900">{{ $sale->product?->title ?? __('A deleted model') }}</p>
                                        <p class="text-xs text-tertiary">{{ __(':tier licence', ['tier' => $sale->tier->label()]) }} · {{ $sale->completed_at->format('M j, Y') }} · {{ __('sold for :amount', ['amount' => Money::formatMinor($sale->amount_minor)]) }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-semibold tabular-nums text-neutral-900">{{ Money::formatMinor($sale->seller_share_minor) }}</p>
                                        <p class="text-xs {{ $sale->released_at ? 'text-green-700' : 'text-amber-700' }}">{{ $sale->released_at ? __('Available') : __('In hold until :date', ['date' => $sale->release_at->format('M j')]) }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                        <x-pager :paginator="$sales" footer />
                    @endif
                </x-panel>
                @endif

                <x-panel :title="__('Job payments')" :description="__('Paid into your balance when a client approves your work.')" flush>
                    @if ($jobPayments->isEmpty())
                        <x-empty-state icon="briefcase" :title="__('No job payments yet')" :description="__('When a client approves a deliverable on a funded job, your share appears here.')" />
                    @else
                        <ul class="divide-y divide-neutral-100">
                            @foreach ($jobPayments as $row)
                                <li class="flex flex-wrap items-center gap-x-4 gap-y-1 px-5 py-3.5">
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-semibold text-neutral-900">@if ($row->engagement_id)<a href="{{ route('engagements.show', $row->engagement_id) }}" class="hover:text-teal-700 hover:underline">{{ $row->title }}</a>@else{{ $row->title }}@endif</p>
                                        <p class="text-xs text-tertiary">{{ $row->kind === 'settlement' ? __('Settlement of a cancelled job') : (isset($row->meta['approved']) ? __(':approved of :total deliverables approved', ['approved' => $row->meta['approved'], 'total' => $row->meta['total']]) : __('Deliverable approved')) }} · {{ $row->at->format('M j, Y') }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-semibold tabular-nums text-neutral-900">{{ Money::formatMinor($row->amount) }}</p>
                                        <p class="text-xs text-green-700">{{ __('Available') }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                        <x-pager :paginator="$jobPayments" footer />
                    @endif
                </x-panel>

                <x-panel :title="__('Your withdrawals')" flush>
                    @if ($payouts->isEmpty())
                        <p class="px-5 py-8 text-center text-sm text-tertiary">{{ __('You have not withdrawn anything yet.') }}</p>
                    @else
                        <ul class="divide-y divide-neutral-100">
                            @foreach ($payouts as $payout)
                                <li class="flex flex-wrap items-center gap-x-4 gap-y-1 px-5 py-3.5">
                                    <div class="min-w-0 flex-1">
                                        <p class="flex flex-wrap items-center gap-2 text-sm font-semibold text-neutral-900">{{ Money::formatMinor($payout->amount_minor) }}<x-badge :tone="$payout->status->tone()" class="px-2 py-0.5 text-xs font-medium">{{ __($payout->status->label()) }}</x-badge></p>
                                        <p class="text-xs text-tertiary">{{ $payout->created_at->format('M j, Y') }} · {{ __('to :phone', ['phone' => Phone::local($payout->msisdn)]) }}@if ($payout->status === PayoutStatus::Paid) · {{ __('you received :net', ['net' => Money::formatMinor($payout->net_minor)]) }}@if ($payout->receipt) · {{ $payout->receipt }}@endif @endif</p>
                                        @if ($payout->failure_reason && ! $payout->status->isOpen() && $payout->status !== PayoutStatus::Paid)<p class="mt-0.5 text-xs text-red-700">{{ $payout->failure_reason }}</p>@endif
                                    </div>
                                    @if ($payout->status === PayoutStatus::Requested)
                                        <form method="POST" action="{{ route('earnings.cancel', $payout) }}">@csrf @method('DELETE')<x-btn size="sm" variant="secondary" type="submit">{{ __('Cancel') }}</x-btn></form>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-panel>
            </div>

            <x-panel :title="__('Withdraw to M-Pesa')" :description="__('Staff approve each withdrawal before it is sent.')">
                @if ($open)
                    <div class="rounded-xl border border-neutral-200 bg-neutral-50/60 p-4 text-sm">
                        <p class="font-semibold text-neutral-900">{{ __('A withdrawal is in progress') }}</p>
                        <p class="mt-1 text-tertiary">{{ Money::formatMinor($open->amount_minor) }} · {{ __($open->status->label()) }}. {{ __('You can ask for another once this one is finished.') }}</p>
                    </div>
                @elseif (! $canWithdraw)
                    <p class="rounded-xl bg-neutral-50 px-4 py-3 text-sm text-neutral-700">{{ __('You can withdraw once you have at least :min available. You have :have.', ['min' => Money::formatMinor($min, 0), 'have' => Money::formatMinor($available, 0)]) }}</p>
                @else
                    <form method="POST" action="{{ route('earnings.withdraw') }}" class="space-y-4" x-data="{ amount: @js(old('amount', (int) ($available / 100))), fee: {{ (int) ($fee / 100) }}, get net() { return Math.max(0, (Number(this.amount) || 0) - this.fee); } }">
                        @csrf
                        <div>
                            <label for="amount" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('Amount (KES)') }}</label>
                            <div class="flex gap-2">
                                <input id="amount" type="number" name="amount" x-model="amount" min="{{ (int) ($min / 100) }}" max="{{ (int) ($available / 100) }}" step="1" inputmode="numeric" required class="block w-full rounded-xl border px-4 py-2.5 text-sm tabular-nums focus:outline-none focus:ring-2 {{ $errors->has('amount') ? 'border-red-400 focus:ring-red-200' : 'border-neutral-300 focus:border-secondary focus:ring-secondary/25' }}">
                                <button type="button" @click="amount = {{ (int) ($available / 100) }}" class="shrink-0 rounded-xl border border-neutral-300 px-3 text-sm font-medium text-neutral-700 hover:bg-neutral-50">{{ __('All') }}</button>
                            </div>
                            @error('amount')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                            <p class="mt-1.5 text-xs text-tertiary">{{ __('At least :min, at most :max.', ['min' => Money::formatMinor($min, 0), 'max' => Money::formatMinor($available, 0)]) }}</p>
                        </div>
                        <div>
                            <label for="payout-phone" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('Send to this M-Pesa number') }}</label>
                            <input id="payout-phone" type="tel" name="phone" value="{{ $phone }}" inputmode="tel" required placeholder="0712 345 678" class="block w-full rounded-xl border px-4 py-2.5 text-sm focus:outline-none focus:ring-2 {{ $errors->has('phone') ? 'border-red-400 focus:ring-red-200' : 'border-neutral-300 focus:border-secondary focus:ring-secondary/25' }}">
                            @error('phone')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                        </div>
                        <dl class="space-y-1.5 rounded-xl bg-neutral-50 px-4 py-3 text-sm">
                            <div class="flex justify-between"><dt class="text-tertiary">{{ __('Withdrawal fee') }}</dt><dd class="tabular-nums text-neutral-900">{{ Money::formatMinor($fee, 0) }}</dd></div>
                            <div class="flex justify-between font-semibold"><dt class="text-neutral-900">{{ __('You receive') }}</dt><dd class="tabular-nums text-neutral-900" x-text="'Ksh' + net.toLocaleString('en-US')"></dd></div>
                        </dl>
                        <x-btn block type="submit">{{ __('Ask to withdraw') }}</x-btn>
                    </form>
                @endif
            </x-panel>
        </div>
    </div>
</x-app-layout>
