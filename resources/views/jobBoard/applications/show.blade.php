@use('App\Enums\ApplicationStatus')
@php
    $job = $application->job;
    $standing = $application->standing();
    $engagement = $standing['engagement'];
    $offerPending = $engagement?->status === \App\Enums\EngagementStatus::EmployerAccepted;
    $primary = match (true) {
        $offerPending => [route('engagements.response-form', $application->id), __('Respond to offer')],
        $engagement && ! $standing['finished'] => [route('engagements.show', $engagement), __('Open workspace')],
        $engagement => [route('engagements.show', $engagement), __('View engagement')],
        default => null,
    };

    // Progress: applied → reviewed → the client's decision
    $failed = in_array($application->status, [ApplicationStatus::Rejected, ApplicationStatus::Withdrawn], true) || $standing['filled'];
    $reviewed = $application->status !== ApplicationStatus::Submitted;
    $decided = $application->status === ApplicationStatus::Hired || $engagement;
    $steps = [
        ['label' => __('Applied :date', ['date' => $application->created_at->format('M j')]), 'state' => 'done'],
        ['label' => __('Reviewed'), 'state' => $reviewed ? 'done' : 'current'],
        ['label' => match (true) {
            $decided => __('Hired'),
            $application->status === ApplicationStatus::Withdrawn => __('Withdrawn'),
            $application->status === ApplicationStatus::Rejected => __('Rejected'),
            $standing['filled'] => __('Position filled'),
            default => __('Decision'),
        }, 'state' => match (true) {
            $decided => 'done',
            $failed => 'failed',
            $reviewed => 'current',
            default => 'todo',
        }],
    ];

    $attachments = collect(is_array($application->portfolio) ? $application->portfolio : [])->map(fn ($path) => [
        'url' => asset('storage/'.$path),
        'name' => basename($path),
        'image' => in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp'], true),
    ]);
