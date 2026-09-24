{{-- This partial displays the content for each notification type --}}
@if ($notification->type === 'App\Notifications\NewApplicationMessage')
    {{ $notification->data['message_preview'] ?? 'You received a new message' }}
    regarding job: <span
        class="font-medium text-neutral-800">{{ $notification->data['job_title'] ?? 'a job posting' }}</span>
@elseif ($notification->type === 'App\Notifications\HiredNotification')
    You've been hired for job:
    <span class="font-medium text-neutral-800">
        {{ $notification->data['job_title'] ?? 'a job posting' }}
    </span>
    with an agreed amount of
    {{ config('app.currency_symbol') }}{{ number_format($notification->data['agreed_amount'], 2) }}
@elseif ($notification->type === 'App\Notifications\EngagementCancelledNotification')
    <div class="flex items-center">
        <span class="text-red-500 mr-2">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                class="h-5 w-5">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>
        </span>
        <p class="font-medium">Engagement cancelled: "{{ $notification->data['job_title'] }}"</p>
    </div>
    <p class="text-sm text-gray-600 mt-1">
        Cancelled by: {{ $notification->data['initiator_type'] }} -
        Reason: {{ $notification->data['reason_category'] }}
    </p>
    @if (!empty($notification->data['partial_payment_amount']))
        <p class="text-sm text-gray-800 font-medium mt-1">
            Partial payment: ${{ number_format($notification->data['partial_payment_amount'], 2) }}
        </p>
    @endif
@elseif ($notification->type === 'App\Notifications\EngagementResponseNotification')
    <span class="font-medium text-neutral-800">{{ $notification->data['applicant_name'] ?? 'An applicant' }}</span>
    has {{ $notification->data['response'] }}
    your offer for job: <span
        class="font-medium text-neutral-800">{{ $notification->data['job_title'] ?? 'a job posting' }}</span>
    @if ($notification->data['response'] === 'accepted')
        <a href="{{ route('engagements.index') }}" class="text-primary hover:underline font-tertiary text-sm">Proceed
            to
            set-up payment.</a>
    @endif
@else
    {{ $notification->data['message'] ?? 'You have a new notification' }}
@endif
