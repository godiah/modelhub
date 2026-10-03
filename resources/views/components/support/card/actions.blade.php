{{--
    Footer buttons shared by every support-assistant card. Rendered inside the widget's x-for, so `card` is the card JSON
    and `run(key)` / `tone()` come from the supportChat() Alpine component. An action either links (`href`) or asks the
    assistant something (`key`).
--}}
<footer x-show="card.actions && card.actions.length" class="flex flex-wrap gap-2 border-t border-neutral-100 bg-neutral-50/70 px-4 py-3">
    <template x-for="(a, ai) in (card.actions || [])" :key="ai">
        <a :href="a.href || '#'" @click="if (a.key) { $event.preventDefault(); run(a.key) }"
            class="inline-flex items-center gap-1.5 rounded-lg border border-neutral-300 bg-white px-3 py-1.5 font-secondary text-[13px] font-semibold text-neutral-700 transition hover:bg-neutral-50 hover:text-neutral-900 focus:outline-none focus-visible:ring-4 focus-visible:ring-neutral-200">
            <span x-text="a.label"></span>
            <x-icon name="arrow-top-right-on-square" class="h-3.5 w-3.5 text-neutral-400" x-show="a.href && a.href !== '#'" />
        </a>
    </template>
</footer>
