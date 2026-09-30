@props(['job', 'status' => null, 'poster' => true, 'compact' => false])
@use('App\Enums\ApplicationStatus')

{{--
    One project in a list: who posted it, the title, a short plain-text excerpt, skills/software tags, the
    budget and the right call to action for the viewer (Apply, Continue draft, their application's status,
    or "Your project"). `poster` needs job.user.profile loaded (lazy loading is blocked outside production).
--}}
@php
    $isOwner = auth()->id() === $job->user_id;
    // Plain-text excerpt: render the Markdown safely, put a space where blocks ended, then drop the tags.
    $html = \Illuminate\Support\Str::markdown((string) $job->description, ['html_input' => 'strip', 'allow_unsafe_links' => false]);
    $excerpt = \Illuminate\Support\Str::limit(
        trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags(preg_replace('#</(p|li|h[1-6]|blockquote|ul|ol)>|<br\s*/?>#i', ' ', $html))))),
        $compact ? 110 : 180,
    );
    // Skills stay neutral and software is tinted teal, so a merged row still tells "what you do" from "what you use".
    $skills = array_values(array_filter((array) $job->skills));
    $software = array_values(array_filter((array) $job->software));
    $shownSkills = array_slice($skills, 0, $compact ? 2 : 3);
    $shownSoftware = array_slice($software, 0, $compact ? 1 : 2);
    $moreTags = (count($skills) - count($shownSkills)) + (count($software) - count($shownSoftware));

    $deadlineSoon = ! $job->no_deadline && $job->deadline && $job->deadline->isFuture() && $job->deadline->diffInDays(now()) <= 3;
    $statusTone = [
        ApplicationStatus::Submitted->value => 'blue',
        ApplicationStatus::Reviewed->value => 'amber',
        ApplicationStatus::Hired->value => 'green',
        ApplicationStatus::Rejected->value => 'red',
        ApplicationStatus::Withdrawn->value => 'neutral',
    ];
@endphp

<article {{ $attributes->class('flex h-full flex-col overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm transition-shadow duration-200 hover:shadow-md') }}>
    {{-- Compact cards (similar projects) lead with the cover image; list cards carry a small thumbnail instead. --}}
    @if ($compact)
        <a href="{{ route('jobs.apply', $job->slug) }}" tabindex="-1" aria-hidden="true" class="block aspect-[16/9] bg-neutral-100">
            @if ($job->images)
                <img src="{{ asset('storage/'.$job->images) }}" alt="" loading="lazy" class="h-full w-full object-cover">
            @else
                <span class="flex h-full w-full items-center justify-center text-neutral-300"><x-icon name="photo" class="h-10 w-10" /></span>
            @endif
        </a>
    @endif

    <div class="flex flex-1 flex-col p-5">
    <div class="flex items-start justify-between gap-3 text-xs text-tertiary">
        @if ($poster && $job->user)
            <span class="flex min-w-0 items-center gap-2">
                <x-user-avatar :user="$job->user" size="h-6 w-6" class="!text-[10px]" />
                <span class="truncate font-medium text-neutral-700">{{ $job->user->name }}</span>
            </span>
        @else
            <span></span>
        @endif
        <time class="shrink-0" datetime="{{ $job->created_at->toIso8601String() }}">{{ $job->created_at->diffForHumans() }}</time>
    </div>

    <div class="mt-3 flex items-start gap-4">
        <div class="min-w-0 flex-1">
            <h3 class="font-tertiary text-base font-semibold leading-snug text-neutral-900">
                <a href="{{ route('jobs.apply', $job->slug) }}" class="rounded transition-colors hover:text-teal-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">{{ $job->title }}</a>
            </h3>

            <p class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-tertiary">
                <span @class(['inline-flex items-center gap-1', 'font-medium text-amber-700' => $deadlineSoon])>
                    <x-icon name="calendar" class="h-3.5 w-3.5" />
                    {{ $job->no_deadline ? __('No deadline') : __('Due :date', ['date' => $job->deadline->format('M j')]) }}
                </span>
                <span class="inline-flex items-center gap-1">
                    <x-icon name="users" class="h-3.5 w-3.5" />
                    {{ trans_choice(':count applicant|:count applicants', (int) $job->applicants_count, ['count' => (int) $job->applicants_count]) }}
                </span>
            </p>
        </div>

        @if ($job->images && ! $compact)
            <img src="{{ asset('storage/'.$job->images) }}" alt="" loading="lazy"
                class="hidden h-16 w-16 shrink-0 rounded-xl border border-neutral-200 object-cover sm:block">
        @endif
    </div>

    @if ($excerpt !== '')
        <p class="mt-3 text-sm leading-relaxed text-neutral-600">{{ $excerpt }}</p>
    @endif

    @if ($shownSkills || $shownSoftware)
        <ul class="mt-3 flex flex-wrap gap-1.5">
            @foreach ($shownSkills as $tag)
                <li class="rounded-full border border-neutral-200 bg-neutral-50 px-2.5 py-0.5 text-xs text-neutral-700">{{ $tag }}</li>
            @endforeach
            @foreach ($shownSoftware as $tag)
                <li class="rounded-full border border-teal-100 bg-teal-50 px-2.5 py-0.5 text-xs text-teal-800">{{ $tag }}</li>
            @endforeach
            @if ($moreTags > 0)
                <li class="rounded-full bg-neutral-100 px-2.5 py-0.5 text-xs text-tertiary">+{{ $moreTags }}</li>
            @endif
        </ul>
    @endif

    <div class="mt-auto flex items-center justify-between gap-3 pt-4">
        <div>
            <p class="text-xs text-tertiary">{{ __('Budget') }}</p>
            <p class="font-tertiary text-lg font-semibold tabular-nums text-neutral-900"><x-money :amount="$job->budget" :decimals="0" /></p>
        </div>

        <div class="flex items-center gap-2">
            @if ($isOwner)
                <x-badge tone="neutral" class="px-2.5 py-0.5 text-xs font-medium">{{ __('Your project') }}</x-badge>
                <x-btn size="sm" variant="secondary" href="{{ route('jobs.show', $job->slug) }}">{{ __('View') }}</x-btn>
            @elseif ($status === ApplicationStatus::Draft)
                <x-btn size="sm" variant="secondary" href="{{ route('applications.continue', $job->slug) }}">{{ __('Continue draft') }}</x-btn>
            @elseif ($status)
                <x-badge :tone="$statusTone[$status->value] ?? 'neutral'" class="px-2.5 py-0.5 text-xs font-medium">{{ $status->label() }}</x-badge>
                <x-btn size="sm" variant="secondary" href="{{ route('applications.show', $job->slug) }}">{{ __('View application') }}</x-btn>
            @else
                <x-btn size="sm" href="{{ route('jobs.apply', $job->slug) }}">
                    {{ __('Apply') }}
                    <x-icon name="arrow-right" class="h-4 w-4" />
                </x-btn>
            @endif
        </div>
    </div>
    </div>
</article>
