{{--
    Withdrawal card: what the assistant found when it looked at one of the member's own withdrawals. Built by the service from
    ModelHub's own record (never written by a model), so every figure here is a copy. "As of" says how fresh it is: a card reopened
    from history shows the time it was true, not now.
    Rendered inside the widget's x-for, so `card` is the card JSON.
--}}
<article class="overflow-hidden rounded-xl border border-neutral-200 bg-white">
    <header class="flex items-start justify-between gap-3 p-4 pb-3">
        <div class="min-w-0">
            <h3 class="font-secondary text-sm font-semibold leading-snug text-neutral-900">{{ __('Withdrawal') }} <span class="font-tertiary tabular-nums text-neutral-500" x-text="card.reference"></span></h3>
            <p class="mt-0.5 font-tertiary text-lg font-semibold tabular-nums text-neutral-900" x-text="card.amount_display"></p>
        </div>
        <span class="inline-flex shrink-0 rounded-full px-2 py-0.5 text-xs font-medium"
            :class="tone({ requested: 'amber', processing: 'teal', paid: 'green', failed: 'red', rejected: 'red', cancelled: 'neutral' }[card.status])"
            x-text="card.status_label"></span>
    </header>

    <dl class="space-y-1.5 px-4 text-sm">
        <div class="flex justify-between gap-3"><dt class="text-neutral-500">{{ __('Requested') }}</dt><dd class="text-right text-neutral-800" x-text="when(card.requested_at)"></dd></div>
        <div x-show="card.approved_at" class="flex justify-between gap-3"><dt class="text-neutral-500">{{ __('Approved') }}</dt><dd class="text-right text-neutral-800" x-text="when(card.approved_at)"></dd></div>
        <div x-show="card.status === 'paid' && card.completed_at" class="flex justify-between gap-3"><dt class="text-neutral-500">{{ __('Paid') }}</dt><dd class="text-right text-neutral-800" x-text="when(card.completed_at)"></dd></div>
        <div class="flex justify-between gap-3"><dt class="text-neutral-500">{{ __('You receive') }}</dt><dd class="text-right font-tertiary tabular-nums text-neutral-800" x-text="card.net_display"></dd></div>
        <div class="flex justify-between gap-3"><dt class="text-neutral-500">{{ __('To phone ending') }}</dt><dd class="text-right font-tertiary tabular-nums text-neutral-800" x-text="card.phone_last3"></dd></div>
        <div x-show="card.receipt" class="flex justify-between gap-3"><dt class="text-neutral-500">{{ __('M-Pesa receipt') }}</dt><dd class="text-right font-tertiary tabular-nums text-neutral-800" x-text="card.receipt"></dd></div>
    </dl>

    <p x-show="card.reason" class="mx-4 mt-3 rounded-lg bg-neutral-50 px-3 py-2 text-sm leading-snug text-neutral-700">
        <span class="font-medium">{{ __('Reason recorded:') }}</span> <span x-text="card.reason"></span>
    </p>

    <p x-show="(m.cards || []).length > 1" class="mx-4 mt-3 text-sm leading-snug text-neutral-700" x-text="card.explanation"></p>

    <footer class="mt-3 flex flex-wrap items-center justify-between gap-2 border-t border-neutral-100 bg-neutral-50/70 px-4 py-2.5">
        <p class="text-xs text-neutral-500">{{ __('As of') }} <span x-text="when(card.as_of)"></span></p>
        <div class="flex flex-wrap gap-2">
            <button type="button" x-show="card.needs_person" @click="run('human')"
                class="inline-flex items-center gap-1.5 rounded-lg border border-neutral-300 bg-white px-3 py-1.5 font-secondary text-[13px] font-semibold text-neutral-700 transition hover:bg-neutral-50 focus:outline-none focus-visible:ring-4 focus-visible:ring-neutral-200">{{ __('Talk to a person') }}</button>
            {{-- The link is a route key from a short list; anything else gets no link --}}
            <a x-show="card.link && links[card.link.route]" :href="links[card.link && card.link.route] || '#'"
                class="inline-flex items-center gap-1.5 rounded-lg border border-neutral-300 bg-white px-3 py-1.5 font-secondary text-[13px] font-semibold text-neutral-700 transition hover:bg-neutral-50 focus:outline-none focus-visible:ring-4 focus-visible:ring-neutral-200">
                <span x-text="card.link ? card.link.label : ''"></span>
                <x-icon name="arrow-top-right-on-square" class="h-3.5 w-3.5 text-neutral-400" />
            </a>
        </div>
    </footer>
</article>
