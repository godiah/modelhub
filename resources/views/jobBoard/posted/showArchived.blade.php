@use('App\Enums\ApplicationStatus')
@php
    $skills = array_values(array_filter((array) $job->skills));
    $software = array_values(array_filter((array) $job->software));
    $applicationTone = [
        ApplicationStatus::Submitted->value => 'blue',
        ApplicationStatus::Reviewed->value => 'amber',
        ApplicationStatus::Hired->value => 'green',
        ApplicationStatus::Rejected->value => 'red',
        ApplicationStatus::Withdrawn->value => 'neutral',
    ];
    $imagePaths = collect([$job->images])->merge($job->jobImages->pluck('image_path'))->filter()->values();
@endphp
<x-app-layout :crumb="$job->title">
    <div class="container mx-auto max-w-7xl px-4 py-8" x-data="{ showingRestore: false }">
        <!-- Header -->
        <x-card class="mb-6 rounded-2xl">
            <div class="flex flex-col gap-4 p-6 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-medium uppercase tracking-wide text-tertiary">{{ __('Archived project') }}</p>
                    <div class="mt-1 flex flex-wrap items-center gap-3">
                        <h1 class="font-tertiary text-2xl font-semibold text-neutral-900">{{ $job->title }}</h1>
                        <x-badge tone="neutral" class="px-2.5 py-0.5 text-xs font-medium"><x-icon name="archive-box-2" class="mr-1 h-3.5 w-3.5" />{{ __('Archived') }}</x-badge>
                    </div>
                    <p class="mt-1.5 text-sm text-tertiary">{{ __('Posted :date', ['date' => $job->created_at->format('M j, Y')]) }}</p>
                </div>
                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    <x-btn variant="secondary" size="sm" href="{{ route('my-jobs.archived.posted-jobs') }}">
                        <x-icon name="arrow-left" class="h-4 w-4" />
                        {{ __('Archived projects') }}
                    </x-btn>
                    <x-btn size="sm" type="button" @click="showingRestore = true">
                        <x-icon name="arrow-path" class="h-4 w-4" />
                        {{ __('Restore project') }}
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
                    <dd class="mt-1 text-base font-semibold text-neutral-900">{{ $job->no_deadline || ! $job->deadline ? __('No fixed deadline') : $job->deadline->format('M j, Y') }}</dd>
                </div>
                <div class="px-6 py-4">
                    <dt class="text-xs text-tertiary">{{ __('Applications') }}</dt>
                    <dd class="mt-1 text-base font-semibold text-neutral-900">{{ $applications->count() }}</dd>
                </div>
            </dl>
        </x-card>

        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <div class="space-y-6">
                <!-- Applications -->
                <x-panel :title="__('Applications')" :description="$applications->isEmpty() ? null : __('Everything freelancers sent while the project was open. Open one to read the proposal.')" :flush="$applications->isNotEmpty()">
                    @forelse ($applications as $application)
                        @if ($loop->first)
                            <div class="divide-y divide-neutral-100 border-t border-neutral-100">
                        @endif
                        <details class="group">
                            <summary class="flex cursor-pointer list-none items-center gap-3 px-6 py-4 transition-colors hover:bg-neutral-50 focus:outline-none focus-visible:bg-neutral-50 [&::-webkit-details-marker]:hidden">
                                <x-user-avatar :user="$application->applicant" size="h-10 w-10" />
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-medium text-neutral-900">{{ $application->applicant->name }}</span>
                                    <span class="block text-xs text-tertiary">
                                        {{ __('Applied :date', ['date' => $application->created_at->format('M j, Y')]) }}
                                        @if ($application->offer_amount)
                                            · <x-money :amount="$application->offer_amount" :decimals="0" />
                                        @endif
                                    </span>
                                </span>
                                <x-badge :tone="$applicationTone[$application->status->value] ?? 'neutral'" class="px-2.5 py-0.5 text-xs font-medium">{{ $application->status->label() }}</x-badge>
                                <x-icon name="chevron-down" class="h-4 w-4 shrink-0 text-neutral-400 transition-transform group-open:rotate-180" />
                            </summary>

                            <div class="space-y-4 bg-neutral-50/60 px-6 pb-5 pt-1">
                                <dl class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-3">
                                    <div>
                                        <dt class="text-xs text-tertiary">{{ __('Offer') }}</dt>
                                        <dd class="mt-0.5 font-medium tabular-nums text-neutral-900">
                                            @if ($application->offer_amount)
                                                <x-money :amount="$application->offer_amount" />
                                            @else
                                                <span class="font-normal text-tertiary">{{ __('Not specified') }}</span>
                                            @endif
                                        </dd>
                                    </div>
                                    @if ($application->net_amount)
                                        <div>
                                            <dt class="text-xs text-tertiary">{{ __('They would receive') }}</dt>
                                            <dd class="mt-0.5 font-medium tabular-nums text-neutral-900"><x-money :amount="$application->net_amount" /></dd>
                                        </div>
                                    @endif
                                    <div class="col-span-2 sm:col-span-1">
                                        <dt class="text-xs text-tertiary">{{ __('Email') }}</dt>
                                        <dd class="mt-0.5 truncate text-neutral-900">{{ $application->applicant->email }}</dd>
                                    </div>
                                </dl>

                                <div>
                                    <h3 class="mb-1.5 text-xs font-medium uppercase tracking-wide text-tertiary">{{ __('Proposal') }}</h3>
                                    @if ($application->proposal)
                                        <p class="max-h-48 overflow-y-auto whitespace-pre-line break-words rounded-xl border border-neutral-200 bg-white px-4 py-3 text-sm leading-relaxed text-neutral-700">{{ $application->proposal }}</p>
                                    @else
                                        <p class="text-sm text-tertiary">{{ __('No proposal was written.') }}</p>
                                    @endif
                                </div>

                                @if (is_array($application->portfolio) && count($application->portfolio) > 0)
                                    <div>
                                        <h3 class="mb-1.5 text-xs font-medium uppercase tracking-wide text-tertiary">{{ __('Portfolio') }}</h3>
                                        <ul class="flex flex-wrap gap-2">
                                            @foreach ($application->portfolio as $item)
                                                @php $isImage = in_array(strtolower(pathinfo($item, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp'], true); @endphp
                                                <li>
                                                    <a href="{{ asset('storage/'.$item) }}" target="_blank" rel="noopener"
                                                        class="flex items-center gap-2 rounded-lg border border-neutral-200 bg-white p-1.5 pr-3 text-xs font-medium text-neutral-700 transition-colors hover:border-neutral-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">
                                                        @if ($isImage)
                                                            <img src="{{ asset('storage/'.$item) }}" alt="" loading="lazy" class="h-9 w-9 rounded-md object-cover">
                                                        @else
                                                            <span class="flex h-9 w-9 items-center justify-center rounded-md bg-neutral-100 text-neutral-400"><x-icon name="document" class="h-4 w-4" /></span>
                                                        @endif
                                                        <span class="max-w-[10rem] truncate">{{ basename($item) }}</span>
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        </details>
                        @if ($loop->last)
                            </div>
                        @endif
                    @empty
                        <x-empty-state :framed="false" compact icon="users" :title="__('No applications')" :description="__('This project did not receive any applications.')" />
                    @endforelse
                </x-panel>

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
            </div>

            <aside class="space-y-6 lg:sticky lg:top-24">
                <x-panel :title="__('Bring it back')">
                    <p class="text-sm text-neutral-700">{{ __('Restoring moves the project back to Posted projects. It accepts applications again unless a freelancer is working on it or its deadline has passed. You can change the deadline from Edit.') }}</p>
                    <x-btn block class="mt-4" type="button" @click="showingRestore = true">
                        <x-icon name="arrow-path" class="h-4 w-4" />
                        {{ __('Restore project') }}
                    </x-btn>
                </x-panel>

                @if ($imagePaths->isNotEmpty())
                    <x-panel :title="__('Images')">
                        <ul class="grid grid-cols-2 gap-3">
                            @foreach ($imagePaths as $path)
                                <li>
                                    <a href="{{ asset('storage/'.$path) }}" target="_blank" rel="noopener" class="block overflow-hidden rounded-xl border border-neutral-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">
                                        <img src="{{ asset('storage/'.$path) }}" alt="{{ $loop->first ? __('Cover image') : __('Extra image :number', ['number' => $loop->iteration - 1]) }}" loading="lazy" class="aspect-square w-full object-cover">
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </x-panel>
                @endif
            </aside>
        </div>

        <x-confirm-dialog bind="showingRestore" title="Restore project" icon="arrow-path" tone="success" confirm-label="Restore" method="PATCH"
            :action="route('my-jobs.archived.restore', $job)"
            message="This brings the project back to Posted projects. It will accept applications again unless a freelancer is working on it or its deadline has passed." />
    </div>
</x-app-layout>
