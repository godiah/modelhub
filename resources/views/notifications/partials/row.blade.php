@php
    $presented = \App\Helpers\NotificationPresenterHelper::present($notification);
    $isUnread = $notification->read_at === null;
    $category = \App\Enums\NotificationCategory::forType($notification->type);
    $readUrl = route('notifications.read', $notification->id);

    // Types that carry longer text, fetched on demand: [button label, hide label]
    $detail = match (true) {
        $notification->type === \App\Notifications\NewApplicationMessage::class => [__('View full message'), __('Show less')],
        $notification->type === \App\Notifications\EngagementResponseNotification::class => [__('View notes'), __('Hide notes')],
        $notification->type === \App\Notifications\EngagementCancelledNotification::class && !empty($notification->data['reason_details']) => [__('View reason'), __('Hide reason')],
        default => null,
    };

    $tone = match ($presented['icon']) {
        'success' => ['bg-teal-50 text-teal-700', 'check-circle'],
        'danger' => ['bg-red-50 text-red-700', 'exclamation-triangle-3'],
        'message' => ['bg-blue-50 text-blue-700', 'envelope'],
        default => ['bg-neutral-100 text-neutral-600', 'bell'],
    };
@endphp

<li id="notification-{{ $notification->id }}" x-data="notificationRow('{{ $readUrl }}', {{ $isUnread ? 'true' : 'false' }})"
    class="group relative flex gap-4 px-5 py-4 transition-colors duration-150 hover:bg-neutral-50/70">
    @if ($isUnread)
        <span x-show="unread" aria-hidden="true" class="pointer-events-none absolute inset-0 bg-teal-50/50"></span>
        <span x-show="unread" aria-hidden="true" class="pointer-events-none absolute inset-y-0 left-0 w-0.5 bg-teal-600"></span>
    @endif

    <span class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $tone[0] }}">
        <x-icon :name="$tone[1]" class="h-5 w-5" />
    </span>

    <div class="relative min-w-0 flex-1">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="flex items-center gap-2 text-sm font-semibold text-neutral-900">
                    <span class="truncate">{{ $presented['title'] }}</span>
                    @if ($isUnread)
                        <span x-show="unread" class="h-2 w-2 shrink-0 rounded-full bg-teal-600" title="{{ __('Unread') }}"></span>
                        <span x-show="unread" class="sr-only">{{ __('Unread') }}</span>
                    @endif
                </p>
                <p class="mt-1 text-sm leading-relaxed text-neutral-600">{{ $presented['content'] }}</p>
            </div>

            <button type="button" x-data
                x-on:click="$dispatch('open-modal', { name: 'delete-notification', id: '{{ $notification->id }}' })"
                aria-label="{{ __('Delete notification') }}" title="{{ __('Delete notification') }}"
                class="shrink-0 rounded-lg p-1.5 text-neutral-400 transition-colors hover:bg-red-50 hover:text-red-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-300 lg:opacity-0 lg:group-focus-within:opacity-100 lg:group-hover:opacity-100">
                <x-icon name="trash" class="h-4 w-4" />
            </button>
        </div>

        <p class="mt-1.5 flex flex-wrap items-center gap-x-2 text-xs text-tertiary">
            <time datetime="{{ $notification->created_at->toIso8601String() }}"
                title="{{ $notification->created_at->format('M j, Y g:i A') }}">{{ $notification->created_at->diffForHumans() }}</time>
            <span aria-hidden="true">·</span>
            <span>{{ __($category->label()) }}</span>
        </p>

        <!-- Expanded detail (plain text: user-written content must never be rendered as HTML) -->
        @if ($detail)
            <div x-show="expanded" x-cloak x-transition.opacity
                class="mt-3 whitespace-pre-wrap rounded-xl border border-neutral-200 bg-white p-4 text-sm leading-relaxed text-neutral-700"
                x-text="detail"></div>
        @endif

        <div class="mt-3 flex flex-wrap items-center gap-2">
            @if ($presented['action_url'])
                <x-btn variant="secondary" size="sm" href="{{ $readUrl }}">{{ $presented['action_label'] }}</x-btn>
            @elseif ($isUnread)
                <x-btn variant="ghost" size="sm" type="button" x-show="unread" x-on:click="markRead()">
                    <x-icon name="check" class="h-4 w-4" />{{ __('Mark as read') }}
                </x-btn>
            @endif

            @if ($detail)
                <x-btn variant="ghost" size="sm" type="button" x-on:click="toggle()">
                    <span x-text="loading ? '{{ __('Loading…') }}' : (expanded ? '{{ $detail[1] }}' : '{{ $detail[0] }}')"></span>
                </x-btn>
            @endif

            <span x-show="failed" x-cloak class="text-xs text-red-600">{{ __('Something went wrong. Please try again.') }}</span>
        </div>
    </div>
</li>
