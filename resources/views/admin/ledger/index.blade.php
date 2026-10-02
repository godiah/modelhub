@use('App\Support\Money')
@php
    $typeLabels = ['sale' => 'Sale', 'unallocated' => 'Unallocated payment', 'release' => 'Hold ended', 'payout_requested' => 'Withdrawal asked', 'payout_paid' => 'Withdrawal sent', 'payout_returned' => 'Withdrawal returned', 'refund' => 'Refund', 'refund_unallocated' => 'Refund (unallocated)'];
    $tabItems = collect($tabs)->map(fn ($t, $key) => ['label' => $t[0], 'on' => $tab === $key, 'url' => route('admin.ledger.index', array_filter(['tab' => $key, 'q' => $term, 'member' => $member?->id]))])->values()->all();
    $chips = [$term !== '' ? ['label' => __('Search: :term', ['term' => $term]), 'remove' => ['q']] : null, $member ? ['label' => __('Member: :name', ['name' => $member->name]), 'remove' => ['member']] : null];
    $columns = [
        ['key' => 'when', 'label' => 'When'],
        ['key' => 'type', 'label' => 'Type'],
        ['key' => 'what', 'label' => 'What'],
        ['key' => 'amount', 'label' => 'Moved', 'align' => 'right'],
    ];
@endphp
<x-staff-layout :title="__('Ledger')">
    <div class="container mx-auto max-w-7xl space-y-6 px-4 py-8">
        <x-staff.header class="!mb-0" :title="__('Ledger')" :description="__('Every money movement on the platform, as balanced postings that are never edited. Read-only.')" />

        {{-- Does it add up? --}}
        <div @class(['rounded-xl border p-4 text-sm', 'border-green-200 bg-green-50 text-green-900' => $summary['reconciled'] && $summary['trial']['balanced'], 'border-red-300 bg-red-50 text-red-900' => ! ($summary['reconciled'] && $summary['trial']['balanced'])]) role="status">
            @if ($summary['reconciled'] && $summary['trial']['balanced'])
                <p class="font-semibold">{{ __('The books add up.') }}</p>
                <p class="mt-0.5">{{ __('What the payment gateway holds equals what is owed to members and what the platform has earned, and total debits equal total credits.') }}</p>
            @else
                <p class="font-semibold">{{ __('The books do not add up. Stop and look into this.') }}</p>
                <p class="mt-0.5">{{ __('The gateway holds :gateway but :expected is accounted for (a difference of :diff). Debits :debits, credits :credits.', ['gateway' => Money::formatMinor($summary['gateway']), 'expected' => Money::formatMinor($summary['expected']), 'diff' => Money::formatMinor(abs($summary['difference'])), 'debits' => Money::formatMinor($summary['trial']['debits']), 'credits' => Money::formatMinor($summary['trial']['credits'])]) }}</p>
            @endif
        </div>

        <section aria-label="{{ __('Where the money is') }}" class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <x-stat-tile :label="__('Held at the gateway')" :value="Money::formatMinor($summary['gateway'])" :hint="__('All the money we hold')" icon="banknotes" />
            <x-stat-tile :label="__('Owed to members')" :value="Money::formatMinor($summary['owed'])" :hint="__(':held in hold · :avail available', ['held' => Money::formatMinor($summary['pending'], 0), 'avail' => Money::formatMinor($summary['available'], 0)])" icon="user-group" tone="amber" />
            <x-stat-tile :label="__('Earned by the platform')" :value="Money::formatMinor($summary['earned'])" :hint="__(':c commission · :f fees', ['c' => Money::formatMinor($summary['commission'], 0), 'f' => Money::formatMinor($summary['fees'], 0)])" icon="cash" />
            <x-stat-tile :label="__('Needs sorting out')" :value="Money::formatMinor($summary['unallocated'] + $summary['in_payouts'])" :hint="__(':u unallocated · :p in withdrawals', ['u' => Money::formatMinor($summary['unallocated'], 0), 'p' => Money::formatMinor($summary['in_payouts'], 0)])" icon="exclamation-triangle" :tone="$summary['unallocated'] > 0 ? 'red' : 'teal'" />
        </section>

        <div>
            <x-staff.toolbar :tabs="$tabItems" :search="$term" :placeholder="__('Description or posting key')" :chips="$chips" :action="route('admin.ledger.index')" />

            @if ($transactions->isEmpty())
                <x-empty-state icon="calculator" :title="__('No postings here')" :description="__('Nothing matches this filter.')" />
            @else
                <x-staff.table :columns="$columns" :paginator="$transactions" :summary="trans_choice(':count posting|:count postings', $transactions->total(), ['count' => number_format($transactions->total())])">
                    @foreach ($transactions as $transaction)
                        <x-staff.row :href="route('admin.ledger.show', $transaction)">
                            <td class="whitespace-nowrap px-4 text-neutral-600">{{ $transaction->occurred_at->format('M j, Y') }}<span class="block text-xs text-tertiary">{{ $transaction->occurred_at->format('g:i:s A') }}</span></td>
                            <td class="whitespace-nowrap px-4"><x-badge tone="neutral" class="px-2 py-0.5 text-xs font-medium">{{ __($typeLabels[$transaction->type] ?? $transaction->type) }}</x-badge></td>
                            <td class="px-4"><a href="{{ route('admin.ledger.show', $transaction) }}" wire:navigate class="block focus:outline-none focus-visible:underline"><span class="block text-neutral-900">{{ $transaction->description }}</span><span class="block font-mono text-xs font-normal text-tertiary">{{ $transaction->idempotency_key }}</span></a></td>
                            <td class="whitespace-nowrap px-4 text-right font-medium tabular-nums text-neutral-900">{{ Money::formatMinor((int) $transaction->debit_total) }}</td>
                        </x-staff.row>
                    @endforeach
                </x-staff.table>
            @endif
        </div>
    </div>
</x-staff-layout>
