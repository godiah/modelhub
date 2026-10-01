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
<x-site-layout :description="__('Post a 3D project or find one. Compare offers, track every deliverable to sign-off, and build a reputation with reviews.')">
    <!-- Hero -->
    <section class="overflow-hidden bg-white">
        <div class="mx-auto grid max-w-7xl items-center gap-10 px-4 pb-12 pt-12 sm:px-6 sm:pt-16 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)] lg:gap-6 lg:px-8 lg:pb-20 lg:pt-20">
            <div class="relative z-10">
                <h1 class="font-tertiary text-5xl font-bold leading-[1.05] tracking-tight text-neutral-900 sm:text-6xl">
                    {{ __('Meet the best') }} <span class="text-teal-600">{{ __('3D talent') }}</span>.<br class="hidden sm:block">
                    {{ __('Or become it.') }}
                </h1>
                <p class="mt-6 max-w-xl text-xl leading-relaxed text-neutral-600">
                    {{ __('Post a 3D project and hire from the offers that come in, or browse open briefs and send yours. Deliverables, messages and reviews all live in one place.') }}
                </p>
                <div class="mt-9 flex flex-wrap items-center gap-3">
                    <x-btn size="lg" href="{{ route('jobs.create') }}">
                        {{ __('Post a project') }}
                    </x-btn>
                    <x-btn size="lg" variant="secondary" href="{{ route('jobs.browse') }}">
                        {{ __('Find 3D work') }}
                        <x-icon name="arrow-right" class="h-5 w-5" />
                    </x-btn>
                </div>
                <p class="mt-4 flex items-center gap-2 text-sm text-tertiary">
                    <x-icon name="user" class="h-4 w-4" />
                    {{ __('Posting a project or applying needs a free account. You will be asked to sign in or join first.') }}
                </p>
            </div>

            <div class="relative">
                <img src="{{ asset('images/jobs/hero-post-job.webp') }}" width="1200" height="720" alt="{{ __('A 3D architectural visualisation of a modern glass house') }}"
                    class="mx-auto w-full max-w-2xl select-none lg:max-w-none lg:scale-110" fetchpriority="high">
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
    <section id="how" class="scroll-mt-16 bg-white">
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

    <!-- Models marketplace teaser -->
    <section id="models" class="scroll-mt-16 bg-paper">
        <div class="mx-auto grid max-w-7xl items-center gap-10 px-4 py-20 sm:px-6 lg:grid-cols-[minmax(0,1fr)_auto] lg:px-8 lg:py-24">
            <div class="max-w-2xl">
                <p class="inline-flex items-center rounded-full bg-teal-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-teal-800">{{ __('Coming soon') }}</p>
                <h2 class="mt-4 font-tertiary text-4xl font-bold tracking-tight text-neutral-900">{{ __('A marketplace for 3D models') }}</h2>
                <p class="mt-4 text-lg leading-relaxed text-neutral-600">
                    {{ __('Soon you will be able to sell your own 3D models and buy ready-made assets, with licensed downloads and payouts, all on the account you already use for projects. Members hear first.') }}
                </p>
                <div class="mt-7"><x-btn variant="secondary" href="{{ route('register') }}">{{ __('Create your account') }}</x-btn></div>
            </div>
            <ul class="flex flex-wrap gap-2 lg:max-w-[16rem] lg:justify-end" aria-label="{{ __('Model formats') }}">
                @foreach (['FBX', 'OBJ', 'GLB', 'BLEND', 'MAX', 'USDZ'] as $format)
                    <li class="rounded-lg border border-neutral-200 bg-white px-3.5 py-2 font-mono text-sm font-semibold text-neutral-600">{{ $format }}</li>
                @endforeach
            </ul>
        </div>
    </section>

    <!-- Final call to action -->
    <section class="bg-white">
        <div class="mx-auto max-w-3xl px-4 py-20 text-center sm:px-6 lg:py-24">
            <h2 class="font-tertiary text-4xl font-bold tracking-tight text-neutral-900">{{ __('Ready to get started?') }}</h2>
            <p class="mx-auto mt-4 max-w-xl text-lg text-neutral-600">{{ __('Create a free account, then post your first project or send your first offer.') }}</p>
            <div class="mt-9 flex flex-wrap justify-center gap-3">
                <x-btn size="lg" href="{{ route('register') }}">{{ __('Join free') }}</x-btn>
                <x-btn size="lg" variant="secondary" href="{{ route('login') }}">{{ __('Sign in') }}</x-btn>
            </div>
        </div>
    </section>
</x-site-layout>
