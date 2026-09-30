@use('App\Enums\ApplicationStatus')
@php
    [$statusLabel, $statusTone] = $job->listingStatus();
    $skills = array_values(array_filter((array) $job->skills));
    $software = array_values(array_filter((array) $job->software));
    $gallery = collect([$job->images])->merge($job->jobImages->pluck('image_path'))->filter()->map(fn ($path) => asset('storage/'.$path))->values();
    $deadlineSoon = $job->deadlineIsSoon();
    $applicationTone = [
        ApplicationStatus::Submitted->value => 'blue',
        ApplicationStatus::Reviewed->value => 'amber',
        ApplicationStatus::Hired->value => 'green',
        ApplicationStatus::Rejected->value => 'red',
        ApplicationStatus::Withdrawn->value => 'neutral',
    ];
    $canArchive = $job->canBeArchived();
@endphp
<x-app-layout :crumb="$job->title">
    <div class="container mx-auto max-w-7xl px-4 py-8">
        <!-- Header -->
        <x-card class="mb-6 rounded-2xl">
            <div class="flex flex-col gap-4 p-6 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-medium uppercase tracking-wide text-tertiary">{{ __('Your project') }}</p>
                    <div class="mt-1 flex flex-wrap items-center gap-3">
                        <h1 class="font-tertiary text-2xl font-semibold text-neutral-900">{{ $job->title }}</h1>
                        <x-badge :tone="$statusTone" class="px-2.5 py-0.5 text-xs font-medium">{{ $statusLabel }}</x-badge>
                    </div>
                    <p class="mt-1.5 text-sm text-tertiary">{{ __('Posted :date', ['date' => $job->created_at->format('M j, Y')]) }}</p>
                </div>

                <div class="flex shrink-0 flex-wrap items-center gap-2" x-data="{ copied: false, async copy() { try { await navigator.clipboard.writeText(@js($publicUrl)); this.copied = true; setTimeout(() => this.copied = false, 2000); } catch (e) {} } }">
                    <x-btn size="sm" href="{{ route('my-jobs.applications.index', $job->slug) }}">
                        <x-icon name="user-group" class="h-4 w-4" />
                        {{ __('Applications') }}
                        @if ($counts['new'] > 0)
                            <span class="rounded-full bg-white/25 px-1.5 text-xs font-semibold">{{ $counts['new'] }}</span>
                        @endif
                    </x-btn>
                    <x-btn size="sm" variant="secondary" href="{{ route('jobs.edit', $job->slug) }}">
                        <x-icon name="pencil-square" class="h-4 w-4" />
                        {{ __('Edit') }}
                    </x-btn>
                    <x-btn size="sm" variant="secondary" type="button" @click="copy()">
                        <x-icon name="link" class="h-4 w-4" />
                        <span x-text="copied ? @js(__('Link copied')) : @js(__('Copy link'))">{{ __('Copy link') }}</span>
                    </x-btn>
                </div>
            </div>

            <dl class="grid grid-cols-1 divide-y divide-neutral-100 border-t border-neutral-100 sm:grid-cols-3 sm:divide-x sm:divide-y-0">
                <div class="px-6 py-4">
                    <dt class="text-xs text-tertiary">{{ __('Budget') }}</dt>
                    <dd class="mt-1 font-tertiary text-xl font-semibold tabular-nums text-neutral-900"><x-money :amount="$job->budget" :decimals="0" /></dd>
                </div>
                <div class="px-6 py-4">
                    <dt class="text-xs text-tertiary">{{ __('Deadline') }}</dt>
                    <dd @class(['mt-1 text-base font-semibold', 'text-amber-700' => $deadlineSoon, 'text-neutral-900' => ! $deadlineSoon])>
                        {{ $job->no_deadline || ! $job->deadline ? __('No fixed deadline') : $job->deadline->format('M j, Y') }}
                    </dd>
                </div>
                <div class="px-6 py-4">
                    <dt class="text-xs text-tertiary">{{ __('Applications') }}</dt>
                    <dd class="mt-1 text-base font-semibold text-neutral-900">
                        {{ $counts['total'] }}
                        @if ($counts['new'] > 0)
                            <span class="ml-1 text-sm font-medium text-teal-700">{{ __(':count new', ['count' => $counts['new']]) }}</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </x-card>

        @if (! $job->isOpenForApplications() && ! $engagement)
            <div class="mb-6 flex items-start gap-3 rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-3 text-sm text-neutral-700" role="status">
                <x-icon name="information-circle" class="mt-0.5 h-5 w-5 shrink-0 text-neutral-400" />
                <p>{{ __('This project is not visible on Browse projects, so freelancers cannot apply.') }}
                    <a href="{{ route('jobs.edit', $job->slug) }}" class="font-medium text-teal-700 hover:underline">{{ __('Edit the project') }}</a> {{ __('to reopen it.') }}</p>
            </div>
        @endif

        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <div class="space-y-6">
                <x-panel :title="__('Description')">
                    <x-jobs.markdown-description :content="$job->description" />
                </x-panel>

                @if ($skills || $software)
                    <x-panel :title="__('What you asked for')">
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
                    <x-panel :title="__('Images')" x-data="{ src: null }" @keydown.escape.window="src = null">
                        <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                            @foreach ($gallery as $image)
                                <li>
                                    <button type="button" @click="src = @js($image)" class="group block w-full overflow-hidden rounded-xl border border-neutral-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40"
                                        aria-label="{{ __('View image :number full size', ['number' => $loop->iteration]) }}">
                                        <img src="{{ $image }}" alt="{{ $loop->first ? __('Cover image') : __('Extra image :number', ['number' => $loop->iteration - 1]) }}" loading="lazy"
                                            class="aspect-square w-full object-cover transition-transform duration-300 group-hover:scale-[1.03]">
                                    </button>
                                </li>
                            @endforeach
                        </ul>

                        <div x-show="src" x-cloak x-transition.opacity @click.self="src = null" role="dialog" aria-modal="true" aria-label="{{ __('Project image') }}"
                            class="fixed inset-0 z-50 flex items-center justify-center bg-neutral-900/80 p-4 backdrop-blur-sm">
                            <div class="relative max-h-full max-w-5xl">
                                <img :src="src" alt="" class="max-h-[85vh] rounded-xl bg-white object-contain shadow-2xl">
                                <button type="button" @click="src = null" class="absolute right-3 top-3 rounded-full bg-white/90 p-2 text-neutral-700 shadow hover:text-neutral-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40" aria-label="{{ __('Close') }}"><x-icon name="x-mark" class="h-5 w-5" /></button>
                            </div>
                        </div>
                    </x-panel>
                @endif
            </div>

            <aside class="space-y-6 lg:sticky lg:top-24">
                @if ($engagement)
                    <x-panel :title="__('Hired')">
                        <p class="text-sm text-neutral-700">
                            {{ $engagement->status === \App\Enums\EngagementStatus::EmployerAccepted ? __('You made an offer and are waiting for the freelancer to respond.') : __('This project has an engagement.') }}
                        </p>
                        <x-btn block class="mt-4" href="{{ route('engagements.show', $engagement) }}">{{ __('Open engagement') }}</x-btn>
                    </x-panel>
                @endif

                <x-panel :title="__('Applications')" :description="$counts['total'] ? trans_choice(':count application|:count applications', $counts['total'], ['count' => $counts['total']]) : null">
                    @forelse ($recent as $application)
                        @if ($loop->first)
                            <ul class="-my-3 divide-y divide-neutral-100">
                        @endif
                        <li>
                            <a href="{{ route('my-jobs.applications.show', $application) }}" class="-mx-2 flex items-center gap-3 rounded-lg px-2 py-3 transition-colors hover:bg-neutral-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">
                                <x-user-avatar :user="$application->applicant" size="h-9 w-9" />
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-medium text-neutral-900">{{ $application->applicant->name }}</span>
                                    <span class="block text-xs text-tertiary"><x-money :amount="$application->offer_amount" :decimals="0" /> · {{ $application->created_at->diffForHumans() }}</span>
                                </span>
                                <x-badge :tone="$applicationTone[$application->status->value] ?? 'neutral'" class="px-2 py-0.5 text-xs font-medium">{{ $application->status->label() }}</x-badge>
                            </a>
                        </li>
                        @if ($loop->last)
                            </ul>
                        @endif
                    @empty
                        <p class="text-sm text-neutral-700">{{ __('No applications yet. Share the link so freelancers can find this project.') }}</p>
                    @endforelse

                    @if ($counts['total'] > 0)
                        <x-btn block variant="secondary" class="mt-6" href="{{ route('my-jobs.applications.index', $job->slug) }}">{{ __('See all applications') }}</x-btn>
                    @endif
                </x-panel>

                <x-panel :title="__('Manage')">
                    <div class="space-y-2">
                        <x-btn block variant="secondary" href="{{ route('jobs.edit', $job->slug) }}">
                            <x-icon name="pencil-square" class="h-4 w-4" />
                            {{ __('Edit project') }}
                        </x-btn>
                        <x-btn block variant="secondary" href="{{ $publicUrl }}">
                            <x-icon name="eye" class="h-4 w-4" />
                            {{ __('View the public page') }}
                        </x-btn>
                        @if ($canArchive)
                            <form action="{{ route('my-jobs.archive', $job) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <x-btn block variant="secondary" type="submit">
                                    <x-icon name="archive-box" class="h-4 w-4" />
                                    {{ __('Archive project') }}
                                </x-btn>
                            </form>
                        @endif
                    </div>
                    <p class="mt-4 text-xs text-tertiary">{{ __('The public page is what freelancers see when they open your link.') }}</p>
                </x-panel>
            </aside>
        </div>
    </div>
</x-app-layout>
