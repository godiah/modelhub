@use('App\Enums\DisputeStatus')
@use('App\Enums\PartialPaymentStatus')
@php
    $application = $engagement->application;
    $job = $application->job;
    $cancellation = $engagement->cancellation;
    $resolved = $dispute?->isResolved() ?? false;
    $status = $dispute?->status ?? DisputeStatus::Pending;
    $filedBy = $dispute?->disputedBy;

    $steps = [
        ['label' => __('Dispute filed'), 'state' => 'done'],
        ['label' => __('Under review'), 'state' => $resolved ? 'done' : 'current'],
        ['label' => __('Resolved'), 'state' => $resolved ? 'done' : 'todo'],
    ];

    $paymentTone = ['pending' => 'amber', 'accepted' => 'green', 'disputed' => 'red', 'finalized' => 'blue'];
@endphp
<x-app-layout :crumb="__('Dispute') . ' · ' . $job->title">
    <x-slot name="toolbar">
        @can('view disputes')
            <x-btn variant="secondary" size="sm" href="{{ route('admin.disputes.index') }}">
                <x-icon name="scale" class="h-4 w-4" />
                {{ __('Disputed engagements') }}
            </x-btn>
        @endcan
    </x-slot>

    <div class="container mx-auto max-w-7xl px-4 py-8">
        <!-- Header -->
        <x-card class="mb-6 rounded-2xl">
            <div class="flex flex-col gap-4 p-6 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-medium uppercase tracking-wide text-tertiary">{{ __('Payment dispute') }}</p>
                    <div class="mt-1 flex flex-wrap items-center gap-3">
                        <h1 class="font-tertiary text-2xl font-semibold text-neutral-900">{{ $job->title }}</h1>
                        <x-badge :tone="$resolved ? 'green' : 'red'" class="px-2.5 py-0.5 text-xs font-medium">{{ $status->label() }}</x-badge>
                    </div>
                    <p class="mt-1.5 text-sm text-tertiary">
                        @if ($dispute)
                            {{ __('Filed :date', ['date' => $dispute->created_at->format('M j, Y · g:i A')]) }}
                            @if ($filedBy)
                                {{ __('by :name', ['name' => $filedBy->id === auth()->id() ? __('you') : $filedBy->name]) }}
                            @endif
                            @if ($resolved && $dispute->resolved_at)
                                · {{ __('Resolved :date', ['date' => $dispute->resolved_at->format('M j, Y')]) }}
                            @endif
                        @else
                            {{ __('No dispute has been filed for this engagement.') }}
                        @endif
                    </p>
                </div>
                <x-btn variant="secondary" size="sm" href="{{ route('engagements.show', $engagement) }}" class="shrink-0 self-start">
                    <x-icon name="arrow-left" class="h-4 w-4" />
                    {{ __('Back to engagement') }}
                </x-btn>
            </div>
            @if ($dispute)
                <x-engagement.stepper :steps="$steps" class="border-t border-neutral-100 px-6 py-5" />
            @endif
        </x-card>

        @if ($dispute)
            <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
                <div class="space-y-6">
                    <x-panel :title="__('The dispute')">
                        <dl class="space-y-5 text-sm">
                            <div>
                                <dt class="text-xs text-tertiary">{{ __('Reason') }}</dt>
                                <dd class="mt-1 font-medium text-neutral-900">{{ $dispute->formatted_reason }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-tertiary">{{ __('Description') }}</dt>
                                <dd class="mt-1 whitespace-pre-line break-words leading-relaxed text-neutral-700">{{ $dispute->dispute_details }}</dd>
                            </div>
                            @if ($dispute->supporting_evidence)
                                <div>
                                    <dt class="text-xs text-tertiary">{{ __('Supporting evidence') }}</dt>
                                    <dd class="mt-2">
                                        <ul class="flex flex-wrap gap-2">
                                            @foreach ($dispute->supporting_evidence as $index => $evidence)
                                                <li>
                                                    <a href="{{ route('engagements.disputes.download-evidence', [$dispute->id, $index]) }}"
                                                        class="inline-flex items-center gap-2 rounded-lg border border-neutral-200 bg-white px-3 py-1.5 text-xs font-medium text-neutral-700 transition-colors hover:border-neutral-300 hover:text-neutral-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">
                                                        <x-icon name="cloud-arrow-down" class="h-4 w-4 text-neutral-400" />
                                                        {{ __('Evidence :number', ['number' => $loop->iteration]) }}
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </dd>
                                </div>
                            @endif
                        </dl>
                    </x-panel>

                    @if ($partialPayment)
                        <x-panel :title="__('Payment under dispute')">
                            <dl class="grid grid-cols-1 gap-x-8 gap-y-4 text-sm sm:grid-cols-3">
                                <div>
                                    <dt class="text-xs text-tertiary">{{ __('Amount') }}</dt>
                                    <dd class="mt-1 font-tertiary text-lg font-semibold tabular-nums text-neutral-900"><x-money :amount="$partialPayment->amount" /></dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-tertiary">{{ __('Status') }}</dt>
                                    <dd class="mt-1.5"><x-badge :tone="$paymentTone[$partialPayment->status->value] ?? 'neutral'" class="px-2.5 py-0.5 text-xs font-medium">{{ $partialPayment->status->label() }}</x-badge></dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-tertiary">{{ __('Processed by') }}</dt>
                                    <dd class="mt-1 font-medium text-neutral-900">{{ $partialPayment->processor?->name ?? '—' }}</dd>
                                </div>
                            </dl>
                        </x-panel>
                    @endif

                    <!-- Admin resolution -->
                    @can('resolve disputes')
                        @unless ($resolved)
                            <x-panel :title="__('Resolve this dispute')" :description="__('Only administrators see this. The decision is final and notifies both parties.')">
                                <form action="{{ route('admin.disputes.resolve', $dispute->id) }}" method="POST" class="space-y-5">
                                    @csrf
                                    <x-field type="textarea" name="resolution_notes" error="resolution_notes" rows="4" required
                                        :label="__('Resolution notes')"
                                        :hint="__('Explain the decision and any actions taken.')">{{ old('resolution_notes') }}</x-field>
                                    <x-field type="number" name="resolution_amount" error="resolution_amount" step="0.01" min="0"
                                        :label="__('Final resolution amount (:currency, optional)', ['currency' => config('app.currency_symbol')])"
                                        :hint="__('Leave blank if no monetary resolution is required.')"
                                        value="{{ old('resolution_amount') }}" />
                                    <x-btn type="submit">
                                        <x-icon name="check-circle" class="h-4 w-4" />
                                        {{ __('Finalise resolution') }}
                                    </x-btn>
                                </form>
                            </x-panel>
                        @endunless
                    @endcan
                </div>

                <div class="space-y-6">
                    <x-panel :title="__('Status')">
                        @if ($resolved)
                            <div class="flex items-center gap-2 text-sm font-medium text-green-700">
                                <x-icon name="check-circle" class="h-5 w-5" />
                                {{ __('This dispute has been resolved.') }}
                            </div>
                            <dl class="mt-4 space-y-4 text-sm">
                                <div>
                                    <dt class="text-xs text-tertiary">{{ $dispute->resolution_amount ? __('Final resolution amount') : __('Agreed payable amount') }}</dt>
                                    <dd class="mt-1 font-tertiary text-lg font-semibold tabular-nums text-neutral-900"><x-money :amount="$dispute->resolution_amount ?: $engagement->net_amount" /></dd>
                                </div>
                                @if ($dispute->resolution_notes)
                                    <div>
                                        <dt class="text-xs text-tertiary">{{ __('Resolution notes') }}</dt>
                                        <dd class="mt-1 whitespace-pre-line break-words rounded-xl bg-neutral-50 px-3 py-2 text-neutral-700">{{ $dispute->resolution_notes }}</dd>
                                    </div>
                                @endif
                                @if ($dispute->resolved_at)
                                    <p class="border-t border-neutral-100 pt-3 text-xs text-tertiary">
                                        {{ __('Resolved :date', ['date' => $dispute->resolved_at->format('M j, Y · g:i A')]) }}
                                        @if ($dispute->resolvedBy)
                                            {{ __('by :name', ['name' => $dispute->resolvedBy->name]) }}
                                        @endif
                                    </p>
                                @endif
                            </dl>
                        @else
                            <div class="flex items-start gap-3 text-sm text-neutral-700">
                                <span class="mt-1.5 h-2 w-2 shrink-0 animate-pulse rounded-full bg-amber-500" aria-hidden="true"></span>
                                <p>{{ __('An administrator is reviewing this dispute. The engagement is frozen and the disputed amount stays in escrow until a decision is made.') }}</p>
                            </div>
                        @endif
                    </x-panel>

                    @if ($filedBy)
                        <x-panel :title="__('Filed by')">
                            <div class="flex items-center gap-3">
                                <x-user-avatar :user="$filedBy" size="h-10 w-10" />
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-neutral-900">{{ $filedBy->name }}</p>
                                    <p class="truncate text-xs text-tertiary">{{ $filedBy->id === $application->applicant_id ? __('Freelancer') : __('Client') }} · {{ $filedBy->email }}</p>
                                </div>
                            </div>
                        </x-panel>
                    @endif

                    <x-panel :title="__('Need help?')">
                        <p class="text-sm text-neutral-700">{{ __('Read how disputes are reviewed and what happens next.') }}</p>
                        <a href="{{ route('engagements.policy') }}" class="mt-3 inline-block text-sm font-medium text-teal-700 hover:text-teal-800 hover:underline">{{ __('Cancellation & payment policy') }}</a>
                    </x-panel>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
