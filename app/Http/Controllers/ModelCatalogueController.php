<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\Marketplace\ProductBrowsingService;
use Illuminate\Http\Request;

/** The public models catalogue: browse published models and open one. */
class ModelCatalogueController extends Controller
{
    public function __construct(protected ProductBrowsingService $catalogue) {}

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
            'sort' => ['nullable', 'in:newest,price_low,price_high'],
        ]);

        return view('marketplace.catalogue.index', [
            'products' => $this->catalogue->browse($filters),
            'filters' => $filters,
            'chips' => $this->catalogue->categoryChips(),
            'category' => $this->catalogue->category($filters['category'] ?? null),
            'formats' => $this->catalogue->formats(),
            'software' => $this->catalogue->software(),
        ]);
    }

    public function show(Request $request, Product $product)
    {
        // Only published models are public; the owner and reviewers can preview their own state.
        $viewer = $request->user();
        $canPreview = $viewer && ($viewer->id === $product->user_id || $viewer->can('review models'));
        abort_unless($product->status->value === 'published' || $canPreview, 404);

        $product->load(['images', 'files', 'software', 'category.parent', 'sellerProfile']);

        return view('marketplace.catalogue.show', [
            'product' => $product,
            'preview' => $product->status->value !== 'published',
            'related' => Product::published()->with(['images', 'sellerProfile', 'files'])
                ->where('category_id', $product->category_id)->whereKeyNot($product->id)->latest('published_at')->limit(4)->get(),
        ]);
    }
}
