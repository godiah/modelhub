@props(['id', 'number', 'title'])

<section id="{{ $id }}" class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="border-l-4 border-secondary">
        <div class="px-6 py-5 bg-gradient-to-r from-secondary/10 to-white border-b border-neutral-200">
            <div class="flex items-center">
                {{ $icon }}
                <h3 class="text-lg font-tertiary font-bold text-primary">{{ $number }}. {{ $title }}</h3>
            </div>
        </div>
        <div class="px-6 py-5 font-main text-neutral-700">
            {{ $slot }}
        </div>
    </div>
</section>
