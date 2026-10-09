@php
    $dot = ['urgent' => 'bg-red-600', 'high' => 'bg-amber-500', 'normal' => 'bg-blue-500', 'low' => 'bg-neutral-300'];
    $pill = ['urgent' => 'bg-red-50 text-red-800 ring-red-200', 'high' => 'bg-amber-50 text-amber-800 ring-amber-200', 'normal' => 'bg-blue-50 text-blue-800 ring-blue-200', 'low' => 'bg-neutral-50 text-neutral-700 ring-neutral-200'];
    $when = [
        'urgent' => __('Only a person sets this. Staff raise a request to urgent when money or an account is at risk. The assistant and the member cannot.'),
        'high' => __('A payment under review, or paid with no licence; a withdrawal still "being sent" after :hours hours; a lock-out; words such as :words; or the same member writing again about the same record within 7 days (one step up from whatever it was).', ['hours' => $staleHours, 'words' => collect($urgentWords)->take(4)->implode(', ')]),
        'normal' => __('Most questions and requests about a payment, a withdrawal, a refund or a model.'),
        'low' => __('A request that is not about a record of theirs, such as a general question or feedback.'),
    ];
    $tiles = [
        ['label' => __('Waiting for staff'), 'value' => $status['waiting'], 'tab' => 'queue', 'hint' => __('Open the queue'), 'alarm' => null],
        ['label' => __('Overdue'), 'value' => $status['overdue'], 'tab' => 'overdue', 'hint' => $status['overdue'] ? __('Past their reply time') : __('Nothing is late'), 'alarm' => $status['overdue'] ? 'red' : null],
        ['label' => __('Due in the next 2 hours'), 'value' => $status['soon'], 'tab' => 'soon', 'hint' => $status['soon'] ? __('Get to these next') : __('Nothing close to late'), 'alarm' => $status['soon'] ? 'amber' : null],
        ['label' => __('Nobody has it yet'), 'value' => $status['unassigned'], 'tab' => 'unassigned', 'hint' => $status['unassigned'] ? __('Someone needs to take these') : __('Everything has an owner'), 'alarm' => $status['unassigned'] ? 'amber' : null],
    ];
    $firstMax = max(1, collect($levels)->max('first_minutes'));
    $openMax = max(1, collect($team)->max('open'));
    $sample = collect($levels)->firstWhere(fn ($l) => $l['severity']->value === 'normal') ?? $levels->first();
