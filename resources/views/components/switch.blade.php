@props(['name', 'label', 'help' => null, 'checked' => false])

{{-- A labelled on/off switch inside a form. Posts "1" when on and nothing when off (a hidden 0 is sent first so the field is always present). --}}
<label for="{{ $attributes->get('id', $name) }}" class="flex cursor-pointer items-start justify-between gap-4 py-3.5">
    <span class="min-w-0">
        <span class="block text-sm font-medium text-neutral-900">{{ $label }}</span>
        @if ($help)<span class="mt-0.5 block text-sm text-tertiary">{{ $help }}</span>@endif
    </span>
    <input type="hidden" name="{{ $name }}" value="0">
    <span class="relative mt-0.5 inline-flex shrink-0">
        <input type="checkbox" id="{{ $attributes->get('id', $name) }}" name="{{ $name }}" value="1" @checked($checked) class="peer sr-only">
        <span class="h-6 w-11 rounded-full bg-neutral-300 transition-colors peer-checked:bg-secondary peer-focus-visible:ring-2 peer-focus-visible:ring-secondary/40 peer-focus-visible:ring-offset-2"></span>
        <span class="pointer-events-none absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform peer-checked:translate-x-5"></span>
    </span>
</label>
