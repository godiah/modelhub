<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductReview;
use App\Support\Staff\ListSort;
use Illuminate\Http\Request;

/** Every model in every status. The review queue stays the work list; this is the whole catalogue. Permission: view models. */
class AdminModelDirectoryController extends Controller
{
    /** Sortable columns: sort key => the column or count alias it orders by (or a closure for ratings, which puts unrated models last). */
    public const SORTS = ['newest' => 'created_at', 'title' => 'title', 'price' => 'price_minor', 'rating' => 'rating_avg', 'saves' => 'wishlist_items_count', 'sales' => 'purchases_count'];

    public function index(Request $request)
    {
        $statuses = ['all' => 'All'] + collect(ProductStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->all();
        $status = array_key_exists($request->query('status'), $statuses) ? $request->query('status') : 'all';
        $term = trim((string) $request->query('q'));
        [$sort, $dir] = ListSort::resolve($request, array_keys(self::SORTS), default: 'newest', descFirst: ['newest', 'price', 'rating', 'saves', 'sales']);

        $models = Product::with(['seller:id,name', 'sellerProfile:id,user_id,display_name,slug', 'images', 'category:id,name'])
            ->withCount(['wishlistItems', 'purchases', 'reviews'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($term !== '', fn ($query) => $query->where(fn ($q) => $q->where('title', 'like', "%{$term}%")->orWhereHas('sellerProfile', fn ($s) => $s->where('display_name', 'like', "%{$term}%"))))
            ->tap(fn ($query) => ListSort::apply($query, $sort, $dir, self::SORTS + ['rating' => fn ($q, $d) => $q->orderByRaw('rating_avg is null')->orderBy('rating_avg', $d)->orderBy('rating_count', $d)]))
            ->paginate(12)
            ->withQueryString();

        $byStatus = Product::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.model-directory.index', [
            'models' => $models, 'statuses' => $statuses, 'status' => $status, 'sort' => $sort, 'dir' => $dir, 'term' => $term,
            'counts' => ['all' => (int) $byStatus->sum()] + collect(ProductStatus::cases())->mapWithKeys(fn ($case) => [$case->value => (int) ($byStatus[$case->value] ?? 0)])->all(),
        ]);
    }

    public function show(Product $product)
    {
        $product->load(['images', 'files', 'software', 'category.parent', 'seller:id,name,avatar', 'sellerProfile', 'reviewer:id,name']);

        return view('admin.model-directory.show', [
            'product' => $product,
            'reviews' => ProductReview::with('author:id,name')->where('product_id', $product->id)->latest()->limit(8)->get(),
            'counts' => ['saves' => $product->wishlistItems()->count(), 'purchases' => $product->purchases()->count(), 'reviews' => $product->reviews()->count(), 'hidden' => $product->reviews()->where('status', 'hidden')->count()],
        ]);
    }
}
