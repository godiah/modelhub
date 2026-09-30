@use('App\Enums\PartialPaymentStatus')
@use('App\Enums\EngagementStatus')
@php
    $application = $engagement->application;
    $job = $application->job;
    $cancellation = $engagement->cancellation;
    $user = auth()->user();
    $isPoster = $user->id === $application->poster_id;
    $isApplicant = $user->id === $application->applicant_id;
    $canProcessAsClient = $isPoster || $user->hasRole('admin');
    $toReview = $engagement->deliverables->where('status', 'submitted');
    $approved = $engagement->getCompletedDeliverablesCount();
    $total = $engagement->getTotalDeliverablesCount();
    $paymentStatus = $payment?->status;
    $cancelledAt = $engagement->cancelled_at ?? $cancellation?->created_at;

    $steps = [
        ['label' => __('Review submitted work'), 'state' => $toReview->isEmpty() ? 'done' : 'current'],
        ['label' => __('Client processes payment'), 'state' => $payment ? 'done' : ($toReview->isEmpty() ? 'current' : 'todo')],
        ['label' => __('Freelancer responds'), 'state' => match (true) {
            in_array($paymentStatus, [PartialPaymentStatus::Accepted, PartialPaymentStatus::Finalized], true) => 'done',
            $paymentStatus === PartialPaymentStatus::Disputed => 'failed',
            $paymentStatus === PartialPaymentStatus::Pending => 'current',
            default => 'todo',
        }],
        ['label' => __('Settled'), 'state' => ($paymentStatus === PartialPaymentStatus::Finalized || $engagement->status === EngagementStatus::Settled) ? 'done' : 'todo'],
    ];

    $statusTone = ['pending' => 'amber', 'accepted' => 'green', 'disputed' => 'red', 'finalized' => 'blue'];
    $deliverableTone = ['pending' => 'neutral', 'submitted' => 'amber', 'approved' => 'green', 'rejected' => 'red'];
    $deliverableLabel = ['pending' => __('Not submitted'), 'submitted' => __('Awaiting review'), 'approved' => __('Approved'), 'rejected' => __('Changes requested')];
