<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-tertiary font-bold text-2xl text-primary leading-tight">
                Notifications
            </h2>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-lg sm:rounded-xl">
                <div class="p-8 bg-white border-b border-neutral-200">
                    <!-- Header with action buttons -->
                    <div class="flex flex-col md:flex-row md:justify-end md:items-center mb-8 gap-4">
                        <div class="flex gap-3">
                            @if (Auth::user()->unreadNotifications->count() > 0)
                                <form action="{{ route('notifications.read-all') }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                        class="inline-flex items-center px-4 py-2 bg-primary text-white text-sm font-medium rounded-lg transition-colors duration-150 hover:bg-primary/90">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                        Mark all read
                                    </button>
                                </form>
                            @endif

                            @if ($notifications->count() > 0)
                                <form action="{{ route('notifications.delete-all') }}" method="POST"
                                    onsubmit="return confirm('Are you sure you want to clear all notifications?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="inline-flex items-center px-4 py-2 border border-neutral-300 text-neutral-700 text-sm font-medium rounded-lg transition-colors duration-150 hover:bg-neutral-50">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        Clear all
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    @if ($notifications->count() > 0)
                        <div class="space-y-4" id="notifications-container">
                            @foreach ($notifications as $notification)
                                <div id="notification-{{ $notification->id }}" x-data="{
                                    expanded: false,
                                    fullMessage: '',
                                    loading: false,
                                    toggleMessage() {
                                        if (!this.expanded && !this.fullMessage) {
                                            this.loading = true;
                                            fetch('{{ route('notifications.read', $notification->id) }}', {
                                                    headers: {
                                                        'X-Requested-With': 'XMLHttpRequest',
                                                        'Content-Type': 'application/json',
                                                        'Accept': 'application/json',
                                                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                                                    }
                                                })
                                                .then(response => response.json())
                                                .then(data => {
                                                    if (data.success) {
                                                        this.fullMessage = data.fullMessage;
                                                        this.expanded = true;
                                                    }
                                                    this.loading = false;
                                                })
                                                .catch(error => {
                                                    console.error('Error:', error);
                                                    this.loading = false;
                                                });
                                        } else {
                                            this.expanded = !this.expanded;
                                        }
                                    },
                                    deleteNotification() {
                                        if (confirm('Are you sure you want to delete this notification?')) {
                                            fetch('{{ route('notifications.delete', $notification->id) }}', {
                                                    method: 'DELETE',
                                                    headers: {
                                                        'X-Requested-With': 'XMLHttpRequest',
                                                        'Content-Type': 'application/json',
                                                        'Accept': 'application/json',
                                                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                                                    }
                                                })
                                                .then(response => response.json())
                                                .then(data => {
                                                    if (data.success) {
                                                        const element = document.getElementById('notification-{{ $notification->id }}');
                                                        element.remove();
                                
                                                        // Check if there are any notifications left
                                                        if (document.getElementById('notifications-container').children.length === 0) {
                                                            location.reload();
                                                        }
                                                    }
                                                }).catch(error => {
                                                    console.error('Error:', error);
                                                });
                                        }
                                    }
                                }"
                                    class="p-5 border {{ $notification->read_at ? 'border-neutral-200 bg-white' : 'border-l-4 border-l-secondary border-r border-t border-b border-neutral-200 bg-neutral-50' }} rounded-xl shadow-sm transition-all duration-200 hover:shadow-md">

                                    <!-- Notification header -->
                                    <div class="flex justify-between items-start">
                                        <!-- Left side with type indicator -->
                                        <div class="flex items-center">
                                            <div class="mr-3">
                                                @if ($notification->type === 'App\Notifications\NewApplicationMessage')
                                                    <div class="p-2 bg-secondary/10 text-secondary rounded-lg">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                        </svg>
                                                    </div>
                                                @elseif ($notification->type === 'App\Notifications\HiredNotification')
                                                    <div class="p-2 bg-green-100 text-green-600 rounded-lg">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                    </div>
                                                @elseif ($notification->type === 'App\Notifications\EngagementResponseNotification')
                                                    @if ($notification->data['response'] === 'accepted')
                                                        <div class="p-2 bg-green-100 text-green-600 rounded-lg">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                                                                fill="none" viewBox="0 0 24 24"
                                                                stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                            </svg>
                                                        </div>
                                                    @else
                                                        <div class="p-2 bg-red-100 text-red-600 rounded-lg">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                                                                fill="none" viewBox="0 0 24 24"
                                                                stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                            </svg>
                                                        </div>
                                                    @endif
                                                @else
                                                    <div class="p-2 bg-primary/10 text-primary rounded-lg">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                                        </svg>
                                                    </div>
                                                @endif
                                            </div>

                                            <h3 class="font-medium text-lg text-neutral-800">
                                                @if ($notification->type === 'App\Notifications\NewApplicationMessage')
                                                    {{ $notification->data['subject'] ?? 'New message' }}
                                                @elseif ($notification->type === 'App\Notifications\HiredNotification')
                                                    You've been hired!
                                                @elseif ($notification->type === 'App\Notifications\EngagementResponseNotification')
                                                    @if ($notification->data['response'] === 'accepted')
                                                        Offer Accepted
                                                    @else
                                                        Offer Declined
                                                    @endif
                                                @else
                                                    {{ class_basename($notification->type) }}
                                                @endif
                                            </h3>
                                        </div>

                                        <!-- Right side with time and actions -->
                                        <div class="flex items-center space-x-3">
                                            <span class="text-sm text-neutral-500 whitespace-nowrap">
                                                {{ $notification->created_at->diffForHumans() }}
                                            </span>

                                            <button @click="deleteNotification"
                                                class="p-1.5 rounded-full text-neutral-400 hover:text-red-500 hover:bg-neutral-100 transition-colors duration-150"
                                                type="button" title="Delete notification">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Notification content -->
                                    <div class="mt-3 ml-10 font-main">
                                        <p class="text-neutral-600">
                                            @if ($notification->type === 'App\Notifications\NewApplicationMessage')
                                                {{ $notification->data['message_preview'] ?? 'You received a new message' }}
                                                regarding job: <span
                                                    class="font-medium text-neutral-800">{{ $notification->data['job_title'] ?? 'a job posting' }}</span>
                                            @elseif ($notification->type === 'App\Notifications\HiredNotification')
                                                You've been hired for job: <span
                                                    class="font-medium text-neutral-800">{{ $notification->data['job_title'] ?? 'a job posting' }}</span>
                                                with an agreed amount of
                                                {{ config('app.currency_symbol') }}{{ number_format($notification->data['agreed_amount'], 2) }}
                                            @elseif ($notification->type === 'App\Notifications\EngagementResponseNotification')
                                                <span
                                                    class="font-medium text-neutral-800">{{ $notification->data['applicant_name'] ?? 'An applicant' }}</span>
                                                has {{ $notification->data['response'] }}
                                                your offer for job: <span
                                                    class="font-medium text-neutral-800">{{ $notification->data['job_title'] ?? 'a job posting' }}</span>
                                                @if ($notification->data['response'] === 'accepted')
                                                    <a href="{{ route('engagements.index') }}"
                                                        class="text-primary hover:underline font-tertiary text-sm">Proceed
                                                        to
                                                        set-up payment.</a>
                                                @endif
                                            @else
                                                {{ $notification->data['message'] ?? 'You have a new notification' }}
                                            @endif
                                        </p>
                                    </div>

                                    <!-- Full message content - shown when expanded -->
                                    <div x-show="expanded" x-cloak
                                        class="mt-4 ml-10 p-4 bg-neutral-50 rounded-lg border border-neutral-200 text-neutral-700">
                                        <div x-html="fullMessage" class="whitespace-pre-wrap prose max-w-none"></div>
                                    </div>

                                    <!-- Action buttons -->
                                    <div class="mt-4 ml-10 flex flex-wrap gap-3">
                                        @if ($notification->type === 'App\Notifications\NewApplicationMessage')
                                            <button @click="toggleMessage"
                                                class="inline-flex items-center px-3 py-1.5 bg-primary/10 text-primary rounded-lg text-sm font-medium hover:bg-primary/20 transition"
                                                x-text="loading ? 'Loading...' : (expanded ? 'Show less' : 'View full message')"
                                                type="button">
                                            </button>

                                            @if (isset($notification->data['job_slug']))
                                                <a href="{{ route('applications.show', $notification->data['job_slug']) }}"
                                                    class="inline-flex items-center px-3 py-1.5 bg-secondary/10 text-secondary rounded-lg text-sm font-medium hover:bg-secondary/20 transition">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1"
                                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                    View application
                                                </a>
                                            @endif
                                        @elseif ($notification->type === 'App\Notifications\HiredNotification')
                                            @if (isset($notification->data['job_slug']))
                                                <a href="{{ route('notifications.read', $notification->id) }}"
                                                    class="inline-flex items-center px-3 py-1.5 bg-green-100 text-green-600 rounded-lg text-sm font-medium hover:bg-green-200 transition">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1"
                                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                                    </svg>
                                                    View details
                                                </a>
                                            @endif
                                        @elseif ($notification->type === 'App\Notifications\EngagementResponseNotification')
                                            <button @click="toggleMessage"
                                                class="inline-flex items-center px-3 py-1.5 bg-primary/10 text-primary rounded-lg text-sm font-medium hover:bg-primary/20 transition"
                                                x-text="loading ? 'Loading...' : (expanded ? 'Hide notes' : 'View notes')"
                                                type="button">
                                            </button>

                                            @if (isset($notification->data['job_slug']))
                                                <a href="{{ route('notifications.read', $notification->id) }}"
                                                    class="inline-flex items-center px-3 py-1.5 {{ $notification->data['response'] === 'accepted' ? 'bg-green-100 text-green-600 hover:bg-green-200' : 'bg-red-100 text-red-600 hover:bg-red-200' }} rounded-lg text-sm font-medium transition">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1"
                                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                    View details
                                                </a>
                                            @endif
                                        @else
                                            <a href="{{ route('notifications.read', $notification->id) }}"
                                                class="inline-flex items-center px-3 py-1.5 bg-primary/10 text-primary rounded-lg text-sm font-medium hover:bg-primary/20 transition">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                View details
                                            </a>
                                        @endif
                                    </div>

                                    <!-- Non-JS fallback form for deletion -->
                                    <form id="delete-form-{{ $notification->id }}"
                                        action="{{ route('notifications.delete', $notification->id) }}"
                                        method="POST" class="hidden">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                </div>
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        <div class="mt-8">
                            {{ $notifications->links() }}
                        </div>
                    @else
                        <div
                            class="flex flex-col items-center justify-center py-12 bg-neutral-50 rounded-xl border border-dashed border-neutral-300">
                            <div class="mb-4 p-3 bg-primary/10 text-primary rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                            </div>
                            <p class="text-lg font-tertiary text-neutral-600">You don't have any notifications yet.</p>
                            <p class="text-neutral-500 mt-1">When you receive notifications, they will appear here.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @include('partials\footer-secondary')
</x-app-layout>
