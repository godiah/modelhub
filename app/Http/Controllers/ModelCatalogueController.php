<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\Marketplace\ProductBrowsingService;
use App\Services\Marketplace\ProductRatingService;
use Illuminate\Http\Request;

/** The public models catalogue: browse published models and open one. */
class ModelCatalogueController extends Controller
{
    public function __construct(protected ProductBrowsingService $catalogue, protected ProductRatingService $ratings) {}

    public function index(Request $request)
    {
        $filters = $request->validate([
            'category' => ['nullable', 'string', 'max:100'],
            'q' => ['nullable', 'string', 'max:100'],
            'format' => ['nullable', 'string', 'max:12'],
            'software' => ['nullable', 'integer'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'in:'.implode(',', array_keys(ProductBrowsingService::FEATURES))],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'sort' => ['nullable', 'in:newest,price_low,price_high,top_rated'],
        ]);

        $products = $this->catalogue->browse($filters);

        return view('marketplace.catalogue.index', [
            'products' => $products,
            'filters' => $filters,
            'chips' => $this->catalogue->categoryChips(),
            'category' => $this->catalogue->category($filters['category'] ?? null),
            'formats' => $this->catalogue->formats(),
            'software' => $this->catalogue->software(),
            'savedIds' => $this->catalogue->savedIds($request->user(), $products),
        ]);
    }

    public function show(Request $request, Product $product)
    {
        // Only published models are public; the owner and reviewers can preview their own state.
        $viewer = $request->user();
        $canPreview = $viewer && ($viewer->id === $product->user_id || $viewer->can('review models'));
        $isLive = Product::published()->whereKey($product->id)->exists();
        abort_unless($isLive || $canPreview, 404);

        $product->load(['images', 'files', 'software', 'category.parent', 'sellerProfile']);

        // The viewer's own review is pinned to the top of the list, then the newest.
        $reviews = $product->reviews()->visible()->with('author')
            ->orderByRaw('user_id = ? desc', [$viewer?->id ?? 0])->latest()->paginate(10, ['*'], 'reviews')->withQueryString();

        return view('marketplace.catalogue.show', [
            'product' => $product,
            'reviews' => $reviews,
            'distribution' => $this->ratings->distribution($product),
            'myReview' => $viewer ? $product->reviews()->where('user_id', $viewer->id)->first() : null,
            'canReview' => $isLive && $product->canBeReviewedBy($viewer),
            'preview' => ! $isLive,
            'related' => $related = Product::published()->with(['images', 'sellerProfile', 'files'])
                ->where('category_id', $product->category_id)->whereKeyNot($product->id)->latest('published_at')->limit(4)->get(),
            'savedIds' => $this->catalogue->savedIds($viewer, $related->push($product)),
            'saves' => $product->wishlistItems()->count(),
        ]);
    }
}
