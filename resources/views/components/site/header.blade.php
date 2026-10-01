{{--
    The guest site header: logo, the main sections, sign in / join.
    Active state: pages mark their own item (aria-current="page"); on the landing page a small script marks the
    section being scrolled through (aria-current="location"). `data-spy` names the landing section an item
    belongs to. The mobile menu is a plain <details> (no framework needed).
--}}
@php
    $links = [
        ['label' => __('Hire talent'), 'url' => route('home').'#hire', 'spy' => '#hire', 'routes' => [], 'soon' => false],
        ['label' => __('Find work'), 'url' => route('jobs.browse'), 'spy' => '#work', 'routes' => ['jobs.browse', 'jobs.apply'], 'soon' => false],
        ['label' => __('How it works'), 'url' => route('jobs.index'), 'spy' => null, 'routes' => ['jobs.index'], 'soon' => false],
        ['label' => __('Models'), 'url' => route('home').'#models', 'spy' => '#models', 'routes' => [], 'soon' => true],
    ];
    $linkClass = 'relative inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-neutral-700 transition-colors hover:text-neutral-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40 '
        .'after:absolute after:inset-x-3 after:-bottom-[14px] after:h-0.5 after:rounded-full after:bg-teal-600 after:opacity-0 after:transition-opacity '
        .'data-[active=true]:text-teal-700 data-[active=true]:after:opacity-100';
    $mobileClass = 'flex items-center justify-between rounded-xl px-3 py-2.5 text-sm font-medium text-neutral-800 hover:bg-neutral-50 data-[active=true]:bg-teal-50 data-[active=true]:text-teal-700';
@endphp
<header class="sticky top-0 z-40 border-b border-neutral-200/70 bg-white/90 backdrop-blur">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-6 px-4 sm:px-6 lg:px-8">
        <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2.5 rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40" aria-label="{{ config('app.name') }}">
            <x-application-logo variant="mark" class="h-9 w-auto" />
            <span class="hidden font-secondary text-sm font-bold uppercase tracking-[0.14em] text-neutral-900 sm:inline">{{ config('app.name') }}</span>
        </a>

        <nav aria-label="{{ __('Main') }}" class="hidden items-center gap-1 md:flex">
            @foreach ($links as $link)
                @php $current = $link['routes'] && request()->routeIs(...$link['routes']); @endphp
                <a href="{{ $link['url'] }}" @if ($link['spy']) data-spy="{{ $link['spy'] }}" @endif data-active="{{ $current ? 'true' : 'false' }}" @if ($current) aria-current="page" @endif class="{{ $linkClass }}">
                    {{ $link['label'] }}
                    @if ($link['soon'])
                        <span class="rounded-full bg-teal-50 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-teal-700">{{ __('Soon') }}</span>
                    @endif
                </a>
            @endforeach
        </nav>

        <div class="flex items-center gap-2">
            <x-btn variant="ghost" size="sm" href="{{ route('login') }}" class="hidden sm:inline-flex">{{ __('Sign in') }}</x-btn>
            <x-btn size="sm" href="{{ route('register') }}">{{ __('Join free') }}</x-btn>

            <details class="group relative md:hidden">
                <summary class="flex h-9 w-9 cursor-pointer list-none items-center justify-center rounded-lg text-neutral-700 hover:bg-neutral-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40 [&::-webkit-details-marker]:hidden" aria-label="{{ __('Menu') }}">
                    <x-icon name="bars-3" class="h-6 w-6 group-open:hidden" />
                    <x-icon name="x-mark" class="hidden h-6 w-6 group-open:block" />
                </summary>
                <div class="absolute right-0 top-11 w-60 rounded-2xl border border-neutral-200 bg-white p-2 shadow-lg">
                    @foreach ($links as $link)
                        @php $current = $link['routes'] && request()->routeIs(...$link['routes']); @endphp
                        <a href="{{ $link['url'] }}" @if ($link['spy']) data-spy="{{ $link['spy'] }}" @endif data-active="{{ $current ? 'true' : 'false' }}" @if ($current) aria-current="page" @endif
                            onclick="this.closest('details').removeAttribute('open')" class="{{ $mobileClass }}">
                            {{ $link['label'] }}
                            @if ($link['soon'])
                                <span class="rounded-full bg-teal-50 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-teal-700">{{ __('Soon') }}</span>
                            @endif
                        </a>
                    @endforeach
                    <a href="{{ route('login') }}" class="mt-1 block rounded-xl border-t border-neutral-100 px-3 py-2.5 text-sm font-medium text-neutral-800 hover:bg-neutral-50">{{ __('Sign in') }}</a>
                </div>
            </details>
        </div>
    </div>
</header>

<script>
    // Landing page only: mark the nav item for the section currently in view.
    document.addEventListener('DOMContentLoaded', function () {
        var links = [].slice.call(document.querySelectorAll('[data-spy]'));
        var sections = {};
        links.forEach(function (a) { var el = document.querySelector(a.getAttribute('data-spy')); if (el) sections[a.getAttribute('data-spy')] = el; });
        var ids = Object.keys(sections);
        if (!ids.length || !('IntersectionObserver' in window)) return;

        var inView = {};
        function paint() {
            var active = null;
            ids.forEach(function (id) { if (inView[id]) active = id; });
            links.forEach(function (a) {
                var on = a.getAttribute('data-spy') === active;
                a.setAttribute('data-active', on ? 'true' : 'false');
                if (on) a.setAttribute('aria-current', 'location'); else a.removeAttribute('aria-current');
            });
        }
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) { inView['#' + entry.target.id] = entry.isIntersecting; });
            paint();
        }, { rootMargin: '-35% 0px -55% 0px' });
        ids.forEach(function (id) { observer.observe(sections[id]); });
    });
</script>
