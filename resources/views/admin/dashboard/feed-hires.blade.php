@php $late = $feed['late']; @endphp
<section aria-labelledby="feed-hires">
    <x-card clip>
        <div class="flex items-center justify-between gap-3 border-b border-neutral-100 px-5 py-4">
            <div class="flex items-center gap-2.5">
                <h2 id="feed-hires" class="font-tertiary text-base font-semibold text-neutral-900">{{ __('Hires needing a look') }}</h2>
                @if ($late > 0)<x-badge tone="amber" class="px-2.5 py-0.5 text-xs font-semibold">{{ __(':n late', ['n' => $late]) }}</x-badge>@endif
            </div>
            <a href="{{ route('admin.engagements.index') }}" wire:navigate class="text-sm font-medium text-teal-700 hover:text-teal-800">{{ __('All hires') }}</a>
        </div>

        <div class="p-5">
            @if ($feed['stuck']->isEmpty())
                <div class="flex flex-col items-center px-4 py-6 text-center">
                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-green-50 text-green-600"><x-icon name="check-circle" class="h-6 w-6" /></span>
                    <p class="mt-4 font-semibold text-neutral-900">{{ __('Every active hire is on schedule.') }}</p>
                    <p class="mt-1 text-sm text-tertiary">{{ $feed['active'] > 0 ? trans_choice(':count hire in progress, none overdue.|:count hires in progress, none overdue.', $feed['active'], ['count' => $feed['active']]) : __('No hires are in progress right now.') }}</p>
                </div>
            @else
                <p class="mb-3 text-xs text-tertiary">{{ __('Active hires with deliverables past their due date, the latest first.') }}</p>
                <ul class="space-y-2">
                    @foreach ($feed['stuck'] as $hire)
                        @php
                            $days = (int) \Illuminate\Support\Carbon::parse($hire->oldest_due)->diffInDays(today());
                            $red = $days >= \App\Services\Admin\StaffDashboardService::RED_DAYS;
                            $done = $hire->total_deliverables > 0 ? round($hire->approved_deliverables / $hire->total_deliverables * 100) : 0;
                            $poster = $hire->application->poster;
                            $applicant = $hire->application->applicant;
                        @endphp
                        <li>
                            <a href="{{ route('admin.engagements.show', $hire) }}" wire:navigate class="group flex items-center gap-3 rounded-xl border border-neutral-200 px-3.5 py-3 transition-colors hover:border-teal-300 hover:bg-teal-50/40 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">
                                <span class="flex shrink-0 -space-x-2">
                                    @if ($poster)<x-user-avatar :user="$poster" size="h-9 w-9 ring-2 ring-white" />@endif
                                    @if ($applicant)<x-user-avatar :user="$applicant" size="h-9 w-9 ring-2 ring-white" />@endif
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-semibold text-neutral-900">{{ $hire->application->job->title }}</span>
                                    <span class="block truncate text-xs text-tertiary">{{ $poster?->name }} → {{ $applicant?->name }} · <x-money :amount="$hire->agreed_amount" :decimals="0" /></span>
                                    @if ($hire->total_deliverables > 0)
                                        <span class="mt-1.5 flex items-center gap-2" title="{{ __(':done of :total approved', ['done' => $hire->approved_deliverables, 'total' => $hire->total_deliverables]) }}">
                                            <span class="h-1.5 w-24 overflow-hidden rounded-full bg-neutral-100"><span class="block h-full rounded-full bg-teal-500" style="width: {{ $done }}%"></span></span>
                                            <span class="text-[11px] tabular-nums text-tertiary">{{ $hire->approved_deliverables }}/{{ $hire->total_deliverables }}</span>
                                        </span>
                                    @endif
                                </span>
                                <span class="shrink-0 text-right text-xs">
                                    <span @class(['inline-block rounded-full px-2 py-0.5 font-semibold', 'bg-red-50 text-red-700' => $red, 'bg-amber-50 text-amber-700' => ! $red])>{{ trans_choice(':count overdue|:count overdue', $hire->overdue_count, ['count' => $hire->overdue_count]) }}</span>
                                    <span class="mt-1 block text-tertiary">{{ __('oldest :n days late', ['n' => $days]) }}</span>
                                </span>
                                <x-icon name="chevron-right" class="h-4 w-4 shrink-0 text-neutral-300 transition-colors group-hover:text-teal-600" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </x-card>
</section>
