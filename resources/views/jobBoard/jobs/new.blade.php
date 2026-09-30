<x-app-layout title="Post a project">
    <div class="container mx-auto max-w-7xl px-4 py-8">
        <x-card class="mb-6 rounded-2xl">
            <div class="p-6">
                <p class="text-xs font-medium uppercase tracking-wide text-tertiary">{{ __('New project') }}</p>
                <h1 class="mt-1 font-tertiary text-2xl font-semibold text-neutral-900">{{ __('Post a project') }}</h1>
                <p class="mt-1 max-w-3xl text-sm text-tertiary">{{ __('Describe what you need and freelancers will send you offers. You choose who to hire.') }}</p>
            </div>
        </x-card>

        @include('jobBoard.jobs.partials.form', [
            'action' => route('jobs.store'),
            'submitLabel' => __('Post project'),
            'cancelUrl' => route('my-jobs.index'),
        ])
    </div>
</x-app-layout>
