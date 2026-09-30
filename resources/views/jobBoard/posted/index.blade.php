@use('App\Enums\ApplicationStatus')
@use('App\Enums\EngagementStatus')
@php
    $tabs = ['all' => __('All'), 'active' => __('Open'), 'closed' => __('Closed')];
    $sortOptions = [
        'newest' => __('Newest first'),
        'deadline' => __('Deadline: soonest'),
        'budget_high' => __('Budget: high to low'),
        'budget_low' => __('Budget: low to high'),
    ];
    $activeTab = $filters['status'] === 'inactive' ? 'closed' : $filters['status'];
    $tabUrl = fn (string $tab) => route('my-jobs.index', array_filter([
        'status' => $tab === 'all' ? null : $tab,
        'sort' => $filters['sort'] === 'newest' ? null : $filters['sort'],
        'search' => $filters['search'],
    ]));

    $tiles = [
        ['label' => __('Open projects'), 'value' => $stats['open'], 'icon' => 'briefcase', 'tone' => 'bg-teal-50 text-teal-700'],
        ['label' => __('New applications'), 'value' => $stats['new_applications'], 'icon' => 'users', 'tone' => 'bg-blue-50 text-blue-700'],
        ['label' => __('In progress'), 'value' => $stats['in_progress'], 'icon' => 'bolt', 'tone' => 'bg-amber-50 text-amber-700'],
        ['label' => __('Completed'), 'value' => $stats['completed'], 'icon' => 'check-circle', 'tone' => 'bg-green-50 text-green-700'],
    ];

    $applicationTone = [
        ApplicationStatus::Submitted->value => ['blue', 'bg-blue-500'],
        ApplicationStatus::Reviewed->value => ['amber', 'bg-amber-500'],
        ApplicationStatus::Hired->value => ['green', 'bg-green-500'],
        ApplicationStatus::Rejected->value => ['red', 'bg-red-400'],
        ApplicationStatus::Withdrawn->value => ['neutral', 'bg-neutral-300'],
    ];
    $fieldClass = 'block w-full rounded-xl border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 placeholder-neutral-400 transition-colors hover:border-neutral-400 focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25';
