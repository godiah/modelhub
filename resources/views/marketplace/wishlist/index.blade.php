<x-app-layout title="Wishlist">
    <div class="container mx-auto max-w-7xl px-4 py-8">
        <div class="mb-6">
            <h1 class="font-tertiary text-2xl font-semibold text-neutral-900">{{ __('Wishlist') }}</h1>
            <p class="mt-1 text-sm text-tertiary">{{ __('Models you saved to come back to. A model that is taken down disappears from here until it is back.') }}</p>
        </div>

        @if ($items->isEmpty())
            <x-empty-state icon="heart" :title="__('Nothing saved yet')" :description="__('Tap the heart on a model to keep it here.')">
                <x-btn href="{{ route('models.index') }}">{{ __('Browse models') }}</x-btn>
            </x-empty-state>
        @else
            <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                @foreach ($items as $item)
                    <x-models.card :product="$item->product" :saved="true" />
                @endforeach
            </div>
            <x-pager :paginator="$items" />
        @endif
    </div>
</x-app-layout>
