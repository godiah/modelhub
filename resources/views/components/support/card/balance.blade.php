{{--
    Balance card: where the member stands on withdrawing. Built by the service from ModelHub's own figures and rules (the same ones a withdrawal
    request is checked against); "as of" says how fresh it is. Text only, no markup.
    Rendered inside the widget's x-for, so `card` is the card JSON.
--}}
<article class="overflow-hidden rounded-xl border border-neutral-200 bg-white">
    <header class="flex items-start justify-between gap-3 p-4 pb-3">
        <div class="min-w-0">
            <h3 class="font-secondary text-sm font-semibold leading-snug text-neutral-900">{{ __('Your balance') }}</h3>
            <p class="mt-0.5 font-tertiary text-lg font-semibold tabular-nums text-neutral-900" x-text="card.available_display"></p>
            <p class="text-xs text-neutral-500">{{ __('available') }}</p>
        </div>
        <span class="inline-flex shrink-0 rounded-full px-2 py-0.5 text-xs font-medium" :class="tone(card.can_withdraw ? 'green' : 'amber')"
            x-text="card.can_withdraw ? '{{ __('Can withdraw') }}' : '{{ __('Cannot withdraw yet') }}'"></span>
    </header>

    <dl class="space-y-1.5 px-4 text-sm">
        <div x-show="card.can_withdraw" class="flex justify-between gap-3"><dt class="shrink-0 text-neutral-500">{{ __('You can withdraw up to') }}</dt><dd class="text-right font-tertiary font-semibold tabular-nums text-neutral-900" x-text="card.withdrawable_display"></dd></div>
        <div class="flex justify-between gap-3"><dt class="shrink-0 text-neutral-500">{{ __('On hold') }}</dt><dd class="text-right font-tertiary tabular-nums text-neutral-800" x-text="card.pending_display"></dd></div>
        <div class="flex justify-between gap-3"><dt class="shrink-0 text-neutral-500">{{ __('Smallest withdrawal') }}</dt><dd class="text-right font-tertiary tabular-nums text-neutral-800" x-text="card.min_withdrawal_display"></dd></div>
        <div class="flex justify-between gap-3"><dt class="shrink-0 text-neutral-500">{{ __('Withdrawal fee') }}</dt><dd class="text-right font-tertiary tabular-nums text-neutral-800" x-text="card.fee_display"></dd></div>
        <div x-show="card.open_withdrawal" class="flex justify-between gap-3"><dt class="shrink-0 text-neutral-500">{{ __('In progress') }}</dt><dd class="text-right text-neutral-800"><span class="font-tertiary tabular-nums" x-text="card.open_withdrawal ? card.open_withdrawal.reference : ''"></span> · <span x-text="card.open_withdrawal ? card.open_withdrawal.status_label : ''"></span></dd></div>
        <div x-show="card.held_payments" class="flex justify-between gap-3"><dt class="shrink-0 text-neutral-500">{{ __('Sales on hold') }}</dt><dd class="text-right text-neutral-800"><span x-text="card.held_payments"></span><span x-show="card.next_release_at"> · {{ __('next release') }} <span x-text="when(card.next_release_at)"></span></span></dd></div>
    </dl>

    <footer class="mt-3 flex flex-wrap items-center justify-between gap-2 border-t border-neutral-100 bg-neutral-50/70 px-4 py-2.5">
        <p class="text-xs text-neutral-500">{{ __('As of') }} <span x-text="when(card.as_of)"></span></p>
        <a x-show="card.link && links[card.link.route]" :href="links[card.link && card.link.route] || '#'"
            class="inline-flex items-center gap-1.5 rounded-lg border border-neutral-300 bg-white px-3 py-1.5 font-secondary text-[13px] font-semibold text-neutral-700 transition hover:bg-neutral-50 focus:outline-none focus-visible:ring-4 focus-visible:ring-neutral-200">
            <span x-text="card.link ? card.link.label : ''"></span>
            <x-icon name="arrow-top-right-on-square" class="h-3.5 w-3.5 text-neutral-400" />
        </a>
    </footer>
</article>
