<x-staff-layout :title="__('Hires')">
    <div class="container mx-auto max-w-6xl px-4 py-8">
        <div class="mb-6">
            <h1 class="font-tertiary text-2xl font-semibold text-neutral-900">{{ __('Hires') }}</h1>
            <p class="mt-1 max-w-2xl text-sm text-tertiary">{{ __('Every engagement between a client and a freelancer, read-only. For spotting work that is stuck or at risk; disputes are handled under Payment disputes. Private messages are not shown.') }}</p>
        </div>

        <nav aria-label="{{ __('Filter by status') }}" class="-mx-4 mb-4 overflow-x-auto px-4 sm:mx-0 sm:px-0">
            <ul class="flex min-w-max items-center gap-2">
                @foreach (['all' => __('All')] + $statuses as $key => $label)
                    @php $on = $status === $key; $count = $key === 'all' ? $counts->sum() : ($counts[$key] ?? 0); @endphp
                    <li><a href="{{ route('admin.engagements.index', array_filter(['status' => $key, 'q' => $term])) }}" @if ($on) aria-current="true" @endif
                        @class(['inline-flex items-center gap-2 rounded-full border px-3.5 py-1.5 text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40', 'border-teal-600 bg-teal-600 text-white' => $on, 'border-neutral-200 bg-white text-neutral-700 hover:border-neutral-300 hover:bg-neutral-50' => ! $on])>
                        {{ __($label) }}<span @class(['text-xs tabular-nums', 'text-teal-100' => $on, 'text-tertiary' => ! $on])>{{ $count }}</span></a></li>
                @endforeach
            </ul>
        </nav>

        <form method="GET" action="{{ route('admin.engagements.index') }}" role="search" class="mb-5 flex gap-2">
            <input type="hidden" name="status" value="{{ $status }}">
            <label for="q" class="sr-only">{{ __('Search hires') }}</label>
            <input id="q" type="search" name="q" value="{{ $term }}" placeholder="{{ __('Project title or either person\'s name') }}" class="min-w-0 flex-1 rounded-xl border border-neutral-300 px-3 py-2 text-sm focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25 sm:max-w-sm">
            <x-btn type="submit" variant="secondary">{{ __('Search') }}</x-btn>
        </form>

        @if ($engagements->isEmpty())
            <x-empty-state icon="chat-bubble-left-right" :title="__('Nothing here')" :description="__('No hires match this filter.')" />
        @else
            <x-card clip>
                <ul class="divide-y divide-neutral-100">
                    @foreach ($engagements as $engagement)
                        @php $application = $engagement->application; @endphp
                        <li>
                            <a href="{{ route('admin.engagements.show', $engagement) }}" class="flex flex-wrap items-center gap-4 px-5 py-4 hover:bg-neutral-50 focus:outline-none focus-visible:bg-neutral-50">
                                <div class="min-w-0 flex-1">
                                    <p class="flex flex-wrap items-center gap-2 text-sm font-semibold text-neutral-900">{{ $application->job?->title ?? __('A deleted project') }}<x-badge :tone="match ($engagement->status->value) { 'active' => 'blue', 'completed', 'settled' => 'green', 'disputed' => 'red', 'cancelled' => 'neutral', default => 'amber' }" class="px-2 py-0.5 text-xs font-medium">{{ $engagement->status->label() }}</x-badge></p>
                                    <p class="mt-0.5 text-xs text-tertiary">{{ $application->poster?->name ?? '—' }} <span aria-hidden="true">→</span> {{ $application->applicant?->name ?? '—' }}</p>
                                </div>
                                <p class="w-28 text-right text-sm font-medium tabular-nums text-neutral-900"><x-money :amount="$engagement->agreed_amount" :decimals="0" /></p>
                                <p class="hidden w-28 text-right text-xs text-tertiary md:block">{{ $engagement->payment_released_at ? __('Paid out') : ($engagement->payment_escrowed_at ? __('In escrow') : __('Not funded')) }}</p>
                                <p class="w-28 text-right text-xs text-tertiary">{{ __('Started :date', ['date' => ($engagement->started_at ?? $engagement->created_at)->format('M j')]) }}</p>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </x-card>
            <x-pager :paginator="$engagements" />
        @endif
    </div>
</x-staff-layout>
