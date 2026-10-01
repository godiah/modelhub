@props(['href' => null, 'select' => null])

{{-- A table row. With an href the whole row is clickable (the link inside it is what keyboards and screen readers use). select: an id adds a tick box; false keeps the column but leaves it empty (a row that cannot be selected); null means the table has no selection. --}}
<tr {{ $attributes->class(['transition-colors hover:bg-neutral-50', 'cursor-pointer' => $href]) }} @if ($select !== null && $select !== false) :class="selected.includes('{{ $select }}') ? 'bg-teal-50/60' : ''" @endif
    @if ($href) x-data @click="if (! $event.target.closest('a, button, input, select, label, summary')) Livewire.navigate(@js($href))" @endif>@if ($select !== null)<td class="w-10 px-4">@if ($select !== false)<x-staff.bulk-check :value="$select" />@endif</td>@endif{{ $slot }}</tr>
