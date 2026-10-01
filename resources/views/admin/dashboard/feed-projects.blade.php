<section aria-labelledby="feed-projects">
    <x-card clip>
        <div class="flex items-center justify-between gap-3 border-b border-neutral-100 px-5 py-4"><h2 id="feed-projects" class="font-tertiary text-base font-semibold text-neutral-900">{{ __('Projects') }}</h2><a href="{{ route('admin.projects.index') }}" class="text-sm font-medium text-teal-700 hover:text-teal-800">{{ __('Open directory') }}</a></div>
        <div class="p-5">
            <h3 class="text-xs font-semibold uppercase tracking-wider text-neutral-400">{{ __('Newly posted') }}</h3>
            <ul class="mt-2 divide-y divide-neutral-100">
                @foreach ($feed['newest'] as $project)
                    <li><a href="{{ route('admin.projects.show', $project) }}" class="flex items-center gap-3 py-2.5 text-sm hover:text-teal-700"><span class="min-w-0 flex-1"><span class="block truncate font-medium text-neutral-900">{{ $project->title }}</span><span class="block truncate text-xs text-tertiary">{{ $project->user?->name ?? __('Deleted account') }} · {{ $project->created_at->diffForHumans() }}</span></span><span class="shrink-0 text-xs tabular-nums text-neutral-700"><x-money :amount="$project->budget" :decimals="0" /></span><span class="w-20 shrink-0 text-right text-xs text-tertiary">{{ trans_choice(':count applicant|:count applicants', $project->applications_count, ['count' => $project->applications_count]) }}</span></a></li>
                @endforeach
            </ul>
            <ul class="mt-4 space-y-1 text-xs text-tertiary">
                @if ($feed['quiet'] > 0)<li><a href="{{ route('admin.projects.index', ['status' => 'open']) }}" class="hover:text-teal-700">{{ trans_choice(':count open project has had no applicants for over a week|:count open projects have had no applicants for over a week', $feed['quiet'], ['count' => $feed['quiet']]) }}</a></li>@endif
                @if ($feed['taken_down'] > 0)<li><a href="{{ route('admin.projects.index', ['status' => 'taken_down']) }}" class="hover:text-teal-700">{{ trans_choice(':count project is taken down|:count projects are taken down', $feed['taken_down'], ['count' => $feed['taken_down']]) }}</a></li>@endif
            </ul>
        </div>
    </x-card>
</section>