@endphp
<x-app-layout :crumb="__('Settlement') . ' · ' . $job->title">
    <div class="container mx-auto max-w-7xl px-4 py-8">
        <!-- Header -->
        <x-card class="mb-6 rounded-2xl">
            <div class="flex flex-col gap-4 p-6 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-medium uppercase tracking-wide text-tertiary">{{ __('Settlement') }}</p>
                    <div class="mt-1 flex flex-wrap items-center gap-3">
                        <h1 class="font-tertiary text-2xl font-semibold text-neutral-900">{{ $job->title }}</h1>
                        <x-engagement.status-badge :status="$engagement->status" class="px-2.5 py-0.5 text-xs font-medium" icon-class="w-3 h-3 mr-1" />
                    </div>
                    <p class="mt-1.5 text-sm text-tertiary">
                        @if ($cancellation?->initiator)
                            {{ __('Cancelled by :name', ['name' => $cancellation->initiator_id === $user->id ? __('you') : $cancellation->initiator->name]) }}
                        @else
                            {{ __('Cancelled') }}
                        @endif
                        @if ($cancelledAt)
                            · <x-date :date="$cancelledAt" />
                        @endif
                    </p>
                </div>
                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    <x-btn variant="secondary" size="sm" href="{{ route('engagements.show', $engagement) }}">
                        <x-icon name="arrow-left" class="h-4 w-4" />
                        {{ __('Back to engagement') }}
                    </x-btn>
                    @if ($canReopen)
                        <form action="{{ route('engagements.reopen-job', $engagement) }}" method="POST">
                            @csrf
                            <x-btn size="sm" type="submit">
                                <x-icon name="arrow-path" class="h-4 w-4" />
                                {{ __('Reopen job') }}
                            </x-btn>
                        </form>
                    @endif
                </div>
            </div>
            <x-engagement.stepper :steps="$steps" class="border-t border-neutral-100 px-6 py-5" />
        </x-card>

        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
            <div class="space-y-6">
                <!-- Payment -->
                <x-panel :title="__('Payment')" :description="__('Payment for the work approved before the engagement ended.')">
                    <dl class="divide-y divide-neutral-100 text-sm">
                        <div class="flex items-center justify-between gap-4 py-2.5 first:pt-0">
                            <dt class="text-tertiary">{{ __('Agreed amount') }}</dt>
                            <dd class="font-medium tabular-nums text-neutral-900"><x-money :amount="$engagement->agreed_amount" /></dd>
                        </div>
                        <div class="flex items-center justify-between gap-4 py-2.5">
                            <dt class="text-tertiary">{{ __('Approved deliverables') }}</dt>
                            <dd class="font-medium text-neutral-900">{{ __(':approved of :total', ['approved' => $approved, 'total' => $total]) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4 py-2.5 last:pb-0">
                            <dt class="text-tertiary">{{ __('Calculated payable amount') }}</dt>
                            <dd class="font-tertiary text-lg font-semibold tabular-nums text-neutral-900"><x-money :amount="$engagement->calculatePartialPaymentAmount()" /></dd>
                        </div>
                    </dl>

                    <div class="mt-6 border-t border-neutral-100 pt-6">
                        @if ($payment)
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p class="text-xs text-tertiary">{{ __('Processed payment') }}</p>
                                    <p class="font-tertiary text-xl font-semibold tabular-nums text-neutral-900"><x-money :amount="$payment->amount" /></p>
                                </div>
                                <x-badge :tone="$statusTone[$payment->status->value] ?? 'neutral'" class="px-2.5 py-0.5 text-xs font-medium">{{ $payment->status->label() }}</x-badge>
                            </div>
                            <p class="mt-3 text-sm text-neutral-700">
                                @if ($isPoster)
                                    {{ match ($paymentStatus) {
                                        PartialPaymentStatus::Pending => __('Payment processed. Waiting for the freelancer to respond.'),
                                        PartialPaymentStatus::Accepted => __('The freelancer accepted this payment.'),
                                        PartialPaymentStatus::Disputed => __('The freelancer disputed this payment.'),
                                        PartialPaymentStatus::Finalized => __('This payment has been finalised.'),
                                        default => '',
                                    } }}
                                @else
                                    {{ match ($paymentStatus) {
                                        PartialPaymentStatus::Pending => __('The client has processed this payment. Please review and respond.'),
                                        PartialPaymentStatus::Accepted => __('You accepted this payment.'),
                                        PartialPaymentStatus::Disputed => __('You disputed this payment.'),
                                        PartialPaymentStatus::Finalized => __('This payment has been finalised.'),
                                        default => '',
                                    } }}
                                @endif
                            </p>
                            <p class="mt-1 text-xs text-tertiary">
                                @if ($payment->processed_at)
                                    {{ __('Processed :date', ['date' => $payment->processed_at->format('M j, Y')]) }}
                                @endif
                                @if ($payment->accepted_at)
                                    · {{ __('Accepted :date', ['date' => $payment->accepted_at->format('M j, Y')]) }}
                                @endif
                                @if ($payment->final_amount !== null)
                                    · {{ __('Final amount :amount', ['amount' => \App\Support\Money::format($payment->final_amount)]) }}
                                @endif
                            </p>

                            @if ($isApplicant && $paymentStatus === PartialPaymentStatus::Pending)
                                <div class="mt-5 flex flex-wrap gap-2">
                                    <form action="{{ route('engagements.accept-partial-payment', $payment->id) }}" method="POST">
                                        @csrf
                                        <x-btn type="submit">
                                            <x-icon name="check" class="h-4 w-4" />
                                            {{ __('Accept payment') }}
                                        </x-btn>
                                    </form>
                                    <x-btn variant="danger-outline" type="button" @click="$dispatch('open-modal', 'dispute-warning')">
                                        <x-icon name="exclamation-triangle" class="h-4 w-4" />
                                        {{ __('Dispute payment') }}
                                    </x-btn>
                                </div>
                            @endif
                        @elseif ($canProcess && $canProcessAsClient)
                            <p class="mb-4 text-sm text-neutral-700">{{ $paymentInfo['message'] }}</p>
                            <form action="{{ route('engagements.process-partial-payment', $engagement->id) }}" method="POST" class="max-w-sm space-y-4">
                                @csrf
                                <x-field name="payment_amount" error="payment_amount" type="number" step="0.01" min="0.01"
                                    :label="__('Payment amount (:currency)', ['currency' => config('app.currency_symbol')])"
                                    :hint="__('Leave blank to pay the calculated amount of :amount.', ['amount' => \App\Support\Money::format($engagement->calculatePartialPaymentAmount())])"
                                    value="{{ old('payment_amount') }}" />
                                <x-btn type="submit">
                                    <x-icon name="banknotes" class="h-4 w-4" />
                                    {{ __('Process payment') }}
                                </x-btn>
                            </form>
                        @elseif ($canProcess && $isApplicant)
                            <p class="text-sm text-neutral-700">{{ __('The client has not processed the payment yet.') }}</p>
                        @else
                            <p class="text-sm text-neutral-700">{{ $paymentInfo['message'] }}</p>
                        @endif
                    </div>
                </x-panel>

                <!-- Deliverables -->
                <x-panel :title="__('Deliverables')" :description="$toReview->isNotEmpty() && $isPoster ? __('Review the submitted work before the payment can be processed.') : null">
                    @if ($engagement->deliverables->isEmpty())
                        <p class="text-sm text-tertiary">{{ __('No deliverables were added to this engagement.') }}</p>
                    @else
                        <ul class="divide-y divide-neutral-100">
                            @foreach ($engagement->deliverables as $deliverable)
                                <li class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-neutral-900">{{ $deliverable->title }}</p>
                                        @if ($deliverable->due_date)
                                            <p class="text-xs text-tertiary">{{ __('Due :date', ['date' => $deliverable->due_date->format('M j, Y')]) }}</p>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <x-badge :tone="$deliverableTone[$deliverable->status] ?? 'neutral'" class="px-2.5 py-0.5 text-xs font-medium">{{ $deliverableLabel[$deliverable->status] ?? \Illuminate\Support\Str::headline($deliverable->status) }}</x-badge>
                                        @if ($isPoster && $deliverable->status === 'submitted')
                                            <x-btn size="sm" variant="secondary" type="button" @click="$dispatch('open-modal', 'reject-deliverable-{{ $deliverable->id }}')">{{ __('Request changes') }}</x-btn>
                                            <x-btn size="sm" type="button" @click="$dispatch('open-modal', 'approve-deliverable-{{ $deliverable->id }}')">{{ __('Approve') }}</x-btn>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    <p class="mt-4 border-t border-neutral-100 pt-4 text-sm">
                        <a href="{{ route('engagements.show', $engagement) }}#deliverables" class="font-medium text-teal-700 hover:text-teal-800 hover:underline">{{ __('See submissions and feedback') }}</a>
                    </p>
                </x-panel>
            </div>

            <div class="space-y-6">
                <x-panel :title="__('Cancellation')">
                    @if ($cancellation)
                        <dl class="space-y-4 text-sm">
                            <div>
                                <dt class="text-xs text-tertiary">{{ __('Ended by') }}</dt>
                                <dd class="mt-1 flex items-center gap-2.5">
                                    @if ($cancellation->initiator)
                                        <x-user-avatar :user="$cancellation->initiator" size="h-8 w-8" />
                                        <span class="font-medium text-neutral-900">{{ $cancellation->initiator->name }}</span>
                                        <x-badge tone="neutral" class="px-2 py-0.5 text-xs font-medium">{{ $cancellation->initiator_id === $application->poster_id ? __('Client') : __('Freelancer') }}</x-badge>
                                    @else
                                        <span class="text-neutral-700">{{ $cancellation->type_label }}</span>
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-tertiary">{{ __('Type') }}</dt>
                                <dd class="mt-1 font-medium text-neutral-900">{{ $cancellation->type_label }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-tertiary">{{ __('Reason') }}</dt>
                                <dd class="mt-1 font-medium text-neutral-900">{{ $cancellation->reason_label }}</dd>
                                @if ($cancellation->reason_details)
                                    <dd class="mt-1.5 whitespace-pre-line break-words rounded-xl bg-neutral-50 px-3 py-2 text-neutral-700">{{ $cancellation->reason_details }}</dd>
                                @endif
                            </div>
                        </dl>
                    @else
                        <p class="text-sm text-tertiary">{{ __('No cancellation details were recorded.') }}</p>
                    @endif
                </x-panel>

                <x-panel :title="__('Need help?')">
                    <p class="text-sm text-neutral-700">{{ __('Read how cancellations, partial payments and disputes work.') }}</p>
                    <a href="{{ route('engagements.policy') }}" class="mt-3 inline-block text-sm font-medium text-teal-700 hover:text-teal-800 hover:underline">{{ __('Cancellation & payment policy') }}</a>
                </x-panel>
            </div>
        </div>
    </div>

    @if ($isPoster)
        @include('jobBoard.engagements.partials.components.modals.approve')
        @include('jobBoard.engagements.partials.components.modals.reject')
    @endif

    <!-- Dispute warning -->
    <x-modal name="dispute-warning" max-width="2xl">
        <x-modal.header :title="__('Before you dispute this payment')" icon="exclamation-triangle" />

        <div class="space-y-4 p-6 text-sm text-neutral-700">
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-amber-900">
                <p class="font-medium">{{ __('The engagement will be frozen') }}</p>
                <p class="mt-1">{{ __('While the dispute is open, no further actions, payments or deliverables can be processed.') }}</p>
            </div>
            <ul class="list-disc space-y-1.5 pl-5">
                <li>{{ __('An administrator reviews every dispute, usually within 3-5 business days.') }}</li>
                <li>{{ __('You can only withdraw a dispute with the administrator\'s approval.') }}</li>
                <li>{{ __('Give a clear reason and, if you have it, supporting evidence.') }}</li>
                <li>{{ __('The disputed amount stays in escrow until the decision.') }}</li>
                <li>{{ __('Frivolous disputes can affect your account standing.') }}</li>
            </ul>
            <p class="text-tertiary">{{ __('We recommend trying to resolve payment questions with the client directly first.') }}</p>
        </div>

        <x-modal.footer class="items-center">
            <x-btn type="button" variant="secondary" x-on:click="dismiss()">{{ __('Cancel') }}</x-btn>
            @if ($payment)
                <x-btn variant="danger" href="{{ route('engagements.dispute-form', $payment->id) }}">{{ __('Continue to dispute') }}</x-btn>
            @endif
        </x-modal.footer>
    </x-modal>
</x-app-layout>
