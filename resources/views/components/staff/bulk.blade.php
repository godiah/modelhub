@props(['actions' => [], 'ids' => []])

{{--
    Bulk actions for a list. Wrap the list in this and give each selectable row/card an <x-staff.bulk-check> (tables do it with
    <x-staff.row :select="$id">). Ticking something shows a bar with the actions the person may run; each opens one dialog that says what
    will happen to how many items and, where the people affected are told why, asks for one shared reason. With no actions it is just the list.
      actions — BulkActions::forPage(...) result
      ids     — the ids a "select all on this page" ticks
--}}
@if ($actions === [])
    {{ $slot }}
@else
    @php $max = \App\Support\Staff\BulkActions::MAX; @endphp
    <div x-data="{
            selected: [], ids: @js(array_map('strval', array_values($ids))), actions: @js(collect($actions)->keyBy('key')->all()),
            urls: @js(collect($actions)->mapWithKeys(fn ($a) => [$a['key'] => route('admin.bulk', $a['key'])])->all()),
            dialog: null, reason: '', submitting: false, max: {{ $max }},
            get all() { return this.ids.length > 0 && this.ids.every(id => this.selected.includes(id)); },
            get some() { return this.selected.length > 0 && ! this.all; },
            toggleAll() { this.selected = this.all ? [] : [...this.ids]; },
            count(noun, n) { const [one, many] = noun.split('|'); return n + ' ' + (n === 1 ? one : many); },
            open(key) { this.dialog = this.actions[key]; this.reason = ''; this.$nextTick(() => this.$refs.reason && this.$refs.reason.focus()); },
            close() { this.dialog = null; },
        }" @keydown.escape.window="dialog ? close() : (selected = [])">
        {{ $slot }}

        {{-- The bar that appears once something is ticked --}}
        <div x-show="selected.length > 0 && ! dialog" x-cloak x-transition:enter="transition duration-150 ease-out" x-transition:enter-start="translate-y-4 opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
            role="region" aria-label="{{ __('Bulk actions') }}" class="fixed inset-x-4 bottom-4 z-40 mx-auto flex max-w-3xl flex-wrap items-center gap-x-4 gap-y-2 rounded-2xl border border-neutral-200 bg-white px-4 py-3 shadow-2xl">
            <p class="text-sm font-semibold text-neutral-900" aria-live="polite"><span x-text="selected.length"></span> {{ __('selected') }}</p>
            <p x-show="selected.length > max" x-cloak class="text-xs font-medium text-amber-700">{{ __('At most :n at a time.', ['n' => $max]) }}</p>
            <div class="ml-auto flex flex-wrap items-center gap-2">
                @foreach ($actions as $action)
                    <x-btn type="button" size="sm" :variant="$action['tone'] === 'danger' ? 'danger-outline' : 'primary'" x-bind:disabled="selected.length > max" @click="open('{{ $action['key'] }}')">{{ __($action['label']) }}</x-btn>
                @endforeach
                <button type="button" @click="selected = []" class="rounded-lg px-2.5 py-1.5 text-sm font-medium text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">{{ __('Clear') }}</button>
            </div>
        </div>

        {{-- One dialog for every action: what will happen, to how many, and the shared reason when one is needed --}}
        <div x-show="dialog" x-cloak class="fixed inset-0 z-[55] flex items-center justify-center px-4" role="dialog" aria-modal="true" :aria-label="dialog ? dialog.label : ''">
            <div x-show="dialog" x-transition.opacity @click="close()" class="absolute inset-0 bg-neutral-900/40" aria-hidden="true"></div>
            <form x-show="dialog" method="POST" :action="dialog ? urls[dialog.key] : '#'" x-trap.noscroll="dialog !== null" @submit="submitting = true" class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                @csrf
                <template x-for="id in selected" :key="id"><input type="hidden" name="ids[]" :value="id"></template>

                <h2 class="font-tertiary text-lg font-semibold text-neutral-900" x-text="dialog ? dialog.label + ' ' + count(dialog.noun, selected.length) + '?' : ''"></h2>
                <p class="mt-1.5 text-sm text-tertiary">
                    <span x-show="dialog && dialog.reason">{{ __('The people affected are shown your reason. It applies to everything you ticked.') }}</span>
                    <span x-show="dialog && ! dialog.reason">{{ __('This applies to everything you ticked. Anything that cannot take it is skipped and listed afterwards.') }}</span>
                </p>

                <div x-show="dialog && dialog.reason" class="mt-4">
                    <label for="bulk-reason" class="mb-1.5 block text-sm font-medium text-neutral-800" x-text="dialog ? dialog.reason : ''"></label>
                    <textarea id="bulk-reason" name="reason" x-ref="reason" x-model="reason" rows="3" minlength="5" maxlength="500" :required="dialog && dialog.reason ? true : false" :disabled="! (dialog && dialog.reason)"
                        class="block w-full rounded-xl border border-neutral-300 px-3 py-2 text-sm focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25"></textarea>
                    <p class="mt-1 text-xs text-tertiary">{{ __('At least 5 characters.') }}</p>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <x-btn type="button" variant="secondary" @click="close()">{{ __('Cancel') }}</x-btn>
                    <button type="submit" :disabled="submitting"
                        :class="dialog && dialog.tone === 'danger' ? 'bg-red-600 hover:bg-red-700 focus-visible:ring-red-300' : 'bg-teal-600 hover:bg-teal-700 focus-visible:ring-secondary/40'"
                        class="inline-flex items-center justify-center rounded-xl px-4 py-2 font-secondary text-sm font-semibold text-white transition-colors focus:outline-none focus-visible:ring-4 disabled:opacity-60" x-text="dialog ? dialog.label : ''"></button>
                </div>
            </form>
        </div>
    </div>
@endif
