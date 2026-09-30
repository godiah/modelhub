@props(['application', 'archived' => false])

{{--
    One of the applicant's sent applications. Shows where it stands (badge + hint), the offer, and the one
    action that matters next: respond to an offer, open the workspace, or view it. Archive / restore / delete
    sit behind confirm dialogs. Needs job, engagement and jobEngagements loaded (the lists do).
--}}
@php
    $job = $application->job;
    $standing = $application->standing();
    $engagement = $standing['engagement'];
    $offerPending = $engagement?->status === \App\Enums\EngagementStatus::EmployerAccepted;
    $primary = match (true) {
        $offerPending => [route('engagements.response-form', $application->id), __('Respond to offer')],
        $engagement && ! $standing['finished'] => [route('engagements.show', $engagement), __('Open workspace')],
        default => null,
    };
    $muted = $archived || $standing['finished'] && ! $engagement;
@endphp
<article x-data="{ restoring: false, deleting: false, archiving: false }" class="group flex flex-col gap-4 rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm transition-shadow duration-200 hover:shadow-md sm:flex-row sm:items-center">
    <a href="{{ route('applications.show', $job->slug) }}" tabindex="-1" aria-hidden="true" class="block aspect-[16/10] w-full shrink-0 overflow-hidden rounded-xl bg-neutral-100 sm:w-44">
        @if ($job->images)
            <img src="{{ asset('storage/'.$job->images) }}" alt="" loading="lazy" @class(['h-full w-full object-cover transition duration-300', 'grayscale group-hover:grayscale-0' => $muted])>
        @else
            <span class="flex h-full w-full items-center justify-center text-neutral-300"><x-icon name="photo" class="h-8 w-8" /></span>
        @endif
    </a>

    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-2">
            <x-badge :tone="$standing['tone']" class="px-2.5 py-0.5 text-xs font-medium">{{ $standing['label'] }}</x-badge>
            @if ($archived)
                <x-badge tone="neutral" class="px-2.5 py-0.5 text-xs font-medium"><x-icon name="archive-box-2" class="mr-1 h-3.5 w-3.5" />{{ __('Archived') }}</x-badge>
            @endif
        </div>
        <h2 class="mt-2 font-tertiary text-base font-semibold leading-snug text-neutral-900">
            <a href="{{ route('applications.show', $job->slug) }}" class="rounded transition-colors hover:text-teal-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">{{ $job->title }}</a>
        </h2>
        <p class="mt-1 text-sm text-tertiary">{{ $standing['hint'] }}</p>
        <p class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-tertiary">
            <span>{{ __('Your offer') }} <span class="font-medium tabular-nums text-neutral-800"><x-money :amount="$application->offer_amount" :decimals="0" /></span></span>
            <span>{{ __('Budget') }} <span class="font-medium tabular-nums text-neutral-800"><x-money :amount="$job->budget" :decimals="0" /></span></span>
            <span>{{ __('Applied :date', ['date' => $application->created_at->format('M j, Y')]) }}</span>
        </p>
    </div>

    <div class="flex shrink-0 flex-wrap items-center gap-2 sm:flex-col sm:items-stretch">
        @if ($primary)
            <x-btn size="sm" href="{{ $primary[0] }}">{{ $primary[1] }}</x-btn>
            <x-btn size="sm" variant="secondary" href="{{ route('applications.show', $job->slug) }}">{{ __('View application') }}</x-btn>
        @else
            <x-btn size="sm" variant="secondary" href="{{ route('applications.show', $job->slug) }}">{{ __('View application') }}</x-btn>
        @endif

        @if ($archived)
            <x-btn size="sm" type="button" variant="secondary" @click="restoring = true">
                <x-icon name="arrow-path" class="h-4 w-4" />
                {{ __('Restore') }}
            </x-btn>
            @unless ($engagement)
                <button type="button" @click="deleting = true" class="rounded px-1 text-xs text-tertiary hover:text-red-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-300">{{ __('Delete') }}</button>
            @endunless
        @elseif ($application->canBeArchived())
            <button type="button" @click="archiving = true" class="inline-flex items-center justify-center gap-1.5 rounded px-1 text-xs text-tertiary hover:text-neutral-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">
                <x-icon name="archive-box-2" class="h-3.5 w-3.5" />{{ __('Archive') }}
            </button>
        @endif
    </div>

    @if ($archived)
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
</article>
