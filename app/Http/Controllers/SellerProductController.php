<?php

namespace App\Http\Controllers;

use App\Enums\ProductStatus;
use App\Helpers\FlashAlertHelper;
use App\Http\Requests\Marketplace\StoreProductRequest;
use App\Http\Requests\Marketplace\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Software;
use App\Services\Marketplace\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/** An approved seller's own model listings: list, create a draft, edit it, send it for review, take it down. */
class SellerProductController extends Controller
{
    public function __construct(protected ProductService $products) {}

    public function index(Request $request)
    {
        $status = in_array($request->query('status'), array_column(ProductStatus::cases(), 'value'), true) ? $request->query('status') : 'all';
        $search = trim((string) $request->query('search'));
        $seller = $request->user();

        $counts = $seller->products()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        $products = $seller->products()
            ->with(['category.parent', 'images'])
            ->withCount('wishlistItems')
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($search !== '', fn ($query) => $query->where('title', 'like', '%'.addcslashes($search, '%_\\').'%'))
            ->latest('updated_at')
            ->paginate(12)
            ->withQueryString();

        return view('marketplace.products.index', [
            'products' => $products,
            'status' => $status,
            'search' => $search,
            'counts' => ['all' => (int) $counts->sum()] + $counts->map(fn ($total) => (int) $total)->all(),
        ]);
    }

    public function create()
    {
        return view('marketplace.products.create', ['categories' => $this->categoryTree()]);
    }

    public function store(StoreProductRequest $request)
    {
        $product = $this->products->createDraft($request->user(), [
            'title' => $request->input('title'),
            'category_id' => $request->input('category_id'),
            'price_minor' => $request->priceMinor(),
        ]);

        return redirect()->route('seller.models.edit', $product)->with(
            FlashAlertHelper::success('Draft created', 'Add your preview images and model files, then fill in the details.')
        );
    }

    public function edit(Product $product)
    {
        Gate::authorize('manage', $product);

        $product->load(['files', 'images', 'software', 'category.parent']);

        return view('marketplace.products.edit', [
            'product' => $product,
            'categories' => $this->categoryTree(),
            'software' => Software::active()->orderBy('name')->get(['id', 'name']),
            'problems' => $product->status->isEditable() ? $product->readinessProblems() : [],
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $this->products->updateDetails($product, array_merge($request->validated(), [
            'tags' => $request->tagList(),
            'price_minor' => $request->priceMinor(),
            'extended_price_minor' => $request->extendedPriceMinor(),
        ]));

        // "Save and send for review" saves first, then submits what was saved.
        if ($request->input('then') === 'submit') {
            return $this->submit($product->fresh());
        }

        return redirect()->route('seller.models.edit', $product)->with(FlashAlertHelper::success('Saved', 'Your changes were saved.'));
    }

    public function submit(Product $product)
    {
        Gate::authorize('edit', $product);

        if ($problems = $this->products->submit($product)) {
            return redirect()->route('seller.models.edit', $product)->with(
                FlashAlertHelper::error('Not ready yet', implode(' ', $problems))
            );
        }

        return redirect()->route('seller.models.index')->with(
            FlashAlertHelper::success('Sent for review', 'A reviewer will check your model and let you know.')
        );
    }

    public function unpublish(Product $product)
    {
        Gate::authorize('manage', $product);

        if (! $this->products->unpublish($product)) {
            return back()->with(FlashAlertHelper::error('Cannot unpublish', 'Only a published model, or one waiting for review, can be taken back.'));
        }

        return redirect()->route('seller.models.edit', $product)->with(
            FlashAlertHelper::success('Unpublished', 'You can edit it now. Send it for review again when you are ready.')
        );
    }

    public function destroy(Product $product)
    {
        Gate::authorize('manage', $product);

        if (! $this->products->delete($product)) {
            return back()->with(FlashAlertHelper::error('Cannot delete', 'Unpublish a live model before deleting it.'));
        }

        return redirect()->route('seller.models.index')->with(FlashAlertHelper::success('Deleted', 'The model and its files were removed.'));
    }

    /** Top-level categories with their sub-categories, for the dependent selects. */
    protected function categoryTree()
    {
        return Category::active()->topLevel()->with(['children' => fn ($query) => $query->active()])->get();
    }
}
