<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component {
    public $hasHeader;
    // public $cartCount = 0;

    // public function mount()
    // {
    //     // Initialize cart count - replace with your actual cart logic
    //     $this->cartCount = session('cart_count', 0);
    // }

    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<nav x-data="{
    open: false,
    designersOpen: false,
    modelsOpen: false,
    supportOpen: false,
    scrolled: false,
    hasHeader: {{ $hasHeader ? 'true' : 'false' }}
}" x-init="window.addEventListener('scroll', () => {
    scrolled = window.scrollY > 20;
});"
    :class="scrolled && !hasHeader ? 'bg-white/95 backdrop-blur-lg shadow-lg border-b border-neutral-200/50' :
        scrolled && hasHeader ? 'bg-white border-b border-neutral-200' :
        'bg-white border-b border-neutral-100'"
    class="fixed top-0 z-50 w-full transition-all duration-300">

    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">

            <!-- Left Section: Logo + Search -->
            <div class="flex items-center space-x-6">
                <!-- Logo -->
                <div class="shrink-0">
                    <a href="{{ route('home') }}" wire:navigate
                        class="flex items-center group transition-transform duration-200 hover:scale-105">
                        <x-application-logo
                            class="block h-9 w-auto fill-current text-primary group-hover:text-secondary transition-colors duration-200" />
                    </a>
                </div>

                <!-- Search Bar - Hidden on small screens -->
                <div class="hidden lg:block flex-1 w-96 max-w-lg">
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-neutral-400 group-focus-within:text-secondary transition-colors duration-200"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" placeholder="Search in more than a million 3D models..."
                            class="w-full py-2.5 pl-12 pr-4 bg-neutral-50 border border-neutral-200 rounded-xl 
                                      focus:outline-none focus:ring-2 focus:ring-secondary/20 focus:border-secondary 
                                      focus:bg-white transition-all duration-200 font-secondary text-sm text-neutral-700
                                      hover:border-neutral-300">
                    </div>
                </div>
            </div>

            <!-- Center Navigation - Hidden on mobile -->
            <div class="hidden lg:flex items-center space-x-1">
                <!-- Home Link -->
                <a href="{{ route('home') }}" wire:navigate
                    class="px-4 py-2 text-sm md:text-base font-medium text-neutral-700 hover:text-primary hover:bg-neutral-50 
                          rounded-lg transition-all duration-200 font-main uppercase">
                    Home
                </a>

                <!-- Designers Dropdown -->
                <div class="relative" @click.away="designersOpen = false">
                    <button @click="designersOpen = !designersOpen"
                        class="flex items-center px-4 py-2 text-sm md:text-base font-medium text-neutral-700 hover:text-primary 
                                   hover:bg-neutral-50 rounded-lg transition-all duration-200 font-main group uppercase">
                        Designers
                        <svg class="ml-1 h-4 w-4 transition-transform duration-200"
                            :class="designersOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </button>

                    <!-- Designers Dropdown Menu -->
                    <div x-show="designersOpen" x-cloak x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 translate-y-1"
                        class="absolute top-full left-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-neutral-200 py-2 z-50">
                        <a href="{{ route('jobs.index') }}" wire:navigate
                            class="flex items-center px-4 py-3 text-sm text-neutral-700 hover:text-primary hover:bg-neutral-50 transition-colors duration-150 font-main">
                            <svg class="h-4 w-4 mr-3 text-neutral-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            Find Jobs
                        </a>
                        <a href="{{ route('jobs.index') }}" wire:navigate
                            class="flex items-center px-4 py-3 text-sm text-neutral-700 hover:text-primary hover:bg-neutral-50 transition-colors duration-150 font-main">
                            <svg class="h-4 w-4 mr-3 text-neutral-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4"></path>
                            </svg>
                            Post Jobs
                        </a>
                    </div>
                </div>

                <!-- 3D Models Dropdown -->
                <div class="relative" @click.away="modelsOpen = false">
                    <button @click="modelsOpen = !modelsOpen"
                        class="flex items-center px-4 py-2 text-sm md:text-base font-medium text-neutral-700 hover:text-primary 
                                   hover:bg-neutral-50 rounded-lg transition-all duration-200 font-main uppercase">
                        3D Models
                        <svg class="ml-1 h-4 w-4 transition-transform duration-200"
                            :class="modelsOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </button>

                    <!-- 3D Models Dropdown Menu -->
                    <div x-show="modelsOpen" x-cloak x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 translate-y-1"
                        class="absolute top-full left-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-neutral-200 py-2 z-50">
                        <a href="#" wire:navigate
                            class="flex items-center px-4 py-3 text-sm text-neutral-700 hover:text-primary hover:bg-neutral-50 transition-colors duration-150 font-main">
                            <svg class="h-4 w-4 mr-3 text-neutral-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                                </path>
                            </svg>
                            Browse Models
                        </a>
                        <a href="#" wire:navigate
                            class="flex items-center px-4 py-3 text-sm text-neutral-700 hover:text-primary hover:bg-neutral-50 transition-colors duration-150 font-main">
                            <svg class="h-4 w-4 mr-3 text-neutral-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12">
                                </path>
                            </svg>
                            Publish Models
                        </a>
                    </div>
                </div>
            </div>

            <!-- Right Section: Support, Cart, Notifications, Profile -->
            <div class="flex items-center space-x-2">

                <!-- Community & Support Dropdown - Hidden on mobile -->
                <div class="hidden md:block relative" x-data="{ supportOpen: false }" @click.away="supportOpen = false">
                    <button @click="supportOpen = !supportOpen"
                        class="p-2 text-neutral-600 hover:text-primary hover:bg-neutral-50 rounded-lg 
                                   transition-all duration-200 group">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                            </path>
                        </svg>
                    </button>

                    <!-- Support Dropdown -->
                    <div x-show="supportOpen" x-cloak x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 translate-y-1"
                        class="absolute top-full right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-neutral-200 py-2 z-50">
                        <a href="#" wire:navigate
                            class="flex items-center px-4 py-3 text-sm text-neutral-700 hover:text-primary hover:bg-neutral-50 transition-colors duration-150 font-main">
                            <svg class="h-4 w-4 mr-3 text-neutral-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z">
                                </path>
                            </svg>
                            Community
                        </a>
                        <a href="#" wire:navigate
                            class="flex items-center px-4 py-3 text-sm text-neutral-700 hover:text-primary hover:bg-neutral-50 transition-colors duration-150 font-main">
                            <svg class="h-4 w-4 mr-3 text-neutral-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253">
                                </path>
                            </svg>
                            Resources
                        </a>
                        <a href="#" wire:navigate
                            class="flex items-center px-4 py-3 text-sm text-neutral-700 hover:text-primary hover:bg-neutral-50 transition-colors duration-150 font-main">
                            <svg class="h-4 w-4 mr-3 text-neutral-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192L5.636 18.364M12 12h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                                </path>
                            </svg>
                            Support
                        </a>
                    </div>
                </div>

                @auth
                    <div class="flex items-center space-x-2">
                        <!-- Shopping Cart -->
                        <div class="mr-4">
                            <a href="#" wire:navigate
                                class="relative text-neutral-600 hover:text-primary hover:bg-neutral-50 rounded-lg 
                                  transition-all duration-200 group">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                                </svg>
                                <span
                                    class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full text-xs px-1.5 py-0.5 font-main hover:bg-accent">
                                    0
                                </span>

                                {{-- @if ($cartCount > 0)
                                <span
                                    class="absolute -top-1 -right-1 bg-accent text-white rounded-full text-xs px-1.5 py-0.5 
                                           font-medium min-w-[1.25rem] h-5 flex items-center justify-center animate-pulse">
                                    {{ $cartCount }}
                                </span>
                            @endif --}}
                            </a>
                        </div>

                        <!-- Notifications -->
                        <div class="mr-4">
                            <a href="{{ route('notifications.index') }}"
                                class="relative text-neutral-600 hover:text-primary hover:bg-neutral-50 rounded-lg 
                                  transition-all duration-200 group">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>

                                @if (auth()->user()->unreadNotifications->count() > 0)
                                    <span
                                        class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full text-xs px-1.5 py-0.5 font-main hover:bg-accent">
                                        {{ auth()->user()->unreadNotifications->count() }}
                                    </span>
                                @endif
                            </a>
                        </div>
                    </div>

                    <!-- User Dropdown -->
                    <x-dropdown align="right" width="64">
                        <x-slot name="trigger">
                            <button
                                class="flex items-center space-x-2 px-3 py-2 rounded-lg text-neutral-700 
                                         hover:bg-neutral-50 transition-all duration-200 focus:outline-none group">

                                <!-- Avatar or Initials -->
                                @php
                                    $profile = auth()->user()->profile;
                                @endphp

                                @if ($profile && $profile->avatar && file_exists(storage_path('app/public/' . $profile->avatar)))
                                    <img src="{{ asset('storage/' . $profile->avatar) }}" alt="User Avatar"
                                        class="h-9 w-9 rounded-full object-cover border-2 border-neutral-200 
                                               group-hover:border-secondary transition-colors duration-200">
                                @else
                                    <div
                                        class="flex items-center justify-center h-9 w-9 rounded-full bg-primary/10 
                                              text-primary font-medium border border-primary/20 font-main text-sm
                                              group-hover:bg-secondary/10 group-hover:text-secondary group-hover:border-secondary/20 
                                              transition-all duration-200">
                                        {{ auth()->user()->getInitials() }}
                                    </div>
                                @endif

                                <!-- Dropdown Arrow -->
                                <svg class="h-4 w-4 transition-transform duration-200 hidden sm:block"
                                    :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <!-- User Info Header -->
                            <div class="px-4 py-3 border-b border-neutral-100">
                                <div class="flex items-center space-x-3">
                                    @if ($profile && $profile->avatar && file_exists(storage_path('app/public/' . $profile->avatar)))
                                        <img src="{{ asset('storage/' . $profile->avatar) }}" alt="User Avatar"
                                            class="h-10 w-10 rounded-full object-cover border-2 border-neutral-200">
                                    @else
                                        <div
                                            class="flex items-center justify-center h-10 w-10 rounded-full bg-primary/10 
                                                  text-primary font-medium border border-primary/20 font-main">
                                            {{ auth()->user()->getInitials() }}
                                        </div>
                                    @endif
                                    <div>
                                        <p class="font-medium text-neutral-900 font-main">{{ auth()->user()->name }}</p>
                                        <p class="text-sm text-neutral-500 font-main">{{ auth()->user()->email }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Menu Items -->
                            <div class="py-2">
                                <!-- Dashboard Link -->
                                <x-dropdown-link :href="route('my-dashboard.index')" wire:navigate>
                                    <x-slot name="icon">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                                    </x-slot>
                                    {{ __('Dashboard') }}
                                </x-dropdown-link>

                                <!-- Projects Link -->
                                <x-dropdown-link :href="route('project.index')" wire:navigate>
                                    <x-slot name="icon">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8.25 7.5V6.108c0-1.135.845-2.098 1.976-2.192.373-.03.748-.057 1.123-.08M15.75 18H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08M15.75 18.75v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5A3.375 3.375 0 0 0 6.375 7.5H5.25m11.9-3.664A2.251 2.251 0 0 0 15 2.25h-1.5a2.251 2.251 0 0 0-2.15 1.586m5.8 0c.065.21.1.433.1.664v.75h-6V4.5c0-.231.035-.454.1-.664M6.75 7.5H4.875c-.621 0-1.125.504-1.125 1.125v12c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V16.5a9 9 0 0 0-9-9Z" />
                                        </path>
                                    </x-slot>
                                    {{ __('My Projects') }}
                                </x-dropdown-link>

                                <!-- Profile Link -->
                                <x-dropdown-link :href="route('profile')" wire:navigate>
                                    <x-slot name="icon">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </x-slot>
                                    {{ __('Profile') }}
                                </x-dropdown-link>
                            </div>

                            <!-- Divider -->
                            <div class="border-t border-neutral-100 my-2"></div>

                            <!-- Logout -->
                            <div class="py-2">
                                <button wire:click="logout"
                                    class="flex items-center w-full px-4 py-3 text-sm text-red-600 hover:bg-red-50 
                                           transition-colors duration-200 font-main group">
                                    <svg class="h-4 w-4 mr-3 group-hover:scale-110 transition-transform duration-200"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                                        </path>
                                    </svg>
                                    {{ __('Log Out') }}
                                </button>
                            </div>
                        </x-slot>
                    </x-dropdown>
                @else
                    <!-- Login Button for Guests -->
                    <a href="{{ route('login') }}" wire:navigate
                        class="inline-flex items-center px-4 py-2 bg-primary text-white font-medium text-sm 
                        rounded-lg hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary/20 
                        transition-all duration-200 font-main uppercase">
                        <!-- SVG Icon -->
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="1.5"
                            viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" />
                        </svg>
                        Log In
                    </a>

                @endauth

                <!-- Mobile Menu Toggle -->
                <div class="lg:hidden">
                    <button @click="open = ! open"
                        class="inline-flex items-center justify-center p-2 rounded-lg text-neutral-600 
                               hover:text-primary hover:bg-neutral-50 focus:outline-none transition-all duration-200">
                        <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path :class="{ 'hidden': open, 'inline-flex': !open }" class="inline-flex"
                                stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                            <path :class="{ 'hidden': !open, 'inline-flex': open }" class="hidden"
                                stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div :class="{ 'block': open, 'hidden': !open }" class="hidden lg:hidden bg-white border-t border-neutral-200">

        <!-- Mobile Search -->
        <div class="px-4 pt-4 pb-2">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" placeholder="Search..."
                    class="w-full py-2.5 pl-10 pr-4 bg-neutral-50 border border-neutral-200 rounded-lg 
                           focus:outline-none focus:ring-2 focus:ring-secondary/20 focus:border-secondary 
                           transition-all duration-200 font-secondary text-sm">
            </div>
        </div>

        <!-- Mobile Navigation Links -->
        <div class="px-4 py-2 space-y-1">
            <!-- Home -->
            <a href="{{ route('home') }}" wire:navigate
                class="block px-3 py-2 text-base font-medium text-neutral-700 hover:text-primary 
                      hover:bg-neutral-50 rounded-lg transition-all duration-200 font-main">
                Home
            </a>

            <!-- Designers Section -->
            <div class="space-y-1">
                <div class="px-3 py-2 text-base font-medium text-neutral-900 font-main">
                    Designers
                </div>
                <a href="#" wire:navigate
                    class="block px-6 py-2 text-sm text-neutral-600 hover:text-primary 
                          hover:bg-neutral-50 rounded-lg transition-all duration-200 font-main">
                    Find Jobs
                </a>
                <a href="#" wire:navigate
                    class="block px-6 py-2 text-sm text-neutral-600 hover:text-primary 
                          hover:bg-neutral-50 rounded-lg transition-all duration-200 font-main">
                    Post Jobs
                </a>
            </div>

            <!-- 3D Models Section -->
            <div class="space-y-1">
                <div class="px-3 py-2 text-base font-medium text-neutral-900 font-main">
                    3D Models
                </div>
                <a href="#" wire:navigate
                    class="block px-6 py-2 text-sm text-neutral-600 hover:text-primary 
                          hover:bg-neutral-50 rounded-lg transition-all duration-200 font-main">
                    Browse Models
                </a>
                <a href="#" wire:navigate
                    class="block px-6 py-2 text-sm text-neutral-600 hover:text-primary 
                          hover:bg-neutral-50 rounded-lg transition-all duration-200 font-main">
                    Publish Models
                </a>
            </div>

            <!-- Community & Support Section -->
            <div class="space-y-1">
                <div class="px-3 py-2 text-base font-medium text-neutral-900 font-main">
                    Community & Support
                </div>
                <a href="#" wire:navigate
                    class="block px-6 py-2 text-sm text-neutral-600 hover:text-primary 
                          hover:bg-neutral-50 rounded-lg transition-all duration-200 font-main">
                    Community
                </a>
                <a href="#" wire:navigate
                    class="block px-6 py-2 text-sm text-neutral-600 hover:text-primary 
                          hover:bg-neutral-50 rounded-lg transition-all duration-200 font-main">
                    Resources
                </a>
                <a href="#" wire:navigate
                    class="block px-6 py-2 text-sm text-neutral-600 hover:text-primary 
                          hover:bg-neutral-50 rounded-lg transition-all duration-200 font-main">
                    Help & Support
                </a>
            </div>
        </div>

        @auth
            <!-- Mobile User Section -->
            <div class="border-t border-neutral-200 px-4 py-4">
                <div class="flex items-center space-x-3 mb-4">
                    @php
                        $profile = auth()->user()->profile;
                    @endphp

                    @if ($profile && $profile->avatar && file_exists(storage_path('app/public/' . $profile->avatar)))
                        <img src="{{ asset('storage/' . $profile->avatar) }}" alt="User Avatar"
                            class="h-10 w-10 rounded-full object-cover border-2 border-neutral-200">
                    @else
                        <div
                            class="flex items-center justify-center h-10 w-10 rounded-full bg-primary/10 
                                  text-primary font-medium border border-primary/20 font-main">
                            {{ auth()->user()->getInitials() }}
                        </div>
                    @endif
                    <div>
                        <p class="font-medium text-neutral-900 font-main">{{ auth()->user()->name }}</p>
                        <p class="text-sm text-neutral-500 font-main">{{ auth()->user()->email }}</p>
                    </div>
                </div>

                <!-- Mobile User Links -->
                {{-- <div class="space-y-1">
                    <a href="{{ route('my-dashboard.index') }}" wire:navigate
                        class="flex items-center px-3 py-2 text-sm text-neutral-700 hover:text-primary 
                              hover:bg-neutral-50 rounded-lg transition-all duration-200 font-main">
                        <svg class="h-4 w-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                        </svg>
                        Dashboard
                    </a>

                    <a href="{{ route('project.index') }}" wire:navigate
                        class="flex items-center px-3 py-2 text-sm text-neutral-700 hover:text-primary 
                              hover:bg-neutral-50 rounded-lg transition-all duration-200 font-main">
                        <svg class="h-4 w-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8.25 7.5V6.108c0-1.135.845-2.098 1.976-2.192.373-.03.748-.057 1.123-.08M15.75 18H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08M15.75 18.75v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5A3.375 3.375 0 0 0 6.375 7.5H5.25m11.9-3.664A2.251 2.251 0 0 0 15 2.25h-1.5a2.251 2.251 0 0 0-2.15 1.586m5.8 0c.065.21.1.433.1.664v.75h-6V4.5c0-.231.035-.454.1-.664M6.75 7.5H4.875c-.621 0-1.125.504-1.125 1.125v12c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V16.5a9 9 0 0 0-9-9Z" />
                        </svg>
                        My Projects
                    </a>

                    <a href="{{ route('profile') }}" wire:navigate
                        class="flex items-center px-3 py-2 text-sm text-neutral-700 hover:text-primary 
                              hover:bg-neutral-50 rounded-lg transition-all duration-200 font-main">
                        <svg class="h-4 w-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        Profile
                    </a>

                    <a href="{{ route('notifications.index') }}" wire:navigate
                        class="flex items-center justify-between px-3 py-2 text-sm text-neutral-700 hover:text-primary 
                              hover:bg-neutral-50 rounded-lg transition-all duration-200 font-main">
                        <div class="flex items-center">
                            <svg class="h-4 w-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            Notifications
                        </div>
                        @if (auth()->user()->unreadNotifications->count() > 0)
                            <span class="bg-red-500 text-white rounded-full text-xs px-1.5 py-0.5 font-medium">
                                {{ auth()->user()->unreadNotifications->count() }}
                            </span>
                        @endif
                    </a>

                    <!-- Mobile Cart Link -->
                    <a href="#" wire:navigate
                        class="flex items-center justify-between px-3 py-2 text-sm text-neutral-700 hover:text-primary 
                              hover:bg-neutral-50 rounded-lg transition-all duration-200 font-main">
                        <div class="flex items-center">
                            <svg class="h-4 w-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 5M7 13l2.5 5M17 13v6a2 2 0 01-2 2H9a2 2 0 01-2-2v-6">
                                </path>
                            </svg>
                            Shopping Cart
                        </div>
                        @if ($cartCount > 0)
                            <span class="bg-accent text-white rounded-full text-xs px-1.5 py-0.5 font-medium">
                                {{ $cartCount }}
                            </span>
                        @endif
                        <span class="bg-accent text-white rounded-full text-xs px-1.5 py-0.5 font-medium">
                            0
                        </span>
                    </a>

                    <!-- Mobile Logout -->
                    <button wire:click="logout"
                        class="flex items-center w-full px-3 py-2 text-sm text-red-600 hover:bg-red-50 
                               rounded-lg transition-all duration-200 font-main mt-4 border-t border-neutral-200 pt-4">
                        <svg class="h-4 w-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                            </path>
                        </svg>
                        Log Out
                    </button>
                </div> --}}
            </div>
        @else
            <!-- Mobile Login/Register for Guests -->
            <div class="border-t border-neutral-200 px-4 py-4 space-y-2">
                <a href="{{ route('login') }}" wire:navigate
                    class="block w-full text-center px-4 py-2.5 bg-primary text-white font-medium 
                          rounded-lg hover:bg-primary/90 transition-all duration-200 font-main">
                    Log In
                </a>
                <a href="{{ route('register') }}" wire:navigate
                    class="block w-full text-center px-4 py-2.5 border border-neutral-300 text-neutral-700 
                          font-medium rounded-lg hover:bg-neutral-50 transition-all duration-200 font-main">
                    Sign Up
                </a>
            </div>
        @endauth
    </div>
</nav>

{{-- <!-- Spacer to prevent content from hiding behind fixed navbar -->
<div class="h-16"></div> --}}
