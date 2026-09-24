@if ($notification->type === 'App\Notifications\NewApplicationMessage')
    <button @click="toggleMessage"
        class="inline-flex items-center px-3 py-1.5 bg-primary/10 text-primary rounded-lg text-sm font-medium hover:bg-primary/20 transition"
        x-text="loading ? 'Loading...' : (expanded ? 'Show less' : 'View full message')" type="button">
    </button>

    @if (isset($notification->data['job_slug']))
        <a href="{{ route('applications.show', $notification->data['job_slug']) }}"
            class="inline-flex items-center px-3 py-1.5 bg-secondary/10 text-secondary rounded-lg text-sm font-medium hover:bg-secondary/20 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
            View application
        </a>
    @endif
@elseif ($notification->type === 'App\Notifications\HiredNotification')
    @if (isset($notification->data['job_slug']))
        <a href="{{ route('notifications.read', $notification->id) }}"
            class="inline-flex items-center px-3 py-1.5 bg-green-100 text-green-600 rounded-lg text-sm font-medium hover:bg-green-200 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            View details
        </a>
    @endif
@elseif ($notification->type === 'App\Notifications\EngagementResponseNotification')
    <button @click="toggleMessage"
        class="inline-flex items-center px-3 py-1.5 bg-primary/10 text-primary rounded-lg text-sm font-medium hover:bg-primary/20 transition"
        x-text="loading ? 'Loading...' : (expanded ? 'Hide notes' : 'View notes')" type="button">
    </button>

    @if (isset($notification->data['job_slug']))
        <a href="{{ route('notifications.read', $notification->id) }}"
            class="inline-flex items-center px-3 py-1.5 {{ $notification->data['response'] === 'accepted' ? 'bg-green-100 text-green-600 hover:bg-green-200' : 'bg-red-100 text-red-600 hover:bg-red-200' }} rounded-lg text-sm font-medium transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
            View details
        </a>
    @endif
@elseif($notification->type === 'App\Notifications\EngagementCancelledNotification')
    @if (!empty($notification->data['reason_details']))
        <button @click="toggleMessage"
            class="inline-flex items-center px-3 py-1.5 bg-red-100 text-red-600 rounded-lg text-sm font-medium hover:bg-red-200 transition"
            x-text="loading ? 'Loading...' : (expanded ? 'Hide reason' : 'View reason')" type="button">
        </button>
    @endif

    @if (isset($notification->data['engagement_id']))
        <a href="{{ route('notifications.read', $notification->id) }}"
            class="inline-flex items-center px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
            View details
        </a>
    @endif
@else
    <a href="{{ route('notifications.read', $notification->id) }}"
        class="inline-flex items-center px-3 py-1.5 bg-primary/10 text-primary rounded-lg text-sm font-medium hover:bg-primary/20 transition">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24"
            stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        View details
    </a>
@endif
