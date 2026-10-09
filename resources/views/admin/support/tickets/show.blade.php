@php
    use Carbon\CarbonInterface;
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Str;

    $canManage = auth()->user()->can('manage support tickets');
    $due = $ticket->first_responded_at === null ? $ticket->first_response_due_at : $ticket->resolution_due_at;
    $late = $ticket->isOverdue();
    $closed = $ticket->status === \App\Enums\SupportTicketStatus::Closed;
    $me = auth()->id();
    $iCanTakeIt = $canManage && ! $closed && $ticket->assignee_id !== $me && $team->contains('id', $me);
    $evidence = $ticket->evidence ? (array) $ticket->evidence : [];
    $facts = collect($evidence['facts'] ?? [])->map(fn ($f) => (string) $f)->values();

    // The chip beside the title: how the clock stands, or whose turn it is
    $clock = null;
    if ($due && $ticket->status->needsStaff()) {
        $span = $due->diffForHumans(syntax: CarbonInterface::DIFF_ABSOLUTE);
        $soon = ! $late && $due->lte(now()->addHours(2));
        $clock = [
            'tone' => $late ? 'bg-red-100 text-red-800' : ($soon ? 'bg-amber-100 text-amber-800' : 'bg-neutral-100 text-neutral-700'),
            'text' => $late
                ? ($ticket->first_responded_at === null ? __('First reply overdue by :time', ['time' => $span]) : __('Resolution overdue by :time', ['time' => $span]))
                : ($ticket->first_responded_at === null ? __('First reply due in :time', ['time' => $span]) : __('Resolution due in :time', ['time' => $span])),
        ];
    } elseif ($ticket->status === \App\Enums\SupportTicketStatus::PendingMember) {
        $clock = ['tone' => 'bg-neutral-100 text-neutral-700', 'text' => __('Waiting for the member')];
    }

    // One history: what was said, and what was done to the request
    $history = $ticket->messages->map(fn ($m) => ['at' => $m->created_at, 'message' => $m])
        ->concat($events->map(fn ($e) => ['at' => $e->created_at, 'event' => $e]))
        ->sortBy(fn ($row) => $row['at']->getTimestamp())->values();

    // A line the assistant read from the member's records belongs under the record it is about
    $factsFor = fn (string $reference) => $facts->filter(fn ($f) => str_contains($f, $reference))->values();
    $shown = collect($ticket->entity_refs ?? [])->flatMap(fn ($ref) => $factsFor(explode(':', $ref, 2)[1] ?? ''))->all();
    $otherFacts = $facts->reject(fn ($f) => in_array($f, $shown, true))->values();
    $asOf = function (string $fact): array {
        // "... (as of 2026-10-07T22:36:47+03:00)" is read by a machine; show the time the way staff read it
        if (preg_match('/^(.*?)\s*\(as of ([^)]+)\)\s*$/s', $fact, $m)) {
            try {
                return [Str::limit($m[1], 240), Carbon::parse($m[2])->format('M j, g:i A')];
            } catch (\Throwable) {
            }
        }

        return [Str::limit($fact, 240), null];
    };
