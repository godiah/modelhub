<?php

namespace App\Services\Marketplace;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\Staff;
use App\Notifications\ProductReviewedNotification;
use Illuminate\Support\Facades\DB;

/** A reviewer's decision on a model listing. */
class ProductReviewService
{
    /**
     * publish: from in review; reject: from in review (asks for changes); takedown: from published.
     * Returns why it is not allowed, or null once done.
     */
    public function review(Product $product, Staff $reviewer, string $decision, ?string $notes): ?string
    {
        $outcome = null;

        $error = DB::transaction(function () use ($product, $reviewer, $decision, $notes, &$outcome) {
            $current = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();

            $target = match ($decision) {
                'publish' => $current->status === ProductStatus::InReview ? ProductStatus::Published : null,
                'reject' => $current->status === ProductStatus::InReview ? ProductStatus::Rejected : null,
                'takedown' => $current->status === ProductStatus::Published ? ProductStatus::Unpublished : null,
                default => null,
            };

            if (! $target) {
                return "This model is {$current->status->label()}, so that action is not available.";
            }

            if ($target !== ProductStatus::Published && blank($notes)) {
                return 'Give a reason: the seller will be shown it.';
            }

            $current->update([
                'status' => $target,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_notes' => $target === ProductStatus::Published ? null : trim($notes),
                'published_at' => $target === ProductStatus::Published ? now() : $current->published_at,
            ]);
            $outcome = $current;

            return null;
        });

        if ($error) {
            return $error;
        }

        $outcome->load('seller');
        $outcome->seller->notify(new ProductReviewedNotification($outcome));

        return null;
    }

    public function counts(): array
    {
        $byStatus = Product::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return ['all' => (int) $byStatus->sum()] + collect(ProductStatus::cases())->mapWithKeys(fn ($case) => [$case->value => (int) ($byStatus[$case->value] ?? 0)])->all();
    }
}
