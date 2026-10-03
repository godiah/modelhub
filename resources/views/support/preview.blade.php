@php
    use App\Support\SupportChat\PreviewScenarios as Scenarios;

    // Each entry is one frozen state of the panel, so the whole design can be reviewed side by side.
    $states = [
        [
            'title' => 'Opening on a page',
            'note' => 'Opens knowing the page you are on, and offers only questions that fit it. "Talk to a person" is always in the header.',
            'widget' => ['context' => Scenarios::contextFor('earnings.index')],
        ],
        [
            'title' => 'Where did it stop?',
            'note' => 'The trail card is the main answer: the step that stopped is called out in plain words, and the next step is a person.',
            'widget' => ['route' => 'earnings.index', 'pairs' => [['I paid but nothing happened', 'paid_nothing']]],
        ],
        [
            'title' => 'Why can\'t I…?',
            'note' => 'A missing requirement is amber, not red: it is something to fix, not an error. Numbers come from the account, not the model.',
            'widget' => ['route' => 'earnings.index', 'pairs' => [["Why can't I withdraw?", 'blocker']]],
        ],
        [
            'title' => 'A withdrawal in progress',
            'note' => 'Same trail, different story. The current step is hollow; what is still to come stays quiet.',
            'widget' => ['route' => 'earnings.index', 'pairs' => [['Where is my withdrawal?', 'withdrawal']]],
        ],
        [
            'title' => 'Where the escrow money is',
            'note' => 'One bar, one legend. Teal is money that reached someone; amber is money waiting on a decision.',
            'widget' => ['route' => 'engagements.show', 'pairs' => [['Where is the escrow money?', 'escrow']]],
        ],
        [
            'title' => 'A policy answer',
            'note' => 'It says what is policy and who decides. Sources sit under the answer with the date they were last updated.',
            'widget' => ['route' => 'earnings.index', 'pairs' => [['What is your refund policy?', 'policy']]],
        ],
        [
            'title' => 'Asking staff to decide',
            'note' => 'The assistant never says "approved". The card says who is deciding, and what happens if they agree.',
            'widget' => ['route' => 'payments.show', 'pairs' => [['Ask for a refund', 'refund']]],
        ],
        [
            'title' => 'Handed to a person',
            'note' => 'Says what was sent, when to expect a reply, and where it will arrive, so nobody has to wonder.',
            'widget' => ['route' => 'payments.show', 'pairs' => [['I paid but nothing happened', 'paid_nothing'], ['Ask staff to look at this', 'ask_staff']]],
        ],
        [
            'title' => 'Frustration',
            'note' => 'After a few misses or a sharp message, it stops guessing and offers a person with the chat attached.',
            'widget' => ['route' => 'earnings.index', 'pairs' => [['this is useless, nobody is helping', 'frustrated']]],
        ],
        [
            'title' => 'Not sure',
            'note' => 'When search finds nothing reliable it says so and offers staff, rather than inventing an answer.',
            'widget' => ['route' => 'earnings.index', 'pairs' => [['Can I change my store name to something with symbols?', 'unknown']]],
        ],
        [
            'title' => 'A PIN or code is blocked',
            'note' => 'Stops in the browser before anything is sent. The message says why, and the send button is off.',
            'widget' => ['route' => 'earnings.index', 'input' => 'my pin is 4821'],
        ],
        [
            'title' => 'Assistant switched off',
            'note' => 'Outage or kill switch: the panel becomes a request form, never a dead end.',
            'widget' => ['route' => 'earnings.index', 'state' => 'unavailable'],
        ],
    ];
@endphp

