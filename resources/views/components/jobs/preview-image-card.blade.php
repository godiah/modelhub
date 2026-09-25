@props(['src', 'alt', 'downloadLabel' => 'Download Image'])

<div class="relative group">
    <div
        class="overflow-hidden border border-neutral-200 shadow-sm group-hover:shadow-md transition-all duration-300 w-full aspect-square rounded-lg">
        <img src="{{ $src }}" alt="{{ $alt }}"
            class="w-full h-full object-cover transform group-hover:scale-[1.02] transition-transform duration-500">
    </div>

    <!-- Zoom Button -->
    <button
        class="absolute top-2 right-2 p-1.5 bg-white/90 backdrop-blur-sm rounded-full shadow-sm border border-neutral-100 text-neutral-700 hover:text-primary transition-colors duration-200"
        onclick="openImageModal('{{ $src }}')">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
            stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
        </svg>
    </button>

    <!-- Download Button -->
    <div class="mt-3">
        <button
            class="w-full flex items-center justify-center px-3 py-1.5 bg-primary text-white rounded-lg shadow-sm hover:bg-primary-dark transition-colors duration-200 text-sm"
            onclick="downloadImage('{{ $src }}')">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            {{ $downloadLabel }}
        </button>
    </div>
</div>
