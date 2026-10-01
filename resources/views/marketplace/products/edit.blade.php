@use('App\Enums\ProductStatus')
@use('App\Enums\GeometryType')
@use('App\Enums\UvLayout')
@php
    $editable = $product->status->isEditable();
    $fieldClass = 'block w-full rounded-xl border bg-white px-4 py-2.5 text-sm text-neutral-900 placeholder-neutral-400 transition-colors focus:outline-none focus:ring-2';
    $okField = 'border-neutral-300 hover:border-neutral-400 focus:border-secondary focus:ring-secondary/25';
    $badField = 'border-red-400 focus:border-red-500 focus:ring-red-200';
    $maxMb = config('marketplace.max_file_mb');
    $formats = collect(config('marketplace.formats'))->flatten()->map(fn ($ext) => '.'.$ext)->implode(',');
    $images = $product->images->map(fn ($image) => [
        'id' => $image->id, 'url' => $image->url(),
        'delete_url' => route('seller.models.images.destroy', [$product, $image]),
        'cover_url' => route('seller.models.images.cover', [$product, $image]),
    ])->values();
    $files = $product->files->map(fn ($file) => [
        'id' => $file->id, 'name' => $file->original_name, 'kind' => $file->kind, 'size' => $file->readableSize(),
        'delete_url' => route('seller.models.files.destroy', [$product, $file]),
    ])->values();
    $priceValue = old('price', $product->price_minor ? rtrim(rtrim(number_format($product->price_minor / 100, 2, '.', ''), '0'), '.') : '');
    $isFree = old('price') !== null ? (string) old('price') === '0' : $product->price_minor === 0;
    $selectedSoftware = old('software', $product->software->pluck('id')->all());
