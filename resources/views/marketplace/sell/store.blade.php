@php
    $fieldClass = 'block w-full rounded-xl border bg-white px-4 py-2.5 text-sm text-neutral-900 placeholder-neutral-400 transition-colors focus:outline-none focus:ring-2';
    $okField = 'border-neutral-300 hover:border-neutral-400 focus:border-secondary focus:ring-secondary/25';
    $badField = 'border-red-400 focus:border-red-500 focus:ring-red-200';
    $nextRename = $store->nextNameChangeAt();
@endphp
<x-app-layout title="My store">
    <div class="container mx-auto max-w-7xl px-4 py-8">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="font-tertiary text-2xl font-semibold text-neutral-900">{{ __('My store') }}</h1>
                <p class="mt-1 max-w-2xl text-sm text-tertiary">{{ __('What buyers see on your storefront and next to your models.') }}</p>
            </div>
            <x-btn variant="secondary" href="{{ route('sellers.show', $store->slug) }}" class="shrink-0"><x-icon name="eye" class="h-4 w-4" />{{ __('View storefront') }}</x-btn>
        </div>

        <form action="{{ route('seller.store.update') }}" method="POST" enctype="multipart/form-data"
            x-data="{
                name: @js(old('display_name', $store->display_name)),
                tagline: @js(old('tagline', $store->tagline ?? '')),
                logo: @js($store->logoUrl()),
                removeLogo: false,
                pick(event) {
                    const file = event.target.files[0];
                    if (! file) return;
                    this.removeLogo = false;
                    this.logo = URL.createObjectURL(file);
                },
                get initials() { return this.name.trim().split(/\s+/).slice(0, 2).map(w => w.charAt(0).toUpperCase()).join(''); },
            }">
            @csrf
            @method('PATCH')

            <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
                <div class="space-y-6">
                    <x-panel :title="__('Logo')" :description="__('A square image works best: at least 200 × 200 px, JPG, PNG or WebP, up to :mb MB.', ['mb' => config('marketplace.max_logo_mb')])">
                        <div class="flex items-center gap-5">
                            <template x-if="logo && ! removeLogo"><img :src="logo" alt="" class="h-20 w-20 shrink-0 rounded-2xl border border-neutral-200 object-cover"></template>
                            <template x-if="! logo || removeLogo"><span aria-hidden="true" class="flex h-20 w-20 shrink-0 items-center justify-center rounded-2xl border border-teal-700/15 bg-teal-50 font-tertiary text-2xl font-bold text-teal-800" x-text="initials"></span></template>
                            <div class="min-w-0">
                                <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-neutral-300 bg-white px-4 py-2 text-sm font-medium text-neutral-800 hover:border-neutral-400 focus-within:ring-2 focus-within:ring-secondary/40">
                                    <x-icon name="photo" class="h-4 w-4" />{{ __('Choose a logo') }}
                                    <input type="file" name="logo" accept=".jpg,.jpeg,.png,.webp" class="sr-only" @change="pick($event)">
                                </label>
                                @if ($store->logo_path)
                                    <label class="mt-3 flex items-center gap-2 text-sm text-neutral-700">
                                        <input type="checkbox" name="remove_logo" value="1" x-model="removeLogo" class="h-4 w-4 rounded border-neutral-300 text-teal-600 focus:ring-teal-600/30">
                                        {{ __('Remove my logo') }}
                                    </label>
                                @endif
                                @error('logo')<p class="mt-2 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </x-panel>

                    <x-panel :title="__('Store details')">
                        <div class="space-y-5">
                            <div>
                                <label for="display_name" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('Store name') }}</label>
                                <input type="text" id="display_name" name="display_name" x-model="name" maxlength="60" required
                                    class="{{ $fieldClass }} {{ $errors->has('display_name') ? $badField : $okField }}" @if ($errors->has('display_name')) aria-invalid="true" @endif>
                                @error('display_name')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                                <p class="mt-1.5 text-xs text-tertiary">
                                    @if ($nextRename)
                                        {{ __('You renamed your store recently. You can change the name again on :date.', ['date' => $nextRename->format('M j, Y')]) }}
                                    @else
                                        {{ __('You can rename your store once every :days days. Your storefront address stays the same.', ['days' => config('marketplace.name_change_days')]) }}
                                    @endif
                                </p>
                            </div>

                            <div>
                                <label for="tagline" class="mb-1.5 flex items-center justify-between text-sm font-medium text-neutral-800">
                                    <span>{{ __('Tagline') }} <span class="font-normal text-tertiary">({{ __('optional') }})</span></span>
                                    <span class="text-xs font-normal tabular-nums text-tertiary" x-text="tagline.length + ' / 80'">0 / 80</span>
                                </label>
                                <input type="text" id="tagline" name="tagline" x-model="tagline" maxlength="80" placeholder="{{ __('One line that says what you are about') }}"
                                    class="{{ $fieldClass }} {{ $errors->has('tagline') ? $badField : $okField }}">
                                @error('tagline')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="bio" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('About your store') }}</label>
                                <textarea id="bio" name="bio" rows="5" maxlength="1000" required class="{{ $fieldClass }} {{ $errors->has('bio') ? $badField : $okField }} resize-none">{{ old('bio', $store->bio) }}</textarea>
                                @error('bio')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="focus" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('What you make') }}</label>
                                <textarea id="focus" name="focus" rows="3" maxlength="500" required class="{{ $fieldClass }} {{ $errors->has('focus') ? $badField : $okField }} resize-none">{{ old('focus', $store->focus) }}</textarea>
                                @error('focus')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="website_url" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('Website') }} <span class="font-normal text-tertiary">({{ __('optional') }})</span></label>
                                <input type="url" id="website_url" name="website_url" value="{{ old('website_url', $store->website_url) }}" maxlength="255" placeholder="https://"
                                    class="{{ $fieldClass }} {{ $errors->has('website_url') ? $badField : $okField }}">
                                @error('website_url')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                                <p class="mt-1.5 text-xs text-tertiary">{{ __('Shown on your storefront. Separate from the portfolio link you gave when you applied, which only reviewers see.') }}</p>
                            </div>
                        </div>

                        <x-slot:footer>
                            <span class="text-xs text-tertiary">{{ __('Changes show on your storefront straight away.') }}</span>
                            <x-btn type="submit">{{ __('Save changes') }}</x-btn>
                        </x-slot:footer>
                    </x-panel>
                </div>

                <aside class="space-y-6 lg:sticky lg:top-24">
                    <x-panel :title="__('How buyers see you')">
                        <div class="flex items-center gap-4">
                            <template x-if="logo && ! removeLogo"><img :src="logo" alt="" class="h-14 w-14 shrink-0 rounded-xl border border-neutral-200 object-cover"></template>
                            <template x-if="! logo || removeLogo"><span aria-hidden="true" class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl border border-teal-700/15 bg-teal-50 font-tertiary text-lg font-bold text-teal-800" x-text="initials"></span></template>
                            <div class="min-w-0">
                                <p class="truncate font-tertiary text-base font-semibold text-neutral-900" x-text="name || @js(__('Your store name'))"></p>
                                <p class="truncate text-sm text-tertiary" x-text="tagline" x-show="tagline"></p>
                            </div>
                        </div>
                        <p class="mt-4 text-xs text-tertiary">{{ __('Your own name and email are never shown, only your store.') }}</p>
                    </x-panel>
                    <x-panel :title="__('Your storefront address')">
                        <p class="break-all rounded-lg bg-neutral-50 px-3 py-2 font-mono text-xs text-neutral-700">{{ route('sellers.show', $store->slug) }}</p>
                    </x-panel>
                </aside>
            </div>
        </form>
    </div>
</x-app-layout>
