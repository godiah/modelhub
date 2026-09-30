@props(['title'])

<div {{ $attributes->class('rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-3.5') }}>
    <h3 class="text-sm font-semibold text-neutral-900">{{ $title }}</h3>
    <div class="mt-1.5 space-y-2 text-sm text-neutral-700">{{ $slot }}</div>
</div>