@endphp
<x-staff-layout :title="__('Support request').' · '.$ticket->reference">
    <div class="container mx-auto max-w-7xl px-4 py-8">
        <a href="{{ route('admin.support.tickets.index') }}" class="mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-teal-700 hover:underline"><x-icon name="arrow-left" class="h-4 w-4" />{{ __('All support requests') }}</a>

        {{-- Where this request stands, and whose move it is --}}
        <x-card class="mb-6 rounded-2xl">
            <div class="flex flex-col gap-4 p-6 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-medium uppercase tracking-wide text-tertiary">{{ __($ticket->category->label()) }}</p>
                    <div class="mt-1 flex flex-wrap items-center gap-3">
                        <h1 class="font-mono text-2xl font-semibold text-neutral-900">{{ $ticket->reference }}</h1>
                        <x-badge :tone="$ticket->severity->tone()" class="px-2.5 py-0.5 text-xs font-medium">{{ __($ticket->severity->label()) }}</x-badge>
                        <x-badge :tone="$ticket->status->tone()" class="px-2.5 py-0.5 text-xs font-medium">{{ __($ticket->status->label()) }}</x-badge>
                        @if ($clock)<span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $clock['tone'] }}"><x-icon name="clock" class="h-3.5 w-3.5" />{{ $clock['text'] }}</span>@endif
                    </div>
                    <p class="mt-1.5 text-sm text-tertiary">{{ __('Filed :date by :name', ['date' => $ticket->created_at->format('M j, Y · g:i A'), 'name' => $ticket->requester?->name ?? __('a deleted account')]) }}</p>
                </div>

                <div class="flex flex-col gap-3 lg:items-end">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="inline-flex items-center gap-2 text-sm">
                            @if ($ticket->assignee)
                                <x-user-avatar :user="$ticket->assignee" size="h-7 w-7" />
                                <span class="text-neutral-800">{{ __('With :name', ['name' => $ticket->assignee->name]) }}</span>
                            @else
                                <span aria-hidden="true" class="flex h-7 w-7 items-center justify-center rounded-full border border-dashed border-neutral-300 text-xs text-neutral-400">?</span>
                                <span class="text-amber-700">{{ __('Nobody has it yet') }}</span>
                            @endif
                        </span>
                        @if ($iCanTakeIt)
                            <form method="POST" action="{{ route('admin.support.tickets.assign', $ticket) }}">
                                @csrf
                                <input type="hidden" name="assignee_id" value="{{ $me }}">
                                <x-btn size="sm">{{ __('Take it') }}</x-btn>
                            </form>
                        @endif
                        @if ($canManage && $ticket->status === \App\Enums\SupportTicketStatus::Resolved)
                            <form method="POST" action="{{ route('admin.support.tickets.close', $ticket) }}">
                                @csrf
                                <x-btn variant="secondary" size="sm">{{ __('Close the ticket') }}</x-btn>
                            </form>
                        @endif
                    </div>
                    @if ($canManage && ! $closed)
                        <div class="flex flex-wrap items-center gap-2">
                            <form method="POST" action="{{ route('admin.support.tickets.assign', $ticket) }}" class="flex items-center gap-2">
                                @csrf
                                <select name="assignee_id" class="rounded-xl border-neutral-300 py-1.5 text-sm focus:border-teal-600 focus:ring-teal-600" aria-label="{{ __('Assign to') }}">
                                    <option value="">{{ __('Nobody') }}</option>
                                    @foreach ($team as $person)
                                        <option value="{{ $person->id }}" @selected($ticket->assignee_id === $person->id)>{{ $person->name }}</option>
                                    @endforeach
                                </select>
                                <x-btn variant="secondary" size="sm">{{ __('Assign') }}</x-btn>
                            </form>
                            <form method="POST" action="{{ route('admin.support.tickets.severity', $ticket) }}" class="flex items-center gap-2">
                                @csrf
                                <select name="severity" class="rounded-xl border-neutral-300 py-1.5 text-sm focus:border-teal-600 focus:ring-teal-600" aria-label="{{ __('Urgency') }}">
                                    @foreach (\App\Enums\SupportTicketSeverity::cases() as $level)
                                        <option value="{{ $level->value }}" @selected($ticket->severity === $level)>{{ __($level->label()) }}</option>
                                    @endforeach
                                </select>
                                <x-btn variant="secondary" size="sm">{{ __('Set urgency') }}</x-btn>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </x-card>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                {{-- The records the member is asking about, with what the assistant read from them --}}
                @if ($ticket->entity_refs)
                    <x-card class="rounded-2xl">
                        <div class="p-6">
                            <h2 class="font-semibold text-neutral-900">{{ __('Records this is about') }}</h2>
                            <p class="mt-1 text-xs text-tertiary">{{ __('Checked against the member: only their own records are listed.') }}</p>
                            <ul class="mt-4 space-y-3">
                                @foreach ($ticket->entity_refs as $ref)
                                    @php [$type, $reference] = explode(':', $ref, 2) + [1 => '']; @endphp
                                    <li class="rounded-xl border border-neutral-200 bg-neutral-50/60 p-4">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="rounded bg-white px-1.5 py-0.5 text-xs font-medium text-neutral-600 ring-1 ring-neutral-200">{{ __(ucfirst($type)) }}</span>
                                            @if ($type === 'payment' && auth()->user()->can('view payments'))
                                                <a href="{{ route('admin.payments.show', $reference) }}" class="font-mono text-sm font-semibold text-teal-700 hover:underline">{{ $reference }}</a>
                                            @else
                                                <span class="font-mono text-sm font-semibold text-neutral-900">{{ $reference }}</span>
                                            @endif
                                        </div>
                                        @foreach ($factsFor($reference) as $fact)
                                            @php [$line, $when] = $asOf($fact); @endphp
                                            <p class="mt-2 break-words text-sm text-neutral-800">{{ $line }}</p>
                                            @if ($when)<p class="mt-0.5 text-xs text-tertiary">{{ __('As the assistant saw it at :time', ['time' => $when]) }}</p>@endif
                                        @endforeach
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </x-card>
                @endif

                {{-- The history. Everything members and staff wrote is plain text, escaped here; internal notes are marked and are staff-only --}}
                <x-card class="rounded-2xl">
                    <div class="p-6">
                        <h2 class="mb-4 font-semibold text-neutral-900">{{ __('History') }}</h2>
                        <ol class="space-y-4">
                            @foreach ($history as $row)
                                @if (isset($row['event']))
                                    @php $event = $row['event']; @endphp
                                    <li class="flex items-center gap-3 text-xs text-neutral-500">
                                        <span class="h-px flex-1 bg-neutral-200" aria-hidden="true"></span>
                                        <span class="max-w-md text-center">{{ $event->summary }}@if ($event->staff) {{ __('by :name', ['name' => $event->staff->name]) }}@endif <span class="text-neutral-400">· {{ $event->created_at->format('M j, g:i A') }}</span></span>
                                        <span class="h-px flex-1 bg-neutral-200" aria-hidden="true"></span>
                                    </li>
                                @else
                                    @php
                                        $message = $row['message'];
                                        $isNote = $message->sender === 'note';
                                        $isStaff = $message->sender === 'staff';
                                        $person = match ($message->sender) {
                                            'member' => $message->member,
                                            'staff', 'note' => $message->staff,
                                            default => null,
                                        };
                                        $who = match ($message->sender) {
                                            'member' => $message->member?->name ?? __('The member'),
                                            'staff', 'note' => $message->staff?->name ?? __('Staff'),
                                            default => __('ModelHub'),
                                        };
                                    @endphp
                                    <li class="flex gap-3">
                                        @if ($person)
                                            <x-user-avatar :user="$person" size="mt-0.5 h-8 w-8" :class="$isNote ? 'ring-2 ring-amber-300' : ''" />
                                        @elseif ($message->sender === 'member')
                                            <x-user-avatar size="mt-0.5 h-8 w-8" />
                                        @else
                                            <span aria-hidden="true" class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-neutral-800 text-xs font-semibold text-white">M</span>
                                        @endif
                                        <div @class(['min-w-0 flex-1 rounded-xl border px-4 py-3', 'border-dashed border-amber-300 bg-amber-50' => $isNote, 'border-teal-200 bg-teal-50/50' => $isStaff, 'border-neutral-200 bg-white' => ! $isNote && ! $isStaff])>
                                            <p class="mb-1 flex flex-wrap items-center gap-2 text-xs text-tertiary">
                                                <span class="font-semibold text-neutral-800">{{ $who }}</span>
                                                @if ($isNote)<x-badge tone="amber" class="px-2 py-0.5 text-xs font-medium">{{ __('Internal note: the member cannot see this') }}</x-badge>@endif
                                                <span>{{ $message->created_at->format('M j, g:i A') }}</span>
                                            </p>
                                            @if ($message->body !== '')<p class="whitespace-pre-line break-words text-sm text-neutral-800">{{ $message->body }}</p>@endif
                                            <x-support.ticket-files :message="$message" :ticket="$ticket" :staff="true" />
                                        </div>
                                    </li>
                                @endif
                            @endforeach
                        </ol>
                    </div>
                </x-card>

                @if ($canManage && ! $closed)
                    @php $canResolve = $ticket->status->isActive(); @endphp
                    <x-card class="rounded-2xl">
                      <div x-data="{ tab: @js(in_array(old('tab'), $canResolve ? ['reply', 'note', 'resolve'] : ['reply', 'note'], true) ? old('tab') : 'reply') }">
                        <div class="flex gap-1 border-b border-neutral-100 px-4 pt-3" role="tablist" aria-label="{{ __('What to do next') }}">
                            @foreach ([['reply', __('Reply to the member')], ['note', __('Internal note')], ...($canResolve ? [['resolve', __('Resolve')]] : [])] as [$key, $label])
                                <button type="button" role="tab" @click="tab = '{{ $key }}'" :aria-selected="tab === '{{ $key }}'"
                                    :class="tab === '{{ $key }}' ? '{{ $key === 'note' ? 'border-amber-500 text-amber-800' : 'border-teal-600 text-teal-800' }}' : 'border-transparent text-neutral-500 hover:text-neutral-800'"
                                    class="-mb-px rounded-t-lg border-b-2 px-4 py-2 text-sm font-semibold focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">{{ $label }}</button>
                            @endforeach
                        </div>

                        <form x-show="tab === 'reply'" method="POST" action="{{ route('admin.support.tickets.reply', $ticket) }}" enctype="multipart/form-data" class="space-y-3 p-6"
                            x-data="{ replies: @js($savedReplies), chosen: '', used: @js(old('saved_reply')),
                                insert() { const r = this.replies.find(x => String(x.id) === this.chosen); if (! r) return; const el = this.$refs.reply; el.value = el.value.trim() === '' ? r.body : el.value.replace(/\s+$/, '') + '\n\n' + r.body; this.used = r.id; this.chosen = ''; el.focus(); } }">
                            @csrf
                            <input type="hidden" name="tab" value="reply">
                            <input type="hidden" name="saved_reply" :value="used">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <label for="reply-body" class="block text-sm font-medium text-neutral-700">{{ __('The member will see this in their request and by email.') }}</label>
                                @if ($savedReplies !== [])
                                    <div class="flex items-center gap-2">
                                        <label for="saved-reply" class="sr-only">{{ __('Insert a saved reply') }}</label>
                                        <select id="saved-reply" x-model="chosen" @change="insert()" class="rounded-xl border-neutral-300 py-1.5 text-sm focus:border-teal-600 focus:ring-teal-600">
                                            <option value="">{{ __('Insert a saved reply…') }}</option>
                                            @foreach (collect($savedReplies)->groupBy('topic') as $topic => $items)
                                                <optgroup label="{{ __($topic) }}">@foreach ($items as $reply)<option value="{{ $reply['id'] }}">{{ $reply['title'] }}</option>@endforeach</optgroup>
                                            @endforeach
                                        </select>
                                        <a href="{{ route('admin.support.replies.index') }}" class="text-xs font-medium text-teal-700 hover:underline">{{ __('Manage') }}</a>
                                    </div>
                                @endif
                            </div>
                            <textarea id="reply-body" name="body" x-ref="reply" rows="6" maxlength="{{ \App\Services\Support\Tickets\TicketService::BODY_MAX }}" class="block w-full rounded-xl border-neutral-300 text-sm focus:border-teal-600 focus:ring-teal-600">{{ old('body') }}</textarea>
                            @error('body')<p class="text-xs text-red-700">{{ $message }}</p>@enderror
                            <label x-data="{ names: [] }" class="relative flex cursor-pointer items-center gap-3 rounded-xl border border-dashed border-neutral-300 bg-neutral-50/60 px-4 py-3 text-sm text-neutral-600 focus-within:ring-2 focus-within:ring-secondary/40 hover:bg-neutral-50">
                                <x-icon name="plus" class="h-4 w-4 shrink-0 text-neutral-400" />
                                <span class="min-w-0 truncate" x-text="names.length ? names.join(', ') : @js(__('Attach files, or drop them here'))">{{ __('Attach files, or drop them here') }}</span>
                                <span class="ml-auto shrink-0 text-xs text-tertiary">{{ __('(JPEG, PNG or PDF)') }}</span>
                                <input type="file" name="files[]" multiple accept="image/jpeg,image/png,application/pdf" @change="names = [...$event.target.files].map(f => f.name)" class="absolute inset-0 h-full w-full cursor-pointer opacity-0">
                            </label>
                            @error('files')<p class="text-xs text-red-700">{{ $message }}</p>@enderror
                            <x-btn>{{ __('Send reply') }}</x-btn>
                        </form>

                        <form x-show="tab === 'note'" x-cloak method="POST" action="{{ route('admin.support.tickets.note', $ticket) }}" class="space-y-3 bg-amber-50/40 p-6">
                            @csrf
                            <input type="hidden" name="tab" value="note">
                            <label for="note-body" class="block text-sm font-medium text-amber-900">{{ __('Only staff can see a note. The member is not told.') }}</label>
                            <textarea id="note-body" name="body" rows="4" maxlength="{{ \App\Services\Support\Tickets\TicketService::BODY_MAX }}" required class="block w-full rounded-xl border-amber-300 bg-white text-sm focus:border-amber-500 focus:ring-amber-500"></textarea>
                            <x-btn variant="secondary">{{ __('Add note') }}</x-btn>
                        </form>

                        @if ($canResolve)
                            <form x-show="tab === 'resolve'" x-cloak method="POST" action="{{ route('admin.support.tickets.resolve', $ticket) }}" class="space-y-3 p-6 text-sm">
                                @csrf
                                <input type="hidden" name="tab" value="resolve">
                                <label for="tag" class="block font-medium text-neutral-700">{{ __('Why did it end this way?') }}</label>
                                <select id="tag" name="tag" required class="block w-full rounded-xl border-neutral-300 text-sm focus:border-teal-600 focus:ring-teal-600 sm:max-w-sm">
                                    @foreach ($tags as $tag)
                                        <option value="{{ $tag->value }}">{{ __($tag->label()) }}</option>
                                    @endforeach
                                </select>
                                <label for="resolve-body" class="block font-medium text-neutral-700">{{ __('Last message to the member (optional)') }}</label>
                                <textarea id="resolve-body" name="body" rows="3" maxlength="{{ \App\Services\Support\Tickets\TicketService::BODY_MAX }}" class="block w-full rounded-xl border-neutral-300 text-sm focus:border-teal-600 focus:ring-teal-600"></textarea>
                                <p class="text-xs text-tertiary">{{ __('The member can still reply for a while after this.') }}</p>
                                <x-btn>{{ __('Mark as resolved') }}</x-btn>
                            </form>
                        @endif
                      </div>
                    </x-card>
                @endif
            </div>

            <div class="space-y-6">
                <x-card class="rounded-2xl">
                    <div class="space-y-3 p-6 text-sm">
                        <h2 class="font-semibold text-neutral-900">{{ __('The member') }}</h2>
                        <p class="flex items-center gap-2 text-neutral-800"><x-user-avatar :user="$ticket->requester" size="h-8 w-8" />{{ $ticket->requester?->name ?? __('A deleted account') }}</p>
                        @if ($ticket->requester && auth()->user()->can('view members'))
                            <a href="{{ route('admin.members.show', $ticket->requester) }}" class="font-medium text-teal-700 hover:underline">{{ __('Open the member') }}</a>
                        @endif
                    </div>
                </x-card>

                @if ($ticket->conversation_id && $ticket->requester && auth()->user()->can('read support transcripts'))
                    <x-card class="rounded-2xl">
                        <div class="space-y-2 p-6 text-sm">
                            <h2 class="font-semibold text-neutral-900">{{ __('The whole chat') }}</h2>
                            <p class="text-xs text-tertiary">{{ __('Everything the member and the assistant said. Opening it is recorded in the activity log.') }}</p>
                            <a href="{{ route('admin.support.tickets.transcript', $ticket) }}" class="font-medium text-teal-700 hover:underline">{{ __('Read the chat') }}</a>
                        </div>
                    </x-card>
                @endif

                @if ($ticket->evidence)
                    <x-card class="rounded-2xl">
                        <div class="space-y-3 p-6 text-sm">
                            <h2 class="font-semibold text-neutral-900">{{ __('What the assistant showed') }}</h2>
                            <p class="text-xs text-tertiary">{{ __('A snapshot taken when the member asked for a person (:when). The facts were read from their records by the assistant\'s code; the quoted messages are what was written in the chat.', ['when' => isset($evidence['captured_at']) ? Carbon::parse($evidence['captured_at'])->format('M j, g:i A') : __('time unknown')]) }}</p>
                            @if ($otherFacts->isNotEmpty())
                                <ul class="space-y-1.5">
                                    @foreach ($otherFacts as $fact)
                                        <li class="break-words text-neutral-800">{{ Str::limit($fact, 240) }}</li>
                                    @endforeach
                                </ul>
                            @endif
                            @if (! empty($evidence['messages']))
                                <details class="group">
                                    <summary class="cursor-pointer font-medium text-teal-700 hover:underline">{{ __('Show the last messages in the chat') }}</summary>
                                    <ol class="mt-2 space-y-2">
                                        @foreach ((array) $evidence['messages'] as $line)
                                            <li class="break-words">
                                                <span class="font-semibold text-neutral-900">{{ ($line['role'] ?? '') === 'member' ? __('The member wrote') : __('The assistant said') }}:</span>
                                                <span class="text-neutral-700">{{ Str::limit((string) ($line['text'] ?? ''), 500) }}</span>
                                            </li>
                                        @endforeach
                                    </ol>
                                </details>
                            @endif
                        </div>
                    </x-card>
                @endif
            </div>
        </div>
    </div>
</x-staff-layout>
