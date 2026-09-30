@props(['name', 'variant' => 'icon'])

@error($name)
    @if ($variant === 'plain')
        <p {{ $attributes->class('text-red-500 text-xs italic') }}>{{ $message }}</p>
    @else
        <div {{ $attributes->class('flex items-center space-x-2 text-red-600 text-sm font-main') }}>
            <x-icon name="exclamation-circle-solid" class="w-4 h-4" />
            <span>{{ $message }}</span>
        </div>
    @endif
@enderror
