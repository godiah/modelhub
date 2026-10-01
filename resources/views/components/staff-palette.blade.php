@props(['menu', 'staff'])

{{--
    The command palette (Ctrl/Cmd+K, or the search box in the top bar): jump to any page you may open, run a quick action, or search
    members, projects, hires, models, stores, disputes and staff. Pages and actions are filtered in the browser; searching asks
    the server, which only returns what this person may view.
--}}
@php
    $pages = collect($menu)->flatMap(fn ($group) => collect($group['items'])->map(fn ($item) => ['title' => __($item['label']), 'subtitle' => __($group['label']), 'url' => route($item['route']), 'icon' => $item['icon']]))->values();
    $actions = collect([
        $staff->can('manage staff') ? ['title' => __('Invite staff'), 'subtitle' => __('Action'), 'url' => route('admin.staff.create'), 'icon' => 'plus'] : null,
        $staff->can('manage roles') ? ['title' => __('New role'), 'subtitle' => __('Action'), 'url' => route('admin.roles.create'), 'icon' => 'plus'] : null,
        $staff->hasRole(\App\Support\Staff\StaffAccess::SUPER_ADMIN) ? ['title' => __('Security settings'), 'subtitle' => __('Settings'), 'url' => route('admin.settings.security'), 'icon' => 'cog-6-tooth'] : null,
        ['title' => __('Your account'), 'subtitle' => __('Account'), 'url' => route('admin.account.edit'), 'icon' => 'user'],
    ])->filter()->values();
