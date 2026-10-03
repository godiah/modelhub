@php
    use App\Support\SupportChat\PreviewTickets as Tickets;

    $levels = Tickets::serviceLevels();
    $team = Tickets::team();
    $all = collect(Tickets::all())->map(fn ($t) => $t + ['due' => Tickets::dueMinutes($t['ref'])]);
    $open = $all->filter(fn ($t) => $t['status'] !== 'resolved');
    $overdue = $open->filter(fn ($t) => $t['due'] !== null && $t['due'] < 0)->count();
    $soon = $open->filter(fn ($t) => $t['due'] !== null && $t['due'] >= 0 && $t['due'] <= 120)->count();
    $unassigned = $open->whereNull('assignee')->count();
    $dot = ['urgent' => 'bg-red-600', 'high' => 'bg-amber-500', 'normal' => 'bg-neutral-400', 'low' => 'bg-neutral-300'];
    $maxOpen = max(array_column($team, 'open'));

    $rules = [
        [__('A payment arrived but did not become a licence'), 'high', true],
        [__('A withdrawal has been "being sent" for more than 6 hours'), 'high', true],
        [__('An escrow refund is due and has not been recorded'), 'high', true],
        [__('A member says they are locked out'), 'high', true],
        [__('Mentions of a scam, paying outside ModelHub, stolen work or the police'), 'urgent', true],
        [__('The same member writes again about the same thing within 7 days'), 'one step up', false],
    ];
@endphp

