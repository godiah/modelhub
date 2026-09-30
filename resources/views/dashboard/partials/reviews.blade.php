<section aria-labelledby="reviews-heading">
    <x-card clip>
        <div class="border-b border-neutral-100 px-5 py-4">
            <h3 id="reviews-heading" class="font-tertiary text-base font-semibold text-neutral-900">
                {{ __('Recent reviews') }}</h3>
        </div>

        <ul class="divide-y divide-neutral-100">
            @foreach ($reviews as $review)
                <li class="flex gap-4 px-5 py-4">
                    <x-user-avatar :user="$review->reviewer" size="h-10 w-10" />
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                            <p class="text-sm font-semibold text-neutral-900">{{ $review->reviewer->name }}</p>
                            <span class="flex items-center gap-0.5" aria-label="{{ __(':rating out of 5', ['rating' => $review->rating]) }}">
                                @for ($i = 1; $i <= 5; $i++)
                                    <x-icon name="star-solid"
                                        class="h-4 w-4 {{ $i <= $review->rating ? 'text-accent' : 'text-neutral-300' }}" />
                                @endfor
                            </span>
                            <span class="text-xs text-tertiary">{{ $review->created_at->diffForHumans() }}</span>
                        </div>
                        @if ($review->engagement?->job)
                            <p class="mt-0.5 truncate text-xs text-tertiary">{{ $review->engagement->job->title }}</p>
                        @endif
                        @if ($review->review)
                            <p class="mt-2 text-sm leading-relaxed text-neutral-700">{{ $review->review }}</p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    </x-card>
</section>
