@php
    $canManage = auth()->user()->can('manage support tickets');
    $due = $ticket->first_responded_at === null ? $ticket->first_response_due_at : $ticket->resolution_due_at;
    $late = $ticket->isOverdue();
    $closed = $ticket->status === \App\Enums\SupportTicketStatus::Closed;
@endphp
<x-staff-layout :title="__('Support request').' · '.$ticket->reference">
    <div class="container mx-auto max-w-7xl px-4 py-8">
        <a href="{{ route('admin.support.tickets.index') }}" class="mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-teal-700 hover:underline"><x-icon name="arrow-left" class="h-4 w-4" />{{ __('All support requests') }}</a>

        <x-card class="mb-6 rounded-2xl">
            <div class="flex flex-col gap-3 p-6 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-medium uppercase tracking-wide text-tertiary">{{ __($ticket->category->label()) }}</p>
                    <div class="mt-1 flex flex-wrap items-center gap-3">
                        <h1 class="font-mono text-2xl font-semibold text-neutral-900">{{ $ticket->reference }}</h1>
                        <x-badge :tone="$ticket->severity->tone()" class="px-2.5 py-0.5 text-xs font-medium">{{ __($ticket->severity->label()) }}</x-badge>
                        <x-badge :tone="$ticket->status->tone()" class="px-2.5 py-0.5 text-xs font-medium">{{ __($ticket->status->label()) }}</x-badge>
                    </div>
                    <p class="mt-1.5 text-sm text-tertiary">
                        {{ __('Filed :date by :name', ['date' => $ticket->created_at->format('M j, Y · g:i A'), 'name' => $ticket->requester?->name ?? __('a deleted account')]) }}
                        · {{ $ticket->assignee ? __('with :name', ['name' => $ticket->assignee->name]) : __('nobody has it yet') }}
                        @if ($due && $ticket->status->needsStaff())
                            · <span @class(['font-medium', 'text-red-700' => $late])>{{ $ticket->first_responded_at === null ? __('first reply') : __('resolution') }} {{ $late ? __('was due :time ago', ['time' => $due->diffForHumans(syntax: \Carbon\CarbonInterface::DIFF_ABSOLUTE)]) : __('due in :time', ['time' => $due->diffForHumans(syntax: \Carbon\CarbonInterface::DIFF_ABSOLUTE)]) }}</span>
                        @endif
                    </p>
                </div>
                @if ($canManage && ! $closed)
                    <div class="flex flex-wrap items-center gap-2">
                        <form method="POST" action="{{ route('admin.support.tickets.assign', $ticket) }}" class="flex items-center gap-2">
                            @csrf
                            <select name="assignee_id" class="rounded-xl border-neutral-300 text-sm focus:border-teal-600 focus:ring-teal-600" aria-label="{{ __('Assign to') }}">
                                <option value="">{{ __('Nobody') }}</option>
                                @foreach ($team as $person)
                                    <option value="{{ $person->id }}" @selected($ticket->assignee_id === $person->id)>{{ $person->name }}</option>
                                @endforeach
                            </select>
                            <x-btn variant="secondary" size="sm">{{ __('Assign') }}</x-btn>
                        </form>
                        <form method="POST" action="{{ route('admin.support.tickets.severity', $ticket) }}" class="flex items-center gap-2">
                            @csrf
                            <select name="severity" class="rounded-xl border-neutral-300 text-sm focus:border-teal-600 focus:ring-teal-600" aria-label="{{ __('Urgency') }}">
                                @foreach (\App\Enums\SupportTicketSeverity::cases() as $level)
                                    <option value="{{ $level->value }}" @selected($ticket->severity === $level)>{{ __($level->label()) }}</option>
                                @endforeach
                            </select>
                            <x-btn variant="secondary" size="sm">{{ __('Set urgency') }}</x-btn>
                        </form>
                    </div>
                @endif
            </div>
        </x-card>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-4 lg:col-span-2">
                {{-- The thread. Everything members and staff wrote is plain text, escaped here; internal notes are marked and are staff-only --}}
                <x-card class="rounded-2xl">
                    <div class="space-y-4 p-6">
                        @foreach ($ticket->messages as $message)
                            @php $isNote = $message->sender === 'note'; @endphp
                            <div @class(['rounded-xl border p-4', 'border-amber-200 bg-amber-50' => $isNote, 'border-neutral-200 bg-white' => ! $isNote && $message->sender !== 'staff', 'border-teal-200 bg-teal-50/50' => $message->sender === 'staff'])>
                                <p class="mb-1 flex flex-wrap items-center gap-2 text-xs text-tertiary">
                                    <span class="font-semibold text-neutral-800">
                                        @switch($message->sender)
                                            @case('member'){{ $message->member?->name ?? __('The member') }}@break
                                            @case('staff'){{ $message->staff?->name ?? __('Staff') }}@break
                                            @case('note'){{ $message->staff?->name ?? __('Staff') }}@break
                                            @default{{ __('ModelHub') }}
                                        @endswitch
                                    </span>
                                    @if ($isNote)<x-badge tone="amber" class="px-2 py-0.5 text-xs font-medium">{{ __('Internal note: the member cannot see this') }}</x-badge>@endif
                                    <span>{{ $message->created_at->format('M j, g:i A') }}</span>
                                </p>
                                @if ($message->body !== '')<p class="whitespace-pre-line break-words text-sm text-neutral-800">{{ $message->body }}</p>@endif
                                <x-support.ticket-files :message="$message" :ticket="$ticket" :staff="true" />
                            </div>
                        @endforeach
                    </div>
                </x-card>

                @if ($canManage && ! $closed)
                    <x-card class="rounded-2xl">
                        <form method="POST" action="{{ route('admin.support.tickets.reply', $ticket) }}" enctype="multipart/form-data" class="space-y-3 p-6">
                            @csrf
                            <label for="reply-body" class="block text-sm font-semibold text-neutral-900">{{ __('Reply to the member') }}</label>
                            <textarea id="reply-body" name="body" rows="4" maxlength="{{ \App\Services\Support\Tickets\TicketService::BODY_MAX }}" class="block w-full rounded-xl border-neutral-300 text-sm focus:border-teal-600 focus:ring-teal-600">{{ old('body') }}</textarea>
                            @error('body')<p class="text-xs text-red-700">{{ $message }}</p>@enderror
                            <label class="block text-sm text-neutral-600">{{ __('Attach files') }} <span class="text-tertiary">{{ __('(JPEG, PNG or PDF)') }}</span>
                                <input type="file" name="files[]" multiple accept="image/jpeg,image/png,application/pdf" class="mt-1 block w-full text-sm">
                            </label>
                            @error('files')<p class="text-xs text-red-700">{{ $message }}</p>@enderror
                            <x-btn>{{ __('Send reply') }}</x-btn>
                        </form>
                        <form method="POST" action="{{ route('admin.support.tickets.note', $ticket) }}" class="space-y-3 border-t border-neutral-100 p-6">
                            @csrf
                            <label for="note-body" class="block text-sm font-semibold text-neutral-900">{{ __('Internal note') }} <span class="font-normal text-tertiary">{{ __('(staff only)') }}</span></label>
                            <textarea id="note-body" name="body" rows="2" maxlength="{{ \App\Services\Support\Tickets\TicketService::BODY_MAX }}" required class="block w-full rounded-xl border-neutral-300 text-sm focus:border-teal-600 focus:ring-teal-600"></textarea>
                            <x-btn variant="secondary">{{ __('Add note') }}</x-btn>
                        </form>
                    </x-card>
                @endif
            </div>

            <div class="space-y-4">
                <x-card class="rounded-2xl">
                    <div class="space-y-3 p-6 text-sm">
                        <h2 class="font-semibold text-neutral-900">{{ __('The member') }}</h2>
                        <p class="text-neutral-800">{{ $ticket->requester?->name ?? __('A deleted account') }}</p>
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

                @if ($ticket->entity_refs)
                    <x-card class="rounded-2xl">
                        <div class="space-y-2 p-6 text-sm">
                            <h2 class="font-semibold text-neutral-900">{{ __('Records this is about') }}</h2>
                            <p class="text-xs text-tertiary">{{ __('Checked against the member: only their own records are listed.') }}</p>
                            <ul class="space-y-1.5">
                                @foreach ($ticket->entity_refs as $ref)
                                    @php [$type, $reference] = explode(':', $ref, 2); @endphp
                                    <li class="flex items-center gap-2">
                                        <span class="rounded bg-neutral-100 px-1.5 py-0.5 text-xs text-neutral-600">{{ __(ucfirst($type)) }}</span>
                                        @if ($type === 'payment' && auth()->user()->can('view payments'))
                                            <a href="{{ route('admin.payments.show', $reference) }}" class="font-mono text-teal-700 hover:underline">{{ $reference }}</a>
                                        @else
                                            <span class="font-mono text-neutral-800">{{ $reference }}</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </x-card>
                @endif

                @if ($ticket->evidence)
                    @php $evidence = (array) $ticket->evidence; @endphp
                    <x-card class="rounded-2xl">
                        <div class="space-y-3 p-6 text-sm">
                            <h2 class="font-semibold text-neutral-900">{{ __('What the assistant showed') }}</h2>
                            <p class="text-xs text-tertiary">{{ __('A snapshot taken when the member asked for a person (:when). The facts were read from their records by the assistant\'s code; the quoted messages are what was written in the chat.', ['when' => isset($evidence['captured_at']) ? \Illuminate\Support\Carbon::parse($evidence['captured_at'])->format('M j, g:i A') : __('time unknown')]) }}</p>
                            @if (! empty($evidence['facts']))
                                <ul class="space-y-1.5">
                                    @foreach ((array) $evidence['facts'] as $fact)
                                        <li class="break-words text-neutral-800">{{ \Illuminate\Support\Str::limit((string) $fact, 240) }}</li>
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
                                                <span class="text-neutral-700">{{ \Illuminate\Support\Str::limit((string) ($line['text'] ?? ''), 500) }}</span>
                                            </li>
                                        @endforeach
                                    </ol>
                                </details>
                            @endif
                        </div>
                    </x-card>
                @endif

                @if ($canManage && $ticket->status->isActive())
                    <x-card class="rounded-2xl">
                        <form method="POST" action="{{ route('admin.support.tickets.resolve', $ticket) }}" class="space-y-3 p-6 text-sm">
                            @csrf
                            <h2 class="font-semibold text-neutral-900">{{ __('Resolve') }}</h2>
                            <label for="tag" class="block text-neutral-600">{{ __('Why did it end this way?') }}</label>
                            <select id="tag" name="tag" required class="block w-full rounded-xl border-neutral-300 text-sm focus:border-teal-600 focus:ring-teal-600">
                                @foreach ($tags as $tag)
                                    <option value="{{ $tag->value }}">{{ __($tag->label()) }}</option>
                                @endforeach
                            </select>
                            <label for="resolve-body" class="block text-neutral-600">{{ __('Last message to the member (optional)') }}</label>
                            <textarea id="resolve-body" name="body" rows="3" maxlength="{{ \App\Services\Support\Tickets\TicketService::BODY_MAX }}" class="block w-full rounded-xl border-neutral-300 text-sm focus:border-teal-600 focus:ring-teal-600"></textarea>
                            <x-btn>{{ __('Mark as resolved') }}</x-btn>
                        </form>
                    </x-card>
                @endif

                @if ($canManage && $ticket->status === \App\Enums\SupportTicketStatus::Resolved)
                    <form method="POST" action="{{ route('admin.support.tickets.close', $ticket) }}">
                        @csrf
                        <x-btn variant="secondary" block>{{ __('Close the ticket') }}</x-btn>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-staff-layout>
