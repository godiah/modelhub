@php
    $hire = [
        ['undraw_people.svg', __('Compare offers, not guesses'), __('Every applicant sends a price and a proposal with portfolio samples. Line them up side by side and message your shortlist with ready-made templates.')],
        ['undraw_quality_work.svg', __('Track every deliverable'), __('Break the job into deliverables with due dates. The freelancer submits each one, and nothing is final until you approve it.')],
        ['undraw_secure_payment.svg', __('Settle fairly if plans change'), __('If a project stops part way, completed work can be settled with a partial payment, and an administrator steps in if you cannot agree.')],
    ];
    $work = [
        ['undraw_savings.svg', __('Know what you will earn'), __('Set your own price. Before you send it, you see exactly what you would receive after the service fee.')],
        ['undraw_slider.svg', __('Filter down to what fits'), __('Browse open 3D projects by skill, software and budget, and save a draft until your proposal is ready.')],
        ['undraw_completing.svg', __('Deliver and build your name'), __('Work against agreed deliverables, get approved, and collect reviews that follow you to your profile.')],
    ];
    $steps = [
        [__('Post or find a project'), __('Describe the brief and budget, or browse projects that fit your skills.')],
        [__('Offer and hire'), __('Freelancers send an offer and proposal. The client compares and hires one.')],
        [__('Deliver in stages'), __('Agree the deliverables up front. Each one is submitted for review.')],
        [__('Approve and review'), __('The client approves the work, both sides review, and the project closes.')],
    ];
