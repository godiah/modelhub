{{--
    Ticket card: the moment a conversation is handed to staff. It says what was sent, when to expect a reply (honestly),
    and where the reply will arrive, so nobody has to wonder. Rendered inside the widget's x-for, so `card` is the card JSON.
--}}
<article class="overflow-hidden rounded-xl border border-teal-200 bg-white">
    <header class="flex items-center gap-3 border-b border-teal-100 bg-teal-50 px-4 py-3">
        <span aria-hidden="true" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-teal-600 text-white">
            <x-icon name="check-solid" class="h-4 w-4" />
        </span>
        <div class="min-w-0 flex-1">
            <h3 class="font-secondary text-sm font-semibold leading-snug text-teal-900">Request sent to staff</h3>
            <p class="text-xs text-teal-800"><span x-text="card.topic"></span></p>
        </div>
        <p class="shrink-0 font-secondary text-xs font-semibold tabular-nums text-teal-900" x-text="card.reference"></p>
    </header>

    <div class="space-y-3 p-4">
        <div>
            <p class="text-xs font-medium text-neutral-500">What we sent</p>
            <p class="mt-1 border-l-2 border-neutral-200 pl-3 text-sm leading-snug text-neutral-800" x-text="card.summary"></p>
        </div>
        <div class="flex items-start gap-2.5 text-sm text-neutral-700">
            <x-icon name="clock" class="mt-0.5 h-4 w-4 shrink-0 text-neutral-400" />
            <span x-text="card.reply"></span>
        </div>
        <div class="flex items-start gap-2.5 text-sm text-neutral-700">
            <x-icon name="envelope" class="mt-0.5 h-4 w-4 shrink-0 text-neutral-400" />
            <span x-text="card.channel"></span>
        </div>
    </div>

    <x-support.card.actions />
</article>
