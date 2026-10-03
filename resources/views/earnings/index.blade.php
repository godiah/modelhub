@php
    use App\Enums\PayoutStatus;
    use App\Services\Payments\EarningsInsights;
    use App\Support\Money;
    use App\Support\Phone;
    use App\Support\Settings\FeePolicy;

    $min = FeePolicy::minPayoutMinor();
    $fee = FeePolicy::payoutFeeMinor();
    $available = $summary['available'];
    $canWithdraw = ! $open && $available >= $min;
    $phone = old('phone', auth()->user()->profile?->telephone_number);
    $hasAnything = $summary['earned'] > 0 || $summary['available'] > 0 || $summary['pending'] > 0 || $summary['withdrawn'] > 0 || $incoming['from_jobs'] > 0 || $feed->total() > 0;
    $change = $overview['change'];
    $totals = array_map(fn ($b) => $b['models'] + $b['jobs'], $overview['buckets']);
    $sourceParts = [
        ['label' => __('Model sales'), 'value' => $overview['by_source']['models'], 'color' => '#0d9488', 'text' => Money::formatMinor($overview['by_source']['models'], 0)],
        ['label' => __('Jobs'), 'value' => $overview['by_source']['jobs'], 'color' => '#6366f1', 'text' => Money::formatMinor($overview['by_source']['jobs'], 0)],
    ];
    $kinds = [
        'sale' => ['cube', 'bg-teal-50 text-teal-700'],
        'job' => ['briefcase', 'bg-indigo-50 text-indigo-600'],
        'withdrawal' => ['banknotes', 'bg-neutral-100 text-neutral-600'],
    ];
    $url = fn (array $query = []) => route('earnings.index', array_filter(['range' => $range === '30d' ? null : $range, 'show' => $filter === 'all' ? null : $filter] + $query));
