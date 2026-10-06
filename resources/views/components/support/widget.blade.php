@props([
    // 'dock': launcher + panel fixed to the right edge (the real widget). 'inline': a static panel, used by the design preview.
    'mode' => 'dock',
    // 'ready' or 'unavailable' (kill switch / outage: the panel turns into a plain request form).
    'state' => 'ready',
    // A transcript to start from (list of messages). Empty = start from the greeting for the current page.
    'transcript' => [],
    // Pre-fill the composer (used to show the PIN guard).
    'input' => '',
    // Override the page context (see PreviewScenarios::contextFor). Null = work it out from the current route.
    'context' => null,
])

@php
    use App\Support\SupportChat\PreviewScenarios;

    $dock = $mode === 'dock';
    // Live: the real assistant answers (SUPPORT_ENABLED). Otherwise the widget plays the scripted preview.
    $live = $dock && config('support.enabled');
    $routeName = request()->route()?->getName();
    $context ??= PreviewScenarios::contextFor($routeName);

    if ($live) {
        // Only what the assistant can honestly do today: explain how ModelHub works. It cannot see the member's account yet.
        // The panel opens with a short greeting by first name, then the frequently asked questions. It claims nothing about what the
        // assistant can see. Each pill's label is sent as the question, so each one is worded to be answered from the help articles
        // (the topics members ask about most: withdrawing, licences, refunds, sign-in). Four at most, so they stay a glance, not a menu.
        $firstName = \Illuminate\Support\Str::of((string) auth()->user()?->name)->trim()->before(' ')->toString();
        $context = [
            'key' => $context['key'],
            'label' => $context['label'],
            'greeting' => $firstName !== ''
                ? __('Hi :name, how may we help you today?', ['name' => $firstName])
                : __('Hi, how may we help you today?'),
            'cards' => [],
            'chips' => [
                ['label' => __('What fee do I pay to withdraw?'), 'key' => 'ask'],
                ['label' => __('Standard vs Extended licence?'), 'key' => 'ask'],
                ['label' => __('Can I get a refund?'), 'key' => 'ask'],
                ['label' => __('How do I reset my password?'), 'key' => 'ask'],
            ],
        ];
    }

    $config = [
        'live' => $live,
        'endpoint' => $live ? route('support.chat') : null,
        'userId' => auth()->id(),
        'supportEmail' => config('mail.support_address'),
        'routeKey' => $routeName && preg_match('/^[a-z0-9._-]{1,64}$/', $routeName) ? $routeName : null,
        'mode' => $mode,
        'state' => $state,
        'transcript' => $transcript,
        'input' => $input,
        'context' => $context,
        // The scripted answers (invented payments and all) are for the design preview only: never shipped in live mode
        'scenarios' => $live ? [] : PreviewScenarios::all(),
        // Chat history (live only). The id placeholder is filled in by the browser.
        'endpoints' => $live ? [
            'list' => route('support.conversations.index'),
            'show' => route('support.conversations.show', '__id__'),
            'destroy' => route('support.conversations.destroy', '__id__'),
            'article' => route('support.articles.show', '__slug__'),
        ] : null,
    ];
    $uid = 'support-'.\Illuminate\Support\Str::random(6);
@endphp