<x-staff-layout :title="__('Service levels')">
    <div class="container mx-auto max-w-7xl px-4 py-8" x-data="{
        levels: @js(collect($levels)->map(fn ($l) => ['severity' => $l['severity'], 'first' => $l['first'], 'resolve' => $l['resolve']])->all()),
        original: null,
        days: [
            { name: 'Monday', on: true, open: '08:00', close: '18:00' }, { name: 'Tuesday', on: true, open: '08:00', close: '18:00' },
            { name: 'Wednesday', on: true, open: '08:00', close: '18:00' }, { name: 'Thursday', on: true, open: '08:00', close: '18:00' },
            { name: 'Friday', on: true, open: '08:00', close: '18:00' }, { name: 'Saturday', on: false, open: '09:00', close: '13:00' },
            { name: 'Sunday', on: false, open: '09:00', close: '13:00' },
        ],
        holidays: [{ date: '2026-12-12', name: 'Jamhuri Day' }, { date: '2026-12-25', name: 'Christmas Day' }, { date: '2026-12-26', name: 'Boxing Day' }],
        newDate: '', newName: '', saved: false,
        init() { this.original = JSON.stringify([this.levels, this.days, this.holidays]); },
        get dirty() { return JSON.stringify([this.levels, this.days, this.holidays]) !== this.original; },
        save() { this.original = JSON.stringify([this.levels, this.days, this.holidays]); this.saved = true; },
        addHoliday() { if (! this.newDate || ! this.newName.trim()) return; this.holidays.push({ date: this.newDate, name: this.newName.trim() }); this.holidays.sort((a, b) => a.date.localeCompare(b.date)); this.newDate = ''; this.newName = ''; },
        clock(t) { const [h, m] = t.split(':').map(Number); const s = h >= 12 ? 'pm' : 'am'; const hh = h % 12 === 0 ? 12 : h % 12; return hh + (m ? ':' + String(m).padStart(2, '0') : '') + s; },
        get promise() {
            const on = this.days.filter(d => d.on);
            if (! on.length) return 'We are not taking requests right now.';
            const same = on.every(d => d.open === on[0].open && d.close === on[0].close);
            const weekdays = on.length === 5 && on.every(d => ! ['Saturday', 'Sunday'].includes(d.name));
            const when = weekdays ? 'weekdays' : on.map(d => d.name.slice(0, 3)).join(', ');
            return 'First reply within ' + this.levels[1].first + ' (' + when + (same ? ', ' + this.clock(on[0].open) + ' to ' + this.clock(on[0].close) : '') + ' EAT).';
        },
    }">
        <x-staff.header :title="__('Service levels')" :description="__('How quickly each kind of request should get a first reply, and when the clock runs. Members are told these times, so keep them honest.')">
            <x-slot:badges><x-badge tone="neutral" class="px-2.5 py-0.5 text-xs font-medium tracking-normal">{{ __('Super admin') }}</x-badge></x-slot:badges>
        </x-staff.header>

        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-3">
            <div class="min-w-0 space-y-6 lg:col-span-2">
                <x-panel :title="__('Reply times')" :description="__('Counted in business hours. A request is overdue when its first reply is later than this.')">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-left text-xs text-neutral-500">
                                <tr><th scope="col" class="pb-2 pr-4 font-medium">{{ __('Severity') }}</th><th scope="col" class="pb-2 pr-4 font-medium">{{ __('First reply within') }}</th><th scope="col" class="pb-2 font-medium">{{ __('Resolved within') }}</th></tr>
                            </thead>
                            <tbody class="divide-y divide-neutral-100">
                                @foreach ($levels as $i => $level)
                                    <tr>
                                        <td class="py-3 pr-4 align-top">
                                            <span class="flex items-center gap-2 font-medium text-neutral-900"><span aria-hidden="true" class="h-2 w-2 rounded-full {{ $dot[$level['severity']] }}"></span>{{ __($level['label']) }}</span>
                                            <span class="mt-0.5 block max-w-[16rem] text-xs text-neutral-500">{{ $level['when'] }}</span>
                                        </td>
                                        <td class="py-3 pr-4 align-top"><label class="sr-only" for="first-{{ $i }}">{{ __('First reply for :s', ['s' => $level['label']]) }}</label><input id="first-{{ $i }}" type="text" x-model="levels[{{ $i }}].first" class="w-40 rounded-xl border-neutral-300 text-sm focus:border-teal-600 focus:ring-teal-600"></td>
                                        <td class="py-3 align-top"><label class="sr-only" for="res-{{ $i }}">{{ __('Resolution for :s', ['s' => $level['label']]) }}</label><input id="res-{{ $i }}" type="text" x-model="levels[{{ $i }}].resolve" class="w-40 rounded-xl border-neutral-300 text-sm focus:border-teal-600 focus:ring-teal-600"></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="mt-4 rounded-lg bg-neutral-50 px-3 py-2 text-sm text-neutral-700"><span class="font-medium text-neutral-900">{{ __('What members read:') }}</span> <span x-text="promise"></span></p>
                </x-panel>

                <x-panel :title="__('Support hours')" :description="__('All times are East Africa Time (EAT). Outside these hours the assistant still takes requests and says when a person will answer.')">
                    <ul class="divide-y divide-neutral-100">
                        <template x-for="(d, i) in days" :key="d.name">
                            <li class="flex flex-wrap items-center gap-x-4 gap-y-2 py-2.5">
                                <label class="flex w-36 cursor-pointer items-center gap-3 text-sm font-medium text-neutral-900">
                                    <input type="checkbox" x-model="d.on" class="h-4 w-4 rounded border-neutral-300 text-teal-600 focus:ring-teal-600/30"><span x-text="d.name"></span>
                                </label>
                                <div class="flex items-center gap-2 text-sm" x-show="d.on">
                                    <input type="time" x-model="d.open" :aria-label="d.name + ' opens'" class="rounded-xl border-neutral-300 py-1.5 text-sm focus:border-teal-600 focus:ring-teal-600">
                                    <span class="text-neutral-500">{{ __('to') }}</span>
                                    <input type="time" x-model="d.close" :aria-label="d.name + ' closes'" class="rounded-xl border-neutral-300 py-1.5 text-sm focus:border-teal-600 focus:ring-teal-600">
                                </div>
                                <span x-show="! d.on" class="text-sm text-neutral-500">{{ __('Closed') }}</span>
                            </li>
                        </template>
                    </ul>

                    <h3 class="mt-6 text-sm font-semibold text-neutral-900">{{ __('Public holidays') }}</h3>
                    <p class="mt-0.5 text-xs text-neutral-500">{{ __('The clock stops on these days.') }}</p>
                    <ul class="mt-3 flex flex-wrap gap-2">
                        <template x-for="(h, i) in holidays" :key="h.date">
                            <li class="inline-flex items-center gap-2 rounded-full border border-neutral-200 bg-neutral-50 py-1 pl-3 pr-1.5 text-sm text-neutral-700">
                                <span><span class="font-medium text-neutral-900" x-text="h.name"></span> <span class="text-neutral-500" x-text="new Date(h.date + 'T00:00').toLocaleDateString('en-GB', { day: 'numeric', month: 'short' })"></span></span>
                                <button type="button" @click="holidays.splice(i, 1)" :aria-label="'Remove ' + h.name" class="rounded-full p-1 text-neutral-400 hover:bg-neutral-200 hover:text-neutral-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40"><x-icon name="x-mark" class="h-3.5 w-3.5" /></button>
                            </li>
                        </template>
                    </ul>
                    <form class="mt-3 flex flex-wrap items-end gap-3" @submit.prevent="addHoliday()">
                        <div><label for="h-date" class="block text-xs font-medium text-neutral-600">{{ __('Date') }}</label><input id="h-date" type="date" x-model="newDate" class="mt-1 rounded-xl border-neutral-300 py-1.5 text-sm focus:border-teal-600 focus:ring-teal-600"></div>
                        <div class="min-w-[10rem] flex-1 sm:max-w-xs"><label for="h-name" class="block text-xs font-medium text-neutral-600">{{ __('Name') }}</label><input id="h-name" type="text" x-model="newName" class="mt-1 block w-full rounded-xl border-neutral-300 py-1.5 text-sm focus:border-teal-600 focus:ring-teal-600"></div>
                        <x-btn type="submit" variant="secondary" size="sm" x-bind:disabled="! newDate || ! newName.trim()">{{ __('Add holiday') }}</x-btn>
                    </form>

                    <x-slot:footer>
                        <p class="text-xs text-neutral-500">{{ __('Changes are recorded in the activity log.') }}</p>
                        <div class="flex items-center gap-3">
                            <span x-show="saved && ! dirty" x-cloak class="flex items-center gap-1.5 text-sm text-teal-700"><x-icon name="check" class="h-4 w-4" />{{ __('Saved') }}</span>
                            <x-btn type="button" x-bind:disabled="! dirty" @click="save()">{{ __('Save service levels') }}</x-btn>
                        </div>
                    </x-slot:footer>
                </x-panel>

                <x-panel :title="__('Raised automatically')" :description="__('These make a request more urgent without anyone having to notice. They are not settings: they exist so money and safety problems are never left to chance.')" flush>
                    <ul class="divide-y divide-neutral-100">
                        @foreach ($rules as [$rule, $to, $always])
                            <li class="flex flex-wrap items-center justify-between gap-3 px-6 py-3.5 text-sm">
                                <span class="min-w-0 flex-1 text-neutral-800">{{ $rule }}</span>
                                <span class="flex items-center gap-2">
                                    <x-badge :tone="$to === 'urgent' ? 'red' : ($to === 'high' ? 'amber' : 'neutral')" class="px-2 py-0.5 text-xs font-medium">{{ $to === 'one step up' ? __('One step up') : __(ucfirst($to)) }}</x-badge>
                                    @if ($always)<span class="text-xs text-neutral-500">{{ __('Always on') }}</span>@endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </x-panel>
            </div>

            <div class="min-w-0 space-y-6">
                <x-panel :title="__('Right now')">
                    <dl class="space-y-3">
                        <div class="flex items-center justify-between gap-3"><dt class="text-sm text-neutral-600">{{ __('Overdue') }}</dt><dd @class(['font-tertiary text-2xl font-bold tabular-nums', 'text-red-700' => $overdue > 0, 'text-neutral-900' => $overdue === 0])>{{ $overdue }}</dd></div>
                        <div class="flex items-center justify-between gap-3"><dt class="text-sm text-neutral-600">{{ __('Due in the next 2 hours') }}</dt><dd class="font-tertiary text-2xl font-bold tabular-nums text-neutral-900">{{ $soon }}</dd></div>
                        <div class="flex items-center justify-between gap-3"><dt class="text-sm text-neutral-600">{{ __('Nobody assigned') }}</dt><dd class="font-tertiary text-2xl font-bold tabular-nums text-neutral-900">{{ $unassigned }}</dd></div>
                    </dl>
                    <x-btn variant="secondary" size="sm" class="mt-4" :href="route('admin.dev.support.tickets', ['tab' => 'overdue'])">{{ __('See overdue requests') }}</x-btn>
                </x-panel>

                <x-panel :title="__('Who has what')" :description="__('Open requests per person. Red is overdue.')">
                    <ul class="space-y-4">
                        @foreach ($team as $person)
                            <li>
                                <div class="flex items-baseline justify-between gap-3 text-sm"><span class="font-medium text-neutral-900">{{ $person['name'] }} <span class="font-normal text-neutral-500">· {{ $person['role'] }}</span></span><span class="tabular-nums text-neutral-700">{{ $person['open'] }}</span></div>
                                <div class="mt-1.5 flex h-2 gap-0.5 overflow-hidden rounded-full bg-neutral-100" role="img" aria-label="{{ $person['open'] }} {{ __('open') }}, {{ $person['overdue'] }} {{ __('overdue') }}">
                                    <span class="h-full rounded-full bg-teal-600" style="width: {{ ($person['open'] - $person['overdue']) / $maxOpen * 100 }}%"></span>
                                    @if ($person['overdue'])<span class="h-full rounded-full bg-red-500" style="width: {{ $person['overdue'] / $maxOpen * 100 }}%"></span>@endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </x-panel>

                <x-panel :title="__('This week')">
                    <dl class="space-y-2.5 text-sm">
                        <div class="flex items-baseline justify-between gap-3"><dt class="text-neutral-500">{{ __('First reply on time') }}</dt><dd class="font-medium tabular-nums text-neutral-900">92%</dd></div>
                        <div class="flex items-baseline justify-between gap-3"><dt class="text-neutral-500">{{ __('Median first reply') }}</dt><dd class="font-medium tabular-nums text-neutral-900">1 h 40 min</dd></div>
                        <div class="flex items-baseline justify-between gap-3"><dt class="text-neutral-500">{{ __('Resolved') }}</dt><dd class="font-medium tabular-nums text-neutral-900">14</dd></div>
                        <div class="flex items-baseline justify-between gap-3"><dt class="text-neutral-500">{{ __('Reopened by the member') }}</dt><dd class="font-medium tabular-nums text-neutral-900">1</dd></div>
                    </dl>
                </x-panel>
            </div>
        </div>
    </div>
</x-staff-layout>
