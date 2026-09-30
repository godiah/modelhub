{{--
    "Quick templates": a modal for picking, creating and deleting reusable message templates.
    Open it with $dispatch('open-modal', 'message-templates'); choosing one dispatches a window event
    `message-template-selected` ({ subject, message }) that the message form listens for.
    Loads its list when the modal opens. Message text is only ever rendered with x-text.
--}}
<x-modal name="message-templates" max-width="6xl" focusable>
    <div x-data="{
        templates: [],
        loaded: false,
        loading: false,
        failed: false,
        query: '',
        creating: false,
        saving: false,
        errors: [],
        confirming: null,
        draft: { name: '', subject: '', message: '' },
        csrf: document.querySelector('meta[name=csrf-token]')?.content,
        get mine() { return this.filter(this.templates.filter(t => t.user_id !== null)); },
        get defaults() { return this.filter(this.templates.filter(t => t.user_id === null)); },
        filter(list) {
            const term = this.query.trim().toLowerCase();
            return term === '' ? list : list.filter(t => [t.name, t.subject, t.message].some(v => String(v).toLowerCase().includes(term)));
        },
        headers(extra = {}) { return { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': this.csrf, ...extra }; },
        async load() {
            if (this.loading) return;
            this.loading = !this.loaded;
            this.failed = false;
            try {
                const response = await fetch(@js(route('my-jobs.message-templates')), { headers: this.headers() });
                if (!response.ok) throw new Error(response.status);
                this.templates = await response.json();
                this.loaded = true;
            } catch (e) { this.failed = true; } finally { this.loading = false; }
        },
        use(template) {
            window.dispatchEvent(new CustomEvent('message-template-selected', { detail: { subject: template.subject, message: template.message } }));
        },
        async save() {
            this.saving = true;
            this.errors = [];
            try {
                const response = await fetch(@js(route('my-jobs.message-templates.store')), { method: 'POST', headers: this.headers({ 'Content-Type': 'application/json' }), body: JSON.stringify(this.draft) });
                const data = await response.json().catch(() => ({}));
                if (response.status === 422) { this.errors = Object.values(data.errors ?? {}).flat(); return; }
                if (!response.ok) throw new Error(response.status);
                this.templates.unshift(data);
                this.draft = { name: '', subject: '', message: '' };
                this.creating = false;
            } catch (e) { this.errors = [@js(__('The template could not be saved. Please try again.'))]; } finally { this.saving = false; }
        },
        async remove(template) {
            if (this.confirming !== template.id) { this.confirming = template.id; return; }
            try {
                const response = await fetch(@js(route('my-jobs.message-templates.destroy', 'TEMPLATE_ID')).replace('TEMPLATE_ID', template.id), { method: 'DELETE', headers: this.headers() });
                if (!response.ok) throw new Error(response.status);
                this.templates = this.templates.filter(t => t.id !== template.id);
            } catch (e) { this.failed = true; } finally { this.confirming = null; }
        },
    }" @open-modal.window="if (($event.detail?.name ?? $event.detail) === 'message-templates') load()">
        <x-modal.header :title="__('Quick templates')" icon="chat-bubble-text" />

        <div class="max-h-[70vh] overflow-y-auto px-6 py-5">
            <div class="mb-5 flex flex-col gap-3 sm:flex-row">
                <div class="relative min-w-0 flex-1">
                    <x-icon name="magnifying-glass" class="pointer-events-none absolute left-3.5 top-2.5 h-5 w-5 text-neutral-400" />
                    <label for="template-search" class="sr-only">{{ __('Search templates') }}</label>
                    <input type="search" id="template-search" x-model="query" placeholder="{{ __('Search templates…') }}" autocomplete="off"
                        class="block w-full rounded-xl border border-neutral-300 bg-white py-2 pl-11 pr-3 text-sm text-neutral-900 placeholder-neutral-400 focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25">
                </div>
                <x-btn variant="secondary" type="button" @click="creating = !creating; errors = []">
                    <x-icon name="plus" class="h-4 w-4" />
                    <span x-text="creating ? @js(__('Cancel')) : @js(__('New template'))">{{ __('New template') }}</span>
                </x-btn>
            </div>

            <!-- New template -->
            <form x-show="creating" x-cloak @submit.prevent="save()" class="mb-6 space-y-4 rounded-xl border border-neutral-200 bg-neutral-50 p-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="template-name" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('Template name') }}</label>
                        <input type="text" id="template-name" x-model="draft.name" maxlength="255" required placeholder="{{ __('e.g. Interview invite') }}"
                            class="block w-full rounded-xl border border-neutral-300 bg-white px-3 py-2 text-sm focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25">
                    </div>
                    <div>
                        <label for="template-subject" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('Subject') }}</label>
                        <input type="text" id="template-subject" x-model="draft.subject" maxlength="255" required
                            class="block w-full rounded-xl border border-neutral-300 bg-white px-3 py-2 text-sm focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25">
                    </div>
                </div>
                <div>
                    <label for="template-message" class="mb-1.5 flex items-center justify-between text-sm font-medium text-neutral-800">
                        <span>{{ __('Message') }}</span>
                        <span class="text-xs font-normal tabular-nums text-tertiary" x-text="draft.message.length + ' / 5000'">0 / 5000</span>
                    </label>
                    <textarea id="template-message" x-model="draft.message" rows="4" maxlength="5000" required
                        class="block w-full resize-none rounded-xl border border-neutral-300 bg-white px-3 py-2 text-sm focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25"></textarea>
                </div>
                <ul x-show="errors.length" x-cloak class="list-disc space-y-1 pl-5 text-xs text-red-600" role="alert">
                    <template x-for="error in errors" :key="error"><li x-text="error"></li></template>
                </ul>
                <div class="flex justify-end">
                    <x-btn type="submit" size="sm" ::disabled="saving">
                        <x-icon name="check" class="h-4 w-4" />
                        {{ __('Save template') }}
                    </x-btn>
                </div>
            </form>

            <p x-show="loading" class="flex items-center justify-center gap-2 py-10 text-sm text-tertiary"><x-spinner class="h-5 w-5" />{{ __('Loading templates…') }}</p>
            <p x-show="failed" x-cloak class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ __('The templates could not be loaded. Close this and try again.') }}</p>

            <div x-show="loaded && !loading" x-cloak class="space-y-6">
                <template x-for="group in [{ title: @js(__('Your templates')), list: mine, own: true }, { title: @js(__('Default templates')), list: defaults, own: false }]" :key="group.title">
                    <section x-show="group.list.length || (group.own && !query)">
                        <h3 class="mb-3 text-xs font-medium uppercase tracking-wide text-tertiary" x-text="group.title"></h3>
                        <p x-show="group.own && !group.list.length" class="rounded-xl border border-dashed border-neutral-300 px-4 py-5 text-center text-sm text-tertiary">{{ __('You have not saved any templates yet. Create one above and it will be here next time.') }}</p>
                        <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            <template x-for="template in group.list" :key="template.id">
                                <li class="flex flex-col rounded-xl border border-neutral-200 bg-white p-4">
                                    <p class="text-sm font-semibold text-neutral-900" x-text="template.name"></p>
                                    <p class="mt-0.5 truncate text-xs text-tertiary" x-text="template.subject"></p>
                                    <p class="mt-2 line-clamp-3 text-sm text-neutral-700" x-text="template.message"></p>
                                    <div class="mt-auto flex items-center justify-between gap-2 pt-4">
                                        <x-btn size="sm" type="button" @click="use(template); dismiss()">{{ __('Use') }}</x-btn>
                                        <button type="button" x-show="group.own" @click="remove(template)" @keydown.escape="confirming = null" @blur="confirming = null"
                                            :class="confirming === template.id ? 'font-medium text-red-600' : 'text-tertiary hover:text-neutral-800'"
                                            class="rounded px-1 text-xs focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40"
                                            x-text="confirming === template.id ? @js(__('Click again to delete')) : @js(__('Delete'))"></button>
                                    </div>
                                </li>
                            </template>
                        </ul>
                    </section>
                </template>
                <p x-show="query && !mine.length && !defaults.length" class="py-6 text-center text-sm text-tertiary">{{ __('No templates match your search.') }}</p>
            </div>
        </div>

        <x-modal.footer>
            <x-btn type="button" variant="secondary" x-on:click="dismiss()">{{ __('Close') }}</x-btn>
        </x-modal.footer>
    </div>
</x-modal>
