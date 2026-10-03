{{--
    Trail card: a payment, withdrawal or any process shown as steps along a rail, with the step where it stopped
    called out in plain words. This is the assistant's signature answer: most support questions are "where did it stop?".

    Step states: done · current (in progress) · stuck (needs a person) · pending · failed.
    Rendered inside the widget's x-for, so `card` is the card JSON.
--}}
<article class="overflow-hidden rounded-xl border border-neutral-200 bg-white">
    <header class="flex items-start justify-between gap-3 p-4 pb-3">
        <div class="min-w-0">
            <h3 class="font-secondary text-sm font-semibold leading-snug text-neutral-900" x-text="card.title"></h3>
            <p class="mt-0.5 text-xs leading-snug text-neutral-500" x-text="card.subtitle"></p>
        </div>
        <div class="shrink-0 text-right">
            <p class="font-tertiary text-base font-bold tabular-nums text-neutral-900" x-text="card.amount"></p>
            <span class="mt-1 inline-flex rounded-full px-2 py-0.5 text-xs font-medium" :class="tone(card.status.tone)" x-text="card.status.label"></span>
        </div>
    </header>

    <ol class="px-4 pb-4 pt-1">
        <template x-for="(s, si) in card.steps" :key="si">
            <li class="relative flex gap-3 pb-4 last:pb-0">
                {{-- The rail runs to the next step and is teal only where the process really got through --}}
                <span x-show="si < card.steps.length - 1" aria-hidden="true" class="absolute bottom-0 left-[9px] top-6 w-px"
                    :class="s.state === 'done' ? 'bg-teal-600' : 'bg-neutral-200'"></span>

                <span aria-hidden="true" class="relative z-10 mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full"
                    :class="{
                        'bg-teal-600 text-white': s.state === 'done',
                        'bg-amber-100 text-amber-700 ring-2 ring-amber-500': s.state === 'stuck',
                        'bg-white ring-2 ring-teal-600': s.state === 'current',
                        'bg-white ring-2 ring-neutral-300': s.state === 'pending',
                        'bg-red-600 text-white': s.state === 'failed',
                    }">
                    <x-icon name="check-solid" class="h-3 w-3" x-show="s.state === 'done'" />
                    <x-icon name="x-mark" class="h-3 w-3" x-show="s.state === 'failed'" />
                    <span x-show="s.state === 'stuck'" class="text-[11px] font-bold leading-none">!</span>
                    <span x-show="s.state === 'current'" class="h-2 w-2 rounded-full bg-teal-600"></span>
                </span>

                <div class="min-w-0 flex-1">
                    <p class="text-sm leading-5"
                        :class="{
                            'font-medium text-neutral-900': s.state === 'done',
                            'font-semibold text-amber-900': s.state === 'stuck',
                            'font-semibold text-neutral-900': s.state === 'current',
                            'text-neutral-400': s.state === 'pending',
                            'font-semibold text-red-800': s.state === 'failed',
                        }" x-text="s.label"></p>
                    <p x-show="s.detail" class="text-xs leading-snug text-neutral-500" x-text="s.detail"></p>
                    <p x-show="s.note" class="mt-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm leading-snug text-amber-900" x-text="s.note"></p>
                </div>
            </li>
        </template>
    </ol>

    <x-support.card.actions />
</article>
