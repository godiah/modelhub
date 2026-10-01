@use('App\Enums\ProductStatus')
@php
    $pills = ['all' => __('All')] + collect(ProductStatus::cases())->mapWithKeys(fn ($case) => [$case->value => __($case->label())])->all();
@endphp
<x-staff-layout title="Model reviews">
    <div class="container mx-auto max-w-7xl px-4 py-8">
        <div class="mb-6">
            <h1 class="font-tertiary text-2xl font-semibold text-neutral-900">{{ __('Model reviews') }}</h1>
            <p class="mt-1 max-w-2xl text-sm text-tertiary">{{ __('Check each model before it goes live: the previews, the files and the details. A model that needs changes goes back to the seller with your reason.') }}</p>
        </div>

        <nav aria-label="{{ __('Filter by status') }}" class="-mx-4 mb-5 overflow-x-auto px-4 sm:mx-0 sm:px-0">
            <ul class="flex min-w-max items-center gap-2">
                @foreach ($pills as $key => $label)
                    @php $active = $status === $key; @endphp
                    <li>
                        <a href="{{ route('admin.models.index', ['status' => $key]) }}" @if ($active) aria-current="true" @endif
                            @class([
                                'inline-flex items-center gap-2 rounded-full border px-3.5 py-1.5 text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40',
                                'border-teal-600 bg-teal-600 text-white' => $active,
                                'border-neutral-200 bg-white text-neutral-700 hover:border-neutral-300 hover:bg-neutral-50' => ! $active,
                            ])>
                            {{ $label }}
                            <span @class(['text-xs tabular-nums', 'text-teal-100' => $active, 'text-tertiary' => ! $active])>{{ $counts[$key] ?? 0 }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        @if ($products->isEmpty())
            <x-empty-state icon="cube" :title="__('Nothing here')" :description="$status === 'in_review' ? __('No models are waiting for review.') : __('No models match this filter.')" />
        @else
            <div class="space-y-4">
                @foreach ($products as $product)
                    <article x-data="{ publishing: false, rejecting: false, takingDown: false }" class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm sm:p-6">
                        <div class="flex flex-col gap-5 lg:flex-row">
                            <div class="w-full shrink-0 lg:w-56">
                                @if ($product->images->isNotEmpty())
                                    <div class="grid grid-cols-3 gap-1.5">
                                        @foreach ($product->images->take(3) as $image)
                                            <a href="{{ $image->url() }}" target="_blank" rel="noopener" @class(['block overflow-hidden rounded-lg bg-neutral-100', 'col-span-3' => $loop->first])><img src="{{ $image->url() }}" alt="" loading="lazy" class="{{ $loop->first ? 'aspect-[4/3]' : 'aspect-square' }} w-full object-cover"></a>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="flex aspect-[4/3] items-center justify-center rounded-lg bg-neutral-100 text-neutral-300"><x-icon name="cube" class="h-10 w-10" /></div>
                                @endif
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h2 class="font-tertiary text-lg font-semibold text-neutral-900">{{ $product->title }}</h2>
                                            <x-badge :tone="$product->status->tone()" class="px-2.5 py-0.5 text-xs font-medium">{{ __($product->status->label()) }}</x-badge>
                                        </div>
                                        <p class="mt-1 text-sm text-tertiary">
                                            {{ $product->seller->sellerProfile?->display_name }} ({{ $product->seller->name }}) · {{ $product->category?->path() ?? __('No category') }} ·
                                            {{ $product->isFree() ? __('Free') : \App\Support\Money::formatMinor($product->price_minor) }}
                                            @if ($product->submitted_at) · {{ __('submitted :date', ['date' => $product->submitted_at->format('M j, Y')]) }}@endif
                                        </p>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <x-btn size="sm" variant="secondary" href="{{ route('models.show', $product) }}" target="_blank">{{ __('Open page') }}</x-btn>
                                        @if ($product->status === ProductStatus::InReview)
                                            <x-btn size="sm" type="button" @click="publishing = true"><x-icon name="check" class="h-4 w-4" />{{ __('Publish') }}</x-btn>
                                            <x-btn size="sm" variant="danger-outline" type="button" @click="rejecting = true">{{ __('Ask for changes') }}</x-btn>
                                        @endif
                                        @if ($product->status === ProductStatus::Published)
                                            <x-btn size="sm" variant="danger-outline" type="button" @click="takingDown = true">{{ __('Take down') }}</x-btn>
                                        @endif
                                    </div>
                                </div>

                                @if (filled($product->description))
                                    <p class="mt-3 line-clamp-3 whitespace-pre-line text-sm text-neutral-700">{{ $product->description }}</p>
                                @endif

                                @if ($features = $product->feature_labels())
                                    <ul class="mt-3 flex flex-wrap gap-1.5">@foreach ($features as $label)<li class="rounded-full border border-teal-100 bg-teal-50 px-2 py-0.5 text-xs text-teal-800">{{ __($label) }}</li>@endforeach</ul>
                                @endif

                                <div class="mt-4 rounded-xl border border-neutral-200">
                                    <p class="border-b border-neutral-100 px-4 py-2 text-xs font-medium uppercase tracking-wide text-tertiary">{{ trans_choice(':count file|:count files', $product->files->count(), ['count' => $product->files->count()]) }}</p>
                                    <ul class="divide-y divide-neutral-100">
                                        @forelse ($product->files as $file)
                                            <li class="flex items-center gap-3 px-4 py-2 text-sm">
                                                <a href="{{ route('admin.models.files.download', [$product, $file]) }}" class="min-w-0 flex-1 truncate font-medium text-teal-700 hover:underline">{{ $file->original_name }}</a>
                                                <span class="text-xs capitalize text-tertiary">{{ $file->kind }}</span>
                                                <span class="text-xs tabular-nums text-tertiary">{{ $file->readableSize() }}</span>
                                            </li>
                                        @empty
                                            <li class="px-4 py-2 text-sm text-tertiary">{{ __('No files uploaded.') }}</li>
                                        @endforelse
                                    </ul>
                                </div>

                                @if ($product->reviewed_at)
                                    <p class="mt-3 text-xs text-tertiary">{{ __('Last decision :date by :name', ['date' => $product->reviewed_at->format('M j, Y'), 'name' => $product->reviewer?->name ?? __('a reviewer')]) }}@if ($product->review_notes) · <span class="text-neutral-700">{{ $product->review_notes }}</span>@endif</p>
                                @endif
                            </div>
                        </div>

                        <x-confirm-dialog bind="publishing" title="Publish this model" icon="check" tone="success" confirm-label="Publish" method="PATCH"
                            :action="route('admin.models.review', [$product, 'publish'])" message="It becomes visible in the catalogue, and the seller is told." />
                        <x-confirm-dialog bind="rejecting" title="Ask for changes" confirm-label="Send back" method="PATCH" state="notes: ''" disabledWhen="notes.trim().length < 5"
                            :action="route('admin.models.review', [$product, 'reject'])" message="Tell the seller what to fix. They will see this and can send the model again.">
                            <label for="reject-{{ $product->id }}" class="sr-only">{{ __('Reason') }}</label>
                            <textarea id="reject-{{ $product->id }}" name="notes" x-model="notes" rows="3" maxlength="1000" required placeholder="{{ __('What needs to change') }}"
                                class="mt-3 block w-full resize-none rounded-xl border border-neutral-300 px-3 py-2 text-sm focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25"></textarea>
                        </x-confirm-dialog>
                        <x-confirm-dialog bind="takingDown" title="Take this model down" confirm-label="Take down" method="PATCH" state="notes: ''" disabledWhen="notes.trim().length < 5"
                            :action="route('admin.models.review', [$product, 'takedown'])" message="It disappears from the catalogue and returns to the seller as unpublished.">
                            <label for="takedown-{{ $product->id }}" class="sr-only">{{ __('Reason') }}</label>
                            <textarea id="takedown-{{ $product->id }}" name="notes" x-model="notes" rows="3" maxlength="1000" required placeholder="{{ __('Why it is being taken down') }}"
                                class="mt-3 block w-full resize-none rounded-xl border border-neutral-300 px-3 py-2 text-sm focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25"></textarea>
                        </x-confirm-dialog>
                    </article>
                @endforeach
            </div>
            <x-pager :paginator="$products" />
        @endif
    </div>
</x-staff-layout>
