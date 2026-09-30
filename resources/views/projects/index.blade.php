<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <x-card shadow="lg" class="p-6 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium font-main text-neutral-600">Total Projects</p>
                        <p class="text-3xl font-bold font-tertiary text-neutral-900 mt-1">{{ $stats['total'] }}</p>
                    </div>
                    <div
                        class="w-12 h-12 bg-gradient-to-br from-primary/10 to-primary/20 rounded-lg flex items-center justify-center">
                        <x-icon name="briefcase" class="w-6 h-6 text-primary" stroke-width="2" />
                    </div>
                </div>
            </x-card>

            <x-card shadow="lg" class="p-6 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium font-main text-neutral-600">Active</p>
                        <p class="text-3xl font-bold font-tertiary text-secondary mt-1">{{ $stats['active'] }}</p>
                    </div>
                    <div
                        class="w-12 h-12 bg-gradient-to-br from-secondary/10 to-secondary/20 rounded-lg flex items-center justify-center">
                        <x-icon name="clock" class="w-6 h-6 text-secondary" />
                    </div>
                </div>
            </x-card>

            <x-card shadow="lg" class="p-6 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium font-main text-neutral-600">Completed</p>
                        <p class="text-3xl font-bold font-tertiary text-green-600 mt-1">{{ $stats['completed'] }}</p>
                    </div>
                    <div
                        class="w-12 h-12 bg-gradient-to-br from-green-100 to-green-200 rounded-lg flex items-center justify-center">
                        <x-icon name="check-circle" class="w-6 h-6 text-green-600" />
                    </div>
                </div>
            </x-card>

            <x-card shadow="lg" class="p-6 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium font-main text-neutral-600">Total Earnings</p>
                        <p class="text-2xl font-bold font-tertiary text-accent mt-1">
                            <x-money :amount="$stats['total_earnings']" />
                        </p>
                    </div>
                    <div
                        class="w-12 h-12 bg-gradient-to-br from-accent/10 to-accent/20 rounded-lg flex items-center justify-center">
                        <x-icon name="banknotes" class="w-6 h-6 text-accent" stroke-width="1.5" />
                    </div>
                </div>
            </x-card>
        </div>

        <!-- Tabs Navigation -->
        <x-card shadow="lg" class="mb-6">
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
        </x-card>
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
