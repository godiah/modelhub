@php $application = $engagement->application; $dispute = $engagement->cancellation?->dispute; $deliverables = $engagement->deliverables; @endphp
<x-staff-layout :title="__('Hire #:id', ['id' => $engagement->id])">
    <div class="container mx-auto max-w-6xl space-y-6 px-4 py-8">
        <a href="{{ route('admin.engagements.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-teal-700 hover:underline"><x-icon name="arrow-left" class="h-4 w-4" />{{ __('All hires') }}</a>

        <x-card class="rounded-2xl">
            <div class="flex flex-wrap items-start justify-between gap-4 p-6">
                <div class="min-w-0">
                    <p class="text-xs font-medium uppercase tracking-wide text-tertiary">{{ __('Hire #:id', ['id' => $engagement->id]) }}</p>
                    <div class="mt-1 flex flex-wrap items-center gap-3"><h1 class="font-tertiary text-2xl font-semibold text-neutral-900">{{ $application->job?->title ?? __('A deleted project') }}</h1><x-badge tone="neutral" class="px-2.5 py-0.5 text-xs font-medium">{{ $engagement->status->label() }}</x-badge></div>
                    @if ($application->job)@can('view projects')<a href="{{ route('admin.projects.show', $application->job) }}" class="mt-1.5 inline-block text-sm font-medium text-teal-700 hover:underline">{{ __('Open the project') }}</a>@endcan @endif
                </div>
                @if ($dispute)@can('view disputes')<x-btn variant="danger-outline" href="{{ route('admin.disputes.show', $dispute->cancellation_id) }}">{{ __('Open the dispute') }}</x-btn>@endcan @endif
            </div>
        </x-card>

        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
            <div class="space-y-6">
                <x-panel :title="__('Deliverables')" :description="__(':done of :total approved', ['done' => $deliverables->where('status', 'approved')->count(), 'total' => $deliverables->count()])">
                    @forelse ($deliverables as $deliverable)
                        <div class="flex items-center justify-between gap-3 border-b border-neutral-100 py-2.5 text-sm last:border-0">
                            <div class="min-w-0"><p class="truncate font-medium text-neutral-900">{{ $deliverable->title }}</p><p class="text-xs text-tertiary">{{ $deliverable->due_date ? __('Due :date', ['date' => $deliverable->due_date->format('M j, Y')]) : __('No due date') }}</p></div>
                            <x-badge :tone="match ($deliverable->status) { 'approved' => 'green', 'submitted' => 'amber', 'rejected' => 'red', default => 'neutral' }" class="px-2 py-0.5 text-xs font-medium">{{ ucfirst($deliverable->status) }}</x-badge>
                        </div>
                    @empty<p class="text-sm text-tertiary">{{ __('No deliverables were agreed.') }}</p>@endforelse
                </x-panel>

                @if ($engagement->cancellation)
                    <x-panel :title="__('Cancellation')">
                        <dl class="grid grid-cols-1 gap-x-8 gap-y-3 text-sm sm:grid-cols-2">
                            <div><dt class="text-xs text-tertiary">{{ __('Reason') }}</dt><dd class="mt-0.5 text-neutral-900">{{ ucwords(str_replace('_', ' ', (string) $engagement->cancellation->reason_category)) }}</dd></div>
                            <div><dt class="text-xs text-tertiary">{{ __('Partial payment') }}</dt><dd class="mt-0.5 tabular-nums text-neutral-900">@if ($engagement->cancellation->partial_payment_amount !== null)<x-money :amount="$engagement->cancellation->partial_payment_amount" />@else—@endif</dd></div>
                            @if (filled($engagement->cancellation->reason_details))<div class="sm:col-span-2"><dt class="text-xs text-tertiary">{{ __('Details') }}</dt><dd class="mt-0.5 whitespace-pre-line break-words text-neutral-700">{{ $engagement->cancellation->reason_details }}</dd></div>@endif
                        </dl>
                    </x-panel>
                @endif
            </div>

            <div class="space-y-6">
                <x-panel :title="__('Money')">
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between gap-4"><dt class="text-tertiary">{{ __('Agreed amount') }}</dt><dd class="font-medium tabular-nums text-neutral-900"><x-money :amount="$engagement->agreed_amount" /></dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-tertiary">{{ __('Service fee') }}</dt><dd class="tabular-nums text-neutral-900"><x-money :amount="$engagement->service_fee" /></dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-tertiary">{{ __('Freelancer receives') }}</dt><dd class="tabular-nums text-neutral-900"><x-money :amount="$engagement->net_amount" /></dd></div>
                        <div class="flex justify-between gap-4 border-t border-neutral-100 pt-3"><dt class="text-tertiary">{{ __('Escrowed') }}</dt><dd class="text-neutral-900">{{ $engagement->payment_escrowed_at?->format('M j, Y') ?? '—' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-tertiary">{{ __('Released') }}</dt><dd class="text-neutral-900">{{ $engagement->payment_released_at?->format('M j, Y') ?? '—' }}</dd></div>
                    </dl>
                </x-panel>
                <x-panel :title="__('The two sides')">
                    <ul class="space-y-4">
                        @foreach ([[__('Client'), $application->poster], [__('Freelancer'), $application->applicant]] as [$role, $person])
                            <li class="flex items-center gap-3">@if ($person)<x-user-avatar :user="$person" size="h-10 w-10" />@endif
                                <div class="min-w-0"><p class="truncate text-sm font-medium text-neutral-900">@if ($person)@can('view members')<a href="{{ route('admin.members.show', $person) }}" class="hover:text-teal-700">{{ $person->name }}</a>@else{{ $person->name }}@endcan @else{{ __('Deleted account') }}@endif</p><p class="text-xs text-tertiary">{{ $role }}</p></div></li>
                        @endforeach
                    </ul>
                </x-panel>
                <x-panel :title="__('Timeline')">
                    <dl class="space-y-2 text-sm">
                        @foreach ([['Offer accepted by client', $engagement->employer_accepted_at], ['Started', $engagement->started_at], ['Completed', $engagement->completed_at], ['Cancelled', $engagement->cancelled_at]] as [$label, $date])
                            @if ($date)<div class="flex justify-between gap-4"><dt class="text-tertiary">{{ __($label) }}</dt><dd class="text-neutral-900">{{ $date->format('M j, Y') }}</dd></div>@endif
                        @endforeach
                    </dl>
                </x-panel>
            </div>
        </div>
    </div>
</x-staff-layout>
