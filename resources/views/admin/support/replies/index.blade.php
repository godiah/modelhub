@php
    $editing = $selected !== null;
    $form = $editing
        ? ['id' => $selected->id, 'title' => $selected->title, 'topic' => $selected->topic, 'body' => $selected->body, 'scope' => $selected->isShared() ? 'team' : 'me']
        : ['id' => null, 'title' => '', 'topic' => 'General', 'body' => '', 'scope' => 'team'];
    // After a failed save, show what was typed rather than what is stored
    $form = array_merge($form, array_filter(old(), fn ($v, $k) => in_array($k, ['title', 'topic', 'body', 'scope'], true), ARRAY_FILTER_USE_BOTH));
    $samples = collect($variables)->map(fn ($v) => $v['sample'])->all();
    $groups = $replies->groupBy('topic');
@endphp
<x-staff-layout :title="__('Saved replies')">
    <div class="container mx-auto max-w-7xl px-4 py-8" x-data="{
        form: @js($form), samples: @js($samples), q: '', deleting: false,
        get preview() { return this.form.body.replace(/\{(\w+)\}/g, (m, k) => this.samples[k] !== undefined ? this.samples[k] : m); },
        get unknown() { return [...new Set((this.form.body.match(/\{(\w+)\}/g) || []).filter(m => this.samples[m.slice(1, -1)] === undefined))]; },
        get promises() { return /\b(guarantee|guaranteed|definitely|will be refunded|you will get your money|within 24 hours)\b/i.test(this.form.body); },
        get literalAmount() { return /\bKsh\s?\d/i.test(this.form.body); },
        matches(text) { return this.q === '' || text.toLowerCase().includes(this.q.toLowerCase()); },
        insert(name) { const el = this.$refs.body; const s = el.selectionStart, e = el.selectionEnd; this.form.body = this.form.body.slice(0, s) + '{' + name + '}' + this.form.body.slice(e); this.$nextTick(() => { el.focus(); el.selectionStart = el.selectionEnd = s + name.length + 2; }); },
    }">
        <x-staff.header :title="__('Saved replies')" :description="__('Answers the team uses again and again. Write them once so every member hears the same thing. On a request, pick one and it is filled in for that member.')">
            <x-slot:actions>
                <x-btn :href="route('admin.support.replies.index', ['new' => 1])"><x-icon name="plus" class="h-4 w-4" />{{ __('New reply') }}</x-btn>
            </x-slot:actions>
        </x-staff.header>

        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[22rem_minmax(0,1fr)]">
            {{-- The list --}}
            <section class="rounded-2xl border border-neutral-200 bg-white shadow-sm" aria-label="{{ __('All saved replies') }}">
                <div class="border-b border-neutral-100 p-4">
                    <label for="reply-search" class="sr-only">{{ __('Search saved replies') }}</label>
                    <input id="reply-search" type="search" x-model="q" placeholder="{{ __('Search replies') }}" class="block w-full rounded-xl border border-neutral-300 bg-white py-2 px-3 text-sm focus:border-teal-600 focus:ring-teal-600">
                </div>
                @forelse ($groups as $topic => $items)
                    <h2 class="border-b border-neutral-100 bg-neutral-50 px-4 py-1.5 text-xs font-semibold uppercase tracking-wide text-tertiary">{{ __($topic) }}</h2>
                    <ul class="divide-y divide-neutral-100">
                        @foreach ($items as $reply)
                            <li x-show="matches(@js($reply->title.' '.$reply->body))">
                                <a href="{{ route('admin.support.replies.index', ['reply' => $reply->id]) }}" @if ($selected?->id === $reply->id) aria-current="true" @endif
                                    @class(['block px-4 py-3 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-secondary/40', 'bg-teal-50/60' => $selected?->id === $reply->id, 'hover:bg-neutral-50' => $selected?->id !== $reply->id])>
                                    <span class="flex items-center justify-between gap-2"><span class="truncate text-sm font-semibold text-neutral-900">{{ $reply->title }}</span>@unless ($reply->isShared())<span class="shrink-0 rounded-full bg-neutral-100 px-2 py-0.5 text-[11px] font-medium text-neutral-600">{{ __('Only me') }}</span>@endunless</span>
                                    <span class="mt-0.5 block text-xs text-tertiary">{{ trans_choice(':count use|:count uses', $reply->uses, ['count' => $reply->uses]) }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @empty
                    <p class="px-4 py-8 text-center text-sm text-tertiary">{{ __('No saved replies yet. Write the first one on the right.') }}</p>
                @endforelse
            </section>

            {{-- The editor --}}
            @if ($editing || $creating)
                <div class="min-w-0 space-y-6">
                    <x-card class="rounded-2xl">
                        <form method="POST" action="{{ $editing ? route('admin.support.replies.update', $selected->id) : route('admin.support.replies.store') }}" class="space-y-4 p-6">
                            @csrf
                            @if ($editing)@method('PUT')@endif
                            <h2 class="font-semibold text-neutral-900">{{ $editing ? __('Edit this reply') : __('New reply') }}</h2>

                            <div class="grid gap-4 sm:grid-cols-3">
                                <div class="sm:col-span-2">
                                    <label for="r-title" class="block text-sm font-medium text-neutral-800">{{ __('Name') }}</label>
                                    <input id="r-title" name="title" type="text" maxlength="120" required x-model="form.title" class="mt-1 block w-full rounded-xl border-neutral-300 text-sm focus:border-teal-600 focus:ring-teal-600">
                                    @error('title')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="r-topic" class="block text-sm font-medium text-neutral-800">{{ __('Topic') }}</label>
                                    <select id="r-topic" name="topic" x-model="form.topic" class="mt-1 block w-full rounded-xl border-neutral-300 text-sm focus:border-teal-600 focus:ring-teal-600">
                                        @foreach ($topics as $topic)<option value="{{ $topic }}">{{ __($topic) }}</option>@endforeach
                                    </select>
                                    @error('topic')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <div>
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <label for="r-body" class="block text-sm font-medium text-neutral-800">{{ __('The reply') }}</label>
                                    <div class="flex flex-wrap items-center gap-1.5" role="group" aria-label="{{ __('Add a placeholder') }}">
                                        @foreach ($variables as $name => $variable)
                                            <button type="button" @click="insert('{{ $name }}')" title="{{ __($variable['label']) }}" class="rounded-full border border-neutral-300 bg-white px-2.5 py-0.5 font-mono text-xs text-neutral-700 hover:bg-neutral-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">{{ '{'.$name.'}' }}</button>
                                        @endforeach
                                    </div>
                                </div>
                                <textarea id="r-body" name="body" x-ref="body" x-model="form.body" rows="10" required maxlength="{{ \App\Services\Support\Tickets\TicketService::BODY_MAX }}" class="mt-1 block w-full rounded-xl border-neutral-300 text-sm focus:border-teal-600 focus:ring-teal-600"></textarea>
                                @error('body')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
                                <ul class="mt-2 space-y-1 text-xs">
                                    <li x-show="unknown.length" x-cloak class="text-red-700">{{ __('These placeholders do not exist and will not be saved:') }} <span class="font-mono" x-text="unknown.join(' ')"></span></li>
                                    <li x-show="promises" x-cloak class="text-amber-700">{{ __('This sounds like a promise (a guarantee, a refund, an exact time). Only say what a person has decided.') }}</li>
                                    <li x-show="literalAmount" x-cloak class="text-amber-700">{{ __('A fixed amount goes stale. Use a placeholder such as {min_withdrawal} so it always matches the live setting.') }}</li>
                                </ul>
                            </div>

                            @if ($editing)
                                <p class="text-xs text-tertiary">{{ $selected->isShared() ? __('Shared with everyone who answers requests. Anyone of them can improve it; each change is in the activity log.') : __('Only you can see and use this reply.') }}@if ($selected->editor) {{ __('Last edited by :name, :when.', ['name' => $selected->editor->name, 'when' => $selected->updated_at->diffForHumans()]) }}@endif</p>
                            @else
                                <fieldset>
                                    <legend class="text-sm font-medium text-neutral-800">{{ __('Who can use it?') }}</legend>
                                    <div class="mt-1 flex flex-wrap gap-4 text-sm text-neutral-700">
                                        <label class="inline-flex items-center gap-2"><input type="radio" name="scope" value="team" x-model="form.scope" class="text-teal-600 focus:ring-teal-600">{{ __('The whole team') }}</label>
                                        <label class="inline-flex items-center gap-2"><input type="radio" name="scope" value="me" x-model="form.scope" class="text-teal-600 focus:ring-teal-600">{{ __('Only me') }}</label>
                                    </div>
                                    <p class="mt-1 text-xs text-tertiary">{{ __('You choose this once, now.') }}</p>
                                </fieldset>
                            @endif

                            <div class="flex flex-wrap items-center gap-3">
                                <x-btn x-bind:disabled="unknown.length > 0">{{ __('Save reply') }}</x-btn>
                                @if ($editing)
                                    <button type="button" @click="deleting = true" class="text-sm font-medium text-red-700 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-red-300">{{ __('Delete') }}</button>
                                @endif
                            </div>
                        </form>
                    </x-card>

                    <x-card class="rounded-2xl">
                        <div class="space-y-2 p-6">
                            <h2 class="font-semibold text-neutral-900">{{ __('How it reads') }}</h2>
                            <p class="text-xs text-tertiary">{{ __('With example values. On a request, the real ones are used.') }}</p>
                            <p class="whitespace-pre-wrap break-words rounded-xl bg-neutral-50 p-4 text-sm text-neutral-800" x-text="preview || '{{ __('Nothing written yet.') }}'"></p>
                        </div>
                    </x-card>
                </div>
            @else
                <x-card class="rounded-2xl"><p class="p-6 text-sm text-tertiary">{{ __('Pick a reply on the left to edit it, or start a new one.') }}</p></x-card>
            @endif
        </div>

        @if ($editing)
            <x-confirm-dialog bind="deleting" title="Delete this reply" confirm-label="Delete" method="DELETE" :action="route('admin.support.replies.destroy', $selected->id)" message="Nobody will be able to insert it any more. Replies already sent are not changed." />
        @endif
    </div>
</x-staff-layout>
