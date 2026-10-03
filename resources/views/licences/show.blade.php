@php
    use App\Support\Money;
    use App\Support\Settings\FeePolicy;

    $tier = $licence->tier;
    $terms = $licence->terms;
    $lib = $licence->library;
    $ended = ! $licence->isActive();
    $extended = $tier->value === 'extended';
    $product = $licence->product;
    $kinds = ['native' => __('Native formats'), 'exchange' => __('Exchange formats'), 'texture' => __('Textures')];
    $files = $product ? $product->files->groupBy('kind') : collect();
@endphp
<x-app-layout title="Licence {{ $licence->key }}">
    <div class="container mx-auto max-w-6xl space-y-6 px-4 py-8">
        <div class="flex flex-wrap items-center justify-between gap-3 print:hidden">
            <a href="{{ route('licences.index') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm font-medium text-teal-700 hover:underline"><x-icon name="arrow-left" class="h-4 w-4" />{{ __('All licences') }}</a>
            <a href="#certificate" class="text-sm font-medium text-neutral-600 hover:text-teal-700">{{ __('Jump to the certificate') }}</a>
        </div>

        @if ($ended)
            <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-900 print:hidden" role="alert">
                <p class="font-semibold">{{ __('This licence ended on :date.', ['date' => $licence->revoked_at->format('F j, Y')]) }}</p>
                @if ($licence->revoked_reason)<p class="mt-1">{{ $licence->revoked_reason }}</p>@endif
                <p class="mt-1">{{ __('The files must no longer be used in new work.') }}</p>
            </div>
        @endif

        {{-- The model, the licence in a line, and what you can do next --}}
        <section aria-label="{{ __('Licence summary') }}" class="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm print:hidden">
            <div class="grid grid-cols-1 md:grid-cols-[minmax(0,5fr)_minmax(0,7fr)]">
                <div class="relative aspect-[16/10] bg-neutral-100 md:aspect-auto md:min-h-[17rem]">
                    @if ($lib->cover)
                        <img src="{{ $lib->cover->url() }}" alt="" @class(['absolute inset-0 h-full w-full object-cover', 'grayscale opacity-60' => $ended])>
                    @else
                        <span class="flex h-full w-full items-center justify-center text-neutral-300"><x-icon name="cube" class="h-16 w-16" /></span>
                    @endif
                    @if ($lib->formats->isNotEmpty())
                        <span class="absolute left-3 top-3 flex gap-1">
                            @foreach ($lib->formats->take(4) as $format)<span class="rounded bg-neutral-900/70 px-1.5 py-0.5 font-mono text-[10px] font-semibold uppercase tracking-wide text-white">{{ $format }}</span>@endforeach
                        </span>
                    @endif
                </div>

                <div class="flex flex-col p-6 sm:p-8">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="text-xs font-medium uppercase tracking-wide text-tertiary">{{ config('app.name') }} · {{ __('Model licence') }}</p>
                        <x-badge :tone="$extended ? 'blue' : 'neutral'" class="px-2 py-0.5 text-xs font-medium">{{ __($tier->label()) }}</x-badge>
                        <x-badge :tone="$ended ? 'red' : 'green'" class="px-2 py-0.5 text-xs font-medium">{{ $ended ? __('Ended') : __('Active') }}</x-badge>
                    </div>
                    <h1 class="mt-2 font-tertiary text-2xl font-bold tracking-tight text-neutral-900 sm:text-3xl">{{ $licence->product_title }}</h1>
                    <p class="mt-1 text-sm text-tertiary">{{ __('From :seller', ['seller' => $licence->seller_name]) }}@unless ($lib->live) · {{ __('No longer listed') }}@endunless</p>
                    <p class="mt-3 text-sm text-neutral-700">{{ __($tier->summary()) }}</p>

                    <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-1 rounded-xl bg-neutral-50 px-4 py-2.5">
                        <span class="text-xs text-tertiary">{{ __('Licence key') }}</span>
                        <span class="font-mono text-sm tracking-wider text-neutral-800">{{ $licence->key }}</span>
                        <x-copy-button :text="$licence->key" />
                    </div>

                    <dl class="mt-5 grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
                        <div><dt class="text-xs text-tertiary">{{ __('Licensee') }}</dt><dd class="mt-0.5 font-medium text-neutral-900">{{ $licence->licensee_name }}</dd></div>
                        <div><dt class="text-xs text-tertiary">{{ __('Issued') }}</dt><dd class="mt-0.5 font-medium text-neutral-900">{{ $licence->issued_at->format('M j, Y') }}</dd></div>
                        <div><dt class="text-xs text-tertiary">{{ __('Price paid') }}</dt><dd class="mt-0.5 font-medium tabular-nums text-neutral-900">{{ $licence->price_minor === 0 ? __('Free') : Money::formatMinor($licence->price_minor) }}</dd></div>
                        <div><dt class="text-xs text-tertiary">{{ __('Terms version') }}</dt><dd class="mt-0.5 font-medium text-neutral-900">{{ $licence->terms_version }}</dd></div>
                    </dl>

                    <div class="mt-auto flex flex-wrap gap-2 pt-6">
                        @unless ($ended)<x-btn size="sm" href="#files"><x-icon name="cloud-arrow-down" class="h-4 w-4" />{{ __('Download files') }}</x-btn>@endunless
                        <button type="button" x-data @click="window.print()" class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 bg-white px-3.5 py-1.5 text-sm font-semibold text-neutral-700 hover:bg-neutral-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">{{ __('Print or save as PDF') }}</button>
                        @if ($lib->live)<x-btn size="sm" variant="secondary" :href="route('models.show', $product)">{{ __('View the model') }}</x-btn>@endif
                    </div>
                    @if ($lib->upgrade_minor || $lib->can_rate)
                        <ul class="mt-4 flex flex-wrap gap-x-5 gap-y-1.5 text-sm">
                            @if ($lib->upgrade_minor)<li><a href="{{ route('models.show', $product) }}" class="inline-flex items-center gap-1.5 font-medium text-indigo-600 hover:underline"><x-icon name="bolt" class="h-4 w-4" />{{ __('Upgrade to Extended for :price', ['price' => Money::formatMinor($lib->upgrade_minor, 0)]) }}</a></li>@endif
                            @if ($lib->can_rate)<li><a href="{{ route('models.show', $product) }}#reviews" class="inline-flex items-center gap-1.5 font-medium text-teal-700 hover:underline"><x-icon name="star" class="h-4 w-4" />{{ __('Rate this model') }}</a></li>@endif
                        </ul>
                    @endif
                </div>
            </div>
        </section>

        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-3 print:hidden">
            {{-- The files this licence is for: private, so this is the only way to reach them --}}
            @unless ($ended)
            <section id="files" class="scroll-mt-24 rounded-2xl border border-neutral-200 bg-white shadow-sm lg:col-span-2" aria-labelledby="files-heading">
                <header class="flex items-start justify-between gap-4 px-6 pt-6">
                    <div>
                        <h2 id="files-heading" class="font-tertiary text-base font-semibold text-neutral-900">{{ __('Your files') }}</h2>
                        @if ($product && $product->files->isNotEmpty())<p class="mt-1 text-sm text-tertiary">{{ trans_choice(':count file|:count files', $product->files->count(), ['count' => $product->files->count()]) }} · {{ number_format($lib->file_bytes / 1048576, 1) }} MB</p>@endif
                    </div>
                </header>
                <div class="px-6 pb-6 pt-4">
                    @if ($product && $product->files->isNotEmpty())
                        <div class="space-y-5">
                            @foreach ($kinds as $kind => $label)
                                @continue(! $files->has($kind))
                                <div>
                                    <h3 class="text-xs font-semibold uppercase tracking-wide text-tertiary">{{ $label }}</h3>
                                    <ul class="mt-2 divide-y divide-neutral-100 rounded-xl border border-neutral-200">
                                        @foreach ($files[$kind] as $file)
                                            @php $stat = $fileStats->get($file->id); @endphp
                                            <li class="flex flex-wrap items-center gap-3 px-4 py-3">
                                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-neutral-900/80 font-mono text-[10px] font-semibold uppercase tracking-wide text-white">{{ \Illuminate\Support\Str::limit($file->extension, 5, '') }}</span>
                                                <span class="min-w-0 flex-1 basis-40">
                                                    <span class="block truncate text-sm font-medium text-neutral-900">{{ $file->original_name }}</span>
                                                    <span class="block text-xs text-tertiary">{{ $file->readableSize() }} · {{ $stat ? trans_choice('Downloaded :count time|Downloaded :count times', $stat->total, ['count' => $stat->total]).' · '.__('last :date', ['date' => \Illuminate\Support\Carbon::parse($stat->last_at)->format('M j')]) : __('Not downloaded yet') }}</span>
                                                </span>
                                                <x-btn size="sm" variant="secondary" :href="route('licences.download', [$licence, $file])"><x-icon name="cloud-arrow-down" class="h-4 w-4" />{{ __('Download') }}</x-btn>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endforeach
                        </div>
                        <p class="mt-4 text-xs text-tertiary">{{ __('Digital files cannot be returned, so a purchase is not refunded once you have downloaded it, unless a file is broken or not as described.') }}</p>
                    @else
                        <p class="rounded-xl bg-neutral-50 px-4 py-3 text-sm text-neutral-700">{{ __('The files for this model are no longer available. Write to us and we will help.') }}</p>
                    @endif
                </div>
            </section>
            @endunless

            <div @class(['space-y-6', 'lg:col-span-3 lg:grid lg:grid-cols-3 lg:items-start lg:gap-6 lg:space-y-0' => $ended])>
                <x-panel :title="__('What this licence covers')">
                    <ul class="space-y-2.5 text-sm text-neutral-700">
                        @foreach (array_slice($terms['permitted'], 0, 3) as $item)
                            <li class="flex items-start gap-2.5"><x-icon name="check-circle-2" class="mt-0.5 h-4 w-4 shrink-0 text-teal-600" /><span>{{ __($item) }}</span></li>
                        @endforeach
                        <li class="flex items-start gap-2.5"><x-icon name="x-circle-solid" class="mt-0.5 h-4 w-4 shrink-0 text-red-500" /><span>{{ __($terms['restrictions'][0] ?? '') }}</span></li>
                    </ul>
                    <p class="mt-4 text-xs"><a href="#certificate" class="font-medium text-teal-700 hover:underline">{{ __('Read the full terms') }}</a> · <a href="{{ route('legal.licences') }}" class="font-medium text-teal-700 hover:underline">{{ __('Compare the licences') }}</a></p>
                </x-panel>

                <x-panel :title="__('Download history')">
                    @if ($recent->isEmpty())
                        <p class="text-sm text-tertiary">{{ __('You have not downloaded any files with this licence yet.') }}</p>
                    @else
                        <ul class="space-y-3">
                            @foreach ($recent as $download)
                                <li class="flex items-start gap-3 text-sm">
                                    <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-teal-50 text-teal-700"><x-icon name="cloud-arrow-down" class="h-4 w-4" /></span>
                                    <span class="min-w-0"><span class="block truncate font-medium text-neutral-800">{{ $download->file_name }}</span><span class="block text-xs text-tertiary">{{ $download->created_at->format('M j, Y · g:i A') }}</span></span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-panel>

                <x-panel :title="__('Something wrong with a file?')">
                    <p class="text-sm text-neutral-700">{{ __('If a file is broken or is not as described, write to us within :days days of buying and we will look into a refund.', ['days' => FeePolicy::saleHoldDays()]) }}</p>
                    <p class="mt-3 text-xs"><a href="{{ route('policies.payments') }}#refunds-for-models" class="font-medium text-teal-700 hover:underline">{{ __('How refunds work') }}</a></p>
                </x-panel>
            </div>
        </div>

        {{-- The certificate: the licence as it was issued, which is what prints --}}
        <article id="certificate" class="mx-auto max-w-3xl scroll-mt-24 rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm sm:p-8 print:max-w-none print:border-0 print:p-0 print:shadow-none">
            <header class="flex flex-wrap items-start justify-between gap-3 border-b border-neutral-200 pb-5">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-tertiary">{{ config('app.name') }} · {{ __('Model licence') }}</p>
                    <h2 class="mt-1 font-tertiary text-2xl font-semibold text-neutral-900">{{ __(':tier licence', ['tier' => $tier->label()]) }}</h2>
                    <p class="mt-1 font-mono text-sm tracking-wider text-neutral-700">{{ $licence->key }}</p>
                </div>
                <button type="button" x-data @click="window.print()" class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 bg-white px-3 py-1.5 text-sm font-semibold text-neutral-700 hover:bg-neutral-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40 print:hidden">{{ __('Print') }}</button>
            </header>

            <dl class="grid gap-x-8 gap-y-4 border-b border-neutral-200 py-5 text-sm sm:grid-cols-2">
                <div><dt class="text-xs text-tertiary">{{ __('Model') }}</dt><dd class="mt-0.5 font-medium text-neutral-900">{{ $licence->product_title }}</dd></div>
                <div><dt class="text-xs text-tertiary">{{ __('Seller') }}</dt><dd class="mt-0.5 font-medium text-neutral-900">{{ $licence->seller_name }}</dd></div>
                <div><dt class="text-xs text-tertiary">{{ __('Licensee') }}</dt><dd class="mt-0.5 font-medium text-neutral-900">{{ $licence->licensee_name }}</dd></div>
                <div><dt class="text-xs text-tertiary">{{ __('Issued') }}</dt><dd class="mt-0.5 font-medium text-neutral-900">{{ $licence->issued_at->format('F j, Y') }}</dd></div>
                <div><dt class="text-xs text-tertiary">{{ __('Price paid') }}</dt><dd class="mt-0.5 font-medium tabular-nums text-neutral-900">{{ $licence->price_minor === 0 ? __('Free') : Money::formatMinor($licence->price_minor) }}</dd></div>
                <div><dt class="text-xs text-tertiary">{{ __('Terms version') }}</dt><dd class="mt-0.5 font-medium text-neutral-900">{{ $licence->terms_version }}</dd></div>
            </dl>

            <section class="py-5">
                <h3 class="font-tertiary text-base font-semibold text-neutral-900">{{ __('What you may do') }}</h3>
                <x-policy.list class="mt-3 text-sm text-neutral-700" :items="array_map('__', $terms['permitted'])" />
            </section>
            <section class="border-t border-neutral-200 py-5">
                <h3 class="font-tertiary text-base font-semibold text-neutral-900">{{ __('What no licence allows') }}</h3>
                <ul class="mt-3 space-y-2.5 text-sm text-neutral-700">
                    @foreach ($terms['restrictions'] as $item)<li class="flex items-start gap-2.5"><x-icon name="x-circle-solid" class="mt-0.5 h-4 w-4 shrink-0 text-red-500" /><span>{{ __($item) }}</span></li>@endforeach
                </ul>
            </section>
            <section class="border-t border-neutral-200 pt-5">
                <h3 class="font-tertiary text-base font-semibold text-neutral-900">{{ __('For every licence') }}</h3>
                <ul class="mt-3 list-disc space-y-2 pl-5 text-sm text-neutral-700">
                    @foreach ($terms['general'] as $item)<li>{{ __($item) }}</li>@endforeach
                </ul>
                <p class="mt-4 text-xs text-tertiary">{{ __('The full wording is at :url.', ['url' => route('legal.licences')]) }}</p>
            </section>
        </article>
    </div>
</x-app-layout>
