@php
    $fieldClass = 'block w-full rounded-xl border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 placeholder-neutral-400 transition-colors hover:border-neutral-400 focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25';
@endphp
<x-app-layout crumb="Archived">
    <div class="container mx-auto max-w-7xl px-4 py-8">
        <!-- Header -->
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="font-tertiary text-2xl font-semibold text-neutral-900">{{ __('Archived projects') }}</h1>
                <p class="mt-1 max-w-2xl text-sm text-tertiary">{{ __('Finished or closed projects you have put away. Freelancers cannot see them and they stay out of Posted projects. Restore one any time.') }}</p>
            </div>
            <x-btn variant="secondary" size="sm" href="{{ route('my-jobs.index') }}" class="shrink-0">
                <x-icon name="arrow-left" class="h-4 w-4" />
                {{ __('Posted projects') }}
            </x-btn>
        </div>

        @if ($archivedJobs->isEmpty() && $search === null)
            <x-empty-state icon="archive-box" :title="__('Nothing archived')"
                :description="__('When a project is closed you can archive it from Posted projects, and it will be kept here.')">
                <x-btn href="{{ route('my-jobs.index') }}">{{ __('Go to Posted projects') }}</x-btn>
            </x-empty-state>
        @else
            <form method="GET" action="{{ route('my-jobs.archived.posted-jobs') }}" role="search" class="mb-6 flex max-w-xl gap-3">
                <div class="relative min-w-0 flex-1">
                    <x-icon name="magnifying-glass" class="pointer-events-none absolute left-3.5 top-2.5 h-5 w-5 text-neutral-400" />
                    <label for="search" class="sr-only">{{ __('Search archived projects') }}</label>
                    <input type="search" id="search" name="search" value="{{ $search }}" maxlength="100" placeholder="{{ __('Search archived projects…') }}" class="{{ $fieldClass }} pl-11">
                </div>
                <x-btn type="submit">{{ __('Search') }}</x-btn>
                @if ($search !== null)
                    <x-btn variant="secondary" href="{{ route('my-jobs.archived.posted-jobs') }}">{{ __('Clear') }}</x-btn>
                @endif
            </form>

            @if ($archivedJobs->isEmpty())
                <x-empty-state icon="magnifying-glass" :title="__('No archived projects match')" :description="__('Try a different search.')" />
            @else
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($archivedJobs as $job)
                        @php
                            $skills = array_slice(array_values(array_filter((array) $job->skills)), 0, 2);
                            $software = array_slice(array_values(array_filter((array) $job->software)), 0, 1);
                            $moreTags = count(array_filter((array) $job->skills)) + count(array_filter((array) $job->software)) - count($skills) - count($software);
                        @endphp
                        <article x-data="{ showingRestore: false }" class="group flex flex-col overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm transition-shadow duration-200 hover:shadow-md">
                            <a href="{{ route('my-jobs.archived.show', $job) }}" tabindex="-1" aria-hidden="true" class="relative block aspect-[16/9] bg-neutral-100">
                                @if ($job->images)
                                    <img src="{{ asset('storage/'.$job->images) }}" alt="" loading="lazy" class="h-full w-full object-cover grayscale transition duration-300 group-hover:grayscale-0">
                                @else
                                    <span class="flex h-full w-full items-center justify-center text-neutral-300"><x-icon name="photo" class="h-10 w-10" /></span>
                                @endif
                                <x-badge tone="neutral" class="absolute left-3 top-3 px-2.5 py-0.5 text-xs font-medium shadow-sm">
                                    <x-icon name="archive-box-2" class="mr-1 h-3.5 w-3.5" />{{ __('Archived') }}
                                </x-badge>
                            </a>

                            <div class="flex flex-1 flex-col p-4">
                                <h2 class="font-tertiary text-base font-semibold leading-snug text-neutral-900">
                                    <a href="{{ route('my-jobs.archived.show', $job) }}" class="rounded transition-colors hover:text-teal-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">{{ $job->title }}</a>
                                </h2>
                                <p class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-tertiary">
                                    <span class="font-medium tabular-nums text-neutral-700"><x-money :amount="$job->budget" :decimals="0" /></span>
                                    <span class="inline-flex items-center gap-1">
                                        <x-icon name="calendar" class="h-3.5 w-3.5" />
                                        {{ $job->no_deadline || ! $job->deadline ? __('No deadline') : __('Due :date', ['date' => $job->deadline->format('M j, Y')]) }}
                                    </span>
                                    <span>{{ __('Posted :date', ['date' => $job->created_at->format('M j, Y')]) }}</span>
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
                                    <span class="text-xs text-tertiary">{{ trans_choice(':count application|:count applications', $job->applications_count, ['count' => $job->applications_count]) }}</span>
                                    <span class="flex items-center gap-2">
                                        <x-btn size="sm" variant="secondary" href="{{ route('my-jobs.archived.show', $job) }}">{{ __('View') }}</x-btn>
                                        <x-btn size="sm" type="button" @click="showingRestore = true">
                                            <x-icon name="arrow-path" class="h-4 w-4" />
                                            {{ __('Restore') }}
                                        </x-btn>
                                    </span>
                                </div>
                            </div>

                            <x-confirm-dialog bind="showingRestore" title="Restore project" icon="arrow-path" tone="success" confirm-label="Restore" method="PATCH"
                                :action="route('my-jobs.archived.restore', $job)"
                                message="This brings the project back to Posted projects. It will accept applications again unless a freelancer is working on it or its deadline has passed." />
                        </article>
                    @endforeach
                </div>

                @if ($archivedJobs->hasPages())
                    <nav role="navigation" aria-label="{{ __('Pagination') }}" class="mt-6 flex items-center justify-between gap-3">
                        <p class="text-sm text-tertiary">{{ __('Showing :from–:to of :total', ['from' => $archivedJobs->firstItem(), 'to' => $archivedJobs->lastItem(), 'total' => $archivedJobs->total()]) }}</p>
                        <div class="flex items-center gap-2">
                            @if ($archivedJobs->onFirstPage())
                                <x-btn variant="secondary" size="sm" type="button" disabled>{{ __('Previous') }}</x-btn>
                            @else
                                <x-btn variant="secondary" size="sm" href="{{ $archivedJobs->previousPageUrl() }}" rel="prev">{{ __('Previous') }}</x-btn>
                            @endif
                            <span class="px-1 text-sm tabular-nums text-tertiary">{{ $archivedJobs->currentPage() }} / {{ $archivedJobs->lastPage() }}</span>
                            @if ($archivedJobs->hasMorePages())
                                <x-btn variant="secondary" size="sm" href="{{ $archivedJobs->nextPageUrl() }}" rel="next">{{ __('Next') }}</x-btn>
                            @else
                                <x-btn variant="secondary" size="sm" type="button" disabled>{{ __('Next') }}</x-btn>
                            @endif
                        </div>
                    </nav>
                @endif
            @endif
        @endif
    </div>
</x-app-layout>
