<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-tertiary font-bold text-2xl text-primary leading-tight flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="h-6 w-6 mr-2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M8.25 7.5V6.108c0-1.135.845-2.098 1.976-2.192.373-.03.748-.057 1.123-.08M15.75 18H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08M15.75 18.75v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5A3.375 3.375 0 0 0 6.375 7.5H5.25m11.9-3.664A2.251 2.251 0 0 0 15 2.25h-1.5a2.251 2.251 0 0 0-2.15 1.586m5.8 0c.065.21.1.433.1.664v.75h-6V4.5c0-.231.035-.454.1-.664M6.75 7.5H4.875c-.621 0-1.125.504-1.125 1.125v12c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V16.5a9 9 0 0 0-9-9Z" />
                </svg>
                {{ __('Projects Dashboard') }}
            </h2>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-lg border border-neutral-200 p-6 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium font-main text-neutral-600">Total Projects</p>
                        <p class="text-3xl font-bold font-tertiary text-neutral-900 mt-1">{{ $stats['total'] }}</p>
                    </div>
                    <div
                        class="w-12 h-12 bg-gradient-to-br from-primary/10 to-primary/20 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0 1 12 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 0 1-.673-.38m0 0A2.18 2.18 0 0 1 3 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 0 1 3.413-.387m7.5 0V5.25A2.25 2.25 0 0 0 13.5 3h-3a2.25 2.25 0 0 0-2.25 2.25v.894m7.5 0a48.667 48.667 0 0 0-7.5 0M12 12.75h.008v.008H12v-.008Z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg border border-neutral-200 p-6 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium font-main text-neutral-600">Active</p>
                        <p class="text-3xl font-bold font-tertiary text-secondary mt-1">{{ $stats['active'] }}</p>
                    </div>
                    <div
                        class="w-12 h-12 bg-gradient-to-br from-secondary/10 to-secondary/20 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg border border-neutral-200 p-6 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium font-main text-neutral-600">Completed</p>
                        <p class="text-3xl font-bold font-tertiary text-green-600 mt-1">{{ $stats['completed'] }}</p>
                    </div>
                    <div
                        class="w-12 h-12 bg-gradient-to-br from-green-100 to-green-200 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg border border-neutral-200 p-6 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium font-main text-neutral-600">Total Earnings</p>
                        <p class="text-2xl font-bold font-tertiary text-accent mt-1">
                            Ksh{{ number_format($stats['total_earnings'], 2) }}
                        </p>
                    </div>
                    <div
                        class="w-12 h-12 bg-gradient-to-br from-accent/10 to-accent/20 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs Navigation -->
        <div class="bg-white rounded-xl shadow-lg border border-neutral-200 mb-6">
            <div class="border-b border-neutral-200 font-main">
                <nav class="-mb-px flex space-x-2 px-6 overflow-x-auto" aria-label="Tabs">
                    <button data-tab="all"
                        class="tab-button py-4 px-3 border-b-2 font-medium text-sm transition-colors whitespace-nowrap {{ $tab === 'all' ? 'border-primary text-primary' : 'border-transparent text-neutral-500 hover:text-neutral-700 hover:border-neutral-300' }}">
                        All
                        <span
                            class="ml-2 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-neutral-600 bg-neutral-100 rounded-full">{{ $stats['total'] }}</span>
                    </button>

                    <button data-tab="active"
                        class="tab-button py-4 px-3 border-b-2 font-medium text-sm transition-colors whitespace-nowrap {{ $tab === 'active' ? 'border-primary text-primary' : 'border-transparent text-neutral-500 hover:text-neutral-700 hover:border-neutral-300' }}">
                        Active
                        <span
                            class="ml-2 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-blue-600 bg-blue-100 rounded-full">{{ $stats['active'] }}</span>
                    </button>

                    <button data-tab="pending"
                        class="tab-button py-4 px-3 border-b-2 font-medium text-sm transition-colors whitespace-nowrap {{ $tab === 'pending' ? 'border-primary text-primary' : 'border-transparent text-neutral-500 hover:text-neutral-700 hover:border-neutral-300' }}">
                        Pending
                        <span
                            class="ml-2 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-yellow-600 bg-yellow-100 rounded-full">{{ $stats['pending'] }}</span>
                    </button>

                    <button data-tab="completed"
                        class="tab-button py-4 px-3 border-b-2 font-medium text-sm transition-colors whitespace-nowrap {{ $tab === 'completed' ? 'border-primary text-primary' : 'border-transparent text-neutral-500 hover:text-neutral-700 hover:border-neutral-300' }}">
                        Completed
                        <span
                            class="ml-2 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-green-600 bg-green-100 rounded-full">{{ $stats['completed'] }}</span>
                    </button>

                    <button data-tab="cancelled"
                        class="tab-button py-4 px-3 border-b-2 font-medium text-sm transition-colors whitespace-nowrap {{ $tab === 'cancelled' ? 'border-primary text-primary' : 'border-transparent text-neutral-500 hover:text-neutral-700 hover:border-neutral-300' }}">
                        Cancelled
                        <span
                            class="ml-2 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-600 bg-red-100 rounded-full">{{ $stats['cancelled'] }}</span>
                    </button>

                    <button data-tab="disputed"
                        class="tab-button py-4 px-3 border-b-2 font-medium text-sm transition-colors whitespace-nowrap {{ $tab === 'disputed' ? 'border-primary text-primary' : 'border-transparent text-neutral-500 hover:text-neutral-700 hover:border-neutral-300' }}">
                        Disputed
                        <span
                            class="ml-2 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-purple-600 bg-purple-100 rounded-full">{{ $stats['disputed'] }}</span>
                    </button>

                    <button data-tab="settled"
                        class="tab-button py-4 px-3 border-b-2 font-medium text-sm transition-colors whitespace-nowrap {{ $tab === 'settled' ? 'border-primary text-primary' : 'border-transparent text-neutral-500 hover:text-neutral-700 hover:border-neutral-300' }}">
                        Settled
                        <span
                            class="ml-2 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-indigo-600 bg-indigo-100 rounded-full">{{ $stats['settled'] }}</span>
                    </button>

                    <button data-tab="archived"
                        class="tab-button py-4 px-3 border-b-2 font-medium text-sm transition-colors whitespace-nowrap {{ $tab === 'archived' ? 'border-primary text-primary' : 'border-transparent text-neutral-500 hover:text-neutral-700 hover:border-neutral-300' }}">
                        Archived
                        <span
                            class="ml-2 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-neutral-600 bg-neutral-100 rounded-full">{{ $stats['archived'] }}</span>
                    </button>
                </nav>
            </div>

            <!-- Loading Indicator -->
            <div id="loading-indicator" class="hidden p-6">
                <div class="flex items-center justify-center">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                    <span class="ml-3 text-neutral-600 font-secondary text-sm">Loading projects...</span>
                </div>
            </div>

            <!-- Engagements List Container -->
            <div class="p-6" id="engagements-container">
                @include('projects.partials.projects-list', [
                    'engagements' => $engagements,
                    'tab' => $tab,
                ])
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('.tab-button');
            const loadingIndicator = document.getElementById('loading-indicator');
            const engagementsContainer = document.getElementById('engagements-container');
            let currentTab = '{{ $tab }}';

            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const newTab = this.getAttribute('data-tab');

                    // Don't reload if clicking the same tab
                    if (newTab === currentTab) {
                        return;
                    }

                    // Update active tab styling
                    tabButtons.forEach(btn => {
                        btn.classList.remove('border-primary', 'text-primary');
                        btn.classList.add('border-transparent', 'text-neutral-500');
                    });

                    this.classList.remove('border-transparent', 'text-neutral-500');
                    this.classList.add('border-primary', 'text-primary');

                    // Show loading indicator
                    loadingIndicator.classList.remove('hidden');
                    engagementsContainer.classList.add('opacity-50');

                    // Make AJAX request
                    fetch(`{{ route('project.index') }}?tab=${newTab}`, {
                            method: 'GET',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'text/html',
                                'X-CSRF-TOKEN': document.querySelector(
                                    'meta[name="csrf-token"]').getAttribute('content')
                            }
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.text();
                        })
                        .then(html => {
                            // Update the container with new content
                            engagementsContainer.innerHTML = html;
                            currentTab = newTab;

                            // Update URL without page reload
                            const url = new URL(window.location);
                            url.searchParams.set('tab', newTab);
                            window.history.pushState({}, '', url);
                        })
                        .catch(error => {
                            console.error('Error loading tab content:', error);
                            // Show error message
                            engagementsContainer.innerHTML = `
                            <div class="text-center py-12 font-main">
                                <div class="w-20 h-20 bg-gradient-to-br from-red-100 to-red-200 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-10 h-10 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-medium text-neutral-900 mb-2">Error Loading Projects</h3>
                                <p class="text-neutral-500 mb-4">There was an error loading the projects. Please try again.</p>
                                <button onclick="location.reload()" class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-md hover:bg-primary-dark transition-colors">
                                    Reload Page
                                </button>
                            </div>
                        `;
                        })
                        .finally(() => {
                            // Hide loading indicator
                            loadingIndicator.classList.add('hidden');
                            engagementsContainer.classList.remove('opacity-50');
                        });
                });
            });

            // Handle browser back/forward buttons
            window.addEventListener('popstate', function(event) {
                const urlParams = new URLSearchParams(window.location.search);
                const tab = urlParams.get('tab') || 'all';

                // Find and click the appropriate tab button
                const targetButton = document.querySelector(`[data-tab="${tab}"]`);
                if (targetButton) {
                    targetButton.click();
                }
            });
        });
    </script>
</x-app-layout>
