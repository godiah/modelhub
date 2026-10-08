@php
    $dot = ['urgent' => 'bg-red-600', 'high' => 'bg-amber-500', 'normal' => 'bg-blue-500', 'low' => 'bg-neutral-300'];
    $when = [
        'urgent' => __('Only a person sets this. Staff raise a request to urgent when money or an account is at risk. The assistant and the member cannot.'),
        'high' => __('A payment under review, or paid with no licence; a withdrawal still "being sent" after :hours hours; a lock-out; words such as :words; or the same member writing again about the same record within 7 days (one step up from whatever it was).', ['hours' => $staleHours, 'words' => collect($urgentWords)->take(4)->implode(', ')]),
        'normal' => __('Most questions and requests about a payment, a withdrawal, a refund or a model.'),
        'low' => __('A request that is not about a record of theirs, such as a general question or feedback.'),
    ];
    $tiles = [
        ['label' => __('Waiting for staff'), 'value' => $status['waiting'], 'tone' => 'text-neutral-900'],
        ['label' => __('Overdue'), 'value' => $status['overdue'], 'tone' => $status['overdue'] ? 'text-red-700' : 'text-neutral-900'],
        ['label' => __('Due in the next 2 hours'), 'value' => $status['soon'], 'tone' => $status['soon'] ? 'text-amber-700' : 'text-neutral-900'],
        ['label' => __('Nobody has it yet'), 'value' => $status['unassigned'], 'tone' => $status['unassigned'] ? 'text-amber-700' : 'text-neutral-900'],
    ];
@endphp
<x-staff-layout :title="__('Service levels')">
    <div class="container mx-auto max-w-7xl px-4 py-8">
        <x-staff.header :title="__('Service levels')" :description="__('How quickly each kind of request should get a first reply, and when the clock runs. Members are told these times, so keep them honest.')" />

        <p class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ __('These times and hours are provisional: they are placeholders until the team decides the real ones. They live in one place in the code (config/support.php), and the clocks, the emails and the saved replies all read them from there, so changing a number there changes everything at once.') }}</p>

        <section aria-label="{{ __('Right now') }}" class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach ($tiles as $tile)
                <x-card class="rounded-2xl"><div class="p-5"><p class="text-sm text-tertiary">{{ $tile['label'] }}</p><p class="mt-1 font-tertiary text-3xl font-bold tabular-nums {{ $tile['tone'] }}">{{ number_format($tile['value']) }}</p></div></x-card>
            @endforeach
        </section>

        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-3">
            <div class="min-w-0 space-y-6 lg:col-span-2">
                <x-card class="rounded-2xl">
                    <div class="p-6">
                        <h2 class="font-semibold text-neutral-900">{{ __('Reply times') }}</h2>
                        <p class="mt-1 text-sm text-tertiary">{{ __('Counted in business hours. A request is overdue when its first reply is later than this, and after that when its resolution is.') }}</p>
                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="text-left text-xs text-tertiary">
                                    <tr><th scope="col" class="pb-2 pr-4 font-medium">{{ __('Urgency') }}</th><th scope="col" class="pb-2 pr-4 font-medium">{{ __('First reply within') }}</th><th scope="col" class="pb-2 font-medium">{{ __('Resolved within') }}</th></tr>
                                </thead>
                                <tbody class="divide-y divide-neutral-100">
                                    @foreach ($levels as $level)
                                        <tr>
                                            <td class="py-3 pr-4 align-top">
                                                <span class="flex items-center gap-2 font-medium text-neutral-900"><span aria-hidden="true" class="h-2 w-2 rounded-full {{ $dot[$level['severity']->value] }}"></span>{{ __($level['severity']->label()) }}</span>
                                                <span class="mt-0.5 block max-w-md text-xs text-tertiary">{{ $when[$level['severity']->value] }}</span>
                                            </td>
                                            <td class="py-3 pr-4 align-top text-neutral-800">{{ $level['first'] }}</td>
                                            <td class="py-3 align-top text-neutral-800">{{ $level['resolution'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </x-card>

                <x-card class="rounded-2xl">
                    <div class="space-y-3 p-6">
                        <h2 class="font-semibold text-neutral-900">{{ __('What members read') }}</h2>
                        <p class="text-sm text-tertiary">{{ __('Shown when they file a request and on their request page, always as an aim and never as a promise.') }}</p>
                        <ul class="space-y-2 text-sm">
                            @foreach ($levels as $level)
                                <li><span class="font-medium text-neutral-900">{{ __($level['severity']->label()) }}:</span> <span class="text-neutral-700">{{ $level['members_read'] }}</span></li>
                            @endforeach
                        </ul>
                    </div>
                </x-card>
            </div>

            <div class="space-y-6">
                <x-card class="rounded-2xl">
                    <div class="space-y-2 p-6 text-sm">
                        <h2 class="font-semibold text-neutral-900">{{ __('Support hours') }}</h2>
                        <p class="text-neutral-800">{{ $hours }}</p>
                        <p class="text-xs text-tertiary">{{ __('The clock only runs inside these hours. A request filed on Friday evening is counted from Monday morning. Public holidays are not excluded yet.') }}</p>
                    </div>
                </x-card>

                <x-card class="rounded-2xl">
                    <div class="space-y-3 p-6 text-sm">
                        <h2 class="font-semibold text-neutral-900">{{ __('The team right now') }}</h2>
                        @if ($team === [])
                            <p class="text-tertiary">{{ __('Nobody can answer requests yet.') }}</p>
                        @else
                            <table class="min-w-full">
                                <thead class="text-left text-xs text-tertiary"><tr><th scope="col" class="pb-1 pr-3 font-medium">{{ __('Person') }}</th><th scope="col" class="pb-1 pr-3 text-right font-medium">{{ __('Open') }}</th><th scope="col" class="pb-1 text-right font-medium">{{ __('Overdue') }}</th></tr></thead>
                                <tbody class="divide-y divide-neutral-100">
                                    @foreach ($team as $person)
                                        <tr><td class="py-1.5 pr-3 text-neutral-800">{{ $person['name'] }}</td><td class="py-1.5 pr-3 text-right tabular-nums">{{ $person['open'] }}</td><td @class(['py-1.5 text-right tabular-nums', 'font-medium text-red-700' => $person['overdue'] > 0])>{{ $person['overdue'] }}</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </x-card>
            </div>
        </div>
    </div>
</x-staff-layout>
