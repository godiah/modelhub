@use('App\Support\Money')
@use('App\Support\Phone')
@use('App\Support\Staff\Masking')
@php
    $tabItems = collect($tabs)->map(fn ($label, $key) => ['label' => $label, 'count' => $counts[$key], 'on' => $tab === $key, 'url' => route('admin.escrow-refunds.index', ['tab' => $key])])->values()->all();
    $columns = $tab === 'returned'
        ? [['key' => 'job', 'label' => 'Job'], ['key' => 'client', 'label' => 'Client', 'class' => 'hidden md:table-cell'], ['key' => 'amount', 'label' => 'Returned', 'align' => 'right'], ['key' => 'staff', 'label' => 'Recorded by', 'class' => 'hidden lg:table-cell'], ['key' => 'date', 'label' => 'Date', 'align' => 'right', 'class' => 'hidden sm:table-cell']]
        : [['key' => 'job', 'label' => 'Job'], ['key' => 'client', 'label' => 'Client', 'class' => 'hidden md:table-cell'], ['key' => 'amount', 'label' => 'Left in escrow', 'align' => 'right'], ['key' => 'phone', 'label' => 'Paid from', 'class' => 'hidden lg:table-cell'], ['key' => 'when', 'label' => $tab === 'due' ? 'Due since' : 'Due back', 'align' => 'right', 'class' => 'hidden sm:table-cell'], ['key' => 'actions', 'label' => '', 'align' => 'right']];
@endphp
<x-staff-layout :title="__('Escrow refunds')">
    <div class="container mx-auto max-w-7xl px-4 py-8" x-data="{ returning: false, target: { name: '', amount: '', phone: '', action: '' } }" @return-escrow.window="target = $event.detail; returning = true">
        <x-staff.header :title="__('Escrow refunds')" :description="__('Money left in a job\'s escrow that is now the client\'s to have back. Record the return here (this posts the books), then send it by M-Pesa to the number they paid from.')" />

        <x-staff.toolbar :tabs="$tabItems" />

        @if ($rows->isEmpty())
            <x-empty-state icon="banknotes" :title="match ($tab) { 'due' => __('Nothing is due back'), 'waiting' => __('Nothing is waiting'), default => __('Nothing has been returned yet') }" :description="match ($tab) { 'due' => __('No client is waiting for money from a job\'s escrow.'), 'waiting' => __('No cancelled job is holding money for a review window or an open payment.'), default => __('Returns you record will be listed here.') }" />
        @else
            <x-staff.table :columns="$columns" :paginator="$rows" :summary="trans_choice(':count job|:count jobs', $rows->total(), ['count' => number_format($rows->total())])">
                @foreach ($rows as $row)
                    @if ($tab === 'returned')
                        <x-staff.row :href="route('admin.engagements.show', $row->engagement_id)">
                            <td class="px-4"><a href="{{ route('admin.engagements.show', $row->engagement_id) }}" wire:navigate class="block truncate font-semibold text-neutral-900 hover:underline">{{ $row->engagement?->application?->job?->title ?? __('A deleted job') }}</a><span class="block text-xs text-tertiary">{{ $row->funding_receipt ? __('Paid with receipt :r', ['r' => $row->funding_receipt]) : '' }}</span></td>
                            <td class="hidden px-4 md:table-cell"><span class="flex items-center gap-2"><x-user-avatar :user="$row->client" size="h-7 w-7" /><span class="truncate text-neutral-700">{{ $row->client?->name }}</span></span></td>
                            <td class="whitespace-nowrap px-4 text-right font-medium tabular-nums text-neutral-900">{{ Money::formatMinor($row->amount_minor) }}</td>
                            <td class="hidden px-4 text-neutral-700 lg:table-cell">{{ $row->staff?->name ?? '—' }}@if ($row->note)<span class="block max-w-xs truncate text-xs text-tertiary" title="{{ $row->note }}">{{ $row->note }}</span>@endif</td>
                            <td class="hidden whitespace-nowrap px-4 text-right text-neutral-600 sm:table-cell">{{ $row->created_at->format('M j, Y') }}</td>
                        </x-staff.row>
                    @else
                        @php
                            $remaining = $row->escrowRemainingMinor();
                            $phone = $row->payments->first()?->msisdn;
                        @endphp
                        <x-staff.row :href="route('admin.engagements.show', $row)">
                            <td class="px-4"><a href="{{ route('admin.engagements.show', $row) }}" wire:navigate class="block truncate font-semibold text-neutral-900 hover:underline">{{ $row->application->job->title }}</a><span class="block text-xs text-tertiary">{{ __($row->status->label()) }} · {{ __('freelancer :name', ['name' => $row->application->applicant->name]) }}</span></td>
                            <td class="hidden px-4 md:table-cell"><span class="flex items-center gap-2"><x-user-avatar :user="$row->application->poster" size="h-7 w-7" /><span class="truncate text-neutral-700">{{ $row->application->poster->name }}</span></span></td>
                            <td class="whitespace-nowrap px-4 text-right font-medium tabular-nums text-neutral-900">{{ Money::formatMinor($remaining) }}<span class="block text-xs font-normal text-tertiary">{{ __('of :total funded', ['total' => Money::formatMinor($row->escrow_minor, 0)]) }}</span></td>
                            <td class="hidden whitespace-nowrap px-4 text-neutral-700 lg:table-cell">{{ $phone ? ($canRefund ? Phone::local($phone) : Masking::phone($phone, false)) : '—' }}</td>
                            <td class="hidden whitespace-nowrap px-4 text-right text-neutral-600 sm:table-cell">@if ($row->escrow_refund_due_at){{ $row->escrow_refund_due_at->format('M j, Y') }}<span class="block text-xs text-tertiary">{{ $row->escrow_refund_due_at->diffForHumans() }}</span>@else<span class="text-xs text-tertiary">{{ __('A payment or dispute is open') }}</span>@endif</td>
                            <td class="whitespace-nowrap px-4 text-right">
                                @if ($canRefund && $tab === 'due')
                                    <x-btn size="sm" type="button" @click="$dispatch('return-escrow', {{ \Illuminate\Support\Js::from(['name' => $row->application->poster->name, 'amount' => Money::formatMinor($remaining), 'phone' => $phone ? Phone::local($phone) : '—', 'action' => route('admin.escrow-refunds.refund', $row)]) }})">{{ __('Record return') }}</x-btn>
                                @endif
                            </td>
                        </x-staff.row>
                    @endif
                @endforeach
            </x-staff.table>
        @endif

        @if ($canRefund)
            <x-confirm-dialog bind="returning" title="Record this return" icon="check" tone="primary" confirm-label="Record return" state="note: ''" action-bind="target.action" message="This posts the books: the money leaves escrow. Then send it from the M-Pesa portal to the number the client paid from.">
                <p class="mt-3 rounded-xl bg-neutral-50 px-3 py-2 text-sm text-neutral-800"><span x-text="target.amount"></span> <span class="text-tertiary">to</span> <span class="font-semibold" x-text="target.name"></span> <span class="text-tertiary">at</span> <span class="font-mono" x-text="target.phone"></span></p>
                <label for="return-note" class="sr-only">{{ __('Note') }}</label>
                <textarea id="return-note" name="note" x-model="note" rows="2" maxlength="255" placeholder="{{ __('Optional note, e.g. the M-Pesa reference you sent it with') }}" class="mt-3 block w-full resize-none rounded-xl border border-neutral-300 px-3 py-2 text-sm focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25"></textarea>
            </x-confirm-dialog>
        @endif
    </div>
</x-staff-layout>
