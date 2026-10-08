<x-staff-layout :title="__('Assistant chat').' · '.$ticket->reference">
    <div class="container mx-auto max-w-3xl px-4 py-8">
        <a href="{{ route('admin.support.tickets.show', $ticket) }}" class="mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-teal-700 hover:underline"><x-icon name="arrow-left" class="h-4 w-4" />{{ __('Back to :ref', ['ref' => $ticket->reference]) }}</a>

        <x-card class="mb-4 rounded-2xl">
            <div class="space-y-1 p-6 text-sm">
                <h1 class="text-lg font-semibold text-neutral-900">{{ __('Chat with the assistant') }}</h1>
                <p class="text-tertiary">{{ __(':name\'s conversation before they asked for a person. Opening it is recorded in the activity log with your name. It is the member\'s private conversation: use it only to help with this request.', ['name' => $ticket->requester->name]) }}</p>
            </div>
        </x-card>

        @if ($problem)
            <x-card class="rounded-2xl">
                <p class="p-6 text-sm text-neutral-700">{{ $problem }}</p>
            </x-card>
        @else
            @if ($truncated)
                <p class="mb-3 text-xs text-tertiary">{{ __('This chat is long: only the latest messages are shown.') }}</p>
            @endif
            <ol class="space-y-3">
                @foreach ($messages as $line)
                    @php $isMember = ($line['role'] ?? '') === 'member'; @endphp
                    <li @class(['rounded-2xl border p-4 text-sm', 'border-teal-200 bg-teal-50' => $isMember, 'border-neutral-200 bg-white' => ! $isMember])>
                        <div class="mb-1 flex items-center justify-between gap-3 text-xs text-tertiary">
                            <span class="font-semibold text-neutral-900">{{ $isMember ? __('The member wrote') : __('The assistant said') }}</span>
                            @if (! empty($line['at']))
                                <time>{{ \Illuminate\Support\Carbon::parse($line['at'])->setTimezone(config('support.tickets.timezone'))->format('M j, g:i A') }}</time>
                            @endif
                        </div>
                        <p class="whitespace-pre-wrap break-words text-neutral-800">{{ (string) ($line['text'] ?? '') }}</p>
                        @foreach ((array) ($line['cards'] ?? []) as $card)
                            <p class="mt-2 rounded-lg bg-neutral-100 px-2.5 py-1.5 text-xs text-neutral-600">{{ __('The assistant showed a :type card', ['type' => (string) ($card['type'] ?? 'record')]) }}@if (! empty($card['reference'])) · <span class="font-mono">{{ $card['reference'] }}</span>@endif</p>
                        @endforeach
                    </li>
                @endforeach
            </ol>
        @endif
    </div>
</x-staff-layout>
