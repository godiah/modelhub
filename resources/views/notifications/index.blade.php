<x-app-layout>
    @php
        $isFiltered = $filter !== 'all';
        $activeCategory = \App\Enums\NotificationCategory::tryFrom($filter);
        $heading = match (true) {
            $filter === 'unread' => __('Unread'),
            $activeCategory !== null => __($activeCategory->label()),
            default => __('All notifications'),
        };
    @endphp

    <div class="container mx-auto max-w-7xl px-4 py-8">
        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[16rem_minmax(0,1fr)]">
            @include('notifications.partials.filters')

            <!-- Feed -->
            <div class="min-w-0">
                <x-card class="rounded-2xl">
                    <header class="flex flex-wrap items-center justify-between gap-3 border-b border-neutral-100 px-5 py-4">
                        <div>
                            <h2 class="font-tertiary text-base font-semibold text-neutral-900">{{ $heading }}</h2>
                            <p class="text-xs text-tertiary">
                                {{ trans_choice(':count notification|:count notifications', $notifications->total(), ['count' => $notifications->total()]) }}
                                @if ($counts['unread'] > 0)
                                    · {{ __(':count unread', ['count' => $counts['unread']]) }}
                                @endif
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            @if ($counts['unread'] > 0)
                                <form action="{{ route('notifications.read-all') }}" method="POST">
                                    @csrf
                                    <x-btn variant="secondary" size="sm">
                                        <x-icon name="check" class="h-4 w-4" />
                                        {{ __('Mark all as read') }}
                                    </x-btn>
                                </form>
                            @endif
                            @if ($counts['all'] > 0)
                                <x-btn variant="danger-outline" size="sm" type="button" x-data
                                    x-on:click="$dispatch('open-modal', 'clear-notifications')">
                                    <x-icon name="trash" class="h-4 w-4" />
                                    {{ __('Clear all') }}
                                </x-btn>
                            @endif
                        </div>
                    </header>

                    @forelse ($groups as $label => $items)
                        <section aria-label="{{ $label }}">
                            <h3 class="border-y border-neutral-100 bg-neutral-50/60 px-5 py-2 text-xs font-semibold uppercase tracking-wide text-tertiary first:border-t-0">{{ __($label) }}</h3>
                            <ul class="divide-y divide-neutral-100">
                                @foreach ($items as $notification)
                                    @include('notifications.partials.row', ['notification' => $notification])
                                @endforeach
                            </ul>
                        </section>
                    @empty
                        <div class="flex flex-col items-center px-6 py-16 text-center">
                            @if ($counts['all'] === 0)
                                <span class="flex h-14 w-14 items-center justify-center rounded-full bg-neutral-100 text-neutral-500"><x-icon name="bell" class="h-7 w-7" /></span>
                                <p class="mt-4 font-semibold text-neutral-900">{{ __('No notifications yet') }}</p>
                                <p class="mt-1 max-w-sm text-sm text-tertiary">{{ __('Offers, messages, payments and reviews will show up here as they happen.') }}</p>
                            @elseif ($filter === 'unread')
                                <span class="flex h-14 w-14 items-center justify-center rounded-full bg-teal-50 text-teal-700"><x-icon name="check-circle" class="h-7 w-7" /></span>
                                <p class="mt-4 font-semibold text-neutral-900">{{ __("You're all caught up") }}</p>
                                <p class="mt-1 max-w-sm text-sm text-tertiary">{{ __('There is nothing unread.') }}</p>
                                <x-btn class="mt-5" variant="secondary" size="sm" href="{{ route('notifications.index') }}" wire:navigate>{{ __('View all notifications') }}</x-btn>
                            @else
                                <span class="flex h-14 w-14 items-center justify-center rounded-full bg-neutral-100 text-neutral-500"><x-icon :name="$activeCategory?->icon() ?? 'bell'" class="h-7 w-7" /></span>
                                <p class="mt-4 font-semibold text-neutral-900">{{ __('Nothing here') }}</p>
                                <p class="mt-1 max-w-sm text-sm text-tertiary">{{ __('You have no notifications in this category.') }}</p>
                                <x-btn class="mt-5" variant="secondary" size="sm" href="{{ route('notifications.index') }}" wire:navigate>{{ __('View all notifications') }}</x-btn>
                            @endif
                        </div>
                    @endforelse

                    <x-pager :paginator="$notifications" :footer="true" :navigate="true" />
                </x-card>
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

    {{-- Inline (not app.js): Livewire starts Alpine before deferred module scripts run. --}}
    <script>
        window.notificationRow = function(url, unread) {
            return {
                unread,
                expanded: false,
                detail: '',
                loading: false,
                failed: false,

                // Every read goes through the same endpoint the action links use; ajax gets JSON back.
                async request() {
                    const response = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content'),
                        },
                    });

                    return response.json();
                },

                async toggle() {
                    if (this.detail) {
                        this.expanded = !this.expanded;
                        return;
                    }

                    this.loading = true;
                    this.failed = false;

                    try {
                        const data = await this.request();

                        if (!data.success) throw new Error('rejected');

                        this.detail = data.fullMessage;
                        this.expanded = true;
                        this.unread = false;
                    } catch (error) {
                        this.failed = true;
                    } finally {
                        this.loading = false;
                    }
                },

                async markRead() {
                    try {
                        const data = await this.request();
                        if (data.success) this.unread = false;
                    } catch (error) {
                        this.failed = true;
                    }
                },
            };
        };
    </script>
</x-app-layout>
