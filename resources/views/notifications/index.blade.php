<x-app-layout>
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
                                    <x-btn variant="secondary" type="submit">
                                        <x-icon name="check" class="h-4 w-4" />
                                        Mark all as read
                                    </x-btn>
                                </form>
                            @endif

                            @if ($notifications->count() > 0)
                                <x-btn variant="danger-outline" type="button" x-data x-on:click="$dispatch('open-modal', 'clear-notifications')">
                                    <x-icon name="trash" class="h-4 w-4" />
                                    Clear all notifications
                                </x-btn>
                            @endif
                        </div>
                    </div>

                    @if ($notifications->count() > 0)
                        <div class="space-y-4" id="notifications-container">
                            {{-- Notification groups --}}
                            @php
                                // Maps each notification class's basename to a group key, keeping related
                                // notification types (even ones added later) filed under a real section
                                // instead of falling into a catch-all "Other" bucket.
                                $groupKeyByType = [
                                    'NewApplicationMessage' => 'messages',
                                    'HiredNotification' => 'hiring',
                                    'EngagementResponseNotification' => 'engagements',
                                    'EngagementCancelledNotification' => 'cancelled',
                                    'DisputeCreatedNotification' => 'disputes',
                                    'PartialPaymentProcessedNotification' => 'payments',
                                    'PaymentAcceptedNotification' => 'payments',
                                    'PaymentDisputedNotification' => 'payments',
                                    'ReviewSubmittedNotification' => 'reviews',
                                    'JobPostedNotification' => 'jobs',
                                ];

                                $groupedNotifications = $notifications->groupBy(
                                    fn($notification) => $groupKeyByType[class_basename($notification->type)] ?? 'other',
                                );

                                // Define the order and labels for groups
                                $groupOrder = [
                                    'hiring' => 'Hiring Notifications',
                                    'engagements' => 'Engagement Responses',
                                    'cancelled' => 'Cancelled Engagement Responses',
                                    'disputes' => 'Disputes',
                                    'payments' => 'Payments',
                                    'reviews' => 'Reviews',
                                    'jobs' => 'Job Postings',
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

                                                            <button type="button" @click="$dispatch('open-modal', { name: 'delete-notification', id: '{{ $notification->id }}' })"
                                                                class="p-1.5 rounded-full text-neutral-400 hover:text-red-500 hover:bg-neutral-100 transition-colors duration-150"
                                                                type="button" title="Delete notification">
                                                                <x-icon name="trash" class="h-4 w-4" />
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
                                <x-icon name="bell" class="h-8 w-8" />
                            </div>
                            <p class="text-lg font-tertiary text-neutral-600">You don't have any notifications yet.</p>
                            <p class="text-neutral-500 mt-1">When you receive notifications, they will appear here.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <x-confirm-dialog name="clear-notifications" title="Clear all notifications" icon="trash" confirm-icon="trash"
        confirm-label="Clear all" method="DELETE" :action="route('notifications.delete-all')"
        message="Are you sure you want to delete all notifications? This action cannot be undone." />

    <x-confirm-dialog name="delete-notification" title="Delete notification" icon="trash" confirm-icon="trash"
        confirm-label="Delete" method="DELETE"
        action-bind="'{{ route('notifications.delete', '__ID__') }}'.replace('__ID__', payload.id)"
        message="Are you sure you want to delete this notification?" />

</x-app-layout>
