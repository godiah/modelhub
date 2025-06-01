<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-tertiary font-bold text-2xl text-primary leading-tight flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="h-6 w-6 mr-2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                </svg>
                {{ __('Dashboard') }}
            </h2>
        </div>
    </x-slot>

    <div class="min-h-screen bg-neutral-50">
        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <!-- Navigation Links -->
            <div class="mb-8">
                <div class="bg-white rounded-xl shadow-sm border border-neutral-200 p-2">
                    <nav class="flex space-x-1">
                        <a href="{{ route('applications.my') }}"
                            class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all duration-200 font-secondary {{ request()->routeIs('applications.my') ? 'bg-secondary text-white shadow-sm' : 'text-neutral-700 hover:text-secondary hover:bg-secondary/10' }}">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                            {{ __('My Applications') }}
                        </a>
                        <a href="{{ route('my-jobs.index') }}"
                            class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all duration-200 font-secondary {{ request()->routeIs('my-jobs.index') ? 'bg-secondary text-white shadow-sm' : 'text-neutral-700 hover:text-secondary hover:bg-secondary/10' }}">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0 1 12 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 0 1-.673-.38m0 0A2.18 2.18 0 0 1 3 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 0 1 3.413-.387m7.5 0V5.25A2.25 2.25 0 0 0 13.5 3h-3a2.25 2.25 0 0 0-2.25 2.25v.894m7.5 0a48.667 48.667 0 0 0-7.5 0M12 12.75h.008v.008H12v-.008Z" />
                            </svg>
                            {{ __('Jobs Posted') }}
                        </a>
                        <a href="{{ route('engagements.index') }}"
                            class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all duration-200 font-secondary {{ request()->routeIs('engagements.index') ? 'bg-secondary text-white shadow-sm' : 'text-neutral-700 hover:text-secondary hover:bg-secondary/10' }}">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
                            </svg>
                            {{ __('My Engagements') }}
                        </a>
                    </nav>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Profile Section -->
                    <div class="bg-white rounded-xl shadow-sm border border-neutral-200 overflow-hidden">
                        <!-- Header -->
                        <div class="bg-gradient-to-r from-primary to-primary/90 px-6 py-5">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z">
                                        </path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-semibold text-white font-main">
                                        {{ __('Profile Information') }}
                                    </h3>
                                    <p class="text-white/60 text-sm font-main mt-1">
                                        {{ __('Your professional profile overview') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="p-6">
                            <div class="flex items-start space-x-6">
                                <!-- Avatar -->
                                <div class="flex-shrink-0">
                                    @if ($dashboardData['profile'] && $dashboardData['profile']->avatar_url)
                                        <img src="{{ $dashboardData['profile']->avatar_url }}"
                                            alt="{{ $dashboardData['user']->name }}"
                                            class="w-20 h-20 rounded-full object-cover border-2 border-secondary shadow-lg">
                                    @else
                                        <div
                                            class="w-20 h-20 rounded-full bg-primary/10 border-2 border-primary/20 flex items-center justify-center shadow-lg">
                                            <span class="text-2xl font-bold font-main">
                                                {{ substr($dashboardData['user']->name, 0, 1) }}
                                            </span>
                                        </div>
                                    @endif
                                </div>

                                <!-- User Info -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <h4 class="text-xl font-semibold text-neutral-900 font-main">
                                                {{ $dashboardData['user']->name }}</h4>
                                            <p class="text-neutral-600 font-main">{{ $dashboardData['user']->email }}
                                            </p>
                                        </div>
                                        <a href="{{ route('profile') }}"
                                            class="inline-flex items-center px-3 py-2 text-sm font-medium text-secondary hover:text-secondary/80 hover:bg-secondary/10 rounded-lg transition-all duration-200 font-main">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                                </path>
                                            </svg>
                                            {{ __('Edit') }}
                                        </a>
                                    </div>

                                    @if ($dashboardData['profile'])
                                        <div class="mt-4 space-y-2">
                                            @if ($dashboardData['profile']->location)
                                                <div class="flex items-center text-neutral-600 font-main">
                                                    <svg class="w-4 h-4 mr-2 text-neutral-400" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                                                        </path>
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z">
                                                        </path>
                                                    </svg>
                                                    {{ $dashboardData['profile']->location }}
                                                </div>
                                            @endif

                                            @if ($dashboardData['profile']->telephone_number)
                                                <div class="flex items-center text-neutral-600 font-main">
                                                    <svg class="w-4 h-4 mr-2 text-neutral-400" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z">
                                                        </path>
                                                    </svg>
                                                    {{ $dashboardData['profile']->telephone_number }}
                                                </div>
                                            @endif
                                        </div>

                                        @if ($dashboardData['profile']->professional_info)
                                            <div class="mt-4">
                                                <h5 class="font-medium text-neutral-900 font-main mb-2">
                                                    {{ __('About') }}</h5>
                                                <p class="text-neutral-700 font-main leading-relaxed text-justify">
                                                    {{ $dashboardData['profile']->professional_info }}
                                                </p>
                                            </div>
                                        @endif
                                    @else
                                        <div class="mt-4 p-4 bg-accent/10 border border-accent/20 rounded-lg">
                                            <div class="flex items-start space-x-3">
                                                <svg class="w-5 h-5 text-accent mt-0.5" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z">
                                                    </path>
                                                </svg>
                                                <div>
                                                    <p class="text-accent font-medium font-main">
                                                        {{ __('Complete your profile') }}</p>
                                                    <p class="text-accent/80 text-sm font-main mt-1">
                                                        {{ __('Add your professional information to showcase your expertise and attract more opportunities.') }}
                                                    </p>
                                                    <a href="{{ route('profile') }}"
                                                        class="inline-flex items-center mt-2 text-accent hover:text-accent/80 text-sm font-medium font-main">
                                                        {{ __('Complete Profile') }}
                                                        <svg class="w-4 h-4 ml-1" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                        </svg>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Social Links Section -->
                    @if ($dashboardData['socialLinks']->count() > 0)
                        <div class="bg-white rounded-xl shadow-sm border border-neutral-200 overflow-hidden">
                            <div class="bg-gradient-to-r from-secondary to-secondary/90 px-6 py-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1">
                                            </path>
                                        </svg>
                                    </div>
                                    <h3 class="text-lg font-semibold text-white font-main">{{ __('Social Links') }}
                                    </h3>
                                </div>
                            </div>
                            <div class="p-6">
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                    @foreach ($dashboardData['socialLinks'] as $socialLink)
                                        <a href="{{ $socialLink->url }}" target="_blank"
                                            class="group flex items-center p-3 rounded-lg border border-neutral-200 hover:border-secondary/50 hover:shadow-md transition-all duration-200"
                                            style="background: linear-gradient(135deg, {{ $socialLink->socialNetwork->color }}10, {{ $socialLink->socialNetwork->color }}05);">
                                            <div class="w-10 h-10 rounded-lg flex items-center justify-center mr-3"
                                                style="background: {{ $socialLink->socialNetwork->color }}20;">
                                                <i class="{{ $socialLink->socialNetwork->icon }} text-lg"
                                                    style="color: {{ $socialLink->socialNetwork->color }};"></i>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="font-medium text-neutral-900 font-main truncate">
                                                    {{ $socialLink->display_name ?: $socialLink->username }}
                                                </p>
                                                <p class="text-sm text-neutral-500 font-main">
                                                    {{ $socialLink->socialNetwork->name }}
                                                </p>
                                            </div>
                                            <svg class="w-4 h-4 text-neutral-400 group-hover:text-secondary transition-colors duration-200"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14">
                                                </path>
                                            </svg>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Skills and Software Row -->
                    <div class="grid grid-cols-1 gap-6">
                        <!-- Skills Section -->
                        @if ($dashboardData['skills']->count() > 0)
                            <div class="bg-white rounded-xl shadow-sm border border-neutral-200 overflow-hidden">
                                <div
                                    class="bg-gradient-to-r from-secondary/20 to-secondary/10 px-6 py-4 border-b border-neutral-200">
                                    <div class="flex items-center space-x-3">
                                        <div
                                            class="w-8 h-8 bg-secondary/20 rounded-lg flex items-center justify-center">
                                            <svg class="w-4 h-4 text-secondary" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z">
                                                </path>
                                            </svg>
                                        </div>
                                        <h3 class="text-lg font-semibold text-neutral-900 font-main">
                                            {{ __('Skills') }}</h3>
                                    </div>
                                </div>
                                <div class="p-6">
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($dashboardData['skills'] as $skill)
                                            <span
                                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-secondary/20 text-secondary font-main">
                                                {{ $skill->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Software Section -->
                        @if ($dashboardData['software']->count() > 0)
                            <div class="bg-white rounded-xl shadow-sm border border-neutral-200 overflow-hidden">
                                <div
                                    class="bg-gradient-to-r from-accent/20 to-accent/10 px-6 py-4 border-b border-neutral-200">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 bg-accent/20 rounded-lg flex items-center justify-center">
                                            <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                                                </path>
                                            </svg>
                                        </div>
                                        <h3 class="text-lg font-semibold text-neutral-900 font-main">
                                            {{ __('Software & Tools') }}</h3>
                                    </div>
                                </div>
                                <div class="p-6">
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($dashboardData['software'] as $software)
                                            <span
                                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-accent/20 text-accent font-main">
                                                {{ $software->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Reviews Section -->
                    @if ($dashboardData['reviews']->count() > 0)
                        <div class="bg-white rounded-xl shadow-sm border border-neutral-200 overflow-hidden">
                            <div
                                class="bg-gradient-to-r from-accent/20 to-accent/10 px-6 py-4 border-b border-neutral-200">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 bg-accent/20 rounded-lg flex items-center justify-center">
                                            <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z">
                                                </path>
                                            </svg>
                                        </div>
                                        <h3 class="text-lg font-semibold text-neutral-900 font-main">
                                            {{ __('Recent Reviews') }}</h3>
                                    </div>
                                    <span
                                        class="text-sm text-neutral-500 font-main">{{ $dashboardData['reviews']->count() }}
                                        {{ __('reviews') }}</span>
                                </div>
                            </div>

                            <div class="p-6">
                                <div class="space-y-6">
                                    @foreach ($dashboardData['reviews'] as $review)
                                        <div class="border-b border-neutral-200 pb-6 last:border-b-0 last:pb-0">
                                            <div class="flex items-start space-x-4">
                                                <!-- Reviewer Avatar -->
                                                <div class="flex-shrink-0">
                                                    @if ($review->reviewer->profile && $review->reviewer->profile->avatar_url)
                                                        <img src="{{ $review->reviewer->profile->avatar_url }}"
                                                            alt="{{ $review->reviewer->name }}"
                                                            class="w-12 h-12 rounded-full object-cover border-2 border-neutral-200">
                                                    @else
                                                        <div
                                                            class="w-12 h-12 rounded-full bg-primary/10 border-2 border-primary/20 flex items-center justify-center">
                                                            <span class="text-primary font-semibold font-main">
                                                                {{ $review->reviewer->getInitials() }}
                                                            </span>
                                                        </div>
                                                    @endif
                                                </div>

                                                <!-- Review Content -->
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center justify-between mb-2">
                                                        <div>
                                                            <h5 class="font-medium text-neutral-900 font-main">
                                                                {{ $review->reviewer->name }}</h5>
                                                            <p class="text-sm text-neutral-500 font-main">
                                                                {{ $review->created_at->diffForHumans() }}</p>
                                                        </div>
                                                        <div class="flex items-center space-x-1">
                                                            @for ($i = 1; $i <= 5; $i++)
                                                                <svg class="w-4 h-4 {{ $i <= $review->rating ? 'text-accent' : 'text-neutral-300' }}"
                                                                    fill="currentColor" viewBox="0 0 20 20">
                                                                    <path
                                                                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z">
                                                                    </path>
                                                                </svg>
                                                            @endfor
                                                            <span
                                                                class="ml-2 text-sm font-medium text-neutral-900 font-main">{{ $review->rating }}.0</span>
                                                        </div>
                                                    </div>

                                                    @if ($review->engagement)
                                                        <div class="mb-3">
                                                            <span
                                                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-primary/10 text-primary font-main">
                                                                {{ $review->engagement->job->title }}
                                                            </span>
                                                        </div>
                                                    @endif

                                                    <p class="text-neutral-700 font-main leading-relaxed">
                                                        {{ $review->review }}</p>

                                                    @if ($review->tags && count($review->tags) > 0)
                                                        <div class="flex flex-wrap gap-1 mt-3">
                                                            @foreach ($review->tags as $tag)
                                                                <span
                                                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-secondary/10 text-secondary font-main">
                                                                    {{ $tag }}
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @else
                        <div
                            class="text-center bg-white rounded-xl shadow-sm border border-neutral-200 overflow-hidden py-12">
                            <div
                                class="w-16 h-16 mx-auto mb-4 bg-accent/10 rounded-full flex items-center justify-center">
                                <svg class="w-8 h-8 text-accent/60" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z">
                                    </path>
                                </svg>
                            </div>
                            <h4 class="text-lg font-medium text-neutral-900 font-main mb-2">{{ __('No Reviews Yet') }}
                            </h4>
                            <p class="text-neutral-500 font-main max-w-sm mx-auto">
                                {{ __('Your reviews will appear here once clients start sharing their experiences working with you.') }}
                            </p>
                        </div>
                    @endif
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Review Statistics -->
                    @if ($dashboardData['reviewStats']['total_reviews'] > 0)
                        <div class="bg-white rounded-xl shadow-sm border border-neutral-200 overflow-hidden">
                            <div
                                class="bg-gradient-to-r from-accent/20 to-accent/10 px-6 py-4 border-b border-neutral-200">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-accent/20 rounded-lg flex items-center justify-center">
                                        <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                                            </path>
                                        </svg>
                                    </div>
                                    <h3 class="text-lg font-semibold text-neutral-900 font-main">
                                        {{ __('Review Statistics') }}</h3>
                                </div>
                            </div>

                            <div class="p-6">
                                <div class="text-center mb-6">
                                    <div class="text-4xl font-bold text-accent font-main">
                                        {{ number_format($dashboardData['reviewStats']['average_rating'], 1) }}
                                    </div>
                                    <div class="flex justify-center items-center mt-2 mb-1">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <svg class="w-5 h-5 {{ $i <= round($dashboardData['reviewStats']['average_rating']) ? 'text-accent' : 'text-neutral-300' }}"
                                                fill="currentColor" viewBox="0 0 20 20">
                                                <path
                                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z">
                                                </path>
                                            </svg>
                                        @endfor
                                    </div>
                                    <p class="text-neutral-600 text-sm font-main">
                                        {{ $dashboardData['reviewStats']['total_reviews'] }} {{ __('total reviews') }}
                                    </p>
                                </div>

                                <div class="space-y-3">
                                    @foreach ([5, 4, 3, 2, 1] as $rating)
                                        <div class="flex items-center text-sm">
                                            <span class="w-3 text-neutral-600 font-main">{{ $rating }}</span>
                                            <svg class="w-4 h-4 text-accent ml-1 mr-2" fill="currentColor"
                                                viewBox="0 0 20 20">
                                                <path
                                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z">
                                                </path>
                                            </svg>
                                            <div class="flex-1 mx-2 bg-neutral-200 rounded-full h-2">
                                                @php
                                                    $percentage =
                                                        $dashboardData['reviewStats']['total_reviews'] > 0
                                                            ? ($dashboardData['reviewStats']['rating_distribution'][
                                                                    $rating
                                                                ] /
                                                                    $dashboardData['reviewStats']['total_reviews']) *
                                                                100
                                                            : 0;
                                                @endphp
                                                <div class="bg-accent h-2 rounded-full transition-all duration-300"
                                                    style="width: {{ $percentage }}%"></div>
                                            </div>
                                            <span class="text-neutral-600 w-6 text-right font-main">
                                                {{ $dashboardData['reviewStats']['rating_distribution'][$rating] }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Activity Summary -->
                    <div class="bg-white rounded-xl shadow-sm border border-neutral-200 overflow-hidden">
                        <div
                            class="bg-gradient-to-r from-primary/20 to-primary/10 px-6 py-4 border-b border-neutral-200">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-primary/20 rounded-lg flex items-center justify-center">
                                    <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                                        </path>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-semibold text-neutral-900 font-main">
                                    {{ __('Activity Summary') }}</h3>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <div class="flex justify-between items-center p-3 bg-neutral-50 rounded-lg">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 bg-primary/20 rounded-lg flex items-center justify-center">
                                            <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                                </path>
                                            </svg>
                                        </div>
                                        <span class="text-neutral-700 font-main">{{ __('Applications') }}</span>
                                    </div>
                                    <span
                                        class="font-semibold text-primary font-main">{{ $dashboardData['activitySummary']['applications_count'] }}</span>
                                </div>

                                <div class="flex justify-between items-center p-3 bg-neutral-50 rounded-lg">
                                    <div class="flex items-center space-x-3">
                                        <div
                                            class="w-8 h-8 bg-secondary/20 rounded-lg flex items-center justify-center">
                                            <svg class="w-4 h-4 text-secondary" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0 1 12 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 0 1-.673-.38m0 0A2.18 2.18 0 0 1 3 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 0 1 3.413-.387m7.5 0V5.25A2.25 2.25 0 0 0 13.5 3h-3a2.25 2.25 0 0 0-2.25 2.25v.894m7.5 0a48.667 48.667 0 0 0-7.5 0M12 12.75h.008v.008H12v-.008Z" />
                                            </svg>
                                        </div>
                                        <span class="text-neutral-700 font-main">{{ __('Jobs Posted') }}</span>
                                    </div>
                                    <span
                                        class="font-semibold text-secondary font-main">{{ $dashboardData['activitySummary']['jobs_posted_count'] }}</span>
                                </div>

                                <div class="flex justify-between items-center p-3 bg-neutral-50 rounded-lg">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 bg-accent/20 rounded-lg flex items-center justify-center">
                                            <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                        <span class="text-neutral-700 font-main">{{ __('Active Engagements') }}</span>
                                    </div>
                                    <span
                                        class="font-semibold text-accent font-main">{{ $dashboardData['activitySummary']['active_engagements_count'] }}</span>
                                </div>

                                <div class="flex justify-between items-center p-3 bg-neutral-50 rounded-lg">
                                    <div class="flex items-center space-x-3">
                                        <div
                                            class="w-8 h-8 bg-green-500/20 rounded-lg flex items-center justify-center">
                                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        </div>
                                        <span class="text-neutral-700 font-main">{{ __('Completed') }}</span>
                                    </div>
                                    <span
                                        class="font-semibold text-green-600 font-main">{{ $dashboardData['activitySummary']['completed_engagements_count'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white rounded-xl shadow-sm border border-neutral-200 overflow-hidden">
                        <div
                            class="bg-gradient-to-r from-secondary/20 to-secondary/10 px-6 py-4 border-b border-neutral-200">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-secondary/20 rounded-lg flex items-center justify-center">
                                    <svg class="w-4 h-4 text-secondary" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-semibold text-neutral-900 font-main">{{ __('Quick Actions') }}
                                </h3>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="space-y-3">
                                <a href="{{ route('profile') }}"
                                    class="flex items-center justify-center w-full px-4 py-3 bg-primary hover:bg-primary/90 text-white font-medium rounded-lg shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 font-main">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                        </path>
                                    </svg>
                                    {{ __('Edit Profile') }}
                                </a>

                                <a href="{{ route('jobs.browse') }}"
                                    class="flex items-center justify-center w-full px-4 py-3 bg-secondary hover:bg-secondary/90 text-white font-medium rounded-lg shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-secondary focus:ring-offset-2 font-main">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                    {{ __('Browse Jobs') }}
                                </a>

                                <a href="{{ route('jobs.create') }}"
                                    class="flex items-center justify-center w-full px-4 py-3 bg-accent hover:bg-accent/90 text-white font-medium rounded-lg shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2 font-main">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    {{ __('Post a Job') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</x-app-layout>
