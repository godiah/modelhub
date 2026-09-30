<section aria-labelledby="engagements-heading">
    <x-card clip>
        <div class="flex items-center justify-between gap-3 border-b border-neutral-100 px-5 py-4">
            <h3 id="engagements-heading" class="font-tertiary text-base font-semibold text-neutral-900">
                {{ __('Active engagements') }}</h3>
            <a href="{{ route('engagements.index') }}" wire:navigate
                class="text-sm font-medium text-teal-700 hover:text-teal-800">{{ __('View all') }}</a>
        </div>

        @if (count($engagements) === 0)
            <div class="flex flex-col items-center px-6 py-10 text-center">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-neutral-100 text-neutral-600">
                    <x-icon name="briefcase" class="h-6 w-6" />
                </span>
                <p class="mt-4 font-semibold text-neutral-900">{{ __('No active engagements yet') }}</p>
                <p class="mt-1 max-w-sm text-sm text-tertiary">
                    {{ __('Once an offer is accepted, the work in progress shows up here with its deliverables and deadlines.') }}
                </p>
                <div class="mt-5 flex flex-wrap justify-center gap-3">
                    <x-btn href="{{ route('jobs.browse') }}" class="text-sm shadow-sm">
                        <x-icon name="magnifying-glass" class="h-4 w-4" />
                        {{ __('Browse projects') }}
                    </x-btn>
                    <x-btn variant="secondary" href="{{ route('jobs.create') }}" class="text-sm shadow-sm">
                        <x-icon name="plus" class="h-4 w-4" />
                        {{ __('Post a project') }}
                    </x-btn>
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2">
                @foreach ($engagements as $engagement)
                    <a href="{{ $engagement['url'] }}" wire:navigate
                        class="block rounded-xl border border-neutral-200 p-4 transition-colors duration-150 hover:border-teal-600/40 hover:bg-neutral-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">
                        <div class="flex items-center justify-between gap-2">
                            <x-badge :tone="$engagement['role'] === __('Freelancer') ? 'blue' : 'neutral'"
                                class="px-2 py-0.5 text-xs font-medium">{{ $engagement['role'] }}</x-badge>
                            @if ($engagement['next_due'])
                                <span @class([
                                    'text-xs font-medium',
                                    'text-red-700' => $engagement['overdue'],
                                    'text-tertiary' => !$engagement['overdue'],
                                ])>
                                    {{ $engagement['overdue'] ? __('Overdue') : __('Next due') }}
                                    {{ $engagement['next_due']->format('M j') }}
                                </span>
                            @endif
                        </div>

                        <p class="mt-3 truncate font-semibold text-neutral-900">{{ $engagement['title'] }}</p>
                        @if ($engagement['counterpart'])
                            <p class="truncate text-sm text-tertiary">{{ __('with :name', ['name' => $engagement['counterpart']]) }}
                            </p>
                        @endif

                        <div class="mt-4 h-2 overflow-hidden rounded-full bg-neutral-100" role="progressbar"
                            aria-label="{{ __('Deliverables approved') }}" aria-valuemin="0" aria-valuemax="100"
                            aria-valuenow="{{ $engagement['percent'] }}">
                            <div class="h-full rounded-full bg-teal-600" style="width: {{ $engagement['percent'] }}%"></div>
                        </div>
                        <p class="mt-2 text-xs text-tertiary">
                            @if ($engagement['total'] > 0)
                                {{ __(':approved of :total deliverables approved', ['approved' => $engagement['approved'], 'total' => $engagement['total']]) }}
                            @else
                                {{ __('No deliverables yet') }}
                            @endif
                        </p>
                    </a>
                @endforeach
            </div>
        @endif
    </x-card>
</section>