<x-app-layout crumb="Support assistant">
    <div class="container mx-auto max-w-7xl px-4 py-8">
        <header class="max-w-2xl">
            <h2 class="font-secondary text-2xl font-bold tracking-tight text-neutral-900">{{ __('Support assistant: design preview') }}</h2>
            <p class="mt-2 text-sm leading-relaxed text-neutral-600">
                {{ __('Every state of the chat panel, frozen so it can be judged side by side. Nothing here talks to a backend: the answers are scripted. Use the Help button at the bottom right to try the live panel; the chips and a few typed messages (refund, withdraw, "human") play the same scripts.') }}
            </p>
        </header>

        <nav aria-label="{{ __('Other support screens') }}" class="mt-6 grid max-w-4xl gap-4 sm:grid-cols-2">
            <div class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
                <h3 class="font-secondary text-sm font-semibold text-neutral-900">{{ __('What a member sees') }}</h3>
                <ul class="mt-3 space-y-2 text-sm">
                    <li><a href="{{ route('dev.support.requests') }}" class="font-medium text-teal-700 hover:underline">{{ __('Your requests') }}</a> <span class="text-neutral-500">· <a href="{{ route('dev.support.requests', ['state' => 'empty']) }}" class="hover:underline">{{ __('empty') }}</a></span></li>
                    <li><a href="{{ route('dev.support.request', 'SUP-1042') }}" class="font-medium text-teal-700 hover:underline">{{ __('A request: staff replied') }}</a> <span class="text-neutral-500">· <a href="{{ route('dev.support.request', 'SUP-1031') }}" class="hover:underline">{{ __('resolved') }}</a></span></li>
                    <li><a href="{{ route('dev.support.contact') }}" class="font-medium text-teal-700 hover:underline">{{ __('Contact form') }}</a> <span class="text-neutral-500">· {{ __('no sign-in needed') }}</span></li>
                    <li><a href="{{ route('dev.support.emails') }}" class="font-medium text-teal-700 hover:underline">{{ __('Emails and notifications') }}</a> <span class="text-neutral-500">· {{ __('seven emails') }}</span></li>
                </ul>
            </div>
            <div class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
                <h3 class="font-secondary text-sm font-semibold text-neutral-900">{{ __('What staff see') }} <span class="font-normal text-neutral-500">({{ __('needs a staff sign-in at /admin/login') }})</span></h3>
                <ul class="mt-3 space-y-2 text-sm">
                    <li><a href="{{ route('admin.dev.support.tickets') }}" class="font-medium text-teal-700 hover:underline">{{ __('Support queue') }}</a></li>
                    <li><a href="{{ route('admin.dev.support.ticket', 'SUP-1043') }}" class="font-medium text-teal-700 hover:underline">{{ __('Refund request: approve or decline') }}</a></li>
                    <li><a href="{{ route('admin.dev.support.replies') }}" class="font-medium text-teal-700 hover:underline">{{ __('Saved replies') }}</a> <span class="text-neutral-500">· <a href="{{ route('admin.dev.support.levels') }}" class="hover:underline">{{ __('service levels') }}</a></span></li>
                    <li><a href="{{ route('admin.dev.support.ticket', 'SUP-1051') }}" class="font-medium text-teal-700 hover:underline">{{ __('Unverified lock-out request') }}</a> <span class="text-neutral-500">· <a href="{{ route('admin.dev.support.ticket', 'SUP-1042') }}" class="hover:underline">{{ __('payment issue') }}</a></span></li>
                </ul>
            </div>
        </nav>

        <div class="mt-10 grid grid-cols-[repeat(auto-fill,minmax(min(100%,26rem),1fr))] gap-x-8 gap-y-12">
            @foreach ($states as $s)
                @php
                    $w = $s['widget'];
                    $route = $w['route'] ?? 'earnings.index';
                @endphp
                <section aria-labelledby="state-{{ $loop->index }}">
                    <h3 id="state-{{ $loop->index }}" class="font-secondary text-sm font-semibold text-neutral-900">{{ $s['title'] }}</h3>
                    <p class="mb-3 mt-1 max-w-[26rem] text-sm leading-snug text-neutral-600">{{ $s['note'] }}</p>

                    <x-support.widget mode="inline"
                        :state="$w['state'] ?? 'ready'"
                        :input="$w['input'] ?? ''"
                        :context="$w['context'] ?? Scenarios::contextFor($route)"
                        :transcript="isset($w['pairs']) ? Scenarios::conversation($route, $w['pairs']) : []" />
                </section>
            @endforeach
        </div>
    </div>
</x-app-layout>
