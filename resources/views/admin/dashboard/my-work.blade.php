@php
    // area (the part of an action before its dot) => [singular|plural label, icon]
    $areas = [
        'model' => ['model|models', 'cube'], 'seller' => ['store|stores', 'tag'], 'review' => ['review|reviews', 'flag'], 'dispute' => ['dispute|disputes', 'scale'],
        'project' => ['project|projects', 'briefcase'], 'member' => ['member|members', 'user-group'], 'role' => ['role|roles', 'shield-check'], 'settings' => ['setting|settings', 'cog-6-tooth'],
    ];
    $max = max(1, collect($work['daily'])->max('count'));
    $delta = $work['total'] - $work['previous'];
@endphp
<section aria-labelledby="work-heading">
    <x-card clip>
        <div class="flex items-center justify-between gap-3 border-b border-neutral-100 px-5 py-4">
            <h2 id="work-heading" class="font-tertiary text-base font-semibold text-neutral-900">{{ __('Your work') }}</h2>
            <p class="text-xs text-tertiary">{{ __('Decisions you made') }}</p>
        </div>

        <div class="space-y-6 p-5">
            {{-- The week at a glance: the total, how it compares with last week, and a bar per day --}}
            <div>
                <h3 class="sr-only">{{ __('Your last 7 days') }}</h3>
                <div class="flex flex-wrap items-end justify-between gap-x-8 gap-y-4">
                    <div>
                        <p class="flex items-baseline gap-2">
                            <span class="font-tertiary text-4xl font-bold tabular-nums text-neutral-900">{{ $work['total'] }}</span>
                            @if ($work['total'] > 0 || $work['previous'] > 0)
                                <span @class(['rounded-full px-1.5 py-0.5 text-xs font-semibold tabular-nums', 'bg-green-50 text-green-700' => $delta >= 0, 'bg-red-50 text-red-700' => $delta < 0])>{{ $delta >= 0 ? '+' : '' }}{{ $delta }}</span>
                            @endif
                        </p>
                        <p class="mt-1 text-sm text-tertiary">{{ __('Your last 7 days') }}@if ($work['total'] > 0 || $work['previous'] > 0) · {{ __(':n the week before', ['n' => $work['previous']]) }}@endif</p>
                    </div>
                    <div class="flex h-20 min-w-[12rem] flex-1 items-end gap-2 sm:max-w-md" role="img" aria-label="{{ __('Decisions per day, oldest first') }}: {{ collect($work['daily'])->pluck('count')->implode(', ') }}">
                        @foreach ($work['daily'] as $day)
                            <div class="flex h-full flex-1 flex-col items-center justify-end gap-1" title="{{ $day['label'] }}: {{ $day['count'] }}">
                                <span class="text-[10px] font-medium tabular-nums {{ $day['count'] ? 'text-neutral-700' : 'text-transparent' }}">{{ $day['count'] }}</span>
                                <div @class(['w-full rounded-md', 'bg-teal-500' => $day['today'] && $day['count'], 'bg-teal-300/80' => ! $day['today'] && $day['count'], 'bg-neutral-100' => ! $day['count']]) style="height: {{ $day['count'] ? max(10, round($day['count'] / $max * 100 * 0.7)) : 6 }}%"></div>
                                <span @class(['text-[10px]', 'font-semibold text-neutral-900' => $day['today'], 'text-neutral-400' => ! $day['today']])>{{ mb_substr($day['label'], 0, 1) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                @if ($work['week'] === [])
                    <div class="mt-5 flex items-center gap-3 rounded-xl bg-neutral-50 px-4 py-3.5">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white text-neutral-400 ring-1 ring-neutral-200"><x-icon name="check-circle" class="h-5 w-5" /></span>
                        <p class="text-sm text-tertiary"><span class="font-medium text-neutral-700">{{ __('No decisions yet this week.') }}</span> {{ __('Models you publish, disputes you settle and members you help are counted here.') }}</p>
                    </div>
                @else
                    <ul class="mt-5 flex flex-wrap gap-2">
                        @foreach ($work['week'] as $area => $count)
                            <li class="flex items-center gap-2 rounded-full bg-neutral-50 py-1.5 pl-2 pr-3 ring-1 ring-neutral-200/70">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-teal-50 text-teal-700"><x-icon :name="$areas[$area][1] ?? 'clipboard-check'" class="h-3.5 w-3.5" /></span>
                                <span class="font-tertiary text-sm font-bold tabular-nums text-neutral-900">{{ $count }}</span>
                                <span class="text-xs text-tertiary">{{ trans_choice($areas[$area][0] ?? $area.'|'.$area, $count) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            @if ($work['disputes']->isNotEmpty())
                <div>
                    <h3 class="mb-2 text-xs font-semibold uppercase tracking-wider text-neutral-400">{{ __('Disputes you are handling') }}</h3>
                    <ul class="space-y-2">
                        @foreach ($work['disputes'] as $dispute)
                            @php
                                $days = (int) $dispute->created_at->diffInDays(now());
                                $amount = $dispute->cancellation->partial_payment_amount;
                                $tone = \App\Services\Admin\StaffDashboardService::tone($dispute->created_at);
                            @endphp
                            <li>
                                <a href="{{ route('admin.disputes.show', $dispute->cancellation_id) }}" wire:navigate class="group flex items-center gap-3 rounded-xl border border-neutral-200 px-3.5 py-3 transition-colors hover:border-teal-300 hover:bg-teal-50/40 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">
                                    <span @class(['flex h-9 w-9 shrink-0 items-center justify-center rounded-full', 'bg-red-50 text-red-700' => $tone === 'red', 'bg-amber-50 text-amber-700' => $tone === 'amber', 'bg-teal-50 text-teal-700' => $tone === 'neutral'])><x-icon name="scale" class="h-5 w-5" /></span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-semibold text-neutral-900">{{ $dispute->cancellation->engagement->application->job->title }}</span>
                                        <span class="block text-xs text-tertiary">{{ __('filed :when', ['when' => $dispute->created_at->diffForHumans()]) }}@if ($amount !== null) · {{ \App\Support\Money::format($amount, 0) }}@endif</span>
                                    </span>
                                    <span @class(['shrink-0 text-xs font-medium tabular-nums', 'text-red-700' => $tone === 'red', 'text-amber-700' => $tone === 'amber', 'text-tertiary' => $tone === 'neutral'])>{{ $days < 1 ? __('today') : trans_choice(':count day|:count days', $days, ['count' => $days]) }}</span>
                                    <x-icon name="chevron-right" class="h-4 w-4 shrink-0 text-neutral-300 transition-colors group-hover:text-teal-600" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($work['recent']->isNotEmpty())
                <div>
                    <h3 class="mb-2 text-xs font-semibold uppercase tracking-wider text-neutral-400">{{ __('What you did last') }}</h3>
                    <ol class="relative space-y-3 before:absolute before:bottom-2 before:left-[0.9rem] before:top-2 before:w-px before:bg-neutral-200">
                        @foreach ($work['recent'] as $entry)
                            @php $area = explode('.', $entry->action)[0]; @endphp
                            <li class="relative flex items-start gap-3">
                                <span class="relative z-10 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-white text-neutral-500 ring-1 ring-neutral-200"><x-icon :name="$areas[$area][1] ?? 'clipboard-check'" class="h-3.5 w-3.5" /></span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm text-neutral-800">{{ \Illuminate\Support\Str::limit($entry->summary, 110) }}</p>
                                    <p class="text-xs text-tertiary">{{ $entry->created_at->diffForHumans() }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endif
        </div>
    </x-card>
</section>
