@props(['items'])

<ul {{ $attributes->class('space-y-2.5') }}>
    @foreach ($items as $item)
        <li class="flex items-start gap-2.5">
            <x-icon name="check-circle-2" class="mt-0.5 h-4 w-4 shrink-0 text-teal-600" />
            <span>{{ $item }}</span>
        </li>
    @endforeach
</ul>
