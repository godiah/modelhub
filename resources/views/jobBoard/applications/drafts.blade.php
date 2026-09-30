<x-app-layout crumb="Drafts">
    <div class="container mx-auto max-w-7xl px-4 py-8">
        <!-- Header -->
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="font-tertiary text-2xl font-semibold text-neutral-900">{{ __('Drafts') }}</h1>
                <p class="mt-1 max-w-2xl text-sm text-tertiary">{{ __('Applications you started but have not sent. Clients cannot see them. Finish one while the project is still open.') }}</p>
            </div>
            <x-btn variant="secondary" size="sm" href="{{ route('applications.my') }}" class="shrink-0">
                <x-icon name="arrow-left" class="h-4 w-4" />
                {{ __('My applications') }}
            </x-btn>
        </div>

        @if ($drafts->isEmpty())
            <x-empty-state icon="pencil-square" :title="__('No drafts')"
                :description="__('When you save an application without sending it, it waits here until you finish it.')">
                <x-btn href="{{ route('jobs.browse') }}">{{ __('Browse projects') }}</x-btn>
            </x-empty-state>
        @else
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($drafts as $draft)
                    @php
                        $job = $draft->job;
                        $open = $job->isOpenForApplications();
                        $hasOffer = (float) $draft->offer_amount > 0;
                    @endphp
                    <article x-data="{ deleting: false }" class="group flex flex-col overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm transition-shadow duration-200 hover:shadow-md">
                        <a href="{{ route('applications.continue', $job->slug) }}" tabindex="-1" aria-hidden="true" class="relative block aspect-[16/9] bg-neutral-100">
                            @if ($job->images)
                                <img src="{{ asset('storage/'.$job->images) }}" alt="" loading="lazy" @class(['h-full w-full object-cover transition duration-300', 'grayscale group-hover:grayscale-0' => ! $open])>
                            @else
                                <span class="flex h-full w-full items-center justify-center text-neutral-300"><x-icon name="photo" class="h-10 w-10" /></span>
                            @endif
                            <x-badge :tone="$open ? 'amber' : 'neutral'" class="absolute left-3 top-3 px-2.5 py-0.5 text-xs font-medium shadow-sm">
                                {{ $open ? __('Draft') : __('Project closed') }}
                            </x-badge>
                        </a>

                        <div class="flex flex-1 flex-col p-4">
                            <h2 class="font-tertiary text-base font-semibold leading-snug text-neutral-900">
                                <a href="{{ route('applications.continue', $job->slug) }}" class="rounded transition-colors hover:text-teal-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">{{ $job->title }}</a>
                            </h2>
                            <p class="mt-1.5 text-xs text-tertiary">
                                {{ __('Saved :time', ['time' => $draft->updated_at->diffForHumans()]) }}
                                · {{ __('Budget') }} <span class="font-medium tabular-nums text-neutral-700"><x-money :amount="$job->budget" :decimals="0" /></span>
                            </p>

                            <p class="mt-3 line-clamp-2 text-sm text-neutral-700">
                                @if (filled($draft->proposal))
                                    {{ $draft->proposal }}
                                @else
                                    <span class="text-tertiary">{{ __('No proposal written yet.') }}</span>
                                @endif
                            </p>

                            <dl class="mb-5 mt-3 flex flex-wrap gap-x-5 gap-y-1 text-xs text-tertiary">
                                <div><dt class="inline">{{ __('Offer') }}</dt> <dd class="inline font-medium tabular-nums text-neutral-800">@if ($hasOffer)<x-money :amount="$draft->offer_amount" :decimals="0" />@else — @endif</dd></div>
                                <div><dt class="inline">{{ __('Files') }}</dt> <dd class="inline font-medium tabular-nums text-neutral-800">{{ count((array) $draft->portfolio) }}</dd></div>
                            </dl>

                            <div class="mt-auto flex items-center justify-between gap-3 border-t border-neutral-100 pt-4">
                                <button type="button" @click="deleting = true" class="rounded px-1 text-xs text-tertiary hover:text-red-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-300">{{ __('Delete') }}</button>
                                <x-btn size="sm" href="{{ route('applications.continue', $job->slug) }}" :variant="$open ? 'primary' : 'secondary'">
                                    <x-icon name="pencil" class="h-4 w-4" />
                                    {{ $open ? __('Continue') : __('View draft') }}
                                </x-btn>
                            </div>
                        </div>

                        <x-confirm-dialog bind="deleting" title="Delete draft" confirm-label="Delete" method="DELETE"
                            :action="route('destroy.drafts', $draft)"
                            message="This removes the draft and any files you attached to it. Nothing was sent to the client." />
                    </article>
                @endforeach
            </div>

            <x-pager :paginator="$drafts" />
        @endif
    </div>
</x-app-layout>
