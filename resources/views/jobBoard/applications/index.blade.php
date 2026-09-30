@use('App\Enums\ApplicationStatus')
@php
    $fieldClass = 'block w-full rounded-xl border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 placeholder-neutral-400 transition-colors hover:border-neutral-400 focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25';
    $search = trim((string) $filters['search']);
    $filtering = $filters['status'] !== 'all' || $search !== '';
    $pills = [
        'all' => [__('All'), $counts['all']],
        ApplicationStatus::Submitted->value => [__('Submitted'), $counts['byStatus']['submitted'] ?? 0],
        ApplicationStatus::Reviewed->value => [__('Reviewed'), $counts['byStatus']['reviewed'] ?? 0],
        ApplicationStatus::Hired->value => [__('Hired'), $counts['byStatus']['hired'] ?? 0],
        ApplicationStatus::Rejected->value => [__('Rejected'), $counts['byStatus']['rejected'] ?? 0],
        ApplicationStatus::Withdrawn->value => [__('Withdrawn'), $counts['byStatus']['withdrawn'] ?? 0],
    ];
    $pillUrl = fn (string $status) => route('applications.my', array_filter([
        'status' => $status === 'all' ? null : $status,
        'sort' => $filters['sort'] === 'date_desc' ? null : $filters['sort'],
        'search' => $search ?: null,
    ]));
    $sorts = [
        'date_desc' => __('Newest first'),
        'date_asc' => __('Oldest first'),
        'status' => __('By status'),
        'offer_high' => __('Highest offer'),
        'offer_low' => __('Lowest offer'),
    ];
@endphp
<x-app-layout>
    <div class="container mx-auto max-w-7xl px-4 py-8">
        <!-- Header -->
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="font-tertiary text-2xl font-semibold text-neutral-900">{{ __('My applications') }}</h1>
                <p class="mt-1 max-w-2xl text-sm text-tertiary">{{ __('Everything you have sent to clients, and where each one stands.') }}</p>
            </div>
            <div class="flex shrink-0 flex-wrap items-center gap-2">
                <x-btn variant="secondary" size="sm" href="{{ route('applications.drafts') }}">
                    <x-icon name="pencil-square" class="h-4 w-4" />
                    {{ __('Drafts') }}
                    @if ($counts['drafts'] > 0)
                        <span class="rounded-full bg-neutral-100 px-1.5 text-xs tabular-nums text-neutral-700">{{ $counts['drafts'] }}</span>
                    @endif
                </x-btn>
                <x-btn variant="secondary" size="sm" href="{{ route('applications.archived') }}">
                    <x-icon name="archive-box-2" class="h-4 w-4" />
                    {{ __('Archived') }}
                    @if ($counts['archived'] > 0)
                        <span class="rounded-full bg-neutral-100 px-1.5 text-xs tabular-nums text-neutral-700">{{ $counts['archived'] }}</span>
                    @endif
                </x-btn>
            </div>
        </div>

        @if ($counts['all'] === 0)
            <x-empty-state icon="document-text" :title="__('No applications yet')"
                :description="$counts['drafts'] > 0 ? __('You have saved drafts waiting to be finished. Send one when it is ready.') : __('Find a project that suits you and send the client your offer. It will show up here.')">
                <div class="flex flex-wrap justify-center gap-3">
                    <x-btn href="{{ route('jobs.browse') }}">{{ __('Browse projects') }}</x-btn>
                    @if ($counts['drafts'] > 0)
                        <x-btn variant="secondary" href="{{ route('applications.drafts') }}">{{ __('Open drafts') }}</x-btn>
                    @endif
                </div>
            </x-empty-state>
        @else
            <!-- Status filters -->
            <nav aria-label="{{ __('Filter by status') }}" class="-mx-4 mb-4 overflow-x-auto px-4 sm:mx-0 sm:px-0">
                <ul class="flex min-w-max items-center gap-2">
                    @foreach ($pills as $status => [$label, $total])
                        @continue($status !== 'all' && $total === 0 && $filters['status'] !== $status)
                        @php $active = $filters['status'] === $status; @endphp
                        <li>
                            <a href="{{ $pillUrl($status) }}" @if ($active) aria-current="true" @endif
                                @class([
                                    'inline-flex items-center gap-2 rounded-full border px-3.5 py-1.5 text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40',
                                    'border-teal-600 bg-teal-600 text-white' => $active,
                                    'border-neutral-200 bg-white text-neutral-700 hover:border-neutral-300 hover:bg-neutral-50' => ! $active,
                                ])>
                                {{ $label }}
                                <span @class(['text-xs tabular-nums', 'text-teal-100' => $active, 'text-tertiary' => ! $active])>{{ $total }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            <!-- Search and sort -->
            <form method="GET" action="{{ route('applications.my') }}" role="search" class="mb-6 flex flex-col gap-3 sm:flex-row">
                @if ($filters['status'] !== 'all')
                    <input type="hidden" name="status" value="{{ $filters['status'] }}">
                @endif
                <div class="relative min-w-0 flex-1">
                    <x-icon name="magnifying-glass" class="pointer-events-none absolute left-3.5 top-2.5 h-5 w-5 text-neutral-400" />
                    <label for="search" class="sr-only">{{ __('Search your applications') }}</label>
                    <input type="search" id="search" name="search" value="{{ $search }}" maxlength="100" placeholder="{{ __('Search by project title…') }}" class="{{ $fieldClass }} pl-11">
                </div>
                <div class="flex gap-3">
                    <label for="sort" class="sr-only">{{ __('Sort applications') }}</label>
                    <select id="sort" name="sort" onchange="this.form.requestSubmit()" class="{{ $fieldClass }} sm:w-44">
                        @foreach ($sorts as $value => $label)
                            <option value="{{ $value }}" @selected($filters['sort'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-btn type="submit">{{ __('Search') }}</x-btn>
                    @if ($filtering)
                        <x-btn variant="secondary" href="{{ route('applications.my') }}">{{ __('Clear') }}</x-btn>
                    @endif
                </div>
            </form>

            @if ($applications->isEmpty())
                <x-empty-state icon="magnifying-glass" :title="__('No applications match')" :description="__('Try a different status or search.')">
                    <x-btn variant="secondary" href="{{ route('applications.my') }}">{{ __('Show all applications') }}</x-btn>
                </x-empty-state>
            @else
                <div class="space-y-3">
                    @foreach ($applications as $application)
                        <x-applications.row :application="$application" />
                    @endforeach
                </div>

                <x-pager :paginator="$applications" />
            @endif
        @endif
    </div>
</x-app-layout>
