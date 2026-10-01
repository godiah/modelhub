{{-- The guest site footer. Only real destinations: no placeholder links, social icons or dead forms. --}}
@php
    $support = config('mail.support_address');
    $columns = [
        __('Hire') => [
            [__('Post a project'), route('jobs.create')],
            [__('How hiring works'), route('home').'#how'],
        ],
        __('Work') => [
            [__('Browse projects'), route('jobs.browse')],
            [__('Create an account'), route('register')],
        ],
        __('Account') => [
            [__('Sign in'), route('login')],
            [__('Join free'), route('register')],
        ],
    ];
@endphp
<footer class="border-t border-neutral-200 bg-white">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 gap-8 md:grid-cols-[minmax(0,1.6fr)_repeat(3,minmax(0,1fr))]">
            <div class="col-span-2 md:col-span-1">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2.5">
                    <x-application-logo variant="mark" class="h-9 w-auto" />
                    <span class="font-secondary text-sm font-bold uppercase tracking-[0.14em] text-neutral-900">{{ config('app.name') }}</span>
                </a>
                <p class="mt-4 max-w-xs text-sm leading-relaxed text-tertiary">{{ __('Where 3D work gets done: post or find a project, agree the deliverables, and settle fairly.') }}</p>
                @if ($support)
                    <p class="mt-4 text-sm"><a href="mailto:{{ $support }}" class="font-medium text-teal-700 hover:underline">{{ $support }}</a></p>
                @endif
            </div>

            @foreach ($columns as $heading => $items)
                <nav aria-label="{{ $heading }}">
                    <h2 class="text-xs font-semibold uppercase tracking-wide text-neutral-900">{{ $heading }}</h2>
                    <ul class="mt-4 space-y-2.5">
                        @foreach ($items as [$label, $url])
                            <li><a href="{{ $url }}" class="text-sm text-tertiary transition-colors hover:text-neutral-900">{{ $label }}</a></li>
                        @endforeach
                    </ul>
                </nav>
            @endforeach
        </div>

        <div class="mt-10 border-t border-neutral-100 pt-6 text-sm text-tertiary">
            &copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('All rights reserved.') }}
        </div>
    </div>
</footer>
