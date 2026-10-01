@props(['columns', 'sort' => null, 'dir' => 'desc', 'summary' => null, 'paginator' => null])

{{--
    The one staff list table: sticky header, sortable column headings, right-aligned numbers, hover rows, a density switch that
    is remembered, and the pager as its footer.
      columns — list of ['key', 'label', 'sort' => key|null, 'first' => asc|desc (direction of the first click), 'align' => left|right|center, 'class' => extra classes (e.g. 'hidden md:table-cell')]
      summary — a short line shown above the rows ("21 members")
    Rows are <x-staff.row> in the slot.
--}}
<div x-data="{ density: 'comfortable', init() { try { this.density = localStorage.getItem('staff.density') || 'comfortable' } catch (e) {} }, set(d) { this.density = d; try { localStorage.setItem('staff.density', d) } catch (e) {} } }">
    <div class="rounded-xl border border-neutral-200 bg-white shadow-sm">
        <div class="flex items-center justify-between gap-3 rounded-t-xl border-b border-neutral-100 px-4 py-2.5">
            <p class="text-sm text-tertiary">{{ $summary }}</p>
            <div class="flex items-center gap-1 rounded-lg bg-neutral-100 p-0.5" role="group" aria-label="{{ __('Row density') }}">
                <button type="button" @click="set('comfortable')" :aria-pressed="(density === 'comfortable').toString()" :class="density === 'comfortable' ? 'bg-white text-neutral-900 shadow-sm' : 'text-neutral-500 hover:text-neutral-800'" class="rounded-md px-2 py-1 text-xs font-medium transition-colors" title="{{ __('Comfortable rows') }}">{{ __('Comfortable') }}</button>
                <button type="button" @click="set('compact')" :aria-pressed="(density === 'compact').toString()" :class="density === 'compact' ? 'bg-white text-neutral-900 shadow-sm' : 'text-neutral-500 hover:text-neutral-800'" class="rounded-md px-2 py-1 text-xs font-medium transition-colors" title="{{ __('Compact rows') }}">{{ __('Compact') }}</button>
            </div>
        </div>

        <div class="max-md:overflow-x-auto">
            <table class="min-w-full text-sm" :class="density === 'compact' ? '[&_td]:py-2' : '[&_td]:py-3.5'">
                <thead class="md:sticky md:top-16 md:z-10 bg-neutral-50 text-xs uppercase tracking-wider text-neutral-500">
                    <tr>
                        @foreach ($columns as $column)
                            @php
                                $align = $column['align'] ?? 'left';
                                $sortKey = $column['sort'] ?? null;
                                $active = $sortKey && $sort === $sortKey;
                                $next = $active ? ($dir === 'asc' ? 'desc' : 'asc') : ($column['first'] ?? 'asc');
                            @endphp
                            <th scope="col" @if ($active) aria-sort="{{ $dir === 'asc' ? 'ascending' : 'descending' }}" @endif
                                @class(['border-b border-neutral-200 px-4 py-2.5 font-semibold', 'text-left' => $align === 'left', 'text-right' => $align === 'right', 'text-center' => $align === 'center', $column['class'] ?? '', 'first:rounded-tl-xl last:rounded-tr-xl'])>
                                @if ($sortKey)
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => $sortKey, 'dir' => $next, 'page' => null]) }}" wire:navigate class="inline-flex items-center gap-1 rounded transition-colors hover:text-neutral-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40 {{ $active ? 'text-neutral-900' : '' }}">
                                        {{ __($column['label']) }}
                                        <span aria-hidden="true" class="text-[10px] leading-none {{ $active ? 'text-teal-700' : 'text-neutral-300' }}">{{ $active ? ($dir === 'asc' ? '▲' : '▼') : '↕' }}</span>
                                    </a>
                                @else
                                    {{ __($column['label']) }}
                                @endif
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">{{ $slot }}</tbody>
            </table>
        </div>

        @if ($paginator)<x-pager :paginator="$paginator" footer navigate />@endif
    </div>
</div>
