<?php

namespace App\Http\Controllers;

use App\Helpers\FlashAlertHelper;
use App\Models\Product;
use App\Models\WishlistItem;
use App\Services\Marketplace\ProductBrowsingService;
use Illuminate\Http\Request;

/** A member's saved models: toggle one from a card or model page, and see them all together. */
class WishlistController extends Controller
{
    public function index(Request $request, ProductBrowsingService $catalogue)
    {
        $items = WishlistItem::where('user_id', $request->user()->id)
            ->whereHas('product', fn ($product) => $product->published())
            ->with(['product' => fn ($product) => $product->with(['images', 'sellerProfile', 'files'])])
            ->latest()
            ->paginate(24);

        return view('marketplace.wishlist.index', ['items' => $items]);
    }

    /** Save the model, or take it off the wishlist if it is already there. Answers JSON for the heart buttons. */
    public function toggle(Request $request, Product $product)
    {
        abort_unless(Product::published()->whereKey($product->id)->exists(), 404);

        $existing = WishlistItem::where('user_id', $request->user()->id)->where('product_id', $product->id)->first();
        $saved = ! $existing;

        $existing ? $existing->delete() : WishlistItem::create(['user_id' => $request->user()->id, 'product_id' => $product->id]);

        if ($request->expectsJson()) {
            return response()->json(['saved' => $saved, 'count' => $product->wishlistItems()->count()]);
        }

        return back()->with(FlashAlertHelper::success($saved ? 'Saved to your wishlist' : 'Removed from your wishlist'));
    }
}
