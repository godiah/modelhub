<x-card clip>
    <div class="flex items-center justify-between gap-3 border-b border-neutral-100 px-5 py-4">
        <h3 class="font-tertiary text-base font-semibold text-neutral-900">{{ __('Recent notifications') }}</h3>
        <a href="{{ route('notifications.index') }}" wire:navigate
            class="text-sm font-medium text-teal-700 hover:text-teal-800">{{ __('View all') }}</a>
    </div>

    @if (count($notifications) === 0)
        <p class="px-5 py-6 text-sm text-tertiary">{{ __('No notifications yet.') }}</p>
    @else
        <ul class="divide-y divide-neutral-100">
            @foreach ($notifications as $notification)
                <li>
                    <a href="{{ route('notifications.read', $notification['id']) }}"
                        class="flex items-start gap-3 px-5 py-3 transition-colors duration-150 hover:bg-neutral-50">
                        <span @class([
                            'mt-1.5 h-2 w-2 shrink-0 rounded-full',
                            'bg-teal-600' => !$notification['read'],
                            'bg-transparent' => $notification['read'],
                        ])
                            @if (!$notification['read']) title="{{ __('Unread') }}" @endif></span>
                        <span class="min-w-0 flex-1">
                            <span @class([
                                'line-clamp-2 block text-sm',
                                'font-medium text-neutral-900' => !$notification['read'],
                                'text-neutral-600' => $notification['read'],
                            ])>{{ $notification['content'] }}</span>
                            <span class="block text-xs text-tertiary">{{ $notification['at']->diffForHumans() }}</span>
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</x-card>
