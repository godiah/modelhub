@props(['icon' => null, 'iconColor' => 'text-neutral-500', 'badge' => null, 'badgeColor' => 'bg-accent'])

<a
    {{ $attributes->merge(['class' => 'flex items-center w-full px-4 py-3 text-start text-sm text-neutral-700 hover:bg-secondary/10 hover:text-secondary focus:outline-none focus:bg-secondary/10 focus:text-secondary transition-all duration-200 font-main']) }}>
    @if ($icon)
        <svg class="h-4 w-4 mr-3 {{ $iconColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            {!! $icon !!}
        </svg>
    @endif

    <span class="flex-1">{{ $slot }}</span>

    @if ($badge)
        <span class="ml-auto {{ $badgeColor }} text-white rounded-full text-xs px-1.5 py-0.5 font-medium">
            {{ $badge }}
        </span>
    @endif
</a>
