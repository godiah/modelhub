<?php

namespace App\Services\Marketplace;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ReviewReport;
use App\Models\SellerProfile;
use App\Models\User;
use App\Notifications\ModelReviewedNotification;
use App\Notifications\ModelReviewHiddenNotification;
use App\Notifications\ModelReviewReplyNotification;
use Illuminate\Support\Facades\DB;

/** Ratings and reviews of models: writing one, the seller's reply, reports and a reviewer's moderation. */
class ProductRatingService
{
    /** A verified buyer reviews a model. Returns the review, or why it cannot be written. */
    public function review(Product $product, User $author, int $rating, string $comment): ProductReview|string
    {
        if (! $product->canBeReviewedBy($author)) {
            return 'Only people who bought this model can review it, and only once.';
        }

        $review = DB::transaction(function () use ($product, $author, $rating, $comment) {
            $purchase = $product->purchases()->completed()->where('user_id', $author->id)->first();

            $review = ProductReview::create([
                'product_id' => $product->id,
                'user_id' => $author->id,
                'purchase_id' => $purchase?->id,
                'rating' => $rating,
                'comment' => trim($comment),
                'status' => 'visible',
            ]);

            $this->recompute($product);

            return $review;
        });

        $product->loadMissing('seller');
        $product->seller->notify(new ModelReviewedNotification($review->load('author'), $product));

        return $review;
    }

    public function update(ProductReview $review, int $rating, string $comment): void
    {
        $review->update(['rating' => $rating, 'comment' => trim($comment), 'edited_at' => now()]);
        $this->recompute($review->product);
    }

    public function delete(ProductReview $review): void
    {
        $product = $review->product;
        $review->delete();
        $this->recompute($product);
    }

    /** The seller of the model answers a visible review (once; saving again edits the reply). */
    public function reply(ProductReview $review, User $seller, string $reply): ?string
    {
        if ($review->product->user_id !== $seller->id || ! $seller->isApprovedSeller()) {
            return 'Only the seller of this model can reply.';
        }

        if (! $review->isVisible()) {
            return 'That review is not visible, so it cannot be replied to.';
        }

        $first = blank($review->seller_reply);
        $review->update(['seller_reply' => trim($reply), 'seller_replied_at' => now()]);

        if ($first) {
            $review->author->notify(new ModelReviewReplyNotification($review->load('product.sellerProfile')));
        }

        return null;
    }

    public function removeReply(ProductReview $review): void
    {
        $review->update(['seller_reply' => null, 'seller_replied_at' => null]);
    }

    /** Anyone but the author can report a review, once. Returns why it cannot be reported, or null. */
    public function report(ProductReview $review, User $reporter, string $reason, ?string $details): ?string
    {
        if ($review->user_id === $reporter->id) {
            return 'You cannot report your own review.';
        }

        if (! $review->isVisible()) {
            return 'That review is already hidden.';
        }

        $report = ReviewReport::firstOrCreate(
            ['review_id' => $review->id, 'user_id' => $reporter->id],
            ['reason' => $reason, 'details' => filled($details) ? trim($details) : null, 'status' => 'open'],
        );

        return $report->wasRecentlyCreated ? null : 'You have already reported this review.';
    }

    /** A reviewer hides a review (it drops out of the rating) with a reason the author is shown. */
    public function hide(ProductReview $review, User $moderator, string $reason): ?string
    {
        if (! $review->isVisible()) {
            return 'That review is already hidden.';
        }

        DB::transaction(function () use ($review, $moderator, $reason) {
            $review->update(['status' => 'hidden', 'hidden_by' => $moderator->id, 'hidden_at' => now(), 'hidden_reason' => trim($reason)]);
            $this->resolveReports($review, $moderator);
            $this->recompute($review->product);
        });

        $review->author->notify(new ModelReviewHiddenNotification($review->load('product')));

        return null;
    }

    public function restore(ProductReview $review): ?string
    {
        if ($review->isVisible()) {
            return 'That review is already visible.';
        }

        $review->update(['status' => 'visible', 'hidden_by' => null, 'hidden_at' => null, 'hidden_reason' => null]);
        $this->recompute($review->product);

        return null;
    }

    /** Close the open reports on a review without hiding it. */
    public function dismissReports(ProductReview $review, User $moderator): void
    {
        $this->resolveReports($review, $moderator);
    }

    private function resolveReports(ProductReview $review, User $moderator): void
    {
        $review->reports()->where('status', 'open')->update(['status' => 'resolved', 'resolved_by' => $moderator->id, 'resolved_at' => now()]);
    }

    /** Keep the model's average and count in step with its visible reviews. */
    public function recompute(Product $product): void
    {
        $stats = ProductReview::where('product_id', $product->id)->visible()->selectRaw('count(*) as total, avg(rating) as average')->first();

        $product->forceFill([
            'rating_count' => (int) $stats->total,
            'rating_avg' => $stats->total > 0 ? round((float) $stats->average, 2) : null,
        ])->save();

        $this->recomputeStore($product->user_id);
    }

    /**
     * Keep a store's rating in step with its reviews. It averages the reviews themselves (not each model's
     * average, so a model with one review does not weigh as much as one with forty), counting visible reviews
     * on published, not deleted models only.
     */
    public function recomputeStore(int $sellerUserId): void
    {
        $stats = ProductReview::visible()
            ->whereHas('product', fn ($query) => $query->where('user_id', $sellerUserId)->where('status', ProductStatus::Published))
            ->selectRaw('count(*) as total, avg(rating) as average')
            ->first();

        SellerProfile::where('user_id', $sellerUserId)->update([
            'rating_count' => (int) $stats->total,
            'rating_avg' => $stats->total > 0 ? round((float) $stats->average, 2) : null,
        ]);
    }

    /** How many visible reviews gave each star rating (5 down to 1), for the breakdown bars. */
    public function distribution(Product $product): array
    {
        $counts = ProductReview::where('product_id', $product->id)->visible()->selectRaw('rating, count(*) as total')->groupBy('rating')->pluck('total', 'rating');

        return collect([5, 4, 3, 2, 1])->mapWithKeys(fn ($stars) => [$stars => (int) ($counts[$stars] ?? 0)])->all();
    }
}
