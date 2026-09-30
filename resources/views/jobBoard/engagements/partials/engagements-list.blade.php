@use('App\Enums\EngagementStatus')
@if ($engagements->isEmpty() && $hasFilters)
    <x-empty-state icon="chat-bubble-text" title="No Engagements Found" description="We couldn't find any engagements matching your current search or filter.">
        <x-btn variant="secondary" href="#" id="clearEngagementFilters">
            <x-icon name="arrow-path" class="w-4 h-4" />
            Clear All Filters
        </x-btn>
    </x-empty-state>
@else
    <div class="space-y-4">
        @foreach ($engagements as $engagement)
            @php
                $summary = $summaries[$engagement->id];
                $actions = $actionSets[$engagement->id];
                $status = $engagement->status;
            @endphp

            <x-card class="rounded-2xl transition-shadow duration-200 hover:shadow-md">
                <div class="flex flex-col gap-4 p-5 lg:flex-row lg:items-center lg:gap-6">
                    <!-- Identity: opens the workspace -->
                    <a href="{{ $actions['workspace_url'] }}" wire:navigate
                        class="flex min-w-0 flex-1 items-start gap-4 rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">
                        <span @class([
                            'flex h-11 w-11 shrink-0 items-center justify-center rounded-xl',
                            'bg-teal-50 text-teal-700' => $summary['role'] === 'freelancer',
                            'bg-blue-50 text-blue-700' => $summary['role'] === 'client',
                        ])>
                            <x-icon :name="$summary['role'] === 'freelancer' ? 'briefcase' : 'users'" class="h-5 w-5" />
                        </span>

                        <span class="min-w-0 flex-1">
                            <span class="flex flex-wrap items-center gap-2">
                                <span class="truncate font-tertiary text-base font-semibold text-neutral-900">{{ $engagement->application->job->title }}</span>
                                <x-engagement.status-badge :status="$status" class="px-2.5 py-0.5 text-xs font-medium" icon-class="w-3 h-3 mr-1" />
                            </span>
                            <span class="mt-1 block truncate text-sm text-tertiary">
                                <span class="font-medium text-neutral-700">{{ $summary['role'] === 'freelancer' ? __('Freelancer') : __('Client') }}</span>
                                @if ($summary['counterpart'])
                                    · {{ __('with :name', ['name' => $summary['counterpart']]) }}
                                @endif
                                @if ($engagement->started_at)
                                    · {{ __('Started :date', ['date' => $engagement->started_at->format('M j')]) }}
                                @endif
                            </span>

                            @if ($summary['to_review'] > 0 || $summary['to_revise'] > 0 || $summary['overdue'] || $summary['unread_messages'] > 0 || ($actions['is_poster'] && $status === EngagementStatus::EmployerAccepted))
                                <span class="mt-2 flex flex-wrap gap-2">
                                    @if ($summary['to_review'] > 0)
                                        <x-badge tone="amber" class="px-2.5 py-0.5 text-xs font-medium">{{ trans_choice(':count to review|:count to review', $summary['to_review'], ['count' => $summary['to_review']]) }}</x-badge>
                                    @endif
                                    @if ($summary['to_revise'] > 0)
                                        <x-badge tone="red" class="px-2.5 py-0.5 text-xs font-medium">{{ trans_choice(':count to revise|:count to revise', $summary['to_revise'], ['count' => $summary['to_revise']]) }}</x-badge>
                                    @endif
                                    @if ($summary['overdue'])
                                        <x-badge tone="red" class="px-2.5 py-0.5 text-xs font-medium">{{ __('Overdue') }}</x-badge>
                                    @endif
                                    @if ($summary['unread_messages'] > 0)
                                        <x-badge tone="blue" class="px-2.5 py-0.5 text-xs font-medium">{{ trans_choice(':count new message|:count new messages', $summary['unread_messages'], ['count' => $summary['unread_messages']]) }}</x-badge>
                                    @endif
                                    @if ($actions['is_poster'] && $status === EngagementStatus::EmployerAccepted)
                                        <x-badge tone="neutral" class="px-2.5 py-0.5 text-xs font-medium">{{ __('Waiting for the freelancer') }}</x-badge>
                                    @endif
                                </span>
                            @endif
                        </span>
                    </a>

                    <!-- Progress and next deadline -->
                    <div class="lg:w-56 lg:shrink-0">
                        <div class="h-2 overflow-hidden rounded-full bg-neutral-100" role="progressbar"
                            aria-label="{{ __('Deliverables approved') }}" aria-valuemin="0" aria-valuemax="100"
                            aria-valuenow="{{ $summary['percent'] }}">
                            <div class="h-full rounded-full bg-teal-600" style="width: {{ $summary['percent'] }}%"></div>
                        </div>
                        <p class="mt-1.5 flex items-center justify-between gap-2 text-xs text-tertiary">
                            <span>
                                @if ($summary['total'] > 0)
                                    {{ __(':approved of :total approved', ['approved' => $summary['approved'], 'total' => $summary['total']]) }}
                                @else
                                    {{ __('No deliverables yet') }}
                                @endif
                            </span>
                            @if ($summary['next_due'])
                                <span @class(['font-medium', 'text-red-600' => $summary['overdue']])>
                                    {{ $summary['overdue'] ? __('Was due') : __('Due') }} {{ $summary['next_due']->format('M j') }}
                                </span>
                            @endif
                        </p>
                    </div>

                    <!-- Amount -->
                    <div class="lg:w-32 lg:shrink-0 lg:text-right">
                        <p class="text-xs text-tertiary">{{ __($summary['amount_label']) }}</p>
                        <p class="font-tertiary text-base font-semibold tabular-nums text-neutral-900"><x-money :amount="$summary['amount']" :decimals="0" /></p>
                    </div>

                    <x-engagement.actions :engagement="$engagement" :summary="$summary" :actions="$actions" class="lg:w-64 lg:justify-end" />
                </div>

                @if ($actions['can_cancel'])
                    @include('jobBoard.engagements.partials.components.modals.cancellation')
                @endif
            </x-card>
        @endforeach

        {{-- Single-instance modals shared by every row --}}
        @include('jobBoard.engagements.partials.components.modals.archive')
        @include('jobBoard.engagements.partials.components.modals.review')
    </div>
@endif
