@use('App\Services\Admin\ProjectDirectoryService')
<x-staff-layout :title="__('Projects')">
    <div class="container mx-auto max-w-6xl px-4 py-8">
        <div class="mb-6">
            <h1 class="font-tertiary text-2xl font-semibold text-neutral-900">{{ __('Projects') }}</h1>
            <p class="mt-1 max-w-2xl text-sm text-tertiary">{{ __('Every project on the board, whatever its state. Open one to see who applied and, if you may moderate, to take it down.') }}</p>
        </div>

        <nav aria-label="{{ __('Filter by state') }}" class="-mx-4 mb-4 overflow-x-auto px-4 sm:mx-0 sm:px-0">
            <ul class="flex min-w-max items-center gap-2">
                @foreach (ProjectDirectoryService::STATUSES as $key => $label)
                    @php $on = $status === $key; @endphp
                    <li><a href="{{ route('admin.projects.index', array_filter(['status' => $key, 'q' => $term])) }}" @if ($on) aria-current="true" @endif
                        @class(['inline-flex items-center gap-2 rounded-full border px-3.5 py-1.5 text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40', 'border-teal-600 bg-teal-600 text-white' => $on, 'border-neutral-200 bg-white text-neutral-700 hover:border-neutral-300 hover:bg-neutral-50' => ! $on])>
                        {{ __($label) }}<span @class(['text-xs tabular-nums', 'text-teal-100' => $on, 'text-tertiary' => ! $on])>{{ number_format($counts[$key]) }}</span></a></li>
                @endforeach
            </ul>
        </nav>

        <form method="GET" action="{{ route('admin.projects.index') }}" role="search" class="mb-5 flex gap-2">
            <input type="hidden" name="status" value="{{ $status }}">
            <label for="q" class="sr-only">{{ __('Search projects') }}</label>
            <input id="q" type="search" name="q" value="{{ $term }}" placeholder="{{ __('Title or poster name') }}" class="min-w-0 flex-1 rounded-xl border border-neutral-300 px-3 py-2 text-sm focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25 sm:max-w-sm">
            <x-btn type="submit" variant="secondary">{{ __('Search') }}</x-btn>
        </form>

        @if ($projects->isEmpty())
            <x-empty-state icon="briefcase" :title="__('Nothing here')" :description="__('No projects match this filter.')" />
        @else
            <x-card clip>
                <ul class="divide-y divide-neutral-100">
                    @foreach ($projects as $project)
                        @php [$stateLabel, $stateTone] = ProjectDirectoryService::state($project); @endphp
                        <li>
                            <a href="{{ route('admin.projects.show', $project) }}" class="flex flex-wrap items-center gap-4 px-5 py-4 hover:bg-neutral-50 focus:outline-none focus-visible:bg-neutral-50">
                                <div class="min-w-0 flex-1">
                                    <p class="flex flex-wrap items-center gap-2 text-sm font-semibold text-neutral-900">{{ $project->title }}<x-badge :tone="$stateTone" class="px-2 py-0.5 text-xs font-medium">{{ __($stateLabel) }}</x-badge></p>
                                    <p class="mt-0.5 flex items-center gap-1.5 text-xs text-tertiary"><x-user-avatar :user="$project->user" size="h-4 w-4" />{{ $project->user?->name ?? __('Deleted account') }} · {{ __('posted :date', ['date' => $project->created_at->format('M j, Y')]) }}</p>
                                </div>
                                <p class="hidden w-28 text-right text-sm font-medium tabular-nums text-neutral-900 sm:block"><x-money :amount="$project->budget" :decimals="0" /></p>
                                <p class="w-24 text-right text-xs text-tertiary">{{ trans_choice(':count applicant|:count applicants', $project->applications_count, ['count' => $project->applications_count]) }}</p>
                                <p class="hidden w-28 text-right text-xs text-tertiary md:block">{{ $project->no_deadline || ! $project->deadline ? __('No deadline') : __('Due :date', ['date' => $project->deadline->format('M j')]) }}</p>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </x-card>
            <x-pager :paginator="$projects" />
        @endif
    </div>
</x-staff-layout>
