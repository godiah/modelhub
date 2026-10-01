<?php

namespace App\Http\Controllers;

use App\Models\SellerProfile;
use App\Services\Marketplace\ProductBrowsingService;
use Illuminate\Http\Request;

/** A seller's public page: who they are and the models they have published. Shows the store name, never the member's own name or email. */
class SellerStorefrontController extends Controller
{
    public function __construct(protected ProductBrowsingService $catalogue) {}

    public function show(Request $request, SellerProfile $seller)
    {
        abort_unless($seller->isApproved(), 404);

        $filters = $request->validate([
            'category' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', 'in:newest,price_low,price_high'],
        ]);

        $seller->load('user.profile');

        $products = $this->catalogue->forSeller($seller, $filters);

        return view('marketplace.sellers.show', [
            'seller' => $seller,
            'products' => $products,
            'categories' => $this->catalogue->sellerCategories($seller),
            'filters' => $filters,
            'total' => $seller->user->products()->published()->count(),
            'savedIds' => $this->catalogue->savedIds($request->user(), $products),
        ]);
    }
}
