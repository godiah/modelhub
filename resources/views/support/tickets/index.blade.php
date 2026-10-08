<x-app-layout>
    <div class="container mx-auto max-w-4xl px-4 py-8">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="font-tertiary text-3xl font-bold tracking-tight text-neutral-900">{{ __('Your requests') }}</h2>
                <p class="mt-1 text-neutral-600">{{ __('Questions you have sent to ModelHub staff, and their replies.') }}</p>
            </div>
            <x-btn type="button" @click="$dispatch('support-open')"><x-icon name="chat-bubble-text" class="h-4 w-4" />{{ __('Ask for help') }}</x-btn>
        </header>

        <nav aria-label="{{ __('Filter requests') }}" class="mt-6">
            <ul class="flex items-center gap-2">
                @foreach (['open' => __('Open'), 'resolved' => __('Resolved')] as $key => $label)
                    <li>
                        <a href="{{ route('support.requests.index', ['tab' => $key]) }}" @if ($tab === $key) aria-current="true" @endif
                            @class([
                                'inline-flex items-center gap-2 rounded-full border px-3.5 py-1.5 text-sm font-medium transition-colors focus:outline-none focus-visible:ring-4 focus-visible:ring-secondary/30',
                                'border-teal-600 bg-teal-600 text-white' => $tab === $key,
                                'border-neutral-200 bg-white text-neutral-700 hover:border-neutral-300 hover:bg-neutral-50' => $tab !== $key,
                            ])>
                            {{ $label }}<span @class(['text-xs tabular-nums', 'text-teal-100' => $tab === $key, 'text-neutral-500' => $tab !== $key])>{{ $counts[$key] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="mt-5">
            @if ($tickets->isEmpty())
                <x-empty-state icon="inbox" :title="$tab === 'resolved' ? __('Nothing resolved yet') : __('No requests yet')"
                    :description="__('When you ask staff for help from the Help button, it shows up here with their replies.')">
                    <x-btn type="button" @click="$dispatch('support-open')">{{ __('Ask for help') }}</x-btn>
                </x-empty-state>
            @else
                <ul class="divide-y divide-neutral-100 overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm">
                    @foreach ($tickets as $ticket)
                        <li>
                            <a href="{{ route('support.requests.show', $ticket) }}" class="flex items-start justify-between gap-4 px-5 py-4 transition hover:bg-neutral-50 focus:outline-none focus-visible:bg-neutral-50">
                                <div class="min-w-0">
                                    <p class="flex flex-wrap items-center gap-2 text-sm">
                                        <span class="font-mono font-semibold text-neutral-900">{{ $ticket->reference }}</span>
                                        <span class="text-neutral-600">{{ __($ticket->category->label()) }}</span>
                                    </p>
                                    {{-- What the member wrote, escaped and cut short --}}
                                    <p class="mt-1 max-w-xl truncate text-[15px] text-neutral-800">{{ \Illuminate\Support\Str::limit($ticket->summary, 120) }}</p>
                                    <p class="mt-1 text-xs text-neutral-500">{{ __('Updated :when', ['when' => $ticket->updated_at->diffForHumans()]) }}</p>
                                </div>
                                <x-badge :tone="$ticket->status->tone()" class="shrink-0 px-2.5 py-0.5 text-xs font-medium">{{ __($ticket->status->label()) }}</x-badge>
                            </a>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-6">{{ $tickets->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
