@php
    $tabs = collect(['all' => __('All')] + $statuses)->map(fn ($label, $key) => ['label' => $label, 'count' => $key === 'all' ? $counts->sum() : ($counts[$key] ?? 0), 'on' => $status === $key, 'url' => route('admin.engagements.index', array_filter(['status' => $key, 'q' => $term, 'sort' => request('sort'), 'dir' => request('dir')]))])->values()->all();
    $chips = [$term !== '' ? ['label' => __('Search: :term', ['term' => $term]), 'remove' => ['q']] : null];
    $filtered = $term !== '' || $status !== 'all';
    $columns = [
        ['key' => 'hire', 'label' => 'Hire'],
        ['key' => 'amount', 'label' => 'Amount', 'sort' => 'amount', 'first' => 'desc', 'align' => 'right'],
        ['key' => 'funding', 'label' => 'Funding', 'class' => 'hidden md:table-cell'],
        ['key' => 'started', 'label' => 'Started', 'sort' => 'started', 'first' => 'desc', 'align' => 'right'],
    ];
@endphp
<x-staff-layout :title="__('Hires')">
    <div class="container mx-auto max-w-7xl px-4 py-8">
        <x-staff.header :title="__('Hires')" :description="__('Every engagement between a client and a freelancer, read-only. For spotting work that is stuck or at risk; disputes are handled under Payment disputes. Private messages are not shown.')" />

        <x-staff.toolbar :tabs="$tabs" :search="$term" :placeholder="__('Project title or either person\'s name')" :chips="$chips" :action="route('admin.engagements.index')" />

        @if ($engagements->isEmpty())
            <x-empty-state icon="chat-bubble-left-right" :title="$filtered ? __('No hires match') : __('No hires yet')" :description="$filtered ? __('Try a different search or filter.') : __('Hires appear here once a client accepts an application.')">
                @if ($filtered)<x-btn variant="secondary" :href="route('admin.engagements.index')" wire:navigate>{{ __('Clear filters') }}</x-btn>@endif
            </x-empty-state>
        @else
            <x-staff.table :columns="$columns" :sort="$sort" :dir="$dir" :paginator="$engagements" :summary="trans_choice(':count hire|:count hires', $engagements->total(), ['count' => number_format($engagements->total())])">
                @foreach ($engagements as $engagement)
                    @php $application = $engagement->application; @endphp
                    <x-staff.row :href="route('admin.engagements.show', $engagement)">
                        <td class="px-4">
                            <a href="{{ route('admin.engagements.show', $engagement) }}" wire:navigate class="block focus:outline-none focus-visible:underline">
                                <span class="flex flex-wrap items-center gap-2 font-semibold text-neutral-900">{{ $application->job?->title ?? __('A deleted project') }}<x-badge :tone="match ($engagement->status->value) { 'active' => 'blue', 'completed', 'settled' => 'green', 'disputed' => 'red', 'cancelled' => 'neutral', default => 'amber' }" class="px-2 py-0.5 text-xs font-medium">{{ $engagement->status->label() }}</x-badge></span>
                                <span class="mt-0.5 block text-xs font-normal text-tertiary">{{ $application->poster?->name ?? '—' }} <span aria-hidden="true">→</span> {{ $application->applicant?->name ?? '—' }}</span>
                            </a>
                        </td>
                        <td class="whitespace-nowrap px-4 text-right font-medium tabular-nums text-neutral-900"><x-money :amount="$engagement->agreed_amount" :decimals="0" /></td>
                        <td class="hidden whitespace-nowrap px-4 text-neutral-600 md:table-cell">{{ $engagement->payment_released_at ? __('Paid out') : ($engagement->payment_escrowed_at ? __('In escrow') : __('Not funded')) }}</td>
                        <td class="whitespace-nowrap px-4 text-right text-neutral-600">{{ ($engagement->started_at ?? $engagement->created_at)->format('M j, Y') }}</td>
                    </x-staff.row>
                @endforeach
            </x-staff.table>
        @endif
    </div>
</x-staff-layout>
