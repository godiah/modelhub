@use('App\Support\Money')
@use('App\Support\Phone')
@use('App\Support\Staff\Masking')
@php
    $tabs = collect($statuses)->map(fn ($label, $key) => ['label' => $label, 'count' => $counts[$key], 'on' => $status === $key, 'url' => route('admin.payouts.index', array_filter(['status' => $key, 'sort' => request('sort'), 'dir' => request('dir')]))])->values()->all();
    $columns = [
        ['key' => 'member', 'label' => 'Member'],
        ['key' => 'amount', 'label' => 'Asked for', 'sort' => 'amount', 'first' => 'desc', 'align' => 'right'],
        ['key' => 'net', 'label' => 'Sent', 'align' => 'right', 'class' => 'hidden md:table-cell'],
        ['key' => 'phone', 'label' => 'To', 'class' => 'hidden lg:table-cell'],
        ['key' => 'requested', 'label' => 'Requested', 'sort' => 'requested', 'first' => 'desc', 'class' => 'hidden sm:table-cell'],
        ['key' => 'status', 'label' => 'Status'],
        ['key' => 'actions', 'label' => '', 'align' => 'right'],
    ];
@endphp
<x-staff-layout :title="__('Payouts')">
    <div class="container mx-auto max-w-7xl px-4 py-8" x-data="{ approving: false, rejecting: false, target: { name: '', amount: '', phone: '', approve: '', reject: '' } }"
        @approve-payout.window="target = $event.detail; approving = true" @reject-payout.window="target = $event.detail; rejecting = true">
        <x-staff.header :title="__('Payouts')" :description="__('Withdrawals members have asked for. Approving one sends the money to their M-Pesa; turning one down puts it back in their balance, with the reason you give.')" />

        <x-staff.toolbar :tabs="$tabs" />

        @if ($payouts->isEmpty())
            <x-empty-state icon="banknotes" :title="__('No withdrawals here')" :description="$status === 'requested' ? __('Nothing is waiting for approval.') : __('No withdrawals match this filter.')" />
        @else
            <x-staff.table :columns="$columns" :sort="$sort" :dir="$dir" :paginator="$payouts" :summary="trans_choice(':count withdrawal|:count withdrawals', $payouts->total(), ['count' => number_format($payouts->total())])">
                @foreach ($payouts as $payout)
                    <x-staff.row>
                        <td class="px-4"><span class="flex items-center gap-3"><x-user-avatar :user="$payout->user" size="h-9 w-9" /><span class="min-w-0"><span class="block truncate font-semibold text-neutral-900">{{ $payout->user?->name ?? __('Deleted account') }}</span><span class="block font-mono text-xs font-normal text-tertiary">{{ $payout->reference }}</span></span></span></td>
                        <td class="whitespace-nowrap px-4 text-right font-medium tabular-nums text-neutral-900">{{ Money::formatMinor($payout->amount_minor, 0) }}</td>
                        <td class="hidden whitespace-nowrap px-4 text-right tabular-nums text-neutral-700 md:table-cell">{{ Money::formatMinor($payout->net_minor, 0) }}<span class="block text-xs text-tertiary">{{ __('fee :fee', ['fee' => Money::formatMinor($payout->fee_minor, 0)]) }}</span></td>
                        <td class="hidden whitespace-nowrap px-4 text-neutral-700 lg:table-cell">{{ $canApprove ? Phone::local($payout->msisdn) : Masking::phone($payout->msisdn, false) }}</td>
                        <td class="hidden whitespace-nowrap px-4 text-neutral-600 sm:table-cell">{{ $payout->created_at->format('M j, g:i A') }}<span class="block text-xs text-tertiary">{{ $payout->created_at->diffForHumans() }}</span></td>
                        <td class="px-4"><x-badge :tone="$payout->status->tone()" class="px-2 py-0.5 text-xs font-medium">{{ __($payout->status->label()) }}</x-badge>@if ($payout->failure_reason && ! $payout->status->isOpen())<span class="mt-1 block max-w-[14rem] truncate text-xs text-tertiary" title="{{ $payout->failure_reason }}">{{ $payout->failure_reason }}</span>@endif @if ($payout->approver)<span class="mt-0.5 block text-xs text-tertiary">{{ __('by :name', ['name' => $payout->approver->name]) }}</span>@endif</td>
                        <td class="whitespace-nowrap px-4 text-right">
                            @if ($canApprove && $payout->status === \App\Enums\PayoutStatus::Requested)
                                <div class="flex justify-end gap-2">
                                    <x-btn size="sm" type="button" @click="$dispatch('approve-payout', {{ \Illuminate\Support\Js::from(['name' => $payout->user?->name, 'amount' => Money::formatMinor($payout->net_minor, 0), 'phone' => Phone::local($payout->msisdn), 'approve' => route('admin.payouts.approve', $payout)]) }})">{{ __('Approve') }}</x-btn>
                                    <x-btn size="sm" variant="danger-outline" type="button" @click="$dispatch('reject-payout', {{ \Illuminate\Support\Js::from(['name' => $payout->user?->name, 'amount' => Money::formatMinor($payout->amount_minor, 0), 'reject' => route('admin.payouts.reject', $payout)]) }})">{{ __('Turn down') }}</x-btn>
                                </div>
                            @endif
                        </td>
                    </x-staff.row>
                @endforeach
            </x-staff.table>
        @endif

        @if ($canApprove)
            <x-confirm-dialog bind="approving" title="Approve this withdrawal" icon="check" tone="success" confirm-label="Approve and send" action-bind="target.approve" message="Check the name and number first. This sends the money to the member's M-Pesa, and it cannot be recalled.">
                <p class="mt-3 rounded-xl bg-neutral-50 px-3 py-2 text-sm text-neutral-800"><span x-text="target.amount"></span> <span class="text-tertiary">to</span> <span class="font-semibold" x-text="target.name"></span> <span class="text-tertiary">at</span> <span class="font-mono" x-text="target.phone"></span></p>
            </x-confirm-dialog>
            <x-confirm-dialog bind="rejecting" title="Turn this withdrawal down" confirm-label="Turn down" state="reason: ''" disabledWhen="reason.trim().length < 5" action-bind="target.reject" message="The money goes back into the member's balance and they are shown your reason.">
                <label for="reject-reason" class="sr-only">{{ __('Reason') }}</label>
                <textarea id="reject-reason" name="reason" x-model="reason" rows="3" maxlength="255" required placeholder="{{ __('Why it is being turned down') }}" class="mt-3 block w-full resize-none rounded-xl border border-neutral-300 px-3 py-2 text-sm focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25"></textarea>
            </x-confirm-dialog>
        @endif
    </div>
</x-staff-layout>
