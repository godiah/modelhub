@use('App\Services\Admin\ProjectDirectoryService')
@php
    $tabs = collect(ProjectDirectoryService::STATUSES)->map(fn ($label, $key) => ['label' => $label, 'count' => $counts[$key], 'on' => $status === $key, 'url' => route('admin.projects.index', array_filter(['status' => $key, 'q' => $term, 'sort' => request('sort'), 'dir' => request('dir')]))])->values()->all();
    $chips = [$term !== '' ? ['label' => __('Search: :term', ['term' => $term]), 'remove' => ['q']] : null];
    $filtered = $term !== '' || $status !== 'all';
    $columns = [
        ['key' => 'title', 'label' => 'Project', 'sort' => 'title'],
        ['key' => 'budget', 'label' => 'Budget', 'sort' => 'budget', 'first' => 'desc', 'align' => 'right', 'class' => 'hidden sm:table-cell'],
        ['key' => 'applicants', 'label' => 'Applicants', 'sort' => 'applicants', 'first' => 'desc', 'align' => 'right'],
        ['key' => 'deadline', 'label' => 'Deadline', 'sort' => 'deadline', 'class' => 'hidden md:table-cell'],
        ['key' => 'posted', 'label' => 'Posted', 'sort' => 'posted', 'first' => 'desc', 'align' => 'right', 'class' => 'hidden lg:table-cell'],
    ];
    $actions = \App\Support\Staff\BulkActions::forPage('projects', auth()->user());
@endphp
<x-staff-layout :title="__('Projects')">
    <div class="container mx-auto max-w-7xl px-4 py-8">
        <x-staff.header :title="__('Projects')" :description="__('Every project on the board, whatever its state. Open one to see who applied and, if you may moderate, to take it down.')" />

        <x-staff.toolbar :tabs="$tabs" :search="$term" :placeholder="__('Title or poster name')" :chips="$chips" :action="route('admin.projects.index')" />

        @if ($projects->isEmpty())
            <x-empty-state icon="briefcase" :title="$filtered ? __('No projects match') : __('No projects yet')" :description="$filtered ? __('Try a different search or filter.') : __('Projects appear here as members post them.')">
                @if ($filtered)<x-btn variant="secondary" :href="route('admin.projects.index')" wire:navigate>{{ __('Clear filters') }}</x-btn>@endif
            </x-empty-state>
        @else
            <x-staff.bulk :actions="$actions" :ids="$projects->pluck('id')->all()">
            <x-staff.table :selectable="$actions !== []" :columns="$columns" :sort="$sort" :dir="$dir" :paginator="$projects" :summary="trans_choice(':count project|:count projects', $projects->total(), ['count' => number_format($projects->total())])">
                @foreach ($projects as $project)
                    @php [$stateLabel, $stateTone] = ProjectDirectoryService::state($project); @endphp
                    <x-staff.row :href="route('admin.projects.show', $project)" :select="$actions ? $project->id : null">
                        <td class="px-4">
                            <a href="{{ route('admin.projects.show', $project) }}" wire:navigate class="block focus:outline-none focus-visible:underline">
                                <span class="flex flex-wrap items-center gap-2 font-semibold text-neutral-900">{{ $project->title }}<x-badge :tone="$stateTone" class="px-2 py-0.5 text-xs font-medium">{{ __($stateLabel) }}</x-badge></span>
                                <span class="mt-0.5 flex items-center gap-1.5 text-xs font-normal text-tertiary"><x-user-avatar :user="$project->user" size="h-4 w-4" />{{ $project->user?->name ?? __('Deleted account') }}</span>
                            </a>
                        </td>
                        <td class="hidden whitespace-nowrap px-4 text-right font-medium tabular-nums text-neutral-900 sm:table-cell"><x-money :amount="$project->budget" :decimals="0" /></td>
                        <td class="whitespace-nowrap px-4 text-right tabular-nums text-neutral-700">{{ $project->applications_count }}</td>
                        <td class="hidden whitespace-nowrap px-4 text-neutral-600 md:table-cell">{{ $project->no_deadline || ! $project->deadline ? __('No deadline') : $project->deadline->format('M j, Y') }}</td>
                        <td class="hidden whitespace-nowrap px-4 text-right text-neutral-600 lg:table-cell">{{ $project->created_at->format('M j, Y') }}</td>
                    </x-staff.row>
                @endforeach
            </x-staff.table>
            </x-staff.bulk>
        @endif
    </div>
</x-staff-layout>