@endphp
<x-app-layout :crumb="$job->title">
    <div class="container mx-auto max-w-7xl px-4 py-8" x-data="{ archiving: false, restoring: false, deleting: false }">
        <!-- Header -->
        <x-card class="mb-6 rounded-2xl">
            <div class="flex flex-col gap-4 p-6 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex min-w-0 items-start gap-4">
                    <div class="hidden h-16 w-24 shrink-0 overflow-hidden rounded-xl bg-neutral-100 sm:block">
                        @if ($job->images)
                            <img src="{{ asset('storage/'.$job->images) }}" alt="" class="h-full w-full object-cover">
                        @else
                            <span class="flex h-full w-full items-center justify-center text-neutral-300"><x-icon name="photo" class="h-6 w-6" /></span>
                        @endif
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-medium uppercase tracking-wide text-tertiary">{{ __('Your application') }}</p>
                        <div class="mt-1 flex flex-wrap items-center gap-3">
                            <h1 class="font-tertiary text-2xl font-semibold text-neutral-900">{{ $job->title }}</h1>
                            <x-badge :tone="$standing['tone']" class="px-2.5 py-0.5 text-xs font-medium">{{ $standing['label'] }}</x-badge>
                            @if ($application->is_archived)
                                <x-badge tone="neutral" class="px-2.5 py-0.5 text-xs font-medium"><x-icon name="archive-box-2" class="mr-1 h-3.5 w-3.5" />{{ __('Archived') }}</x-badge>
                            @endif
                        </div>
                        <p class="mt-1.5 text-sm text-tertiary">{{ __('Sent :date', ['date' => $application->created_at->format('M j, Y')]) }}</p>
                    </div>
                </div>
                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    <x-btn variant="secondary" size="sm" href="{{ route('applications.my') }}">
                        <x-icon name="arrow-left" class="h-4 w-4" />
                        {{ __('My applications') }}
                    </x-btn>
                    @if ($primary)
                        <x-btn size="sm" href="{{ $primary[0] }}">{{ $primary[1] }}</x-btn>
                    @endif
                </div>
            </div>

            <dl class="grid grid-cols-1 divide-y divide-neutral-100 border-t border-neutral-100 sm:grid-cols-3 sm:divide-x sm:divide-y-0">
                <div class="px-6 py-4">
                    <dt class="text-xs text-tertiary">{{ __('Your offer') }}</dt>
                    <dd class="mt-1 font-tertiary text-xl font-semibold tabular-nums text-neutral-900"><x-money :amount="$application->offer_amount" /></dd>
                </div>
                <div class="px-6 py-4">
                    <dt class="text-xs text-tertiary">{{ __('You receive') }}</dt>
                    <dd class="mt-1 font-tertiary text-xl font-semibold tabular-nums text-teal-700"><x-money :amount="$application->net_amount" /></dd>
                </div>
                <div class="px-6 py-4">
                    <dt class="text-xs text-tertiary">{{ __('Client budget') }}</dt>
                    <dd class="mt-1 font-tertiary text-xl font-semibold tabular-nums text-neutral-900"><x-money :amount="$job->budget" :decimals="0" /></dd>
                </div>
            </dl>
        </x-card>

        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <div class="space-y-6">
                <!-- Progress -->
                <x-panel :title="__('Where it stands')" :description="$standing['hint']">
                    <x-engagement.stepper :steps="$steps" />
                </x-panel>

                <!-- Messages from the client -->
                @if ($clientMessages->isNotEmpty())
                    <x-panel :title="__('Messages from the client')" :flush="true">
                        <ul class="divide-y divide-neutral-100 border-t border-neutral-100">
                            @foreach ($clientMessages as $message)
                                <li class="px-6 py-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <p class="min-w-0 text-sm font-medium text-neutral-900">{{ $message->subject }}</p>
                                        <time datetime="{{ $message->created_at->toIso8601String() }}" class="shrink-0 text-xs text-tertiary">{{ $message->created_at->diffForHumans() }}</time>
                                    </div>
                                    <p class="mt-1.5 whitespace-pre-line break-words text-sm leading-relaxed text-neutral-700">{{ $message->message }}</p>
                                    <p class="mt-1.5 text-xs text-tertiary">{{ __('From :name', ['name' => $message->sender?->name ?? __('the client')]) }}</p>
                                </li>
                            @endforeach
                        </ul>
                    </x-panel>
                @endif

                <!-- Proposal -->
                <x-panel :title="__('Your proposal')">
                    @if (filled($application->proposal))
                        <p class="whitespace-pre-line break-words text-sm leading-relaxed text-neutral-700">{{ $application->proposal }}</p>
                    @else
                        <p class="text-sm text-tertiary">{{ __('You did not write a proposal for this application.') }}</p>
                    @endif
                </x-panel>

                <!-- Attachments -->
                @if ($attachments->isNotEmpty())
                    <x-panel :title="__('Portfolio samples')">
                        <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
                            @foreach ($attachments as $file)
                                <li>
                                    <a href="{{ $file['url'] }}" target="_blank" rel="noopener" class="group block overflow-hidden rounded-xl border border-neutral-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">
                                        @if ($file['image'])
                                            <img src="{{ $file['url'] }}" alt="" loading="lazy" class="aspect-square w-full object-cover transition-transform duration-300 group-hover:scale-[1.03]">
                                        @else
                                            <span class="flex aspect-square w-full items-center justify-center bg-neutral-50 text-neutral-300"><x-icon name="document" class="h-10 w-10" /></span>
                                        @endif
                                        <span class="block truncate border-t border-neutral-100 px-2.5 py-1.5 text-xs text-neutral-600">{{ $file['name'] }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </x-panel>
                @endif

                <!-- The project -->
                <x-panel :title="__('About the project')">
                    <x-jobs.markdown-description :content="$job->description" />
                </x-panel>
            </div>

            <aside class="space-y-6 lg:sticky lg:top-24">
                <x-panel :title="__('Your offer')">
                    <dl class="space-y-2 text-sm">
                        <div class="flex items-center justify-between">
                            <dt class="text-tertiary">{{ __('Offer') }}</dt>
                            <dd class="font-medium tabular-nums text-neutral-900"><x-money :amount="$application->offer_amount" /></dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-tertiary">{{ __('Service fee') }}</dt>
                            <dd class="tabular-nums text-neutral-700">− <x-money :amount="$application->service_fee" /></dd>
                        </div>
                        <div class="flex items-center justify-between border-t border-neutral-100 pt-2">
                            <dt class="font-medium text-neutral-900">{{ __('You receive') }}</dt>
                            <dd class="font-tertiary text-base font-semibold tabular-nums text-teal-700"><x-money :amount="$application->net_amount" /></dd>
                        </div>
                    </dl>
                    <p class="mt-3 text-xs text-tertiary">{{ $application->terms_accepted ? __('You accepted the terms when you applied.') : __('Terms were not accepted.') }}</p>
                </x-panel>

                @if ($application->poster)
                    <x-panel :title="__('The client')">
                        <div class="flex items-center gap-3">
                            <x-user-avatar :user="$application->poster" size="h-10 w-10" />
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-neutral-900">{{ $application->poster->name }}</p>
                                <p class="text-xs text-tertiary">{{ __('Posted :date', ['date' => $job->created_at->format('M j, Y')]) }}</p>
                            </div>
                        </div>
                        <x-btn block variant="secondary" size="sm" class="mt-4" href="{{ route('jobs.apply', $job->slug) }}">{{ __('Open the project page') }}</x-btn>
                    </x-panel>
                @endif

                @if ($application->is_archived)
                    <x-panel :title="__('Archived')" :description="__('This application is out of your My applications list.')">
                        <div class="flex flex-wrap items-center gap-3">
                            <x-btn size="sm" type="button" @click="restoring = true">
                                <x-icon name="arrow-path" class="h-4 w-4" />
                                {{ __('Restore') }}
                            </x-btn>
                            @unless ($engagement)
                                <button type="button" @click="deleting = true" class="rounded px-1 text-xs text-tertiary hover:text-red-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-300">{{ __('Delete') }}</button>
                            @endunless
                        </div>
                    </x-panel>
                @elseif ($application->canBeArchived())
                    <x-panel :title="__('Finished with this?')" :description="__('Archive it to keep My applications to what is still going.')">
                        <x-btn size="sm" variant="secondary" type="button" @click="archiving = true">
                            <x-icon name="archive-box-2" class="h-4 w-4" />
                            {{ __('Archive') }}
                        </x-btn>
                    </x-panel>
                @endif
            </aside>
        </div>

        @if ($application->is_archived)
            <x-confirm-dialog bind="restoring" title="Restore application" icon="arrow-path" tone="success" confirm-label="Restore"
                :action="route('applications.restore', $application)"
                message="This brings the application back to My applications." />
            @unless ($engagement)
                <x-confirm-dialog bind="deleting" title="Delete application" confirm-label="Delete" method="DELETE"
                    :action="route('applications.destroy', $application)"
                    message="This removes the application for good. You will not be able to apply to this project again." />
            @endunless
        @elseif ($application->canBeArchived())
            <x-confirm-dialog bind="archiving" title="Archive application" icon="archive-box-2" tone="primary" confirm-label="Archive"
                :action="route('applications.archive', $application)"
                message="This moves the application to your archive. You can restore it any time." />
        @endif
    </div>
</x-app-layout>
