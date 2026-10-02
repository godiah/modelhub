<x-app-layout title="My licences">
    <div class="container mx-auto max-w-7xl px-4 py-8">
        <div class="mb-6">
            <h1 class="font-tertiary text-2xl font-semibold text-neutral-900">{{ __('My licences') }}</h1>
            <p class="mt-1 max-w-2xl text-sm text-tertiary">{{ __('Each model you buy comes with a licence that says what you may do with it. Open one to see the full terms, or print it for your records.') }} <a href="{{ route('legal.licences') }}" class="font-medium text-teal-700 hover:underline">{{ __('Compare the licences') }}</a></p>
        </div>

        @if ($licences->isEmpty())
            <x-empty-state icon="document-text" :title="__('No licences yet')" :description="__('When you buy or download a model, its licence appears here.')">
                <x-btn :href="route('models.index')" wire:navigate>{{ __('Browse models') }}</x-btn>
            </x-empty-state>
        @else
            <x-card clip>
                <ul class="divide-y divide-neutral-100">
                    @foreach ($licences as $licence)
                        <li>
                            <a href="{{ route('licences.show', $licence) }}" wire:navigate class="flex flex-wrap items-center gap-4 px-5 py-4 hover:bg-neutral-50 focus:outline-none focus-visible:bg-neutral-50">
                                <div class="min-w-0 flex-1">
                                    <p class="flex flex-wrap items-center gap-2 text-sm font-semibold text-neutral-900">{{ $licence->product_title }}
                                        <x-badge :tone="$licence->tier->value === 'extended' ? 'blue' : 'neutral'" class="px-2 py-0.5 text-xs font-medium">{{ __($licence->tier->label()) }}</x-badge>
                                        @unless ($licence->isActive())<x-badge tone="red" class="px-2 py-0.5 text-xs font-medium">{{ __('Ended') }}</x-badge>@endunless
                                    </p>
                                    <p class="mt-0.5 truncate text-xs text-tertiary">{{ __('From :seller', ['seller' => $licence->seller_name]) }} · <span class="font-mono">{{ $licence->key }}</span></p>
                                </div>
                                <p class="w-24 text-right text-sm font-medium tabular-nums text-neutral-900">{{ $licence->price_minor === 0 ? __('Free') : \App\Support\Money::formatMinor($licence->price_minor, 0) }}</p>
                                <p class="w-28 text-right text-xs text-tertiary">{{ $licence->issued_at->format('M j, Y') }}</p>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </x-card>
            <x-pager :paginator="$licences" navigate />
        @endif
    </div>
</x-app-layout>
