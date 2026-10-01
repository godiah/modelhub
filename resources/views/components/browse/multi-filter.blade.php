@props(['name', 'label', 'options', 'selected' => []])

{{--
    A dropdown of checkboxes for the browse filter bar. The checkboxes belong to the page's #browse-form
    (form attribute), so the page's own script reads them like any other control. The button shows how many
    are chosen (from the page's `chips`).
--}}
<div x-data="{ open: false, q: '' }" @keydown.escape.window="open = false" @click.outside="open = false" class="relative">
    <button type="button" @click="open = !open" :aria-expanded="open.toString()" aria-haspopup="true"
        class="inline-flex h-11 items-center gap-2 rounded-xl border border-neutral-300 bg-white px-4 text-sm font-medium text-neutral-800 transition-colors hover:border-neutral-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40"
        :class="chips.some(c => c.key === '{{ $name }}') && 'border-teal-600'">
        {{ $label }}
        <span x-show="chips.filter(c => c.key === '{{ $name }}').length" x-cloak x-text="chips.filter(c => c.key === '{{ $name }}').length" class="rounded-full bg-teal-600 px-1.5 text-xs font-semibold text-white"></span>
        <x-icon name="chevron-down" class="h-4 w-4 text-neutral-500" />
    </button>

    <div x-show="open" x-cloak x-transition.opacity.duration.100ms class="absolute left-0 z-30 mt-2 w-72 rounded-2xl border border-neutral-200 bg-white p-3 shadow-lg">
        @if ($options->count() > 8)
            <label class="sr-only" for="filter-{{ $name }}">{{ __('Filter :what', ['what' => strtolower($label)]) }}</label>
            <input type="search" id="filter-{{ $name }}" x-model="q" placeholder="{{ __('Filter :what…', ['what' => strtolower($label)]) }}"
                class="mb-2 block w-full rounded-xl border border-neutral-300 bg-white px-3 py-1.5 text-sm placeholder-neutral-400 focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25">
        @endif
        <ul class="max-h-64 space-y-0.5 overflow-y-auto">
            @forelse ($options as $option)
                <li x-show="!q || @js(strtolower($option->name)).includes(q.toLowerCase())">
                    <label class="flex cursor-pointer items-center gap-2.5 rounded-lg px-2 py-1.5 text-sm text-neutral-700 hover:bg-neutral-50">
                        <input type="checkbox" name="{{ $name }}[]" value="{{ $option->id }}" form="browse-form" @checked(in_array($option->id, $selected, true)) @change="schedule()"
                            class="h-4 w-4 rounded border-neutral-300 text-teal-600 focus:ring-teal-600/30">
                        <span>{{ $option->name }}</span>
                    </label>
                </li>
            @empty
                <li class="px-2 py-1.5 text-sm text-tertiary">{{ __('Nothing to filter by yet.') }}</li>
            @endforelse
        </ul>
    </div>
</div>
