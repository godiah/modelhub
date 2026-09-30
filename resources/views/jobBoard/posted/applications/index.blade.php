@use('App\Enums\ApplicationStatus')
@php
    [$jobStatusLabel, $jobStatusTone] = $job->listingStatus();
    $statusTabs = [
        'all' => __('All'),
        ApplicationStatus::Submitted->value => __('New'),
        ApplicationStatus::Reviewed->value => __('Reviewed'),
        ApplicationStatus::Hired->value => __('Hired'),
        ApplicationStatus::Rejected->value => __('Rejected'),
        ApplicationStatus::Withdrawn->value => __('Withdrawn'),
    ];
    $sortOptions = [
        'date_desc' => __('Newest first'),
        'date_asc' => __('Oldest first'),
        'offer_low' => __('Lowest offer'),
        'offer_high' => __('Highest offer'),
    ];
    $statusTone = [
        ApplicationStatus::Submitted->value => ['amber', __('New')],
        ApplicationStatus::Reviewed->value => ['blue', __('Reviewed')],
        ApplicationStatus::Hired->value => ['green', __('Hired')],
        ApplicationStatus::Rejected->value => ['red', __('Rejected')],
        ApplicationStatus::Withdrawn->value => ['neutral', __('Withdrawn')],
    ];
    $tabUrl = fn (string $tab) => route('my-jobs.applications.index', array_filter([
        'slug' => $job->slug,
        'status' => $tab === 'all' ? null : $tab,
        'sort' => ($filters['sort'] ?? 'date_desc') === 'date_desc' ? null : $filters['sort'],
        'search' => $filters['search'],
    ]));
    $activeTab = $filters['status'];
    $fieldClass = 'block w-full rounded-xl border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 placeholder-neutral-400 transition-colors hover:border-neutral-400 focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25';
    $budget = (float) $job->budget;
    $barMax = max($budget, $bids['high'] ?? 0, 1);
