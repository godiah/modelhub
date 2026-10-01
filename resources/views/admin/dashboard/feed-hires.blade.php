<section aria-labelledby="feed-hires">
    <x-card clip>
        <div class="flex items-center justify-between gap-3 border-b border-neutral-100 px-5 py-4"><h2 id="feed-hires" class="font-tertiary text-base font-semibold text-neutral-900">{{ __('Hires needing a look') }}</h2><a href="{{ route('admin.engagements.index') }}" class="text-sm font-medium text-teal-700 hover:text-teal-800">{{ __('All hires') }}</a></div>
        <div class="p-5">
            <p class="mb-2 text-xs text-tertiary">{{ __('Active hires with deliverables past their due date.') }}</p>
            @if ($feed['stuck']->isEmpty())
                <p class="py-4 text-sm text-tertiary">{{ __('None. Every active hire is on schedule.') }}</p>
            @else
                <ul class="divide-y divide-neutral-100">
                    @foreach ($feed['stuck'] as $hire)
                        @php $days = (int) \Illuminate\Support\Carbon::parse($hire->oldest_due)->diffInDays(today()); @endphp
                        <li><a href="{{ route('admin.engagements.show', $hire) }}" class="flex items-center gap-3 py-2.5 text-sm hover:text-teal-700"><span class="min-w-0 flex-1"><span class="block truncate font-medium text-neutral-900">{{ $hire->application->job->title }}</span><span class="block truncate text-xs text-tertiary">{{ $hire->application->poster?->name }} → {{ $hire->application->applicant?->name }}</span></span><span class="shrink-0 text-right text-xs"><span class="block font-medium {{ $days >= \App\Services\Admin\StaffDashboardService::RED_DAYS ? 'text-red-700' : 'text-amber-700' }}">{{ trans_choice(':count overdue|:count overdue', $hire->overdue_count, ['count' => $hire->overdue_count]) }}</span><span class="block text-tertiary">{{ __('oldest :n days late', ['n' => $days]) }}</span></span></a></li>
                    @endforeach
                </ul>
            @endif
        </div>
    </x-card>
</section>
