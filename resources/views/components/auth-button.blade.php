@props(['variant' => 'primary'])

<button
    {{ $attributes->merge(['type' => 'submit'])->class([
        'relative inline-flex w-full items-center justify-center gap-2 rounded-xl px-6 py-3 font-secondary text-sm font-semibold transition-all duration-200 focus:outline-none focus-visible:ring-4 disabled:cursor-not-allowed disabled:opacity-60',
        'bg-teal-600 text-white hover:bg-teal-700 focus-visible:ring-secondary/40 hover:shadow-lg hover:shadow-secondary/30' => $variant === 'primary',
        'border border-neutral-200 bg-white text-neutral-700 hover:bg-neutral-50 hover:text-neutral-900 focus-visible:ring-neutral-200' => $variant === 'secondary',
    ]) }}
    wire:loading.attr="disabled">
    <x-spinner class="hidden h-4 w-4" wire:loading.class.remove="hidden" {{ $attributes->whereStartsWith('wire:target') }} />
    {{ $slot }}
</button>