@endphp
<div x-data="{
        open: false, q: '', active: 0, loading: false, remote: [], seq: 0,
        pages: @js($pages), actions: @js($actions), searchUrl: @js(route('admin.search')),
        get groups() {
            const needle = this.q.trim().toLowerCase();
            const match = (i) => ! needle || (i.title + ' ' + i.subtitle).toLowerCase().includes(needle);
            const local = [
                { label: @js(__('Pages')), items: this.pages.filter(match) },
                { label: @js(__('Actions')), items: this.actions.filter(match) },
            ];
            let n = 0;
            return [...local, ...this.remote].filter(g => g.items.length).map(g => ({ ...g, items: g.items.map(i => ({ ...i, icon: i.icon || g.icon, idx: n++ })) }));
        },
        get flat() { return this.groups.flatMap(g => g.items); },
        show() { this.open = true; this.q = ''; this.remote = []; this.active = 0; this.$nextTick(() => this.$refs.input.focus()); },
        close() { this.open = false; },
        move(step) { const n = this.flat.length; if (! n) return; this.active = (this.active + step + n) % n; this.$nextTick(() => document.getElementById('palette-item-' + this.active)?.scrollIntoView({ block: 'nearest' })); },
        go(item) { if (! item) return; this.close(); window.Livewire ? Livewire.navigate(item.url) : (window.location = item.url); },
        search() {
            this.active = 0;
            const term = this.q.trim();
            if (term.length < 2) { this.remote = []; this.loading = false; return; }
            const mine = ++this.seq; this.loading = true;
            fetch(this.searchUrl + '?q=' + encodeURIComponent(term), { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                .then(r => r.ok ? r.json() : { groups: [] })
                .then(d => { if (mine === this.seq) { this.remote = d.groups; this.loading = false; } })
                .catch(() => { if (mine === this.seq) this.loading = false; });
        },
    }"
    @keydown.window="if (($event.ctrlKey || $event.metaKey) && $event.key.toLowerCase() === 'k') { $event.preventDefault(); open ? close() : show(); }"
    @open-palette.window="show()">

    <div x-show="open" x-cloak class="fixed inset-0 z-[60] flex items-start justify-center px-4 pt-[12vh]" role="dialog" aria-modal="true" aria-label="{{ __('Search and jump to') }}" @keydown.escape.prevent="close()">
        <div x-show="open" x-transition.opacity @click="close()" class="absolute inset-0 bg-neutral-900/40" aria-hidden="true"></div>

        <div x-show="open" x-transition:enter="transition duration-150 ease-out" x-transition:enter-start="scale-95 opacity-0" x-transition:enter-end="scale-100 opacity-100" x-trap.noscroll="open"
            class="relative w-full max-w-xl overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-2xl">
            <div class="flex items-center gap-3 border-b border-neutral-100 px-4">
                <x-icon name="magnifying-glass" class="h-5 w-5 shrink-0 text-neutral-400" />
                <input x-ref="input" x-model="q" @input.debounce.200ms="search()" type="text" autocomplete="off" spellcheck="false" placeholder="{{ __('Search or jump to a page…') }}"
                    role="combobox" aria-expanded="true" aria-controls="palette-list" :aria-activedescendant="flat.length ? 'palette-item-' + active : null" aria-label="{{ __('Search') }}"
                    @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)" @keydown.enter.prevent="go(flat[active])"
                    class="w-full border-0 bg-transparent py-4 text-sm text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-0">
                <span x-show="loading" x-cloak class="shrink-0"><x-spinner class="h-4 w-4" /></span>
                <kbd class="hidden shrink-0 rounded border border-neutral-200 bg-neutral-50 px-1.5 py-0.5 font-sans text-[11px] font-medium text-neutral-500 sm:block">Esc</kbd>
            </div>

            <div id="palette-list" role="listbox" class="max-h-[50vh] overflow-y-auto p-2">
                <template x-for="group in groups" :key="group.label">
                    <div class="mb-1">
                        <p class="px-3 pb-1 pt-2 text-[11px] font-semibold uppercase tracking-wider text-neutral-400" x-text="group.label"></p>
                        <template x-for="item in group.items" :key="item.idx">
                            <a :id="'palette-item-' + item.idx" :href="item.url" role="option" :aria-selected="(item.idx === active).toString()" @click.prevent="go(item)" @mousemove="active = item.idx"
                                :class="item.idx === active ? 'bg-teal-50 text-neutral-900' : 'text-neutral-700'" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-neutral-100 text-neutral-500" :class="item.idx === active ? '!bg-teal-100 !text-teal-700' : ''">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use :href="'#palette-icon-' + item.icon" /></svg>
                                </span>
                                <span class="min-w-0 flex-1"><span class="block truncate font-medium" x-text="item.title"></span><span class="block truncate text-xs text-tertiary" x-text="item.subtitle"></span></span>
                                <span x-show="item.idx === active" class="text-xs text-teal-700" aria-hidden="true">↵</span>
                            </a>
                        </template>
                    </div>
                </template>
                <p x-show="! flat.length && ! loading" x-cloak class="px-3 py-8 text-center text-sm text-tertiary">{{ __('Nothing matches. Try a name, a project title or a page.') }}</p>
            </div>

            <div class="flex items-center gap-4 border-t border-neutral-100 bg-neutral-50/60 px-4 py-2 text-xs text-tertiary">
                <span><kbd class="font-sans font-medium">↑↓</kbd> {{ __('to move') }}</span><span><kbd class="font-sans font-medium">↵</kbd> {{ __('to open') }}</span><span><kbd class="font-sans font-medium">Esc</kbd> {{ __('to close') }}</span>
            </div>
        </div>
    </div>

    {{-- The icons the palette can show, as symbols it points at (icons are inline SVG paths in config/icons.php) --}}
    <svg style="position:absolute;width:0;height:0;overflow:hidden" aria-hidden="true" focusable="false">
        @foreach (collect($pages)->pluck('icon')->merge($actions->pluck('icon'))->merge(['user-group', 'briefcase', 'chat-bubble-left-right', 'cube', 'tag', 'scale', 'users'])->unique() as $icon)
            @php $def = config('icons.'.$icon); @endphp
            @if ($def)<symbol id="palette-icon-{{ $icon }}" viewBox="{{ $def['viewBox'] }}"><path d="{{ $def['d'] }}" /></symbol>@endif
        @endforeach
    </svg>
</div>
