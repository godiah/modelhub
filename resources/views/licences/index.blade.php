@php
    use App\Services\Marketplace\LicenceLibrary;
    use App\Support\Money;

    $none = $counts['all'] === 0;
    $base = ['q' => $term !== '' ? $term : null, 'tab' => $tab === 'all' ? null : $tab, 'sort' => $sort === 'newest' ? null : $sort];
    $url = fn (array $override = []) => route('licences.index', array_filter(array_merge($base, $override), fn ($v) => $v !== null));
    $size = fn (int $bytes) => $bytes >= 1048576 ? number_format($bytes / 1048576, 1).' MB' : ($bytes >= 1024 ? number_format($bytes / 1024).' KB' : $bytes.' B');
@endphp
<x-app-layout title="My licences">
    <div class="container mx-auto max-w-7xl space-y-6 px-4 py-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="font-tertiary text-2xl font-bold tracking-tight text-neutral-900 sm:text-3xl">{{ __('My licences') }}</h1>
                <p class="mt-1 max-w-2xl text-sm text-tertiary">{{ __('Every model you have bought or taken, with its licence, its files and what you may do with it.') }} <a href="{{ route('legal.licences') }}" class="font-medium text-teal-700 hover:underline">{{ __('Compare the licences') }}</a></p>
            </div>
            <x-btn :href="route('models.index')" wire:navigate variant="secondary"><x-icon name="cube" class="h-4 w-4" />{{ __('Browse models') }}</x-btn>
        </div>

        @if ($none)
            <x-empty-state icon="clipboard-document" :title="__('No licences yet')" :description="__('When you buy or download a model, its licence appears here, together with its files. A licence is your proof of what you may do with a model.')">
                    <x-btn :href="route('models.index')" wire:navigate>{{ __('Browse models') }}</x-btn>
                </x-empty-state>
        @else
            {{-- What you hold, at a glance --}}
            <section aria-label="{{ __('Summary') }}" class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <x-stat-tile :label="__('Active licences')" :value="number_format($stats['active'])" :hint="$counts['ended'] > 0 ? trans_choice(':count has ended|:count have ended', $counts['ended'], ['count' => $counts['ended']]) : __('All of them in good standing')" icon="clipboard-document" />
                <x-stat-tile :label="__('Extended licences')" :value="number_format($stats['extended'])" :hint="__('For many products and small teams')" icon="users" />
                <x-stat-tile :label="__('Spent on models')" :value="Money::formatMinor($stats['spent'], 0)" :hint="__('Across your active licences')" icon="cash" />
                <x-stat-tile :label="__('File downloads')" :value="number_format($stats['downloads'])" :hint="__('Every time you took a file')" icon="cloud-arrow-down" />
            </section>

            {{-- Find one --}}
            <div class="space-y-3">
                <form method="GET" action="{{ route('licences.index') }}" class="flex flex-col gap-2 sm:flex-row" role="search">
                    @if ($tab !== 'all')<input type="hidden" name="tab" value="{{ $tab }}">@endif
                    <label for="licence-search" class="sr-only">{{ __('Search your licences') }}</label>
                    <div class="relative flex-1">
                        <x-icon name="magnifying-glass" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-400" />
                        <input id="licence-search" type="search" name="q" value="{{ $term }}" placeholder="{{ __('Search by model, seller or licence key') }}" class="block w-full rounded-xl border border-neutral-300 bg-white py-2.5 pl-10 pr-4 text-sm focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25">
                    </div>
                    <label for="licence-sort" class="sr-only">{{ __('Sort by') }}</label>
                    <select id="licence-sort" name="sort" onchange="this.form.requestSubmit()" class="rounded-xl border border-neutral-300 bg-white py-2.5 pl-3 pr-9 text-sm text-neutral-700 focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25">
                        @foreach (LicenceLibrary::SORTS as $key => $label)<option value="{{ $key }}" @selected($sort === $key)>{{ __($label) }}</option>@endforeach
                    </select>
                    <x-btn type="submit">{{ __('Search') }}</x-btn>
                </form>

                <nav aria-label="{{ __('Filter licences') }}" class="flex flex-wrap gap-1.5">
                    @foreach (LicenceLibrary::TABS as $key => $label)
                        <a href="{{ $url(['tab' => $key === 'all' ? null : $key]) }}" wire:navigate @if ($tab === $key) aria-current="true" @endif
                            @class(['inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-sm font-medium transition-colors', 'bg-teal-600 text-white' => $tab === $key, 'bg-white text-neutral-700 ring-1 ring-neutral-200 hover:bg-neutral-50' => $tab !== $key])>
                            {{ __($label) }}<span @class(['text-xs tabular-nums', 'text-teal-100' => $tab === $key, 'text-tertiary' => $tab !== $key])>{{ $counts[$key] }}</span>
                        </a>
                    @endforeach
                </nav>
            </div>

            @if ($licences->isEmpty())
                <x-empty-state icon="magnifying-glass" :title="__('No licences match')" :description="$term !== '' ? __('Nothing matches “:term”.', ['term' => $term]) : __('You have no licences of this kind.')">
                        <x-btn :href="route('licences.index')" wire:navigate variant="secondary">{{ __('Show all licences') }}</x-btn>
                    </x-empty-state>
            @else
                <ul class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($licences as $licence)
                        @php
                            $lib = $licence->library;
                            $ended = ! $licence->isActive();
                            $extended = $licence->tier->value === 'extended';
                        @endphp
                        <li>
                            <article @class(['group flex h-full flex-col overflow-hidden rounded-2xl border bg-white shadow-sm transition-shadow duration-200 hover:shadow-md', 'border-neutral-200' => ! $ended, 'border-red-200' => $ended])>
                                <a href="{{ route('licences.show', $licence) }}" wire:navigate class="relative block aspect-[16/10] overflow-hidden bg-neutral-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40" aria-label="{{ __('Open the licence for :model', ['model' => $licence->product_title]) }}">
                                    @if ($lib->cover)
                                        <img src="{{ $lib->cover->url() }}" alt="" loading="lazy" @class(['absolute inset-0 h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.03]', 'grayscale opacity-60' => $ended])>
                                    @else
                                        <span class="flex h-full w-full items-center justify-center text-neutral-300"><x-icon name="cube" class="h-12 w-12" /></span>
                                    @endif
                                    @if ($lib->formats->isNotEmpty())
                                        <span class="absolute left-2.5 top-2.5 flex gap-1">
                                            @foreach ($lib->formats->take(3) as $format)
                                                <span class="rounded bg-neutral-900/70 px-1.5 py-0.5 font-mono text-[10px] font-semibold uppercase tracking-wide text-white">{{ $format }}</span>
                                            @endforeach
                                        </span>
                                    @endif
                                    <span @class(['absolute right-2.5 top-2.5 rounded-lg px-2.5 py-1 text-xs font-semibold shadow', 'bg-indigo-600 text-white' => $extended, 'bg-white text-neutral-800' => ! $extended])>{{ __($licence->tier->label()) }}</span>
                                    @if ($ended)
                                        <span class="absolute inset-x-0 bottom-0 bg-red-600/90 px-3 py-1.5 text-center text-xs font-semibold text-white">{{ __('Licence ended :date', ['date' => $licence->revoked_at->format('M j, Y')]) }}</span>
                                    @endif
                                </a>

                                <div class="flex flex-1 flex-col p-4">
                                    <h2 class="font-tertiary text-base font-semibold leading-snug text-neutral-900"><a href="{{ route('licences.show', $licence) }}" wire:navigate class="hover:text-teal-700 focus:outline-none focus-visible:underline">{{ $licence->product_title }}</a></h2>
                                    <p class="mt-0.5 text-xs text-tertiary">{{ __('From :seller', ['seller' => $licence->seller_name]) }}@unless ($lib->live) · {{ __('No longer listed') }}@endunless</p>

                                    <dl class="mt-4 grid grid-cols-3 gap-2 text-xs">
                                        <div><dt class="text-tertiary">{{ __('Issued') }}</dt><dd class="mt-0.5 font-medium text-neutral-800">{{ $licence->issued_at->format('M j, Y') }}</dd></div>
                                        <div><dt class="text-tertiary">{{ __('Paid') }}</dt><dd class="mt-0.5 font-medium tabular-nums text-neutral-800">{{ $licence->price_minor === 0 ? __('Free') : Money::formatMinor($licence->price_minor, 0) }}</dd></div>
                                        <div><dt class="text-tertiary">{{ __('Files') }}</dt><dd class="mt-0.5 font-medium text-neutral-800">{{ $lib->file_count > 0 ? $lib->file_count.' · '.$size($lib->file_bytes) : '—' }}</dd></div>
                                    </dl>

                                    <p class="mt-3 flex items-center gap-1.5 text-xs text-tertiary">
                                        <x-icon name="cloud-arrow-down" class="h-3.5 w-3.5" />
                                        @if ($lib->downloads > 0)
                                            {{ trans_choice('Downloaded :count time|Downloaded :count times', $lib->downloads, ['count' => $lib->downloads]) }} · {{ __('last :date', ['date' => $lib->last_download->format('M j')]) }}
                                        @else
                                            {{ $ended ? __('Never downloaded') : __('Not downloaded yet') }}
                                        @endif
                                    </p>

                                    <div class="mt-3 flex items-center justify-between gap-2 rounded-lg bg-neutral-50 px-3 py-2" x-data="{ copied: false }">
                                        <span class="min-w-0 truncate font-mono text-xs tracking-wide text-neutral-600" title="{{ __('Licence key') }}">{{ $licence->key }}</span>
                                        <button type="button" @click="navigator.clipboard.writeText(@js($licence->key)).then(() => { copied = true; setTimeout(() => copied = false, 1600) })" class="shrink-0 rounded text-xs font-medium text-teal-700 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">
                                            <span x-show="! copied">{{ __('Copy') }}</span><span x-show="copied" x-cloak>{{ __('Copied') }}</span>
                                        </button>
                                    </div>

                                    @if ($lib->upgrade_minor || $lib->can_rate)
                                        <ul class="mt-3 space-y-1.5 text-xs">
                                            @if ($lib->upgrade_minor)<li><a href="{{ route('models.show', $licence->product) }}" class="inline-flex items-center gap-1.5 font-medium text-indigo-600 hover:underline"><x-icon name="bolt" class="h-4 w-4" />{{ __('Upgrade to Extended for :price', ['price' => Money::formatMinor($lib->upgrade_minor, 0)]) }}</a></li>@endif
                                            @if ($lib->can_rate)<li><a href="{{ route('models.show', $licence->product) }}#reviews" class="inline-flex items-center gap-1.5 font-medium text-teal-700 hover:underline"><x-icon name="star" class="h-4 w-4" />{{ __('Rate this model') }}</a></li>@endif
                                        </ul>
                                    @endif

                                    <div class="mt-auto flex gap-2 pt-4">
                                        @if (! $ended)
                                            <x-btn size="sm" class="flex-1" :href="route('licences.show', $licence).'#files'" wire:navigate><x-icon name="cloud-arrow-down" class="h-4 w-4" />{{ __('Download files') }}</x-btn>
                                            <x-btn size="sm" variant="secondary" :href="route('licences.show', $licence)" wire:navigate>{{ __('Licence') }}</x-btn>
                                        @else
                                            <x-btn size="sm" variant="secondary" class="flex-1" :href="route('licences.show', $licence)" wire:navigate>{{ __('See why it ended') }}</x-btn>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        </li>
                    @endforeach
                </ul>
                <x-pager :paginator="$licences" navigate />
            @endif
        @endif
    </div>
</x-app-layout>
