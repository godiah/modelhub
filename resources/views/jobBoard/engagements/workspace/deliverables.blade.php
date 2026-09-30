@php
    $canManage = $actions['can_manage_deliverables'];
    $canSubmit = $actions['can_submit_deliverables'];
    $badges = [
        'pending' => ['neutral', __('Not submitted')],
        'submitted' => ['amber', __('Awaiting review')],
        'approved' => ['green', __('Approved')],
        'rejected' => ['red', __('Changes requested')],
    ];
    $iconButton = 'rounded-lg p-2 text-neutral-500 transition-colors hover:bg-neutral-100 hover:text-neutral-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40';
@endphp

<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <p class="text-sm text-tertiary">
        @if ($summary['total'] > 0)
            {{ __(':approved of :total approved', ['approved' => $summary['approved'], 'total' => $summary['total']]) }}
        @else
            {{ __('Deliverables break the project into pieces of work that are reviewed and approved one at a time.') }}
        @endif
    </p>
    @if ($canManage)
        <x-btn size="sm" type="button" @click="$dispatch('open-modal', 'add-deliverable-{{ $engagement->id }}')">
            <x-icon name="plus" class="h-4 w-4" />
            {{ __('Add deliverable') }}
        </x-btn>
    @endif
</div>

@if ($engagement->deliverables->isEmpty())
    <x-empty-state icon="clipboard-list" :title="__('No deliverables yet')"
        :description="$canManage ? __('Add the first deliverable so the freelancer knows what to submit and when.') : __('The client has not added any deliverables yet.')" />
@else
    <div class="space-y-4">
        @foreach ($engagement->deliverables->sortBy('due_date') as $deliverable)
            @php
                [$tone, $label] = $badges[$deliverable->status] ?? ['neutral', \Illuminate\Support\Str::headline($deliverable->status)];
                $open = in_array($deliverable->status, ['pending', 'rejected'], true);
                $overdue = $open && $deliverable->due_date && $deliverable->due_date->isPast() && !$deliverable->due_date->isToday();
                $files = $deliverable->submission_files ?? [];
            @endphp

            <x-card class="rounded-2xl" id="deliverable-{{ $deliverable->id }}">
                <div class="p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="font-tertiary text-base font-semibold text-neutral-900">{{ $deliverable->title }}</h3>
                                <x-badge :tone="$tone" class="px-2.5 py-0.5 text-xs font-medium">{{ $label }}</x-badge>
                                @if ($overdue)
                                    <x-badge tone="red" class="px-2.5 py-0.5 text-xs font-medium">{{ __('Overdue') }}</x-badge>
                                @endif
                            </div>
                            @if ($deliverable->due_date)
                                <p @class(['mt-1 text-xs', 'font-medium text-red-600' => $overdue, 'text-tertiary' => !$overdue])>
                                    {{ __('Due :date', ['date' => $deliverable->due_date->format('M j, Y')]) }}
                                </p>
                            @endif
                        </div>

                        <!-- Actions for this deliverable, by role and state -->
                        <div class="flex shrink-0 items-center gap-2">
                            @if ($canManage && $deliverable->status === 'submitted')
                                <x-btn size="sm" variant="secondary" type="button"
                                    @click="$dispatch('open-modal', 'reject-deliverable-{{ $deliverable->id }}')">{{ __('Request changes') }}</x-btn>
                                <x-btn size="sm" type="button"
                                    @click="$dispatch('open-modal', 'approve-deliverable-{{ $deliverable->id }}')">{{ __('Approve') }}</x-btn>
                            @endif

                            @if ($canSubmit && in_array($deliverable->status, ['pending', 'rejected'], true))
                                <x-btn size="sm" type="button"
                                    @click="$dispatch('open-modal', { name: 'submit-deliverable', id: {{ $deliverable->id }}, title: {{ \Illuminate\Support\Js::from($deliverable->title) }}, resubmit: {{ $deliverable->status === 'rejected' ? 'true' : 'false' }} })">
                                    {{ $deliverable->status === 'rejected' ? __('Resubmit') : __('Submit work') }}
                                </x-btn>
                            @endif

                            @if ($canManage && $deliverable->status === 'pending')
                                <button type="button" class="{{ $iconButton }}" aria-label="{{ __('Edit :title', ['title' => $deliverable->title]) }}"
                                    @click="$dispatch('open-modal', 'edit-deliverable-{{ $deliverable->id }}')">
                                    <x-icon name="pencil-square" class="h-5 w-5" />
                                </button>
                                <button type="button" class="{{ $iconButton }} hover:!bg-red-50 hover:!text-red-600" aria-label="{{ __('Remove :title', ['title' => $deliverable->title]) }}"
                                    @click="$dispatch('open-modal', 'remove-deliverable-{{ $deliverable->id }}')">
                                    <x-icon name="trash" class="h-5 w-5" />
                                </button>
                            @endif
                        </div>
                    </div>

                    @if ($deliverable->description)
                        <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-neutral-700">{{ $deliverable->description }}</p>
                    @endif

                    @if ($deliverable->status === 'rejected' && $deliverable->feedback)
                        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-red-800">{{ __('Feedback from the client') }}</p>
                            <p class="mt-1 whitespace-pre-line text-sm text-red-900">{{ $deliverable->feedback }}</p>
                        </div>
                    @endif

                    @if ($deliverable->submitted_at && ($deliverable->submission_notes || $files))
                        <div class="mt-4 rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-tertiary">
                                {{ __('Submitted :date', ['date' => $deliverable->submitted_at->format('M j, Y')]) }}
                            </p>
                            @if ($deliverable->submission_notes)
                                <p class="mt-1 whitespace-pre-line text-sm text-neutral-700">{{ $deliverable->submission_notes }}</p>
                            @endif
                            @if ($files)
                                <ul class="mt-3 flex flex-wrap gap-2">
                                    @foreach ($files as $index => $file)
                                        <li>
                                            <a href="{{ route('engagements.deliverables.download-file', [$deliverable->id, $index]) }}"
                                                class="inline-flex items-center gap-2 rounded-lg border border-neutral-200 bg-white px-3 py-1.5 text-xs font-medium text-neutral-700 transition-colors hover:border-neutral-300 hover:text-neutral-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">
                                                <x-icon name="cloud-arrow-down" class="h-4 w-4 text-neutral-400" />
                                                <span class="max-w-[14rem] truncate">{{ $file['name'] }}</span>
                                                @if (!empty($file['size']))
                                                    <span class="text-tertiary">{{ \Illuminate\Support\Number::fileSize($file['size']) }}</span>
                                                @endif
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endif

                    @if ($deliverable->status === 'approved' && $deliverable->feedback)
                        <p class="mt-4 text-sm text-neutral-600">
                            <span class="font-medium text-neutral-800">{{ __('Client feedback:') }}</span> {{ $deliverable->feedback }}
                        </p>
                    @endif
                </div>
            </x-card>
        @endforeach
    </div>
@endif
