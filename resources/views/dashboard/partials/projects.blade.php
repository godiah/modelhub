<x-card clip>
    <div class="flex items-center justify-between gap-3 border-b border-neutral-100 px-5 py-4">
        <h3 class="font-tertiary text-base font-semibold text-neutral-900">{{ __('Posted projects') }}</h3>
        <a href="{{ route('my-jobs.index') }}" wire:navigate
            class="text-sm font-medium text-teal-700 hover:text-teal-800">{{ __('View all') }}</a>
    </div>

    @if ($projects->isEmpty())
        <p class="px-5 py-6 text-sm text-tertiary">
            {{ __('No projects posted yet.') }}
            <a href="{{ route('jobs.create') }}" wire:navigate
                class="font-medium text-teal-700 hover:text-teal-800">{{ __('Post a project') }}</a>
        </p>
    @else
        <ul class="divide-y divide-neutral-100">
            @foreach ($projects as $project)
                <li>
                    <a href="{{ route('my-jobs.applications.index', $project->slug) }}" wire:navigate
                        class="flex items-center justify-between gap-3 px-5 py-3 transition-colors duration-150 hover:bg-neutral-50">
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-medium text-neutral-900">{{ $project->title }}</span>
                            <span class="block text-xs text-tertiary">{{ $project->created_at->diffForHumans() }}</span>
                        </span>
                        <span class="shrink-0 text-xs font-medium text-neutral-600">
                            {{ trans_choice(':count applicant|:count applicants', $project->applicants_count, ['count' => $project->applicants_count]) }}
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</x-card>
