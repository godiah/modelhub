<x-staff-layout :title="__('Notifications')">
    <div class="container mx-auto max-w-7xl px-4 py-8">
        <x-staff.header :title="__('Notifications')">{{ __('New models, applications and disputes that need someone on staff.') }}
            <x-slot:actions>@if ($unread > 0)<form method="POST" action="{{ route('admin.notifications.read-all') }}">@csrf<x-btn variant="secondary" size="sm" type="submit">{{ __('Mark all as read') }}</x-btn></form>@endif</x-slot:actions>
        </x-staff.header>

        @if ($notifications->isEmpty())
            <x-empty-state icon="bell" :title="__('Nothing here')" :description="__('You will be told here when something needs you.')" />
        @else
            <x-card clip>
                <ul class="divide-y divide-neutral-100">
                    @foreach ($notifications as $notification)
                        <li>
                            <a href="{{ route('admin.notifications.open', $notification['id']) }}" class="flex items-start gap-3 px-5 py-4 hover:bg-neutral-50 focus:outline-none focus-visible:bg-neutral-50">
                                <span @class(['mt-2 h-2 w-2 shrink-0 rounded-full', 'bg-teal-500' => ! $notification['read'], 'bg-transparent' => $notification['read']]) aria-hidden="true"></span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm {{ $notification['read'] ? 'text-neutral-700' : 'font-semibold text-neutral-900' }}">{{ $notification['title'] }}</p>
                                    <p class="mt-0.5 text-sm text-neutral-600">{{ $notification['content'] }}</p>
                                    <p class="mt-1 text-xs text-tertiary">{{ $notification['at']->diffForHumans() }}</p>
                                </div>
                                @if ($notification['action_url'])<span class="shrink-0 text-sm font-medium text-teal-700">{{ $notification['action_label'] }}</span>@endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </x-card>
            <x-pager :paginator="$notifications" />
        @endif
    </div>
</x-staff-layout>
