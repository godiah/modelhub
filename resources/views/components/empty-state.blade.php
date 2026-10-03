@props(['icon', 'title', 'description' => null, 'framed' => true, 'compact' => false])

{{--
    What a page shows when there is nothing to list yet: a tinted icon, a title, one or two sentences and the thing to do about it (the slot, usually
    one or two buttons). This is the one design for it, as first set out on "My licences".
      - framed (default): the whole thing as a card with an inset frame, for a page or a tab that has nothing in it.
      - :framed="false": just the centred content, for an empty list inside a card, panel or table that already has its own frame.
      - compact: less padding, for a small panel.
--}}
@php
    $inner = ['flex flex-col items-center justify-center text-center font-main', $compact ? 'px-6 py-8' : 'px-6 py-12 sm:p-12'];
@endphp
@if ($framed)
    <div data-empty-state class="rounded-2xl border border-neutral-200 bg-white p-2 shadow-sm">
        <div {{ $attributes->class(array_merge($inner, ['rounded-xl border border-neutral-200'])) }}>
@else
    <div data-empty-state {{ $attributes->class($inner) }}>
@endif
        <div class="mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-primary/10">
            <x-icon :name="$icon" class="h-8 w-8 text-primary" />
        </div>

        <h3 class="mb-2 text-lg font-semibold text-neutral-800">{{ $title }}</h3>

        @if ($description)
            <p class="max-w-md text-neutral-600">{{ $description }}</p>
        @endif

        @if (trim((string) $slot) !== '')
            <div class="mt-6 flex flex-wrap items-center justify-center gap-3">{{ $slot }}</div>
        @endif
@if ($framed)
        </div>
    </div>
@else
    </div>
@endif