@endphp
<x-site-layout :description="__('Browse 3D models from independent creators, or post a 3D project and hire from the offers that come in. Sell your own models and build a reputation with reviews.')">
    <!-- Hero: models and talent, with a model search -->
    <section class="overflow-hidden bg-white">
        <div class="mx-auto grid max-w-7xl items-center gap-10 px-4 pb-12 pt-12 sm:px-6 sm:pt-16 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)] lg:gap-6 lg:px-8 lg:pb-20 lg:pt-20">
            <div class="relative z-10">
                <h1 class="font-tertiary text-5xl font-bold leading-[1.05] tracking-tight text-neutral-900 sm:text-6xl">
                    {{ __('Find 3D models.') }}<br class="hidden sm:block">
                    <span class="text-teal-600">{{ __('Hire 3D talent.') }}</span>
                </h1>
                <p class="mt-6 max-w-xl text-xl leading-relaxed text-neutral-600">
                    {{ __('Browse ready-made models from independent creators, or post a project and hire from the offers that come in. Sell your own models and take on briefs from the same account.') }}
                </p>

                <form method="GET" action="{{ route('models.index') }}" role="search" class="mt-8 flex max-w-xl flex-col gap-2 sm:flex-row">
                    <label for="home-search" class="sr-only">{{ __('Search 3D models') }}</label>
                    <div class="relative flex-1">
                        <x-icon name="magnifying-glass" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-neutral-400" />
                        <input id="home-search" type="search" name="q" maxlength="100" placeholder="{{ __('Search 3D models…') }}"
                            class="block w-full rounded-xl border border-neutral-300 bg-white py-3 pl-12 pr-4 text-base text-neutral-900 shadow-sm placeholder:text-neutral-400 focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25">
                    </div>
                    <x-btn size="lg" type="submit" class="justify-center">{{ __('Search') }}</x-btn>
                </form>
                @if ($categories->isNotEmpty())
                    <p class="mt-4 flex max-w-xl flex-wrap items-center gap-2 text-sm text-tertiary">
                        <span>{{ __('Popular:') }}</span>
                        @foreach ($categories->take(5) as $category)
                            <a href="{{ route('models.index', ['category' => $category->slug]) }}" class="rounded-full border border-neutral-200 bg-white px-3 py-1 font-medium text-neutral-700 transition-colors hover:border-teal-300 hover:text-teal-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">{{ $category->name }}</a>
                        @endforeach
                    </p>
                @endif

                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <x-btn size="lg" variant="secondary" href="{{ route('models.index') }}">
                        {{ __('Browse models') }}
                        <x-icon name="arrow-right" class="h-5 w-5" />
                    </x-btn>
                    <x-btn size="lg" variant="secondary" href="{{ route('jobs.create') }}">
                        {{ __('Post a project') }}
                    </x-btn>
                </div>
                <p class="mt-4 flex items-center gap-2 text-sm text-tertiary">
                    <x-icon name="user" class="h-4 w-4 shrink-0" />
                    {{ __('Browsing is open to everyone. Saving models, posting a project or applying needs a free account.') }}
                </p>
            </div>

            <div class="relative">
                <img src="{{ asset('images/jobs/hero-post-job.webp') }}" width="1200" height="720" alt="{{ __('A 3D architectural visualisation of a modern glass house') }}"
                    class="mx-auto w-full max-w-2xl select-none lg:max-w-none lg:scale-110" fetchpriority="high">
            </div>
        </div>
    </section>

    <!-- The models marketplace: browse by type and format, a gallery of top models, top stores. Each part hides itself when there is nothing real to show. -->
    @if ($types->isNotEmpty() || $formats->isNotEmpty() || $models->isNotEmpty() || $stores->isNotEmpty())
        <div id="models" class="scroll-mt-16 bg-paper">
            @if ($types->isNotEmpty() || $formats->isNotEmpty())
                <section class="mx-auto max-w-7xl px-4 pt-20 sm:px-6 lg:px-8" aria-labelledby="home-types">
                    <div class="flex flex-wrap items-end justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-wide text-teal-700">{{ __('3D models') }}</p>
                            <h2 id="home-types" class="mt-2 font-tertiary text-4xl font-bold tracking-tight text-neutral-900">{{ __('Browse by type') }}</h2>
                        </div>
                        <a href="{{ route('models.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-teal-700 hover:text-teal-800">{{ __('All models') }}<x-icon name="arrow-right" class="h-4 w-4" /></a>
                    </div>

                    @if ($types->isNotEmpty())
                        {{-- Up to six types fill one row whatever their number; more wrap into even rows of four --}}
                        <ul @class(['mt-8 grid grid-cols-2 gap-3', 'sm:grid-cols-[repeat(auto-fit,minmax(9.5rem,1fr))]' => $types->count() <= 6, 'sm:grid-cols-3 lg:grid-cols-4' => $types->count() > 6])>
                            @foreach ($types as $type)
                                <li>
                                    <a href="{{ route('models.index', ['features' => [$type['key']]]) }}" class="group relative block aspect-[4/3] overflow-hidden rounded-xl bg-neutral-800 ring-1 ring-black/5 focus:outline-none focus-visible:ring-4 focus-visible:ring-secondary/60">
                                        @if ($type['cover'])
                                            <img src="{{ $type['cover']->url() }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.06]">
                                        @endif
                                        <span class="absolute inset-0 bg-gradient-to-t from-neutral-950/80 via-neutral-950/15 to-transparent"></span>
                                        <span class="absolute inset-x-0 bottom-0 block px-3.5 pb-3 text-white">
                                            <span class="block font-tertiary text-base font-semibold leading-tight">{{ __($type['label']) }}</span>
                                            <span class="block text-xs text-white/80">{{ trans_choice(':count model|:count models', $type['count'], ['count' => number_format($type['count'])]) }}</span>
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if ($formats->isNotEmpty())
                        <div class="mt-6 flex flex-wrap items-center gap-2">
                            <p class="mr-1 text-sm font-medium text-neutral-700">{{ __('By file format') }}</p>
                            @foreach ($formats as $format)
                                <a href="{{ route('models.index', ['format' => $format['extension']]) }}" class="inline-flex items-center gap-2 rounded-lg border border-neutral-200 bg-white px-3 py-1.5 text-sm shadow-sm transition-colors hover:border-teal-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">
                                    <span class="font-mono font-semibold uppercase text-neutral-800">{{ $format['extension'] }}</span>
                                    <span class="text-xs tabular-nums text-tertiary">{{ number_format($format['count']) }}</span>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </section>
            @endif

            @if ($models->isNotEmpty())
                @php
                    // A gallery: the first tile is a big 2x2 one, and the count keeps every row full on 2, 3, 4 and 6 columns
                    // (the big tile takes four cells, so 9 or 21 models fill 12 or 24 cells). Few models: an even grid, no big tile.
                    $showBig = $models->count() >= 9;
                    $shown = $models->take($models->count() >= 21 ? 21 : ($showBig ? 9 : $models->count()));
                @endphp
                <section class="mx-auto max-w-7xl px-4 pt-20 sm:px-6 lg:px-8" aria-labelledby="home-models">
                    <div class="flex flex-wrap items-end justify-between gap-3">
                        <div>
                            <h2 id="home-models" class="font-tertiary text-4xl font-bold tracking-tight text-neutral-900">{{ $topRated ? __('Top-rated models') : __('New in the catalogue') }}</h2>
                            <p class="mt-3 text-lg text-neutral-600">{{ $topRated ? __('Loved by the buyers who used them.') : __('The latest models from our sellers.') }}</p>
                        </div>
                        <a href="{{ route('models.index', $topRated ? ['sort' => 'top_rated'] : []) }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-teal-700 hover:text-teal-800">{{ __('Browse all models') }}<x-icon name="arrow-right" class="h-4 w-4" /></a>
                    </div>
                    <div class="mt-8 grid grid-flow-dense grid-cols-2 gap-2 sm:grid-cols-3 sm:gap-3 lg:grid-cols-4 xl:grid-cols-6">
                        @foreach ($shown as $model)<x-models.tile :product="$model" :big="$showBig && $loop->first" />@endforeach
                    </div>
                </section>
            @endif

            @if ($stores->isNotEmpty())
                <section class="mx-auto max-w-7xl px-4 pt-20 sm:px-6 lg:px-8" aria-labelledby="home-stores">
                    <h2 id="home-stores" class="font-tertiary text-4xl font-bold tracking-tight text-neutral-900">{{ __('Top-rated stores') }}</h2>
                    <p class="mt-3 text-lg text-neutral-600">{{ __('Stores with the best reviews from the buyers who used their models.') }}</p>
                    <ul class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($stores as $store)
                            <li>
                                <a href="{{ route('sellers.show', $store->slug) }}" class="flex h-full items-center gap-4 rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm transition-shadow hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">
                                    <x-store-avatar :store="$store" size="h-14 w-14" />
                                    <span class="min-w-0">
                                        <span class="block truncate font-tertiary text-base font-semibold text-neutral-900">{{ $store->display_name }}</span>
                                        <x-models.stars :rating="$store->rating_avg" :count="$store->rating_count" showNumber class="mt-0.5" />
                                        <span class="block text-xs text-tertiary">{{ trans_choice(':count model|:count models', $store->models_count, ['count' => $store->models_count]) }}</span>
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
            <div class="h-20"></div>
        </div>
    @endif

    <!-- Sell your models -->
    <section id="sell" class="scroll-mt-16 bg-white">
        <div class="mx-auto grid max-w-7xl items-center gap-10 px-4 py-20 sm:px-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,24rem)] lg:gap-16 lg:px-8 lg:py-24">
            <div class="max-w-2xl">
                <p class="text-sm font-semibold uppercase tracking-wide text-teal-700">{{ __('Sell your models') }}</p>
                <h2 class="mt-3 font-tertiary text-4xl font-bold tracking-tight text-neutral-900">{{ __('Open your own 3D model store') }}</h2>
                <p class="mt-4 text-lg leading-relaxed text-neutral-600">{{ __('List your models for buyers to find, with a storefront that carries your name, your logo and everything you make.') }}</p>
                <ul class="mt-6 space-y-3 text-neutral-700">
                    @foreach ([
                        __('A storefront of your own, with your bio, logo and every model you list.'),
                        __('Buyers rate and review your models, and your store earns a public rating.'),
                        __('Each listing is checked by our team before it goes live.'),
                        __('Reply to reviews yourself. Your model files stay private on our servers.'),
                    ] as $point)
                        <li class="flex items-start gap-3"><x-icon name="check-circle-solid" class="mt-0.5 h-5 w-5 shrink-0 text-teal-600" /><span>{{ $point }}</span></li>
                    @endforeach
                </ul>
                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <x-btn size="lg" href="{{ route('seller.index') }}">{{ __('Apply to sell') }}</x-btn>
                    <x-btn size="lg" variant="secondary" href="{{ route('models.index') }}">{{ __('See what sellers list') }}</x-btn>
                </div>
                <p class="mt-4 text-sm text-tertiary">{{ __('Free account required. Applications are reviewed by our team, and checkout for buyers is opening soon.') }}</p>
            </div>
            <div class="rounded-3xl bg-paper p-6 sm:p-8">
                <p class="text-sm font-semibold text-neutral-900">{{ __('Bring the files you already have') }}</p>
                <ul class="mt-4 flex flex-wrap gap-2" aria-label="{{ __('Model formats') }}">
                    @foreach (['FBX', 'OBJ', 'GLB', 'BLEND', 'MAX', 'USDZ'] as $format)
                        <li class="rounded-lg border border-neutral-200 bg-white px-3.5 py-2 font-mono text-sm font-semibold text-neutral-600">{{ $format }}</li>
                    @endforeach
                </ul>
                <p class="mt-4 text-sm leading-relaxed text-neutral-600">{{ __('Native files, exchange formats and texture maps, up to :mb MB per file and 20 files per model.', ['mb' => config('marketplace.max_file_mb')]) }}</p>
            </div>
        </div>
    </section>

    <!-- Hire talent -->
    <section id="hire" class="scroll-mt-16 bg-paper">
        <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8 lg:py-24">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-sm font-semibold uppercase tracking-wide text-teal-700">{{ __('Hire talent') }}</p>
                <h2 class="mt-3 font-tertiary text-4xl font-bold tracking-tight text-neutral-900">{{ __('Get your 3D project delivered') }}</h2>
                <p class="mt-4 text-lg text-neutral-600">{{ __('For studios, developers and anyone with a 3D brief.') }}</p>
            </div>
            <div class="mt-14 grid gap-10 md:grid-cols-3">
                @foreach ($hire as [$image, $title, $text])
                    <div class="text-center">
                        <div class="flex h-48 items-center justify-center rounded-3xl bg-white px-6 py-4 ring-1 ring-neutral-200/70">
                            <img src="{{ asset('images/jobs/'.$image) }}" alt="" loading="lazy" class="max-h-full w-auto">
                        </div>
                        <h3 class="mt-6 font-tertiary text-xl font-semibold text-neutral-900">{{ $title }}</h3>
                        <p class="mt-2 leading-relaxed text-neutral-600">{{ $text }}</p>
                    </div>
                @endforeach
            </div>
            <div class="mt-12 text-center">
                <x-btn size="lg" href="{{ route('jobs.create') }}">{{ __('Post a project') }}</x-btn>
                <p class="mt-3 text-sm text-tertiary">{{ __('Free account required. It takes a minute to set up.') }}</p>
            </div>
        </div>
    </section>

    <!-- Find work -->
    <section id="work" class="scroll-mt-16 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8 lg:py-24">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-sm font-semibold uppercase tracking-wide text-teal-700">{{ __('Find work') }}</p>
                <h2 class="mt-3 font-tertiary text-4xl font-bold tracking-tight text-neutral-900">{{ __('Turn your skills into steady work') }}</h2>
                <p class="mt-4 text-lg text-neutral-600">{{ __('For 3D artists, modellers and animators.') }}</p>
            </div>
            <div class="mt-14 grid gap-10 md:grid-cols-3">
                @foreach ($work as [$image, $title, $text])
                    <div class="text-center">
                        <div class="flex h-48 items-center justify-center rounded-3xl bg-paper px-6 py-4">
                            <img src="{{ asset('images/jobs/'.$image) }}" alt="" loading="lazy" class="max-h-full w-auto">
                        </div>
                        <h3 class="mt-6 font-tertiary text-xl font-semibold text-neutral-900">{{ $title }}</h3>
                        <p class="mt-2 leading-relaxed text-neutral-600">{{ $text }}</p>
                    </div>
                @endforeach
            </div>
            <div class="mt-12 text-center">
                <x-btn size="lg" variant="secondary" href="{{ route('jobs.browse') }}">{{ __('Browse projects') }}<x-icon name="arrow-right" class="h-5 w-5" /></x-btn>
                <p class="mt-3 text-sm text-tertiary">{{ __('You can look around freely. A free account is needed to apply.') }}</p>
            </div>
        </div>
    </section>

    <!-- Open projects (no client names or applicant numbers) -->
    @if ($projects->isNotEmpty())
        <section class="bg-paper">
            <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <h2 class="font-tertiary text-4xl font-bold tracking-tight text-neutral-900">{{ __('Open projects right now') }}</h2>
                    <p class="mt-4 text-lg text-neutral-600">{{ __('The newest briefs looking for a freelancer.') }}</p>
                </div>
                <div class="mt-12 grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($projects as $project)
                        <x-jobs.card :job="$project" :compact="true" :public="true" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <!-- How it works -->
    <section id="how" class="scroll-mt-16 {{ $projects->isNotEmpty() ? 'bg-white' : 'bg-paper' }}">
        <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8 lg:py-24">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="font-tertiary text-4xl font-bold tracking-tight text-neutral-900">{{ __('From brief to sign-off in four steps') }}</h2>
            </div>
            <ol class="mt-14 grid gap-10 sm:grid-cols-2 lg:grid-cols-4 lg:gap-6">
                @foreach ($steps as [$title, $text])
                    <li class="relative text-center">
                        <div class="flex items-center justify-center">
                            <span class="relative z-10 flex h-12 w-12 items-center justify-center rounded-full bg-teal-600 font-secondary text-lg font-bold text-white">{{ $loop->iteration }}</span>
                        </div>
                        @unless ($loop->last)
                            {{-- Connector to the next step: only when the four steps sit in one row --}}
                            <span aria-hidden="true" class="absolute left-[calc(50%+2rem)] top-6 hidden h-0.5 w-[calc(100%-2.5rem)] bg-teal-200 lg:block"></span>
                        @endunless
                        <h3 class="mt-5 font-tertiary text-lg font-semibold text-neutral-900">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-neutral-600">{{ $text }}</p>
                    </li>
                @endforeach
            </ol>
            <p class="mt-12 text-center"><a href="{{ route('jobs.index') }}" class="font-medium text-teal-700 underline-offset-4 hover:underline">{{ __('Learn more about how it works') }}<span aria-hidden="true"> →</span></a></p>
        </div>
    </section>

    <!-- Final call to action -->
    <section class="{{ $projects->isNotEmpty() ? 'bg-paper' : 'bg-white' }}">
        <div class="mx-auto max-w-3xl px-4 py-20 text-center sm:px-6 lg:py-24">
            <h2 class="font-tertiary text-4xl font-bold tracking-tight text-neutral-900">{{ __('Ready to get started?') }}</h2>
            <p class="mx-auto mt-4 max-w-xl text-lg text-neutral-600">{{ __('Create a free account, then save models, post your first project, send your first offer or open a store.') }}</p>
            <div class="mt-9 flex flex-wrap justify-center gap-3">
                <x-btn size="lg" href="{{ route('register') }}">{{ __('Join free') }}</x-btn>
                <x-btn size="lg" variant="secondary" href="{{ route('login') }}">{{ __('Sign in') }}</x-btn>
            </div>
        </div>
    </section>
</x-site-layout>
