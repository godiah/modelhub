@props(['event', 'message', 'variant' => 'inline', 'timeout' => 3000])

@if ($variant === 'floating')
    <div x-data="{ show: false }" x-show="show" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform translate-x-full"
        x-transition:enter-end="opacity-100 transform translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 transform translate-x-0"
        x-transition:leave-end="opacity-0 transform translate-x-full"
        x-on:{{ $event }}.window="show = true; setTimeout(() => show = false, {{ $timeout }})"
        class="fixed top-4 right-4 bg-secondary/10 border border-secondary/30 text-secondary px-6 py-4 rounded-lg shadow-lg z-50"
        style="display: none;">
        <div class="flex items-center space-x-3">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                    clip-rule="evenodd"></path>
            </svg>
            <span class="font-medium font-main">{{ $message }}</span>
        </div>
    </div>
@else
    <div x-data="{ show: false }" x-show="show" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform scale-90"
        x-transition:enter-end="opacity-100 transform scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 transform scale-100"
        x-transition:leave-end="opacity-0 transform scale-90"
        x-on:{{ $event }}.window="show = true; setTimeout(() => show = false, {{ $timeout }})"
        class="flex items-center space-x-2 text-secondary font-medium text-sm font-main" style="display: none;">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd"
                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                clip-rule="evenodd"></path>
        </svg>
        <span>{{ $message }}</span>
    </div>
@endif
