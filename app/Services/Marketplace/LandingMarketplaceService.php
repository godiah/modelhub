<?php

namespace App\Services\Marketplace;

use App\Enums\SellerStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductFile;
use App\Models\ProductImage;
use App\Models\SellerProfile;
use Illuminate\Support\Collection;

/** What the public landing page shows of the models marketplace. Each part is empty when there is nothing real to show. */
class LandingMarketplaceService
{
    /** A model needs at least this many reviews to count as "top rated" on the landing page. */
    private const MIN_REVIEWS = 2;

    /**
     * Top-level categories that hold published models, with a count, most models first (for the hero's quick links).
     *
     * @return Collection<int, Category>
     */
    public function categories(int $limit = 12): Collection
    {
        // A plain collection: Eloquent's only() filters by primary key, not by the category_id keys used below
        $counts = Product::published()->selectRaw('category_id, count(*) as total')->groupBy('category_id')->get()->toBase()->pluck('total', 'category_id');

        if ($counts->isEmpty()) {
            return collect();
        }

        return Category::active()->topLevel()->with(['children' => fn ($query) => $query->active()])->get()
            ->each(fn (Category $top) => $top->published_total = (int) $counts->only($top->children->pluck('id')->push($top->id)->all())->sum())
            ->filter(fn (Category $top) => $top->published_total > 0)
            ->sortByDesc('published_total')
            ->take($limit)
            ->values();
    }

    /**
     * "Browse by type": the catalogue's own filters (free, animated, rigged, PBR, low-poly, print-ready) as tiles,
     * each with the number of published models and the newest one's preview as its cover. Types with none are left out.
     *
     * @return Collection<int, array{key: string, label: string, count: int, cover: ?ProductImage}>
     */
    public function types(): Collection
    {
        $columns = ['animated' => 'is_animated', 'rigged' => 'is_rigged', 'pbr' => 'is_pbr', 'low_poly' => 'is_low_poly', 'print' => 'is_print_ready'];
        $labels = ['free' => 'Free', 'animated' => 'Animated', 'rigged' => 'Rigged', 'pbr' => 'PBR', 'low_poly' => 'Low-poly', 'print' => '3D print ready'];
        $scope = fn ($query, string $key) => $key === 'free' ? $query->where('price_minor', 0) : $query->where($columns[$key], true);

        $used = [];

        return collect($labels)
            ->map(function (string $label, string $key) use ($scope, &$used) {
                $count = $scope(Product::published(), $key)->count();
                $cover = null;

                if ($count) {
                    // The newest model not already used as another type's cover, so neighbouring tiles differ where they can
                    $product = $scope(Product::published(), $key)->with('images')->whereNotIn('products.id', $used)->latest('published_at')->first()
                        ?? $scope(Product::published(), $key)->with('images')->latest('published_at')->first();
                    $used[] = $product?->id;
                    $cover = $product?->images->first();
                }

                return ['key' => $key, 'label' => $label, 'count' => $count, 'cover' => $cover];
            })
            ->filter(fn (array $type) => $type['count'] > 0)
            ->values();
    }

    /**
     * "Browse by format": the model and exchange file formats on published models, most used first.
     *
     * @return Collection<int, array{extension: string, count: int}>
     */
    public function formats(int $limit = 8): Collection
    {
        return ProductFile::query()
            ->whereIn('kind', ['native', 'exchange'])
            ->whereIn('product_id', Product::published()->select('products.id'))
            ->selectRaw('extension, count(distinct product_id) as total')
            ->groupBy('extension')->orderByDesc('total')->orderBy('extension')->limit($limit)->get()
            ->map(fn ($row) => ['extension' => $row->extension, 'count' => (int) $row->total])
            ->toBase();
    }

    /**
     * The best-rated published models, or the newest ones when too few have reviews yet.
     *
     * @return array{models: Collection<int, Product>, topRated: bool}
     */
    public function models(int $limit = 21): array
    {
        $with = ['images', 'sellerProfile', 'files'];

        $rated = Product::published()->with($with)->where('rating_count', '>=', self::MIN_REVIEWS)
            ->orderByDesc('rating_avg')->orderByDesc('rating_count')->latest('published_at')->limit($limit)->get();

        if ($rated->count() >= min($limit, 4)) {
            return ['models' => $rated, 'topRated' => true];
        }

        return ['models' => Product::published()->with($with)->latest('published_at')->limit($limit)->get(), 'topRated' => false];
    }

    /**
     * Stores with a public rating, best first.
     *
     * @return Collection<int, SellerProfile>
     */
    public function stores(int $limit = 4): Collection
    {
        return SellerProfile::where('status', SellerStatus::Approved)
            ->where('rating_count', '>=', config('marketplace.min_store_reviews'))
            ->whereNotNull('rating_avg')
            ->withCount(['products as models_count' => fn ($query) => $query->published()])
            ->having('models_count', '>', 0)
            ->orderByDesc('rating_avg')->orderByDesc('rating_count')
            ->limit($limit)
            ->get();
    }
}
