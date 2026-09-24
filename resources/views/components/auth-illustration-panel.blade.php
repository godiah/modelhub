@props(['description'])

<!-- Left Side - Illustration Area -->
<div
    class="font-main hidden lg:flex lg:w-1/2 bg-gradient-to-br from-secondary via-teal-400 to-secondary relative overflow-hidden">
    <!-- Decorative Background Elements -->
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute top-1/4 left-1/4 w-20 h-20 bg-white/10 rounded-full animate-pulse"></div>
        <div class="absolute top-2/4 right-1/4 w-16 h-16 bg-white/10 rounded-full animate-pulse delay-75"></div>
        <div class="absolute bottom-1/3 left-1/5 w-12 h-12 bg-white/10 rounded-full animate-pulse delay-150">
        </div>
    </div>

    <!-- Main Illustration Area -->
    <div class="relative z-10 flex items-center justify-center w-full p-12">
        <div class="text-center text-white">
            <!-- Logo or Illustration Placeholder -->
            <div
                class="w-48 h-48 mx-auto mb-8 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-sm">
                <svg class="w-24 h-24 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h2 class="text-xl font-bold mb-4 font-tertiary">{{ config('app.name', 'ModelHub') }}</h2>
            <p class="text-white/90 text-base leading-relaxed">
                {{ $description }}
            </p>
        </div>
    </div>
</div>
