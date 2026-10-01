@php
    $content = [
        'hire' => [
            'tab' => __('Hire 3D freelancers'),
            'title' => __('Hire the right 3D artist for your project'),
            'lead' => __('Post your brief, compare the offers that come in, and manage the whole project in one place.'),
            'cta' => [__('Post a project'), route('jobs.create')],
            'note' => __('A free account is needed to post. You will be asked to sign in or join first.'),
            'hero' => asset('images/jobs/hero-post-job.webp'),
            'whyTitle' => __('Make your next 3D project a success'),
            'why' => [
                ['undraw_people.svg', __('Offers from people who want the job'), __('Applicants send their own price, a written proposal and portfolio samples. You see them side by side, filter by status and message anyone with your own or ready-made templates.')],
                ['undraw_quality_work.svg', __('Deliverables you can hold to account'), __('Split the job into deliverables with due dates. The freelancer submits each one for review, and you approve it or ask for changes.')],
                ['undraw_secure_payment.svg', __('A fair way out if plans change'), __('If a project stops part way, completed work can be settled with a partial payment. If you and the freelancer cannot agree, an administrator reviews the case.')],
            ],
            'stepsTitle' => __('Get your project done in 3 steps'),
            'steps' => [
                ['undraw_describe.svg', __('Describe your project'), __('Tell us what you need: a title, the brief, your budget, a deadline and the skills and software you want. Add a cover image and extra pictures to show what you mean.')],
                ['undraw_hiring.svg', __('Choose your freelancer'), __('Review the applications as they arrive, read the proposals, check portfolios and message candidates. When you are ready, hire one and send them the offer.')],
                ['undraw_money.svg', __('Approve the work'), __('Follow progress in a shared workspace, approve each deliverable, and leave a review when the project is done.')],
            ],
            'closing' => __('Hire the best 3D talent today'),
            'closingText' => __('Post your project for free and see how easy it is to get your next 3D challenge solved.'),
        ],
        'work' => [
            'tab' => __('Find 3D jobs'),
            'title' => __('Find 3D projects that fit your skills'),
            'lead' => __('Browse open briefs, send your offer, and build a reputation one delivered project at a time.'),
            'cta' => [__('Browse projects'), route('jobs.browse')],
            'note' => __('You can look around freely. A free account is needed to apply.'),
            'hero' => asset('images/jobs/hero-find-job.avif'),
            'whyTitle' => __('Work on your terms'),
            'why' => [
                ['undraw_savings.svg', __('You set the price'), __('Name your own offer. Before you send it, you see the service fee and exactly what you would receive.')],
                ['undraw_slider.svg', __('Find the right fit quickly'), __('Filter projects by skill, software, budget and how recently they were posted, and save a draft application until your proposal is ready.')],
                ['undraw_completing.svg', __('Build a name that follows you'), __('Deliver against agreed deliverables, collect reviews from clients, and carry them to your profile.')],
            ],
            'stepsTitle' => __('Start earning in 3 steps'),
            'steps' => [
                ['undraw_add-user.svg', __('Create your profile'), __('Join for free, add your skills, software and a short bio so clients know what you do best.')],
                ['undraw_file-search.svg', __('Find a project and apply'), __('Browse open projects, read the brief, then send your offer with a proposal and portfolio samples.')],
                ['undraw_completing.svg', __('Deliver and get reviewed'), __('If you are hired, accept the offer, submit each deliverable for approval and finish with a review.')],
            ],
            'closing' => __('Ready to find your next project?'),
            'closingText' => __('Join for free, set up your profile and start sending offers today.'),
        ],
    ][$audience];
