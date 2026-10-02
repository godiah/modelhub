@php
    $application = $engagement->application;
    $skills = array_filter((array) $job->skills);
    $software = array_filter((array) $job->software);
    $description = \Illuminate\Support\Str::markdown((string) $job->description, ['html_input' => 'strip', 'allow_unsafe_links' => false]);
    $people = [
        ['label' => __('Client'), 'user' => $application->poster],
        ['label' => __('Freelancer'), 'user' => $application->applicant],
    ];
    $facts = [
        [__('Project budget'), $job->budget !== null ? \App\Support\Money::format($job->budget) : null],
        [__('Agreed amount'), \App\Support\Money::format($engagement->agreed_amount)],
        [__('Service fee'), \App\Support\Money::format($engagement->service_fee)],
        [__('Freelancer receives'), \App\Support\Money::format($engagement->net_amount)],
        [__('Deadline'), $job->deadline?->format('M j, Y') ?? ($job->no_deadline ? __('No deadline') : null)],
        [__('Posted'), $job->created_at?->format('M j, Y')],
    ];
@endphp

<div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
    <div class="space-y-6">
        <x-panel :title="__('Project description')">
            <div x-data="{ expanded: false, overflowing: false }"
                x-init="$nextTick(() => overflowing = $refs.body.scrollHeight > 260)">
                <div x-ref="body" :class="expanded ? '' : 'max-h-64 overflow-hidden'"
                    class="text-sm leading-relaxed text-neutral-700 [&_a]:text-teal-700 [&_a]:underline [&_blockquote]:border-l-4 [&_blockquote]:border-neutral-200 [&_blockquote]:pl-4 [&_blockquote]:text-neutral-600 [&_h1]:mb-2 [&_h1]:mt-4 [&_h1]:text-lg [&_h1]:font-semibold [&_h2]:mb-2 [&_h2]:mt-4 [&_h2]:text-base [&_h2]:font-semibold [&_h3]:mb-1 [&_h3]:mt-3 [&_h3]:font-semibold [&_ol]:mb-3 [&_ol]:list-decimal [&_ol]:pl-6 [&_p]:mb-3 [&_ul]:mb-3 [&_ul]:list-disc [&_ul]:pl-6">
                    {!! $description !!}
                </div>
                <button type="button" x-show="overflowing" x-cloak @click="expanded = !expanded"
                    class="mt-2 text-sm font-medium text-teal-700 hover:text-teal-800 focus:outline-none focus-visible:underline"
                    x-text="expanded ? @js(__('Show less')) : @js(__('Read more'))"></button>
            </div>

            @if ($skills || $software)
                <div class="mt-6 space-y-4 border-t border-neutral-100 pt-5">
                    @foreach ([__('Skills') => $skills, __('Software') => $software] as $label => $items)
                        @if ($items)
                            <div>
                                <h3 class="mb-2 text-xs font-medium uppercase tracking-wide text-tertiary">{{ $label }}</h3>
                                <ul class="flex flex-wrap gap-2">
                                    @foreach ($items as $item)
                                        <li class="rounded-full border border-neutral-200 bg-neutral-50 px-3 py-1 text-xs font-medium text-neutral-700">{{ $item }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif
        </x-panel>

        <x-panel :title="__('Proposal')" :description="__('What the freelancer offered when they applied.')">
            @if ($application->proposal)
                <div x-data="{ expanded: false, overflowing: false }"
                    x-init="$nextTick(() => overflowing = $refs.body.scrollHeight > 200)">
                    <div x-ref="body" :class="expanded ? '' : 'max-h-48 overflow-hidden'" class="text-sm leading-relaxed text-neutral-700">
                        {!! nl2br(e($application->proposal)) !!}
                    </div>
                    <button type="button" x-show="overflowing" x-cloak @click="expanded = !expanded"
                        class="mt-2 text-sm font-medium text-teal-700 hover:text-teal-800 focus:outline-none focus-visible:underline"
                        x-text="expanded ? @js(__('Show less')) : @js(__('Read more'))"></button>
                </div>
            @else
                <p class="text-sm text-tertiary">{{ __('The freelancer did not include a written proposal.') }}</p>
            @endif
            <p class="mt-4 border-t border-neutral-100 pt-4 text-sm text-neutral-700">
                {{ __('Offer at application:') }} <span class="font-semibold tabular-nums"><x-money :amount="$application->offer_amount" /></span>
            </p>
        </x-panel>
    </div>

    <div class="space-y-6">
        <x-panel :title="__('Key facts')">
            <dl class="divide-y divide-neutral-100 text-sm">
                @foreach ($facts as [$label, $value])
                    @if ($value)
                        <div class="flex items-center justify-between gap-4 py-2.5 first:pt-0 last:pb-0">
                            <dt class="text-tertiary">{{ $label }}</dt>
                            <dd class="text-right font-medium tabular-nums text-neutral-900">{{ $value }}</dd>
                        </div>
                    @endif
                @endforeach
            </dl>
        </x-panel>

        <x-engagement.escrow-panel :engagement="$engagement" />

        <x-panel :title="__('People')">
            <ul class="space-y-4">
                @foreach ($people as $person)
                    @if ($person['user'])
                        <li class="flex items-center gap-3">
                            <x-user-avatar :user="$person['user']" size="h-10 w-10" />
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-neutral-900">{{ $person['user']->name }}</p>
                                <p class="truncate text-xs text-tertiary">{{ $person['label'] }} · {{ $person['user']->email }}</p>
                            </div>
                        </li>
                    @endif
                @endforeach
            </ul>
        </x-panel>
    </div>
</div>
