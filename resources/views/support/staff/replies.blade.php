@php
    use App\Support\SupportChat\PreviewTickets as Tickets;

    $replies = Tickets::savedReplies();
    $categories = collect($replies)->pluck('category')->unique()->values()->all();
    $vars = [
        'member_name' => ['Achieng', __('Their first name')],
        'reference' => ['SUP-1042', __('The request reference')],
        'staff_name' => ['Grace', __('Your first name')],
        'first_reply_time' => ['4 business hours', __('From the service levels')],
        'min_withdrawal' => ['Ksh500', __('From the live fee settings')],
    ];
@endphp

<x-staff-layout :title="__('Saved replies')">
    <div class="container mx-auto max-w-7xl px-4 py-8" x-data="{
        replies: @js($replies), vars: @js(collect($vars)->map(fn ($v) => $v[0])->all()),
        category: 'all', q: '', current: null, form: null, deleting: false, saved: false,
        init() { this.pick(this.replies[0]); },
        get shown() { return this.replies.filter(r => (this.category === 'all' || r.category === this.category) && (this.q === '' || (r.title + ' ' + r.body).toLowerCase().includes(this.q.toLowerCase()))); },
        get dirty() { return this.current && JSON.stringify(this.form) !== JSON.stringify(this.current); },
        pick(r) { this.current = r ? { ...r } : null; this.form = r ? { ...r } : null; this.saved = false; },
        create() { this.current = { id: null, title: '', category: 'General', body: '', shared: true, used: 0, edited: '' }; this.form = { ...this.current, title: 'Untitled reply' }; },
        get preview() { return (this.form ? this.form.body : '').replace(/\{(\w+)\}/g, (m, k) => this.vars[k] !== undefined ? this.vars[k] : m); },
        get unknown() { return [...new Set(((this.form ? this.form.body : '').match(/\{(\w+)\}/g) || []).filter(m => this.vars[m.slice(1, -1)] === undefined))]; },
        get promises() { return /\b(guarantee|guaranteed|definitely|will be refunded|you will get your money|within 24 hours)\b/i.test(this.form ? this.form.body : ''); },
        get literalAmount() { return /\bKsh\s?\d/i.test(this.form ? this.form.body : ''); },
        insert(name) { const el = this.$refs.body; const s = el.selectionStart, e = el.selectionEnd; this.form.body = this.form.body.slice(0, s) + '{' + name + '}' + this.form.body.slice(e); this.$nextTick(() => { el.focus(); el.selectionStart = el.selectionEnd = s + name.length + 2; }); },
        save() { const i = this.replies.findIndex(r => r.id === this.form.id); if (i >= 0) { this.replies[i] = { ...this.form }; } else { this.form.id = Date.now(); this.replies.unshift({ ...this.form }); } this.current = { ...this.form }; this.saved = true; },
        remove() { this.replies = this.replies.filter(r => r.id !== this.form.id); this.deleting = false; this.pick(this.replies[0]); },
    }">
        <x-staff.header :title="__('Saved replies')" :description="__('Answers the team uses again and again. Write them once so every member hears the same thing, and so the assistant and staff never disagree.')">
            <x-slot:actions>
                <x-btn type="button" @click="create()"><x-icon name="plus" class="h-4 w-4" />{{ __('New reply') }}</x-btn>
            </x-slot:actions>
        </x-staff.header>

        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[22rem_minmax(0,1fr)]">
            {{-- The list --}}
            <section class="rounded-2xl border border-neutral-200 bg-white shadow-sm" aria-label="{{ __('All saved replies') }}">
                <div class="space-y-3 border-b border-neutral-100 p-4">
                    <div class="relative">
                        <x-icon name="magnifying-glass" class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-neutral-400" />
                        <label for="reply-search" class="sr-only">{{ __('Search saved replies') }}</label>
                        <input id="reply-search" type="search" x-model="q" placeholder="{{ __('Search replies') }}" class="block w-full rounded-xl border border-neutral-300 bg-white py-2 pl-9 pr-3 text-sm focus:border-teal-600 focus:ring-teal-600">
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        <button type="button" @click="category = 'all'" :aria-pressed="(category === 'all').toString()" :class="category === 'all' ? 'border-teal-600 bg-teal-600 text-white' : 'border-neutral-300 bg-white text-neutral-700 hover:bg-neutral-50'" class="rounded-full border px-3 py-1 text-xs font-medium transition">{{ __('All') }}</button>
                        @foreach ($categories as $c)
                            <button type="button" @click="category = '{{ $c }}'" :aria-pressed="(category === '{{ $c }}').toString()" :class="category === '{{ $c }}' ? 'border-teal-600 bg-teal-600 text-white' : 'border-neutral-300 bg-white text-neutral-700 hover:bg-neutral-50'" class="rounded-full border px-3 py-1 text-xs font-medium transition">{{ __($c) }}</button>
                        @endforeach
                    </div>
                </div>
                <ul class="max-h-[34rem] divide-y divide-neutral-100 overflow-y-auto">
                    <template x-for="r in shown" :key="r.id">
                        <li>
                            <button type="button" @click="pick(r)" :aria-current="(current && current.id === r.id).toString()" :class="current && current.id === r.id ? 'bg-teal-50/60' : 'hover:bg-neutral-50'"
                                class="block w-full px-4 py-3 text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-secondary/40">
                                <span class="flex items-center justify-between gap-2"><span class="truncate text-sm font-semibold text-neutral-900" x-text="r.title"></span><span x-show="! r.shared" class="shrink-0 rounded-full bg-neutral-100 px-2 py-0.5 text-[11px] font-medium text-neutral-600">{{ __('Only me') }}</span></span>
                                <span class="mt-0.5 flex items-center gap-2 text-xs text-neutral-500"><span x-text="r.category"></span><span aria-hidden="true">·</span><span><span x-text="r.used"></span> {{ __('uses') }}</span></span>
                            </button>
                        </li>
                    </template>
                    <li x-show="shown.length === 0" x-cloak class="px-4 py-8 text-center text-sm text-neutral-500">{{ __('No replies match.') }}</li>
                </ul>
            </section>

            {{-- The editor --}}
            <div class="min-w-0 space-y-6" x-show="form" x-cloak>
                <x-panel :title="__('Reply')" :description="__('How it reads when staff insert it into a message.')">
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="sm:col-span-2">
                            <label for="r-title" class="block text-sm font-medium text-neutral-800">{{ __('Name') }}</label>
                            <input id="r-title" type="text" x-model="form.title" class="mt-1 block w-full rounded-xl border-neutral-300 text-sm focus:border-teal-600 focus:ring-teal-600">
                        </div>
                        <div>
                            <label for="r-cat" class="block text-sm font-medium text-neutral-800">{{ __('Topic') }}</label>
                            <select id="r-cat" x-model="form.category" class="mt-1 block w-full rounded-xl border-neutral-300 text-sm focus:border-teal-600 focus:ring-teal-600">
                                @foreach (['Payments', 'Refunds', 'Account access', 'Withdrawals', 'General'] as $c)<option>{{ __($c) }}</option>@endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label for="r-body" class="block text-sm font-medium text-neutral-800">{{ __('Message') }}</label>
                        <div class="mt-1.5 flex flex-wrap items-center gap-1.5" role="group" aria-label="{{ __('Insert a variable') }}">
                            <span class="mr-1 text-xs text-neutral-500">{{ __('Insert') }}</span>
                            @foreach ($vars as $name => [$sample, $hint])
                                <button type="button" @click="insert('{{ $name }}')" title="{{ $hint }}" class="rounded-md border border-neutral-200 bg-neutral-50 px-2 py-1 font-mono text-xs text-neutral-700 transition hover:border-teal-300 hover:bg-teal-50 hover:text-teal-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">{{ '{'.$name.'}' }}</button>
                            @endforeach
                        </div>
                        <textarea id="r-body" x-ref="body" x-model="form.body" rows="10" class="mt-2 block w-full rounded-xl border-neutral-300 text-[15px] leading-snug focus:border-teal-600 focus:ring-teal-600"></textarea>

                        {{-- Checks that run as you type: the same rules the assistant follows --}}
                        <ul class="mt-3 space-y-2 text-sm" aria-live="polite">
                            <li x-show="promises" x-cloak class="flex gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-amber-900"><x-icon name="exclamation-triangle" class="mt-0.5 h-4 w-4 shrink-0 text-amber-700" /><span>{{ __('This promises an outcome or a time. Say who decides, or what happens next, instead.') }}</span></li>
                            <li x-show="literalAmount" x-cloak class="flex gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-amber-900"><x-icon name="information-circle" class="mt-0.5 h-4 w-4 shrink-0 text-amber-700" /><span>{{ __('This has an amount typed in. Use a variable such as {min_withdrawal} so it never goes out of date when a fee changes.') }}</span></li>
                            <li x-show="unknown.length" x-cloak class="flex gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-red-900"><x-icon name="exclamation-circle" class="mt-0.5 h-4 w-4 shrink-0 text-red-700" /><span>{{ __('Unknown variable') }}: <span class="font-mono" x-text="unknown.join(' ')"></span></span></li>
                        </ul>
                    </div>

                    <fieldset class="mt-4">
                        <legend class="text-sm font-medium text-neutral-800">{{ __('Who can use it') }}</legend>
                        <div class="mt-2 flex flex-wrap gap-x-6 gap-y-2 text-sm text-neutral-700">
                            <label class="flex items-center gap-2"><input type="radio" :value="true" x-model="form.shared" class="text-teal-600 focus:ring-teal-600/30">{{ __('The whole team') }}</label>
                            <label class="flex items-center gap-2"><input type="radio" :value="false" x-model="form.shared" class="text-teal-600 focus:ring-teal-600/30">{{ __('Only me') }}</label>
                        </div>
                    </fieldset>

                    <x-slot:footer>
                        <x-btn type="button" variant="danger-outline" size="sm" @click="deleting = true" x-show="form.id"><x-icon name="trash" class="h-4 w-4" />{{ __('Delete') }}</x-btn>
                        <span x-show="! form.id"></span>
                        <div class="flex items-center gap-3">
                            <span x-show="saved && ! dirty" x-cloak class="flex items-center gap-1.5 text-sm text-teal-700"><x-icon name="check" class="h-4 w-4" />{{ __('Saved') }}</span>
                            <x-btn type="button" variant="secondary" x-show="dirty" x-cloak @click="pick(current)">{{ __('Discard changes') }}</x-btn>
                            <x-btn type="button" x-bind:disabled="! dirty || unknown.length > 0 || ! form.title.trim() || ! form.body.trim()" @click="save()">{{ __('Save reply') }}</x-btn>
                        </div>
                    </x-slot:footer>
                </x-panel>

                <x-panel :title="__('How the member sees it')" :description="__('With example values filled in. Staff can still edit it before they send.')">
                    <div class="rounded-xl border border-neutral-200 bg-white px-4 py-3">
                        <p class="flex justify-between gap-3 text-sm"><span class="font-semibold text-neutral-900">Grace <span class="font-normal text-neutral-500">· ModelHub support</span></span><span class="text-xs text-neutral-500">{{ __('Just now') }}</span></p>
                        <p class="mt-1.5 whitespace-pre-line text-[15px] leading-[1.55] text-neutral-800" x-text="preview"></p>
                    </div>
                </x-panel>
            </div>
        </div>

        <x-modal name="delete-reply" bind="deleting" max-width="md">
            <x-modal.header :title="__('Delete this saved reply?')" icon="trash" />
            <div class="px-6 py-5 text-sm text-neutral-700">
                <p><span class="font-medium text-neutral-900" x-text="form ? form.title : ''"></span> {{ __('will be removed for everyone who can use it. Messages already sent are not changed.') }}</p>
            </div>
            <x-modal.footer>
                <x-btn type="button" variant="secondary" @click="deleting = false">{{ __('Cancel') }}</x-btn>
                <x-btn type="button" variant="danger" @click="remove()">{{ __('Delete reply') }}</x-btn>
            </x-modal.footer>
        </x-modal>
    </div>
</x-staff-layout>
