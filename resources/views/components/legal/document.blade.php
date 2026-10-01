@props(['title', 'sections', 'intro' => null, 'kicker' => null, 'effective' => null, 'updated' => null])

{{--
    A legal/policy document: header card (title, intro, dates), a table of contents that follows the reader,
    and the article. Fill the slot with <x-policy.section id number title> blocks whose ids match $sections
    (id => label). Works for guests and signed-in users (the page wraps it in <x-app-layout>).
--}}
<div class="container mx-auto max-w-7xl px-4 py-8">
    <x-card class="mb-6 rounded-2xl">
        <div class="p-6">
            <p class="text-xs font-medium uppercase tracking-wide text-tertiary">{{ $kicker ?? __('Legal') }}</p>
            <h1 class="mt-1 font-tertiary text-2xl font-semibold text-neutral-900">{{ $title }}</h1>
            @if ($intro)
                <p class="mt-2 max-w-3xl text-sm text-neutral-700">{{ $intro }}</p>
            @endif
            @if ($effective || $updated)
                <p class="mt-3 flex flex-wrap gap-x-5 gap-y-1 text-xs text-tertiary">
                    @if ($effective)<span>{{ __('Effective date: :date', ['date' => $effective]) }}</span>@endif
                    @if ($updated)<span>{{ __('Last updated: :date', ['date' => $updated]) }}</span>@endif
                </p>
            @endif
        </div>
    </x-card>

    <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[16rem_minmax(0,1fr)]" x-data="{
        active: @js(array_key_first($sections)),
        init() {
            const observer = new IntersectionObserver((entries) => {
                const visible = entries.filter(entry => entry.isIntersecting);
                if (visible.length) this.active = visible[0].target.id;
            }, { rootMargin: '-80px 0px -65% 0px' });
            this.$root.querySelectorAll('section[id]').forEach(section => observer.observe(section));
        },
    }">
        <!-- Table of contents -->
        <nav aria-label="{{ __('On this page') }}" class="lg:sticky lg:top-24">
            <div class="-mx-4 overflow-x-auto px-4 [scrollbar-width:none] lg:mx-0 lg:px-0 [&::-webkit-scrollbar]:hidden">
                <p class="mb-2 hidden text-xs font-medium uppercase tracking-wide text-tertiary lg:block">{{ __('On this page') }}</p>
                <ol class="flex gap-1 lg:flex-col">
                    @foreach ($sections as $id => $label)
                        <li class="shrink-0">
                            <a href="#{{ $id }}" :aria-current="active === '{{ $id }}' ? 'true' : null"
                                :class="active === '{{ $id }}' ? 'bg-white font-medium text-neutral-900 shadow-sm ring-1 ring-neutral-200' : 'text-neutral-600 hover:bg-white/70 hover:text-neutral-900'"
                                class="flex items-baseline gap-2.5 whitespace-nowrap rounded-lg px-3 py-2 text-sm transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">
                                <span class="text-xs tabular-nums text-tertiary">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                {{ $label }}
                            </a>
                        </li>
                    @endforeach
                </ol>
            </div>
        </nav>

        <article class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm sm:p-8">
            {{ $slot }}
        </article>
    </div>
</div>
