@props(['id', 'number', 'title'])

{{-- One numbered policy section; `id` is the anchor the table of contents links to. --}}
<section id="{{ $id }}" aria-labelledby="{{ $id }}-title" class="scroll-mt-24 border-b border-neutral-200 py-8 first:pt-0 last:border-b-0 last:pb-0">
    <h2 id="{{ $id }}-title" class="flex items-baseline gap-3 font-tertiary text-xl font-semibold text-neutral-900">
        <span class="text-sm font-semibold tabular-nums text-teal-700">{{ str_pad($number, 2, '0', STR_PAD_LEFT) }}</span>
        {{ $title }}
    </h2>
    <div {{ $attributes->class('mt-4 space-y-4 text-sm leading-relaxed text-neutral-700') }}>
        {{ $slot }}
    </div>
</section>
