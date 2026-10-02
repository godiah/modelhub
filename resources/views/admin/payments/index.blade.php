@use('App\Support\Money')
@use('App\Support\Phone')
@php
    $tabItems = collect($tabs)->map(fn ($label, $key) => ['label' => $label, 'count' => $counts[$key], 'on' => $tab === $key, 'url' => route('admin.payments.index', array_filter(['status' => $key, 'q' => $term, 'sort' => request('sort'), 'dir' => request('dir')]))])->values()->all();
    $chips = [$term !== '' ? ['label' => __('Search: :term', ['term' => $term]), 'remove' => ['q']] : null];
    $columns = [
        ['key' => 'payment', 'label' => 'Payment'],
        ['key' => 'buyer', 'label' => 'Buyer', 'class' => 'hidden md:table-cell'],
        ['key' => 'amount', 'label' => 'Amount', 'sort' => 'amount', 'first' => 'desc', 'align' => 'right'],
        ['key' => 'status', 'label' => 'Status', 'sort' => 'status'],
        ['key' => 'date', 'label' => 'Date', 'sort' => 'date', 'first' => 'desc', 'align' => 'right', 'class' => 'hidden sm:table-cell'],
    ];
@endphp
<x-staff-layout :title="__('Payments')">
    <div class="container mx-auto max-w-7xl px-4 py-8">
        <x-staff.header :title="__('Payments')" :description="__('Every payment for a model, from the M-Pesa prompt to the licence. Payments that arrived but could not be matched to a licence are under Needs review.')" />

        <x-staff.toolbar :tabs="$tabItems" :search="$term" :placeholder="__('Reference, M-Pesa receipt, buyer or model')" :chips="$chips" :action="route('admin.payments.index')" />

        @if ($payments->isEmpty())
            <x-empty-state icon="currency-dollar" :title="$tab === 'review' ? __('Nothing needs review') : __('No payments here')" :description="$tab === 'review' ? __('Every payment that arrived has become a licence.') : __('No payments match this filter.')" />
        @else
            <x-staff.table :columns="$columns" :sort="$sort" :dir="$dir" :paginator="$payments" :summary="trans_choice(':count payment|:count payments', $payments->total(), ['count' => number_format($payments->total())])">
                @foreach ($payments as $payment)
                    <x-staff.row :href="route('admin.payments.show', $payment)">
                        <td class="px-4">
                            <a href="{{ route('admin.payments.show', $payment) }}" wire:navigate class="block focus:outline-none focus-visible:underline">
                                <span class="block truncate font-semibold text-neutral-900">{{ $payment->subjectTitle() }}</span>
                                <span class="block text-xs font-normal text-tertiary"><span class="font-mono">{{ $payment->reference }}</span> · {{ $payment->subjectKind() }}</span>
                            </a>
                        </td>
                        <td class="hidden px-4 md:table-cell"><span class="flex items-center gap-2"><x-user-avatar :user="$payment->buyer" size="h-7 w-7" /><span class="truncate text-neutral-700">{{ $payment->buyer?->name }}</span></span></td>
                        <td class="whitespace-nowrap px-4 text-right font-medium tabular-nums text-neutral-900">{{ Money::formatMinor($payment->amount_minor) }}</td>
                        <td class="px-4"><x-badge :tone="$payment->status->tone()" class="px-2 py-0.5 text-xs font-medium">{{ __($payment->status->label()) }}</x-badge>@if ($payment->failure_reason && $payment->status === \App\Enums\PaymentStatus::Review)<span class="mt-1 block max-w-[16rem] truncate text-xs text-amber-800" title="{{ $payment->failure_reason }}">{{ $payment->failure_reason }}</span>@endif</td>
                        <td class="hidden whitespace-nowrap px-4 text-right text-neutral-600 sm:table-cell">{{ $payment->created_at->format('M j, Y') }}<span class="block text-xs text-tertiary">{{ $payment->created_at->format('g:i A') }}</span></td>
                    </x-staff.row>
                @endforeach
            </x-staff.table>
        @endif
    </div>
</x-staff-layout>
