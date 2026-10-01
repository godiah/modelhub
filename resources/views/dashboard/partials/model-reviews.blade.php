<section aria-labelledby="model-reviews-heading">
    <x-card clip>
        <div class="border-b border-neutral-100 px-5 py-4">
            <h3 id="model-reviews-heading" class="font-tertiary text-base font-semibold text-neutral-900">{{ __('Reviews of your models') }}</h3>
        </div>

        <ul class="divide-y divide-neutral-100">
            @foreach ($reviews as $review)
                <li class="px-5 py-4">
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                        <x-models.stars :rating="$review->rating" />
                        <span class="text-sm font-semibold text-neutral-900">{{ $review->author->publicName() }}</span>
                        <span class="text-xs text-tertiary">{{ $review->created_at->diffForHumans() }}</span>
                    </div>
                    <p class="mt-0.5 truncate text-xs text-tertiary">{{ $review->product->title }}</p>
                    <p class="mt-2 line-clamp-3 text-sm leading-relaxed text-neutral-700">{{ $review->comment }}</p>
                    <p class="mt-2 text-xs">
                        @if (filled($review->seller_reply))
                            <span class="inline-flex items-center gap-1 text-teal-700"><x-icon name="check-circle" class="h-4 w-4" />{{ __('You replied') }}</span>
                        @else
                            <a href="{{ route('models.show', $review->product) }}#review-{{ $review->id }}" class="font-medium text-teal-700 hover:text-teal-800 hover:underline">{{ __('Reply') }}</a>
                        @endif
                    </p>
                </li>
            @endforeach
        </ul>
    </x-card>
</section>