@endphp
<x-app-layout :crumb="__('Applications') . ': ' . $job->title">
    <div class="container mx-auto max-w-7xl px-4 py-8">
        <!-- Header -->
        <x-card class="mb-6 rounded-2xl">
            <div class="flex flex-col gap-4 p-6 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-medium uppercase tracking-wide text-tertiary">{{ __('Applications') }}</p>
                    <div class="mt-1 flex flex-wrap items-center gap-3">
                        <h1 class="font-tertiary text-2xl font-semibold text-neutral-900">{{ $job->title }}</h1>
                        <x-badge :tone="$jobStatusTone" class="px-2.5 py-0.5 text-xs font-medium">{{ $jobStatusLabel }}</x-badge>
                    </div>
                    <p class="mt-1.5 text-sm text-tertiary">{{ __('Posted :date', ['date' => $job->created_at->format('M j, Y')]) }}</p>
                </div>
                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    <x-btn variant="secondary" size="sm" href="{{ route('jobs.show', $job->slug) }}">
                        <x-icon name="arrow-left" class="h-4 w-4" />
                        {{ __('Project overview') }}
                    </x-btn>
                    <x-btn variant="secondary" size="sm" href="{{ route('jobs.edit', $job->slug) }}">
                        <x-icon name="pencil-square" class="h-4 w-4" />
                        {{ __('Edit') }}
                    </x-btn>
                </div>
            </div>

            <dl class="grid grid-cols-2 divide-neutral-100 border-t border-neutral-100 sm:grid-cols-4 sm:divide-x">
                <div class="px-6 py-4">
                    <dt class="text-xs text-tertiary">{{ __('Budget') }}</dt>
                    <dd class="mt-1 font-tertiary text-xl font-semibold tabular-nums text-neutral-900"><x-money :amount="$job->budget" :decimals="0" /></dd>
                </div>
                <div class="px-6 py-4">
                    <dt class="text-xs text-tertiary">{{ __('Deadline') }}</dt>
                    <dd @class(['mt-1 text-base font-semibold', 'text-amber-700' => $job->deadlineIsSoon(), 'text-neutral-900' => ! $job->deadlineIsSoon()])>{{ $job->no_deadline || ! $job->deadline ? __('None') : $job->deadline->format('M j, Y') }}</dd>
                </div>
                <div class="px-6 py-4">
                    <dt class="text-xs text-tertiary">{{ __('Applications') }}</dt>
                    <dd class="mt-1 text-base font-semibold text-neutral-900">{{ $counts['all'] }}</dd>
                </div>
                <div class="px-6 py-4">
                    <dt class="text-xs text-tertiary">{{ __('Waiting for you') }}</dt>
                    <dd class="mt-1 text-base font-semibold text-teal-700">{{ $counts[ApplicationStatus::Submitted->value] ?? 0 }}</dd>
                </div>
            </dl>
        </x-card>

        @if ($filled)
            <div class="mb-6 flex items-start gap-3 rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-3 text-sm text-neutral-700" role="status">
                <x-icon name="information-circle" class="mt-0.5 h-5 w-5 shrink-0 text-neutral-400" />
                <p>{{ __('You have hired for this project, so the other applicants can no longer be hired.') }}</p>
            </div>
        @endif

        @if ($counts['all'] === 0)
            <x-empty-state icon="users" :title="__('No applications yet')"
                :description="__('Freelancers find your project on Browse projects. Make sure it is open, and share the link to bring people in.')">
                <div class="flex flex-wrap justify-center gap-2">
                    <x-btn variant="secondary" href="{{ route('jobs.edit', $job->slug) }}">{{ __('Edit the project') }}</x-btn>
                    <x-btn variant="secondary" href="{{ route('jobs.show', $job->slug) }}">{{ __('Copy the link') }}</x-btn>
                </div>
            </x-empty-state>
        @else
            <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
                <div class="min-w-0">
                    <!-- Search, sort and status -->
                    <div class="mb-5 rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm">
                        <form method="GET" action="{{ route('my-jobs.applications.index', $job->slug) }}" role="search" class="flex flex-col gap-3 sm:flex-row">
                            @if ($activeTab !== 'all')
                                <input type="hidden" name="status" value="{{ $activeTab }}">
                            @endif
                            <div class="relative min-w-0 flex-1">
                                <x-icon name="magnifying-glass" class="pointer-events-none absolute left-3.5 top-2.5 h-5 w-5 text-neutral-400" />
                                <label for="search" class="sr-only">{{ __('Search applicants') }}</label>
                                <input type="search" id="search" name="search" value="{{ $filters['search'] }}" maxlength="255" placeholder="{{ __('Search by name or email…') }}" class="{{ $fieldClass }} pl-11">
                            </div>
                            <label class="flex items-center gap-2 whitespace-nowrap text-sm text-tertiary">
                                <span class="hidden sm:inline">{{ __('Sort by') }}</span>
                                <select name="sort" onchange="this.form.submit()" class="{{ $fieldClass }} w-auto py-2 pr-8 font-medium">
                                    @foreach ($sortOptions as $value => $label)
                                        <option value="{{ $value }}" @selected(($filters['sort'] ?? 'date_desc') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <x-btn type="submit">{{ __('Search') }}</x-btn>
                        </form>

                        <nav class="mt-3 flex flex-wrap items-center gap-2" aria-label="{{ __('Application status') }}">
                            @foreach ($statusTabs as $key => $label)
                                @continue($key === ApplicationStatus::Withdrawn->value && ($counts[$key] ?? 0) === 0 && $activeTab !== $key)
                                <a href="{{ $tabUrl($key) }}" @if ($activeTab === $key) aria-current="page" @endif
                                    @class([
                                        'inline-flex items-center gap-2 rounded-full border px-3.5 py-1.5 text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40',
                                        'border-teal-600 bg-teal-600 text-white' => $activeTab === $key,
                                        'border-neutral-200 bg-white text-neutral-700 hover:border-neutral-300 hover:text-neutral-900' => $activeTab !== $key,
                                    ])>
                                    {{ $label }}
                                    <span @class(['rounded-full px-1.5 text-xs font-semibold tabular-nums', 'bg-white/25 text-white' => $activeTab === $key, 'bg-neutral-100 text-neutral-600' => $activeTab !== $key])>{{ $counts[$key] ?? 0 }}</span>
                                </a>
                            @endforeach
                            @if ($hasFilters)
                                <a href="{{ route('my-jobs.applications.index', $job->slug) }}" class="ml-1 text-sm font-medium text-teal-700 hover:text-teal-800 focus:outline-none focus-visible:underline">{{ __('Clear filters') }}</a>
                            @endif
                        </nav>
                    </div>

                    @if ($applications->isEmpty())
                        <x-empty-state icon="magnifying-glass" :title="__('No applications match')" :description="__('Try a different search or status.')">
                            <x-btn variant="secondary" href="{{ route('my-jobs.applications.index', $job->slug) }}">{{ __('Show all applications') }}</x-btn>
                        </x-empty-state>
                    @else
                        <div class="space-y-4">
                            @foreach ($applications as $application)
                                @php
                                    $applicant = $application->applicant;
                                    [$tone, $statusLabel] = $statusTone[$application->status->value] ?? ['neutral', $application->status->label()];
                                    $rating = $ratings[$application->applicant_id] ?? null;
                                    $offer = (float) $application->offer_amount;
                                    $diff = $budget > 0 && $offer > 0 ? (int) round(($offer - $budget) / $budget * 100) : null;
                                    $excerpt = \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', (string) $application->proposal)), 230);
                                    $files = array_values(array_filter((array) $application->portfolio));
                                    $images = array_values(array_filter($files, fn ($f) => in_array(strtolower(pathinfo($f, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)));
                                    $canAct = ! $filled && in_array($application->status, [ApplicationStatus::Submitted, ApplicationStatus::Reviewed], true);
                                @endphp
                                <x-card class="rounded-2xl transition-shadow duration-200 hover:shadow-md">
                                    <div class="p-5">
                                        <div class="flex items-start justify-between gap-4">
                                            <div class="flex min-w-0 items-center gap-3">
                                                <x-user-avatar :user="$applicant" size="h-11 w-11" />
                                                <div class="min-w-0">
                                                    <h2 class="truncate font-tertiary text-base font-semibold text-neutral-900">
                                                        <a href="{{ route('my-jobs.applications.show', $application) }}" class="rounded hover:text-teal-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">{{ $applicant->name }}</a>
                                                    </h2>
                                                    <p class="mt-0.5 flex flex-wrap items-center gap-x-3 text-xs text-tertiary">
                                                        <span>{{ __('Applied :time', ['time' => $application->created_at->diffForHumans()]) }}</span>
                                                        @if ($rating)
                                                            <span class="inline-flex items-center gap-1 text-neutral-700"><x-icon name="star-solid" class="h-3.5 w-3.5 text-amber-400" />{{ number_format($rating->average, 1) }} <span class="text-tertiary">({{ $rating->total }})</span></span>
                                                        @else
                                                            <span>{{ __('No reviews yet') }}</span>
                                                        @endif
                                                    </p>
                                                </div>
                                            </div>
                                            <x-badge :tone="$tone" class="shrink-0 px-2.5 py-0.5 text-xs font-medium">{{ $statusLabel }}</x-badge>
                                        </div>

                                        <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2">
                                            <p class="font-tertiary text-xl font-semibold tabular-nums text-neutral-900">
                                                @if ($offer > 0)
                                                    <x-money :amount="$application->offer_amount" :decimals="0" />
                                                @else
                                                    <span class="text-base font-normal text-tertiary">{{ __('No offer') }}</span>
                                                @endif
                                            </p>
                                            @if ($diff !== null)
                                                <x-badge :tone="$diff < 0 ? 'green' : ($diff > 0 ? 'amber' : 'neutral')" class="px-2.5 py-0.5 text-xs font-medium">
                                                    {{ $diff === 0 ? __('On budget') : ($diff < 0 ? __(':percent% below budget', ['percent' => abs($diff)]) : __(':percent% above budget', ['percent' => $diff])) }}
                                                </x-badge>
                                            @endif
                                        </div>

                                        <p class="mt-3 text-sm leading-relaxed text-neutral-700">{{ $excerpt !== '' ? $excerpt : __('No proposal was written.') }}</p>

                                        @if ($images || $files)
                                            <ul class="mt-3 flex items-center gap-2" aria-label="{{ __('Portfolio') }}">
                                                @foreach (array_slice($images, 0, 4) as $image)
                                                    <li><img src="{{ asset('storage/'.$image) }}" alt="" loading="lazy" class="h-12 w-12 rounded-lg border border-neutral-200 object-cover"></li>
                                                @endforeach
                                                @if (count($files) > min(count($images), 4))
                                                    <li class="flex h-12 items-center rounded-lg bg-neutral-100 px-3 text-xs font-medium text-tertiary">+{{ count($files) - min(count($images), 4) }} {{ __('more') }}</li>
                                                @endif
                                            </ul>
                                        @endif

                                        <div class="mt-5 flex flex-wrap items-center gap-2 border-t border-neutral-100 pt-4">
                                            <x-btn size="sm" href="{{ route('my-jobs.applications.show', $application) }}">{{ __('Review application') }}</x-btn>
                                            @if ($canAct && $application->status === ApplicationStatus::Submitted)
                                                <form action="{{ route('my-jobs.applications.update-status', $application) }}" method="POST">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="reviewed">
                                                    <x-btn size="sm" variant="secondary" type="submit">
                                                        <x-icon name="check" class="h-4 w-4" />
                                                        {{ __('Mark reviewed') }}
                                                    </x-btn>
                                                </form>
                                            @endif
                                            @if ($canAct)
                                                <x-btn size="sm" variant="secondary" href="{{ route('my-jobs.applications.show', ['application' => $application, 'hire' => 1]) }}">
                                                    <x-icon name="user-group" class="h-4 w-4" />
                                                    {{ __('Hire') }}
                                                </x-btn>
                                            @endif
                                        </div>
                                    </div>
                                </x-card>
                            @endforeach
                        </div>

                        @if ($applications->hasPages())
                            <nav role="navigation" aria-label="{{ __('Pagination') }}" class="mt-6 flex items-center justify-between gap-3">
                                <p class="text-sm text-tertiary">{{ __('Showing :from–:to of :total', ['from' => $applications->firstItem(), 'to' => $applications->lastItem(), 'total' => $applications->total()]) }}</p>
                                <div class="flex items-center gap-2">
                                    @if ($applications->onFirstPage())
                                        <x-btn variant="secondary" size="sm" type="button" disabled>{{ __('Previous') }}</x-btn>
                                    @else
                                        <x-btn variant="secondary" size="sm" href="{{ $applications->previousPageUrl() }}" rel="prev">{{ __('Previous') }}</x-btn>
                                    @endif
                                    <span class="px-1 text-sm tabular-nums text-tertiary">{{ $applications->currentPage() }} / {{ $applications->lastPage() }}</span>
                                    @if ($applications->hasMorePages())
                                        <x-btn variant="secondary" size="sm" href="{{ $applications->nextPageUrl() }}" rel="next">{{ __('Next') }}</x-btn>
                                    @else
                                        <x-btn variant="secondary" size="sm" type="button" disabled>{{ __('Next') }}</x-btn>
                                    @endif
                                </div>
                            </nav>
                        @endif
                    @endif
                </div>

                <!-- Bids at a glance -->
                <aside class="space-y-6 lg:sticky lg:top-24">
                    <x-panel :title="__('Bids at a glance')" :description="__('How the offers compare with your budget.')">
                        @if ($bids)
                            <dl class="space-y-4 text-sm">
                                @foreach ([
                                    [__('Your budget'), $budget, 'bg-neutral-300', false],
                                    [__('Lowest offer'), $bids['low'], 'bg-green-500', true],
                                    [__('Average offer'), $bids['mean'], 'bg-teal-500', true],
                                    [__('Highest offer'), $bids['high'], 'bg-amber-500', true],
                                ] as [$label, $value, $bar, $compare])
                                    <div>
                                        <div class="flex items-baseline justify-between gap-2">
                                            <dt class="text-tertiary">{{ $label }}</dt>
                                            <dd class="font-medium tabular-nums text-neutral-900"><x-money :amount="$value" :decimals="0" /></dd>
                                        </div>
                                        <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-neutral-100" aria-hidden="true">
                                            <div class="h-full rounded-full {{ $bar }}" style="width: {{ round($value / $barMax * 100, 1) }}%"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </dl>
                        @else
                            <p class="text-sm text-neutral-700">{{ __('Offers will appear here as applications come in.') }}</p>
                        @endif
                    </x-panel>
                </aside>
            </div>
        @endif
    </div>
</x-app-layout>
