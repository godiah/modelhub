@php
    use App\Support\SupportChat\PreviewTickets as Tickets;

    $all = collect(Tickets::all())->map(fn ($t) => $t + ['due' => Tickets::dueMinutes($t['ref'])]);
    $isOpen = fn ($t) => $t['status'] !== 'resolved';
    $filters = [
        'open' => [__('Open'), $isOpen],
        'overdue' => [__('Overdue'), fn ($t) => $t['due'] !== null && $t['due'] < 0],
        'needs' => [__('Needs staff'), fn ($t) => $t['status'] === 'open'],
        'approvals' => [__('Approvals'), fn ($t) => $t['kind'] === 'approval' && $isOpen($t)],
        'unverified' => [__('Unverified'), fn ($t) => ! $t['verified'] && $isOpen($t)],
        'waiting' => [__('Waiting for member'), fn ($t) => $t['status'] === 'pending_member'],
        'resolved' => [__('Resolved'), fn ($t) => $t['status'] === 'resolved'],
    ];
    $tab = array_key_exists(request('tab'), $filters) ? request('tab') : 'open';
    $weight = ['urgent' => 0, 'high' => 1, 'normal' => 2, 'low' => 3];
    // Most overdue first, then by severity: what is already late comes before what is merely important
    $rows = $all->filter($filters[$tab][1])->sortBy(fn ($t) => [$weight[$t['severity']], $t['due'] ?? PHP_INT_MAX])->values();

    $tabItems = collect($filters)->map(fn ($f, $key) => ['label' => $f[0], 'count' => $all->filter($f[1])->count(), 'on' => $tab === $key, 'url' => request()->fullUrlWithQuery(['tab' => $key])])->values()->all();

    $overdue = $all->filter($filters['overdue'][1])->count();
    $columns = [
        ['key' => 'request', 'label' => 'Request'],
        ['key' => 'from', 'label' => 'From', 'class' => 'hidden md:table-cell'],
        ['key' => 'severity', 'label' => 'Severity'],
        ['key' => 'due', 'label' => 'Reply due', 'class' => 'hidden sm:table-cell'],
        ['key' => 'assigned', 'label' => 'Assigned', 'class' => 'hidden lg:table-cell'],
        ['key' => 'status', 'label' => 'Status'],
    ];
    $dot = ['urgent' => 'bg-red-600', 'high' => 'bg-amber-500', 'normal' => 'bg-neutral-400', 'low' => 'bg-neutral-300'];
    $team = Tickets::team();
@endphp

