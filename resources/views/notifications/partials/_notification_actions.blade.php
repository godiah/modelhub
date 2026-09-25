@php
    $presented = \App\Helpers\NotificationPresenterHelper::present($notification);
@endphp

{{-- Types with server-fetched expandable content keep their "show more" toggle --}}
@if ($notification->type === 'App\Notifications\NewApplicationMessage')
    <button @click="toggleMessage"
        class="inline-flex items-center px-3 py-1.5 bg-primary/10 text-primary rounded-lg text-sm font-medium hover:bg-primary/20 transition"
        x-text="loading ? 'Loading...' : (expanded ? 'Show less' : 'View full message')" type="button">
    </button>
@elseif ($notification->type === 'App\Notifications\EngagementResponseNotification')
    <button @click="toggleMessage"
        class="inline-flex items-center px-3 py-1.5 bg-primary/10 text-primary rounded-lg text-sm font-medium hover:bg-primary/20 transition"
        x-text="loading ? 'Loading...' : (expanded ? 'Hide notes' : 'View notes')" type="button">
    </button>
@elseif ($notification->type === 'App\Notifications\EngagementCancelledNotification' && ! empty($notification->data['reason_details']))
    <button @click="toggleMessage"
        class="inline-flex items-center px-3 py-1.5 bg-red-100 text-red-600 rounded-lg text-sm font-medium hover:bg-red-200 transition"
        x-text="loading ? 'Loading...' : (expanded ? 'Hide reason' : 'View reason')" type="button">
    </button>
@endif

@if ($presented['action_url'])
    <a href="{{ route('notifications.read', $notification->id) }}"
        class="inline-flex items-center px-3 py-1.5 bg-primary/10 text-primary rounded-lg text-sm font-medium hover:bg-primary/20 transition">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24"
            stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
        </svg>
        {{ $presented['action_label'] }}
    </a>
@endif
