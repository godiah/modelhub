{{--
    Licences card: the licences the member holds, those in force first. The titles are written by sellers, so they are drawn as plain text (x-text),
    never as markup. Built by the service from ModelHub's own record; "as of" says how fresh it is.
    Rendered inside the widget's x-for, so `card` is the card JSON.
--}}
<article class="overflow-hidden rounded-xl border border-neutral-200 bg-white">
    <header class="p-4 pb-2">
        <h3 class="font-secondary text-sm font-semibold leading-snug text-neutral-900">{{ __('Your licences') }}</h3>
        <p class="mt-0.5 text-xs text-neutral-500"><span x-text="card.active"></span> {{ __('in force') }} · <span x-text="card.total"></span> {{ __('in all') }}</p>
    </header>

    <ul class="divide-y divide-neutral-100 px-4">
        <template x-for="(item, ii) in card.items" :key="ii">
            <li class="py-2.5">
                <div class="flex items-start justify-between gap-3">
                    <p class="min-w-0 break-words text-sm font-medium text-neutral-900" x-text="item.title"></p>
                    <span class="inline-flex shrink-0 rounded-full px-2 py-0.5 text-xs font-medium" :class="tone(item.status === 'active' ? 'green' : 'neutral')"
                        x-text="item.status === 'active' ? item.tier_label : '{{ __('Ended') }}'"></span>
                </div>
                <p class="mt-0.5 text-xs text-neutral-500">
                    <span x-text="item.tier_label"></span> · <span class="font-tertiary tabular-nums" x-text="item.price_display"></span> · <span x-text="when(item.issued_at)"></span>
                </p>
                <p x-show="item.status === 'ended' && item.reason" class="mt-1 text-xs text-neutral-600"><span class="font-medium">{{ __('Reason recorded:') }}</span> <span x-text="item.reason"></span></p>
            </li>
        </template>
    </ul>

    <footer class="flex flex-wrap items-center justify-between gap-2 border-t border-neutral-100 bg-neutral-50/70 px-4 py-2.5">
        <p class="text-xs text-neutral-500">{{ __('As of') }} <span x-text="when(card.as_of)"></span></p>
        <a x-show="card.link && links[card.link.route]" :href="links[card.link && card.link.route] || '#'"
            class="inline-flex items-center gap-1.5 rounded-lg border border-neutral-300 bg-white px-3 py-1.5 font-secondary text-[13px] font-semibold text-neutral-700 transition hover:bg-neutral-50 focus:outline-none focus-visible:ring-4 focus-visible:ring-neutral-200">
            <span x-text="card.link ? card.link.label : ''"></span>
            <x-icon name="arrow-top-right-on-square" class="h-3.5 w-3.5 text-neutral-400" />
        </a>
    </footer>
</article>