<x-staff-layout :title="__('Support requests')">
    <div class="container mx-auto max-w-7xl px-4 py-8">
        <x-staff.header :title="__('Support requests')" :description="__('Everything members and visitors have sent, from the assistant and the contact form. Unverified requests come from people who were not signed in: do not act on what they claim.')">
            <x-slot:actions>
                <x-btn variant="secondary" :href="route('admin.dev.support.replies')"><x-icon name="chat-bubble-text" class="h-4 w-4" />{{ __('Saved replies') }}</x-btn>
                <x-btn variant="secondary" :href="route('admin.dev.support.levels')"><x-icon name="clock" class="h-4 w-4" />{{ __('Service levels') }}</x-btn>
            </x-slot:actions>
        </x-staff.header>

        <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
            <x-stat-tile :label="__('Open')" :value="$all->filter($isOpen)->count()" :hint="__('Across every category')" icon="inbox" />
            <x-stat-tile :label="__('Overdue')" :value="$overdue" :hint="$overdue ? __('Past the reply time for their severity') : __('Nothing is late')" icon="clock" :tone="$overdue ? 'red' : 'teal'" :url="request()->fullUrlWithQuery(['tab' => 'overdue'])" />
            <x-stat-tile :label="__('Refunds to decide')" :value="$all->filter(fn ($t) => $t['kind'] === 'approval' && $isOpen($t))->count()" :hint="__('Requested by the assistant')" icon="banknotes" />
            <x-stat-tile :label="__('Unverified')" :value="$all->filter(fn ($t) => ! $t['verified'] && $isOpen($t))->count()" :hint="__('Check who they are first')" icon="shield-check" tone="amber" />
        </div>

        <x-staff.toolbar :tabs="$tabItems" :search="''" :placeholder="__('Reference, member, payment or receipt')" />

        @if ($rows->isEmpty())
            <x-empty-state icon="inbox" :title="$tab === 'overdue' ? __('Nothing is overdue') : __('Nothing here')" :description="$tab === 'overdue' ? __('Every request is inside the reply time for its severity.') : __('No requests match this filter.')" />
        @else
            {{-- Bulk selection, same behaviour and look as the other staff lists (preview: nothing is saved) --}}
            <div x-data="{
                selected: [], ids: @js($rows->pluck('ref')->all()), dialog: null, chosen: '', severity: 'normal', note: '',
                get all() { return this.ids.length > 0 && this.ids.every(id => this.selected.includes(id)); },
                get some() { return this.selected.length > 0 && ! this.all; },
                toggleAll() { this.selected = this.all ? [] : [...this.ids]; },
                noun(n) { return n + ' ' + (n === 1 ? 'request' : 'requests'); },
                close() { this.dialog = null; },
            }" @keydown.escape.window="dialog ? close() : (selected = [])">

                <x-staff.table :selectable="true" :columns="$columns" :summary="trans_choice(':count request|:count requests', $rows->count(), ['count' => $rows->count()]).' · '.($tab === 'overdue' ? __('most overdue first') : __('most urgent first'))">
                    @foreach ($rows as $t)
                        @php
                            $status = Tickets::staffStatus($t['status']);
                            $due = Tickets::dueLabel($t['due']);
                        @endphp
                        <x-staff.row :href="route('admin.dev.support.ticket', $t['ref'])" :select="$t['ref']">
                            <td class="px-4">
                                <a href="{{ route('admin.dev.support.ticket', $t['ref']) }}" wire:navigate class="block focus:outline-none focus-visible:underline">
                                    <span class="flex items-center gap-2">
                                        <span class="truncate font-semibold text-neutral-900">{{ $t['category'] }}</span>
                                        @if ($t['kind'] === 'approval')<x-badge tone="blue" class="px-2 py-0.5 text-xs font-medium">{{ __('Approval') }}</x-badge>@endif
                                    </span>
                                    <span class="block max-w-md truncate text-xs font-normal text-tertiary"><span class="font-mono">{{ $t['ref'] }}</span> · {{ $t['summary'] }}</span>
                                </a>
                            </td>
                            <td class="hidden px-4 md:table-cell">
                                <span class="block truncate text-neutral-800">{{ $t['verified'] ? $t['requester']['name'] : __('Not signed in') }}</span>
                                <span class="mt-0.5 flex items-center gap-1.5 whitespace-nowrap text-xs text-tertiary">
                                    @unless ($t['verified'])<x-badge tone="amber" class="px-1.5 py-0.5 text-[11px] font-medium">{{ __('Unverified') }}</x-badge>@endunless
                                    {{ $t['source'] === 'bot' ? __('Assistant') : __('Contact form') }}
                                </span>
                            </td>
                            <td class="px-4"><span class="inline-flex items-center gap-2 text-neutral-800"><span aria-hidden="true" class="h-2 w-2 rounded-full {{ $dot[$t['severity']] }}"></span>{{ __(ucfirst($t['severity'])) }}</span></td>
                            <td class="hidden whitespace-nowrap px-4 sm:table-cell">
                                @if ($due)
                                    <span @class(['inline-flex items-center gap-1.5 text-sm tabular-nums', 'font-semibold text-red-700' => $due['tone'] === 'red', 'font-medium text-amber-700' => $due['tone'] === 'amber', 'text-neutral-600' => $due['tone'] === 'neutral'])>
                                        <x-icon :name="$due['tone'] === 'red' ? 'exclamation-circle' : 'clock'" class="h-4 w-4" />{{ $due['label'] }}
                                    </span>
                                @else
                                    <span class="text-neutral-400">{{ $t['status'] === 'pending_member' ? __('Waiting for member') : '—' }}</span>
                                @endif
                            </td>
                            <td class="hidden px-4 text-neutral-700 lg:table-cell">{{ $t['assignee'] ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4"><x-badge :tone="$status['tone']" class="px-2 py-0.5 text-xs font-medium">{{ __($status['label']) }}</x-badge></td>
                        </x-staff.row>
                    @endforeach
                </x-staff.table>

                {{-- The bar that appears once something is ticked --}}
                <div x-show="selected.length > 0 && ! dialog" x-cloak x-transition:enter="transition duration-150 ease-out" x-transition:enter-start="translate-y-4 opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
                    role="region" aria-label="{{ __('Bulk actions') }}" class="fixed inset-x-4 bottom-4 z-40 mx-auto flex max-w-3xl flex-wrap items-center gap-x-4 gap-y-2 rounded-2xl border border-neutral-200 bg-white px-4 py-3 shadow-2xl">
                    <p class="text-sm font-semibold text-neutral-900" aria-live="polite"><span x-text="selected.length"></span> {{ __('selected') }}</p>
                    <div class="ml-auto flex flex-wrap items-center gap-2">
                        <x-btn type="button" size="sm" @click="dialog = 'assign'; chosen = ''">{{ __('Assign…') }}</x-btn>
                        <x-btn type="button" size="sm" variant="secondary" @click="dialog = 'severity'">{{ __('Change severity…') }}</x-btn>
                        <x-btn type="button" size="sm" variant="secondary" @click="dialog = 'resolve'">{{ __('Resolve…') }}</x-btn>
                        <button type="button" @click="selected = []" class="rounded-lg px-2.5 py-1.5 text-sm font-medium text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">{{ __('Clear') }}</button>
                    </div>
                </div>

                {{-- One dialog for every bulk action --}}
                <div x-show="dialog" x-cloak class="fixed inset-0 z-[55] flex items-center justify-center px-4" role="dialog" aria-modal="true">
                    <div x-show="dialog" x-transition.opacity @click="close()" class="absolute inset-0 bg-neutral-900/40" aria-hidden="true"></div>
                    <form x-show="dialog" x-trap.noscroll="dialog !== null" @submit.prevent="close(); selected = []" class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                        <h2 class="font-tertiary text-lg font-semibold text-neutral-900" x-text="dialog === 'assign' ? 'Assign ' + noun(selected.length) + '?' : (dialog === 'severity' ? 'Change severity of ' + noun(selected.length) + '?' : 'Resolve ' + noun(selected.length) + '?')"></h2>

                        <div x-show="dialog === 'assign'" class="mt-4">
                            <p class="text-sm text-tertiary">{{ __('They are told, and the request moves to their list. Anything already assigned is reassigned.') }}</p>
                            <fieldset class="mt-3 space-y-2">
                                <legend class="sr-only">{{ __('Assign to') }}</legend>
                                @foreach ($team as $person)
                                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-neutral-200 p-3 text-sm transition has-[:checked]:border-teal-600 has-[:checked]:bg-teal-50/60 hover:border-neutral-300">
                                        <input type="radio" name="assignee" value="{{ $person['name'] }}" x-model="chosen" class="text-teal-600 focus:ring-teal-600/30">
                                        <span aria-hidden="true" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-neutral-200 text-xs font-semibold text-neutral-700">{{ mb_substr($person['name'], 0, 1) }}</span>
                                        <span class="min-w-0 flex-1"><span class="block font-medium text-neutral-900">{{ $person['name'] }}</span><span class="block text-xs text-neutral-500">{{ $person['role'] }}</span></span>
                                        <span class="text-right text-xs tabular-nums text-neutral-500">{{ $person['open'] }} {{ __('open') }}@if ($person['overdue'])<span class="block font-medium text-red-700">{{ $person['overdue'] }} {{ __('overdue') }}</span>@endif</span>
                                    </label>
                                @endforeach
                            </fieldset>
                        </div>

                        <div x-show="dialog === 'severity'" class="mt-4">
                            <p class="text-sm text-tertiary">{{ __('Severity sets how soon a reply is due. Money and safety requests are raised automatically; lowering one needs a reason.') }}</p>
                            <fieldset class="mt-3 space-y-2">
                                @foreach (['urgent' => 'Urgent', 'high' => 'High', 'normal' => 'Normal', 'low' => 'Low'] as $key => $label)
                                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-neutral-200 p-3 text-sm transition has-[:checked]:border-teal-600 has-[:checked]:bg-teal-50/60 hover:border-neutral-300">
                                        <input type="radio" name="severity" value="{{ $key }}" x-model="severity" class="text-teal-600 focus:ring-teal-600/30">
                                        <span aria-hidden="true" class="h-2 w-2 rounded-full {{ $dot[$key] }}"></span><span class="font-medium text-neutral-900">{{ __($label) }}</span>
                                    </label>
                                @endforeach
                            </fieldset>
                        </div>

                        <div x-show="dialog === 'resolve'" class="mt-4">
                            <p class="text-sm text-tertiary">{{ __('Members are told it is resolved and can reply to reopen it. Choose why, so we learn from it.') }}</p>
                            <label for="bulk-why" class="mt-3 block text-sm font-medium text-neutral-800">{{ __('Why is it resolved?') }}</label>
                            <select id="bulk-why" class="mt-1 block w-full rounded-xl border-neutral-300 text-sm focus:border-teal-600 focus:ring-teal-600">
                                <option>{{ __('The assistant could have answered') }}</option>
                                <option>{{ __('A help article is missing') }}</option>
                                <option>{{ __('The assistant lacked a tool') }}</option>
                                <option>{{ __('Policy decision') }}</option>
                                <option>{{ __('A bug in ModelHub') }}</option>
                                <option>{{ __('Something else') }}</option>
                            </select>
                        </div>

                        <div class="mt-6 flex justify-end gap-3">
                            <x-btn type="button" variant="secondary" @click="close()">{{ __('Cancel') }}</x-btn>
                            <x-btn type="submit" x-bind:disabled="dialog === 'assign' && ! chosen">{{ __('Apply') }}</x-btn>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
</x-staff-layout>
