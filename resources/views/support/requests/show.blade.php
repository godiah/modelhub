@php
    use App\Support\SupportChat\PreviewTickets as Tickets;

    $status = Tickets::memberStatus($t['status']);
    $resolved = $t['status'] === 'resolved';

    // Where the request is, in the same trail the assistant uses for a payment
    $journey = [
        'type' => 'trail',
        'title' => __('Where your request is'),
        'subtitle' => $t['ref'],
        'amount' => '',
        'status' => $status,
        'steps' => $resolved ? [
            ['state' => 'done', 'label' => __('Request sent')],
            ['state' => 'done', 'label' => __('Staff looked into it')],
            ['state' => 'done', 'label' => __('Resolved'), 'detail' => $t['updated']],
        ] : [
            ['state' => 'done', 'label' => __('Request sent'), 'detail' => $t['messages'][0]['at'] ?? $t['updated']],
            ['state' => $t['status'] === 'pending_member' ? 'done' : 'current', 'label' => __('Staff are looking into it'), 'detail' => $t['status'] === 'pending_member' ? __('Replied :when', ['when' => $t['updated']]) : __('You will get an email and a notification when they reply.')],
            ['state' => $t['status'] === 'pending_member' ? 'current' : 'pending', 'label' => __('Your reply'), 'detail' => $t['status'] === 'pending_member' ? __('Staff are waiting for your answer.') : null],
            ['state' => 'pending', 'label' => __('Resolved')],
        ],
    ];
@endphp

