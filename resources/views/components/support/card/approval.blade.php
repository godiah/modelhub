{{--
    Approval card: the assistant has asked staff to decide something (v1: a refund). It must never read as "approved":
    the status is amber and says who is deciding. Rendered inside the widget's x-for, so `card` is the card JSON.
--}}
<article class="overflow-hidden rounded-xl border border-neutral-200 bg-white">
    <header class="flex items-start justify-between gap-3 p-4 pb-3">
        <h3 class="min-w-0 font-secondary text-sm font-semibold leading-snug text-neutral-900" x-text="card.title"></h3>
        <span class="inline-flex shrink-0 rounded-full px-2 py-0.5 text-xs font-medium" :class="tone(card.status.tone)" x-text="card.status.label"></span>
    </header>

    <dl class="space-y-2 px-4 pb-3">
        <template x-for="(r, ri) in card.rows" :key="ri">
            <div class="flex items-baseline justify-between gap-4 text-sm">
                <dt class="shrink-0 text-neutral-500" x-text="r.k"></dt>
                <dd class="text-right font-medium text-neutral-900" x-text="r.v"></dd>
            </div>
        </template>
    </dl>

    <p x-show="card.note" class="mx-4 mb-4 rounded-lg bg-neutral-50 px-3 py-2 text-sm leading-snug text-neutral-700" x-text="card.note"></p>

    <x-support.card.actions />
</article>
