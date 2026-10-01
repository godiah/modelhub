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
            <ul class="flex flex-wrap gap-2" aria-label="{{ __('Queues') }}">
                @foreach ($a['queues'] as $queue)
                    <li><a href="{{ $queue['url'] }}" @class(['inline-flex items-center gap-2 rounded-full border px-3.5 py-1.5 text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40', 'border-amber-300 bg-amber-50 text-amber-900 hover:bg-amber-100' => $queue['late'] > 0, 'border-neutral-200 bg-white text-neutral-700 hover:border-neutral-300 hover:bg-neutral-50' => $queue['late'] === 0])>
                        {{ __($queue['label']) }}<span class="text-xs tabular-nums {{ $queue['late'] > 0 ? 'text-amber-800' : 'text-tertiary' }}">{{ $queue['count'] }}</span>@if ($queue['late'] > 0)<span class="text-xs font-semibold text-red-700">{{ __(':n late', ['n' => $queue['late']]) }}</span>@endif</a></li>
                @endforeach
            </ul>
        @endif

        @include('admin.dashboard.attention')

        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2">@include('admin.dashboard.my-work')</div>
            @include('admin.dashboard.notifications')
        </div>

        @if ($pulse)@include('admin.dashboard.pulse')@endif

        @if ($feeds)
            <div class="grid grid-cols-1 items-start gap-6 xl:grid-cols-2">
                @isset($feeds['members'])@include('admin.dashboard.feed-members', ['feed' => $feeds['members']])@endisset
                @isset($feeds['projects'])@include('admin.dashboard.feed-projects', ['feed' => $feeds['projects']])@endisset
                @isset($feeds['hires'])@include('admin.dashboard.feed-hires', ['feed' => $feeds['hires']])@endisset
                @isset($feeds['models'])@include('admin.dashboard.feed-models', ['feed' => $feeds['models']])@endisset
            </div>
        @endif

        @if ($team)@include('admin.dashboard.team')@endif
    </div>
</x-staff-layout>
