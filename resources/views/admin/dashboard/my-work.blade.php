@php
    $labels = ['model' => 'model|models', 'seller' => 'store|stores', 'review' => 'review|reviews', 'dispute' => 'dispute|disputes', 'project' => 'project|projects', 'member' => 'member|members', 'role' => 'role|roles'];
@endphp
<section aria-labelledby="work-heading">
    <x-card clip>
        <div class="border-b border-neutral-100 px-5 py-4"><h2 id="work-heading" class="font-tertiary text-base font-semibold text-neutral-900">{{ __('Your work') }}</h2></div>
        <div class="space-y-5 p-5">
            @if ($work['disputes']->isNotEmpty())
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-neutral-400">{{ __('Disputes you are handling') }}</h3>
                    <ul class="mt-2 divide-y divide-neutral-100">
                        @foreach ($work['disputes'] as $dispute)
                            <li><a href="{{ route('admin.disputes.show', $dispute->cancellation_id) }}" class="flex items-center justify-between gap-3 py-2.5 text-sm hover:text-teal-700"><span class="min-w-0 truncate font-medium text-neutral-900">{{ $dispute->cancellation->engagement->application->job->title }}</span><span class="shrink-0 text-xs text-tertiary">{{ __('filed :when', ['when' => $dispute->created_at->diffForHumans()]) }}</span></a></li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wider text-neutral-400">{{ __('Your last 7 days') }}</h3>
                @if ($work['week'] === [])
                    <p class="mt-2 text-sm text-tertiary">{{ __('No decisions yet this week.') }}</p>
                @else
                    <ul class="mt-2 flex flex-wrap gap-2">
                        @foreach ($work['week'] as $area => $count)
                            <li class="rounded-xl bg-neutral-50 px-3 py-2"><span class="font-tertiary text-lg font-bold tabular-nums text-neutral-900">{{ $count }}</span> <span class="text-xs text-tertiary">{{ trans_choice($labels[$area] ?? $area.'|'.$area, $count) }}</span></li>
                        @endforeach
                    </ul>
                @endif
            </div>

            @if ($work['recent']->isNotEmpty())
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-neutral-400">{{ __('What you did last') }}</h3>
                    <ul class="mt-2 space-y-1.5">
                        @foreach ($work['recent'] as $entry)
                            <li class="flex items-baseline justify-between gap-3 text-sm"><span class="min-w-0 truncate text-neutral-700">{{ $entry->summary }}</span><span class="shrink-0 text-xs text-tertiary">{{ $entry->created_at->diffForHumans() }}</span></li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </x-card>
</section>
