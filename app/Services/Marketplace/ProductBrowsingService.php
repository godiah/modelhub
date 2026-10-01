<?php

namespace App\Services\Marketplace;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductFile;
use App\Models\SellerProfile;
use App\Models\Software;
use App\Models\User;
use App\Models\WishlistItem;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;

/** The public catalogue: published models, filtered the way buyers think about them (category, format, features, price). */
class ProductBrowsingService
{
    public const PER_PAGE = 24;

    /** Quick toggles in the filter bar: query value => column. */
    public const FEATURES = [
        'free' => null,
        'animated' => 'is_animated',
        'pbr' => 'is_pbr',
        'rigged' => 'is_rigged',
        'low_poly' => 'is_low_poly',
        'print' => 'is_print_ready',
        'textures' => 'has_textures',
        'vr' => 'is_vr_ready',
    ];

    public function browse(array $filters): LengthAwarePaginator
    {
        $query = Product::published()->with(['images', 'sellerProfile', 'category.parent', 'files']);

        if ($category = $this->category($filters['category'] ?? null)) {
            $ids = $category->parent_id ? [$category->id] : $category->children()->pluck('id')->push($category->id)->all();
            $query->whereIn('category_id', $ids);
        }

        if (filled($filters['q'] ?? null)) {
            $term = '%'.addcslashes(trim($filters['q']), '%_\\').'%';
            $query->where(fn ($q) => $q->where('title', 'like', $term)->orWhere('description', 'like', $term)->orWhere('tags', 'like', $term));
        }

        if (filled($filters['format'] ?? null)) {
            $query->whereHas('files', fn ($q) => $q->where('extension', strtolower($filters['format']))->whereIn('kind', ['native', 'exchange']));
        }

        if (filled($filters['software'] ?? null)) {
            $query->whereHas('software', fn ($q) => $q->where('software.id', (int) $filters['software']));
        }

        foreach ((array) ($filters['features'] ?? []) as $feature) {
            if ($feature === 'free') {
                $query->where('price_minor', 0);
            } elseif ($column = self::FEATURES[$feature] ?? null) {
                $query->where($column, true);
            }
        }

        if (filled($filters['min_price'] ?? null)) {
            $query->where('price_minor', '>=', (int) round($filters['min_price'] * 100));
        }
        if (filled($filters['max_price'] ?? null)) {
            $query->where('price_minor', '<=', (int) round($filters['max_price'] * 100));
        }

        match ($filters['sort'] ?? 'newest') {
            'price_low' => $query->orderBy('price_minor')->latest('published_at'),
            'price_high' => $query->orderByDesc('price_minor')->latest('published_at'),
            'top_rated' => $query->orderByRaw('rating_avg is null')->orderByDesc('rating_avg')->orderByDesc('rating_count')->latest('published_at'),
            default => $query->latest('published_at'),
        };

        return $query->paginate(self::PER_PAGE)->withQueryString();
    }

    /** Top-level categories with how many published models each holds, for the category chips. */
    public function categoryChips()
    {
        $counts = Product::published()->selectRaw('category_id, count(*) as total')->groupBy('category_id')->pluck('total', 'category_id');

        return Category::active()->topLevel()->with(['children' => fn ($q) => $q->active()])->get()->map(function (Category $top) use ($counts) {
            $top->published_total = (int) $top->children->sum(fn ($child) => $counts[$child->id] ?? 0) + (int) ($counts[$top->id] ?? 0);
            $top->children->each(fn ($child) => $child->published_total = (int) ($counts[$child->id] ?? 0));

            return $top;
        });
    }

    /** File formats actually present on published models, for the format dropdown. */
    public function formats(): array
    {
        return ProductFile::query()
            ->whereIn('kind', ['native', 'exchange'])
            ->whereIn('product_id', Product::published()->select('id'))
            ->distinct()->orderBy('extension')->pluck('extension')->all();
    }

    public function software()
    {
        return Software::active()->whereIn('id', \DB::table('product_software')->whereIn('product_id', Product::published()->select('id'))->select('software_id'))->orderBy('name')->get(['id', 'name']);
    }

    public function category(?string $slug): ?Category
    {
        return filled($slug) ? Category::active()->with('parent')->where('slug', $slug)->first() : null;
    }

    /** A seller's published models, optionally narrowed to one category (a top-level one includes its sub-categories). */
    public function forSeller(SellerProfile $seller, array $filters): LengthAwarePaginator
    {
        $query = Product::published()->where('user_id', $seller->user_id)->with(['images', 'sellerProfile', 'category.parent', 'files']);

        if ($category = $this->category($filters['category'] ?? null)) {
            $ids = $category->parent_id ? [$category->id] : $category->children()->pluck('id')->push($category->id)->all();
            $query->whereIn('category_id', $ids);
        }

        match ($filters['sort'] ?? 'newest') {
            'price_low' => $query->orderBy('price_minor')->latest('published_at'),
            'price_high' => $query->orderByDesc('price_minor')->latest('published_at'),
            'top_rated' => $query->orderByRaw('rating_avg is null')->orderByDesc('rating_avg')->orderByDesc('rating_count')->latest('published_at'),
            default => $query->latest('published_at'),
        };

        return $query->paginate(self::PER_PAGE)->withQueryString();
    }

    /** The top-level categories a seller has published in, with counts, for their storefront's filter chips. */
    public function sellerCategories(SellerProfile $seller)
    {
        $counts = Product::published()->where('user_id', $seller->user_id)->selectRaw('category_id, count(*) as total')->groupBy('category_id')->pluck('total', 'category_id');

        return Category::active()->topLevel()->with(['children' => fn ($q) => $q->active()])->get()
            ->map(function (Category $top) use ($counts) {
                $top->published_total = (int) $top->children->sum(fn ($child) => $counts[$child->id] ?? 0) + (int) ($counts[$top->id] ?? 0);

                return $top;
            })
            ->filter(fn ($top) => $top->published_total > 0)
            ->values();
    }

    /** Which of these models the member has saved (product ids), so cards can show a filled heart. */
    public function savedIds(?User $user, iterable $products): array
    {
        if (! $user) {
            return [];
        }

        $ids = collect($products instanceof Paginator ? $products->items() : $products)->pluck('id')->all();

        return $ids === [] ? [] : WishlistItem::where('user_id', $user->id)->whereIn('product_id', $ids)->pluck('product_id')->all();
    }
}
