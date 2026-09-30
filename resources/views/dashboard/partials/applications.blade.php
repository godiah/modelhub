@php
    $tones = [
        'submitted' => 'blue',
        'reviewed' => 'amber',
        'hired' => 'green',
        'rejected' => 'red',
        'withdrawn' => 'neutral',
    ];
@endphp

<x-card clip>
    <div class="flex items-center justify-between gap-3 border-b border-neutral-100 px-5 py-4">
        <h3 class="font-tertiary text-base font-semibold text-neutral-900">{{ __('My applications') }}</h3>
        <a href="{{ route('applications.my') }}" wire:navigate
            class="text-sm font-medium text-teal-700 hover:text-teal-800">{{ __('View all') }}</a>
    </div>

    @if ($applications->isEmpty())
        <p class="px-5 py-6 text-sm text-tertiary">
            {{ __('No applications yet.') }}
            <a href="{{ route('jobs.browse') }}" wire:navigate
                class="font-medium text-teal-700 hover:text-teal-800">{{ __('Browse projects') }}</a>
        </p>
    @else
        <ul class="divide-y divide-neutral-100">
            @foreach ($applications as $application)
                <li>
                    <a href="{{ route('applications.show', $application->job->slug) }}" wire:navigate
                        class="flex items-center justify-between gap-3 px-5 py-3 transition-colors duration-150 hover:bg-neutral-50">
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-medium text-neutral-900">{{ $application->job->title }}</span>
                            <span class="block text-xs text-tertiary">{{ $application->created_at->diffForHumans() }}</span>
                        </span>
                        <x-badge :tone="$tones[$application->status->value] ?? 'neutral'"
                            class="shrink-0 px-2 py-0.5 text-xs font-medium">{{ $application->status->label() }}</x-badge>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</x-card>
