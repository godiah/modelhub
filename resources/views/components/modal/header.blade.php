@props(['title', 'icon' => null])

{{-- Modal title bar: quiet white with a divider, like every other surface. Colour lives in the buttons and alerts, not the header. --}}
<div {{ $attributes->class('flex items-center justify-between border-b border-neutral-200 px-6 py-4') }}>
    <h3 class="flex items-center font-tertiary text-lg font-semibold text-neutral-900">
        @if ($icon)
            <x-icon :name="$icon" class="mr-2 h-5 w-5 text-teal-600" />
        @endif
        {{ $title }}
    </h3>

    <button type="button" x-on:click="dismiss()" aria-label="Close"
        class="rounded-lg p-1 text-neutral-400 transition-colors hover:text-neutral-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">
        <x-icon name="x-mark" class="h-5 w-5" />
    </button>
</div>
