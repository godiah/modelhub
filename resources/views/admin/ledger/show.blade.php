@use('App\Support\Money')
<x-staff-layout :title="__('Posting :id', ['id' => $transaction->id])">
    <div class="container mx-auto max-w-7xl space-y-6 px-4 py-8">
        <a href="{{ route('admin.ledger.index') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm font-medium text-teal-700 hover:underline"><x-icon name="arrow-left" class="h-4 w-4" />{{ __('Ledger') }}</a>

        <x-staff.header class="!mb-0" :title="$transaction->description">
            <span class="font-mono">{{ $transaction->idempotency_key }}</span> · {{ $transaction->occurred_at->format('F j, Y · g:i:s A') }}
        </x-staff.header>

        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-3">
            <x-panel :title="__('Lines')" class="lg:col-span-2" flush>
                <table class="min-w-full text-sm">
                    <thead class="bg-neutral-50 text-xs uppercase tracking-wider text-neutral-500"><tr><th class="px-6 py-2.5 text-left font-semibold">{{ __('Account') }}</th><th class="px-4 py-2.5 text-right font-semibold">{{ __('Debit') }}</th><th class="px-6 py-2.5 text-right font-semibold">{{ __('Credit') }}</th></tr></thead>
                    <tbody class="divide-y divide-neutral-100">
                        @foreach ($transaction->entries as $entry)
                            <tr>
                                <td class="px-6 py-3"><span class="font-medium text-neutral-900">{{ $entry->account->name }}</span>@if ($entry->account->user)<span class="block text-xs text-tertiary">{{ $entry->account->user->name }}</span>@endif<span class="block font-mono text-xs text-tertiary">{{ $entry->account->code }}</span></td>
                                <td class="px-4 py-3 text-right tabular-nums text-neutral-900">{{ $entry->direction === 'debit' ? Money::formatMinor($entry->amount_minor) : '' }}</td>
                                <td class="px-6 py-3 text-right tabular-nums text-neutral-900">{{ $entry->direction === 'credit' ? Money::formatMinor($entry->amount_minor) : '' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t border-neutral-200 bg-neutral-50 font-semibold"><tr><td class="px-6 py-2.5 text-neutral-700">{{ __('Total') }}</td><td class="px-4 py-2.5 text-right tabular-nums">{{ Money::formatMinor($transaction->entries->where('direction', 'debit')->sum('amount_minor')) }}</td><td class="px-6 py-2.5 text-right tabular-nums">{{ Money::formatMinor($transaction->entries->where('direction', 'credit')->sum('amount_minor')) }}</td></tr></tfoot>
                </table>
            </x-panel>

            <x-panel :title="__('About this posting')">
                <dl class="space-y-3 text-sm">
                    <div><dt class="text-xs text-tertiary">{{ __('Type') }}</dt><dd class="mt-0.5 font-mono text-neutral-900">{{ $transaction->type }}</dd></div>
                    @if ($reference)<div><dt class="text-xs text-tertiary">{{ __('About') }}</dt><dd class="mt-0.5"><a href="{{ $reference['url'] }}" wire:navigate class="font-medium text-teal-700 hover:underline">{{ $reference['label'] }}</a></dd></div>@endif
                    @if ($transaction->staff)<div><dt class="text-xs text-tertiary">{{ __('Posted by') }}</dt><dd class="mt-0.5 text-neutral-900">{{ $transaction->staff->name }}</dd></div>@endif
                    @if ($transaction->meta)<div><dt class="text-xs text-tertiary">{{ __('Details') }}</dt><dd class="mt-0.5 space-y-0.5">@foreach ($transaction->meta as $key => $value)<span class="block text-xs text-neutral-700"><span class="text-tertiary">{{ $key }}:</span> {{ is_scalar($value) ? $value : json_encode($value) }}</span>@endforeach</dd></div>@endif
                </dl>
                <p class="mt-4 rounded-lg bg-neutral-50 px-3 py-2 text-xs text-tertiary">{{ __('A posting is never changed or deleted. A mistake is put right by a new, opposite posting.') }}</p>
            </x-panel>
        </div>
    </div>
</x-staff-layout>