@endphp
<x-app-layout title="Earnings">
    <div class="container mx-auto max-w-7xl space-y-6 px-4 py-8">
        {{-- Header: what this is, and the period everything below is read over --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="font-tertiary text-2xl font-bold tracking-tight text-neutral-900 sm:text-3xl">{{ __('Earnings') }}</h1>
                <p class="mt-1 max-w-2xl text-sm text-tertiary">{{ __('Your balance, what you have earned, and what is on its way.') }} <a href="{{ route('policies.payments') }}" class="font-medium text-teal-700 hover:underline">{{ __('How earnings and withdrawals work') }}</a></p>
            </div>
            <nav aria-label="{{ __('Time range') }}" class="flex w-full gap-1 rounded-xl bg-neutral-100 p-1 sm:w-auto">
                @foreach (EarningsInsights::RANGES as $key => [$long, $short])
                    <a href="{{ route('earnings.index', array_filter(['range' => $key === '30d' ? null : $key, 'show' => $filter === 'all' ? null : $filter])) }}" wire:navigate title="{{ __($long) }}" @if ($range === $key) aria-current="true" @endif
                        @class(['flex-1 rounded-lg px-3 py-1.5 text-center text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40 sm:flex-none', 'bg-white text-neutral-900 shadow-sm' => $range === $key, 'text-neutral-600 hover:text-neutral-900' => $range !== $key])>{{ __($short) }}</a>
                @endforeach
            </nav>
        </div>

        {{-- The balance, and the one thing to do with it --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <section aria-label="{{ __('Balance') }}" class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-teal-700 to-teal-900 text-white shadow-sm lg:col-span-2">
                <div class="p-6 sm:p-8">
                    <p class="text-sm font-medium text-teal-100">{{ __('Available to withdraw') }}</p>
                    <p class="mt-2 font-tertiary text-4xl font-bold tabular-nums tracking-tight sm:text-5xl">{{ Money::formatMinor($available) }}</p>

                    <div class="mt-6 flex flex-wrap items-center gap-3">
                        @if ($canWithdraw)
                            <button type="button" @click="$dispatch('open-modal', 'withdraw')" class="inline-flex items-center gap-2 rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-teal-800 shadow-sm transition hover:bg-teal-50 focus:outline-none focus-visible:ring-4 focus-visible:ring-white/40">
                                <x-icon name="banknotes" class="h-5 w-5" />{{ __('Withdraw to M-Pesa') }}
                            </button>
                            <p class="text-sm text-teal-100">{{ __('Fee :fee · staff approve each one', ['fee' => Money::formatMinor($fee, 0)]) }}</p>
                        @elseif ($open)
                            <div class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-4 py-2.5 text-sm font-medium ring-1 ring-white/20">
                                <x-icon name="clock" class="h-5 w-5 text-teal-100" />{{ __('Withdrawal of :amount: :status', ['amount' => Money::formatMinor($open->amount_minor, 0), 'status' => strtolower(__($open->status->label()))]) }}
                            </div>
                        @else
                            <span class="inline-flex cursor-not-allowed items-center gap-2 rounded-xl bg-white/15 px-5 py-2.5 text-sm font-semibold text-white/70"><x-icon name="banknotes" class="h-5 w-5" />{{ __('Withdraw to M-Pesa') }}</span>
                            <p class="text-sm text-teal-100">{{ __('You can withdraw once you have :min available.', ['min' => Money::formatMinor($min, 0)]) }}</p>
                        @endif
                    </div>
                </div>

                <dl class="grid grid-cols-1 divide-y divide-white/10 border-t border-white/10 bg-black/10 sm:grid-cols-3 sm:divide-x sm:divide-y-0">
                    <div class="px-6 py-4 sm:px-8">
                        <dt class="flex items-center gap-1.5 text-xs font-medium text-teal-100"><span class="h-2 w-2 rounded-full bg-amber-300"></span>{{ __('In the hold period') }}</dt>
                        <dd class="mt-1 font-tertiary text-lg font-semibold tabular-nums">{{ Money::formatMinor($incoming['in_hold']) }}</dd>
                        <p class="text-xs text-teal-100">{{ $incoming['next_release'] ? __('Next one is released :date', ['date' => $incoming['next_release']->format('M j')]) : __('Model sales wait a few days first') }}</p>
                    </div>
                    <div class="px-6 py-4 sm:px-8">
                        <dt class="flex items-center gap-1.5 text-xs font-medium text-teal-100"><span class="h-2 w-2 rounded-full bg-indigo-300"></span>{{ __('Still to come from jobs') }}</dt>
                        <dd class="mt-1 font-tertiary text-lg font-semibold tabular-nums">{{ Money::formatMinor($incoming['from_jobs']) }}</dd>
                        <p class="text-xs text-teal-100">{{ $incoming['jobs_count'] > 0 ? trans_choice('In escrow on :count active job|In escrow on :count active jobs', $incoming['jobs_count'], ['count' => $incoming['jobs_count']]) : __('Paid as clients approve your work') }}</p>
                    </div>
                    <div class="px-6 py-4 sm:px-8">
                        <dt class="flex items-center gap-1.5 text-xs font-medium text-teal-100"><span class="h-2 w-2 rounded-full bg-emerald-300"></span>{{ __('Withdrawn so far') }}</dt>
                        <dd class="mt-1 font-tertiary text-lg font-semibold tabular-nums">{{ Money::formatMinor($summary['withdrawn']) }}</dd>
                        <p class="text-xs text-teal-100">{{ $summary['in_progress'] > 0 ? __(':amount on its way', ['amount' => Money::formatMinor($summary['in_progress'], 0)]) : __('Sent to your M-Pesa') }}</p>
                    </div>
                </dl>
            </section>

            {{-- How much they earned in the chosen period, against the one before --}}
            <x-card class="flex flex-col justify-between p-6">
                <div>
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-sm font-medium text-tertiary">{{ __('Earned') }} · {{ __($overview['label']) }}</p>
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-teal-50 text-teal-700"><x-icon name="cash" class="h-5 w-5" /></span>
                    </div>
                    <p class="mt-3 font-tertiary text-3xl font-bold tabular-nums text-neutral-900">{{ Money::formatMinor($overview['earned']) }}</p>
                    <p class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-tertiary">
                        @if ($change !== null)
                            <span @class(['inline-flex items-center gap-0.5 rounded-full px-1.5 py-0.5 font-semibold tabular-nums', 'bg-green-50 text-green-700' => $change >= 0, 'bg-red-50 text-red-700' => $change < 0])>{{ $change >= 0 ? '▲' : '▼' }} {{ abs($change) }}%</span>
                            <span>{{ __('vs the :period before (:amount)', ['period' => $range === 'ytd' ? __('period') : strtolower(__($overview['label'])), 'amount' => Money::formatMinor($overview['previous'], 0)]) }}</span>
                        @elseif ($overview['previous'] !== null && $overview['previous'] === 0 && $overview['earned'] > 0)
                            <span class="inline-flex rounded-full bg-green-50 px-1.5 py-0.5 font-semibold text-green-700">{{ __('New') }}</span><span>{{ __('Nothing earned in the period before') }}</span>
                        @elseif ($range !== 'all')
                            <span>{{ __('Nothing to compare with yet') }}</span>
                        @else
                            <span>{{ __('Since your first payment') }}</span>
                        @endif
                    </p>
                </div>
                <div class="mt-4">
                    <x-sparkline :values="$totals" :label="__('Earnings over the period')" />
                    <p class="mt-2 text-xs text-tertiary">{{ $overview['count'] > 0 ? trans_choice(':count payment|:count payments', $overview['count'], ['count' => $overview['count']]).' · '.__('average :amount', ['amount' => Money::formatMinor($overview['average'], 0)]) : __('No payments in this period') }}</p>
                </div>
            </x-card>
        </div>

        @if (! $hasAnything)
            <x-empty-state icon="banknotes" :title="__('No earnings yet')" :description="__('When someone buys one of your models, or a client approves your work on a project, your share shows up here.')">
                    <div class="mt-4 flex flex-wrap justify-center gap-2">
                        <x-btn size="sm" :href="route('seller.index')">{{ __('Sell a model') }}</x-btn>
                        <x-btn size="sm" variant="secondary" :href="route('jobs.browse')">{{ __('Find a project') }}</x-btn>
                    </div>
                </x-empty-state>
        @else
            {{-- Over time, and where it came from --}}
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <x-panel :title="__('Earnings over time')" :description="__('What reached you, by :unit.', ['unit' => __($overview['unit'])])" class="flex flex-col lg:col-span-2" fill>
                    <x-slot:actions>
                        <span class="hidden items-center gap-4 text-xs text-tertiary sm:flex">
                            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-teal-600"></span>{{ __('Model sales') }}</span>
                            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-indigo-500"></span>{{ __('Jobs') }}</span>
                        </span>
                    </x-slot:actions>
                    @if ($overview['earned'] > 0)
                        <x-earnings-chart :buckets="$overview['buckets']" :max="$overview['max']" :unit="$overview['unit']" />
                    @else
                        <x-empty-state :framed="false" class="min-h-[18rem] flex-1" icon="chart-bar" :title="__('Nothing earned in this period')" :description="__('Try a longer range to see your earlier earnings.')">
                            @if ($range !== 'all')<a href="{{ route('earnings.index', ['range' => 'all']) }}" wire:navigate class="text-sm font-medium text-teal-700 hover:underline">{{ __('Show all time') }}</a>@endif
                        </x-empty-state>
                    @endif
                </x-panel>

                <x-panel :title="__('Where it comes from')" :description="__($overview['label'])">
                    @if ($overview['earned'] > 0)
                        <x-donut :parts="$sourceParts" :center="Money::formatMinor($overview['earned'], 0)" :caption="__('earned')" />
                        <h3 class="mt-6 text-xs font-semibold uppercase tracking-wide text-tertiary">{{ __('Top earners') }}</h3>
                        <ul class="mt-3 space-y-3">
                            @foreach (array_slice($overview['top'], 0, EarningsInsights::TOP_EARNERS) as $item)
                                <li>
                                    <div class="flex items-baseline justify-between gap-3 text-sm">
                                        <span class="min-w-0 truncate font-medium text-neutral-800">{{ $item['title'] }}</span>
                                        <span class="shrink-0 tabular-nums text-neutral-900">{{ Money::formatMinor($item['minor'], 0) }}</span>
                                    </div>
                                    <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-neutral-100"><div class="h-full rounded-full {{ $item['source'] === 'jobs' ? 'bg-indigo-500' : 'bg-teal-600' }}" style="width: {{ max(4, round($item['minor'] / max(1, $overview['top'][0]['minor']) * 100)) }}%"></div></div>
                                    <p class="mt-1 text-xs text-tertiary">{{ $item['source'] === 'jobs' ? __('Job') : __('Model') }} · {{ trans_choice(':count payment|:count payments', $item['count'], ['count' => $item['count']]) }}</p>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <x-empty-state :framed="false" compact icon="chart-bar" :title="__('Nothing earned in this period')" />
                    @endif
                </x-panel>
            </div>

            {{-- Every sale, job payment and withdrawal --}}
            <x-panel :title="__('Activity')" :description="__('Every sale, job payment and withdrawal, newest first.')" flush>

                    <nav aria-label="{{ __('Filter activity') }}" class="flex flex-wrap gap-1.5 px-5 pb-4 pt-1 sm:px-6">
                        @foreach (EarningsInsights::FEED_FILTERS as $key => $label)
                            <a href="{{ route('earnings.index', array_filter(['range' => $range === '30d' ? null : $range, 'show' => $key === 'all' ? null : $key])) }}" wire:navigate @if ($filter === $key) aria-current="true" @endif
                                @class(['rounded-full px-3 py-1 text-xs font-medium transition-colors', 'bg-teal-600 text-white' => $filter === $key, 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200' => $filter !== $key])>{{ __($label) }}</a>
                        @endforeach
                    </nav>

                @if ($feed->isEmpty())
                    <x-empty-state :framed="false" icon="banknotes" :title="__('Nothing here yet')" :description="__('Nothing matches this filter.')" />
                @else
                    <ul class="divide-y divide-neutral-100">
                        @foreach ($feed as $row)
                            @php [$icon, $chip] = $kinds[$row->kind]; @endphp
                            <li class="flex flex-wrap items-center gap-x-4 gap-y-2 px-5 py-4 sm:px-6">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $chip }}"><x-icon :name="$icon" class="h-5 w-5" /></span>
                                <div class="min-w-0 flex-1 basis-48">
                                    <p class="truncate text-sm font-semibold text-neutral-900">@if ($row->url)<a href="{{ $row->url }}" class="hover:text-teal-700 hover:underline">{{ $row->title }}</a>@else{{ $row->title }}@endif</p>
                                    <p class="truncate text-xs text-tertiary">{{ $row->detail }}</p>
                                </div>
                                <div class="hidden text-xs text-tertiary sm:block">{{ $row->at->format('M j, Y') }}</div>
                                <x-badge :tone="$row->status[1]" class="px-2 py-0.5 text-xs font-medium">{{ __($row->status[0]) }}</x-badge>
                                <p @class(['w-28 text-right text-sm font-semibold tabular-nums', 'text-neutral-900' => $row->minor < 0 && ! $row->struck, 'text-green-700' => $row->minor > 0, 'text-neutral-400 line-through' => $row->struck])>{{ $row->minor > 0 ? '+' : ($row->minor < 0 ? '−' : '') }}{{ Money::formatMinor(abs($row->minor)) }}</p>
                                @if (isset($row->payout) && $row->payout->status === PayoutStatus::Requested)
                                    <form method="POST" action="{{ route('earnings.cancel', $row->payout) }}">@csrf @method('DELETE')<x-btn size="sm" variant="secondary" type="submit">{{ __('Cancel') }}</x-btn></form>
                                @endif
                                @if (isset($row->payout) && $row->payout->failure_reason && ! $row->payout->status->isOpen() && $row->payout->status !== PayoutStatus::Paid)
                                    <p class="basis-full pl-14 text-xs text-red-700">{{ $row->payout->failure_reason }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    <x-pager :paginator="$feed" footer />
                @endif
            </x-panel>
        @endif
    </div>

    {{-- Asking for a withdrawal --}}
    <x-modal name="withdraw" max-width="md" :show="$errors->any()">
        <x-modal.header :title="__('Withdraw to M-Pesa')" icon="banknotes" />
        <div class="p-6">
            <p class="mb-4 text-sm text-tertiary">{{ __('Staff approve each withdrawal before it is sent.') }}</p>
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
                            <input id="amount" type="number" name="amount" x-model="amount" min="{{ (int) ($min / 100) }}" max="{{ (int) ($available / 100) }}" step="1" inputmode="numeric" required class="block w-full rounded-xl border px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-secondary/25 @error('amount') border-red-400 @else border-neutral-300 @enderror">
                            <button type="button" @click="amount = {{ (int) ($available / 100) }}" class="shrink-0 rounded-xl border border-neutral-300 px-3 text-sm font-medium text-neutral-700 hover:bg-neutral-50">{{ __('All') }}</button>
                        </div>
                        @error('amount')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                        <p class="mt-1.5 text-xs text-tertiary">{{ __('At least :min, at most :max.', ['min' => Money::formatMinor($min, 0), 'max' => Money::formatMinor($available, 0)]) }}</p>
                    </div>
                    <div>
                        <label for="payout-phone" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('Send to this M-Pesa number') }}</label>
                        <input id="payout-phone" type="tel" name="phone" value="{{ $phone }}" inputmode="tel" required placeholder="0712 345 678" class="block w-full rounded-xl border px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-secondary/25 @error('phone') border-red-400 @else border-neutral-300 @enderror">
                        @error('phone')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <dl class="space-y-1.5 rounded-xl bg-neutral-50 px-4 py-3 text-sm">
                        <div class="flex justify-between"><dt class="text-tertiary">{{ __('Withdrawal fee') }}</dt><dd class="tabular-nums text-neutral-900">{{ Money::formatMinor($fee, 0) }}</dd></div>
                        <div class="flex justify-between font-semibold"><dt class="text-neutral-900">{{ __('You receive') }}</dt><dd class="tabular-nums text-neutral-900" x-text="'Ksh' + net.toLocaleString('en-US')"></dd></div>
                    </dl>
                    <p class="text-xs text-tertiary">{{ __('Check the number carefully: M-Pesa transfers cannot be recalled.') }}</p>
                    <x-btn block type="submit">{{ __('Ask to withdraw') }}</x-btn>
                </form>
            @endif
        </div>
    </x-modal>
</x-app-layout>
