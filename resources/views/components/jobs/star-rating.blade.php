@props(['rating' => 0, 'size' => 'md', 'showNumber' => false])

@php
    $sizes = [
        'sm' => 'h-3 w-3',
        'md' => 'h-5 w-5',
        'lg' => 'h-7 w-7',
    ];

    $starSize = $sizes[$size] ?? $sizes['md'];
@endphp

<div class="flex items-center">
    @for ($i = 1; $i <= 5; $i++)
        @if ($i <= $rating)
            <!-- Full Star -->
            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $starSize }} text-accent" viewBox="0 0 20 20"
                fill="currentColor">
                <path
                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
            </svg>
        @elseif($i - 0.5 <= $rating)
            <!-- Half Star -->
            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $starSize }} text-accent" viewBox="0 0 24 24"
                fill="none" stroke="currentColor">
                <path fill="currentColor" d="M12 2l1.8 5.6h5.8l-4.7 3.4 1.8 5.6-4.7-3.4-2.4 1.7v-12.9z" />
                <path stroke="currentColor" fill="none" stroke-width="1.5" stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
            </svg>
        @else
            <!-- Empty Star -->
            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $starSize }} text-neutral-300" fill="none"
                viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
            </svg>
        @endif
    @endfor

    @if ($showNumber)
        <span class="ml-2 text-neutral-700 font-medium">{{ number_format($rating, 1) }}</span>
    @endif
</div>

{{-- 
Example usage: <x-star-rating :rating="4.5" size="md" :showNumber="true" />
--}}
