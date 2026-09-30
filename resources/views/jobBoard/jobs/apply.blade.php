@use('App\Enums\ApplicationStatus')
@php
    $isOwner = auth()->id() === $job->user_id;
    $canApply = auth()->check() && ! $isOwner && $applicationStatus === null;
    $skills = array_values(array_filter((array) $job->skills));
    $software = array_values(array_filter((array) $job->software));
    $deadlineSoon = $job->deadlineIsSoon();

    $gallery = collect([$job->images])
        ->merge($job->jobImages->pluck('image_path'))
        ->filter()
        ->map(fn ($path) => asset('storage/'.$path))
        ->values();

    $statusCopy = [
        ApplicationStatus::Submitted->value => __('Your application is waiting for the client to review it.'),
        ApplicationStatus::Reviewed->value => __('The client has looked at your application.'),
        ApplicationStatus::Hired->value => __('You were hired for this project. Check your engagements.'),
        ApplicationStatus::Rejected->value => __('The client chose another freelancer for this project.'),
        ApplicationStatus::Withdrawn->value => __('You withdrew this application.'),
    ];
@endphp
<x-app-layout title="Project details" :crumb="$job->title">
    <div class="container mx-auto max-w-7xl px-4 py-8">
        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[minmax(0,1fr)_24rem]">
            <!-- The project -->
            <div class="space-y-6">
                <x-card class="rounded-2xl">
                    <div class="p-6 sm:p-8">
                        <p class="text-xs font-medium uppercase tracking-wide text-tertiary">{{ __('Project') }}</p>
                        <h1 class="mt-1 font-tertiary text-2xl font-semibold leading-snug text-neutral-900 sm:text-3xl">{{ $job->title }}</h1>

                        @if ($job->user)
                            <div class="mt-4 flex items-center gap-3">
                                <x-user-avatar :user="$job->user" size="h-10 w-10" />
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-neutral-900">{{ $job->user->name }}</p>
                                    <p class="text-xs text-tertiary">{{ __('Posted :time', ['time' => $job->created_at->diffForHumans()]) }}</p>
                                </div>
                            </div>
                        @endif
                    </div>

                    <dl class="grid grid-cols-1 divide-y divide-neutral-100 border-t border-neutral-100 sm:grid-cols-3 sm:divide-x sm:divide-y-0">
                        <div class="px-6 py-4 sm:px-8">
                            <dt class="text-xs text-tertiary">{{ __('Budget') }}</dt>
                            <dd class="mt-1 font-tertiary text-xl font-semibold tabular-nums text-neutral-900"><x-money :amount="$job->budget" :decimals="0" /></dd>
                        </div>
                        <div class="px-6 py-4 sm:px-8">
                            <dt class="text-xs text-tertiary">{{ __('Deadline') }}</dt>
                            <dd @class(['mt-1 text-base font-semibold', 'text-amber-700' => $deadlineSoon, 'text-neutral-900' => ! $deadlineSoon])>
                                {{ $job->no_deadline ? __('No fixed deadline') : $job->deadline->format('M j, Y') }}
                            </dd>
                        </div>
                        <div class="px-6 py-4 sm:px-8">
                            <dt class="text-xs text-tertiary">{{ __('Proposals') }}</dt>
                            <dd class="mt-1 text-base font-semibold text-neutral-900">{{ trans_choice(':count applicant|:count applicants', (int) $job->applicants_count, ['count' => (int) $job->applicants_count]) }}</dd>
                        </div>
                    </dl>
                </x-card>

                <x-panel :title="__('About this project')">
                    <x-jobs.markdown-description :content="$job->description" />
                </x-panel>

                @if ($skills || $software)
                    <x-panel :title="__('What the client is looking for')" :description="__('Preferred, not mandatory for every applicant.')">
                        <div class="space-y-5">
                            @foreach ([__('Skills') => [$skills, 'border-neutral-200 bg-neutral-50 text-neutral-700'], __('Software') => [$software, 'border-teal-100 bg-teal-50 text-teal-800']] as $label => [$items, $tone])
                                @if ($items)
                                    <div>
                                        <h3 class="mb-2 text-xs font-medium uppercase tracking-wide text-tertiary">{{ $label }}</h3>
                                        <ul class="flex flex-wrap gap-2">
                                            @foreach ($items as $item)
                                                <li class="rounded-full border px-3 py-1 text-xs font-medium {{ $tone }}">{{ $item }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </x-panel>
                @endif

                @if ($gallery->isNotEmpty())
                    <x-panel :title="__('Project preview')" x-data="{ src: null }" @keydown.escape.window="src = null">
                        <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                            @foreach ($gallery as $image)
                                <li>
                                    <button type="button" @click="src = @js($image)" class="group block w-full overflow-hidden rounded-xl border border-neutral-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40"
                                        aria-label="{{ __('View image :number full size', ['number' => $loop->iteration]) }}">
                                        <img src="{{ $image }}" alt="{{ __('Project preview :number', ['number' => $loop->iteration]) }}" loading="lazy"
                                            class="aspect-square w-full object-cover transition-transform duration-300 group-hover:scale-[1.03]">
                                    </button>
                                </li>
                            @endforeach
                        </ul>

                        <div x-show="src" x-cloak x-transition.opacity @click.self="src = null" role="dialog" aria-modal="true" aria-label="{{ __('Project preview') }}"
                            class="fixed inset-0 z-50 flex items-center justify-center bg-neutral-900/80 p-4 backdrop-blur-sm">
                            <div class="relative max-h-full max-w-5xl">
                                <img :src="src" alt="" class="max-h-[85vh] rounded-xl bg-white object-contain shadow-2xl">
                                <div class="absolute right-3 top-3 flex gap-2">
                                    <a :href="src" download class="rounded-full bg-white/90 p-2 text-neutral-700 shadow hover:text-neutral-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40" aria-label="{{ __('Download image') }}"><x-icon name="cloud-arrow-down" class="h-5 w-5" /></a>
                                    <button type="button" @click="src = null" class="rounded-full bg-white/90 p-2 text-neutral-700 shadow hover:text-neutral-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40" aria-label="{{ __('Close') }}"><x-icon name="x-mark" class="h-5 w-5" /></button>
                                </div>
                            </div>
                        </div>
                    </x-panel>
                @endif
            </div>

            <!-- Apply -->
            <aside class="lg:sticky lg:top-24">
                @if (! auth()->check())
                    <x-panel :title="__('Interested in this project?')" :description="__('Sign in to send the client your offer and proposal.')">
                        <div class="space-y-3">
                            <x-btn block href="{{ route('login') }}">{{ __('Sign in to apply') }}</x-btn>
                            <x-btn block variant="secondary" href="{{ route('register') }}">{{ __('Create an account') }}</x-btn>
                        </div>
                    </x-panel>
                @elseif ($isOwner)
                    <x-panel :title="__('This is your project')" :description="__('Freelancers see this page when they open your project.')">
                        <div class="space-y-3">
                            <x-btn block href="{{ route('my-jobs.applications.index', $job->slug) }}">{{ __('Review applications') }}</x-btn>
                            <x-btn block variant="secondary" href="{{ route('jobs.show', $job->slug) }}">{{ __('Open your project page') }}</x-btn>
                        </div>
                    </x-panel>
                @elseif ($applicationStatus === ApplicationStatus::Draft)
                    <x-panel :title="__('You have a saved draft')" :description="__('Pick up where you left off and send it when you are ready.')">
                        <x-btn block href="{{ route('applications.continue', $job->slug) }}">{{ __('Continue draft') }}</x-btn>
                    </x-panel>
                @elseif ($applicationStatus)
                    <x-panel :title="__('You applied to this project')">
                        <x-badge tone="blue" class="px-2.5 py-0.5 text-xs font-medium">{{ $applicationStatus->label() }}</x-badge>
                        <p class="mt-3 text-sm text-neutral-700">{{ $statusCopy[$applicationStatus->value] ?? '' }}</p>
                        <x-btn block variant="secondary" class="mt-4" href="{{ route('applications.show', $job->slug) }}">{{ __('View your application') }}</x-btn>
                    </x-panel>
                @else
                    @include('jobBoard.applications.partials.form', ['job' => $job, 'application' => null])
                @endif
            </aside>
        </div>

        @if ($similarJobs->isNotEmpty())
            <section class="mt-10 border-t border-neutral-200 pt-8" aria-labelledby="similar-projects">
                <h2 id="similar-projects" class="mb-4 font-tertiary text-lg font-semibold text-neutral-900">{{ __('Similar projects') }}</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($similarJobs->take(3) as $similarJob)
                        <x-jobs.card :job="$similarJob" :poster="false" :compact="true" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-app-layout>
