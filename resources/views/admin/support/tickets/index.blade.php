<x-staff-layout :title="__('Support requests')">
    <div class="container mx-auto max-w-7xl px-4 py-8">
        <x-staff.header :title="__('Support requests')" :description="__('What members have handed to staff from the assistant. The most urgent are first; a red time means the reply is already late.')" />

        <nav aria-label="{{ __('Filter by status') }}" class="-mx-4 mb-5 overflow-x-auto px-4 sm:mx-0 sm:px-0">
            <ul class="flex min-w-max items-center gap-2">
                @foreach ($tabs as $key => $label)
                    @php $active = $tab === $key; @endphp
                    <li>
                        <a href="{{ route('admin.support.tickets.index', array_filter(['tab' => $key, 'q' => $search])) }}" @if ($active) aria-current="true" @endif
                            @class([
                                'inline-flex items-center gap-2 rounded-full border px-3.5 py-1.5 text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40',
                                'border-teal-600 bg-teal-600 text-white' => $active,
                                'border-neutral-200 bg-white text-neutral-700 hover:border-neutral-300 hover:bg-neutral-50' => ! $active,
                            ])>
                            {{ __($label) }}
                            <span @class(['text-xs tabular-nums', 'text-teal-100' => $active, 'text-tertiary' => ! $active])>{{ $counts[$key] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <form method="GET" action="{{ route('admin.support.tickets.index') }}" class="mb-5 flex max-w-md gap-2">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <input type="search" name="q" value="{{ $search }}" placeholder="{{ __('Reference, member name or words in the request') }}" maxlength="80"
                class="block w-full rounded-xl border-neutral-300 text-sm focus:border-teal-600 focus:ring-teal-600">
            <x-btn variant="secondary">{{ __('Search') }}</x-btn>
        </form>

        @if ($tickets->isEmpty())
            <x-empty-state icon="inbox" :title="$tab === 'overdue' ? __('Nothing is overdue') : __('Nothing here')" :description="$tab === 'overdue' ? __('Every request is inside the reply time for its urgency.') : __('No support requests match this view.')" />
        @else
            <div class="space-y-3">
                @foreach ($tickets as $ticket)
                    @php
                        $due = $ticket->first_responded_at === null ? $ticket->first_response_due_at : $ticket->resolution_due_at;
                        $late = $ticket->isOverdue();
                    @endphp
                    <a href="{{ route('admin.support.tickets.show', $ticket) }}" class="block rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm transition hover:border-neutral-300 hover:shadow focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-mono text-sm font-semibold text-neutral-900">{{ $ticket->reference }}</span>
                                    <x-badge :tone="$ticket->severity->tone()" class="px-2 py-0.5 text-xs font-medium">{{ __($ticket->severity->label()) }}</x-badge>
                                    <x-badge :tone="$ticket->status->tone()" class="px-2 py-0.5 text-xs font-medium">{{ __($ticket->status->label()) }}</x-badge>
                                    <span class="text-sm text-tertiary">{{ __($ticket->category->label()) }}</span>
                                </div>
                                {{-- The member's words, escaped by Blade and cut short: never markup --}}
                                <p class="mt-1.5 max-w-3xl truncate text-sm text-neutral-700">{{ \Illuminate\Support\Str::limit($ticket->summary, 160) }}</p>
                                <p class="mt-1 text-xs text-tertiary">{{ $ticket->requester?->name ?? __('A deleted account') }} · {{ $ticket->created_at->diffForHumans() }} · {{ $ticket->assignee ? __('With :name', ['name' => $ticket->assignee->name]) : __('Nobody has it yet') }}</p>
                            </div>
                            @if ($due && $ticket->status->needsStaff())
                                <p @class(['shrink-0 text-xs font-medium', 'text-red-700' => $late, 'text-tertiary' => ! $late])>
                                    {{ $ticket->first_responded_at === null ? __('First reply') : __('Resolve') }}: {{ $late ? __(':time late', ['time' => $due->diffForHumans(syntax: \Carbon\CarbonInterface::DIFF_ABSOLUTE)]) : __('due in :time', ['time' => $due->diffForHumans(syntax: \Carbon\CarbonInterface::DIFF_ABSOLUTE)]) }}
                                </p>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
            <div class="mt-6">{{ $tickets->links() }}</div>
        @endif
    </div>
</x-staff-layout>
