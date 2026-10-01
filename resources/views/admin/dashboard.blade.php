@php
    $staff = auth()->user();
    $a = $attention;
    $hasAnyQueue = $a['queues'] !== [];
@endphp
<x-staff-layout :title="__('Dashboard')">
    <div class="container mx-auto max-w-7xl space-y-6 px-4 py-8">
        <!-- Welcome and the headline -->
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="font-tertiary text-2xl font-bold tracking-tight text-neutral-900 sm:text-3xl">{{ __('Welcome, :name', ['name' => \Illuminate\Support\Str::before($staff->name, ' ')]) }}</h1>
                <p class="mt-1 text-sm text-tertiary sm:text-base">
                    @if ($staff->roles->isEmpty())
                        {{ __('You are signed in, but have no role yet. Ask a Super admin to give you one.') }}
                    @elseif (! $hasAnyQueue)
                        {{ __('Signed in as :roles.', ['roles' => $staff->roles->pluck('name')->implode(', ')]) }}
                    @elseif ($a['total'] === 0)
                        {{ __('Every queue is clear.') }}
                    @else
                        <span class="font-medium text-neutral-900">{{ trans_choice(':count item needs you|:count items need you', $a['total'], ['count' => $a['total']]) }}</span>@if ($a['overdue'] > 0)<span class="text-red-700"> · {{ trans_choice(':count has waited over :days days|:count have waited over :days days', $a['overdue'], ['count' => $a['overdue'], 'days' => \App\Services\Admin\StaffDashboardService::AMBER_DAYS]) }}</span>@endif.
                    @endif
                </p>
            </div>
            <p class="text-sm text-tertiary">{{ now()->format('l, F j') }}</p>
        </div>

        @if ($hasAnyQueue)
            <section aria-label="{{ __('Queues') }}" @class(['grid grid-cols-2 gap-4', 'lg:grid-cols-4' => count($a['queues']) >= 4, 'lg:grid-cols-3' => count($a['queues']) === 3])>
                @foreach ($a['queues'] as $queue)
                    @php
                        $tone = $queue['count'] === 0 ? 'teal' : ['red' => 'red', 'amber' => 'amber', 'neutral' => 'teal'][\App\Services\Admin\StaffDashboardService::tone($queue['oldest'])];
                        $hint = $queue['count'] === 0 ? __('Nothing waiting') : __('Oldest waiting :when', ['when' => $queue['oldest']?->diffForHumans(null, true) ?? '—']).($queue['late'] > 0 ? ' · '.__(':n late', ['n' => $queue['late']]) : '');
                    @endphp
                    <x-stat-tile :label="__($queue['title'])" :value="number_format($queue['count'])" :hint="$hint" :icon="$queue['icon']" :url="$queue['url']" :tone="$tone" />
                @endforeach
            </section>
        @endif

        @include('admin.dashboard.attention')

        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2">@include('admin.dashboard.my-work')</div>
            @include('admin.dashboard.notifications')
        </div>

        @if ($pulse)@include('admin.dashboard.pulse')@endif
        @if (! $pulse && $activity)@include('admin.dashboard.activity')@endif

        @if ($feeds)
            <div class="grid grid-cols-1 items-start gap-6 xl:grid-cols-2">
                @isset($feeds['members'])@include('admin.dashboard.feed-members', ['feed' => $feeds['members']])@endisset
                @isset($feeds['projects'])@include('admin.dashboard.feed-projects', ['feed' => $feeds['projects']])@endisset
                @isset($feeds['models'])@include('admin.dashboard.feed-models', ['feed' => $feeds['models']])@endisset
                @isset($feeds['hires'])@include('admin.dashboard.feed-hires', ['feed' => $feeds['hires']])@endisset
            </div>
        @endif

        @if ($team)@include('admin.dashboard.team')@endif
    </div>
</x-staff-layout>
