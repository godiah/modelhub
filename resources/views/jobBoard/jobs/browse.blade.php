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
        filtersOpen: false,
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
        <!-- Header + search -->
        <x-card class="mb-6 rounded-2xl" x-ref="top">
            <div class="p-6">
                <h1 class="font-tertiary text-2xl font-semibold text-neutral-900">{{ __('Browse projects') }}</h1>
                <p class="mt-1 text-sm text-tertiary">{{ __('Find 3D modelling and visualisation projects that match your skills.') }}</p>

                <form id="browse-form" x-ref="form" method="GET" action="{{ route('jobs.browse') }}" role="search" @submit.prevent="apply()" class="mt-5">
                    <div class="flex gap-3">
                        <div class="relative min-w-0 flex-1">
                            <x-icon name="magnifying-glass" class="pointer-events-none absolute left-3.5 top-3 h-5 w-5 text-neutral-400" />
                            <label for="search" class="sr-only">{{ __('Search projects') }}</label>
                            <input type="search" id="search" name="search" value="{{ $filters['search'] ?? '' }}" maxlength="255" autocomplete="off"
                                placeholder="{{ __('Search projects…') }}" @input="schedule()"
                                class="{{ $fieldClass }} py-2.5 pl-11 text-base">
                        </div>
                        <x-btn type="submit" size="lg" class="shrink-0">{{ __('Search') }}</x-btn>
                    </div>
                </form>
            </div>
        </x-card>

        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[17rem_minmax(0,1fr)]">
            <!-- Filter rail -->
            <aside :class="filtersOpen ? 'block' : 'hidden lg:block'" aria-label="{{ __('Filters') }}" class="space-y-6 rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm lg:sticky lg:top-24">
                <div class="flex items-center justify-between">
                    <h2 class="font-tertiary text-base font-semibold text-neutral-900">{{ __('Filters') }}</h2>
                    <button type="button" @click="clear()" x-show="chips.length" x-cloak class="text-sm font-medium text-teal-700 hover:text-teal-800 focus:outline-none focus-visible:underline">{{ __('Reset') }}</button>
                </div>

                @foreach ([
                    'skills' => [__('Skills'), $skills, $selectedSkills],
                    'software' => [__('Software'), $software, $selectedSoftware],
                ] as $name => [$label, $options, $selected])
                    <div x-data="{ q: '' }" role="group" aria-labelledby="group-{{ $name }}" class="border-t border-neutral-100 pt-5">
                        <h3 id="group-{{ $name }}" class="mb-3 text-sm font-semibold text-neutral-900">{{ $label }}</h3>
                        @if ($options->count() > 8)
                            <label class="sr-only" for="filter-{{ $name }}">{{ __('Filter :what', ['what' => strtolower($label)]) }}</label>
                            <input type="search" id="filter-{{ $name }}" x-model="q" placeholder="{{ __('Filter :what…', ['what' => strtolower($label)]) }}" class="{{ $fieldClass }} mb-3 py-1.5">
                        @endif
                        <ul class="max-h-52 space-y-2.5 overflow-y-auto pr-1">
                            @forelse ($options as $option)
                                <li x-show="!q || @js(strtolower($option->name)).includes(q.toLowerCase())">
                                    <label class="flex cursor-pointer items-center gap-2.5 text-sm text-neutral-700">
                                        <input type="checkbox" name="{{ $name }}[]" value="{{ $option->id }}" form="browse-form" @checked(in_array($option->id, $selected, true)) @change="schedule()" class="{{ $checkboxClass }}">
                                        <span>{{ $option->name }}</span>
                                    </label>
                                </li>
                            @empty
                                <li class="text-sm text-tertiary">{{ __('Nothing to filter by yet.') }}</li>
                            @endforelse
                        </ul>
                    </div>
                @endforeach

                <div role="group" aria-labelledby="group-budget" class="border-t border-neutral-100 pt-5">
                    <h3 id="group-budget" class="mb-3 text-sm font-semibold text-neutral-900">{{ __('Budget') }} <span class="font-normal text-tertiary">({{ config('app.currency_symbol') }})</span></h3>
                    <div class="flex items-center gap-2">
                        <label class="sr-only" for="budget_min">{{ __('Minimum budget') }}</label>
                        <input type="number" id="budget_min" name="budget_min" form="browse-form" min="0" step="1" inputmode="numeric" placeholder="{{ __('Min') }}" value="{{ $filters['budget_min'] ?? '' }}" @input="schedule()" class="{{ $fieldClass }} py-1.5">
                        <span class="text-tertiary" aria-hidden="true">–</span>
                        <label class="sr-only" for="budget_max">{{ __('Maximum budget') }}</label>
                        <input type="number" id="budget_max" name="budget_max" form="browse-form" min="0" step="1" inputmode="numeric" placeholder="{{ __('Max') }}" value="{{ $filters['budget_max'] ?? '' }}" @input="schedule()" class="{{ $fieldClass }} py-1.5">
                    </div>
                </div>

                <div role="group" aria-labelledby="group-posted" class="border-t border-neutral-100 pt-5">
                    <h3 id="group-posted" class="mb-3 text-sm font-semibold text-neutral-900">{{ __('Posted') }}</h3>
                    <ul class="space-y-2.5">
                        @foreach ($postedOptions as $value => $label)
                            <li>
                                <label class="flex cursor-pointer items-center gap-2.5 text-sm text-neutral-700">
                                    <input type="radio" name="posted" value="{{ $value }}" form="browse-form" @checked(($filters['posted'] ?? '') === $value) @change="apply()" class="border-neutral-300 text-teal-600 focus:ring-teal-600/30">
                                    <span>{{ $label }}</span>
                                </label>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </aside>

            <!-- Results -->
            <section aria-label="{{ __('Results') }}" class="min-w-0">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <button type="button" @click="filtersOpen = !filtersOpen" :aria-expanded="filtersOpen.toString()"
                            class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 bg-white px-3 py-2 text-sm font-medium text-neutral-700 transition-colors hover:border-neutral-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40 lg:hidden">
                            <x-icon name="funnel" class="h-4 w-4" />
                            {{ __('Filters') }}
                            <span x-show="chips.length" x-cloak x-text="chips.length" class="rounded-full bg-teal-600 px-1.5 text-xs font-semibold text-white"></span>
                        </button>
                        <p class="text-sm text-neutral-700" aria-live="polite">
                            <span class="font-semibold tabular-nums text-neutral-900" x-text="count">{{ $jobs->total() }}</span>
                            <span x-text="count === 1 ? @js(__('project')) : @js(__('projects'))">{{ trans_choice('project|projects', $jobs->total()) }}</span>
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <label class="flex items-center gap-2 whitespace-nowrap text-sm text-tertiary">
                            <span class="hidden sm:inline">{{ __('Sort by') }}</span>
                            <select name="sort" form="browse-form" @change="apply()" class="{{ $fieldClass }} w-auto py-1.5 pr-8 font-medium">
                                @foreach ($sortOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(($filters['sort'] ?? 'newest') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>

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
