{{--
    Category choice in two steps (CGTrader-style): a top-level category, then a sub-category when it has any.
    Posts one `category_id` (the most specific one chosen). Needs $categories (top-level with children) and
    optionally $selected (a Category with its parent loaded).
--}}
@php
    $fieldClass = 'block w-full rounded-xl border border-neutral-300 bg-white px-3 py-2.5 text-sm text-neutral-900 transition-colors hover:border-neutral-400 focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25';
    $tree = $categories->map(fn ($top) => ['id' => $top->id, 'name' => $top->name, 'children' => $top->children->map(fn ($child) => ['id' => $child->id, 'name' => $child->name])->values()])->values();
    $oldId = (int) old('category_id', $selected?->id);
    $oldTop = $selected?->parent_id ?? $selected?->id;
    foreach ($tree as $top) {
        if (collect($top['children'])->contains('id', $oldId)) { $oldTop = $top['id']; }
    }
@endphp
<div x-data="{
    tree: @js($tree),
    top: @js($oldTop ? (string) $oldTop : ''),
    sub: @js(($oldTop && $oldId && $oldId !== (int) $oldTop) ? (string) $oldId : ''),
    get children() { return (this.tree.find(t => String(t.id) === this.top)?.children) ?? []; },
    get chosen() { return this.children.length ? this.sub : this.top; },
}" x-effect="if (!children.some(c => String(c.id) === sub)) sub = ''" class="grid gap-4 sm:grid-cols-2">
    <div>
        <label for="top_category" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('Category') }}</label>
        <select id="top_category" x-model="top" class="{{ $fieldClass }}">
            <option value="">{{ __('Choose…') }}</option>
            <template x-for="t in tree" :key="t.id"><option :value="t.id" x-text="t.name" :selected="String(t.id) === top"></option></template>
        </select>
    </div>
    <div x-show="children.length" x-cloak>
        <label for="sub_category" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('Sub-category') }}</label>
        <select id="sub_category" x-model="sub" class="{{ $fieldClass }}">
            <option value="">{{ __('Choose…') }}</option>
            <template x-for="c in children" :key="c.id"><option :value="c.id" x-text="c.name" :selected="String(c.id) === sub"></option></template>
        </select>
    </div>
    <input type="hidden" name="category_id" :value="chosen">
    @error('category_id')<p class="text-xs text-red-600 sm:col-span-2" role="alert">{{ $message }}</p>@enderror
</div>
