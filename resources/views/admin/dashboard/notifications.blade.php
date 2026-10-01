<section aria-labelledby="notes-heading">
    <x-card clip>
        <div class="flex items-center justify-between gap-3 border-b border-neutral-100 px-5 py-4">
            <h2 id="notes-heading" class="font-tertiary text-base font-semibold text-neutral-900">{{ __('Notifications') }}</h2>
            <a href="{{ route('admin.notifications.index') }}" class="text-sm font-medium text-teal-700 hover:text-teal-800">{{ __('View all') }}</a>
        </div>
        @if ($notifications->isEmpty())
            <p class="px-5 py-8 text-center text-sm text-tertiary">{{ __('No notifications yet.') }}</p>
        @else
            <ul class="divide-y divide-neutral-100">
                @foreach ($notifications as $notification)
                    @php $shown = \App\Helpers\NotificationPresenterHelper::present($notification); @endphp
                    <li><a href="{{ route('admin.notifications.open', $notification->id) }}" class="block px-5 py-3 hover:bg-neutral-50 focus:outline-none focus-visible:bg-neutral-50">
                        <p class="flex items-start gap-2 text-sm {{ $notification->read_at ? 'text-neutral-600' : 'font-medium text-neutral-900' }}">@unless ($notification->read_at)<span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-teal-500" aria-hidden="true"></span>@endunless<span>{{ $shown['content'] }}</span></p>
                        <p class="mt-0.5 pl-4 text-xs text-tertiary">{{ $notification->created_at->diffForHumans() }}</p>
                    </a></li>
                @endforeach
            </ul>
        @endif
    </x-card>
</section>
