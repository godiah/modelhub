<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-tertiary font-bold text-2xl text-primary leading-tight flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="h-6 w-6 mr-2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
                </svg>
                Job Engagements
            </h2>
            <div class="flex items-center text-sm font-tertiary font-medium text-neutral-500">
                <span class="hidden md:inline-flex items-center mr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-secondary" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Active: {{ $engagements->where('status', 'active')->count() }}
                </span>
                <span class="hidden md:inline-flex items-center mr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-accent" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                    </svg>
                    Pending: {{ $engagements->where('status', 'employer_accepted')->count() }}
                </span>
                <span class="hidden md:inline-flex items-center mr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-green-800" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Completed: {{ $engagements->where('status', 'completed')->count() }}
                </span>
                <span class="hidden md:inline-flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="h-4 w-4 mr-1 text-red-800">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    </svg>
                    Withdrawn: {{ $engagements->where('status', 'cancelled')->count() }}
                </span>
            </div>
        </div>
    </x-slot>

    <div class="container mx-auto max-w-7xl px-4 py-8 pb-24">
        <!-- Search & Filter -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="relative flex-grow max-w-md">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-neutral-400" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" id="searchEngagements" name="search"
                    class="font-tertiary text-sm block w-full pl-10 pr-3 py-2.5 border border-neutral-300 rounded-lg focus:ring-primary focus:border-primary"
                    value="{{ request('search') }}" placeholder="Search engagements...">
            </div>

            <div class="flex gap-3">
                <div>
                    <select id="statusEngagementFilter" name="status"
                        class="font-tertiary block w-full border-neutral-300 rounded-lg focus:ring-primary focus:border-primary py-2.5 pl-3 pr-10 text-sm">
                        <option value="all">All Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="completed"{{ request('status') == 'completed' ? 'selected' : '' }}>Completed
                        </option>
                        <option value="cancelled"{{ request('status') == 'cancelled' ? 'selected' : '' }}>Withdrawn
                        </option>
                    </select>
                </div>
                <div>
                    @if ($hasArchivedEngagements)
                        <a href="{{ route('engagements.archived') }}"
                            class="inline-flex items-center px-4 py-2.5 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors duration-200 font-tertiary text-sm font-medium shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="h-5 w-5 mr-2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                            </svg>
                            View Archived
                        </a>
                    @endif
                </div>
            </div>
        </div>
        <div id="engagementsContainer">
            @if ($engagements->isEmpty() && !$hasFilters)
                <div class="bg-white border border-neutral-200 rounded-xl p-12 text-center shadow-sm">
                    <div class="bg-neutral-100 h-24 w-24 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="h-12 w-12 text-neutral-400">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
                        </svg>
                    </div>
                    <h3 class="font-tertiary font-semibold text-xl text-neutral-700 mb-3">No Job Engagements Found</h3>
                    <p class="text-neutral-500 font-main max-w-md mx-auto mb-6">You don't have any active job
                        engagements at
                        the moment. Apply to job posts to receive offers.</p>
                    <a href="{{ route('jobs.browse') }}"
                        class="inline-flex items-center px-5 py-3 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-gradient-to-r from-primary to-primary/90 hover:from-primary/90 hover:to-primary/80 transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        Browse Available Jobs
                    </a>
                </div>
            @else
                @include('jobBoard.engagements.partials.engagements-list', [
                    'engagements' => $engagements,
                    'hasFilters' => $hasFilters,
                    'hasArchivedEngagements' => $hasArchivedEngagements,
                ])
            @endif
        </div>
    </div>

    <!-- Create new deliverable script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const openModalBtn = document.getElementById('openDeliverableModal');
            const closeModalBtn = document.getElementById('closeDeliverableModal');
            const cancelBtn = document.getElementById('cancelDeliverableBtn');
            const modal = document.getElementById('deliverableModal');
            const backdrop = document.getElementById('deliverableModalBackdrop');

            // Set minimum date for due_date to tomorrow
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            const dueDateInput = document.getElementById('due_date');
            dueDateInput.min = tomorrow.toISOString().split('T')[0];

            // Open modal function
            function openModal() {
                backdrop.classList.remove('hidden');
                document.body.style.overflow = 'hidden'; // Prevent scrolling
            }

            // Close modal function
            function closeModal() {
                backdrop.classList.add('hidden');
                document.body.style.overflow = ''; // Enable scrolling
            }

            // Event listeners
            openModalBtn.addEventListener('click', openModal);
            closeModalBtn.addEventListener('click', closeModal);
            cancelBtn.addEventListener('click', closeModal);

            // Close modal when clicking outside
            backdrop.addEventListener('click', function(event) {
                if (event.target === backdrop) {
                    closeModal();
                }
            });

            // Close modal with ESC key
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape' && !backdrop.classList.contains('hidden')) {
                    closeModal();
                }
            });

            // Show the modal automatically if there are validation errors
            @if ($errors->any())
                openModal();
            @endif

            // Show the modal automatically if returning after form submission with an error
            @if (session('error'))
                openModal();
            @endif
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.getElementById('searchEngagements');
            const statusSelect = document.getElementById('statusEngagementFilter');
            const container = document.getElementById('engagementsContainer');
            const clearBtn = document.getElementById('clearEngagementFilters');
            let debounce;

            function fetchList() {
                const url = new URL(window.location.href);
                url.searchParams.set('search', searchInput.value);
                url.searchParams.set('status', statusSelect.value);

                container.classList.add('opacity-50');
                fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(r => r.text())
                    .then(html => {
                        container.innerHTML = html;
                        window.history.pushState({}, '', url);
                        container.classList.remove('opacity-50');
                        bindPagination();
                    });
            }

            searchInput.addEventListener('input', () => {
                clearTimeout(debounce);
                debounce = setTimeout(fetchList, 300);
            });
            statusSelect.addEventListener('change', fetchList);

            document.addEventListener('click', e => {
                if (e.target.id === 'clearEngagementFilters') {
                    e.preventDefault();
                    searchInput.value = '';
                    statusSelect.value = 'all';
                    fetchList();
                }
            });

            function bindPagination() {
                container.querySelectorAll('.pagination a').forEach(link => {
                    link.addEventListener('click', e => {
                        e.preventDefault();
                        fetch(link.href, {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            })
                            .then(r => r.text())
                            .then(html => {
                                container.innerHTML = html;
                                window.history.pushState({}, '', link.href);
                                bindPagination();
                            });
                    });
                });
            }

            bindPagination();
        });
    </script>
</x-app-layout>
