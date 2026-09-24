@props(['title', 'subtitle'])

<!-- Form Header -->
<div class="p-8 bg-primary text-white relative overflow-hidden">
    <div class="relative z-10">
        <h1 class="text-3xl font-bold font-tertiary mb-3 flex items-center">
            {{ $icon }}
            {{ $title }}
        </h1>
        <p class="text-sm text-neutral-100 font-secondary max-w-2xl">
            {{ $subtitle }}
        </p>
    </div>
    <div class="absolute top-0 right-0 w-64 h-64 transform translate-x-16 -translate-y-16 opacity-10">
        {{ $decoration }}
    </div>
</div>
