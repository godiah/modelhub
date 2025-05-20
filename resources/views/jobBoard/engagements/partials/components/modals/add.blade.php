<!-- Add Deliverable Modal -->
<div id="deliverableModalBackdrop-{{ $engagement->id }}" class="fixed inset-0 bg-neutral-900/70 z-40 hidden">
    <!-- Modal Container -->
    <div id="deliverableModal-{{ $engagement->id }}"
        class="fixed left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-2xl bg-white rounded-xl shadow-xl z-50">
        <!-- Modal Header -->
        <div class="bg-primary px-6 py-4 flex justify-between items-center">
            <h3 class="text-xl font-tertiary font-bold text-white flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                Add New Deliverable
            </h3>
            <button type="button" id="closeDeliverableModal-{{ $engagement->id }}"
                class="text-white hover:text-neutral-200 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="p-6">
            <form action="{{ route('engagements.deliverables.store', ['engagement' => $engagement->id]) }}"
                method="POST">
                @csrf

                <!-- Title Field -->
                <div class="mb-4">
                    <label for="title" class="block font-tertiary font-medium text-neutral-700 mb-1">
                        <span class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1 text-secondary" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Title
                        </span>
                    </label>
                    <input type="text" id="del_title" name="del_title" required
                        class="w-full px-3 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary focus:border-transparent"
                        placeholder="Enter deliverable title">
                </div>

                <!-- Description Field -->
                <div class="mb-4">
                    <label for="description" class="block font-tertiary font-medium text-neutral-700 mb-1">
                        <span class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1 text-secondary" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h7" />
                            </svg>
                            Description
                        </span>
                    </label>
                    <textarea id="del_description" name="del_description" rows="4"
                        class="w-full px-3 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary focus:border-transparent"
                        placeholder="Describe what needs to be delivered"></textarea>
                </div>

                <!-- Due Date Field -->
                <div class="mb-6">
                    <label for="due_date" class="block font-tertiary font-medium text-neutral-700 mb-1">
                        <span class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1 text-secondary" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Due Date
                        </span>
                    </label>
                    <input type="date" id="due_date" name="due_date"
                        class="w-full px-3 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary focus:border-transparent">
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end gap-3">
                    <button type="button" id="cancelDeliverableBtn-{{ $engagement->id }}"
                        class="px-4 py-2 border border-neutral-300 text-neutral-700 font-tertiary rounded-lg hover:bg-neutral-100 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-4 py-2 bg-secondary text-white font-tertiary font-medium rounded-lg hover:bg-secondary/90 transition-colors flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Save Deliverable
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const engagementId = {{ $engagement->id }};
        const openModalBtn = document.getElementById('openDeliverableModal-' + engagementId);
        const closeModalBtn = document.getElementById('closeDeliverableModal-' + engagementId);
        const cancelBtn = document.getElementById('cancelDeliverableBtn-' + engagementId);
        const modal = document.getElementById('deliverableModal-' + engagementId);
        const backdrop = document.getElementById('deliverableModalBackdrop-' + engagementId);

        if (!openModalBtn || !modal || !backdrop) return;

        function openModal() {
            backdrop.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            backdrop.classList.add('hidden');
            document.body.style.overflow = '';
        }

        openModalBtn.addEventListener('click', openModal);
        if (closeModalBtn) closeModalBtn.addEventListener('click', closeModal);
        if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

        backdrop.addEventListener('click', function(event) {
            if (event.target === backdrop) closeModal();
        });

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && !backdrop.classList.contains('hidden')) {
                closeModal();
            }
        });

        @if ($errors->any())
            openModal();
        @endif

        @if (session('error'))
            openModal();
        @endif
    });
</script>
