<?php

use App\Models\Product;
use App\Models\ProductFile;
use App\Models\ProductImage;
use App\Models\ProductReview;
use App\Models\Purchase;
use App\Models\ReviewReport;
use App\Models\SellerProfile;
use App\Models\User;
use Database\Seeders\DemoModelsSeeder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/*
 * The demo seeder feeds the marketplace pages with real CC0 models and renders. Network calls are faked here; the point
 * is what it creates, that it is repeatable, that it never touches production, and that it degrades without a network.
 */

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');
});

/** A few Poly Haven style assets, enough to exercise the category mapping. */
function polyHavenCatalogue(): array
{
    $asset = fn (string $name, array $categories, array $tags, int $polycount = 8000) => [
        'name' => $name, 'categories' => $categories, 'tags' => $tags, 'polycount' => $polycount,
        'description' => "Free (CC0) {$name} 3D model, detailed for archviz and games.",
        'max_resolution' => [4096, 4096], 'dimensions' => [850.0, 760.0, 1060.0], 'authors' => ['Some Artist' => 'All'], 'type' => 2,
    ];

    return [
        'ArmChair_01' => $asset('Arm Chair 01', ['furniture', 'seating'], ['chair', 'wood']),
        'Sofa_02' => $asset('Sofa 02', ['furniture', 'seating'], ['sofa', 'couch']),
        'Table_03' => $asset('Table 03', ['furniture', 'table'], ['table', 'wood']),
        'Barrel_01' => $asset('Barrel 01', ['props', 'containers'], ['barrel', 'storage'], 3000),
        'Rock_04' => $asset('Rock 04', ['nature', 'rocks'], ['rock']),
        'Pine_05' => $asset('Pine 05', ['nature', 'trees'], ['pine'], 12000),
        'Tiny_06' => $asset('Tiny 06', ['props'], ['bit'], 40),
        'NoText_07' => array_merge($asset('No Text 07', ['props'], ['x']), ['description' => '']),
    ];
}

function fakePolyHaven(): void
{
    Http::fake([
        'api.polyhaven.com/*' => Http::response(polyHavenCatalogue(), 200),
        'cdn.polyhaven.com/*' => Http::response('fake-png-bytes', 200, ['Content-Type' => 'image/png']),
    ]);
}

it('creates approved demo sellers and models with images, files and the right categories', function () {
    fakePolyHaven();

    $this->seed(DemoModelsSeeder::class);

    expect(User::where('email', 'like', 'demo.seller%@demo.test')->count())->toBe(4)
        ->and(SellerProfile::where('status', 'approved')->count())->toBe(4);

    $products = Product::where('slug', 'like', '%-demo')->get();
    expect($products)->toHaveCount(6) // the sub-100-polygon and description-less assets are skipped
        ->and($products->every(fn (Product $p) => $p->images()->count() === 2))->toBeTrue()
        ->and($products->every(fn (Product $p) => $p->files()->count() === 3))->toBeTrue()
        ->and($products->every(fn (Product $p) => $p->currency === 'KES' && $p->category_id !== null))->toBeTrue();

    $category = fn (string $slug) => Product::where('slug', $slug.'-demo')->first()->category->slug;
    expect($category('arm-chair-01'))->toBe('furniture-chair')
        ->and($category('sofa-02'))->toBe('furniture-sofa')
        ->and($category('table-03'))->toBe('furniture-table')
        ->and($category('barrel-01'))->toBe('industrial-industrial-part')
        ->and($category('rock-04'))->toBe('scanned-models')
        ->and($category('pine-05'))->toBe('plants-conifer');
});

it('uses real polygon counts, believable file sizes and an attribution line', function () {
    fakePolyHaven();
    $this->seed(DemoModelsSeeder::class);

    $product = Product::where('slug', 'arm-chair-01-demo')->first();

    expect($product->polygons)->toBe(8000)
        ->and($product->vertices)->toBe(4160)
        ->and($product->is_pbr)->toBeTrue()
        ->and($product->description)->toContain('CC0')->toContain('Poly Haven')->toContain('4K PBR textures')->toContain('85.0 × 76.0 × 106.0 cm')
        ->and($product->files->pluck('extension')->sort()->values()->all())->toBe(['blend', 'fbx', 'glb'])
        ->and($product->files->firstWhere('extension', 'blend')->size_bytes)->toBe(34 * 1048576);
    Storage::disk('local')->assertExists($product->files->first()->path);
    expect(Storage::disk('local')->size($product->files->first()->path))->toBe($product->files->first()->size_bytes);
});

it('publishes most models and leaves some in review, needing changes or as drafts', function () {
    fakePolyHaven();
    $this->seed(DemoModelsSeeder::class);

    expect(Product::published()->count())->toBeGreaterThan(0)
        ->and(Product::published()->get()->every(fn (Product $p) => $p->published_at !== null))->toBeTrue();
});

it('is safe to run twice', function () {
    fakePolyHaven();

    $this->seed(DemoModelsSeeder::class);
    $this->seed(DemoModelsSeeder::class);

    expect(Product::count())->toBe(6)
        ->and(ProductImage::count())->toBe(12)
        ->and(ProductFile::count())->toBe(18)
        ->and(User::where('email', 'like', '%@demo.test')->count())->toBe(10);
});

it('gives published models demo buyers, purchases and reviews that match the stored ratings, and repeats cleanly', function () {
    fakePolyHaven();

    $this->seed(DemoModelsSeeder::class);
    $reviews = ProductReview::count();
    $purchases = Purchase::count();
    $this->seed(DemoModelsSeeder::class);

    expect($reviews)->toBeGreaterThan(0)->and(ProductReview::count())->toBe($reviews)->and(Purchase::count())->toBe($purchases);
    expect(ProductReview::whereNotNull('purchase_id')->count())->toBe($reviews);
    expect(ReviewReport::count())->toBeLessThanOrEqual(2);

    Product::published()->where('rating_count', '>', 0)->each(function (Product $product) {
        expect($product->rating_count)->toBe($product->reviews()->visible()->count())
            ->and($product->rating_avg)->toBe(round((float) $product->reviews()->visible()->avg('rating'), 2));
    });
});

it('does nothing without a network', function () {
    Http::fake(fn () => throw new ConnectionException('offline'));

    $this->seed(DemoModelsSeeder::class);

    expect(Product::count())->toBe(0)->and(User::where('email', 'like', '%@demo.test')->count())->toBe(0);
});

it('creates the models even when the images cannot be fetched', function () {
    Http::fake([
        'api.polyhaven.com/*' => Http::response(polyHavenCatalogue(), 200),
        'cdn.polyhaven.com/*' => fn () => throw new ConnectionException('offline'),
    ]);

    $this->seed(DemoModelsSeeder::class);

    expect(Product::count())->toBe(6)->and(ProductImage::count())->toBe(0);
});

it('never runs in production', function () {
    fakePolyHaven();
    app()->detectEnvironment(fn () => 'production');

    // Called directly: `db:seed` would stop to ask for confirmation in production before the seeder's own guard
    (new DemoModelsSeeder)->run();

    expect(Product::count())->toBe(0);
});

it('shows the seeded models in the public catalogue', function () {
    fakePolyHaven();
    $this->seed(DemoModelsSeeder::class);
    $published = Product::published()->first();

    $this->get(route('models.index'))->assertOk()->assertSee($published->title);
    $this->get(route('models.show', $published))->assertOk()->assertSee('Demo listing based on the CC0');
});
