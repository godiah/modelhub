@props([
    // Flip to true once OAuth routes exist. Until then the buttons render visibly inert.
    'enabled' => false,
    'providers' => ['google' => 'Google', 'facebook' => 'Facebook'],
])

<div>
    <div class="grid grid-cols-2 gap-3">
        @foreach ($providers as $key => $name)
            @php
                $classes = 'flex w-full items-center justify-center gap-2.5 rounded-xl border border-neutral-200 bg-white px-4 py-3 font-secondary text-sm font-semibold text-neutral-700 transition-colors duration-200';
            @endphp

            @if ($enabled)
                {{-- Expects a named route such as social.redirect once OAuth is implemented. --}}
                <a href="{{ route('social.redirect', $key) }}" class="{{ $classes }} hover:bg-neutral-50">
                    <x-social-icon :provider="$key" />
                    {{ $name }}
                </a>
            @else
                <button type="button" disabled aria-disabled="true" title="{{ __('Coming soon') }}"
                    class="{{ $classes }} cursor-not-allowed opacity-60">
                    <x-social-icon :provider="$key" />
                    {{ $name }}
                </button>
            @endif
        @endforeach
    </div>

    <div class="relative my-6">
        <div class="absolute inset-0 flex items-center" aria-hidden="true">
            <div class="w-full border-t border-neutral-200"></div>
        </div>
        <div class="relative flex justify-center">
            <span class="bg-white px-4 text-sm text-tertiary">{{ __('or') }}</span>
        </div>
    </div>
</div>
