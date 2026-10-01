@props([
    'kind' => 'people',
    'current' => null,
    'fallback' => 0,
    'action' => null,
    'name' => 'avatar',
    'title' => null,
    'hint' => null,
    'shape' => 'rounded-full',
])

{{--
    Choose an avatar from the catalogue (App\Support\Avatars). kind: people (members) | stores.

    With an `action` it is its own form: picking one (or "Surprise me") saves at once with a PATCH and the page reloads
    showing it. Without one it only sets a hidden `avatar` field inside the form around it (the store settings page, where
    changes are saved together) and announces the pick with an `avatar-chosen` window event, so previews elsewhere on the
    page can follow along. Only the active style's pictures are in the page at a time, so the picker stays light.
--}}
@php
    $start = \App\Support\Avatars::isValid($kind, $current) ? $current : \App\Support\Avatars::fallback($kind, $fallback);
    $dialog = 'avatar-picker-'.$kind;
    $base = asset(config('avatars.path').'/'.$kind);
    $tag = $action ? 'form' : 'div';
@endphp

<{{ $tag }} @if ($action) method="POST" action="{{ $action }}" x-ref="form" @endif
    x-data="{
        key: @js($start),
        style: @js(explode('/', $start)[0]),
        styles: @js(\App\Support\Avatars::catalogue($kind)),
        base: @js($base),
        submits: @js((bool) $action),
        get active() { return this.styles.find(s => s.key === this.style) ?? this.styles[0] },
        get url() { return this.base + '/' + this.key + '.svg' },
        choose(style, seed) {
            this.key = style + '/' + seed;
            this.style = style;
            this.$dispatch('avatar-chosen', { key: this.key, url: this.url });
            this.$dispatch('close-modal', '{{ $dialog }}');
            if (this.submits) this.$nextTick(() => this.$refs.form.requestSubmit());
        },
        surprise() {
            const style = this.styles[Math.floor(Math.random() * this.styles.length)];
            this.choose(style.key, style.seeds[Math.floor(Math.random() * style.seeds.length)]);
        },
    }">
    @if ($action)
        @csrf
        @method('PATCH')
    @endif
    <input type="hidden" name="{{ $name }}" :value="key">

    <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
        <img :src="url" alt="" width="96" height="96" class="h-24 w-24 shrink-0 {{ $shape }} border border-neutral-200 bg-white object-cover">
        <div class="min-w-0">
            @if ($hint)<p class="text-sm text-tertiary">{{ $hint }}</p>@endif
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <x-btn type="button" variant="secondary" x-on:click="$dispatch('open-modal', '{{ $dialog }}')">
                    <x-icon name="photo" class="h-4 w-4" />{{ __('Choose an avatar') }}
                </x-btn>
                <x-btn type="button" variant="ghost" x-on:click="surprise()">
                    <x-icon name="arrow-path" class="h-4 w-4" />{{ __('Surprise me') }}
                </x-btn>
            </div>
        </div>
    </div>

    <x-modal :name="$dialog" max-width="3xl" focusable>
        <div class="flex items-start justify-between gap-4 px-6 pt-6">
            <div>
                <h3 class="font-tertiary text-lg font-semibold text-neutral-900">{{ $title ?? __('Choose your avatar') }}</h3>
                <p class="mt-1 text-sm text-tertiary">{{ __('Pick a style, then the one you like.') }}</p>
            </div>
            <button type="button" x-on:click="dismiss()" aria-label="{{ __('Close') }}" class="rounded-lg p-1.5 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">
                <x-icon name="x-mark" class="h-5 w-5" />
            </button>
        </div>

        <div class="px-6 pt-4">
            <div role="tablist" aria-label="{{ __('Avatar styles') }}" class="-mx-1 flex gap-1.5 overflow-x-auto px-1 pb-2 [scrollbar-width:thin]">
                <template x-for="s in styles" :key="s.key">
                    <button type="button" role="tab" :aria-selected="(style === s.key).toString()" x-on:click="style = s.key" x-text="s.label"
                        :class="style === s.key ? 'border-teal-600 bg-teal-600 text-white' : 'border-neutral-200 bg-white text-neutral-700 hover:border-neutral-300 hover:bg-neutral-50'"
                        class="shrink-0 rounded-full border px-3.5 py-1.5 text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40"></button>
                </template>
            </div>
        </div>

        <div class="max-h-[55vh] overflow-y-auto px-6 py-4" role="tabpanel">
            <ul class="grid grid-cols-4 gap-3 sm:grid-cols-6 md:grid-cols-8">
                <template x-for="seed in active.seeds" :key="active.key + '/' + seed">
                    <li>
                        <button type="button" x-on:click="choose(active.key, seed)" :aria-pressed="(key === active.key + '/' + seed).toString()" :aria-label="active.label + ' ' + seed"
                            :class="key === active.key + '/' + seed ? 'ring-4 ring-teal-600 ring-offset-2' : 'ring-1 ring-black/5 hover:ring-2 hover:ring-teal-300'"
                            class="block aspect-square w-full overflow-hidden {{ $shape === 'rounded-full' ? 'rounded-2xl' : $shape }} bg-white transition focus:outline-none focus-visible:ring-4 focus-visible:ring-secondary/60">
                            <img :src="base + '/' + active.key + '/' + seed + '.svg'" alt="" loading="lazy" class="h-full w-full object-cover">
                        </button>
                    </li>
                </template>
            </ul>
        </div>

        <x-modal.footer>
            <x-btn type="button" variant="ghost" x-on:click="surprise()"><x-icon name="arrow-path" class="h-4 w-4" />{{ __('Surprise me') }}</x-btn>
            <x-btn type="button" variant="secondary" x-on:click="dismiss()">{{ __('Cancel') }}</x-btn>
        </x-modal.footer>
    </x-modal>
</{{ $tag }}>