@endphp
<x-app-layout>
    <div class="container mx-auto max-w-7xl px-4 py-8" x-data="{
        selected: {{ $postedJobs->first()?->id ?? 'null' }},
        // The detail panel only exists from xl up; on smaller screens a card opens the project page instead.
        select(id, url) {
            if (window.matchMedia('(min-width: 1280px)').matches) this.selected = id;
            else window.location.href = url;
        },
    }">
        <!-- Header -->
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="font-tertiary text-2xl font-semibold text-neutral-900">{{ __('Posted projects') }}</h1>
                <p class="mt-1 text-sm text-tertiary">{{ __('Everything you have posted, who has applied, and what needs a decision.') }}</p>
            </div>
            <div class="flex shrink-0 flex-wrap items-center gap-2">
                <x-btn variant="secondary" size="sm" href="{{ route('my-jobs.archived.posted-jobs') }}">
                    <x-icon name="archive-box-2" class="h-4 w-4" />
                    {{ __('Archived') }}
                </x-btn>
                <x-btn size="sm" href="{{ route('jobs.create') }}">
                    <x-icon name="plus" class="h-4 w-4" />
                    {{ __('Post a project') }}
                </x-btn>
            </div>
        </div>

        @if ($counts['all'] === 0)
            <x-empty-state icon="document-text" :title="__('You have not posted any projects yet')"
                :description="__('Post your first project and freelancers can start sending you offers.')">
                <x-btn href="{{ route('jobs.create') }}">
                    <x-icon name="plus" class="h-4 w-4" />
                    {{ __('Post your first project') }}
                </x-btn>
            </x-empty-state>
        @else
            <!-- At a glance -->
            <dl class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
                @foreach ($tiles as $tile)
                    <div class="flex items-center gap-3 rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $tile['tone'] }}"><x-icon :name="$tile['icon']" class="h-5 w-5" /></span>
                        <div class="min-w-0">
                            <dd class="font-tertiary text-2xl font-semibold tabular-nums leading-none text-neutral-900">{{ $tile['value'] }}</dd>
                            <dt class="mt-1 text-xs leading-tight text-tertiary">{{ $tile['label'] }}</dt>
                        </div>
                    </div>
                @endforeach
            </dl>

            <!-- Search, status and sort -->
            <div class="mb-6 rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm">
                <form method="GET" action="{{ route('my-jobs.index') }}" role="search" class="flex flex-col gap-3 sm:flex-row">
                    @if ($activeTab !== 'all')
                        <input type="hidden" name="status" value="{{ $activeTab }}">
                    @endif
                    <div class="relative min-w-0 flex-1">
                        <x-icon name="magnifying-glass" class="pointer-events-none absolute left-3.5 top-2.5 h-5 w-5 text-neutral-400" />
                        <label for="search" class="sr-only">{{ __('Search your projects') }}</label>
                        <input type="search" id="search" name="search" value="{{ $filters['search'] }}" maxlength="100" placeholder="{{ __('Search your projects…') }}" class="{{ $fieldClass }} pl-11">
                    </div>
                    <label class="flex items-center gap-2 whitespace-nowrap text-sm text-tertiary">
                        <span class="hidden sm:inline">{{ __('Sort by') }}</span>
                        <select name="sort" onchange="this.form.submit()" class="{{ $fieldClass }} w-auto py-2 pr-8 font-medium">
                            @foreach ($sortOptions as $value => $label)
                                <option value="{{ $value }}" @selected($filters['sort'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <x-btn type="submit" class="sm:w-auto">{{ __('Search') }}</x-btn>
                </form>

                <nav class="mt-3 flex flex-wrap items-center gap-2" aria-label="{{ __('Project status') }}">
                    @foreach ($tabs as $key => $label)
                        <a href="{{ $tabUrl($key) }}" @if ($activeTab === $key) aria-current="page" @endif
                            @class([
                                'inline-flex items-center gap-2 rounded-full border px-3.5 py-1.5 text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40',
                                'border-teal-600 bg-teal-600 text-white' => $activeTab === $key,
                                'border-neutral-200 bg-white text-neutral-700 hover:border-neutral-300 hover:text-neutral-900' => $activeTab !== $key,
                            ])>
                            {{ $label }}
                            <span @class(['rounded-full px-1.5 text-xs font-semibold tabular-nums', 'bg-white/25 text-white' => $activeTab === $key, 'bg-neutral-100 text-neutral-600' => $activeTab !== $key])>{{ $counts[$key] }}</span>
                        </a>
                    @endforeach
                    @if ($hasFilters)
                        <a href="{{ route('my-jobs.index') }}" class="ml-1 text-sm font-medium text-teal-700 hover:text-teal-800 focus:outline-none focus-visible:underline">{{ __('Clear filters') }}</a>
                    @endif
                </nav>
            </div>

            @if ($postedJobs->isEmpty())
                <x-empty-state icon="magnifying-glass" :title="__('No projects match')" :description="__('Try a different search or show all your projects.')">
                    <x-btn variant="secondary" href="{{ route('my-jobs.index') }}">{{ __('Show all projects') }}</x-btn>
                </x-empty-state>
            @else
                <div class="grid grid-cols-1 items-start gap-6 xl:grid-cols-[minmax(0,1fr)_26rem]">
                    <!-- Projects -->
                    <div>
                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            @foreach ($postedJobs as $job)
                                @php
                                    [$statusLabel, $statusTone] = $job->listingStatus();
                                    $applications = $insights[$job->id] ?? collect();
                                    $deadlineSoon = $job->deadlineIsSoon();
                                    $skills = array_slice(array_values(array_filter((array) $job->skills)), 0, 2);
                                    $software = array_slice(array_values(array_filter((array) $job->software)), 0, 1);
                                    $moreTags = count(array_filter((array) $job->skills)) + count(array_filter((array) $job->software)) - count($skills) - count($software);
                                @endphp
                                <article @click="select({{ $job->id }}, @js(route('jobs.show', $job->slug)))"
                                    :class="selected === {{ $job->id }} ? 'ring-2 ring-teal-600 border-transparent' : 'border-neutral-200 hover:border-neutral-300'"
                                    class="group flex cursor-pointer flex-col overflow-hidden rounded-2xl border bg-white shadow-sm transition-shadow duration-200 hover:shadow-md">
                                    <div class="relative aspect-[16/9] bg-neutral-100">
                                        @if ($job->images)
                                            <img src="{{ asset('storage/'.$job->images) }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                        @else
                                            <span class="flex h-full w-full items-center justify-center text-neutral-300"><x-icon name="photo" class="h-10 w-10" /></span>
                                        @endif
                                        <x-badge :tone="$statusTone" class="absolute left-3 top-3 px-2.5 py-0.5 text-xs font-medium shadow-sm">{{ $statusLabel }}</x-badge>
                                        @if ($job->new_applications_count > 0)
                                            <span class="absolute right-3 top-3 rounded-full bg-teal-600 px-2.5 py-0.5 text-xs font-semibold text-white shadow-sm">{{ __(':count new', ['count' => $job->new_applications_count]) }}</span>
                                        @endif
                                    </div>

                                    <div class="flex flex-1 flex-col p-4">
                                        <h2 class="font-tertiary text-base font-semibold leading-snug text-neutral-900">
                                            <button type="button" @click.stop="select({{ $job->id }}, @js(route('jobs.show', $job->slug)))" class="rounded text-left transition-colors hover:text-teal-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">{{ $job->title }}</button>
                                        </h2>
                                        <p class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-tertiary">
                                            <span class="font-medium tabular-nums text-neutral-700"><x-money :amount="$job->budget" :decimals="0" /></span>
                                            <span @class(['inline-flex items-center gap-1', 'font-medium text-amber-700' => $deadlineSoon])>
                                                <x-icon name="calendar" class="h-3.5 w-3.5" />
                                                {{ $job->no_deadline || ! $job->deadline ? __('No deadline') : __('Due :date', ['date' => $job->deadline->format('M j')]) }}
                                            </span>
                                        </p>

                                        <ul class="mb-5 mt-3 flex flex-wrap gap-1.5">
                                            @foreach ($skills as $tag)
                                                <li class="rounded-full border border-neutral-200 bg-neutral-50 px-2.5 py-0.5 text-xs text-neutral-700">{{ $tag }}</li>
                                            @endforeach
                                            @foreach ($software as $tag)
                                                <li class="rounded-full border border-teal-100 bg-teal-50 px-2.5 py-0.5 text-xs text-teal-800">{{ $tag }}</li>
                                            @endforeach
                                            @if ($moreTags > 0)
                                                <li class="rounded-full bg-neutral-100 px-2.5 py-0.5 text-xs text-tertiary">+{{ $moreTags }}</li>
                                            @endif
                                        </ul>

                                        <div class="mt-auto flex items-center justify-between gap-3 border-t border-neutral-100 pt-4">
                                            <div class="flex items-center gap-2.5">
                                                @if ($applications->isNotEmpty())
                                                    <span class="flex -space-x-2">
                                                        @foreach ($applications->take(3) as $application)
                                                            <x-user-avatar :user="$application->applicant" size="h-7 w-7" class="!text-[10px] ring-2 ring-white" />
                                                        @endforeach
                                                    </span>
                                                @endif
                                                <span class="text-xs text-tertiary">{{ trans_choice(':count application|:count applications', $applications->count(), ['count' => $applications->count()]) }}</span>
                                            </div>
                                            <a href="{{ route('jobs.show', $job->slug) }}" @click.stop class="text-xs font-medium text-teal-700 hover:text-teal-800 focus:outline-none focus-visible:underline">{{ __('Open') }} →</a>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        @if ($postedJobs->hasPages())
                            <nav role="navigation" aria-label="{{ __('Pagination') }}" class="mt-6 flex items-center justify-between gap-3">
                                <p class="text-sm text-tertiary">{{ __('Showing :from–:to of :total', ['from' => $postedJobs->firstItem(), 'to' => $postedJobs->lastItem(), 'total' => $postedJobs->total()]) }}</p>
                                <div class="flex items-center gap-2">
                                    @if ($postedJobs->onFirstPage())
                                        <x-btn variant="secondary" size="sm" type="button" disabled>{{ __('Previous') }}</x-btn>
                                    @else
                                        <x-btn variant="secondary" size="sm" href="{{ $postedJobs->previousPageUrl() }}" rel="prev">{{ __('Previous') }}</x-btn>
                                    @endif
                                    <span class="px-1 text-sm tabular-nums text-tertiary">{{ $postedJobs->currentPage() }} / {{ $postedJobs->lastPage() }}</span>
                                    @if ($postedJobs->hasMorePages())
                                        <x-btn variant="secondary" size="sm" href="{{ $postedJobs->nextPageUrl() }}" rel="next">{{ __('Next') }}</x-btn>
                                    @else
                                        <x-btn variant="secondary" size="sm" type="button" disabled>{{ __('Next') }}</x-btn>
                                    @endif
                                </div>
                            </nav>
                        @endif
                    </div>

                    <!-- The selected project -->
                    <aside class="hidden xl:sticky xl:top-24 xl:block" aria-label="{{ __('Selected project') }}">
                        @foreach ($postedJobs as $job)
                            @php
                                [$statusLabel, $statusTone] = $job->listingStatus();
                                $applications = $insights[$job->id] ?? collect();
                                $byStatus = $applications->countBy(fn ($a) => $a->status->value);
                                $allSkills = array_values(array_filter((array) $job->skills));
                                $allSoftware = array_values(array_filter((array) $job->software));
                                $engagement = $job->engagements->sortByDesc('id')->first(fn ($e) => in_array($e->status, [EngagementStatus::Active, EngagementStatus::Disputed, EngagementStatus::Completed, EngagementStatus::EmployerAccepted], true));
                                $publicUrl = route('jobs.apply', $job->slug);
                                $canArchive = $job->canBeArchived();
                            @endphp
                            <section x-show="selected === {{ $job->id }}" @if (! $loop->first) x-cloak @endif
                                x-data="{ copied: false, async copy() { try { await navigator.clipboard.writeText(@js($publicUrl)); this.copied = true; setTimeout(() => this.copied = false, 2000); } catch (e) {} } }"
                                class="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm">
                                <div class="relative h-32 bg-neutral-100">
                                    @if ($job->images)
                                        <img src="{{ asset('storage/'.$job->images) }}" alt="" class="h-full w-full object-cover">
                                    @else
                                        <span class="flex h-full w-full items-center justify-center text-neutral-300"><x-icon name="photo" class="h-10 w-10" /></span>
                                    @endif
                                </div>

                                <div class="p-5">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <h2 class="font-tertiary text-lg font-semibold leading-snug text-neutral-900">{{ $job->title }}</h2>
                                            <p class="mt-1 text-xs text-tertiary">{{ __('Posted :time', ['time' => $job->created_at->diffForHumans()]) }}</p>
                                        </div>
                                        <x-badge :tone="$statusTone" class="shrink-0 px-2.5 py-0.5 text-xs font-medium">{{ $statusLabel }}</x-badge>
                                    </div>

                                    <div class="mt-4 flex flex-wrap items-center gap-2">
                                        <x-btn size="sm" href="{{ route('my-jobs.applications.index', $job->slug) }}">
                                            <x-icon name="user-group" class="h-4 w-4" />
                                            {{ __('Applications') }}
                                        </x-btn>
                                        <x-btn size="sm" variant="secondary" href="{{ route('jobs.edit', $job->slug) }}">
                                            <x-icon name="pencil-square" class="h-4 w-4" />
                                            {{ __('Edit') }}
                                        </x-btn>
                                        <x-btn size="sm" variant="secondary" type="button" @click="copy()" aria-label="{{ __('Copy the public link') }}">
                                            <x-icon name="link" class="h-4 w-4" />
                                            <span x-text="copied ? @js(__('Copied')) : @js(__('Copy link'))">{{ __('Copy link') }}</span>
                                        </x-btn>
                                    </div>

                                    <dl class="mt-5 grid grid-cols-2 gap-x-4 gap-y-3 border-y border-neutral-100 py-4 text-sm">
                                        <div>
                                            <dt class="text-xs text-tertiary">{{ __('Budget') }}</dt>
                                            <dd class="mt-0.5 font-tertiary font-semibold tabular-nums text-neutral-900"><x-money :amount="$job->budget" :decimals="0" /></dd>
                                        </div>
                                        <div>
                                            <dt class="text-xs text-tertiary">{{ __('Deadline') }}</dt>
                                            <dd @class(['mt-0.5 font-semibold', 'text-amber-700' => $job->deadlineIsSoon(), 'text-neutral-900' => ! $job->deadlineIsSoon()])>
                                                {{ $job->no_deadline || ! $job->deadline ? __('None') : $job->deadline->format('M j, Y') }}
                                            </dd>
                                        </div>
                                    </dl>

                                    <!-- Applicant pipeline -->
                                    <div class="mt-5">
                                        <div class="flex items-baseline justify-between">
                                            <h3 class="text-sm font-semibold text-neutral-900">{{ __('Applicants') }}</h3>
                                            <span class="text-xs text-tertiary">{{ trans_choice(':count application|:count applications', $applications->count(), ['count' => $applications->count()]) }}</span>
                                        </div>

                                        @if ($applications->isEmpty())
                                            <p class="mt-2 text-sm text-neutral-700">{{ __('No applications yet. Copy the link and share it so freelancers can find this project.') }}</p>
                                        @else
                                            <div class="mt-3 flex h-2 overflow-hidden rounded-full bg-neutral-100" role="img"
                                                aria-label="{{ $byStatus->map(fn ($n, $s) => $n.' '.ApplicationStatus::from($s)->label())->implode(', ') }}">
                                                @foreach ($applicationTone as $status => [$tone, $bar])
                                                    @if (($byStatus[$status] ?? 0) > 0)
                                                        <span class="{{ $bar }}" style="width: {{ round($byStatus[$status] / $applications->count() * 100, 2) }}%"></span>
                                                    @endif
                                                @endforeach
                                            </div>
                                            <ul class="mt-2.5 flex flex-wrap gap-x-4 gap-y-1 text-xs text-tertiary">
                                                @foreach ($applicationTone as $status => [$tone, $bar])
                                                    @if (($byStatus[$status] ?? 0) > 0)
                                                        <li class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full {{ $bar }}"></span>{{ ApplicationStatus::from($status)->label() }} <span class="font-semibold tabular-nums text-neutral-700">{{ $byStatus[$status] }}</span></li>
                                                    @endif
                                                @endforeach
                                            </ul>

                                            <ul class="mt-3 divide-y divide-neutral-100">
                                                @foreach ($applications->take(4) as $application)
                                                    <li>
                                                        <a href="{{ route('my-jobs.applications.show', $application) }}" class="-mx-2 flex items-center gap-3 rounded-lg px-2 py-2.5 transition-colors hover:bg-neutral-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">
                                                            <x-user-avatar :user="$application->applicant" size="h-8 w-8" />
                                                            <span class="min-w-0 flex-1">
                                                                <span class="block truncate text-sm font-medium text-neutral-900">{{ $application->applicant->name }}</span>
                                                                <span class="block text-xs text-tertiary"><x-money :amount="$application->offer_amount" :decimals="0" /> · {{ $application->created_at->diffForHumans() }}</span>
                                                            </span>
                                                            <x-badge :tone="$applicationTone[$application->status->value][0] ?? 'neutral'" class="px-2 py-0.5 text-xs font-medium">{{ $application->status->label() }}</x-badge>
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>

                                    @if ($engagement)
                                        <a href="{{ route('engagements.show', $engagement) }}" class="mt-4 flex items-center justify-between rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-3 text-sm transition-colors hover:border-neutral-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">
                                            <span class="font-medium text-neutral-900">{{ __('Open the engagement') }}</span>
                                            <x-icon name="arrow-right" class="h-4 w-4 text-neutral-400" />
                                        </a>
                                    @endif

                                    @if ($allSkills || $allSoftware)
                                        <div class="mt-5 border-t border-neutral-100 pt-4">
                                            <ul class="flex flex-wrap gap-1.5">
                                                @foreach ($allSkills as $tag)
                                                    <li class="rounded-full border border-neutral-200 bg-neutral-50 px-2.5 py-0.5 text-xs text-neutral-700">{{ $tag }}</li>
                                                @endforeach
                                                @foreach ($allSoftware as $tag)
                                                    <li class="rounded-full border border-teal-100 bg-teal-50 px-2.5 py-0.5 text-xs text-teal-800">{{ $tag }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif

                                    <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-neutral-100 pt-4 text-sm">
                                        <a href="{{ route('jobs.show', $job->slug) }}" class="font-medium text-teal-700 hover:text-teal-800 focus:outline-none focus-visible:underline">{{ __('Project overview') }} →</a>
                                        <span class="flex items-center gap-4">
                                            <a href="{{ $publicUrl }}" class="text-tertiary hover:text-neutral-800 focus:outline-none focus-visible:underline">{{ __('Public page') }}</a>
                                            @if ($canArchive)
                                                <form action="{{ route('my-jobs.archive', $job) }}" method="POST">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="text-tertiary hover:text-neutral-800 focus:outline-none focus-visible:underline">{{ __('Archive') }}</button>
                                                </form>
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </section>
                        @endforeach
                    </aside>
                </div>
            @endif
        @endif
    </div>
</x-app-layout>
