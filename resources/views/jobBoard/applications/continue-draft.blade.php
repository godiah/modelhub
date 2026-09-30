@php
    $open = $job->isOpenForApplications();
    $cover = $job->images ? asset('storage/'.$job->images) : null;
@endphp
<x-app-layout crumb="Resume application">
    <div class="container mx-auto max-w-7xl px-4 py-8">
        <!-- Header -->
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-medium uppercase tracking-wide text-tertiary">{{ __('Draft application') }}</p>
                <h1 class="mt-1 font-tertiary text-2xl font-semibold text-neutral-900">{{ __('Finish your application') }}</h1>
                <p class="mt-1 max-w-2xl text-sm text-tertiary">{{ __('Saved :time. Nothing is sent to the client until you submit.', ['time' => $application->updated_at->diffForHumans()]) }}</p>
            </div>
            <x-btn variant="secondary" size="sm" href="{{ route('applications.drafts') }}" class="shrink-0">
                <x-icon name="arrow-left" class="h-4 w-4" />
                {{ __('All drafts') }}
            </x-btn>
        </div>

        @unless ($open)
            <div class="mb-6 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900" role="status">
                <x-icon name="exclamation-triangle" class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" />
                <p>{{ __('This project is no longer accepting applications, so this draft cannot be sent. You can still read it or delete it from your drafts.') }}</p>
            </div>
        @endunless

        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[minmax(0,1fr)_28rem]">
            <!-- The project you are applying to -->
            <div class="space-y-6">
                <x-card class="rounded-2xl">
                    @if ($cover)
                        <img src="{{ $cover }}" alt="" class="aspect-[16/7] w-full rounded-t-2xl object-cover">
                    @endif
                    <div class="p-6">
                        <p class="text-xs font-medium uppercase tracking-wide text-tertiary">{{ __('Project') }}</p>
                        <h2 class="mt-1 font-tertiary text-xl font-semibold leading-snug text-neutral-900">{{ $job->title }}</h2>
                        @if ($job->user)
                            <div class="mt-4 flex items-center gap-3">
                                <x-user-avatar :user="$job->user" size="h-9 w-9" />
                                <p class="min-w-0 truncate text-sm font-medium text-neutral-900">{{ $job->user->name }}</p>
                            </div>
                        @endif
                    </div>
                    <dl class="grid grid-cols-1 divide-y divide-neutral-100 border-t border-neutral-100 sm:grid-cols-2 sm:divide-x sm:divide-y-0">
                        <div class="px-6 py-4">
                            <dt class="text-xs text-tertiary">{{ __('Budget') }}</dt>
                            <dd class="mt-1 font-tertiary text-xl font-semibold tabular-nums text-neutral-900"><x-money :amount="$job->budget" :decimals="0" /></dd>
                        </div>
                        <div class="px-6 py-4">
                            <dt class="text-xs text-tertiary">{{ __('Deadline') }}</dt>
                            <dd @class(['mt-1 text-base font-semibold', 'text-amber-700' => $job->deadlineIsSoon(), 'text-neutral-900' => ! $job->deadlineIsSoon()])>
                                {{ $job->no_deadline || ! $job->deadline ? __('No fixed deadline') : $job->deadline->format('M j, Y') }}
                            </dd>
                        </div>
                    </dl>
                </x-card>

                <x-panel :title="__('About this project')" :description="__('Re-read the brief before you send.')">
                    <x-jobs.markdown-description :content="$job->description" />
                    <x-btn variant="secondary" size="sm" class="mt-5" href="{{ route('jobs.apply', $job->slug) }}">
                        {{ __('Open the project page') }}
                        <x-icon name="arrow-right" class="h-4 w-4" />
                    </x-btn>
                </x-panel>
            </div>

            <aside class="lg:sticky lg:top-24">
                @include('jobBoard.applications.partials.form', ['job' => $job, 'application' => $application])
            </aside>
        </div>
    </div>
</x-app-layout>
