@php $tier = $licence->tier; $terms = $licence->terms; @endphp
<x-app-layout title="Licence {{ $licence->key }}">
    <div class="container mx-auto max-w-3xl px-4 py-8">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 print:hidden">
            <a href="{{ route('licences.index') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm font-medium text-teal-700 hover:underline"><x-icon name="arrow-left" class="h-4 w-4" />{{ __('All licences') }}</a>
            <button type="button" x-data @click="window.print()" class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 bg-white px-3.5 py-2 text-sm font-semibold text-neutral-700 hover:bg-neutral-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">{{ __('Print or save as PDF') }}</button>
        </div>

        @unless ($licence->isActive())
            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-900" role="alert">
                <p class="font-semibold">{{ __('This licence ended on :date.', ['date' => $licence->revoked_at->format('F j, Y')]) }}</p>
                @if ($licence->revoked_reason)<p class="mt-1">{{ $licence->revoked_reason }}</p>@endif
                <p class="mt-1">{{ __('The files must no longer be used in new work.') }}</p>
            </div>
        @endunless

        <article class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm sm:p-8 print:border-0 print:p-0 print:shadow-none">
            <header class="border-b border-neutral-200 pb-5">
                <p class="text-xs font-medium uppercase tracking-wide text-tertiary">{{ config('app.name') }} · {{ __('Model licence') }}</p>
                <h1 class="mt-1 font-tertiary text-2xl font-semibold text-neutral-900">{{ __(':tier licence', ['tier' => $tier->label()]) }}</h1>
                <p class="mt-1 font-mono text-sm tracking-wider text-neutral-700">{{ $licence->key }}</p>
            </header>

            <dl class="grid gap-x-8 gap-y-4 border-b border-neutral-200 py-5 text-sm sm:grid-cols-2">
                <div><dt class="text-xs text-tertiary">{{ __('Model') }}</dt><dd class="mt-0.5 font-medium text-neutral-900">{{ $licence->product_title }}</dd></div>
                <div><dt class="text-xs text-tertiary">{{ __('Seller') }}</dt><dd class="mt-0.5 font-medium text-neutral-900">{{ $licence->seller_name }}</dd></div>
                <div><dt class="text-xs text-tertiary">{{ __('Licensee') }}</dt><dd class="mt-0.5 font-medium text-neutral-900">{{ $licence->licensee_name }}</dd></div>
                <div><dt class="text-xs text-tertiary">{{ __('Issued') }}</dt><dd class="mt-0.5 font-medium text-neutral-900">{{ $licence->issued_at->format('F j, Y') }}</dd></div>
                <div><dt class="text-xs text-tertiary">{{ __('Price paid') }}</dt><dd class="mt-0.5 font-medium tabular-nums text-neutral-900">{{ $licence->price_minor === 0 ? __('Free') : \App\Support\Money::formatMinor($licence->price_minor) }}</dd></div>
                <div><dt class="text-xs text-tertiary">{{ __('Terms version') }}</dt><dd class="mt-0.5 font-medium text-neutral-900">{{ $licence->terms_version }}</dd></div>
            </dl>

            <section class="py-5">
                <h2 class="font-tertiary text-base font-semibold text-neutral-900">{{ __('What you may do') }}</h2>
                <x-policy.list class="mt-3 text-sm text-neutral-700" :items="array_map('__', $terms['permitted'])" />
            </section>
            <section class="border-t border-neutral-200 py-5">
                <h2 class="font-tertiary text-base font-semibold text-neutral-900">{{ __('What no licence allows') }}</h2>
                <ul class="mt-3 space-y-2.5 text-sm text-neutral-700">
                    @foreach ($terms['restrictions'] as $item)<li class="flex items-start gap-2.5"><x-icon name="x-circle-solid" class="mt-0.5 h-4 w-4 shrink-0 text-red-500" /><span>{{ __($item) }}</span></li>@endforeach
                </ul>
            </section>
            <section class="border-t border-neutral-200 pt-5">
                <h2 class="font-tertiary text-base font-semibold text-neutral-900">{{ __('For every licence') }}</h2>
                <ul class="mt-3 list-disc space-y-2 pl-5 text-sm text-neutral-700">
                    @foreach ($terms['general'] as $item)<li>{{ __($item) }}</li>@endforeach
                </ul>
                <p class="mt-4 text-xs text-tertiary">{{ __('The full wording is at :url.', ['url' => route('legal.licences')]) }}</p>
            </section>
        </article>
    </div>
</x-app-layout>
