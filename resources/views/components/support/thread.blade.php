@props([
    'messages',
    // 'member' hides internal notes; 'staff' shows them, clearly marked.
    'audience' => 'member',
])

{{--
    A request's conversation, read like a thread rather than a chat: who wrote, when, what. System lines ("You handed this chat
    to staff") are quiet; staff notes are only for staff and are marked so nobody mistakes them for a reply.
--}}
<ol class="space-y-5">
    @foreach ($messages as $m)
        @continue($m['from'] === 'note' && $audience !== 'staff')

        @if ($m['from'] === 'system')
            <li class="flex items-center gap-3 text-xs text-neutral-500">
                <span class="h-px flex-1 bg-neutral-200" aria-hidden="true"></span>
                <span class="max-w-md text-center">{{ $m['text'] }} <span class="text-neutral-400">· {{ $m['at'] }}</span></span>
                <span class="h-px flex-1 bg-neutral-200" aria-hidden="true"></span>
            </li>
        @else
            @php
                $isStaff = $m['from'] === 'staff';
                $isNote = $m['from'] === 'note';
                $mine = $audience === 'member' ? $m['from'] === 'member' : $isStaff || $isNote;
            @endphp
            <li class="flex gap-3">
                <span aria-hidden="true" @class([
                    'mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-semibold',
                    'bg-teal-700 text-white' => $isStaff,
                    'bg-amber-100 text-amber-800' => $isNote,
                    'bg-neutral-200 text-neutral-700' => ! $isStaff && ! $isNote,
                ])>{{ $isStaff || $isNote ? mb_substr($m['name'] ?? 'M', 0, 1) : __('You')[0] }}</span>

                <div @class([
                    'min-w-0 flex-1 rounded-xl border px-4 py-3',
                    'border-neutral-200 bg-white' => ! $isNote,
                    'border-dashed border-amber-300 bg-amber-50' => $isNote,
                ])>
                    <p class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-0.5 text-sm">
                        <span class="font-semibold text-neutral-900">
                            @if ($isNote){{ $m['name'] }} <span class="font-normal text-amber-800">· {{ __('internal note, the member cannot see this') }}</span>
                            @elseif ($isStaff){{ $m['name'] }} <span class="font-normal text-neutral-500">· {{ $m['role'] ?? __('ModelHub support') }}</span>
                            @else{{ $audience === 'member' ? __('You') : ($m['name'] ?? __('Member')) }}@endif
                        </span>
                        <span class="text-xs text-neutral-500">{{ $m['at'] }}</span>
                    </p>
                    <p class="mt-1.5 whitespace-pre-line text-[15px] leading-[1.55] {{ $isNote ? 'text-amber-950' : 'text-neutral-800' }}">{{ $m['text'] }}</p>
                </div>
            </li>
        @endif
    @endforeach
</ol>
