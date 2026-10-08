@php
    $resolved = $ticket->status === \App\Enums\SupportTicketStatus::Resolved;
    $closed = $ticket->status === \App\Enums\SupportTicketStatus::Closed;
    $canReply = ! $closed && ! ($resolved && $ticket->resolved_at?->lt(now()->subDays((int) config('support.tickets.reopen_days'))));
@endphp
<x-app-layout>
    <div class="container mx-auto max-w-4xl px-4 py-8">
        <a href="{{ route('support.requests.index') }}" class="mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-teal-700 hover:underline"><x-icon name="arrow-left" class="h-4 w-4" />{{ __('All your requests') }}</a>

        <header class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-xs font-medium uppercase tracking-wide text-neutral-500">{{ __($ticket->category->label()) }}</p>
                <h2 class="mt-1 font-mono text-2xl font-semibold text-neutral-900">{{ $ticket->reference }}</h2>
                <p class="mt-1 text-sm text-neutral-600">{{ __('Sent :when', ['when' => $ticket->created_at->format('M j, Y · g:i A')]) }}</p>
            </div>
            <x-badge :tone="$ticket->status->tone()" class="px-2.5 py-0.5 text-xs font-medium">{{ __($ticket->status->label()) }}</x-badge>
        </header>

        @if ($ticket->status->needsStaff() && $ticket->first_responded_at === null)
            <p class="mt-4 rounded-lg bg-neutral-100 px-3 py-2 text-sm text-neutral-700">{{ \App\Services\Support\Tickets\TicketTargets::sentence($ticket->severity) }}</p>
        @elseif ($ticket->status === \App\Enums\SupportTicketStatus::PendingMember)
            <p class="mt-4 rounded-lg bg-blue-50 px-3 py-2 text-sm text-blue-900">{{ __('Staff are waiting for your reply.') }}</p>
        @endif

        <x-panel :title="__('Conversation')" class="mt-6">
            <ol class="space-y-4">
                @foreach ($ticket->memberMessages as $message)
                    @php $mine = $message->sender === 'member'; @endphp
                    <li @class(['rounded-xl border px-4 py-3', 'border-neutral-200 bg-white' => $mine, 'border-teal-200 bg-teal-50/50' => $message->sender === 'staff', 'border-neutral-200 bg-neutral-50' => $message->sender === 'system'])>
                        <p class="flex justify-between gap-3 text-sm">
                            <span class="font-semibold text-neutral-900">
                                @if ($mine){{ __('You') }}@elseif ($message->sender === 'staff'){{ $message->staff?->name ?? __('ModelHub staff') }} <span class="font-normal text-neutral-500">· {{ __('ModelHub support') }}</span>@else{{ __('ModelHub') }}@endif
                            </span>
                            <span class="text-xs text-neutral-500">{{ $message->created_at->format('M j, g:i A') }}</span>
                        </p>
                        {{-- Plain text, escaped: what anyone wrote is never drawn as markup --}}
                        @if ($message->body !== '')<p class="mt-1.5 whitespace-pre-line break-words text-[15px] leading-[1.55] text-neutral-800">{{ $message->body }}</p>@endif
                        <x-support.ticket-files :message="$message" :ticket="$ticket" />
                    </li>
                @endforeach
            </ol>
            @if ($resolved)
                <p class="mt-5 rounded-lg bg-green-50 px-3 py-2 text-sm text-green-900">{{ __('This request is resolved. If something is still wrong, reply and staff will pick it up again.') }}</p>
            @endif
        </x-panel>

        @if ($canReply)
            <x-panel :title="$resolved ? __('Reply to reopen') : __('Your reply')" class="mt-6">
                <form method="POST" action="{{ route('support.requests.reply', $ticket) }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <label for="reply" class="sr-only">{{ __('Reply to staff') }}</label>
                        <textarea id="reply" name="body" rows="4" maxlength="{{ \App\Services\Support\Tickets\TicketService::BODY_MAX }}" placeholder="{{ __('Write your reply') }}"
                            class="block w-full rounded-xl border-neutral-300 text-[15px] leading-snug focus:border-teal-600 focus:ring-teal-600">{{ old('body') }}</textarea>
                        @error('body')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
                        <p class="mt-1.5 flex items-center gap-1.5 text-xs text-neutral-500"><x-icon name="shield-check" class="h-3.5 w-3.5 text-teal-600" />{{ __('Never include your M-Pesa PIN or a code from an SMS.') }}</p>
                    </div>
                    <div>
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-dashed border-neutral-300 bg-neutral-50 px-4 py-3 text-sm text-neutral-600 hover:border-teal-400">
                            <x-icon name="photo" class="h-5 w-5 text-neutral-400" />
                            <span><span class="font-medium text-teal-700">{{ __('Add a screenshot or a PDF') }}</span> <span class="text-neutral-500">{{ __('(JPEG, PNG or PDF; up to :n files, :mb MB each)', ['n' => config('support.tickets.attachments.max_files'), 'mb' => config('support.tickets.attachments.max_bytes') / 1048576]) }}</span></span>
                            <input type="file" name="files[]" multiple accept="image/jpeg,image/png,application/pdf" class="sr-only">
                        </label>
                        @error('files')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex justify-end"><x-btn type="submit">{{ __('Send reply') }}</x-btn></div>
                </form>
            </x-panel>
        @else
            <p class="mt-6 rounded-lg bg-neutral-100 px-3 py-2 text-sm text-neutral-700">{{ __('This request is closed. If you still need help, start a new one from the Help button.') }}</p>
        @endif
    </div>
</x-app-layout>
