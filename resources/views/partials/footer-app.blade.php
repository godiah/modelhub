{{-- Slim footer for the signed-in app shell (guests get the site footer component) --}}
<footer class="border-t border-neutral-200/70 px-4 py-5 sm:px-6 lg:px-8">
    <div
        class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 text-xs text-tertiary sm:flex-row">
        <p>&copy; {{ date('Y') }} {{ config('app.name', 'ModelHub') }}. {{ __('All rights reserved.') }}</p>
        <nav aria-label="{{ __('Legal') }}" class="flex flex-wrap items-center justify-center gap-x-5 gap-y-1">
            <a href="{{ route('legal.terms') }}" wire:navigate class="font-medium text-neutral-600 transition-colors duration-200 hover:text-teal-700">{{ __('Terms') }}</a>
            <a href="{{ route('legal.privacy') }}" wire:navigate class="font-medium text-neutral-600 transition-colors duration-200 hover:text-teal-700">{{ __('Privacy') }}</a>
            <a href="{{ route('engagements.policy') }}" wire:navigate class="font-medium text-neutral-600 transition-colors duration-200 hover:text-teal-700">{{ __('Cancellation & payment policy') }}</a>
        </nav>
    </div>
</footer>
