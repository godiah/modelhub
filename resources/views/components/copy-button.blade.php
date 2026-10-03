@props(['text', 'label' => null])

{{-- Copies `text` to the clipboard and says so for a moment. --}}
<button type="button" x-data="{ copied: false }" @click="navigator.clipboard.writeText(@js($text)).then(() => { copied = true; setTimeout(() => copied = false, 1600) })"
    {{ $attributes->class('inline-flex items-center gap-1.5 rounded-lg text-xs font-medium text-teal-700 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40') }}>
    <x-icon name="clipboard-document" class="h-3.5 w-3.5" />
    <span x-show="! copied">{{ $label ?? __('Copy') }}</span><span x-show="copied" x-cloak>{{ __('Copied') }}</span>
</button>
