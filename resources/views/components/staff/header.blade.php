@props(['title', 'description' => null])

{{-- The heading block every staff page opens with: title and one line of context on the left, page actions (slot `actions`) on the right. --}}
<div {{ $attributes->class('mb-6 flex flex-wrap items-end justify-between gap-x-6 gap-y-3') }}>
    <div class="min-w-0">
        <h1 class="flex flex-wrap items-center gap-2 font-tertiary text-2xl font-bold tracking-tight text-neutral-900 sm:text-3xl">{{ $title }}{{ $badges ?? '' }}</h1>
        @if ($description)<p class="mt-1 max-w-2xl text-sm text-tertiary sm:text-base">{{ $description }}</p>@elseif (trim((string) $slot) !== '')<p class="mt-1 max-w-2xl text-sm text-tertiary sm:text-base">{{ $slot }}</p>@endif
    </div>
    @isset($actions)<div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>@endisset
</div>
