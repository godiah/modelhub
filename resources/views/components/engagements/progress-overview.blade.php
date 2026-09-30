@props(['engagement'])

@if ($engagement->deliverables->isNotEmpty())
    <x-card rounded="lg" class="mt-8 p-5">
        <h5 class="font-tertiary font-medium text-neutral-800 mb-4 flex items-center">
            <x-icon name="chart-bar" class="h-5 w-5 mr-2 text-primary" />
            Project Progress
        </h5>

        <div class="flex flex-col md:flex-row gap-6">
            <!-- Progress Bar -->
            <div class="flex-1">
                <div class="flex justify-between text-sm mb-1 font-main">
                    <span
                        class="font-medium text-neutral-700">{{ round($engagement->completionPercentage()) }}%
                        Complete</span>
                    <span class="text-neutral-500">
                        {{ $engagement->deliverables->where('status', 'approved')->count() }}/{{ $engagement->deliverables->count() }}
                        deliverables
                    </span>
                </div>
                <div class="h-2 bg-neutral-200 rounded-full overflow-hidden">
                    <div class="h-full bg-secondary rounded-full"
                        style="width: {{ $engagement->completionPercentage() }}%">
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="flex divide-x divide-neutral-200 font-main">
                <div class="px-4 first:pl-0 last:pr-0">
                    <div class="text-xs text-neutral-500 mb-1">Pending</div>
                    <div class="text-lg text-center font-medium text-neutral-800">
                        {{ $engagement->deliverables->where('status', 'pending')->count() }}
                    </div>
                </div>
                <div class="px-4">
                    <div class="text-xs text-neutral-500 mb-1">Submitted</div>
                    <div class="text-lg text-center font-medium text-accent">
                        {{ $engagement->deliverables->where('status', 'submitted')->count() }}
                    </div>
                </div>
                <div class="px-4">
                    <div class="text-xs text-neutral-500 mb-1">Approved</div>
                    <div class="text-lg text-center font-medium text-secondary">
                        {{ $engagement->deliverables->where('status', 'approved')->count() }}
                    </div>
                </div>
                <div class="px-4 last:pr-0">
                    <div class="text-xs text-neutral-500 mb-1">Rejected</div>
                    <div class="text-lg text-center font-medium text-red-800">
                        {{ $engagement->deliverables->where('status', 'rejected')->count() }}
                    </div>
                </div>
            </div>
        </div>
    </x-card>
@endif
