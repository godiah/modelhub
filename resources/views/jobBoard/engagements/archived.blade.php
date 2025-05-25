<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-tertiary font-bold text-2xl text-primary leading-tight flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="h-6 w-6 mr-2">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                </svg>
                {{ __('Archived Engagements') }}
            </h2>
            <a href="{{ route('engagements.index') }}"
                class="inline-flex items-center px-4 py-2 bg-secondary text-white rounded-lg hover:bg-secondary/90 transition-colors duration-200 font-main text-sm font-medium shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="h-5 w-5 mr-2">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M7 16l-4-4m0 0l4-4m-4 4h18" />
                </svg>
                Back to Active Engagements
            </a>
        </div>
    </x-slot>

    <div class="py-8 bg-gradient-to-br from-neutral-50 to-neutral-100 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Main Content Card -->
            <div class="bg-white rounded-2xl shadow-xl border border-neutral-200 overflow-hidden">
                <div class="bg-gradient-to-r from-primary/5 to-secondary/5 px-6 py-4 border-b border-neutral-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="flex items-center space-x-2">
                                <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3.75 6.75h16.5M3.75 12H12m-8.25 5.25h16.5" />
                                </svg>
                                <span class="text-sm font-medium font-secondary text-neutral-700" id="total-count">
                                    {{ $archivedEngagements->total() }} Total Archived
                                </span>
                            </div>
                        </div>

                        <!-- Filter Options -->
                        <div class="flex items-center space-x-2">
                            <!-- Loading Spinner -->
                            <div id="filter-loading" class="hidden">
                                <svg class="animate-spin h-4 w-4 text-primary" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                            </div>

                            <select id="status-filter"
                                class="text-sm border border-neutral-300 rounded-lg px-3 py-2 font-secondary focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <option value="all">All Status</option>
                                <option value="completed">Completed</option>
                                <option value="settled">Settled</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Engagements Content -->
                <div id="engagements-content">
                    @include('jobBoard.engagements.partials.archived-list', [
                        'archivedEngagements' => $archivedEngagements,
                    ])
                </div>
            </div>
        </div>
    </div>

    <!-- Restore Confirmation Modal -->
    <div id="restore-modal" x-data="{ open: false, engagementId: null }" x-cloak>
        <!-- Modal backdrop -->
        <div x-show="open" class="fixed inset-0 z-40 bg-black bg-opacity-25" @click="open = false"></div>

        <!-- Modal content -->
        <div x-show="open" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Restore Engagement</h3>
                    <p class="text-gray-600 mb-4">
                        Are you sure you want to restore this engagement? It will be moved back to your active
                        engagements list.
                    </p>
                    <div class="flex justify-end space-x-3 mt-6">
                        <button @click="open = false"
                            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <form id="restore-form" action="{{ route('engagements.restore') }}" method="POST">
                            @csrf
                            <input type="hidden" name="engagement_id" x-bind:value="engagementId">
                            <button type="submit"
                                class="px-4 py-2 bg-green-600 border border-transparent rounded-md text-white hover:bg-green-700">
                                Restore
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // 5. JavaScript for AJAX Filtering
        document.addEventListener('DOMContentLoaded', function() {
            const statusFilter = document.getElementById('status-filter');
            const loadingSpinner = document.getElementById('filter-loading');
            const totalCount = document.getElementById('total-count');
            const engagementsContent = document.getElementById('engagements-content');

            let currentRequest = null;

            function showLoading() {
                loadingSpinner.classList.remove('hidden');
                statusFilter.disabled = true;
            }

            function hideLoading() {
                loadingSpinner.classList.add('hidden');
                statusFilter.disabled = false;
            }

            function updateEngagements(status) {
                // Cancel previous request if it exists
                if (currentRequest) {
                    currentRequest.abort();
                }

                showLoading();

                // Create new request
                currentRequest = new XMLHttpRequest();
                currentRequest.open('GET', `{{ route('engagements.archived') }}?status=${status}`, true);
                currentRequest.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                currentRequest.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]')
                    .getAttribute('content'));

                currentRequest.onload = function() {
                    hideLoading();

                    if (currentRequest.status === 200) {
                        try {
                            const response = JSON.parse(currentRequest.responseText);

                            // Update content
                            engagementsContent.innerHTML = response.html;
                            totalCount.textContent = `${response.total} Total Archived`;

                            // Smooth scroll to top of results
                            engagementsContent.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });

                        } catch (error) {
                            console.error('Error parsing response:', error);
                            showError('Failed to parse server response');
                        }
                    } else {
                        showError('Failed to load engagements');
                    }

                    currentRequest = null;
                };

                currentRequest.onerror = function() {
                    hideLoading();
                    showError('Network error occurred');
                    currentRequest = null;
                };

                currentRequest.onabort = function() {
                    hideLoading();
                    currentRequest = null;
                };

                currentRequest.send();
            }

            function showError(message) {
                // Create and show error notification
                const errorDiv = document.createElement('div');
                errorDiv.className =
                    'fixed top-4 right-4 bg-red-500 text-white px-4 py-2 rounded-lg shadow-lg z-50';
                errorDiv.textContent = message;
                document.body.appendChild(errorDiv);

                setTimeout(() => {
                    errorDiv.remove();
                }, 3000);
            }

            // Event listener for filter change
            statusFilter.addEventListener('change', function() {
                const selectedStatus = this.value;
                updateEngagements(selectedStatus);

                // Update URL without reload
                const url = new URL(window.location);
                if (selectedStatus === 'all') {
                    url.searchParams.delete('status');
                } else {
                    url.searchParams.set('status', selectedStatus);
                }
                window.history.pushState({}, '', url);
            });

            // Handle pagination clicks
            document.addEventListener('click', function(e) {
                if (e.target.matches('#pagination-container a') || e.target.closest(
                        '#pagination-container a')) {
                    e.preventDefault();
                    const link = e.target.matches('a') ? e.target : e.target.closest('a');
                    const url = new URL(link.href);
                    const page = url.searchParams.get('page');
                    const status = statusFilter.value;

                    // Update with current filter and new page
                    updateEngagements(status + (page ? `&page=${page}` : ''));
                }
            });

            // Set initial filter state from URL
            const urlParams = new URLSearchParams(window.location.search);
            const initialStatus = urlParams.get('status') || 'all';
            statusFilter.value = initialStatus;
        });
    </script>
</x-app-layout>
