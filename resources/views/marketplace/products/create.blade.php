@php
    $fieldClass = 'block w-full rounded-xl border bg-white px-4 py-2.5 text-sm text-neutral-900 placeholder-neutral-400 transition-colors focus:outline-none focus:ring-2';
    $okField = 'border-neutral-300 hover:border-neutral-400 focus:border-secondary focus:ring-secondary/25';
    $badField = 'border-red-400 focus:border-red-500 focus:ring-red-200';
@endphp
<x-app-layout title="Add a model" crumb="Add a model">
    <div class="container mx-auto max-w-3xl px-4 py-8">
        <div class="mb-6">
            <h1 class="font-tertiary text-2xl font-semibold text-neutral-900">{{ __('Add a model') }}</h1>
            <p class="mt-1 text-sm text-tertiary">{{ __('Start with the basics. Next you will upload preview images and your model files, and add the technical details.') }}</p>
        </div>

        <form action="{{ route('seller.models.store') }}" method="POST" x-data="{ free: {{ old('price', '') === '0' ? 'true' : 'false' }} }">
            @csrf
            <x-panel :title="__('The basics')">
                <div class="space-y-5">
                    <div>
                        <label for="title" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('Title') }}</label>
                        <input type="text" id="title" name="title" value="{{ old('title') }}" maxlength="150" required placeholder="{{ __('e.g. Mid-century oak armchair, PBR textures') }}"
                            class="{{ $fieldClass }} {{ $errors->has('title') ? $badField : $okField }}">
                        @error('title')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                        <p class="mt-1.5 text-xs text-tertiary">{{ __('Say what it is, and a standout feature. Buyers search by title.') }}</p>
                    </div>

                    @include('marketplace.products.partials.category-select', ['categories' => $categories, 'selected' => null])

                    <div>
                        <label for="price" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('Price') }}</label>
                        <div class="relative max-w-xs">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-sm text-neutral-500">{{ config('app.currency_symbol') }}</span>
                            <input type="number" id="price" name="price" value="{{ old('price') }}" min="0" max="1000000" step="0.01" inputmode="decimal" :disabled="free" placeholder="0.00"
                                class="{{ $fieldClass }} {{ $errors->has('price') ? $badField : $okField }} pl-14 tabular-nums disabled:bg-neutral-50 disabled:text-neutral-400">
                            <input type="hidden" name="price" value="0" x-bind:disabled="!free">
                        </div>
                        @error('price')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                        <label class="mt-2.5 flex items-center gap-2 text-sm text-neutral-700">
                            <input type="checkbox" x-model="free" class="h-4 w-4 rounded border-neutral-300 text-teal-600 focus:ring-teal-600/30">
                            {{ __('This model is free') }}
                        </label>
                    </div>
                </div>

                <x-slot:footer>
                    <x-btn variant="secondary" href="{{ route('seller.models.index') }}">{{ __('Cancel') }}</x-btn>
                    <x-btn type="submit">{{ __('Create draft') }}<x-icon name="arrow-right" class="h-4 w-4" /></x-btn>
                </x-slot:footer>
            </x-panel>
        </form>
    </div>
</x-app-layout>
