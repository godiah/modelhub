{{--
    Inline chat on the engagement's JSON endpoints. Message text is only ever rendered with x-text
    (never innerHTML), so a message can't inject markup. Loads when the tab is first shown and refreshes on each visit.
--}}
<x-card class="rounded-2xl">
<div x-data="{
    id: {{ $engagement->id }},
    loaded: false,
    loading: false,
    sending: false,
    error: '',
    draft: '',
    messages: [],
    csrf: document.querySelector('meta[name=csrf-token]')?.content,
    headers(extra = {}) {
        return { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': this.csrf, ...extra };
    },
    async open() {
        if (this.loading) return;
        this.loading = !this.loaded;
        this.error = '';
        try {
            const response = await fetch(@js(route('engagements.data', $engagement)), { headers: this.headers() });
            if (!response.ok) throw new Error();
            const data = await response.json();
            this.messages = Array.isArray(data.messages) ? data.messages : [];
            this.loaded = true;
            this.scrollDown();
            this.markRead();
        } catch (e) {
            this.error = @js(__('Messages could not be loaded. Please try again.'));
        } finally {
            this.loading = false;
        }
    },
    async markRead() {
        try {
            const response = await fetch(@js(route('messages.read', $engagement)), { method: 'PATCH', headers: this.headers() });
            if (response.ok) {
                document.querySelectorAll(`[data-unread-for='${this.id}']`).forEach(el => el.style.display = 'none');
            }
        } catch (e) {}
    },
    async send() {
        const content = this.draft.trim();
        if (!content || this.sending) return;
        this.sending = true;
        this.error = '';
        try {
            const response = await fetch(@js(route('messages.store', $engagement)), {
                method: 'POST',
                headers: this.headers({ 'Content-Type': 'application/json' }),
                body: JSON.stringify({ content }),
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(data.error || data.message || '');
            this.messages.push(data.message);
            this.draft = '';
            this.scrollDown();
        } catch (e) {
            this.error = e.message || @js(__('Your message could not be sent. Please try again.'));
        } finally {
            this.sending = false;
        }
    },
    scrollDown() {
        this.$nextTick(() => { if (this.$refs.list) this.$refs.list.scrollTop = this.$refs.list.scrollHeight; });
    },
    time(iso) {
        return new Date(iso).toLocaleString([], { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
    },
}" x-effect="tab === 'messages' && $nextTick(() => open())">
    <div x-ref="list" class="h-[22rem] max-h-[50vh] space-y-3 overflow-y-auto bg-neutral-50/60 p-5" aria-live="polite">
        <div x-show="loading" class="flex h-full flex-col items-center justify-center gap-2 text-sm text-tertiary">
            <x-spinner class="h-6 w-6" />
            {{ __('Loading messages…') }}
        </div>

        <div x-show="loaded && messages.length === 0" x-cloak class="flex h-full flex-col items-center justify-center text-center">
            <x-icon name="chat-bubble-left-right" class="h-9 w-9 text-neutral-300" />
            <p class="mt-3 text-sm font-medium text-neutral-800">{{ __('No messages yet') }}</p>
            <p class="mt-1 text-xs text-tertiary">{{ __('Say hello to :name to get the conversation going.', ['name' => $summary['counterpart'] ?? __('the other party')]) }}</p>
        </div>

        <template x-for="message in messages" :key="message.id">
            <div :class="message.is_own ? 'items-end' : 'items-start'" class="flex flex-col">
                <div :class="message.is_own ? 'bg-primary text-white' : 'border border-neutral-200 bg-white text-neutral-800'"
                    class="max-w-[85%] whitespace-pre-line break-words rounded-2xl px-4 py-2.5 text-sm sm:max-w-[70%]"
                    x-text="message.content"></div>
                <p class="mt-1 px-1 text-xs text-tertiary">
                    <span x-show="!message.is_own" x-text="message.sender_name + ' · '"></span><span x-text="time(message.created_at)"></span>
                </p>
            </div>
        </template>
    </div>

    <form @submit.prevent="send()" class="border-t border-neutral-200 p-4">
        <p x-show="error" x-text="error" x-cloak class="mb-2 text-xs text-red-600" role="alert"></p>
        <div class="flex items-end gap-3">
            <label for="chat-draft" class="sr-only">{{ __('Your message') }}</label>
            <textarea id="chat-draft" x-model="draft" rows="2" maxlength="2000"
                @keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); send() }"
                placeholder="{{ __('Write a message… (Enter to send, Shift+Enter for a new line)') }}"
                class="block w-full resize-none rounded-xl border border-neutral-300 bg-white px-4 py-2.5 text-sm text-neutral-900 placeholder-neutral-400 transition-colors hover:border-neutral-400 focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25"></textarea>
            <x-btn type="submit" ::disabled="sending || !draft.trim()" aria-label="{{ __('Send message') }}">
                <x-icon name="paper-airplane" class="h-5 w-5" x-show="!sending" />
                <x-spinner class="h-5 w-5" x-show="sending" x-cloak />
            </x-btn>
        </div>
    </form>
</div>
</x-card>