@endphp
<x-app-layout :title="$product->title" :crumb="$product->title">
    <div class="container mx-auto max-w-7xl px-4 py-8" x-data="{ deleting: false }">
        <!-- Header -->
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-medium uppercase tracking-wide text-tertiary">{{ __('Model') }}</p>
                <div class="mt-1 flex flex-wrap items-center gap-3">
                    <h1 class="font-tertiary text-2xl font-semibold text-neutral-900">{{ $product->title }}</h1>
                    <x-badge :tone="$product->status->tone()" class="px-2.5 py-0.5 text-xs font-medium">{{ __($product->status->label()) }}</x-badge>
                </div>
            </div>
            <x-btn variant="secondary" size="sm" href="{{ route('seller.models.index') }}" class="shrink-0"><x-icon name="arrow-left" class="h-4 w-4" />{{ __('My models') }}</x-btn>
        </div>

        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <div class="space-y-6">
                @if ($editable)
                    <x-panel :title="__('Preview images')" :description="__('The first image is the cover buyers see in the catalogue. At least 400 × 300 px, JPG, PNG or WebP, up to :mb MB each.', ['mb' => config('marketplace.max_image_mb')])">
                        @include('marketplace.products.partials.uploader', [
                            'kind' => 'images', 'url' => route('seller.models.images.store', $product), 'field' => 'image', 'items' => $images,
                            'max' => config('marketplace.max_images'), 'maxMb' => config('marketplace.max_image_mb'),
                            'accept' => '.jpg,.jpeg,.png,.webp', 'label' => __('Choose images'), 'hint' => __('Up to :max images', ['max' => config('marketplace.max_images')]),
                        ])
                    </x-panel>

                    <x-panel :title="__('Model files')" :description="__('What the buyer downloads: your model in its native format and exchange formats such as FBX or OBJ, with textures. Up to :mb MB per file; compress or split larger models.', ['mb' => $maxMb])">
                        @include('marketplace.products.partials.uploader', [
                            'kind' => 'files', 'url' => route('seller.models.files.store', $product), 'field' => 'file', 'items' => $files,
                            'max' => config('marketplace.max_files'), 'maxMb' => $maxMb,
                            'accept' => $formats, 'label' => __('Choose files'), 'hint' => __('Up to :max files, :mb MB each', ['max' => config('marketplace.max_files'), 'mb' => $maxMb]),
                        ])
                    </x-panel>

                    <form id="product-form" action="{{ route('seller.models.update', $product) }}" method="POST" class="space-y-6" x-data="{ free: {{ $isFree ? 'true' : 'false' }} }">
                        @csrf
                        @method('PATCH')

                        <x-panel :title="__('Basics')">
                            <div class="space-y-5">
                                <div>
                                    <label for="title" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('Title') }}</label>
                                    <input type="text" id="title" name="title" value="{{ old('title', $product->title) }}" maxlength="150" required
                                        class="{{ $fieldClass }} {{ $errors->has('title') ? $badField : $okField }}">
                                    @error('title')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                                </div>

                                @include('marketplace.products.partials.category-select', ['categories' => $categories, 'selected' => $product->category])

                                <div>
                                    <label for="description" class="mb-1.5 flex items-center justify-between text-sm font-medium text-neutral-800">
                                        <span>{{ __('Description') }}</span><span class="text-xs font-normal text-tertiary">{{ __('Markdown supported') }}</span>
                                    </label>
                                    <textarea id="description" name="description" rows="10" maxlength="20000" placeholder="{{ __('What is included, how it was made, the formats and any limits. Be specific: it helps buyers decide and helps search.') }}"
                                        class="{{ $fieldClass }} {{ $errors->has('description') ? $badField : $okField }}">{{ old('description', $product->description) }}</textarea>
                                    @error('description')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                                </div>

                                <div>
                                    <label for="tags" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('Tags') }}</label>
                                    <input type="text" id="tags" name="tags" value="{{ old('tags', implode(', ', $product->tags ?? [])) }}" maxlength="500" placeholder="{{ __('armchair, oak, mid-century, living room') }}"
                                        class="{{ $fieldClass }} {{ $errors->has('tags') ? $badField : $okField }}">
                                    <p class="mt-1.5 text-xs text-tertiary">{{ __('Separate with commas. Up to :max tags.', ['max' => config('marketplace.max_tags')]) }}</p>
                                </div>
                            </div>
                        </x-panel>

                        <x-panel :title="__('Technical details')" :description="__('Accurate details help buyers filter, and save you questions.')">
                            <div class="space-y-6">
                                <fieldset>
                                    <legend class="mb-2 text-sm font-medium text-neutral-800">{{ __('Features') }}</legend>
                                    <div class="grid gap-x-6 gap-y-2.5 sm:grid-cols-2 lg:grid-cols-3">
                                        @foreach (\App\Models\Product::FEATURES as $field => $label)
                                            <label class="flex items-center gap-2.5 text-sm text-neutral-700">
                                                <input type="hidden" name="{{ $field }}" value="0">
                                                <input type="checkbox" name="{{ $field }}" value="1" @checked(old($field, $product->{$field})) class="h-4 w-4 rounded border-neutral-300 text-teal-600 focus:ring-teal-600/30">
                                                {{ __($label) }}
                                            </label>
                                        @endforeach
                                    </div>
                                </fieldset>

                                <div class="grid gap-5 sm:grid-cols-2">
                                    <div>
                                        <label for="geometry_type" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('Geometry') }}</label>
                                        <select id="geometry_type" name="geometry_type" class="{{ $fieldClass }} {{ $okField }}">
                                            <option value="">{{ __('Not specified') }}</option>
                                            @foreach (GeometryType::cases() as $case)
                                                <option value="{{ $case->value }}" @selected(old('geometry_type', $product->geometry_type?->value) === $case->value)>{{ $case->label() }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label for="uv_layout" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('Unwrapped UVs') }}</label>
                                        <select id="uv_layout" name="uv_layout" class="{{ $fieldClass }} {{ $okField }}">
                                            <option value="">{{ __('Not specified') }}</option>
                                            @foreach (UvLayout::cases() as $case)
                                                <option value="{{ $case->value }}" @selected(old('uv_layout', $product->uv_layout?->value) === $case->value)>{{ $case->label() }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label for="polygons" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('Polygons') }}</label>
                                        <input type="number" id="polygons" name="polygons" value="{{ old('polygons', $product->polygons) }}" min="0" step="1" inputmode="numeric" class="{{ $fieldClass }} {{ $errors->has('polygons') ? $badField : $okField }} tabular-nums">
                                        @error('polygons')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label for="vertices" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('Vertices') }}</label>
                                        <input type="number" id="vertices" name="vertices" value="{{ old('vertices', $product->vertices) }}" min="0" step="1" inputmode="numeric" class="{{ $fieldClass }} {{ $errors->has('vertices') ? $badField : $okField }} tabular-nums">
                                        @error('vertices')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label for="render_engine" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('Render engine') }} <span class="font-normal text-tertiary">({{ __('optional') }})</span></label>
                                        <input type="text" id="render_engine" name="render_engine" value="{{ old('render_engine', $product->render_engine) }}" maxlength="80" placeholder="{{ __('e.g. Cycles 4.2, V-Ray 6') }}" class="{{ $fieldClass }} {{ $okField }}">
                                    </div>
                                </div>

                                <x-tag-picker field="software" name="software[]" :label="__('Compatible software')" :items="$software->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values()->all()"
                                    :selected="array_map('intval', (array) $selectedSoftware)" :placeholder="__('Search software…')" :hint="__('The programs this model opens in.')" />
                            </div>
                        </x-panel>

                        <x-panel :title="__('Price')" :description="__('Amounts are in :currency. Free models are fine.', ['currency' => config('marketplace.currency')])">
                            <div class="grid gap-5 sm:grid-cols-2">
                                <div>
                                    <label for="price" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('Price') }}</label>
                                    <div class="relative">
                                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-sm text-neutral-500">{{ config('app.currency_symbol') }}</span>
                                        <input type="number" id="price" name="price" value="{{ $isFree ? '' : $priceValue }}" min="0" max="1000000" step="0.01" inputmode="decimal" :disabled="free" placeholder="0.00"
                                            class="{{ $fieldClass }} {{ $errors->has('price') ? $badField : $okField }} pl-14 tabular-nums disabled:bg-neutral-50 disabled:text-neutral-400">
                                        <input type="hidden" name="price" value="0" x-bind:disabled="!free">
                                    </div>
                                    @error('price')<p class="mt-1.5 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                                    <label class="mt-2.5 flex items-center gap-2 text-sm text-neutral-700">
                                        <input type="checkbox" x-model="free" class="h-4 w-4 rounded border-neutral-300 text-teal-600 focus:ring-teal-600/30">
                                        {{ __('This model is free') }}
                                    </label>
                                </div>
                                <div>
                                    <span class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('Licence') }}</span>
                                    <p class="rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-2.5 text-sm text-neutral-700">{{ __('Standard licence') }}</p>
                                    <p class="mt-1.5 text-xs text-tertiary">{{ __('The licence terms are set out in the seller terms, shown before you publish.') }}</p>
                                </div>
                            </div>

                            <x-slot:footer>
                                <span class="text-xs text-tertiary">{{ __('Your changes are kept as a draft until you send the model for review.') }}</span>
                                <x-btn type="submit">{{ __('Save changes') }}</x-btn>
                            </x-slot:footer>
                        </x-panel>
                    </form>
                @else
                    {{-- Read-only while a reviewer has it or it is live --}}
                    <x-panel :title="__('Details')">
                        <dl class="grid gap-4 text-sm sm:grid-cols-2">
                            <div><dt class="text-xs text-tertiary">{{ __('Category') }}</dt><dd class="mt-1 text-neutral-900">{{ $product->category?->path() }}</dd></div>
                            <div><dt class="text-xs text-tertiary">{{ __('Price') }}</dt><dd class="mt-1 tabular-nums text-neutral-900">{{ $product->isFree() ? __('Free') : \App\Support\Money::formatMinor($product->price_minor) }}</dd></div>
                            @if ($product->polygons)<div><dt class="text-xs text-tertiary">{{ __('Polygons') }}</dt><dd class="mt-1 tabular-nums text-neutral-900">{{ number_format($product->polygons) }}</dd></div>@endif
                            @if ($product->vertices)<div><dt class="text-xs text-tertiary">{{ __('Vertices') }}</dt><dd class="mt-1 tabular-nums text-neutral-900">{{ number_format($product->vertices) }}</dd></div>@endif
                            <div class="sm:col-span-2"><dt class="text-xs text-tertiary">{{ __('Description') }}</dt><dd class="mt-1"><x-jobs.markdown-description :content="$product->description" /></dd></div>
                        </dl>
                    </x-panel>
                    <x-panel :title="__('Files')" :flush="$product->files->isNotEmpty()">
                        <ul class="divide-y divide-neutral-100 border-t border-neutral-100">
                            @foreach ($product->files as $file)
                                <li class="flex items-center gap-3 px-6 py-3 text-sm">
                                    <x-icon name="document" class="h-5 w-5 shrink-0 text-neutral-400" />
                                    <a href="{{ route('seller.models.files.download', [$product, $file]) }}" class="min-w-0 flex-1 truncate font-medium text-teal-700 hover:underline">{{ $file->original_name }}</a>
                                    <span class="text-xs capitalize text-tertiary">{{ $file->kind }}</span>
                                    <span class="text-xs tabular-nums text-tertiary">{{ $file->readableSize() }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </x-panel>
                @endif
            </div>

            <aside class="space-y-6 lg:sticky lg:top-24">
                <x-panel :title="__('Status')">
                    <x-badge :tone="$product->status->tone()" class="px-2.5 py-0.5 text-xs font-medium">{{ __($product->status->label()) }}</x-badge>
                    <p class="mt-3 text-sm text-neutral-700">
                        @switch($product->status)
                            @case(ProductStatus::Draft) {{ __('Only you can see this draft. When it is ready, send it for review.') }} @break
                            @case(ProductStatus::InReview) {{ __('A reviewer is checking this model. You will be told the outcome.') }} @break
                            @case(ProductStatus::Published) {{ __('This model is live in the catalogue.') }} @break
                            @case(ProductStatus::Rejected) {{ __('A reviewer asked for changes. Update the model and send it again.') }} @break
                            @case(ProductStatus::Unpublished) {{ __('This model is not visible to buyers. Edit it and send it for review again.') }} @break
                        @endswitch
                    </p>

                    @if ($product->review_notes && in_array($product->status, [ProductStatus::Rejected, ProductStatus::Unpublished], true))
                        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                            <p class="font-medium">{{ __('Reason from the reviewer') }}</p>
                            <p class="mt-1 whitespace-pre-line break-words">{{ $product->review_notes }}</p>
                        </div>
                    @endif

                    @if ($editable)
                        {{-- Live checklist: title, category and description follow the saved listing; previews and files update as you upload --}}
                        <ul class="mt-5 space-y-2 border-t border-neutral-100 pt-5 text-sm"
                            x-data="{
                                saved: @js(array_map(fn ($key) => ! array_key_exists($key, $problems), array_combine(array_keys(\App\Models\Product::requirements()), array_keys(\App\Models\Product::requirements())))),
                                descLength: @js(mb_strlen(trim((string) $product->description))),
                                images: @js($images->count()),
                                modelFiles: @js($files->whereIn('kind', ['native', 'exchange', 'archive'])->count()),
                                labels: @js(array_map(fn ($label) => __($label), \App\Models\Product::requirements())),
                                done(key) { return key === 'images' ? this.images > 0 : key === 'files' ? this.modelFiles > 0 : key === 'description' ? this.descLength >= 30 : this.saved[key]; },
                            }"
                            x-on:input.window="if ($event.target.id === 'description') descLength = $event.target.value.trim().length"
                            x-on:uploads-changed.window="$event.detail.kind === 'images' ? images = $event.detail.items.length : modelFiles = $event.detail.items.filter(i => ['native', 'exchange', 'archive'].includes(i.kind)).length">
                            <template x-for="(label, key) in labels" :key="key">
                                <li class="flex items-center gap-2.5" :class="done(key) ? 'text-neutral-800' : 'text-tertiary'">
                                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full" :class="done(key) ? 'bg-teal-600 text-white' : 'border border-neutral-300'">
                                        <svg x-show="done(key)" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="h-3 w-3"><path d="M5 13l4 4L19 7" /></svg>
                                    </span>
                                    <span x-text="label"></span>
                                </li>
                            </template>
                        </ul>
                        <x-btn block class="mt-5" type="submit" form="product-form" name="then" value="submit"><x-icon name="paper-airplane" class="h-4 w-4" />{{ __('Save and send for review') }}</x-btn>
                    @endif

                    @if ($product->status === ProductStatus::Published)
                        <x-btn block variant="secondary" class="mt-5" href="{{ route('models.show', $product) }}">{{ __('View in the catalogue') }}</x-btn>
                    @endif
                    @if (in_array($product->status, [ProductStatus::Published, ProductStatus::InReview], true))
                        <form action="{{ route('seller.models.unpublish', $product) }}" method="POST" class="mt-3">
                            @csrf
                            <x-btn block variant="secondary" type="submit">{{ __('Unpublish to edit') }}</x-btn>
                        </form>
                    @endif
                </x-panel>

                @if ($editable)
                    <x-panel :title="__('Delete this model')" danger>
                        <p class="text-sm text-neutral-700">{{ __('Removes the model, its files and its images for good.') }}</p>
                        <x-btn block variant="danger-outline" class="mt-4" type="button" @click="deleting = true">{{ __('Delete model') }}</x-btn>
                    </x-panel>
                @endif
            </aside>
        </div>

        @if ($editable)
            <x-confirm-dialog bind="deleting" title="Delete this model" confirm-label="Delete" method="DELETE" :action="route('seller.models.destroy', $product)"
                message="The model, its files and its images are removed for good. This cannot be undone." />
        @endif
    </div>
</x-app-layout>
