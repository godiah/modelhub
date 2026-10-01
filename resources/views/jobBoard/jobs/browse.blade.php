@php
    $postedOptions = ['' => __('Any time'), 'day' => __('Last 24 hours'), 'week' => __('Last 7 days'), 'month' => __('Last 30 days')];
    $sortOptions = [
        'newest' => __('Newest first'),
        'budget_high' => __('Budget: high to low'),
        'budget_low' => __('Budget: low to high'),
        'deadline' => __('Deadline: soonest'),
    ];
    $selectedSkills = $filters['skills'] ?? [];
    $selectedSoftware = $filters['software'] ?? [];
    $fieldClass = 'block w-full rounded-xl border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 placeholder-neutral-400 transition-colors hover:border-neutral-400 focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25';
    $checkboxClass = 'h-4 w-4 rounded border-neutral-300 text-teal-600 focus:ring-teal-600/30';
@endphp
<x-app-layout title="Browse projects">
    {{-- The filters are a real GET form, so the page works without JS; Alpine then swaps the results in place. --}}
    <div class="container mx-auto max-w-7xl px-4 py-8" x-data="{
        loading: false,
        failed: false,
        view: 'grid',
        count: {{ (int) $jobs->total() }},
        chips: [],
        names: @js(['skills' => $skills->pluck('name', 'id'), 'software' => $software->pluck('name', 'id')]),
        posted: @js(array_filter($postedOptions)),
        money: @js(config('app.currency_symbol')),
        timer: null,
        controller: null,
        init() {
            try { this.view = localStorage.getItem('browse-view') === 'list' ? 'list' : 'grid'; } catch (e) {}
            this.syncView();
            this.updateChips();
            window.addEventListener('popstate', () => {
                this.fillForm(new URLSearchParams(window.location.search));
                this.load(false);
            });
        },
        controls() { return Array.from(this.$refs.form.elements).filter(el => el.name); },
        params() {
            const params = new URLSearchParams();
            this.controls().forEach(el => {
                if ((el.type === 'checkbox' || el.type === 'radio') && !el.checked) return;
                const value = el.value.trim();
                if (value === '' || (el.name === 'sort' && value === 'newest')) return;
                params.append(el.name, value);
            });
            return params;
        },
        fillForm(params) {
            this.controls().forEach(el => {
                if (el.type === 'checkbox') el.checked = params.getAll(el.name).includes(el.value);
                else if (el.type === 'radio') el.checked = (params.get(el.name) ?? '') === el.value;
                else el.value = params.get(el.name) ?? (el.name === 'sort' ? 'newest' : '');
            });
        },
        schedule() { clearTimeout(this.timer); this.timer = setTimeout(() => this.load(true), 300); },
        apply() { clearTimeout(this.timer); this.load(true); },
        clear() {
            this.fillForm(new URLSearchParams());
            this.apply();
        },
        async load(push, url = null) {
            this.controller?.abort();
            this.controller = new AbortController();
            this.loading = true;
            this.failed = false;
            const query = url ? new URL(url, window.location.origin).searchParams : this.params();
            if (url) this.fillForm(query);
            const target = window.location.pathname + (query.toString() ? '?' + query.toString() : '');
            try {
                const response = await fetch(target, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: this.controller.signal });
                if (!response.ok) throw new Error(response.status);
                this.$refs.results.innerHTML = await response.text();
                this.count = Number(this.$refs.results.querySelector('[data-results-count]')?.dataset.resultsCount ?? 0);
                if (push) history.pushState({}, '', target);
                this.syncView();
                this.updateChips();
                if (url) this.$refs.top.scrollIntoView({ behavior: 'smooth', block: 'start' });
            } catch (e) {
                if (e.name !== 'AbortError') this.failed = true;
            } finally {
                this.loading = false;
            }
        },
        results(event) {
            const page = event.target.closest('[data-page-link]');
            if (page) { event.preventDefault(); this.load(true, page.getAttribute('href')); return; }
            if (event.target.closest('[data-clear-filters]')) this.clear();
        },
        setView(view) {
            this.view = view;
            try { localStorage.setItem('browse-view', view); } catch (e) {}
            this.syncView();
        },
        syncView() {
            this.$refs.results.querySelector('[data-jobs-grid]')?.classList.toggle('md:grid-cols-2', this.view === 'grid');
        },
        updateChips() {
            const params = this.params();
            const chips = [];
            if (params.get('search')) chips.push({ key: 'search', value: params.get('search'), label: '“' + params.get('search') + '”' });
            ['skills', 'software'].forEach(key => params.getAll(key + '[]').forEach(id => chips.push({ key, value: id, label: this.names[key][id] ?? id })));
            const min = params.get('budget_min'), max = params.get('budget_max');
            if (min || max) chips.push({ key: 'budget', value: '', label: min && max ? this.money + Number(min).toLocaleString() + '–' + Number(max).toLocaleString() : min ? this.money + Number(min).toLocaleString() + '+' : @js(__('Up to')) + ' ' + this.money + Number(max).toLocaleString() });
            if (params.get('posted')) chips.push({ key: 'posted', value: params.get('posted'), label: this.posted[params.get('posted')] });
            this.chips = chips;
        },
        remove(chip) {
            this.controls().forEach(el => {
                if (chip.key === 'budget' && (el.name === 'budget_min' || el.name === 'budget_max')) el.value = '';
                else if (chip.key === 'posted' && el.name === 'posted') el.checked = el.value === '';
                else if (el.name.replace('[]', '') === chip.key && (el.type === 'checkbox' ? el.value === chip.value : true)) { if (el.type === 'checkbox') el.checked = false; else el.value = ''; }
            });
            this.apply();
        },
    }">
        <!-- Header -->
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4" x-ref="top">
            <div>
                <h1 class="font-tertiary text-3xl font-semibold tracking-tight text-neutral-900">{{ __('3D projects') }}</h1>
                <p class="mt-2"><a href="{{ route('jobs.index', ['for' => 'work']) }}" class="text-sm font-medium text-teal-700 underline underline-offset-4 hover:text-teal-800">{{ __('Learn more how it works') }}</a></p>
            </div>
            <x-btn size="lg" href="{{ route('jobs.create') }}">{{ __('Start a project') }}</x-btn>
        </div>

        <!-- Filter bar: a real GET form, so the page works without JS; Alpine then swaps the results in place. -->
        <form id="browse-form" x-ref="form" method="GET" action="{{ route('jobs.browse') }}" role="search" @submit.prevent="apply()" class="mb-5 flex flex-wrap items-center gap-3">
            <div class="relative min-w-[16rem] flex-1">
                <x-icon name="magnifying-glass" class="pointer-events-none absolute left-3.5 top-3 h-5 w-5 text-neutral-400" />
                <label for="search" class="sr-only">{{ __('Search projects') }}</label>
                <input type="search" id="search" name="search" value="{{ $filters['search'] ?? '' }}" maxlength="255" autocomplete="off"
                    placeholder="{{ __('Search projects…') }}" @input="schedule()" class="{{ $fieldClass }} h-11 pl-11">
            </div>

            <x-browse.multi-filter name="software" :label="__('3D software')" :options="$software" :selected="$selectedSoftware" />
            <x-browse.multi-filter name="skills" :label="__('3D skills')" :options="$skills" :selected="$selectedSkills" />

            <!-- Budget and posted -->
            <div x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false" class="relative">
                <button type="button" @click="open = !open" :aria-expanded="open.toString()" aria-haspopup="true"
                    class="inline-flex h-11 items-center gap-2 rounded-xl border border-neutral-300 bg-white px-4 text-sm font-medium text-neutral-800 transition-colors hover:border-neutral-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40"
                    :class="chips.some(c => c.key === 'budget' || c.key === 'posted') && 'border-teal-600'">
                    {{ __('Budget & date') }}
                    <x-icon name="chevron-down" class="h-4 w-4 text-neutral-500" />
                </button>
                <div x-show="open" x-cloak x-transition.opacity.duration.100ms class="absolute left-0 z-30 mt-2 w-72 space-y-5 rounded-2xl border border-neutral-200 bg-white p-4 shadow-lg">
                    <div role="group" aria-labelledby="group-budget">
                        <h3 id="group-budget" class="mb-2 text-sm font-semibold text-neutral-900">{{ __('Budget') }} <span class="font-normal text-tertiary">({{ config('app.currency_symbol') }})</span></h3>
                        <div class="flex items-center gap-2">
                            <label class="sr-only" for="budget_min">{{ __('Minimum budget') }}</label>
                            <input type="number" id="budget_min" name="budget_min" min="0" step="1" inputmode="numeric" placeholder="{{ __('Min') }}" value="{{ $filters['budget_min'] ?? '' }}" @input="schedule()" class="{{ $fieldClass }} py-1.5">
                            <span class="text-tertiary" aria-hidden="true">–</span>
                            <label class="sr-only" for="budget_max">{{ __('Maximum budget') }}</label>
                            <input type="number" id="budget_max" name="budget_max" min="0" step="1" inputmode="numeric" placeholder="{{ __('Max') }}" value="{{ $filters['budget_max'] ?? '' }}" @input="schedule()" class="{{ $fieldClass }} py-1.5">
                        </div>
                    </div>
                    <div role="group" aria-labelledby="group-posted">
                        <h3 id="group-posted" class="mb-2 text-sm font-semibold text-neutral-900">{{ __('Posted') }}</h3>
                        <ul class="space-y-2">
                            @foreach ($postedOptions as $value => $label)
                                <li>
                                    <label class="flex cursor-pointer items-center gap-2.5 text-sm text-neutral-700">
                                        <input type="radio" name="posted" value="{{ $value }}" @checked(($filters['posted'] ?? '') === $value) @change="apply()" class="border-neutral-300 text-teal-600 focus:ring-teal-600/30">
                                        <span>{{ $label }}</span>
                                    </label>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>

            <label class="flex items-center gap-2 whitespace-nowrap text-sm text-tertiary">
                <span class="sr-only">{{ __('Sort by') }}</span>
                <select name="sort" @change="apply()" class="{{ $fieldClass }} h-11 w-auto pr-8 font-medium">
                    @foreach ($sortOptions as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['sort'] ?? 'newest') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </form>

        <div>
            <!-- Results -->
            <section aria-label="{{ __('Results') }}" class="min-w-0">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <p class="text-sm text-neutral-700" aria-live="polite">
                            <span class="font-semibold tabular-nums text-neutral-900" x-text="count">{{ $jobs->total() }}</span>
                            <span x-text="count === 1 ? @js(__('project')) : @js(__('projects'))">{{ trans_choice('project|projects', $jobs->total()) }}</span>
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="hidden rounded-xl border border-neutral-200 bg-white p-0.5 sm:inline-flex" role="group" aria-label="{{ __('Layout') }}">
                            <button type="button" @click="setView('list')" :aria-pressed="(view === 'list').toString()" aria-label="{{ __('List view') }}"
                                :class="view === 'list' ? 'bg-neutral-100 text-neutral-900' : 'text-neutral-400 hover:text-neutral-700'"
                                class="rounded-lg p-1.5 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40"><x-icon name="list-bullet" class="h-5 w-5" /></button>
                            <button type="button" @click="setView('grid')" :aria-pressed="(view === 'grid').toString()" aria-label="{{ __('Grid view') }}"
                                :class="view === 'grid' ? 'bg-neutral-100 text-neutral-900' : 'text-neutral-400 hover:text-neutral-700'"
                                class="rounded-lg p-1.5 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40"><x-icon name="squares-2x2" class="h-5 w-5" /></button>
                        </div>
                    </div>
                </div>

                <!-- Active filters -->
                <ul x-show="chips.length" x-cloak class="mb-4 flex flex-wrap items-center gap-2" aria-label="{{ __('Active filters') }}">
                    <template x-for="chip in chips" :key="chip.key + chip.value">
                        <li>
                            <button type="button" @click="remove(chip)" class="inline-flex items-center gap-1.5 rounded-full border border-neutral-300 bg-white py-1 pl-3 pr-2 text-xs font-medium text-neutral-800 transition-colors hover:border-neutral-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">
                                <span x-text="chip.label"></span>
                                <x-icon name="x-mark" class="h-3.5 w-3.5 text-neutral-400" />
                                <span class="sr-only">{{ __('Remove filter') }}</span>
                            </button>
                        </li>
                    </template>
                    <li><button type="button" @click="clear()" class="text-xs font-medium text-teal-700 hover:text-teal-800 focus:outline-none focus-visible:underline">{{ __('Clear all') }}</button></li>
                </ul>

                <p x-show="failed" x-cloak class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ __('The projects could not be loaded. Check your connection and try again.') }}</p>

                <div x-ref="results" @click="results($event)" :class="loading && 'opacity-50 transition-opacity'" :aria-busy="loading.toString()">
                    @include('jobBoard.jobs.partials.jobs-list')
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
