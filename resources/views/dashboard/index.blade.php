<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-tertiary font-bold text-2xl text-primary leading-tight flex items-center">
                <x-icon name="squares-2x2" class="h-6 w-6 mr-2" stroke-width="1.5" />
                {{ __('Dashboard') }}
            </h2>
        </div>
    </x-slot>

    <div class="min-h-screen bg-neutral-50">
        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <!-- Navigation Links -->
            <div class="mb-8">
                <x-card class="p-2">
                    <nav class="flex space-x-1">
                        <a href="{{ route('applications.my') }}"
                            class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all duration-200 font-secondary {{ request()->routeIs('applications.my') ? 'bg-secondary text-white shadow-sm' : 'text-neutral-700 hover:text-secondary hover:bg-secondary/10' }}">
                            <x-icon name="document-text" class="w-4 h-4 mr-2" />
                            {{ __('My Applications') }}
                        </a>
                        <a href="{{ route('my-jobs.index') }}"
                            class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all duration-200 font-secondary {{ request()->routeIs('my-jobs.index') ? 'bg-secondary text-white shadow-sm' : 'text-neutral-700 hover:text-secondary hover:bg-secondary/10' }}">
                            <x-icon name="briefcase" class="w-4 h-4 mr-2" stroke-width="2" />
                            {{ __('Jobs Posted') }}
                        </a>
                        <a href="{{ route('engagements.index') }}"
                            class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all duration-200 font-secondary {{ request()->routeIs('engagements.index') ? 'bg-secondary text-white shadow-sm' : 'text-neutral-700 hover:text-secondary hover:bg-secondary/10' }}">
                            <x-icon name="chat-bubble-text" class="w-4 h-4 mr-2" stroke-width="2" />
                            {{ __('My Engagements') }}
                        </a>
                    </nav>
                </x-card>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Profile Section -->
                    <x-card clip>
                        <x-section-header :title="__('Profile Information')"
                            :subtitle="__('Your professional profile overview')">
                            <x-slot:icon>
                                <x-icon name="user" class="w-5 h-5 text-white" />
                            </x-slot:icon>
                        </x-section-header>

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
                                            <x-icon name="pencil-square" class="w-4 h-4 mr-1" />
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
                                                    <x-icon name="phone" class="w-4 h-4 mr-2 text-neutral-400" />
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
                                                <x-icon name="exclamation-triangle-2" class="w-5 h-5 text-accent mt-0.5" />
                                                <div>
                                                    <p class="text-accent font-medium font-main">
                                                        {{ __('Complete your profile') }}</p>
                                                    <p class="text-accent/80 text-sm font-main mt-1">
                                                        {{ __('Add your professional information to showcase your expertise and attract more opportunities.') }}
                                                    </p>
                                                    <a href="{{ route('profile') }}"
                                                        class="inline-flex items-center mt-2 text-accent hover:text-accent/80 text-sm font-medium font-main">
                                                        {{ __('Complete Profile') }}
                                                        <x-icon name="chevron-right" class="w-4 h-4 ml-1" />
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </x-card>

                    <!-- Social Links Section -->
                    @if ($dashboardData['socialLinks']->count() > 0)
                        <x-card clip>
                            <div class="bg-gradient-to-r from-secondary to-secondary/90 px-6 py-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                                        <x-icon name="link" class="w-4 h-4 text-white" />
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
                                            <x-icon name="arrow-top-right-on-square" class="w-4 h-4 text-neutral-400 group-hover:text-secondary transition-colors duration-200" />
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </x-card>
                    @endif

                    <!-- Skills and Software Row -->
                    <div class="grid grid-cols-1 gap-6">
                        <!-- Skills Section -->
                        @if ($dashboardData['skills']->count() > 0)
                            <x-card clip>
                                <div
                                    class="bg-gradient-to-r from-secondary/20 to-secondary/10 px-6 py-4 border-b border-neutral-200">
                                    <div class="flex items-center space-x-3">
                                        <div
                                            class="w-8 h-8 bg-secondary/20 rounded-lg flex items-center justify-center">
                                            <x-icon name="light-bulb" class="w-4 h-4 text-secondary" />
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
                            </x-card>
                        @endif

                        <!-- Software Section -->
                        @if ($dashboardData['software']->count() > 0)
                            <x-card clip>
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
                            </x-card>
                        @endif
                    </div>

                    <!-- Reviews Section -->
                    @if ($dashboardData['reviews']->count() > 0)
                        <x-card clip>
                            <div
                                class="bg-gradient-to-r from-accent/20 to-accent/10 px-6 py-4 border-b border-neutral-200">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 bg-accent/20 rounded-lg flex items-center justify-center">
                                            <x-icon name="star" class="w-4 h-4 text-accent" />
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
                        </x-card>
                    @else
                        <x-card clip class="text-center py-12">
                            <div
                                class="w-16 h-16 mx-auto mb-4 bg-accent/10 rounded-full flex items-center justify-center">
                                <x-icon name="star" class="w-8 h-8 text-accent/60" />
                            </div>
                            <h4 class="text-lg font-medium text-neutral-900 font-main mb-2">{{ __('No Reviews Yet') }}
                            </h4>
                            <p class="text-neutral-500 font-main max-w-sm mx-auto">
                                {{ __('Your reviews will appear here once clients start sharing their experiences working with you.') }}
                            </p>
                        </x-card>
                    @endif
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Review Statistics -->
                    @if ($dashboardData['reviewStats']['total_reviews'] > 0)
                        <x-card clip>
                            <div
                                class="bg-gradient-to-r from-accent/20 to-accent/10 px-6 py-4 border-b border-neutral-200">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-accent/20 rounded-lg flex items-center justify-center">
                                        <x-icon name="chart-bar" class="w-4 h-4 text-accent" />
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
                                            <x-icon name="star-solid" class="w-5 h-5 {{ $i <= round($dashboardData['reviewStats']['average_rating']) ? 'text-accent' : 'text-neutral-300' }}" />
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
                                            <x-icon name="star-solid" class="w-4 h-4 text-accent ml-1 mr-2" />
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
                        </x-card>
                    @endif

                    <!-- Activity Summary -->
                    <x-card clip>
                        <div
                            class="bg-gradient-to-r from-primary/20 to-primary/10 px-6 py-4 border-b border-neutral-200">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-primary/20 rounded-lg flex items-center justify-center">
                                    <x-icon name="chart-bar" class="w-4 h-4 text-primary" />
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
                                            <x-icon name="document-text" class="w-4 h-4 text-primary" />
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
                                            <x-icon name="briefcase" class="w-4 h-4 text-secondary" stroke-width="2" />
                                        </div>
                                        <span class="text-neutral-700 font-main">{{ __('Jobs Posted') }}</span>
                                    </div>
                                    <span
                                        class="font-semibold text-secondary font-main">{{ $dashboardData['activitySummary']['jobs_posted_count'] }}</span>
                                </div>

                                <div class="flex justify-between items-center p-3 bg-neutral-50 rounded-lg">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 bg-accent/20 rounded-lg flex items-center justify-center">
                                            <x-icon name="clock" class="w-4 h-4 text-accent" />
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
                                            <x-icon name="check" class="w-4 h-4 text-green-600" />
                                        </div>
                                        <span class="text-neutral-700 font-main">{{ __('Completed') }}</span>
                                    </div>
                                    <span
                                        class="font-semibold text-green-600 font-main">{{ $dashboardData['activitySummary']['completed_engagements_count'] }}</span>
                                </div>
                            </div>
                        </div>
                    </x-card>

                    <!-- Quick Actions -->
                    <x-card clip>
                        <div
                            class="bg-gradient-to-r from-secondary/20 to-secondary/10 px-6 py-4 border-b border-neutral-200">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-secondary/20 rounded-lg flex items-center justify-center">
                                    <x-icon name="bolt" class="w-4 h-4 text-secondary" />
                                </div>
                                <h3 class="text-lg font-semibold text-neutral-900 font-main">{{ __('Quick Actions') }}
                                </h3>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="space-y-3">
                                <a href="{{ route('profile') }}"
                                    class="flex items-center justify-center w-full px-4 py-3 bg-primary hover:bg-primary/90 text-white font-medium rounded-lg shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 font-main">
                                    <x-icon name="pencil-square" class="w-4 h-4 mr-2" />
                                    {{ __('Edit Profile') }}
                                </a>

                                <a href="{{ route('jobs.browse') }}"
                                    class="flex items-center justify-center w-full px-4 py-3 bg-secondary hover:bg-secondary/90 text-white font-medium rounded-lg shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-secondary focus:ring-offset-2 font-main">
                                    <x-icon name="magnifying-glass" class="w-4 h-4 mr-2" />
                                    {{ __('Browse Jobs') }}
                                </a>

                                <a href="{{ route('jobs.create') }}"
                                    class="flex items-center justify-center w-full px-4 py-3 bg-accent hover:bg-accent/90 text-white font-medium rounded-lg shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2 font-main">
                                    <x-icon name="plus" class="w-4 h-4 mr-2" />
                                    {{ __('Post a Job') }}
                                </a>
                            </div>
                        </div>
                    </x-card>
                </div>
            </div>
        </div>
    </div>

</x-app-layout>
