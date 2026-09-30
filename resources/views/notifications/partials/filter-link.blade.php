@php($active = $filter === $item['key'])
<li>
    <a href="{{ $link($item['key']) }}" wire:navigate @if ($active) aria-current="page" @endif
        @class([
            'flex items-center gap-3 rounded-xl border px-3 py-2.5 text-sm font-medium transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40',
            'border-neutral-200/80 bg-paper text-neutral-900 shadow-sm' => $active,
            'border-transparent text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900' => !$active,
        ])>
        <x-icon :name="$item['icon']" :class="'h-5 w-5 shrink-0 ' . ($active ? 'text-teal-700' : 'text-neutral-400')" />
        <span class="flex-1 truncate">{{ $item['label'] }}</span>
        @if ($item['badge'] && $item['count'] > 0)
            <span class="rounded-full bg-red-500 px-2 py-0.5 text-xs font-semibold text-white">{{ $item['count'] }}</span>
        @elseif ($item['count'] > 0)
            <span class="text-xs tabular-nums text-tertiary">{{ $item['count'] }}</span>
        @endif
    </a>
</li>
