<?php

namespace App\Services\Dashboard;

use App\Enums\ProductStatus;
use App\Enums\SellerStatus;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\SellerProfile;
use App\Models\User;
use App\Models\WishlistItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * The models side of the signed-in dashboard: what an approved seller has listed and what buyers said about it,
 * plus things waiting on the member (a listing that needs changes, a review to reply to, a seller application that
 * was turned down). Everything is eager-loaded: lazy loading is blocked outside production.
 */
class ModelsDashboardService
{
    /** Reviews younger than this many days that still have no reply are surfaced as needing attention. */
    private const REPLY_WINDOW_DAYS = 30;

    /** Attention priorities, on the same scale as the job-board items (lower comes first). */
    private const PRIORITY_TAKEN_DOWN = 18;

    private const PRIORITY_NEEDS_CHANGES = 22;

    private const PRIORITY_APPLICATION = 28;

    private const PRIORITY_REPLY = 45;

    /**
     * @return array{seller: bool, store: ?SellerProfile, summary: ?array<string, mixed>, attention: list<array<string, mixed>>, models: Collection<int, Product>, reviews: Collection<int, ProductReview>}
     */
    public function for(User $user): array
    {
        $none = ['seller' => false, 'store' => null, 'summary' => null, 'attention' => [], 'models' => collect(), 'reviews' => collect()];
        $store = SellerProfile::where('user_id', $user->id)->first();

        if (! $store) {
            return $none;
        }

        if ($store->status !== SellerStatus::Approved) {
            return ['attention' => $this->applicationItems($store)] + $none;
        }

        $products = Product::where('user_id', $user->id);

        return [
            'seller' => true,
            'store' => $store,
            'summary' => $this->summary($user, $store),
            'attention' => [...$this->listingItems($user), ...$this->replyItems($user)],
            'models' => (clone $products)->with('images')->withCount('wishlistItems')->latest('updated_at')->limit(4)->get(),
            'reviews' => ProductReview::visible()
                ->with(['author:id,name', 'product:id,title,slug,user_id'])
                ->whereHas('product', fn ($query) => $query->where('user_id', $user->id))
                ->latest()->limit(3)->get(),
        ];
    }

    /**
     * @return array{live: int, in_review: int, needs_changes: int, saves: int, rating: ?float, rating_count: int, rating_public: bool}
     */
    private function summary(User $user, SellerProfile $store): array
    {
        $byStatus = Product::where('user_id', $user->id)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return [
            'live' => (int) ($byStatus[ProductStatus::Published->value] ?? 0),
            'in_review' => (int) ($byStatus[ProductStatus::InReview->value] ?? 0),
            'needs_changes' => (int) ($byStatus[ProductStatus::Rejected->value] ?? 0) + (int) ($byStatus[ProductStatus::Unpublished->value] ?? 0),
            'saves' => WishlistItem::whereHas('product', fn ($query) => $query->where('user_id', $user->id))->count(),
            'rating' => $store->rating_avg,
            'rating_count' => $store->rating_count,
            'rating_public' => $store->hasPublicRating(),
        ];
    }

    /**
     * Listings a reviewer sent back or took down, one row each so the seller sees the reason.
     *
     * @return list<array<string, mixed>>
     */
    private function listingItems(User $user): array
    {
        return Product::where('user_id', $user->id)
            ->whereIn('status', [ProductStatus::Rejected, ProductStatus::Unpublished])
            ->latest('reviewed_at')
            ->limit(5)
            ->get()
            ->map(function (Product $product) {
                $takenDown = $product->status === ProductStatus::Unpublished;

                return $this->item(
                    $takenDown ? 'exclamation-triangle' : 'pencil-square',
                    'red',
                    $takenDown ? __('Model taken down: :title', ['title' => $product->title]) : __('Model needs changes: :title', ['title' => $product->title]),
                    filled($product->review_notes)
                        ? Str::limit($product->review_notes, 90)
                        : ($takenDown ? __('Open the listing to see why.') : __('Open the listing to see what a reviewer asked for.')),
                    route('seller.models.edit', $product),
                    $takenDown ? __('Open') : __('Update'),
                    $takenDown ? self::PRIORITY_TAKEN_DOWN : self::PRIORITY_NEEDS_CHANGES,
                );
            })
            ->all();
    }

    /**
     * Recent reviews of the seller's models that have no reply yet, one row per model.
     *
     * @return list<array<string, mixed>>
     */
    private function replyItems(User $user): array
    {
        return ProductReview::visible()
            ->whereNull('seller_reply')
            ->where('created_at', '>=', now()->subDays(self::REPLY_WINDOW_DAYS))
            ->whereHas('product', fn ($query) => $query->where('user_id', $user->id))
            ->with('product:id,title,slug,user_id')
            ->latest()
            ->get()
            ->groupBy('product_id')
            ->take(4)
            ->map(function (Collection $reviews) {
                $product = $reviews->first()->product;

                return $this->item(
                    'chat-bubble-left-right',
                    'blue',
                    trans_choice('Reply to :count new review|Reply to :count new reviews', $reviews->count(), ['count' => $reviews->count()]),
                    $product->title,
                    route('models.show', $product).'#review-'.$reviews->first()->id,
                    __('Reply'),
                    self::PRIORITY_REPLY,
                );
            })
            ->values()
            ->all();
    }

    /**
     * A seller application that was turned down or suspended: the member should know and can act on it.
     *
     * @return list<array<string, mixed>>
     */
    private function applicationItems(SellerProfile $store): array
    {
        return match ($store->status) {
            SellerStatus::Rejected => [$this->item(
                'document-text', 'amber', __('Your seller application needs changes'),
                filled($store->review_notes) ? Str::limit($store->review_notes, 90) : __('Open it to see what a reviewer asked for.'),
                route('seller.index'), __('Review'), self::PRIORITY_APPLICATION,
            )],
            SellerStatus::Suspended => [$this->item(
                'shield-check', 'red', __('Your store is suspended'),
                filled($store->review_notes) ? Str::limit($store->review_notes, 90) : __('Your models are hidden while it is suspended.'),
                route('seller.index'), __('View'), self::PRIORITY_APPLICATION,
            )],
            default => [],
        };
    }

    /**
     * @return array{icon: string, tone: string, title: string, detail: string, url: string, cta: string, priority: int}
     */
    private function item(string $icon, string $tone, string $title, string $detail, string $url, string $cta, int $priority): array
    {
        return compact('icon', 'tone', 'title', 'detail', 'url', 'cta', 'priority');
    }
}
