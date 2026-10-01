@php
    $unreadHere = collect($notifications->items())->filter(fn ($n) => ! $n['read']);
    $actions = $unreadHere->isNotEmpty() ? \App\Support\Staff\BulkActions::forPage('notifications', auth()->user()) : [];
@endphp
<x-staff-layout :title="__('Notifications')">
    <div class="container mx-auto max-w-7xl px-4 py-8">
        <x-staff.header :title="__('Notifications')">{{ __('New models, applications and disputes that need someone on staff.') }}
            <x-slot:actions>@if ($unread > 0)<form method="POST" action="{{ route('admin.notifications.read-all') }}">@csrf<x-btn variant="secondary" size="sm" type="submit">{{ __('Mark all as read') }}</x-btn></form>@endif</x-slot:actions>
        </x-staff.header>

        @if ($notifications->isEmpty())
            <x-empty-state icon="bell" :title="__('Nothing here')" :description="__('You will be told here when something needs you.')" />
        @else
            <x-staff.bulk :actions="$actions" :ids="$unreadHere->pluck('id')->all()">
            @if ($actions)<x-staff.bulk-selectall />@endif
            <x-card clip>
                <ul class="divide-y divide-neutral-100">
                    @foreach ($notifications as $notification)
                        <li class="flex items-start" :class="selected.includes('{{ $notification['id'] }}') ? 'bg-teal-50/50' : ''">
                            @if ($actions)<span class="w-12 shrink-0 pl-5 pt-[1.15rem]">@unless ($notification['read'])<x-staff.bulk-check :value="$notification['id']" :label="__('Select this notification')" />@endunless</span>@endif
                            <a href="{{ route('admin.notifications.open', $notification['id']) }}" class="flex min-w-0 flex-1 items-start gap-3 py-4 pr-5 hover:bg-neutral-50 focus:outline-none focus-visible:bg-neutral-50 {{ $actions ? '' : 'pl-5' }}">
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
            </x-staff.bulk>
            <x-pager :paginator="$notifications" />
        @endif
    </div>
</x-staff-layout>
