<x-app-layout :crumb="__('Edit') . ': ' . $job->title">
    <div class="container mx-auto max-w-7xl px-4 py-8">
        <x-card class="mb-6 rounded-2xl">
            <div class="flex flex-col gap-3 p-6 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-medium uppercase tracking-wide text-tertiary">{{ __('Edit project') }}</p>
                    <h1 class="mt-1 font-tertiary text-2xl font-semibold text-neutral-900">{{ $job->title }}</h1>
                    <p class="mt-1 max-w-3xl text-sm text-tertiary">{{ __('Changes are visible to freelancers straight away. The project keeps its link, and people who already applied keep their applications.') }}</p>
                </div>
                <x-btn variant="secondary" size="sm" href="{{ route('jobs.show', $job->slug) }}" class="shrink-0 self-start">
                    <x-icon name="arrow-left" class="h-4 w-4" />
                    {{ __('Back to project') }}
                </x-btn>
            </div>
        </x-card>

        @include('jobBoard.jobs.partials.form', [
            'job' => $job,
            'locked' => $locked,
            'action' => route('jobs.update', $job),
            'submitLabel' => __('Save changes'),
            'cancelUrl' => route('jobs.show', $job->slug),
        ])
    </div>
</x-app-layout>