@endphp
<x-staff-layout :title="__('Service levels')">
    <div class="container mx-auto max-w-7xl px-4 py-8">
        <x-staff.header :title="__('Service levels')" :description="__('How quickly each kind of request should get a first reply, and when the clock runs. Members are told these times, so keep them honest.')" />

        <p class="mb-6 flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm text-amber-900"><x-icon name="clock" class="mt-0.5 h-4 w-4 shrink-0" /><span>{{ __('These times and hours are provisional: they are placeholders until the team decides the real ones. They live in one place in the code (config/support.php), and the clocks, the emails and the saved replies all read them from there, so changing a number there changes everything at once.') }}</span></p>

        <section aria-label="{{ __('Right now') }}" class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach ($tiles as $tile)
                <a href="{{ route('admin.support.tickets.index', ['tab' => $tile['tab']]) }}" class="group block rounded-2xl border bg-white p-5 shadow-sm transition hover:shadow focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40 {{ $tile['alarm'] === 'red' ? 'border-red-200' : ($tile['alarm'] === 'amber' ? 'border-amber-200' : 'border-neutral-200 hover:border-neutral-300') }}">
                    <p class="text-sm text-tertiary">{{ $tile['label'] }}</p>
                    <p @class(['mt-1 font-tertiary text-3xl font-bold tabular-nums', 'text-red-700' => $tile['alarm'] === 'red', 'text-amber-700' => $tile['alarm'] === 'amber', 'text-neutral-900' => ! $tile['alarm']])>{{ number_format($tile['value']) }}</p>
                    <p class="mt-1 flex items-center justify-between gap-2 text-xs text-tertiary"><span>{{ $tile['hint'] }}</span><span aria-hidden="true" class="text-teal-700 opacity-0 transition group-hover:opacity-100 group-focus-visible:opacity-100">→</span></p>
                </a>
            @endforeach
        </section>

        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-3">
            <div class="min-w-0 space-y-6 lg:col-span-2">
                <x-card class="rounded-2xl">
                    <div class="p-6">
                        <h2 class="font-semibold text-neutral-900">{{ __('Reply times') }}</h2>
                        <p class="mt-1 text-sm text-tertiary">{{ __('Counted in business hours. A request is overdue when its first reply is later than this, and after that when its resolution is.') }}</p>
                        <ul class="mt-2 divide-y divide-neutral-100">
                            @foreach ($levels as $level)
                                @php $key = $level['severity']->value; @endphp
                                <li class="py-4">
                                    <div class="grid items-start gap-x-6 gap-y-3 sm:grid-cols-[7.5rem_minmax(0,1fr)_minmax(0,1fr)]">
                                        <span class="inline-flex w-fit items-center gap-2 rounded-full px-3 py-1 text-sm font-semibold ring-1 {{ $pill[$key] }}"><span aria-hidden="true" class="h-2 w-2 rounded-full {{ $dot[$key] }}"></span>{{ __($level['severity']->label()) }}</span>
                                        <div>
                                            <p class="text-xs text-tertiary">{{ __('First reply within') }}</p>
                                            <p class="mt-0.5 font-medium text-neutral-900">{{ $level['first'] }}</p>
                                            <div class="mt-1.5 h-1.5 max-w-[12rem] overflow-hidden rounded-full bg-neutral-100" aria-hidden="true"><div class="h-full rounded-full {{ $dot[$key] }}" style="width: {{ max(4, round($level['first_minutes'] / $firstMax * 100)) }}%"></div></div>
                                        </div>
                                        <div>
                                            <p class="text-xs text-tertiary">{{ __('Resolved within') }}</p>
                                            <p class="mt-0.5 font-medium text-neutral-900">{{ $level['resolution'] }}</p>
                                        </div>
                                    </div>
                                    <details class="mt-2 sm:ml-[9rem]">
                                        <summary class="cursor-pointer text-xs font-medium text-teal-700 hover:underline">{{ __('When is a request :level?', ['level' => strtolower(__($level['severity']->label()))]) }}</summary>
                                        <p class="mt-1 max-w-xl text-xs text-tertiary">{{ $when[$key] }}</p>
                                    </details>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </x-card>

                <x-card class="rounded-2xl">
                    <div class="space-y-3 p-6">
                        <h2 class="font-semibold text-neutral-900">{{ __('What members read') }}</h2>
                        <p class="text-sm text-tertiary">{{ __('Shown when they file a request and on their request page, always as an aim and never as a promise.') }}</p>
                        <blockquote class="rounded-xl border-l-4 border-teal-600 bg-neutral-50 px-4 py-3 text-sm text-neutral-800">{{ $sample['members_read'] }}</blockquote>
                        <details>
                            <summary class="cursor-pointer text-xs font-medium text-teal-700 hover:underline">{{ __('See the wording for each urgency') }}</summary>
                            <ul class="mt-2 space-y-2 text-sm">
                                @foreach ($levels as $level)
                                    <li><span class="font-medium text-neutral-900">{{ __($level['severity']->label()) }}:</span> <span class="text-neutral-700">{{ $level['members_read'] }}</span></li>
                                @endforeach
                            </ul>
                        </details>
                    </div>
                </x-card>
            </div>

            <div class="space-y-6">
                <x-card class="rounded-2xl">
                    <div class="space-y-3 p-6 text-sm">
                        <div class="flex items-center justify-between gap-2">
                            <h2 class="font-semibold text-neutral-900">{{ __('Support hours') }}</h2>
                            <span @class(['inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium', 'bg-green-100 text-green-800' => $openNow, 'bg-neutral-100 text-neutral-700' => ! $openNow])><span aria-hidden="true" @class(['h-1.5 w-1.5 rounded-full', 'bg-green-600' => $openNow, 'bg-neutral-400' => ! $openNow])></span>{{ $openNow ? __('Open now') : __('Closed now') }}</span>
                        </div>
                        <p class="text-neutral-800">{{ $hours }}</p>
                        <ol class="grid grid-cols-7 gap-1 text-center text-xs" aria-label="{{ __('The week') }}">
                            @foreach ($week as $day)
                                <li @class(['rounded-lg px-1 py-1.5', 'bg-teal-50 text-teal-800' => $day['open'], 'bg-neutral-50 text-neutral-400' => ! $day['open'], 'ring-2 ring-teal-600' => $day['today']])>
                                    <span class="block font-medium">{{ __($day['name']) }}</span>
                                    <span class="mt-0.5 block text-[10px]">{{ $day['open'] ? __('Open') : __('Off') }}</span>
                                </li>
                            @endforeach
                        </ol>
                        @unless ($openNow)<p class="text-xs text-neutral-700">{{ __('Opens again :when.', ['when' => $opensAt->format('l \a\t g:i A')]) }}</p>@endunless
                        <p class="text-xs text-tertiary">{{ __('The clock only runs inside these hours. A request filed on Friday evening is counted from Monday morning. Public holidays are not excluded yet.') }}</p>
                    </div>
                </x-card>

                <x-card class="rounded-2xl">
                    <div class="space-y-3 p-6 text-sm">
                        <h2 class="font-semibold text-neutral-900">{{ __('The team right now') }}</h2>
                        @if ($team === [])
                            <p class="text-tertiary">{{ __('Nobody can answer requests yet.') }}</p>
                        @else
                            <ul class="space-y-3">
                                @foreach ($team as $person)
                                    <li>
                                        <div class="flex items-center gap-2">
                                            <x-user-avatar :user="$person['person']" size="h-6 w-6" />
                                            <span class="min-w-0 flex-1 truncate text-neutral-800">{{ $person['name'] }}</span>
                                            <span class="tabular-nums text-neutral-700">{{ $person['open'] }} <span class="text-xs text-tertiary">{{ __('Open') }}</span></span>
                                            <span @class(['w-20 text-right tabular-nums', 'font-medium text-red-700' => $person['overdue'] > 0, 'text-tertiary' => $person['overdue'] === 0])>{{ $person['overdue'] }} <span class="text-xs">{{ __('Overdue') }}</span></span>
                                        </div>
                                        <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-neutral-100" aria-hidden="true"><div class="h-full rounded-full {{ $person['overdue'] > 0 ? 'bg-red-500' : 'bg-teal-500' }}" style="width: {{ $person['open'] === 0 ? 0 : max(6, round($person['open'] / $openMax * 100)) }}%"></div></div>
                                    </li>
                                @endforeach
                            </ul>
                            @if ($status['unassigned'] > 0)
                                <a href="{{ route('admin.support.tickets.index', ['tab' => 'unassigned']) }}" class="block rounded-lg bg-amber-50 px-3 py-2 text-xs font-medium text-amber-900 hover:bg-amber-100">{{ trans_choice(':count request has no owner yet|:count requests have no owner yet', $status['unassigned'], ['count' => $status['unassigned']]) }} →</a>
                            @endif
                        @endif
                    </div>
                </x-card>
            </div>
        </div>
    </div>
</x-staff-layout>
