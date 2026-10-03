{{--
    Blocker card: "why can't I…?" as a short checklist. A missing requirement is amber, not red: it is something to fix,
    not an error. Rendered inside the widget's x-for, so `card` is the card JSON.
--}}
<article class="overflow-hidden rounded-xl border border-neutral-200 bg-white">
    <header class="p-4 pb-2">
        <h3 class="font-secondary text-sm font-semibold leading-snug text-neutral-900" x-text="card.title"></h3>
    </header>

    <ul class="divide-y divide-neutral-100 px-4">
        <template x-for="(r, ri) in card.rows" :key="ri">
            <li class="flex items-start gap-3 py-2.5">
                <span aria-hidden="true" class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full"
                    :class="r.ok ? 'bg-teal-600 text-white' : 'bg-amber-100 text-amber-700 ring-2 ring-amber-500'">
                    <x-icon name="check-solid" class="h-3 w-3" x-show="r.ok" />
                    <span x-show="!r.ok" class="text-[11px] font-bold leading-none">!</span>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm leading-5" :class="r.ok ? 'text-neutral-700' : 'font-semibold text-neutral-900'" x-text="r.label"></p>
                    <p class="text-xs leading-snug" :class="r.ok ? 'text-neutral-500' : 'text-amber-800'" x-text="r.have"></p>
                </div>
                <span class="sr-only" x-text="r.ok ? 'Met' : 'Not met'"></span>
            </li>
        </template>
    </ul>

    <p x-show="card.summary" class="mx-4 mb-4 mt-1 rounded-lg bg-neutral-50 px-3 py-2 text-sm leading-snug text-neutral-700" x-text="card.summary"></p>

    <x-support.card.actions />
</article>
