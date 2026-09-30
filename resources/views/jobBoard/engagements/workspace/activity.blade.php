@php
    $tones = [
        'neutral' => 'bg-neutral-100 text-neutral-600',
        'teal' => 'bg-teal-50 text-teal-700',
        'blue' => 'bg-blue-50 text-blue-700',
        'red' => 'bg-red-50 text-red-700',
    ];
@endphp

@if ($timeline === [])
    <x-empty-state icon="clock" :title="__('Nothing here yet')" :description="__('Offers, submissions, approvals and payments will show up here as they happen.')" />
@else
    <x-panel class="max-w-3xl" :title="__('Activity')" :description="__('Everything that has happened on this engagement, newest first.')">
        <ol class="relative space-y-6">
            @foreach ($timeline as $event)
                <li class="relative flex gap-4">
                    @unless ($loop->last)
                        <span class="absolute left-4 top-9 -bottom-6 w-px bg-neutral-200" aria-hidden="true"></span>
                    @endunless
                    <span class="relative flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $tones[$event['tone']] ?? $tones['neutral'] }}">
                        <x-icon :name="$event['icon']" class="h-4 w-4" />
                    </span>
                    <div class="min-w-0 flex-1 pt-0.5">
                        <p class="text-sm font-medium text-neutral-900">{{ $event['title'] }}</p>
                        @if ($event['detail'])
                            <p class="mt-0.5 whitespace-pre-line break-words text-sm text-neutral-600">{{ $event['detail'] }}</p>
                        @endif
                        <p class="mt-1 text-xs text-tertiary">
                            <time datetime="{{ $event['at']->toIso8601String() }}">{{ $event['at']->format('M j, Y · g:i A') }}</time>
                        </p>
                    </div>
                </li>
            @endforeach
        </ol>
    </x-panel>
@endif
