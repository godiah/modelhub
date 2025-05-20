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
                    <div class="flex flex-col md:flex-row md:justify-end md:items-center mb-1 gap-4">
                        <div class="flex gap-3">
                            @if (Auth::user()->unreadNotifications->count() > 0)
                                <form action="{{ route('notifications.read-all') }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit"
                                        class="inline-flex items-center px-4 py-2 bg-primary/10 text-primary rounded-lg text-sm font-medium hover:bg-primary/20 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                        Mark all as read
                                    </button>
                                </form>
                            @endif

                            @if ($notifications->count() > 0)
                                <form action="{{ route('notifications.delete-all') }}" method="POST" class="inline"
                                    onsubmit="return confirm('Are you sure you want to delete all notifications? This action cannot be undone.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="inline-flex items-center px-4 py-2 bg-red-50 text-red-600 rounded-lg text-sm font-medium hover:bg-red-100 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        Clear all notifications
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    @if ($notifications->count() > 0)
                        <div class="space-y-4" id="notifications-container">
                            {{-- Notification groups --}}
                            @php
                                // Group notifications by type
                                $groupedNotifications = $notifications->groupBy(function ($notification) {
                                    if (strpos($notification->type, 'NewApplicationMessage') !== false) {
                                        return 'messages';
                                    } elseif (strpos($notification->type, 'HiredNotification') !== false) {
                                        return 'hiring';
                                    } elseif (strpos($notification->type, 'EngagementResponseNotification') !== false) {
                                        return 'engagements';
                                    } elseif (
                                        strpos($notification->type, 'EngagementCancelledNotification') !== false
                                    ) {
                                        return 'cancelled';
                                    } else {
                                        return 'other';
                                    }
                                });

                                // Define the order and labels for groups
                                $groupOrder = [
                                    'hiring' => 'Hiring Notifications',
                                    'engagements' => 'Engagement Responses',
                                    'cancelled' => 'Cancelled Engagement Responses',
                                    'messages' => 'Application Messages',
                                    'other' => 'Other Notifications',
                                ];
                            @endphp

                            {{-- Render notifications by group --}}
                            @foreach ($groupOrder as $groupKey => $groupLabel)
                                @if (isset($groupedNotifications[$groupKey]) && $groupedNotifications[$groupKey]->count() > 0)
                                    <div class="mb-6">
                                        <h3 class="text-lg font-medium text-gray-700 mb-3">{{ $groupLabel }}</h3>
                                        <div class="space-y-3">
                                            @foreach ($groupedNotifications[$groupKey] as $notification)
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

                                                    {{-- Notification header --}}
                                                    <div class="flex justify-between items-start">
                                                        {{-- Left side with type indicator --}}
                                                        <div class="flex items-center">
                                                            <div class="mr-3">
                                                                @include(
                                                                    'notifications.partials._notification_icon',
                                                                    ['notification' => $notification]
                                                                )
                                                            </div>

                                                            <h3 class="font-medium  text-neutral-800">
                                                                @include(
                                                                    'notifications.partials._notification_title',
                                                                    ['notification' => $notification]
                                                                )
                                                            </h3>
                                                        </div>

                                                        {{-- Right side with time and actions --}}
                                                        <div class="flex items-center space-x-3">
                                                            <span class="text-sm text-neutral-500 whitespace-nowrap">
                                                                {{ $notification->created_at->diffForHumans() }}
                                                            </span>

                                                            <button @click="deleteNotification"
                                                                class="p-1.5 rounded-full text-neutral-400 hover:text-red-500 hover:bg-neutral-100 transition-colors duration-150"
                                                                type="button" title="Delete notification">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                                    fill="none" viewBox="0 0 24 24"
                                                                    stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2"
                                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    </div>

                                                    {{-- Notification content --}}
                                                    <div class="mt-3 ml-10 font-main">
                                                        <p class="text-neutral-600 text-sm">
                                                            @include(
                                                                'notifications.partials._notification_content',
                                                                ['notification' => $notification]
                                                            )
                                                        </p>
                                                    </div>

                                                    {{-- Full message content - shown when expanded --}}
                                                    <div x-show="expanded" x-cloak
                                                        class="mt-4 ml-10 p-4 bg-neutral-50 rounded-lg border border-neutral-200 text-neutral-700">
                                                        <div x-html="fullMessage"
                                                            class="whitespace-pre-wrap prose max-w-none text-sm"></div>
                                                    </div>

                                                    {{-- Action buttons --}}
                                                    <div class="mt-4 ml-10 flex flex-wrap gap-3">
                                                        @include(
                                                            'notifications.partials._notification_actions',
                                                            ['notification' => $notification]
                                                        )
                                                    </div>

                                                    {{-- Non-JS fallback form for deletion --}}
                                                    <form id="delete-form-{{ $notification->id }}"
                                                        action="{{ route('notifications.delete', $notification->id) }}"
                                                        method="POST" class="hidden">
                                                        @csrf
                                                        @method('DELETE')
                                                    </form>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>

                        {{-- Pagination --}}
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
