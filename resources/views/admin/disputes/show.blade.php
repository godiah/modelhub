@use('App\Enums\DisputeStatus')
@php
    $cancellation = $dispute->cancellation;
    $engagement = $cancellation->engagement;
    $application = $engagement->application;
    $resolved = $dispute->isResolved();
    $mine = $dispute->admin_assigned === auth()->id();
    $canResolve = auth()->user()->can('resolve disputes');
    $filedAs = $dispute->disputed_by === $application->applicant_id ? __('Freelancer') : __('Client');
    $tones = ['pending' => 'amber', 'under_review' => 'blue', 'resolved' => 'green'];
    $deliverables = $engagement->deliverables;
@endphp
<x-staff-layout :title="__('Dispute') . ' · ' . $application->job->title">
    <div class="container mx-auto max-w-7xl px-4 py-8">
        <a href="{{ route('admin.disputes.index') }}" class="mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-teal-700 hover:underline"><x-icon name="arrow-left" class="h-4 w-4" />{{ __('All disputes') }}</a>

        <x-card class="mb-6 rounded-2xl">
            <div class="flex flex-col gap-4 p-6 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-medium uppercase tracking-wide text-tertiary">{{ __('Payment dispute') }} · #{{ $dispute->id }}</p>
                    <div class="mt-1 flex flex-wrap items-center gap-3">
                        <h1 class="font-tertiary text-2xl font-semibold text-neutral-900">{{ $application->job->title }}</h1>
                        <x-badge :tone="$tones[$dispute->status->value]" class="px-2.5 py-0.5 text-xs font-medium">{{ __($dispute->status->label()) }}</x-badge>
                    </div>
                    <p class="mt-1.5 text-sm text-tertiary">
                        {{ __('Filed :date by :name (:role)', ['date' => $dispute->created_at->format('M j, Y · g:i A'), 'name' => $dispute->disputedBy?->name ?? __('a deleted account'), 'role' => strtolower($filedAs)]) }}
                        @if ($resolved && $dispute->resolved_at) · {{ __('Resolved :date', ['date' => $dispute->resolved_at->format('M j, Y')]) }}@endif
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @if ($canResolve && ! $resolved && ! $dispute->admin_assigned)
                        <form method="POST" action="{{ route('admin.disputes.assign', $dispute) }}">@csrf<x-btn type="submit">{{ __('Assign to me') }}</x-btn></form>
                    @endif
                </div>
            </div>
        </x-card>

        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
            <div class="space-y-6">
                <x-panel :title="__('The dispute')">
                    <dl class="space-y-5 text-sm">
                        <div><dt class="text-xs text-tertiary">{{ __('Reason') }}</dt><dd class="mt-1 font-medium text-neutral-900">{{ $dispute->formatted_reason }}</dd></div>
                        <div><dt class="text-xs text-tertiary">{{ __('Description') }}</dt><dd class="mt-1 whitespace-pre-line break-words leading-relaxed text-neutral-700">{{ $dispute->dispute_details }}</dd></div>
                        @if ($dispute->supporting_evidence)
                            <div>
                                <dt class="text-xs text-tertiary">{{ __('Supporting evidence') }}</dt>
                                <dd class="mt-2"><ul class="flex flex-wrap gap-2">
                                    @foreach ($dispute->supporting_evidence as $index => $evidence)
                                        <li><a href="{{ route('admin.disputes.evidence', [$dispute, $index]) }}" class="inline-flex items-center gap-2 rounded-lg border border-neutral-200 bg-white px-3 py-1.5 text-xs font-medium text-neutral-700 transition-colors hover:border-neutral-300 hover:bg-neutral-50"><x-icon name="cloud-arrow-down" class="h-4 w-4 text-neutral-400" />{{ __('Evidence :number', ['number' => $loop->iteration]) }}</a></li>
                                    @endforeach
                                </ul></dd>
                            </div>
                        @endif
                    </dl>
                </x-panel>

                <x-panel :title="__('How it got here')" :description="__('The cancellation that led to this dispute.')">
                    <dl class="grid grid-cols-1 gap-x-8 gap-y-4 text-sm sm:grid-cols-2">
                        <div><dt class="text-xs text-tertiary">{{ __('Cancelled by') }}</dt><dd class="mt-1 font-medium text-neutral-900">{{ $cancellation->initiator?->name ?? '—' }}</dd></div>
                        <div><dt class="text-xs text-tertiary">{{ __('Reason') }}</dt><dd class="mt-1 text-neutral-900">{{ ucwords(str_replace('_', ' ', (string) $cancellation->reason_category)) }}</dd></div>
                        @if (filled($cancellation->reason_details))<div class="sm:col-span-2"><dt class="text-xs text-tertiary">{{ __('Details') }}</dt><dd class="mt-1 whitespace-pre-line break-words text-neutral-700">{{ $cancellation->reason_details }}</dd></div>@endif
                        <div><dt class="text-xs text-tertiary">{{ __('Agreed amount') }}</dt><dd class="mt-1 font-tertiary text-lg font-semibold tabular-nums text-neutral-900"><x-money :amount="$engagement->net_amount" /></dd></div>
                        <div><dt class="text-xs text-tertiary">{{ __('Deliverables approved') }}</dt><dd class="mt-1 text-neutral-900">{{ $deliverables->where('status', 'approved')->count() }} / {{ $deliverables->count() }}</dd></div>
                    </dl>
                </x-panel>

                @if ($dispute->partialPayment)
                    @php $payment = $dispute->partialPayment; @endphp
                    <x-panel :title="__('Payment under dispute')">
                        <dl class="grid grid-cols-1 gap-x-8 gap-y-4 text-sm sm:grid-cols-3">
                            <div><dt class="text-xs text-tertiary">{{ __('Amount') }}</dt><dd class="mt-1 font-tertiary text-lg font-semibold tabular-nums text-neutral-900"><x-money :amount="$payment->amount" /></dd></div>
                            <div><dt class="text-xs text-tertiary">{{ __('Status') }}</dt><dd class="mt-1.5"><x-badge tone="neutral" class="px-2.5 py-0.5 text-xs font-medium">{{ $payment->status->label() }}</x-badge></dd></div>
                            <div><dt class="text-xs text-tertiary">{{ __('Processed by') }}</dt><dd class="mt-1 font-medium text-neutral-900">{{ $payment->processor?->name ?? '—' }}</dd></div>
                        </dl>
                    </x-panel>
                @endif

                @if ($messages !== null)
                    <x-panel :title="__('Conversation')" :description="__('The messages between the two people on this hire. Private: you can read them because of this dispute, and that is recorded in the activity log.')">
                        @forelse ($messages as $message)
                            <div class="flex items-start gap-3 border-b border-neutral-100 py-3 last:border-0">
                                @if ($message->sender)<x-user-avatar :user="$message->sender" size="h-8 w-8" />@endif
                                <div class="min-w-0"><p class="text-xs text-tertiary"><span class="font-medium text-neutral-900">{{ $message->sender?->name ?? __('Deleted account') }}</span> · {{ $message->created_at->format('M j, g:i A') }}</p><p class="mt-0.5 whitespace-pre-line break-words text-sm text-neutral-800">{{ $message->content }}</p></div>
                            </div>
                        @empty<p class="text-sm text-tertiary">{{ __('They never exchanged a message.') }}</p>@endforelse
                    </x-panel>
                @endif

                @if ($canResolve && ! $resolved)
                    <x-panel :title="__('Resolve this dispute')" :description="__('The decision is final and notifies both parties.')">
                        <form action="{{ route('admin.disputes.resolve', $dispute) }}" method="POST" class="space-y-5">
                            @csrf
                            <x-field type="textarea" name="resolution_notes" error="resolution_notes" rows="4" required :label="__('Resolution notes')" :hint="__('Explain the decision and any actions taken.')">{{ old('resolution_notes') }}</x-field>
                            <x-field type="number" name="resolution_amount" error="resolution_amount" step="0.01" min="0"
                                :label="__('Final resolution amount (:currency, optional)', ['currency' => config('app.currency_symbol')])" :hint="__('Leave blank if no monetary resolution is required.')" value="{{ old('resolution_amount') }}" />
                            <x-btn type="submit"><x-icon name="check-circle" class="h-4 w-4" />{{ __('Finalise resolution') }}</x-btn>
                        </form>
                    </x-panel>
                @endif
            </div>

            <div class="space-y-6">
                <x-panel :title="__('Handling')">
                    @if ($resolved)
                        <p class="flex items-center gap-2 text-sm font-medium text-green-700"><x-icon name="check-circle" class="h-5 w-5" />{{ __('Resolved') }}</p>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div><dt class="text-xs text-tertiary">{{ __('Resolved by') }}</dt><dd class="mt-1 font-medium text-neutral-900">{{ $dispute->resolvedBy?->name ?? __('a reviewer') }}</dd></div>
                            <div><dt class="text-xs text-tertiary">{{ $dispute->resolution_amount ? __('Final amount') : __('Agreed amount') }}</dt><dd class="mt-1 font-tertiary text-lg font-semibold tabular-nums text-neutral-900"><x-money :amount="$dispute->resolution_amount ?: $engagement->net_amount" /></dd></div>
                            @if ($dispute->resolution_notes)<div><dt class="text-xs text-tertiary">{{ __('Notes') }}</dt><dd class="mt-1 whitespace-pre-line break-words rounded-xl bg-neutral-50 px-3 py-2 text-neutral-700">{{ $dispute->resolution_notes }}</dd></div>@endif
                        </dl>
                    @elseif ($dispute->assignedAdmin)
                        <p class="text-sm text-neutral-700">{{ $mine ? __('You are handling this dispute.') : __(':name is handling this dispute.', ['name' => $dispute->assignedAdmin->name]) }}</p>
                    @else
                        <p class="text-sm text-neutral-700">{{ __('Nobody has taken this on yet.') }}</p>
                    @endif
                </x-panel>

                <x-panel :title="__('The people involved')">
                    <ul class="space-y-4">
                        @foreach ([[__('Client'), $application->poster], [__('Freelancer'), $application->applicant]] as [$role, $person])
                            <li class="flex items-center gap-3">
                                @if ($person)<x-user-avatar :user="$person" size="h-10 w-10" />@endif
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-neutral-900">{{ $person?->name ?? __('Deleted account') }}</p>
                                    <p class="truncate text-xs text-tertiary">{{ $role }}@if ($person) · {{ $person->email }}@endif</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </x-panel>
            </div>
        </div>
    </div>
</x-staff-layout>
