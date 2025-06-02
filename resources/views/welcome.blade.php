<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>ModelHub</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-100">
    <!-- Navbar -->
    <livewire:layout.navigation />

    <!-- Hero Section -->
    <section class="relative min-h-[80vh] flex items-center mt-16">
        <!-- Background gradient with pattern overlay -->
        <div class="absolute inset-0 bg-gradient-to-br from-primary/90 to-secondary/90 z-0">
            <!-- SVG Pattern Overlay -->
            <svg class="absolute inset-0 h-full w-full opacity-10" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern id="model-pattern" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse">
                        <path d="M0 10h20v1H0v-1zm10-10v20h1V0h-1z" fill="#FFFFFF" />
                    </pattern>
                </defs>
                <rect x="0" y="0" width="100%" height="100%" fill="url(#model-pattern)" />
            </svg>
        </div>

        <!-- Hero Content -->
        <div class="container mx-auto px-4 py-16 z-20 relative">
            <div class="max-w-3xl mx-auto text-center">
                <h1 class="text-4xl md:text-5xl font-bold text-white mb-6 font-tertiary">Find the Perfect 3D Models for
                    Your Projects</h1>
                <p class="text-white/90 text-lg mb-8 font-secondary">Access over 2 million high-quality 3D models for
                    games, VR/AR, architecture, and more</p>

                <!-- Search Bar -->
                <div class="bg-white rounded-2xl shadow-xl p-2 mb-8">
                    <div class="relative flex items-center">
                        <div class="pl-4 pr-2">
                            <svg class="h-5 w-5 text-neutral-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" placeholder="Search in more than 2 million 3D models"
                            class="w-full py-3 px-2 border-none focus:ring-0 font-secondary text-neutral-700">
                        <button
                            class="bg-secondary hover:bg-secondary/90 text-white px-8 py-3 rounded-xl ml-2 font-main font-medium transition-colors duration-300">
                            Search
                        </button>
                    </div>
                </div>

                <!-- Quick Categories -->
                <div class="flex flex-wrap gap-3">
                    <a href="#"
                        class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-full text-white font-secondary text-sm transition-colors duration-300">Character
                        Models</a>
                    <a href="#"
                        class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-full text-white font-secondary text-sm transition-colors duration-300">Architecture</a>
                    <a href="#"
                        class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-full text-white font-secondary text-sm transition-colors duration-300">Vehicle
                        Models</a>
                    <a href="#"
                        class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-full text-white font-secondary text-sm transition-colors duration-300">Game
                        Assets</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Category Section -->
    <section class="py-12 bg-gradient-to-r from-primary/5 to-secondary/5">
        <div class="container mx-auto px-4 max-w-7xl">
            <div class="mb-8">
                <h2 class="text-2xl font-bold font-main text-primary">Browse Categories</h2>
                <p class="text-tertiary font-secondary">Discover thousands of high-quality 3D models</p>
            </div>

            <div class="relative">
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4 w-full">
                    <!-- Category Items with custom styling -->
                    <a href="#"
                        class="flex flex-col items-center group transition-all duration-300 p-4 rounded-xl hover:bg-white hover:shadow-lg">
                        <div
                            class="w-16 h-16 rounded-full bg-gradient-to-br from-secondary/20 to-secondary/30 flex items-center justify-center text-secondary group-hover:from-secondary/30 group-hover:to-secondary group-hover:text-white transition-all duration-300">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <span
                            class="mt-3 text-sm font-medium font-secondary text-neutral-700 group-hover:text-primary">Discounts</span>
                        <span class="text-xs text-neutral-400 mt-1 group-hover:text-secondary">Special offers</span>
                    </a>

                    <a href="#"
                        class="flex flex-col items-center group transition-all duration-300 p-4 rounded-xl hover:bg-white hover:shadow-lg">
                        <div
                            class="w-16 h-16 rounded-full bg-gradient-to-br from-secondary/20 to-secondary/30 flex items-center justify-center text-secondary group-hover:from-secondary/30 group-hover:to-secondary group-hover:text-white transition-all duration-300">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                            </svg>
                        </div>
                        <span
                            class="mt-3 text-sm font-medium font-secondary text-neutral-700 group-hover:text-primary">Aircraft</span>
                        <span class="text-xs text-neutral-400 mt-1 group-hover:text-secondary">1,240+ models</span>
                    </a>

                    <a href="#"
                        class="flex flex-col items-center group transition-all duration-300 p-4 rounded-xl hover:bg-white hover:shadow-lg">
                        <div
                            class="w-16 h-16 rounded-full bg-gradient-to-br from-secondary/20 to-secondary/30 flex items-center justify-center text-secondary group-hover:from-secondary/30 group-hover:to-secondary group-hover:text-white transition-all duration-300">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                        </div>
                        <span
                            class="mt-3 text-sm font-medium font-secondary text-neutral-700 group-hover:text-primary">Animals</span>
                        <span class="text-xs text-neutral-400 mt-1 group-hover:text-secondary">3,560+ models</span>
                    </a>

                    <a href="#"
                        class="flex flex-col items-center group transition-all duration-300 p-4 rounded-xl hover:bg-white hover:shadow-lg">
                        <div
                            class="w-16 h-16 rounded-full bg-gradient-to-br from-secondary/20 to-secondary/30 flex items-center justify-center text-secondary group-hover:from-secondary/30 group-hover:to-secondary group-hover:text-white transition-all duration-300">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <span
                            class="mt-3 text-sm font-medium font-secondary text-neutral-700 group-hover:text-primary">Architectural</span>
                        <span class="text-xs text-neutral-400 mt-1 group-hover:text-secondary">5,320+ models</span>
                    </a>

                    <a href="#"
                        class="flex flex-col items-center group transition-all duration-300 p-4 rounded-xl hover:bg-white hover:shadow-lg">
                        <div
                            class="w-16 h-16 rounded-full bg-gradient-to-br from-secondary/20 to-secondary/30 flex items-center justify-center text-secondary group-hover:from-secondary/30 group-hover:to-secondary group-hover:text-white transition-all duration-300">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                        </div>
                        <span
                            class="mt-3 text-sm font-medium font-secondary text-neutral-700 group-hover:text-primary">Exterior</span>
                        <span class="text-xs text-neutral-400 mt-1 group-hover:text-secondary">2,140+ models</span>
                    </a>

                    <a href="#"
                        class="flex flex-col items-center group transition-all duration-300 p-4 rounded-xl hover:bg-white hover:shadow-lg">
                        <div
                            class="w-16 h-16 rounded-full bg-gradient-to-br from-secondary/20 to-secondary/30 flex items-center justify-center text-secondary group-hover:from-secondary/30 group-hover:to-secondary group-hover:text-white transition-all duration-300">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <span
                            class="mt-3 text-sm font-medium font-secondary text-neutral-700 group-hover:text-primary">Interior</span>
                        <span class="text-xs text-neutral-400 mt-1 group-hover:text-secondary">4,780+ models</span>
                    </a>

                    <a href="#"
                        class="flex flex-col items-center group transition-all duration-300 p-4 rounded-xl hover:bg-white hover:shadow-lg">
                        <div
                            class="w-16 h-16 rounded-full bg-gradient-to-br from-secondary/20 to-secondary/30 flex items-center justify-center text-secondary group-hover:from-secondary/30 group-hover:to-secondary group-hover:text-white transition-all duration-300">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </div>
                        <span
                            class="mt-3 text-sm font-medium font-secondary text-neutral-700 group-hover:text-primary">Car</span>
                        <span class="text-xs text-neutral-400 mt-1 group-hover:text-secondary">3,920+ models</span>
                    </a>
                </div>

                <!-- Navigation Controls -->

            </div>
        </div>
    </section>

    <!-- About Us Section with modern design -->
    <section class="py-16 relative overflow-hidden">
        <!-- Background decoration -->
        <div
            class="absolute top-0 right-0 -translate-y-1/4 translate-x-1/4 w-96 h-96 bg-gradient-to-br from-primary/10 to-secondary/10 rounded-full blur-3xl">
        </div>
        <div
            class="absolute bottom-0 left-0 translate-y-1/4 -translate-x-1/4 w-96 h-96 bg-gradient-to-tr from-accent/10 to-secondary/10 rounded-full blur-3xl">
        </div>

        <div class="container mx-auto max-w-7xl px-4 py-8 relative">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-12">
                <!-- Content Section -->
                <div class="max-w-lg">
                    <!-- Logo with enhanced styling -->
                    <div class="flex items-center mb-2 group">
                        <div class="relative">
                            <div
                                class="absolute inset-0 bg-gradient-to-r from-primary to-secondary rounded-lg blur-sm opacity-50 group-hover:opacity-70 transition-opacity duration-300">
                            </div>
                            {{-- <div
                                class="relative h-12 w-12 bg-white rounded-lg shadow-md flex items-center justify-center">
                                <svg class="h-8 w-8 text-primary" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 2L2 7v10l10 5 10-5V7L12 2z" />
                                </svg>
                            </div> --}}
                        </div>
                        <span
                            class="ml-3 text-2xl font-bold font-tertiary bg-gradient-to-r from-primary to-secondary text-transparent bg-clip-text">ModelHub</span>
                    </div>

                    <!-- Heading with enhanced typography -->
                    <h1 class="text-4xl lg:text-5xl font-bold font-main text-neutral-800 leading-tight mb-6">
                        Explore <span class="text-primary">ModelHub</span>,<br>
                        <span class="text-neutral-600">where AI meets 3D models.</span>
                    </h1>

                    <!-- Description with better readability -->
                    <p class="text-lg text-neutral-600 font-secondary mb-8 leading-relaxed">
                        ModelHub is a cutting-edge application that leverages the power of generative AI and 3D models
                        to produce stunning 2D images.
                        <span class="font-semibold text-primary block mt-2">Your creative journey starts now.</span>
                    </p>

                    <!-- CTA Buttons with better styling -->
                    <div class="flex flex-wrap gap-4">
                        <a href="{{ route('register') }}"
                            class="inline-flex items-center bg-gradient-to-r from-secondary to-secondary/80 hover:from-primary hover:to-primary/80 text-white font-medium px-6 py-3 rounded-lg transition duration-300 shadow-md hover:shadow-lg">
                            <span>Get started for Free!</span>
                            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </a>
                        <a href="#"
                            class="inline-flex items-center bg-white border border-neutral-200 hover:border-secondary/50 text-neutral-700 hover:text-secondary font-medium px-6 py-3 rounded-lg transition duration-300 shadow-sm hover:shadow">
                            <span>Learn more</span>
                        </a>
                    </div>

                    <!-- Trust indicators -->
                    <div class="mt-10 flex items-center text-neutral-500 text-sm">
                        <div class="flex -space-x-2 mr-3">
                            <div
                                class="h-8 w-8 rounded-full bg-neutral-200 border-2 border-white flex items-center justify-center text-xs font-bold">
                                JD</div>
                            <div
                                class="h-8 w-8 rounded-full bg-neutral-300 border-2 border-white flex items-center justify-center text-xs font-bold">
                                KM</div>
                            <div
                                class="h-8 w-8 rounded-full bg-neutral-400 border-2 border-white flex items-center justify-center text-xs font-bold">
                                TW</div>
                        </div>
                        <span>Join <b>10,000+</b> 3D artists already using ModelHub</span>
                    </div>
                </div>

                <!-- Image grid with enhanced styling -->
                <div class="lg:flex-1">
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-2 xl:grid-cols-4 gap-4 relative">
                        <!-- Main featured image -->
                        <div
                            class="bg-gradient-to-br from-neutral-100 to-neutral-200 rounded-2xl overflow-hidden shadow-md lg:col-span-2 lg:row-span-2 relative group">
                            <div
                                class="absolute inset-0 bg-gradient-to-br from-primary/30 to-secondary/30 opacity-0 group-hover:opacity-100 transition-opacity duration-300 z-10">
                            </div>
                            <img src="{{ asset('images/home/astronaut.jpg') }}" alt="3D Astronaut Model"
                                class="w-full h-full object-cover">
                            <div
                                class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-neutral-900/80 to-transparent p-4 text-white z-20">
                                <span class="text-xs font-semibold bg-secondary/80 px-2 py-1 rounded">Featured</span>
                                <h3 class="font-semibold mt-2">Space Explorer Series</h3>
                            </div>
                        </div>

                        <!-- Smaller images -->
                        <div
                            class="bg-gradient-to-br from-neutral-100 to-neutral-200 rounded-xl overflow-hidden shadow-md group relative">
                            <div
                                class="absolute inset-0 bg-gradient-to-br from-primary/20 to-secondary/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                            </div>
                            <img src="{{ asset('images/home/mars.jpg') }}" alt="Astronaut on Earth"
                                class="w-full h-full object-cover">
                        </div>

                        <div
                            class="bg-gradient-to-br from-neutral-100 to-neutral-200 rounded-xl overflow-hidden shadow-md group relative">
                            <div
                                class="absolute inset-0 bg-gradient-to-br from-primary/20 to-secondary/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                            </div>
                            <img src="{{ asset('images/home/city.jpg') }}" alt="Astronaut in Space"
                                class="w-full h-full object-cover">
                        </div>

                        <div
                            class="bg-gradient-to-br from-neutral-100 to-neutral-200 rounded-xl overflow-hidden shadow-md hidden xl:block group relative">
                            <div
                                class="absolute inset-0 bg-gradient-to-br from-primary/20 to-secondary/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                            </div>
                            <img src="{{ asset('images/home/ai.jpg') }}" alt="Astronaut with Ship"
                                class="w-full h-full object-cover">
                        </div>

                        <div
                            class="bg-gradient-to-br from-neutral-100 to-neutral-200 rounded-xl overflow-hidden shadow-md hidden xl:block group relative">
                            <div
                                class="absolute inset-0 bg-gradient-to-br from-primary/20 to-secondary/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                            </div>
                            <img src="{{ asset('images/home/space-vehicle.jpg') }}" alt="Space Vehicle"
                                class="w-full h-full object-cover">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- Floating Chat Button -->
    <div class="fixed bottom-6 right-6">
        <button
            class="w-12 h-12 rounded-full bg-teal-500 text-white flex items-center justify-center shadow-lg hover:bg-teal-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
        </button>
    </div>
</body>

</html>
