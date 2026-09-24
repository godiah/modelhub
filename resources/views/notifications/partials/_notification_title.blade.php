{{-- This partial displays the title for each notification type --}}
@if ($notification->type === 'App\Notifications\NewApplicationMessage')
    {{ $notification->data['subject'] ?? 'New message' }}
@elseif ($notification->type === 'App\Notifications\HiredNotification')
    You've been hired!
@elseif ($notification->type === 'App\Notifications\EngagementCancelledNotification')
    Cancelled Engagement
@elseif ($notification->type === 'App\Notifications\EngagementResponseNotification')
    @if ($notification->data['response'] === 'accepted')
        Offer Accepted
    @else
        Offer Declined
    @endif
@else
    {{ class_basename($notification->type) }}
@endif
