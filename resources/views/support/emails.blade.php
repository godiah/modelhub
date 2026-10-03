@php
    // key => [subject, who gets it and when]
    $emails = [
        'received' => ['We have your request (SUP-1042)', __('Sent at once to the person who made the request, from the chat or the contact form.')],
        'replied' => ['Grace replied to your request SUP-1042', __('Every time staff reply. The reply is quoted so it reads on its own.')],
        'needs_you' => ['SUP-1042 is waiting for your reply', __('Once, after a few days of silence while we are the ones waiting.')],
        'resolved' => ['Your request SUP-1042 is resolved', __('When staff resolve it. Asks how we did; replying reopens it.')],
        'refund_approved' => ['Your refund has been recorded (SUP-1043)', __('When staff approve a refund. Says the money is returned by hand, with no promised time.')],
        'refund_declined' => ['About your refund request (SUP-1043)', __('When staff decline. Gives their reason and a way to add something new.')],
        'identity_check' => ['We need to check it is you (SUP-1051)', __('To an unverified account-access request. Links to nothing, asks for nothing.')],
    ];

    $notes = [
        ['icon' => 'inbox', 'unread' => true, 'title' => __('Grace replied to your request SUP-1042'), 'text' => __('Hi Achieng, thanks for sending this. I can see your payment arrived but the licence wasn\'t created…'), 'when' => __('Today, 15:05')],
        ['icon' => 'check-circle', 'unread' => false, 'title' => __('Your refund has been recorded'), 'text' => __('Staff approved your refund request for Alarm Clock 01 – Clock Time PBR. The money is returned by hand.'), 'when' => __('Yesterday, 16:40')],
        ['icon' => 'clock', 'unread' => false, 'title' => __('SUP-1042 is waiting for your reply'), 'text' => __('Grace asked you to confirm the phone number you paid from.'), 'when' => __('2 Oct, 09:00')],
    ];
@endphp

<x-app-layout crumb="Support emails">
    <div class="container mx-auto max-w-7xl px-4 py-8" x-data="{ current: 'received', width: 'desktop', src(k) { return '{{ url('dev/support-ui/emails') }}/' + k; } }">
        <header class="max-w-2xl">
            <h2 class="font-tertiary text-2xl font-bold tracking-tight text-neutral-900">{{ __('Support emails and notifications') }}</h2>
            <p class="mt-2 text-sm leading-relaxed text-neutral-600">{{ __('Every email a request can send, in ModelHub\'s one email template. They say plainly what is done and what is not, and none of them promises a refund, a time or an outcome on money.') }}</p>
        </header>

        <div class="mt-6 grid grid-cols-1 items-start gap-6 lg:grid-cols-[22rem_minmax(0,1fr)]">
            <nav aria-label="{{ __('Emails') }}" class="rounded-2xl border border-neutral-200 bg-white shadow-sm">
                <ul class="divide-y divide-neutral-100">
                    @foreach ($emails as $key => [$subject, $when])
                        <li>
                            <button type="button" @click="current = '{{ $key }}'" :aria-current="(current === '{{ $key }}').toString()" :class="current === '{{ $key }}' ? 'bg-teal-50/60' : 'hover:bg-neutral-50'"
                                class="block w-full px-4 py-3 text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-secondary/40">
                                <span class="block text-sm font-semibold text-neutral-900">{{ $subject }}</span>
                                <span class="mt-0.5 block text-xs leading-snug text-neutral-500">{{ $when }}</span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            </nav>

            <div class="min-w-0">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                    <div class="inline-flex rounded-lg bg-neutral-200/70 p-0.5 text-sm font-medium" role="group" aria-label="{{ __('Preview width') }}">
                        <button type="button" @click="width = 'desktop'" :aria-pressed="(width === 'desktop').toString()" :class="width === 'desktop' ? 'bg-white text-neutral-900 shadow-sm' : 'text-neutral-600 hover:text-neutral-900'" class="rounded-md px-3 py-1.5 transition">{{ __('Desktop') }}</button>
                        <button type="button" @click="width = 'phone'" :aria-pressed="(width === 'phone').toString()" :class="width === 'phone' ? 'bg-white text-neutral-900 shadow-sm' : 'text-neutral-600 hover:text-neutral-900'" class="rounded-md px-3 py-1.5 transition">{{ __('Phone') }}</button>
                    </div>
                    <a :href="src(current)" target="_blank" class="inline-flex items-center gap-1.5 text-sm font-medium text-teal-700 hover:underline">{{ __('Open on its own') }}<x-icon name="arrow-top-right-on-square" class="h-4 w-4" /></a>
                </div>
                <div class="overflow-hidden rounded-2xl border border-neutral-200 bg-white p-2 shadow-sm">
                    <iframe :src="src(current)" title="{{ __('Email preview') }}" :style="width === 'phone' ? 'width:375px' : 'width:100%'" class="mx-auto block h-[46rem] max-w-full rounded-xl border-0 bg-paper transition-[width] duration-200 motion-reduce:transition-none"></iframe>
                </div>
            </div>
        </div>

        <section class="mt-12 max-w-3xl" aria-labelledby="in-app">
            <h3 id="in-app" class="font-tertiary text-lg font-semibold text-neutral-900">{{ __('In the app') }}</h3>
            <p class="mt-1 text-sm text-neutral-600">{{ __('The same events also show in Notifications, under a new category, "Support requests" (inbox icon), so members can filter for them like any other.') }}</p>
            <x-card class="mt-4 rounded-2xl">
                <ul class="divide-y divide-neutral-100">
                    @foreach ($notes as $n)
                        <li class="relative flex gap-4 px-5 py-4">
                            @if ($n['unread'])
                                <span aria-hidden="true" class="pointer-events-none absolute inset-0 bg-teal-50/50"></span>
                                <span aria-hidden="true" class="pointer-events-none absolute inset-y-0 left-0 w-0.5 bg-teal-600"></span>
                            @endif
                            <span class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $n['icon'] === 'check-circle' ? 'bg-teal-50 text-teal-700' : ($n['icon'] === 'clock' ? 'bg-amber-50 text-amber-700' : 'bg-blue-50 text-blue-700') }}"><x-icon :name="$n['icon']" class="h-5 w-5" /></span>
                            <div class="relative min-w-0 flex-1">
                                <p class="flex items-center gap-2 text-sm font-semibold text-neutral-900"><span class="truncate">{{ $n['title'] }}</span>@if ($n['unread'])<span class="h-2 w-2 shrink-0 rounded-full bg-teal-600"></span><span class="sr-only">{{ __('Unread') }}</span>@endif</p>
                                <p class="mt-1 text-sm leading-relaxed text-neutral-600">{{ $n['text'] }}</p>
                                <p class="mt-1.5 text-xs text-neutral-500">{{ $n['when'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </x-card>
        </section>
    </div>
</x-app-layout>
