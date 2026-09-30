@props(['steps'])

{{--
    Horizontal progress steps for multi-party flows (settlement, dispute). Each step: ['label' => string,
    'state' => 'done' | 'current' | 'todo' | 'failed']. Stacks vertically on phones. A connector line joins each
    step to the next; it is teal once the step before it is done.
--}}
<ol {{ $attributes->class('grid gap-3 sm:grid-flow-col sm:auto-cols-fr') }}>
    @foreach ($steps as $step)
        @php $state = $step['state']; @endphp
        <li class="relative flex items-center gap-3 sm:flex-col sm:items-start sm:gap-2" @if ($state === 'current') aria-current="step" @endif>
            <span @class([
                'flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold',
                'bg-teal-600 text-white' => $state === 'done',
                'border-2 border-teal-600 bg-white text-teal-700' => $state === 'current',
                'border border-neutral-300 bg-white text-neutral-400' => $state === 'todo',
                'bg-red-600 text-white' => $state === 'failed',
            ])>
                @if ($state === 'done')
                    <x-icon name="check" class="h-4 w-4" />
                @elseif ($state === 'failed')
                    <x-icon name="x-mark" class="h-4 w-4" />
                @else
                    {{ $loop->iteration }}
                @endif
            </span>
            <span @class([
                'text-sm',
                'font-medium text-neutral-900' => in_array($state, ['done', 'current', 'failed'], true),
                'text-tertiary' => $state === 'todo',
            ])>{{ $step['label'] }}</span>
            @unless ($loop->last)
                <span aria-hidden="true" @class([
                    'absolute left-[13px] top-7 h-[calc(100%-1rem)] w-0.5 sm:left-9 sm:top-[13px] sm:h-0.5 sm:w-[calc(100%-2rem)]',
                    'bg-teal-600' => $state === 'done',
                    'bg-neutral-200' => $state !== 'done',
                ])></span>
            @endunless
        </li>
    @endforeach
</ol>
