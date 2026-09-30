@props(['title' => null, 'description' => null, 'danger' => false, 'flush' => false])

{{--
    Settings card: title + description on top, body, optional footer for actions (slot `footer`) and header
    actions (slot `actions`). The quiet replacement for the old gradient section headers.
--}}
<section {{ $attributes->class(['overflow-hidden rounded-2xl border bg-white shadow-sm', 'border-neutral-200' => !$danger, 'border-red-200' => $danger]) }}>
    @if ($title || isset($actions))
        <header class="flex items-start justify-between gap-4 px-6 pt-6">
            <div class="min-w-0">
                @if ($title)
                    <h2 @class([
                        'font-tertiary text-base font-semibold',
                        'text-neutral-900' => !$danger,
                        'text-red-800' => $danger,
                    ])>{{ $title }}</h2>
                @endif
                @if ($description)
                    <p class="mt-1 text-sm text-tertiary">{{ $description }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="shrink-0">{{ $actions }}</div>
            @endisset
        </header>
    @endif

    <div @class(['px-6 pb-6', 'pt-5' => $title || isset($actions), 'pt-6' => !$title && !isset($actions), 'px-0 pb-0' => $flush])>
        {{ $slot }}
    </div>

    @isset($footer)
        <footer class="flex flex-wrap items-center justify-between gap-3 border-t border-neutral-100 bg-neutral-50/60 px-6 py-4">
            {{ $footer }}
        </footer>
    @endisset
</section>