<x-app-layout :crumb="$t['ref']">
    <div class="container mx-auto max-w-6xl px-4 py-8">
        <a href="{{ route('dev.support.requests') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm font-medium text-teal-700 hover:underline"><x-icon name="arrow-left" class="h-4 w-4" />{{ __('All requests') }}</a>

        <header class="mt-3">
            <h2 class="flex flex-wrap items-center gap-3 font-tertiary text-2xl font-bold tracking-tight text-neutral-900 sm:text-3xl">
                {{ $t['category'] }}
                <x-badge :tone="$status['tone']" class="px-2.5 py-0.5 text-xs font-medium tracking-normal">{{ __($status['label']) }}</x-badge>
            </h2>
            <p class="mt-1 text-sm text-neutral-600">{{ $t['ref'] }} · {{ __('Sent') }} {{ $t['messages'][0]['at'] ?? $t['updated'] }}</p>
        </header>

        <div class="mt-6 grid grid-cols-1 items-start gap-6 lg:grid-cols-3">
            {{-- The conversation, then the reply box --}}
            <div class="space-y-5 lg:col-span-2" x-data="{ text: '', files: [], replies: [], send() { if (! this.text.trim()) return; this.replies.push({ text: this.text.trim(), files: this.files.slice() }); this.text = ''; this.files = []; $refs.picker.value = ''; } }">
                <x-panel :title="__('What we sent')">
                    <p class="border-l-2 border-neutral-200 pl-3 text-[15px] leading-snug text-neutral-800">{{ $t['summary'] }}</p>
                    @if ($t['entities'])
                        <ul class="mt-3 flex flex-wrap gap-2">
                            @foreach ($t['entities'] as $e)
                                <li class="inline-flex items-center gap-1.5 rounded-full border border-neutral-200 bg-neutral-50 px-2.5 py-1 text-xs text-neutral-700"><x-icon :name="$e['icon']" class="h-3.5 w-3.5 text-neutral-400" />{{ $e['label'] }}</li>
                            @endforeach
                        </ul>
                    @endif
                </x-panel>

                <x-panel :title="__('Conversation')">
                    <x-support.thread :messages="$t['messages']" audience="member" />

                    {{-- Replies written on this page (preview only: they are not saved) --}}
                    <ol class="mt-5 space-y-5" x-show="replies.length" x-cloak>
                        <template x-for="(r, i) in replies" :key="i">
                            <li class="flex gap-3">
                                <span aria-hidden="true" class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-neutral-200 text-xs font-semibold text-neutral-700">Y</span>
                                <div class="min-w-0 flex-1 rounded-xl border border-neutral-200 bg-white px-4 py-3">
                                    <p class="flex justify-between gap-3 text-sm"><span class="font-semibold text-neutral-900">{{ __('You') }}</span><span class="text-xs text-neutral-500">{{ __('Just now') }}</span></p>
                                    <p class="mt-1.5 whitespace-pre-line text-[15px] leading-[1.55] text-neutral-800" x-text="r.text"></p>
                                    <ul class="mt-2 flex flex-wrap gap-1.5" x-show="r.files.length">
                                        <template x-for="f in r.files"><li class="inline-flex items-center gap-1.5 rounded-full border border-neutral-200 bg-neutral-50 px-2.5 py-1 text-xs text-neutral-700"><x-icon name="document" class="h-3.5 w-3.5 text-neutral-400" /><span x-text="f"></span></li></template>
                                    </ul>
                                </div>
                            </li>
                        </template>
                    </ol>

                    @if ($resolved)
                        <p class="mt-5 rounded-lg bg-green-50 px-3 py-2 text-sm text-green-900">{{ __('This request is resolved. If something is still wrong, reply and staff will pick it up again.') }}</p>
                    @endif
                </x-panel>

                <x-panel :title="$resolved ? __('Reply to reopen') : __('Your reply')">
                    <form @submit.prevent="send()" class="space-y-4">
                        <div>
                            <label for="reply" class="sr-only">{{ __('Reply to staff') }}</label>
                            <textarea id="reply" x-model="text" rows="4" placeholder="{{ __('Write your reply') }}"
                                class="block w-full rounded-xl border-neutral-300 text-[15px] leading-snug focus:border-teal-600 focus:ring-teal-600"></textarea>
                            <p class="mt-1.5 flex items-center gap-1.5 text-xs text-neutral-500"><x-icon name="shield-check" class="h-3.5 w-3.5 text-teal-600" />{{ __('Never include your M-Pesa PIN or a code from an SMS.') }}</p>
                        </div>

                        <div>
                            <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-dashed border-neutral-300 bg-neutral-50 px-4 py-3 text-sm text-neutral-600 transition hover:border-teal-400 hover:bg-teal-50/40 focus-within:ring-4 focus-within:ring-secondary/20">
                                <x-icon name="photo" class="h-5 w-5 text-neutral-400" />
                                <span><span class="font-medium text-teal-700">{{ __('Add screenshots or a PDF') }}</span> <span class="text-neutral-500">{{ __('(up to 5 files, 10 MB each)') }}</span></span>
                                <input x-ref="picker" type="file" multiple accept="image/png,image/jpeg,application/pdf" class="sr-only" @change="files = [...$event.target.files].map(f => f.name)">
                            </label>
                            <ul class="mt-2 flex flex-wrap gap-1.5" x-show="files.length" x-cloak>
                                <template x-for="f in files"><li class="inline-flex items-center gap-1.5 rounded-full border border-neutral-200 bg-white px-2.5 py-1 text-xs text-neutral-700"><x-icon name="document" class="h-3.5 w-3.5 text-neutral-400" /><span x-text="f"></span></li></template>
                            </ul>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-3">
                            @if (! $resolved)
                                <button type="button" class="text-sm font-medium text-neutral-600 underline-offset-2 hover:text-neutral-900 hover:underline focus:outline-none focus-visible:underline">{{ __('This is sorted, close the request') }}</button>
                            @else
                                <span></span>
                            @endif
                            <x-btn type="submit" x-bind:disabled="! text.trim()">{{ __('Send reply') }}</x-btn>
                        </div>
                    </form>
                </x-panel>
            </div>

            {{-- Where it is, and what staff can see --}}
            <div class="space-y-6">
                <x-support.card-static :card="$journey" />

                @foreach ($t['cards'] as $card)
                    <x-support.card-static :card="$card" />
                @endforeach

                @if ($t['transcript'])
                    <x-panel :title="__('Your chat with the assistant')" :description="__('Staff can read this too, so you do not have to explain again.')">
                        <details class="group">
                            <summary class="flex cursor-pointer list-none items-center gap-1.5 text-sm font-medium text-teal-700 hover:underline focus:outline-none focus-visible:underline">
                                <x-icon name="chevron-right" class="h-4 w-4 transition-transform group-open:rotate-90" />{{ __('Show the chat') }}
                            </summary>
                            <ol class="mt-3 space-y-3">
                                @foreach ($t['transcript'] as $line)
                                    <li class="text-sm leading-snug"><span class="font-semibold text-neutral-900">{{ $line['who'] }}</span><span class="text-neutral-500"> · </span><span class="text-neutral-700">{{ $line['text'] }}</span></li>
                                @endforeach
                            </ol>
                        </details>
                    </x-panel>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
