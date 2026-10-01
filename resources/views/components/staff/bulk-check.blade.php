@props(['value', 'label' => null])

{{-- The tick box of one row or card in a bulk list (inside <x-staff.bulk>). --}}
<input type="checkbox" x-model="selected" value="{{ $value }}" aria-label="{{ $label ?? __('Select') }}" {{ $attributes->class('h-4 w-4 shrink-0 cursor-pointer rounded border-neutral-300 text-teal-600 focus:ring-teal-600/30') }}>
