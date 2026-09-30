@props(['review'])

<div
    class="bg-white rounded-xl border border-neutral-200 overflow-hidden shadow-sm hover:shadow-md transition-shadow duration-300">
    <div class="p-5">
        <!-- Header -->
        <div class="flex justify-between items-start">
            <div class="flex items-center">
                <div
                    class="h-10 w-10 rounded-full bg-primary/10 text-primary flex items-center justify-center border border-primary/20 font-medium mr-3">
                    {{ $review->reviewer->getInitials() }}
                </div>
                <div>
                    <h4 class="font-medium text-neutral-800">{{ $review->reviewer->name }}</h4>
                    <p class="text-xs text-neutral-500"><x-date :date="$review->created_at" format="M d, Y" /></p>
                </div>
            </div>
            <x-jobs.star-rating :rating="$review->rating" size="sm" :showNumber="true" />
        </div>

        <!-- Review Content -->
        <div class="mt-4">
            <p class="text-neutral-700">{{ $review->review }}</p>
        </div>

        <!-- Tags -->
        @if (count($review->tags) > 0)
            <div class="mt-4 flex flex-wrap gap-1">
                @foreach ($review->tags as $tag)
                    <span
                        class="font-tertiary font-medium px-2 py-1 bg-primary/10 text-primary text-xs rounded-full">{{ $tag }}</span>
                @endforeach
            </div>
        @endif

        <!-- Job Info -->
        <div class="mt-4 pt-4 border-t border-neutral-200">
            <div class="flex items-center text-sm text-neutral-500">
                <x-icon name="briefcase-2" class="h-4 w-4 mr-1" />
                <span>{{ $review->engagement->application->job->title }}</span>
            </div>
        </div>
    </div>
</div>

{{-- 
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach ($reviews as $review)
            <x-review-card :review="$review" />
        @endforeach
    </div>
--}}
