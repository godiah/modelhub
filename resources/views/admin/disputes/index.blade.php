@php
    $pills = ['pending' => __('Pending'), 'under_review' => __('Under review'), 'resolved' => __('Resolved'), 'all' => __('All')];
    $tones = ['pending' => 'amber', 'under_review' => 'blue', 'resolved' => 'green'];
    $canResolve = auth()->user()->can('resolve disputes');
@endphp
<x-staff-layout title="Disputed engagements">
    <div class="container mx-auto max-w-7xl px-4 py-8">
        <x-staff.header :title="__('Disputed engagements')">{{ __('Freelancers dispute a payment decision after a project is cancelled. Take one on, read both sides and the evidence, then settle it with a final amount. The dispute that has waited longest is at the top.') }}</x-staff.header>

        <nav aria-label="{{ __('Filter by status') }}" class="-mx-4 mb-5 overflow-x-auto px-4 sm:mx-0 sm:px-0">
            <ul class="flex min-w-max items-center gap-2">
                @foreach ($pills as $key => $label)
                    @php $active = $status === $key; @endphp
                    <li>
                        <a href="{{ route('admin.disputes.index', ['status' => $key]) }}" @if ($active) aria-current="true" @endif
                            @class([
                                'inline-flex items-center gap-2 rounded-full border px-3.5 py-1.5 text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40',
                                'border-teal-600 bg-teal-600 text-white' => $active,
                                'border-neutral-200 bg-white text-neutral-700 hover:border-neutral-300 hover:bg-neutral-50' => ! $active,
                            ])>
                            {{ $label }}
                            <span @class(['text-xs tabular-nums', 'text-teal-100' => $active, 'text-tertiary' => ! $active])>{{ $counts[$key] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        @if ($disputes->isEmpty())
            <x-empty-state icon="shield-check" :title="__('Nothing here')" :description="match ($status) {
                'pending' => __('No disputes are waiting for someone to take them on.'),
                'under_review' => __('No disputes are being reviewed right now.'),
                'resolved' => __('No disputes have been resolved yet.'),
                default => __('No payment disputes have been filed.'),
            }" />
        @else
            <div class="space-y-4">
                @foreach ($disputes as $dispute)
                    @php
                        $engagement = $dispute->cancellation->engagement;
                        $application = $engagement->application;
                        $resolved = $dispute->isResolved();
                        $mine = $dispute->admin_assigned === auth()->id();
                        $filedAs = $dispute->disputed_by === $application->applicant_id ? __('freelancer') : __('client');
                        $waitingDays = $resolved ? 0 : (int) $dispute->created_at->diffInDays(now());
                        $openUrl = route('admin.disputes.show', $dispute->cancellation_id);
                    @endphp
                    <article class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm sm:p-6">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="font-tertiary text-lg font-semibold text-neutral-900"><a href="{{ $openUrl }}" class="rounded hover:text-teal-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">{{ $application->job->title }}</a></h2>
                                    <x-badge :tone="$tones[$dispute->status->value]" class="px-2.5 py-0.5 text-xs font-medium">{{ __($dispute->status->label()) }}</x-badge>
                                </div>
                                <p class="mt-1 text-sm text-tertiary">
                                    {{ __('Filed by :name (:role)', ['name' => $dispute->disputedBy?->name ?? __('a deleted account'), 'role' => $filedAs]) }}
                                    · {{ $dispute->created_at->format('M j, Y') }}
                                    @unless ($resolved)
                                        · <span @class(['font-medium text-amber-700' => $waitingDays >= 3])>{{ $waitingDays < 1 ? __('waiting less than a day') : trans_choice('waiting :count day|waiting :count days', $waitingDays, ['count' => $waitingDays]) }}</span>
                                    @endunless
                                </p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                @if ($canResolve && ! $resolved && ! $dispute->admin_assigned)
                                    <form method="POST" action="{{ route('admin.disputes.assign', $dispute) }}">@csrf<x-btn size="sm" type="submit">{{ __('Assign to me') }}</x-btn></form>
                                @endif
                                <x-btn size="sm" variant="secondary" href="{{ $openUrl }}">{{ $resolved ? __('View') : __('Open dispute') }}<x-icon name="arrow-right" class="h-4 w-4" /></x-btn>
                            </div>
                        </div>

                        <dl class="mt-4 grid grid-cols-1 gap-x-8 gap-y-3 text-sm sm:grid-cols-3">
                            <div>
                                <dt class="text-xs text-tertiary">{{ __('Reason') }}</dt>
                                <dd class="mt-0.5 font-medium text-neutral-900">{{ $dispute->formatted_reason }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-tertiary">{{ __('Between') }}</dt>
                                <dd class="mt-0.5 text-neutral-900"><span class="font-medium">{{ $application->poster?->name ?? '—' }}</span> <span class="text-tertiary">{{ __('(client)') }}</span><br><span class="font-medium">{{ $application->applicant?->name ?? '—' }}</span> <span class="text-tertiary">{{ __('(freelancer)') }}</span></dd>
                            </div>
                            <div>
                                <dt class="text-xs text-tertiary">{{ $resolved ? __('Settled at') : __('Amount in question') }}</dt>
                                <dd class="mt-0.5 font-tertiary font-semibold tabular-nums text-neutral-900">
                                    @php $amount = $resolved ? ($dispute->resolution_amount ?? $dispute->cancellation->partial_payment_amount) : $dispute->cancellation->partial_payment_amount; @endphp
                                    @if ($amount !== null)<x-money :amount="$amount" />@else<span class="font-normal text-tertiary">—</span>@endif
                                </dd>
                            </div>
                        </dl>

                        @if (filled($dispute->dispute_details))
                            <p class="mt-4 line-clamp-2 whitespace-pre-line break-words rounded-xl bg-neutral-50 px-4 py-3 text-sm text-neutral-700">{{ $dispute->dispute_details }}</p>
                        @endif

                        <p class="mt-4 flex flex-wrap items-center gap-x-2 border-t border-neutral-100 pt-3 text-xs text-tertiary">
                            @if ($resolved)
                                <x-icon name="check-circle" class="h-4 w-4 text-green-600" />
                                {{ __('Resolved :date by :name', ['date' => $dispute->resolved_at?->format('M j, Y'), 'name' => $dispute->resolvedBy?->name ?? __('a reviewer')]) }}
                            @elseif ($dispute->assignedAdmin)
                                <x-icon name="shield-check" class="h-4 w-4 text-teal-600" />
                                {{ $mine ? __('Assigned to you') : __('Assigned to :name', ['name' => $dispute->assignedAdmin->name]) }}
                            @else
                                <x-icon name="user" class="h-4 w-4" />
                                {{ __('Not assigned to anyone yet') }}
                            @endif
                        </p>
                    </article>
                @endforeach
            </div>
            <x-pager :paginator="$disputes" />
        @endif
    </div>
</x-staff-layout>