@endphp
<x-app-layout title="How it works" crumb="How it works">
    <!-- Audience switch -->
    <div class="border-b border-neutral-200 bg-white">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
            <nav class="flex items-center gap-6 text-sm font-medium" aria-label="{{ __('On this page') }}">
                <a href="#why" class="text-neutral-700 hover:text-teal-700">{{ __('Why ModelHub') }}</a>
                <a href="#steps" class="text-neutral-700 hover:text-teal-700">{{ __('How it works') }}</a>
            </nav>
            <div class="inline-flex rounded-xl border border-neutral-200 bg-neutral-50 p-1" role="group" aria-label="{{ __('Choose your path') }}">
                @foreach (['hire' => __('Hire 3D freelancers'), 'work' => __('Find 3D jobs')] as $key => $label)
                    <a href="{{ route('jobs.index', ['for' => $key]) }}" @if ($audience === $key) aria-current="page" @endif
                        @class(['rounded-lg px-4 py-2 text-sm font-semibold transition-colors', 'bg-teal-600 text-white shadow-sm' => $audience === $key, 'text-neutral-700 hover:text-neutral-900' => $audience !== $key])>{{ $label }}</a>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Hero -->
    <section class="overflow-hidden bg-white">
        <div class="mx-auto grid max-w-7xl items-center gap-8 px-4 py-12 sm:px-6 lg:grid-cols-2 lg:px-8 lg:py-16">
            <div>
                <h1 class="font-tertiary text-4xl font-bold leading-tight tracking-tight text-neutral-900 sm:text-5xl">{{ $content['title'] }}</h1>
                <p class="mt-5 max-w-xl text-lg leading-relaxed text-neutral-600">{{ $content['lead'] }}</p>
                <div class="mt-8"><x-btn size="lg" href="{{ $content['cta'][1] }}">{{ $content['cta'][0] }}</x-btn></div>
                <p class="mt-3 text-sm text-tertiary">{{ $content['note'] }}</p>
            </div>
            <div class="flex justify-center">
                <img src="{{ $content['hero'] }}" alt="" class="max-h-80 w-auto select-none lg:max-h-96">
            </div>
        </div>
    </section>

    <!-- Why -->
    <section id="why" class="scroll-mt-16 bg-paper">
        <div class="mx-auto max-w-5xl px-4 py-20 sm:px-6 lg:px-8">
            <h2 class="text-center font-tertiary text-3xl font-bold tracking-tight text-neutral-900 sm:text-4xl">{{ $content['whyTitle'] }}</h2>
            <div class="mt-16 space-y-20">
                @foreach ($content['why'] as [$image, $title, $text])
                    <div class="grid items-center gap-8 md:grid-cols-2 md:gap-14">
                        <div @class(['flex justify-center', 'md:order-2' => $loop->even])>
                            <img src="{{ asset('images/jobs/'.$image) }}" alt="" loading="lazy" class="h-56 w-auto">
                        </div>
                        <div @class(['md:order-1' => $loop->even])>
                            <h3 class="font-tertiary text-2xl font-semibold text-neutral-900">{{ $title }}</h3>
                            <p class="mt-3 text-lg leading-relaxed text-neutral-600">{{ $text }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-16 text-center"><x-btn size="lg" href="{{ $content['cta'][1] }}">{{ $content['cta'][0] }}</x-btn></div>
        </div>
    </section>

    <!-- Steps -->
    <section id="steps" class="scroll-mt-16 bg-white">
        <div class="mx-auto max-w-4xl px-4 py-20 sm:px-6 lg:px-8">
            <h2 class="text-center font-tertiary text-3xl font-bold tracking-tight text-neutral-900 sm:text-4xl">{{ $content['stepsTitle'] }}</h2>
            <ol class="relative mt-16 space-y-16 before:absolute before:bottom-6 before:left-5 before:top-6 before:border-l-2 before:border-dashed before:border-neutral-300 md:before:left-[calc(50%-0.5px)]">
                @foreach ($content['steps'] as [$image, $title, $text])
                    <li class="relative grid items-center gap-6 pl-14 md:grid-cols-2 md:gap-16 md:pl-0">
                        <span class="absolute left-0 top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-teal-600 font-secondary font-bold text-white md:left-1/2 md:-translate-x-1/2">{{ $loop->iteration }}</span>
                        <div @class(['flex md:justify-end', 'md:order-2 md:justify-start' => $loop->even])>
                            <img src="{{ asset('images/jobs/'.$image) }}" alt="" loading="lazy" class="h-44 w-auto">
                        </div>
                        <div @class(['md:order-1' => $loop->even])>
                            <h3 class="font-tertiary text-xl font-semibold text-neutral-900">{{ $title }}</h3>
                            <p class="mt-2 leading-relaxed text-neutral-600">{{ $text }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    <!-- Closing call to action -->
    <section class="bg-paper">
        <div class="mx-auto max-w-3xl px-4 py-20 text-center sm:px-6">
            <h2 class="font-tertiary text-3xl font-bold tracking-tight text-neutral-900 sm:text-4xl">{{ $content['closing'] }}</h2>
            <p class="mx-auto mt-4 max-w-xl text-lg text-neutral-600">{{ $content['closingText'] }}</p>
            <div class="mt-8"><x-btn size="lg" href="{{ $content['cta'][1] }}">{{ $content['cta'][0] }}</x-btn></div>
            <p class="mt-3 text-sm text-tertiary">{{ $content['note'] }}</p>
        </div>
    </section>
</x-app-layout>
