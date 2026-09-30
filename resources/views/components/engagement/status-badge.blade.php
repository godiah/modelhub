@props(['status', 'iconClass' => 'w-3 h-3 mr-1'])

@php
    $badge = $status->badgeClasses();
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-full border', $badge['bg'], $badge['text'], $badge['border']]) }}>
    <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        {!! $status->iconPath() !!}
    </svg>
    {{ $status->label() }}
</span>