{{--
    Support assistant widget. VISUAL PREVIEW: it plays scripted conversations (App\Support\SupportChat\PreviewScenarios)
    and talks to nothing. The assistant's answers are data (messages with cards), rendered by the card components in
    components/support/card, so the real assistant can stream the same shapes later without changing this markup.
--}}
<div x-data="supportChat(@js($config))" @keydown.escape.window="if (mode === 'dock' && open) open = false" @support-open.window="open = true"
    @class(['print:hidden' => $dock, 'contents' => $dock])>

    @if ($dock)
        <button type="button" x-ref="launcher" x-show="!open" x-cloak @click="open = true" aria-haspopup="dialog" aria-controls="{{ $uid }}"
            :style="{ bottom: 'calc(1.25rem + ' + lift + 'px)' }"
            class="fixed bottom-5 right-5 z-50 inline-flex items-center gap-2 rounded-full bg-teal-700 py-3 pl-4 pr-5 font-secondary text-sm font-semibold text-white shadow-lg shadow-teal-900/20 transition hover:bg-teal-800 focus:outline-none focus-visible:ring-4 focus-visible:ring-teal-600/40">
            <x-icon name="chat-bubble-text" class="h-5 w-5" />{{ __('Help') }}
        </button>
    @endif

    <section id="{{ $uid }}" role="dialog" aria-label="{{ __('Support assistant') }}"
        @if ($dock)
            x-show="open" x-cloak x-trap.noscroll="open && window.innerWidth < 640"
            x-transition:enter="transition ease-out duration-200 motion-reduce:duration-0" x-transition:enter-start="translate-x-6 opacity-0" x-transition:enter-end="translate-x-0 opacity-100"
            x-transition:leave="transition ease-in duration-150 motion-reduce:duration-0" x-transition:leave-start="translate-x-0 opacity-100" x-transition:leave-end="translate-x-6 opacity-0"
        @endif
        @class([
            'flex flex-col bg-paper',
            'fixed inset-y-0 right-0 z-60 w-full border-l border-neutral-200 shadow-2xl sm:w-[26rem]' => $dock,
            'relative h-[40rem] w-full max-w-[26rem] overflow-hidden rounded-2xl border border-neutral-200 shadow-sm' => !$dock,
        ])>

        {{-- Header: who this is, the one thing you can always do (talk to a person), and close --}}
        <header class="flex items-center gap-3 border-b border-neutral-200 bg-white px-4 py-3">
            <span aria-hidden="true" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-teal-700 text-white">
                <x-icon name="chat-bubble-text" class="h-5 w-5" />
            </span>
            <div class="min-w-0 flex-1 leading-tight">
                <h2 class="font-secondary text-[15px] font-semibold text-neutral-900">{{ __('Support') }}</h2>
                <p class="text-xs text-neutral-500">{{ __('AI assistant') }}</p>
            </div>
            <button type="button" @click="run('human')" x-show="state === 'ready'"
                class="inline-flex items-center gap-1.5 rounded-lg border border-neutral-300 bg-white px-3 py-1.5 font-secondary text-[13px] font-semibold text-neutral-700 transition hover:bg-neutral-50 hover:text-neutral-900 focus:outline-none focus-visible:ring-4 focus-visible:ring-neutral-200">
                <x-icon name="user" class="h-4 w-4 text-neutral-500" />{{ __('Talk to a person') }}
            </button>
            @if ($dock)
                <button type="button" @click="open = false" aria-label="{{ __('Close support') }}"
                    class="-mr-1 rounded-lg p-2 text-neutral-500 transition hover:bg-neutral-100 hover:text-neutral-900 focus:outline-none focus-visible:ring-4 focus-visible:ring-neutral-200">
                    <x-icon name="x-mark" class="h-5 w-5" />
                </button>
            @endif
        </header>

        {{-- History and New chat (live mode): earlier chats are kept for the member, like any chat assistant --}}
        @if ($live)
            <div x-show="state === 'ready'" class="flex items-center justify-between gap-2 border-b border-neutral-200/70 bg-white px-3 py-1.5">
                <button type="button" @click="newChat()" :disabled="busy"
                    class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 font-secondary text-[13px] font-medium text-teal-700 transition hover:bg-teal-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-600/30 disabled:cursor-not-allowed disabled:opacity-50">
                    <x-icon name="plus" class="h-4 w-4" />{{ __('New chat') }}
                </button>
                <button type="button" @click="view === 'history' ? backToChat() : showHistory()" :disabled="busy" :aria-pressed="view === 'history'"
                    :class="view === 'history' ? 'bg-neutral-100 text-neutral-900' : 'text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900'"
                    class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 font-secondary text-[13px] font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-neutral-300 disabled:cursor-not-allowed disabled:opacity-50">
                    <x-icon name="clock" class="h-4 w-4" /><span x-text="view === 'history' ? '{{ __('Back to chat') }}' : '{{ __('History') }}'"></span>
                </button>
            </div>
        @endif

        {{-- What the assistant can see of the page you are on, so its answers never feel like guesswork --}}
        <p x-show="state === 'ready' && view === 'chat' && context.label" class="flex items-center gap-2 border-b border-neutral-200/70 bg-white/60 px-4 py-1.5 text-xs text-neutral-500">
            <x-icon name="eye" class="h-3.5 w-3.5" />
            <span>{{ __('Looking at') }} <span class="font-medium text-neutral-700" x-text="context.label"></span></span>
        </p>

        {{-- Conversation --}}
        <div x-show="state === 'ready' && view === 'chat'" x-ref="scroller" aria-live="polite" aria-relevant="additions"
            class="flex flex-1 flex-col gap-5 overflow-y-auto overscroll-contain px-4 py-5 [scrollbar-color:theme(colors.neutral.300)_transparent] [scrollbar-width:thin]">
            <template x-for="(m, i) in messages" :key="i">
                <div>
                    {{-- You --}}
                    <div x-show="m.role === 'user'" class="flex justify-end">
                        <p class="max-w-[85%] whitespace-pre-line rounded-2xl rounded-br-md bg-teal-700 px-4 py-2.5 text-[15px] leading-[1.5] text-white" x-text="m.text"></p>
                    </div>

                    {{-- Assistant: flat text on the page, with cards as the evidence --}}
                    <div x-show="m.role === 'assistant'" class="flex max-w-[96%] flex-col gap-3">
                        <p x-show="m.tool" class="flex items-center gap-1.5 text-xs text-neutral-500">
                            <x-spinner class="h-3.5 w-3.5 text-teal-600 motion-reduce:animate-none" x-show="m.tool && !m.tool.done" />
                            <x-icon name="check" class="h-3.5 w-3.5 text-teal-600" x-show="m.tool && m.tool.done" />
                            <span x-text="m.tool ? (m.tool.done ? m.tool.label : m.tool.label.replace(/^Checked/, 'Checking').replace(/^Searched/, 'Searching') + '…') : ''"></span>
                        </p>

                        {{-- While the answer is being prepared. Replies are held until they have been checked, so this can take up to a minute --}}
                        <p x-show="live && busy && !m.text && i === messages.length - 1" x-cloak class="flex items-center gap-2 text-sm text-neutral-500">
                            <x-spinner class="h-4 w-4 shrink-0 text-teal-600 motion-reduce:animate-none" />
                            <span x-text="waitingLabel"></span>
                        </p>

                        {{-- The assistant's text is rendered (bold, italics, lists, links) by supportRender(), which escapes it first --}}
                        <div x-show="m.text" class="text-[15px] leading-[1.55] text-neutral-800" x-html="supportRender(m.text)"></div>

                        <template x-for="(card, ci) in (m.cards || [])" :key="ci">
                            <div>
                                <template x-if="card.type === 'trail'"><x-support.card.trail /></template>
                                <template x-if="card.type === 'blocker'"><x-support.card.blocker /></template>
                                <template x-if="card.type === 'escrow'"><x-support.card.escrow /></template>
                                <template x-if="card.type === 'ticket'"><x-support.card.ticket /></template>
                                <template x-if="card.type === 'approval'"><x-support.card.approval /></template>
                            </div>
                        </template>

                        {{-- Sources: the help articles the answer was built from. Each one opens, with the passage used marked. --}}
                        <div x-show="m.citations && m.citations.length" class="flex flex-col gap-1.5">
                            <p class="text-xs font-medium text-neutral-500">{{ __('Sources') }}</p>
                            <div class="flex flex-wrap gap-1.5">
                                <template x-for="(c, ki) in (m.citations || [])" :key="ki">
                                    <button type="button" @click="openArticle(c, m.text)" :disabled="!live || !c.slug" :title="live && c.slug ? '{{ __('Open this article') }}' : null"
                                        class="inline-flex max-w-full items-center gap-1.5 rounded-full border border-neutral-200 bg-white px-2.5 py-1 text-xs text-neutral-600 transition enabled:hover:border-teal-300 enabled:hover:bg-teal-50 enabled:hover:text-teal-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-600/30 disabled:cursor-default">
                                        <x-icon name="document-text" class="h-3.5 w-3.5 shrink-0 text-neutral-400" />
                                        <span class="truncate font-medium" x-text="c.title"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <p x-show="m.first && !live" class="text-xs leading-snug text-neutral-500">
                            {{ __("I'm an AI assistant. I can see your ModelHub account but I can't change anything on it. If you hand a chat to staff, they can read it.") }}
                        </p>
                        {{-- Suggestions only on the latest turn, and only the ones that make sense here --}}
                        <div x-show="m.chips && m.chips.length && i === messages.length - 1 && !busy" class="flex flex-col gap-2">
                            <p x-show="m.first && live" class="text-xs font-medium text-neutral-500">{{ __('Frequently asked questions') }}</p>
                            <div class="flex flex-wrap gap-1.5">
                                <template x-for="(chip, hi) in (m.chips || [])" :key="hi">
                                    <button type="button" @click="run(chip.key, chip.label)"
                                        class="rounded-full border border-teal-200 bg-white px-2.5 py-1 text-left text-xs font-medium text-teal-800 transition hover:border-teal-300 hover:bg-teal-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-600/30"
                                        x-text="chip.label"></button>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- Composer --}}
        <form x-show="state === 'ready' && view === 'chat'" @submit.prevent="submit()" class="border-t border-neutral-200 bg-white px-4 pb-3 pt-3">
            <p x-show="guard" x-cloak role="alert" class="mb-2 flex items-start gap-2 rounded-lg bg-red-50 px-3 py-2 text-xs leading-snug text-red-800">
                <x-icon name="exclamation-circle" class="mt-px h-4 w-4 shrink-0" />
                <span x-text="guard"></span>
            </p>
            <div class="flex items-end gap-2">
                <label for="{{ $uid }}-input" class="sr-only">{{ __('Message') }}</label>
                <textarea id="{{ $uid }}-input" x-ref="input" x-model="input" rows="1"
                    @input="check(); grow()" @keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); submit() }"
                    x-bind:placeholder="live ? '{{ __('Ask how ModelHub works') }}' : '{{ __('Ask about your account') }}'"
                    :class="guard ? '!border-red-400 focus:!border-red-500 focus:!ring-red-500' : 'border-neutral-300 focus:border-teal-600 focus:ring-teal-600'"
                    class="max-h-32 min-h-[2.75rem] flex-1 resize-none overflow-y-auto rounded-xl bg-white px-3.5 py-2.5 text-[15px] leading-snug placeholder:text-neutral-400"></textarea>
                <button type="submit" :disabled="!input.trim() || !!guard || busy" aria-label="{{ __('Send') }}"
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-teal-600 text-white transition hover:bg-teal-700 focus:outline-none focus-visible:ring-4 focus-visible:ring-teal-600/40 disabled:cursor-not-allowed disabled:bg-neutral-200 disabled:text-neutral-400">
                    <x-icon name="arrow-up" class="h-5 w-5" />
                </button>
            </div>
            <p class="mt-2 text-center text-xs leading-snug text-neutral-500">{{ __('AI can make mistakes. Please double-check important information.') }}</p>
        </form>

        {{-- History: the member's earlier chats, newest first, grouped by day --}}
        @if ($live)
            <div x-show="state === 'ready' && view === 'history'" x-cloak class="flex flex-1 flex-col gap-1 overflow-y-auto px-2 py-3 [scrollbar-color:theme(colors.neutral.300)_transparent] [scrollbar-width:thin]">
                <h3 class="px-2.5 pb-1 font-secondary text-xs font-semibold uppercase tracking-wide text-neutral-500">{{ __('Your chats') }}</h3>

                <p x-show="chatsState === 'loading'" class="flex items-center gap-2 px-2.5 py-3 text-sm text-neutral-500"><x-spinner class="h-4 w-4 text-teal-600" />{{ __('Loading your chats…') }}</p>
                <div x-show="chatsState === 'error'" x-cloak class="px-2.5 py-3 text-sm text-neutral-600">
                    <p>{{ __("Couldn't load your chats.") }}</p>
                    <button type="button" @click="loadChats()" class="mt-2 inline-flex items-center gap-1.5 font-medium text-teal-700 hover:underline"><x-icon name="arrow-path" class="h-4 w-4" />{{ __('Try again') }}</button>
                </div>
                <p x-show="chatsState === 'ready' && !chats.length" x-cloak class="px-2.5 py-3 text-sm text-neutral-500">{{ __('No earlier chats yet. Ask a question and it will appear here.') }}</p>
                <p x-show="notice" x-cloak x-text="notice" class="mx-2.5 mb-1 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800"></p>

                <template x-for="group in groups" :key="group.label">
                    <div class="pt-2">
                        <h4 class="px-2.5 pb-1 text-xs font-medium text-neutral-500" x-text="group.label"></h4>
                        <template x-for="c in group.chats" :key="c.id">
                            <div class="group relative">
                                <div x-show="confirming !== c.id" class="flex items-center gap-1 rounded-lg" :class="c.id === conversationId ? 'bg-teal-50' : 'hover:bg-neutral-50'">
                                    <button type="button" @click="openChat(c.id)" :disabled="opening === c.id"
                                        class="min-w-0 flex-1 rounded-lg px-2.5 py-2 text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-600/30 disabled:opacity-60">
                                        <span class="block truncate text-sm font-medium text-neutral-900" x-text="c.title"></span>
                                        <span class="block text-xs text-neutral-500" x-text="chatTime(c.updated_at)"></span>
                                    </button>
                                    <button type="button" @click="confirming = c.id" aria-label="{{ __('Delete chat') }}"
                                        class="mr-1 rounded-md p-1.5 text-neutral-400 opacity-60 transition hover:bg-neutral-100 hover:text-red-600 hover:opacity-100 focus:opacity-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-neutral-300">
                                        <x-icon name="trash" class="h-4 w-4" />
                                    </button>
                                </div>
                                <div x-show="confirming === c.id" x-cloak class="flex items-center justify-between gap-2 rounded-lg bg-red-50 px-2.5 py-2">
                                    <span class="min-w-0 truncate text-sm text-red-800">{{ __('Delete this chat?') }}</span>
                                    <span class="flex shrink-0 gap-1.5">
                                        <button type="button" @click="confirming = null" class="rounded-md px-2 py-1 text-xs font-medium text-neutral-700 hover:bg-white">{{ __('Keep') }}</button>
                                        <button type="button" @click="archiveChat(c.id)" class="rounded-md bg-red-600 px-2 py-1 text-xs font-semibold text-white hover:bg-red-700">{{ __('Delete') }}</button>
                                    </span>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        @endif

        {{-- Article: a help article opened from a source, with the passage the answer was built from marked --}}
        @if ($live)
            <div x-show="state === 'ready' && view === 'article'" x-cloak x-ref="article" class="flex flex-1 flex-col overflow-y-auto px-4 py-3 [scrollbar-color:theme(colors.neutral.300)_transparent] [scrollbar-width:thin]">
                <button type="button" @click="backToChat()" class="mb-3 inline-flex items-center gap-1.5 self-start rounded-md py-1 text-[13px] font-medium text-teal-700 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-600/30">
                    <x-icon name="arrow-left" class="h-4 w-4" />{{ __('Back to chat') }}
                </button>

                <p x-show="articleState === 'loading'" class="flex items-center gap-2 py-3 text-sm text-neutral-500"><x-spinner class="h-4 w-4 text-teal-600" />{{ __('Opening the article…') }}</p>
                <p x-show="articleState === 'missing'" x-cloak class="py-3 text-sm text-neutral-600">{{ __("That article isn't available any more.") }}</p>
                <div x-show="articleState === 'error'" x-cloak class="py-3 text-sm text-neutral-600">
                    <p>{{ __("Couldn't open that article right now.") }}</p>
                </div>

                <template x-if="articleState === 'ready' && article">
                    <article>
                        <h3 class="font-secondary text-base font-semibold text-neutral-900" x-text="article.title"></h3>
                        <p x-show="article.sections.some((s) => s.cited)" class="mt-2 rounded-lg bg-teal-50 px-3 py-2 text-xs leading-snug text-teal-800">{{ __('The highlighted part is what this answer was built from.') }}</p>
                        <template x-for="(section, si) in article.sections" :key="si">
                            <section class="mt-4" :data-cited="section.cited ? '1' : null"
                                :style="section.cited ? 'border-left:3px solid #0d9488;background:#f0fdfa;padding:.5rem .75rem;border-radius:.5rem' : ''">
                                <h4 x-show="section.heading" class="mb-1 font-secondary text-sm font-semibold text-neutral-900" x-text="section.heading"></h4>
                                <div class="text-[15px] leading-[1.55] text-neutral-800" x-html="supportRender(section.text)"></div>
                            </section>
                        </template>
                    </article>
                </template>
            </div>
        @endif

        {{-- Unavailable: kill switch or outage. Never a dead end: the same question goes to staff as a request. --}}
        <div x-show="state === 'unavailable'" x-cloak class="flex-1 overflow-y-auto px-4 py-6">
            <div class="flex items-start gap-3">
                <span aria-hidden="true" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                    <x-icon name="exclamation-triangle" class="h-5 w-5" />
                </span>
                <div>
                    <h3 class="font-secondary text-[15px] font-semibold text-neutral-900">{{ __("The assistant isn't available right now") }}</h3>
                    <p x-show="!live" class="mt-1 text-sm leading-snug text-neutral-600">{{ __("Send your question to staff instead. They'll reply by email and in your notifications.") }}</p>
                    <p x-show="live" x-cloak class="mt-1 text-sm leading-snug text-neutral-600">
                        <span x-show="supportEmail">{{ __('You can email us instead at') }} <a :href="'mailto:' + supportEmail" class="font-medium text-teal-700 hover:underline" x-text="supportEmail"></a>. {{ __('Say what happened, and include a payment reference if you have one. Never include your PIN.') }}</span>
                        <span x-show="!supportEmail">{{ __('Please try again in a little while.') }}</span>
                    </p>
                    <x-btn type="button" variant="secondary" size="sm" class="mt-3" x-show="live" x-cloak @click="state = 'ready'">{{ __('Try again') }}</x-btn>
                </div>
            </div>

            <form x-show="!live" class="mt-5 space-y-4 rounded-xl border border-neutral-200 bg-white p-4" @submit.prevent>
                <div>
                    <label for="{{ $uid }}-topic" class="block text-sm font-medium text-neutral-700">{{ __('What is it about?') }}</label>
                    <select id="{{ $uid }}-topic" class="mt-1 block w-full rounded-xl border-neutral-300 text-sm focus:border-teal-600 focus:ring-teal-600">
                        <option>{{ __('A payment') }}</option>
                        <option>{{ __('A withdrawal') }}</option>
                        <option>{{ __('One of my models') }}</option>
                        <option>{{ __('A job or engagement') }}</option>
                        <option>{{ __('My account') }}</option>
                        <option>{{ __('Something else') }}</option>
                    </select>
                </div>
                <div>
                    <label for="{{ $uid }}-message" class="block text-sm font-medium text-neutral-700">{{ __('What happened?') }}</label>
                    <textarea id="{{ $uid }}-message" rows="4" class="mt-1 block w-full rounded-xl border-neutral-300 text-sm leading-snug focus:border-teal-600 focus:ring-teal-600"></textarea>
                    <p class="mt-1 text-xs text-neutral-500">{{ __('Include a payment reference or M-Pesa receipt code if you have one. Never include your PIN.') }}</p>
                </div>
                <x-btn type="button" block>{{ __('Send to staff') }}</x-btn>
            </form>
        </div>
    </section>
</div>

@once
    <script>
        /**
         * Turns the assistant's text (light Markdown) into safe HTML: bold, italics, inline code, bullet and numbered lists, headings,
         * and links (underlined, http/https only). The text is HTML-escaped FIRST and only then given tags, so nothing the model
         * writes can inject markup or a script. Styled inline so it needs no CSS rebuild. Half-written text (while streaming) is fine.
         */
        window.supportRender = function (text) {
            const esc = (s) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
            const inline = (s) => esc(s)
                .replace(/`([^`\n]+)`/g, '<code style="background:#f3f4f6;border-radius:4px;padding:1px 5px;font-size:.9em">$1</code>')
                .replace(/\*\*([^*\n]+?)\*\*/g, '<strong>$1</strong>')
                .replace(/__([^_\n]+?)__/g, '<strong>$1</strong>')
                .replace(/(^|[^*\w])\*([^*\s][^*\n]*?)\*(?![*\w])/g, '$1<em>$2</em>')
                .replace(/(^|[^_\w])_([^_\s][^_\n]*?)_(?![_\w])/g, '$1<em>$2</em>')
                .replace(/\[([^\]\n]+)\]\((https?:\/\/[^\s)]+)\)/g, '<a href="$2" target="_blank" rel="noopener noreferrer" style="text-decoration:underline">$1</a>');

            const lines = String(text == null ? '' : text).replace(/\r\n/g, '\n').split('\n').map((l) => l.trimEnd());
            // A list line: "- x", "* x", "• x", "1. x" or "1) x", with how far it is indented (that is what makes a bullet belong to the item above)
            const itemOf = (line) => {
                const m = line.match(/^(\s*)([-*•]|\d+[.)])\s+(.*)$/);
                return m ? { indent: m[1].replace(/\t/g, '    ').length, ordered: /\d/.test(m[2]), number: parseInt(m[2], 10), body: m[3] } : null;
            };
            const nextItem = (from) => {
                for (let j = from + 1; j < lines.length; j++) if (lines[j].trim()) return itemOf(lines[j]);
                return null;
            };
            const newList = (item) => ({ tag: item.ordered ? 'ol' : 'ul', indent: item.indent, start: item.ordered ? item.number : 1, items: [] });

            const out = [];
            let para = [];
            let root = null;   // the outermost list being built
            let stack = [];    // the lists currently open, outermost first (a nested list sits inside the last item of the one before it)

            const flushPara = () => {
                if (para.length) out.push('<p style="margin:0 0 .5rem">' + para.map(inline).join('<br>') + '</p>');
                para = [];
            };
            const renderList = (list, nested) => {
                const start = list.tag === 'ol' && list.start > 1 ? ' start="' + list.start + '"' : '';
                const style = 'list-style:' + (list.tag === 'ol' ? 'decimal' : 'disc') + ';padding-left:1.25rem;margin:' + (nested ? '.15rem 0 .25rem' : '0 0 .5rem');
                return '<' + list.tag + start + ' style="' + style + '">'
                    + list.items.map((i) => '<li style="margin:.15rem 0">' + i.html + i.children.map((c) => renderList(c, true)).join('') + '</li>').join('')
                    + '</' + list.tag + '>';
            };
            const flushList = () => {
                if (root) out.push(renderList(root, false));
                root = null;
                stack = [];
            };
            const attach = (list) => {   // put a new list inside the last item of the list that is open
                const parent = stack[stack.length - 1].items[stack[stack.length - 1].items.length - 1];
                parent.children.push(list);
                stack.push(list);
            };

            for (let i = 0; i < lines.length; i++) {
                const line = lines[i];
                let m;

                // A blank line does NOT end a list: models often put one between numbered items, and the numbering must carry on
                if (! line.trim()) { if (! root) flushPara(); continue; }

                const item = itemOf(line);
                if (item) {
                    // A bullet that is only a bold label ("**Standard licence:**") followed by bullets at the SAME level is a sub-heading
                    const following = nextItem(i);
                    if (! item.ordered && /^\*\*[^*]+:\*\*$/.test(item.body.trim()) && following && ! following.ordered && following.indent === item.indent) {
                        flushPara(); flushList();
                        out.push('<p style="margin:.25rem 0 .25rem">' + inline(item.body.trim()) + '</p>');
                        continue;
                    }

                    flushPara();
                    while (stack.length && stack[stack.length - 1].indent > item.indent) stack.pop();   // back out of deeper lists
                    let list = stack[stack.length - 1];

                    if (! list) {                                    // no list open: start one
                        flushList();
                        list = root = newList(item);
                        stack = [root];
                    } else if (list.indent < item.indent) {          // more indented than the open list: a list inside the last item
                        list = newList(item);
                        attach(list);
                    } else if (list.tag !== (item.ordered ? 'ol' : 'ul')) {   // same level, other kind of list
                        stack.pop();
                        list = newList(item);
                        if (stack.length) attach(list); else { flushList(); root = list; stack = [list]; }
                    }
                    list.items.push({ html: inline(item.body), children: [] });
                } else if ((m = line.match(/^#{1,6}\s+(.*)$/))) {
                    flushPara(); flushList();
                    out.push('<p style="margin:0 0 .5rem"><strong>' + inline(m[1]) + '</strong></p>');
                } else if (root && /^\s/.test(line)) {               // an indented line under a list item carries on that item
                    const open = stack[stack.length - 1];
                    open.items[open.items.length - 1].html += '<br>' + inline(line.trim());
                } else {
                    flushList();
                    para.push(line);
                }
            }
            flushPara(); flushList();
            return out.join('');
        };
        // end supportRender

        /**
         * The server marks every section of the passage the answer was retrieved from, and small sections are packed together, so that can be
         * four sections when the answer only drew on one. Keep the marked sections whose own words the answer actually uses; if none of them
         * stand out (or only one is marked) leave the marking as the server gave it. Never marks a section the server did not.
         */
        window.supportNarrowCited = function (sections, answer) {
            const stop = new Set('a an the and or of to in on for with is are be can you your it its this that as at by from if not do does how i my me we our they them there their will would should could may might have has had was were been than then so such into about up out all any more most some no yes also just only'.split(' '));
            const words = (t) => (String(t || '').toLowerCase().replace(/\u2019/g, "'").match(/[a-z0-9']+/g) || []);
            const inAnswer = new Set(words(answer));
            const marked = (sections || []).filter((s) => s.cited);
            if (marked.length <= 1) return sections;

            const coverage = (section) => {
                const content = [...new Set(words(section.heading + ' ' + section.text).filter((w) => ! stop.has(w) && w.length > 2))];
                return content.length ? content.filter((w) => inAnswer.has(w)).length / content.length : 0;
            };
            const scores = new Map(marked.map((s) => [s, coverage(s)]));
            const best = Math.max(...scores.values());
            if (best === 0) return sections;
            return sections.map((s) => (s.cited ? { ...s, cited: scores.get(s) >= best * 0.6 } : s));
        };
        // end supportNarrowCited

        /** Groups chats (newest first) under Today / Yesterday / Previous 7 days / Earlier, by the member's own calendar days. */
        window.supportGroupChats = function (chats, now) {
            const day = (d) => new Date(d.getFullYear(), d.getMonth(), d.getDate()).getTime();
            const today = day(now), DAY = 86400000;
            const buckets = [['Today', []], ['Yesterday', []], ['Previous 7 days', []], ['Earlier', []]];
            for (const c of chats || []) {
                const ago = Math.round((today - day(new Date(c.updated_at))) / DAY);
                buckets[ago <= 0 ? 0 : ago === 1 ? 1 : ago <= 7 ? 2 : 3][1].push(c);
            }
            return buckets.filter(([, list]) => list.length).map(([label, list]) => ({ label, chats: list }));
        };

        /** "10:16" for today, otherwise "6 Oct" (and the year when it is not this year). */
        window.supportChatTime = function (iso, now) {
            const d = new Date(iso);
            if (isNaN(d)) return '';
            if (d.toDateString() === now.toDateString()) return d.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
            return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', ...(d.getFullYear() !== now.getFullYear() ? { year: 'numeric' } : {}) });
        };
        // end supportGroupChats

        /**
         * Alpine data for the support assistant widget. Two modes:
         *  - scripted (the design preview and gallery): plays the answers it is given and makes no network calls
         *  - live (SUPPORT_ENABLED): posts to ModelHub's /support/chat and reads the assistant's reply as it streams
         * Message shape: { role, text, tool?, cards[], citations[], chips[] }.
         */
        window.supportChat = function (cfg) {
            const wait = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
            const tones = {
                amber: 'bg-amber-100 text-amber-800',
                green: 'bg-green-100 text-green-800',
                teal: 'bg-teal-100 text-teal-800',
                red: 'bg-red-100 text-red-800',
                neutral: 'bg-neutral-100 text-neutral-700',
            };

            return {
                mode: cfg.mode,
                live: !!cfg.live,
                endpoint: cfg.endpoint,
                supportEmail: cfg.supportEmail,
                routeKey: cfg.routeKey,
                conversationId: null,
                state: cfg.state,
                context: cfg.context,
                scenarios: cfg.scenarios,
                open: cfg.mode === 'inline',
                messages: [],
                input: cfg.input || '',
                guard: '',
                busy: false,
                // Seconds spent waiting for the current answer (counted while it is being prepared)
                waited: 0,
                waitTimer: null,
                // How far the Help button is raised so it never sits over the footer (0 until the footer scrolls into view)
                lift: 0,
                // Chat history (live mode): which screen is showing, the member's chats, and what is in flight
                view: 'chat',
                endpoints: cfg.endpoints,
                chats: [],
                chatsState: 'idle',
                confirming: null,
                opening: null,
                notice: '',
                // An article opened from a source
                article: null,
                articleState: 'idle',
                chatScroll: 0,
                reduced: window.matchMedia('(prefers-reduced-motion: reduce)').matches,

                init() {
                    this.messages = cfg.transcript && cfg.transcript.length ? cfg.transcript : [this.greeting()];
                    if (this.live) this.restore();
                    if (this.mode === 'dock') this.followFooter();
                    if (this.input) this.check();
                    this.$watch('open', (isOpen) => {
                        if (isOpen) this.$nextTick(() => { this.scroll(); if (this.$refs.input) this.$refs.input.focus(); });
                        else if (this.mode === 'dock') this.$nextTick(() => { if (this.$refs.launcher) this.$refs.launcher.focus(); });
                    });
                },

                tone(name) { return tones[name] || tones.neutral; },

                // Keep the launcher just above the footer once the footer scrolls into view, instead of reserving a blank band
                // under it. Any scroll (the page or an inner container) and any resize re-measures, at most once per frame.
                followFooter() {
                    const footer = document.querySelector('[data-app-footer]');
                    if (! footer) return;
                    let queued = false;
                    const measure = () => {
                        queued = false;
                        const visible = window.innerHeight - footer.getBoundingClientRect().top;  // how much of the footer is on screen
                        this.lift = Math.round(Math.min(Math.max(visible, 0), window.innerHeight / 2));
                    };
                    const queue = () => { if (! queued) { queued = true; requestAnimationFrame(measure); } };
                    document.addEventListener('scroll', queue, { passive: true, capture: true });
                    window.addEventListener('resize', queue);
                    measure();
                },

                // What to say while the answer is being prepared: just "Thinking…", then that it can take a while
                get waitingLabel() {
                    return this.waited < 10 ? 'Thinking…' : 'Still thinking… the first answer can take up to a minute.';
                },

                startWaiting() {
                    this.stopWaiting();
                    this.waited = 0;
                    this.waitTimer = setInterval(() => { this.waited++; }, 1000);
                },

                stopWaiting() {
                    if (this.waitTimer) clearInterval(this.waitTimer);
                    this.waitTimer = null;
                    this.waited = 0;
                },

                greeting() {
                    return { role: 'assistant', text: this.context.greeting, tool: null, cards: this.context.cards || [], citations: [], chips: this.context.chips || [], first: true };
                },

                get groups() { return window.supportGroupChats(this.chats, new Date()); },
                chatTime(iso) { return window.supportChatTime(iso, new Date()); },

                // ---- chat history (live mode) ------------------------------------------------------------------------

                headers() {
                    return { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': this.csrf() };
                },

                url(kind, id) { return this.endpoints[kind].replace(/__id__|__slug__/, encodeURIComponent(id)); },

                // Back from an article or the history to the conversation, where the member was reading
                backToChat() {
                    this.view = 'chat';
                    this.$nextTick(() => { if (this.$refs.scroller) this.$refs.scroller.scrollTop = this.chatScroll; });
                },

                // Open the help article behind a source. The passage the answer was built from comes marked.
                async openArticle(citation, answer = '') {
                    if (! citation || ! citation.slug || ! this.endpoints) return;
                    if (this.$refs.scroller) this.chatScroll = this.$refs.scroller.scrollTop;
                    this.view = 'article';
                    this.article = null;
                    this.articleState = 'loading';
                    try {
                        const query = citation.chunk_id ? '?chunk=' + encodeURIComponent(citation.chunk_id) : '';
                        const response = await fetch(this.url('article', citation.slug) + query, { credentials: 'same-origin', headers: this.headers() });
                        if (response.status === 404) { this.articleState = 'missing'; return; }
                        if (! response.ok) throw new Error('article failed');  // one article failing is not the whole assistant being off
                        const article = await response.json();
                        this.article = { ...article, sections: window.supportNarrowCited(article.sections, answer) };
                        this.articleState = 'ready';
                        this.$nextTick(() => {
                            const marked = this.$refs.article ? this.$refs.article.querySelector('[data-cited]') : null;
                            if (marked) marked.scrollIntoView({ block: 'center', behavior: this.reduced ? 'auto' : 'smooth' });
                        });
                    } catch (e) {
                        this.articleState = 'error';
                    }
                },

                // A new chat: back to the greeting and the suggestions. The chat you leave stays in History.
                newChat() {
                    if (this.busy) return;
                    this.conversationId = null;
                    this.messages = [this.greeting()];
                    this.input = '';
                    this.guard = '';
                    this.view = 'chat';
                    this.persist();
                    this.$nextTick(() => { if (this.$refs.input) this.$refs.input.focus(); });
                },

                async showHistory() {
                    if (this.busy) return;
                    this.view = 'history';
                    this.confirming = null;
                    this.notice = '';
                    await this.loadChats();
                },

                async loadChats() {
                    this.chatsState = 'loading';
                    try {
                        const response = await fetch(this.url('list'), { credentials: 'same-origin', headers: this.headers() });
                        if (response.status === 503) { this.state = 'unavailable'; return; }
                        if (! response.ok) throw new Error('list failed');
                        this.chats = (await response.json()).conversations || [];
                        this.chatsState = 'ready';
                    } catch (e) {
                        this.chatsState = 'error';
                    }
                },

                // Open an earlier chat: its messages (with the citations its answers were built from) replace the screen, and the next
                // message carries on in that same chat.
                async openChat(id) {
                    if (this.busy || this.opening) return;
                    this.opening = id;
                    this.notice = '';
                    try {
                        const response = await fetch(this.url('show', id), { credentials: 'same-origin', headers: this.headers() });
                        if (response.status === 404) {
                            this.chats = this.chats.filter((c) => c.id !== id);
                            this.notice = "That chat isn't available any more.";
                            return;
                        }
                        if (! response.ok) throw new Error('open failed');
                        const chat = await response.json();
                        this.conversationId = chat.id;
                        this.messages = [{ ...this.greeting(), chips: [] }, ...(chat.messages || []).map((m) => ({
                            role: m.role, text: m.text, tool: null, cards: [], chips: [],
                            citations: (m.citations || []).map((c) => ({ title: c.title, slug: c.slug, chunk_id: c.chunk_id })),
                        }))];
                        this.view = 'chat';
                        this.persist();
                        this.scroll();
                    } catch (e) {
                        this.notice = "Couldn't open that chat. Try again.";
                    } finally {
                        this.opening = null;
                    }
                },

                async archiveChat(id) {
                    this.confirming = null;
                    try {
                        const response = await fetch(this.url('destroy', id), { method: 'DELETE', credentials: 'same-origin', headers: this.headers() });
                        if (! response.ok && response.status !== 404) throw new Error('delete failed');
                        this.chats = this.chats.filter((c) => c.id !== id);
                        if (id === this.conversationId) {  // the chat that was open is gone: start from a clean one
                            this.conversationId = null;
                            this.messages = [this.greeting()];
                            this.persist();
                        }
                    } catch (e) {
                        this.notice = "Couldn't delete that chat. Try again.";
                    }
                },

                // Stops a PIN, an SMS code or a card number leaving the browser. Heuristics are deliberately narrow so an
                // amount like "Ksh1500" or a year is never blocked; the real guard needs tuning against real messages.
                check() {
                    const t = this.input;
                    const sensitive = /\b(pin|otp|password|code)\b\D{0,15}\d{4,8}/i.test(t) || /^\s*\d{4,8}\s*$/.test(t) || /\b(?:\d[ -]?){13,19}\b/.test(t);
                    this.guard = sensitive ? "That looks like a PIN or a code, so it wasn't sent. Never type your M-Pesa PIN or SMS codes here." : '';
                },

                grow() {
                    const el = this.$refs.input;
                    if (!el) return;
                    el.style.height = 'auto';
                    el.style.height = Math.min(el.scrollHeight, 128) + 'px';
                },

                scroll() {
                    this.$nextTick(() => {
                        const el = this.$refs.scroller;
                        if (el) el.scrollTo({ top: el.scrollHeight, behavior: this.reduced ? 'auto' : 'smooth' });
                    });
                },

                match(text) {
                    if (/\b(human|person|agent|someone|staff)\b/i.test(text)) return 'human';
                    if (/useless|not helping|stupid|nonsense|scam|nimechoka|wezi|!!!/i.test(text)) return 'frustrated';
                    if (/withdraw|payout|balance|hold/i.test(text)) return 'blocker';
                    if (/refund|money back/i.test(text)) return 'policy';
                    if (/escrow|client|freelancer|deliverable/i.test(text)) return 'escrow';
                    if (/paid|mpesa|m-pesa|licen[cs]e|download|payment/i.test(text)) return 'paid_nothing';
                    return 'unknown';
                },

                submit() {
                    const text = this.input.trim();
                    if (!text || this.busy || this.guard) return;
                    this.input = '';
                    this.$nextTick(() => this.grow());
                    if (this.live) return this.sendLive(text);
                    this.send(text, this.match(text));
                },

                run(key, label) {
                    if (this.busy) return;
                    if (this.live) return key === 'human' ? this.talkToAPersonLive() : this.sendLive(label);
                    const said = label || ({ human: 'Talk to a person', ask_staff: 'Ask staff to look at this', refund: 'Ask for a refund' }[key]) || key;
                    this.send(said, key);
                },

                // ---- live mode -------------------------------------------------------------------------------------

                csrf() {
                    const tag = document.querySelector('meta[name="csrf-token"]');
                    return tag ? tag.content : '';
                },

                // Staff requests are not built yet, so say so plainly and give the one real way to reach a person.
                talkToAPersonLive() {
                    this.messages.push({ role: 'user', text: 'Talk to a person' });
                    this.messages.push({
                        role: 'assistant',
                        text: this.supportEmail
                            ? `Staff requests aren't switched on yet. For now, email ${this.supportEmail} and say what happened, with a payment reference if you have one. Never include your M-Pesa PIN or a code from an SMS.`
                            : "Staff requests aren't switched on yet. Please try again a little later.",
                        tool: null, cards: [], citations: [], chips: [],
                    });
                    this.scroll();
                    this.persist();
                },

                async sendLive(text) {
                    this.messages.push({ role: 'user', text });
                    const assistant = this.messages[this.messages.push({ role: 'assistant', text: '', tool: null, cards: [], citations: [], chips: [] }) - 1];
                    this.busy = true;
                    this.startWaiting();
                    this.scroll();

                    try {
                        const response = await fetch(this.endpoint, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'text/event-stream',
                                'X-CSRF-TOKEN': this.csrf(),
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({
                                message: text,
                                conversation_id: this.conversationId || undefined,
                                page_context: this.routeKey ? { route_key: this.routeKey } : undefined,
                            }),
                        });

                        if (!response.ok) {
                            await this.refused(response, text);
                        } else {
                            await this.readStream(response, assistant);
                        }
                    } catch (e) {
                        this.dropLastTurn();
                        this.input = text;
                        this.guard = "I couldn't reach the assistant. Check your connection and try again.";
                    } finally {
                        this.busy = false;
                        this.stopWaiting();
                        this.persist();
                        this.scroll();
                    }
                },

                // The server turned the message away before answering. Take the turn back out and give the text back to edit.
                dropLastTurn() {
                    if (this.messages.length >= 2) this.messages.splice(-2, 2);
                },

                async refused(response, text) {
                    let code = '', message = '';
                    try { const body = await response.json(); code = body.error?.code || ''; message = body.error?.message || ''; } catch (e) { /* not JSON */ }

                    if (response.status === 503) {
                        this.dropLastTurn();
                        this.input = text;
                        this.state = 'unavailable';
                        return;
                    }

                    this.dropLastTurn();
                    this.input = text;
                    if (response.status === 404 && code === 'conversation_not_found') {
                        this.conversationId = null;
                        this.guard = 'That conversation is no longer available. Send your message again to start a new one.';
                    } else if (response.status === 429) {
                        this.guard = "You're sending messages quickly. Wait a moment and try again.";
                    } else if (response.status === 401 || response.status === 419) {
                        this.guard = 'Your session has ended. Reload the page and sign in again.';
                    } else if (response.status === 422) {
                        this.guard = message || "That message couldn't be sent.";
                    } else {
                        this.guard = 'Something went wrong. Try again in a moment.';
                    }
                },

                async readStream(response, assistant) {
                    const reader = response.body.getReader();
                    const decoder = new TextDecoder();
                    let buffer = '';
                    let failed = false;

                    const handle = (block) => {
                        let event = 'message', data = '';
                        for (const line of block.split('\n')) {
                            if (line.startsWith('event:')) event = line.slice(6).trim();
                            else if (line.startsWith('data:')) data += line.slice(5).trim();
                        }
                        if (!data) return;
                        let payload;
                        try { payload = JSON.parse(data); } catch (e) { return; }

                        if (event === 'conversation') this.conversationId = payload.conversation_id;
                        else if (event === 'delta') { assistant.text += payload.text; this.scroll(); }
                        else if (event === 'done') assistant.citations = (payload.citations || []).map((c) => ({ title: c.title, slug: c.slug, chunk_id: c.chunk_id }));
                        else if (event === 'error') failed = true;
                    };

                    for (;;) {
                        const { value, done } = await reader.read();
                        if (done) break;
                        buffer = (buffer + decoder.decode(value, { stream: true })).replace(/\r\n/g, '\n');
                        let end;
                        while ((end = buffer.indexOf('\n\n')) !== -1) {
                            handle(buffer.slice(0, end));
                            buffer = buffer.slice(end + 2);
                        }
                    }

                    if (failed || ! assistant.text) {
                        assistant.text += (assistant.text ? '\n\n' : '') + "Sorry, I couldn't finish that answer. You can ask again, or press \"Talk to a person\".";
                    }
                },

                // Keep the conversation across page loads in this tab (it is the member's own words, in their own browser).
                storageKey() { return 'support.chat.v1.' + (cfg.userId ?? 'anon'); },

                persist() {
                    if (! this.live) return;
                    try {
                        const turns = this.messages.filter((m) => m.text && ! m.first).slice(-30).map((m) => ({ role: m.role, text: m.text, citations: m.citations || [] }));
                        sessionStorage.setItem(this.storageKey(), JSON.stringify({ conversationId: this.conversationId, turns }));
                    } catch (e) { /* storage blocked: the chat still works, it just will not survive a reload */ }
                },

                restore() {
                    try {
                        const saved = JSON.parse(sessionStorage.getItem(this.storageKey()) || 'null');
                        if (! saved || ! Array.isArray(saved.turns) || ! saved.turns.length) return;
                        this.conversationId = saved.conversationId || null;
                        const greeting = { ...this.messages[0], chips: [] };  // (kept as it was: the greeting without its suggestions)
                        this.messages = [greeting, ...saved.turns.map((m) => ({ role: m.role, text: m.text, tool: null, cards: [], citations: Array.isArray(m.citations) ? m.citations : [], chips: [] }))];
                    } catch (e) { /* ignore a corrupt value */ }
                },

                // ---- scripted mode (design preview) -----------------------------------------------------------------

                async send(text, key) {
                    this.messages.push({ role: 'user', text });
                    this.busy = true;
                    this.scroll();

                    const script = this.scenarios[key] || this.scenarios.unknown;
                    await wait(this.reduced ? 0 : 350);

                    // Mutate through the reactive proxy, not the object we pushed, or Alpine never sees the changes.
                    const m = this.messages[this.messages.push({ role: 'assistant', text: '', tool: script.tool ? { label: script.tool, done: false } : null, cards: [], citations: [], chips: [] }) - 1];
                    this.scroll();

                    if (script.tool) { await wait(this.reduced ? 0 : 800); m.tool.done = true; }

                    if (this.reduced) {
                        m.text = script.text;
                    } else {
                        const words = script.text.split(' ');
                        for (let i = 0; i < words.length; i++) {
                            m.text += (i ? ' ' : '') + words[i];
                            if (i % 4 === 0) this.scroll();
                            await wait(22);
                        }
                    }

                    m.cards = script.cards || [];
                    this.scroll();
                    await wait(this.reduced ? 0 : 150);
                    m.citations = script.citations || [];
                    m.chips = script.chips || [];
                    this.busy = false;
                    this.scroll();
                },
            };
        };
    </script>
@endonce
