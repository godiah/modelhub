@php $staff = auth()->user(); @endphp
<x-staff-layout :title="__('Dashboard')">
    <div class="container mx-auto max-w-7xl px-4 py-8">
        <div class="mb-6">
            <h1 class="font-tertiary text-2xl font-bold tracking-tight text-neutral-900 sm:text-3xl">{{ __('Welcome, :name', ['name' => \Illuminate\Support\Str::before($staff->name, ' ')]) }}</h1>
            <p class="mt-1 text-sm text-tertiary sm:text-base">
                @if ($staff->roles->isNotEmpty())
                    {{ __('Signed in as :roles.', ['roles' => $staff->roles->pluck('name')->implode(', ')]) }}
                @else
                    {{ __('You are signed in, but have no role yet. Ask a Super admin to give you one.') }}
                @endif
                @if (collect($queues)->sum('count') > 0)
                    {{ trans_choice(':count item is waiting for you.|:count items are waiting for you.', collect($queues)->sum('count'), ['count' => collect($queues)->sum('count')]) }}
                @elseif ($queues)
                    {{ __('Every queue is clear.') }}
                @endif
            </p>
        </div>

        @if ($queues)
            <section aria-labelledby="queues-heading">
                <h2 id="queues-heading" class="sr-only">{{ __('Work queues') }}</h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ($queues as $queue)
                        <a href="{{ $queue['url'] }}" class="group block focus:outline-none">
                            <x-card class="h-full p-5 transition-shadow group-hover:shadow-md group-focus-visible:ring-2 group-focus-visible:ring-secondary/40">
                                <div class="flex items-start justify-between gap-3">
                                    <p class="text-sm font-medium text-tertiary">{{ __($queue['label']) }}</p>
                                    <span @class(['flex h-9 w-9 shrink-0 items-center justify-center rounded-full', 'bg-amber-50 text-amber-700' => $queue['count'] > 0, 'bg-neutral-100 text-neutral-500' => $queue['count'] === 0])><x-icon :name="$queue['icon']" class="h-5 w-5" /></span>
                                </div>
                                <p class="mt-3 font-tertiary text-3xl font-bold tabular-nums text-neutral-900">{{ $queue['count'] }}</p>
                                <p class="mt-1 text-xs text-tertiary">
                                    @if ($queue['count'] > 0)
                                        {{ __($queue['what']) }}@if ($queue['oldest']) · {{ __('oldest from :when', ['when' => $queue['oldest']->diffForHumans()]) }}@endif
                                    @else
                                        {{ __($queue['empty']) }}
                                    @endif
                                </p>
                            </x-card>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                @if ($mine->isNotEmpty())
                    <section aria-labelledby="mine-heading">
                        <x-card clip>
                            <div class="border-b border-neutral-100 px-5 py-4"><h2 id="mine-heading" class="font-tertiary text-base font-semibold text-neutral-900">{{ __('Disputes you are handling') }}</h2></div>
                            <ul class="divide-y divide-neutral-100">
                                @foreach ($mine as $dispute)
                                    <li><a href="{{ route('admin.disputes.show', $dispute->cancellation_id) }}" class="flex items-center justify-between gap-3 px-5 py-3.5 text-sm hover:bg-neutral-50 focus:outline-none focus-visible:bg-neutral-50">
                                        <span class="min-w-0 truncate font-medium text-neutral-900">{{ $dispute->cancellation->engagement->application->job->title }}</span>
                                        <span class="shrink-0 text-xs text-tertiary">{{ __('filed :when', ['when' => $dispute->created_at->diffForHumans()]) }}</span>
                                    </a></li>
                                @endforeach
                            </ul>
                        </x-card>
                    </section>
                @endif

                @if ($staff->can('view audit log'))
                    <section aria-labelledby="activity-heading">
                        <x-card clip>
                            <div class="flex items-center justify-between gap-3 border-b border-neutral-100 px-5 py-4">
                                <h2 id="activity-heading" class="font-tertiary text-base font-semibold text-neutral-900">{{ __('Recent staff activity') }}</h2>
                                <a href="{{ route('admin.activity.index') }}" class="text-sm font-medium text-teal-700 hover:text-teal-800">{{ __('View all') }}</a>
                            </div>
                            @if ($activity->isEmpty())
                                <p class="px-5 py-8 text-center text-sm text-tertiary">{{ __('Nothing has happened yet.') }}</p>
                            @else
                                <ul class="divide-y divide-neutral-100">
                                    @foreach ($activity as $entry)
                                        <li class="flex items-start gap-3 px-5 py-3">
                                            @if ($entry->staff)<x-user-avatar :user="$entry->staff" size="h-8 w-8" />@endif
                                            <div class="min-w-0">
                                                <p class="text-sm text-neutral-900"><span class="font-medium">{{ $entry->staff?->name ?? __('Someone') }}</span> <span class="text-neutral-600">{{ \Illuminate\Support\Str::lcfirst($entry->summary) }}</span></p>
                                                <p class="text-xs text-tertiary">{{ $entry->created_at->diffForHumans() }}</p>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </x-card>
                    </section>
                @endif
            </div>

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
        </div>
    </div>
</x-staff-layout>
