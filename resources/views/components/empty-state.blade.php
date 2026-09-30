@props(['icon', 'title', 'description' => null])

<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-neutral-200">
    <div {{ $attributes->class('p-12 flex flex-col items-center justify-center text-center font-main') }}>
        <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center mb-6">
            <x-icon :name="$icon" class="w-8 h-8 text-primary" />
        </div>

        <h3 class="text-lg font-semibold text-neutral-800 mb-2">{{ $title }}</h3>

        @if ($description)
            <p class="text-neutral-600 mb-6 max-w-md">{{ $description }}</p>
        @endif

        {{ $slot }}
    </div>
</div>
