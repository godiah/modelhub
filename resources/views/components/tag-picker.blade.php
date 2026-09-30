@props([
    'field',           // Livewire property that receives the selected ids
    'label',
    'items',           // [['id' => 1, 'name' => 'Blender'], ...]
    'selected' => [],
    'placeholder' => null,
    'hint' => null,
    'tone' => 'teal',  // chip colour: teal|neutral
])

@php
    $chip = ['teal' => 'bg-teal-50 text-teal-800 ring-teal-600/15', 'neutral' => 'bg-neutral-100 text-neutral-800 ring-neutral-500/15'][$tone];
    $messages = $errors->get($field);
@endphp

<div x-data="multiSelect('{{ $field }}', @js($items), @js($selected))" @click.outside="showDropdown = false" class="relative">
    <label for="{{ $field }}-search" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ $label }}</label>

    <div @click="$refs.search.focus()"
        class="flex min-h-[2.75rem] cursor-text flex-wrap items-center gap-2 rounded-xl border border-neutral-300 bg-white px-3 py-2 transition-colors duration-200 hover:border-neutral-400 focus-within:border-secondary focus-within:ring-2 focus-within:ring-secondary/25">
        <template x-for="item in selectedItems" :key="item.id">
            <span class="inline-flex items-center gap-1 rounded-full py-1 pl-3 pr-1.5 text-xs font-medium ring-1 ring-inset {{ $chip }}">
                <span x-text="item.name"></span>
                <button type="button" @click.stop="removeItem(item)" :aria-label="'{{ __('Remove') }} ' + item.name"
                    class="rounded-full p-0.5 opacity-60 transition-opacity hover:opacity-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-current">
                    <x-icon name="x-mark" class="h-3.5 w-3.5" />
                </button>
            </span>
        </template>

        <input id="{{ $field }}-search" x-ref="search" type="text" x-model="searchTerm" @focus="showDropdown = true"
            @keydown.escape="showDropdown = false" @keydown.backspace="removeLast()"
            placeholder="{{ $placeholder ?? __('Search…') }}" autocomplete="off"
            class="min-w-[8rem] flex-1 border-0 bg-transparent p-0 py-0.5 text-sm text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-0">
    </div>

    <!-- Options -->
    <div x-show="showDropdown && filteredItems.length > 0" x-cloak x-transition.opacity.duration.100ms
        class="absolute z-20 mt-2 max-h-60 w-full overflow-y-auto rounded-xl border border-neutral-200 bg-white p-1.5 shadow-lg">
        <template x-for="item in filteredItems" :key="item.id">
            <button type="button" @click="addItem(item)"
                class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm text-neutral-700 transition-colors hover:bg-neutral-50 focus:bg-neutral-50 focus:outline-none">
                <span x-text="item.name"></span>
                <x-icon name="plus" class="h-4 w-4 text-neutral-400" />
            </button>
        </template>
    </div>

    @if ($hint)
        <p class="mt-1.5 text-xs text-tertiary">{{ $hint }}</p>
    @endif
    @if (count($messages))
        <x-input-error :messages="$messages" class="mt-1.5" />
    @endif
</div>

{{-- Defined inline (not in app.js): Livewire starts Alpine before deferred module scripts run, so x-data would fail. --}}
@once
    <script>
/**
 * Alpine data for the tag picker component: pick several items from a searchable list, shown as removable chips.
 * The selection is written to the Livewire property named `field` without a network round trip; it is
 * sent with the next form submit.
 */
window.multiSelect = function (field, allItems, selectedIds = []) {
    return {
        searchTerm: "",
        showDropdown: false,
        allItems,
        selectedItems: allItems.filter((item) => selectedIds.includes(item.id)),

        get filteredItems() {
            const term = this.searchTerm.trim().toLowerCase();

            return this.allItems.filter(
                (item) =>
                    !this.isSelected(item) &&
                    item.name.toLowerCase().includes(term)
            );
        },

        isSelected(item) {
            return this.selectedItems.some((selected) => selected.id === item.id);
        },

        addItem(item) {
            this.selectedItems.push(item);
            this.searchTerm = "";
            this.sync();
        },

        removeItem(item) {
            this.selectedItems = this.selectedItems.filter((selected) => selected.id !== item.id);
            this.sync();
        },

        // Backspace on an empty search box removes the last chip, like most tag inputs.
        removeLast() {
            if (this.searchTerm === "" && this.selectedItems.length > 0) {
                this.removeItem(this.selectedItems[this.selectedItems.length - 1]);
            }
        },

        sync() {
            this.$wire.$set(field, this.selectedItems.map((item) => item.id), false);
        },
    };
};
    </script>
@endonce
