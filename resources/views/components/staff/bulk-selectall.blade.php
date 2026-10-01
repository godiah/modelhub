{{-- "Select all on this page" for lists made of cards (tables have it in their header). Inside <x-staff.bulk>. --}}
<label {{ $attributes->class('mb-3 inline-flex cursor-pointer items-center gap-2 text-sm text-neutral-600 hover:text-neutral-900') }}>
    <input type="checkbox" :checked="all" x-effect="$el.indeterminate = some" @change="toggleAll()" class="h-4 w-4 rounded border-neutral-300 text-teal-600 focus:ring-teal-600/30">
    {{ __('Select all on this page') }}
</label>
