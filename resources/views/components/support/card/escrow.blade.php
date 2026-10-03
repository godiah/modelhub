{{--
    Escrow card: where an engagement's money is, as one stacked bar plus a legend. Same visual language as the
    earnings donut: teal is money that reached someone, amber is money waiting on a decision.
    Rendered inside the widget's x-for, so `card` is the card JSON.
--}}
<article class="overflow-hidden rounded-xl border border-neutral-200 bg-white">
    <header class="flex items-start justify-between gap-3 p-4 pb-3">
        <div class="min-w-0">
            <h3 class="font-secondary text-sm font-semibold leading-snug text-neutral-900" x-text="card.title"></h3>
            <p class="mt-0.5 text-xs text-neutral-500">Escrow</p>
        </div>
        <span class="inline-flex shrink-0 rounded-full px-2 py-0.5 text-xs font-medium" :class="tone(card.state.tone)" x-text="card.state.label"></span>
    </header>

    <div class="px-4">
        <div class="flex h-2.5 w-full gap-0.5 overflow-hidden rounded-full bg-neutral-100" role="img"
            :aria-label="card.segments.map(s => s.label + ' ' + s.value).join(', ')">
            <template x-for="(seg, gi) in card.segments" :key="gi">
                <span class="h-full first:rounded-l-full last:rounded-r-full" :style="'width:' + seg.pct + '%'"
                    :class="{ 'bg-teal-600': seg.tone === 'teal', 'bg-neutral-400': seg.tone === 'slate', 'bg-amber-400': seg.tone === 'amber' }"></span>
            </template>
        </div>

        <dl class="mt-3 space-y-1.5">
            <template x-for="(seg, gi) in card.segments" :key="gi">
                <div class="flex items-center justify-between gap-3 text-sm">
                    <dt class="flex min-w-0 items-center gap-2 text-neutral-600">
                        <span aria-hidden="true" class="h-2 w-2 shrink-0 rounded-full"
                            :class="{ 'bg-teal-600': seg.tone === 'teal', 'bg-neutral-400': seg.tone === 'slate', 'bg-amber-400': seg.tone === 'amber' }"></span>
                        <span class="truncate" x-text="seg.label"></span>
                    </dt>
                    <dd class="shrink-0 font-tertiary font-semibold tabular-nums text-neutral-900" x-text="seg.value"></dd>
                </div>
            </template>
        </dl>
    </div>

    <p x-show="card.note" class="mx-4 mb-4 mt-3 rounded-lg bg-neutral-50 px-3 py-2 text-sm leading-snug text-neutral-700" x-text="card.note"></p>

    <x-support.card.actions />
</article>
