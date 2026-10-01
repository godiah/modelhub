@props(['href' => null])

{{-- A table row. With an href the whole row is clickable (the link inside it is what keyboards and screen readers use). --}}
<tr {{ $attributes->class(['transition-colors hover:bg-neutral-50', 'cursor-pointer' => $href]) }}
    @if ($href) x-data @click="if (! $event.target.closest('a, button, input, select, label, summary')) Livewire.navigate(@js($href))" @endif>{{ $slot }}</tr>
