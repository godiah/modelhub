@use('App\Services\Admin\ProjectDirectoryService')
@php
    [$stateLabel, $stateTone] = ProjectDirectoryService::state($job);
    $canModerate = auth()->user()->can('moderate projects');
@endphp
<x-staff-layout :title="$job->title">
    <div class="container mx-auto max-w-6xl space-y-6 px-4 py-8" x-data="{ takingDown: false, restoring: false }">
        <a href="{{ route('admin.projects.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-teal-700 hover:underline"><x-icon name="arrow-left" class="h-4 w-4" />{{ __('All projects') }}</a>

        @if ($job->isTakenDown())
            <div class="flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-900" role="status">
                <x-icon name="exclamation-triangle" class="mt-0.5 h-5 w-5 shrink-0 text-red-600" />
                <p><span class="font-semibold">{{ __('Taken down :when', ['when' => $job->taken_down_at->diffForHumans()]) }}@if ($job->takenDownBy) {{ __('by :name', ['name' => $job->takenDownBy->name]) }}@endif.</span> {{ $job->taken_down_reason }}</p>
            </div>
        @endif

        <x-card class="rounded-2xl">
            <div class="flex flex-wrap items-start justify-between gap-4 p-6">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-3"><h1 class="font-tertiary text-2xl font-semibold text-neutral-900">{{ $job->title }}</h1><x-badge :tone="$stateTone" class="px-2.5 py-0.5 text-xs font-medium">{{ __($stateLabel) }}</x-badge></div>
                    <p class="mt-1.5 flex items-center gap-2 text-sm text-tertiary"><x-user-avatar :user="$job->user" size="h-5 w-5" />
                        @if ($job->user)@can('view members')<a href="{{ route('admin.members.show', $job->user) }}" class="font-medium text-teal-700 hover:underline">{{ $job->user->name }}</a>@else{{ $job->user->name }}@endcan @else{{ __('Deleted account') }}@endif
                        · {{ __('posted :date', ['date' => $job->created_at->format('M j, Y')]) }}</p>
                </div>
                @if ($canModerate)
                    @if ($job->isTakenDown())
                        <x-btn type="button" variant="secondary" @click="restoring = true">{{ __('Restore project') }}</x-btn>
                    @else
                        <x-btn type="button" variant="danger-outline" @click="takingDown = true">{{ __('Take down') }}</x-btn>
                    @endif
                @endif
            </div>
        </x-card>

        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
            <div class="space-y-6">
                <x-panel :title="__('The brief')">
                    <div class="text-sm"><x-jobs.markdown-description :content="$job->description" /></div>
                    @if ($job->skills)<ul class="mt-5 flex flex-wrap gap-2 border-t border-neutral-100 pt-4" aria-label="{{ __('Skills') }}">@foreach ($job->skills as $skill)<li class="rounded-full bg-neutral-100 px-3 py-1 text-xs text-neutral-700">{{ $skill }}</li>@endforeach</ul>@endif
                    @if ($job->software)<ul class="mt-3 flex flex-wrap gap-2" aria-label="{{ __('Software') }}">@foreach ($job->software as $tool)<li class="rounded-full border border-teal-100 bg-teal-50 px-3 py-1 text-xs text-teal-800">{{ $tool }}</li>@endforeach</ul>@endif
                </x-panel>

                <x-panel :title="__('Applicants (:count)', ['count' => $job->applications->count()])">
                    @forelse ($job->applications->sortByDesc('id') as $application)
                        <div class="flex items-center gap-3 border-b border-neutral-100 py-3 text-sm last:border-0">
                            @if ($application->applicant)<x-user-avatar :user="$application->applicant" size="h-8 w-8" />@endif
                            <div class="min-w-0 flex-1">
                                @if ($application->applicant)@can('view members')<a href="{{ route('admin.members.show', $application->applicant) }}" class="font-medium text-neutral-900 hover:text-teal-700">{{ $application->applicant->name }}</a>@else<span class="font-medium text-neutral-900">{{ $application->applicant->name }}</span>@endcan @else<span class="text-tertiary">{{ __('Deleted account') }}</span>@endif
                                <p class="text-xs text-tertiary">{{ $application->created_at->format('M j, Y') }}</p>
                            </div>
                            <p class="text-sm font-medium tabular-nums text-neutral-900"><x-money :amount="$application->offer_amount" :decimals="0" /></p>
                            <x-badge tone="neutral" class="px-2 py-0.5 text-xs font-medium">{{ $application->status->label() }}</x-badge>
                        </div>
                    @empty<p class="text-sm text-tertiary">{{ __('Nobody has applied.') }}</p>@endforelse
                </x-panel>
            </div>

            <div class="space-y-6">
                <x-panel :title="__('Details')">
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between gap-4"><dt class="text-tertiary">{{ __('Budget') }}</dt><dd class="font-medium tabular-nums text-neutral-900"><x-money :amount="$job->budget" :decimals="0" /></dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-tertiary">{{ __('Deadline') }}</dt><dd class="text-neutral-900">{{ $job->no_deadline || ! $job->deadline ? __('None') : $job->deadline->format('M j, Y') }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-tertiary">{{ __('Switched on') }}</dt><dd class="text-neutral-900">{{ $job->is_active ? __('Yes') : __('No') }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-tertiary">{{ __('Archived') }}</dt><dd class="text-neutral-900">{{ $job->is_archived ? __('Yes') : __('No') }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-tertiary">{{ __('Project #') }}</dt><dd class="tabular-nums text-neutral-900">{{ $job->id }}</dd></div>
                    </dl>
                </x-panel>
                @if ($job->engagements->isNotEmpty())
                    <x-panel :title="__('Hires from this project')">
                        @foreach ($job->engagements as $engagement)
                            @can('view engagements')<a href="{{ route('admin.engagements.show', $engagement) }}" class="block border-b border-neutral-100 py-2 text-sm last:border-0 hover:text-teal-700">{{ __('Hire #:id', ['id' => $engagement->id]) }} · {{ $engagement->status->label() }}</a>@else<p class="border-b border-neutral-100 py-2 text-sm last:border-0">{{ __('Hire #:id', ['id' => $engagement->id]) }} · {{ $engagement->status->label() }}</p>@endcan
                        @endforeach
                    </x-panel>
                @endif
            </div>
        </div>

        @if ($canModerate)
            <x-confirm-dialog bind="takingDown" title="Take this project down" confirm-label="Take down" state="reason: ''" disabledWhen="reason.trim().length < 5" :action="route('admin.projects.take-down', $job)"
                message="It leaves the board and stops accepting applications. The poster is told why and cannot reopen it. Work already in progress is not touched.">
                <label for="takedown-reason" class="sr-only">{{ __('Reason') }}</label>
                <textarea id="takedown-reason" name="reason" x-model="reason" rows="3" maxlength="500" required placeholder="{{ __('The reason the poster will be shown') }}" class="mt-3 block w-full resize-none rounded-xl border border-neutral-300 px-3 py-2 text-sm focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25"></textarea>
            </x-confirm-dialog>
            <x-confirm-dialog bind="restoring" title="Restore this project" icon="check" tone="success" confirm-label="Restore" :action="route('admin.projects.restore', $job)" message="It goes back on the board if it is still open (not archived, deadline not passed), and the poster is told." />
        @endif
    </div>
</x-staff-layout>
