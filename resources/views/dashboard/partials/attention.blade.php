@php
    $tones = [
        'amber' => 'bg-amber-50 text-amber-700',
        'red' => 'bg-red-50 text-red-700',
        'blue' => 'bg-blue-50 text-blue-700',
    ];
@endphp

<section id="attention" aria-labelledby="attention-heading" class="scroll-mt-24">
    <x-card clip>
        <div class="flex items-center justify-between gap-3 border-b border-neutral-100 px-5 py-4">
            <h3 id="attention-heading" class="font-tertiary text-base font-semibold text-neutral-900">
                {{ __('Needs your attention') }}</h3>
            @if (count($items) > 0)
                <x-badge tone="amber" class="px-2.5 py-0.5 text-xs font-semibold">{{ count($items) + $more }}</x-badge>
            @endif
        </div>

        @if (count($items) === 0)
            <div class="flex flex-col items-center px-6 py-10 text-center">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-teal-50 text-teal-700">
                    <x-icon name="check-circle" class="h-6 w-6" />
                </span>
                <p class="mt-4 font-semibold text-neutral-900">{{ __("You're all caught up") }}</p>
                <p class="mt-1 max-w-sm text-sm text-tertiary">
                    {{ __('Offers, deliverables to review, deadlines and new applicants will show up here when they need you.') }}
                </p>
            </div>
        @else
            <ul class="divide-y divide-neutral-100">
                @foreach ($items as $item)
                    <li>
                        <a href="{{ $item['url'] }}" wire:navigate
                            class="flex items-center gap-4 px-5 py-4 transition-colors duration-150 hover:bg-neutral-50 focus:outline-none focus-visible:bg-neutral-50 focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-secondary/40">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $tones[$item['tone']] }}">
                                <x-icon :name="$item['icon']" class="h-5 w-5" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-semibold text-neutral-900">{{ $item['title'] }}</span>
                                <span class="block truncate text-sm text-tertiary">{{ $item['detail'] }}</span>
                            </span>
                            <span
                                class="hidden shrink-0 items-center gap-1 text-sm font-medium text-teal-700 sm:inline-flex">
                                {{ $item['cta'] }}
                                <x-icon name="chevron-right" class="h-4 w-4" />
                            </span>
                            <x-icon name="chevron-right" class="h-4 w-4 shrink-0 text-neutral-400 sm:hidden" />
                        </a>
                    </li>
                @endforeach
            </ul>

            @if ($more > 0)
                <p class="border-t border-neutral-100 px-5 py-3 text-center text-sm text-tertiary">
                    {{ __('+:count more not shown', ['count' => $more]) }}</p>
            @endif
        @endif
    </x-card>
</section>
