@props(['product', 'reviews', 'distribution', 'myReview', 'canReview', 'isOwner'])

{{--
    Ratings and reviews on a model page: the average with a per-star breakdown, the review form for a verified
    buyer, and the visible reviews with the seller's reply. Authors edit or delete their own; others can report.
--}}
@php
    $total = array_sum($distribution);
    $viewer = auth()->user();
    $reportReasons = \App\Models\ProductReview::REPORT_REASONS;
    $fieldClass = 'block w-full rounded-xl border border-neutral-300 px-3 py-2 text-sm focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25';
@endphp
<x-panel id="reviews" :title="__('Ratings & reviews')" class="scroll-mt-24">
    @if ($total === 0)
        <p class="text-sm text-tertiary">{{ __('No reviews yet.') }} {{ $canReview ? __('You bought this model, so you can be the first to review it.') : __('Buyers can review a model once they have bought it.') }}</p>
    @else
        <div class="flex flex-col gap-6 sm:flex-row sm:items-center">
            <div class="shrink-0 text-center sm:w-36">
                <p class="font-tertiary text-5xl font-bold tabular-nums text-neutral-900">{{ number_format((float) $product->rating_avg, 1) }}</p>
                <x-models.stars :rating="$product->rating_avg" size="h-5 w-5" class="mt-1" />
                <p class="mt-1 text-xs text-tertiary">{{ trans_choice(':count review|:count reviews', $total, ['count' => $total]) }}</p>
            </div>
            <ul class="min-w-0 flex-1 space-y-1.5" aria-label="{{ __('Reviews by star rating') }}">
                @foreach ($distribution as $stars => $count)
                    <li class="flex items-center gap-3 text-xs">
                        <span class="w-10 shrink-0 tabular-nums text-neutral-700">{{ $stars }} {{ __('stars') }}</span>
                        <span class="h-2 min-w-0 flex-1 overflow-hidden rounded-full bg-neutral-100"><span class="block h-full rounded-full bg-amber-400" style="width: {{ $total ? round($count / $total * 100) : 0 }}%"></span></span>
                        <span class="w-6 shrink-0 text-right tabular-nums text-tertiary">{{ $count }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Write a review (verified buyers) --}}
    @if ($canReview)
        <form method="POST" action="{{ route('models.reviews.store', $product) }}" x-data="{ rating: {{ (int) old('rating', 0) }}, hover: 0 }" class="mt-6 rounded-xl border border-neutral-200 bg-neutral-50/60 p-4 sm:p-5">
            @csrf
            <h3 class="font-tertiary text-base font-semibold text-neutral-900">{{ __('Review this model') }}</h3>
            <fieldset class="mt-3">
                <legend class="text-sm text-neutral-700">{{ __('Your rating') }}</legend>
                <div class="mt-1.5 flex items-center gap-1" @mouseleave="hover = 0">
                    @foreach (range(1, 5) as $value)
                        <label class="cursor-pointer rounded p-0.5 focus-within:ring-2 focus-within:ring-secondary/50" @mouseenter="hover = {{ $value }}">
                            <input type="radio" name="rating" value="{{ $value }}" class="sr-only" x-model.number="rating" required>
                            <span class="sr-only">{{ trans_choice(':count star|:count stars', $value, ['count' => $value]) }}</span>
                            <x-icon name="star-solid" class="h-8 w-8 transition-colors" x-bind:class="(hover || rating) >= {{ $value }} ? 'text-amber-400' : 'text-neutral-300'" />
                        </label>
                    @endforeach
                </div>
                @error('rating')<p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>@enderror
            </fieldset>
            <div class="mt-4">
                <label for="review-comment" class="text-sm text-neutral-700">{{ __('Your review') }}</label>
                <textarea id="review-comment" name="comment" rows="4" maxlength="2000" required placeholder="{{ __('How was the quality, the topology, the textures? Did it work in your software?') }}" class="mt-1.5 {{ $fieldClass }}">{{ old('comment') }}</textarea>
                @error('comment')<p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>@enderror
            </div>
            <x-btn type="submit" class="mt-4">{{ __('Post review') }}</x-btn>
        </form>
    @elseif ($viewer && ! $isOwner && ! $myReview && $total > 0)
        <p class="mt-5 rounded-xl bg-neutral-50 px-4 py-3 text-xs text-tertiary">{{ __('Only buyers of this model can review it.') }}</p>
    @elseif (! $viewer)
        <p class="mt-5 rounded-xl bg-neutral-50 px-4 py-3 text-sm text-neutral-700"><a href="{{ route('login') }}" class="font-medium text-teal-700 underline">{{ __('Sign in') }}</a> {{ __('to review this model if you have bought it.') }}</p>
    @endif

    {{-- The reviews --}}
    @if ($reviews->isNotEmpty())
        <ul class="mt-6 divide-y divide-neutral-100 border-t border-neutral-100">
            @foreach ($reviews as $review)
                @php $mine = $viewer && $review->user_id === $viewer->id; @endphp
                <li id="review-{{ $review->id }}" class="scroll-mt-24 py-5" x-data="{ editing: false, replying: false, reporting: false, deleting: false, removingReply: false, reason: 'spam', details: '' }">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                <x-models.stars :rating="$review->rating" />
                                <span class="text-sm font-medium text-neutral-900">{{ $review->author->publicName() }}</span>
                                @if ($review->purchase_id)<x-badge tone="green" class="px-2 py-0.5 text-[11px] font-medium">{{ __('Verified buyer') }}</x-badge>@endif
                                @if ($mine)<x-badge tone="neutral" class="px-2 py-0.5 text-[11px] font-medium">{{ __('Your review') }}</x-badge>@endif
                            </div>
                            <p class="mt-0.5 text-xs text-tertiary">{{ $review->created_at->format('M j, Y') }}@if ($review->edited_at) · {{ __('edited') }}@endif</p>
                        </div>
                        <div class="flex shrink-0 items-center gap-1 text-xs">
                            @if ($mine)
                                <button type="button" @click="editing = ! editing" class="rounded px-2 py-1 font-medium text-neutral-600 hover:bg-neutral-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">{{ __('Edit') }}</button>
                                <button type="button" @click="deleting = true" class="rounded px-2 py-1 font-medium text-red-600 hover:bg-red-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">{{ __('Delete') }}</button>
                            @elseif ($viewer && ! $isOwner)
                                <button type="button" @click="reporting = true" class="inline-flex items-center gap-1 rounded px-2 py-1 text-tertiary hover:bg-neutral-100 hover:text-neutral-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40"><x-icon name="flag" class="h-3.5 w-3.5" />{{ __('Report') }}</button>
                            @endif
                        </div>
                    </div>

                    <p x-show="! editing" class="mt-3 whitespace-pre-line break-words text-sm text-neutral-800">{{ $review->comment }}</p>

                    @if ($mine)
                        <form x-show="editing" x-cloak method="POST" action="{{ route('reviews.update', $review) }}" x-data="{ rating: {{ $review->rating }}, hover: 0 }" class="mt-3">
                            @csrf @method('PATCH')
                            <div class="flex items-center gap-1" @mouseleave="hover = 0">
                                @foreach (range(1, 5) as $value)
                                    <label class="cursor-pointer rounded p-0.5 focus-within:ring-2 focus-within:ring-secondary/50" @mouseenter="hover = {{ $value }}">
                                        <input type="radio" name="rating" value="{{ $value }}" class="sr-only" x-model.number="rating" required>
                                        <span class="sr-only">{{ trans_choice(':count star|:count stars', $value, ['count' => $value]) }}</span>
                                        <x-icon name="star-solid" class="h-7 w-7" x-bind:class="(hover || rating) >= {{ $value }} ? 'text-amber-400' : 'text-neutral-300'" />
                                    </label>
                                @endforeach
                            </div>
                            <label for="edit-comment-{{ $review->id }}" class="sr-only">{{ __('Your review') }}</label>
                            <textarea id="edit-comment-{{ $review->id }}" name="comment" rows="4" maxlength="2000" required class="mt-3 {{ $fieldClass }}">{{ $review->comment }}</textarea>
                            <div class="mt-3 flex gap-2"><x-btn type="submit" size="sm">{{ __('Save changes') }}</x-btn><x-btn type="button" size="sm" variant="secondary" @click="editing = false">{{ __('Cancel') }}</x-btn></div>
                        </form>
                    @endif

                    @if (filled($review->seller_reply))
                        <div class="mt-4 rounded-xl border-l-4 border-teal-600 bg-teal-50/60 px-4 py-3">
                            <div class="flex items-start justify-between gap-3">
                                <p class="text-xs font-semibold text-teal-900">{{ __('Reply from :store', ['store' => $product->sellerProfile?->display_name ?? __('the seller')]) }}
                                    <span class="font-normal text-tertiary">· {{ $review->seller_replied_at?->format('M j, Y') }}</span></p>
                                @if ($isOwner)
                                    <span class="flex shrink-0 gap-1 text-xs">
                                        <button type="button" @click="replying = true" class="rounded px-2 py-0.5 font-medium text-neutral-600 hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">{{ __('Edit') }}</button>
                                        <button type="button" @click="removingReply = true" class="rounded px-2 py-0.5 font-medium text-red-600 hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">{{ __('Remove') }}</button>
                                    </span>
                                @endif
                            </div>
                            <p class="mt-1.5 whitespace-pre-line break-words text-sm text-neutral-800">{{ $review->seller_reply }}</p>
                        </div>
                    @elseif ($isOwner)
                        <button type="button" @click="replying = true" class="mt-3 inline-flex items-center gap-1.5 rounded-lg border border-neutral-300 px-3 py-1.5 text-xs font-medium text-neutral-700 hover:bg-neutral-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">{{ __('Reply publicly') }}</button>
                    @endif

                    @if ($isOwner)
                        <x-confirm-dialog bind="replying" max-width="2xl" title="Reply to this review" icon="chat-bubble-text" tone="primary" confirm-label="Post reply" :action="route('reviews.reply', $review)"
                            message="Your reply is public and shown under the review. You can edit or remove it later." :state="'reply: '.json_encode($review->seller_reply ?? '')" disabledWhen="reply.trim().length < 2">
                            <label for="reply-{{ $review->id }}" class="sr-only">{{ __('Your reply') }}</label>
                            <textarea id="reply-{{ $review->id }}" name="reply" x-model="reply" rows="6" maxlength="1000" required class="mt-3 {{ $fieldClass }}"></textarea>
                        </x-confirm-dialog>
                        <x-confirm-dialog bind="removingReply" title="Remove your reply" confirm-label="Remove" method="DELETE" :action="route('reviews.reply.destroy', $review)" message="The reply disappears from the review." />
                    @endif
                    @if ($mine)
                        <x-confirm-dialog bind="deleting" title="Delete your review" confirm-label="Delete" method="DELETE" :action="route('reviews.destroy', $review)" message="It is removed for good and no longer counts towards the rating. You can write a new one afterwards." />
                    @elseif ($viewer && ! $isOwner)
                        <x-confirm-dialog bind="reporting" max-width="xl" title="Report this review" icon="flag" confirm-label="Send report" :action="route('reviews.report', $review)" message="A reviewer will check it against the review rules. The author is not told who reported it.">
                            <fieldset class="mt-3">
                                <legend class="sr-only">{{ __('Why are you reporting it?') }}</legend>
                                @foreach ($reportReasons as $key => $label)
                                    <label class="flex cursor-pointer items-center gap-2 py-1 text-sm text-neutral-800"><input type="radio" name="reason" value="{{ $key }}" x-model="reason" class="text-teal-600 focus:ring-secondary/40"> {{ __($label) }}</label>
                                @endforeach
                            </fieldset>
                            <label for="report-details-{{ $review->id }}" class="sr-only">{{ __('Details (optional)') }}</label>
                            <textarea id="report-details-{{ $review->id }}" name="details" x-model="details" rows="2" maxlength="500" placeholder="{{ __('Details (optional)') }}" class="mt-2 {{ $fieldClass }}"></textarea>
                        </x-confirm-dialog>
                    @endif
                </li>
            @endforeach
        </ul>
        <x-pager :paginator="$reviews" />
    @endif
</x-panel>
