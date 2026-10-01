<?php

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\ModelJob;
use App\Models\Product;
use App\Models\ProductFile;
use App\Models\ProductImage;
use App\Models\SellerProfile;
use App\Models\User;

/*
 * The public landing page: honest copy (only features that exist), real open projects, working links only.
 * Every test asserts OK first so a 500 cannot satisfy loose text assertions.
 */

it('shows the landing page to guests with models, talent and selling', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Find 3D models.')
        ->assertSee('Hire 3D talent.')
        ->assertSee('Hire talent')
        ->assertSee('Find work')
        ->assertSee('How it works')
        ->assertSee('Open your own 3D model store')
        ->assertDontSee('Coming soon')
        ->assertDontSee('Soon you will be able');
});

it('links its calls to action to real routes', function () {
    $this->get('/')->assertOk()
        ->assertSee(route('jobs.create'), false)
        ->assertSee(route('jobs.browse'), false)
        ->assertSee(route('models.index'), false)
        ->assertSee(route('seller.index'), false)
        ->assertSee(route('register'), false)
        ->assertSee(route('login'), false);
});

it('makes no claims about a catalogue beyond the models that really exist', function () {
    $this->get('/')->assertOk()
        ->assertDontSee('2 million')
        ->assertDontSee('Browse Categories')
        ->assertDontSee('Subscribe to our newsletter')
        ->assertDontSee('Connect With Us');
});

it('lists the newest open projects, and only open ones', function () {
    $client = User::factory()->create();
    ModelJob::factory()->create(['user_id' => $client->id, 'title' => 'Open lobby model']);
    ModelJob::factory()->create(['user_id' => $client->id, 'title' => 'Closed villa', 'is_active' => false]);
    ModelJob::factory()->create(['user_id' => $client->id, 'title' => 'Archived tower', 'is_archived' => true]);

    $this->get('/')->assertOk()
        ->assertSee('Open projects right now')
        ->assertSee('Open lobby model')
        ->assertDontSee('Closed villa')
        ->assertDontSee('Archived tower');
});

it('leaves the open-projects section out when there are none', function () {
    $this->get('/')->assertOk()->assertDontSee('Open projects right now');
});

it('sends signed-in users to their dashboard instead', function () {
    $this->actingAs(User::factory()->create())->get('/')->assertRedirect();
});

it('has a footer with only real destinations', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain(route('jobs.browse'))->not->toContain('href="#"');
});

it('puts every guest page on the same site header and footer', function () {
    foreach ([route('jobs.browse'), route('jobs.apply', ModelJob::factory()->create()->slug)] as $url) {
        $this->get($url)->assertOk()->assertSee('Join free')->assertSee('How it works')->assertDontSee('Connect With Us');
    }
});

it('explains how it works for clients by default and for freelancers on request', function () {
    $this->get(route('jobs.index'))->assertOk()
        ->assertSee('Hire the right 3D artist for your project')
        ->assertSee('Get your project done in 3 steps')
        ->assertSee(route('jobs.create'), false);

    $this->get(route('jobs.index', ['for' => 'work']))->assertOk()
        ->assertSee('Find 3D projects that fit your skills')
        ->assertSee('Start earning in 3 steps')
        ->assertSee(route('jobs.browse'), false);

    $this->get(route('jobs.index', ['for' => 'nonsense']))->assertOk()->assertSee('Hire the right 3D artist for your project');
});

it('marks the current page in the navigation', function () {
    expect($this->get(route('jobs.browse'))->assertOk()->getContent())->toMatch('/data-active="true"\s+aria-current="page"/');
    expect($this->get(route('jobs.index'))->assertOk()->getContent())->toMatch('/data-active="true"\s+aria-current="page"/');
    $this->get('/')->assertOk()->assertSee('data-spy="#hire"', false)->assertDontSee('aria-current="page"', false);
});

it('tells guests a free account is needed to post or apply', function () {
    $this->get('/')->assertOk()->assertSee('needs a free account')->assertSee('Free account required');
});

it('shows open projects on the landing without any client or applicant details', function () {
    $client = User::factory()->create(['name' => 'Wanjiru Private']);
    ModelJob::factory()->create([
        'user_id' => $client->id, 'title' => 'Atrium render', 'budget' => 55000, 'no_deadline' => false,
        'deadline' => now()->addDays(10), 'applicants_count' => 7, 'description' => 'A bright atrium with timber details',
        'skills' => ['Rendering & Lighting'], 'software' => ['Blender'],
    ]);

    $this->get('/')->assertOk()
        ->assertSee('Atrium render')
        ->assertSee('A bright atrium with timber details')
        ->assertSee('Rendering & Lighting')
        ->assertSee('Blender')
        ->assertSee('55,000')
        ->assertSee('10 days left')
        ->assertSee('Apply')
        ->assertDontSee('Wanjiru Private')
        ->assertDontSee('7 applicants')
        ->assertDontSee('View all projects');
});

/** ---------------------------------------------------------------- models on the landing page */
function landingModel(array $overrides = [], ?User $seller = null): Product
{
    $seller ??= User::factory()->create();
    SellerProfile::where('user_id', $seller->id)->exists() || SellerProfile::factory()->approved()->create(['user_id' => $seller->id]);
    $product = Product::factory()->published()->create(array_merge(['user_id' => $seller->id, 'category_id' => Category::where('slug', 'furniture-chair')->value('id')], $overrides));
    ProductImage::create(['product_id' => $product->id, 'disk' => 'public', 'path' => "product-images/{$product->id}/c.jpg", 'position' => 1]);

    return $product;
}

it('leaves the marketplace sections out when there are no models', function () {
    $this->get('/')->assertOk()
        ->assertDontSee('Browse by type')
        ->assertDontSee('New in the catalogue')
        ->assertDontSee('Top-rated models')
        ->assertDontSee('Top-rated stores')
        ->assertSee('Find 3D models.');
});

it('shows browse by type with real counts, linking to the catalogue filters', function () {
    landingModel(['title' => 'Walking robot', 'is_animated' => true, 'is_rigged' => true]);
    landingModel(['title' => 'Metal crate', 'is_pbr' => true, 'price_minor' => 0]);
    landingModel(['title' => 'Plain chair']);
    landingModel(['title' => 'Textured lamp', 'has_textures' => true, 'is_vr_ready' => true]);
    landingModel(['title' => 'Draft rigged', 'is_rigged' => true])->update(['status' => ProductStatus::Draft]);

    $html = $this->get('/')->assertOk()->assertSee('Browse by type')->getContent();
    $types = substr($html, strpos($html, 'id="home-types"'));
    $types = substr($types, 0, strpos($types, '</section>'));

    expect($types)->toContain(route('models.index', ['features' => ['animated']]))
        ->toContain(route('models.index', ['features' => ['rigged']]))
        ->toContain(route('models.index', ['features' => ['pbr']]))
        ->toContain(route('models.index', ['features' => ['free']]))
        ->toContain(route('models.index', ['features' => ['textures']]))
        ->toContain(route('models.index', ['features' => ['vr']]))
        ->toContain('VR / AR ready')
        ->not->toContain(route('models.index', ['features' => ['low_poly']]))
        ->not->toContain(route('models.index', ['features' => ['print']]));
    // Rigged counts the published one only
    expect(preg_match('/Rigged<\/span>\s*<span[^>]*>1 model</', $types))->toBe(1);
});

it('wraps more than six types into even rows instead of leaving one stranded', function () {
    landingModel(['title' => 'All-rounder', 'price_minor' => 0, 'is_animated' => true, 'is_rigged' => true, 'is_pbr' => true, 'is_low_poly' => true, 'has_textures' => true, 'is_vr_ready' => true, 'is_print_ready' => true]);
    $html = $this->get('/')->assertOk()->getContent();
    expect($html)->toContain('sm:grid-cols-3 lg:grid-cols-4')->not->toContain('auto-fit');

    Product::query()->update(['is_print_ready' => false, 'is_vr_ready' => false]);
    $html = $this->get('/')->assertOk()->getContent();
    expect($html)->toContain('auto-fit')->not->toContain('sm:grid-cols-3 lg:grid-cols-4');
});

it('shows browse by format from the files on published models only', function () {
    $a = landingModel(['title' => 'Model A']);
    $b = landingModel(['title' => 'Model B']);
    $draft = landingModel(['title' => 'Draft model']);
    $draft->update(['status' => ProductStatus::Draft]);
    foreach ([[$a, 'fbx', 'exchange'], [$b, 'fbx', 'exchange'], [$a, 'blend', 'native'], [$draft, 'obj', 'exchange'], [$a, 'png', 'texture']] as [$product, $ext, $kind]) {
        ProductFile::create(['product_id' => $product->id, 'disk' => 'local', 'path' => "x/{$ext}", 'original_name' => "m.{$ext}", 'extension' => $ext, 'kind' => $kind, 'size_bytes' => 1000, 'sha256' => str_repeat('a', 64)]);
    }

    $html = $this->get('/')->assertOk()->assertSee('By file format')->getContent();

    expect($html)->toContain(route('models.index', ['format' => 'fbx']))
        ->toContain(route('models.index', ['format' => 'blend']))
        ->not->toContain(route('models.index', ['format' => 'obj']))
        ->not->toContain(route('models.index', ['format' => 'png']));
    expect(strpos($html, 'format=fbx'))->toBeLessThan(strpos($html, 'format=blend'));
});

it('shows models as a gallery of tiles that fills its rows, with a big first tile once there are enough', function () {
    foreach (range(1, 5) as $n) {
        landingModel(['title' => "Gallery model {$n}"]);
    }
    $html = $this->get('/')->assertOk()->getContent();
    expect(substr_count($html, 'aspect-square'))->toBe(5)->and($html)->not->toContain('col-span-2 row-span-2');

    foreach (range(6, 30) as $n) {
        landingModel(['title' => "Gallery model {$n}"]);
    }
    $html = $this->get('/')->assertOk()->getContent();
    expect(substr_count($html, 'aspect-square'))->toBe(20)->and(substr_count($html, 'col-span-2 row-span-2'))->toBe(1);

    expect(substr_count($html, 'href="'.route('models.index').'/gallery-model'))->toBe(21);
    $this->get('/')->assertSee('New in the catalogue');
});

it('shows the newest models until enough have reviews, then the top rated', function () {
    $a = landingModel(['title' => 'Plain oak chair']);
    landingModel(['title' => 'Plain birch chair']);

    $this->get('/')->assertOk()->assertSee('New in the catalogue')->assertSee('Plain oak chair')->assertSee('Plain birch chair');

    foreach (range(1, 5) as $n) {
        landingModel(['title' => "Rated chair {$n}", 'rating_avg' => 4 + $n / 10, 'rating_count' => 3]);
    }
    landingModel(['title' => 'Barely rated chair', 'rating_avg' => 5.0, 'rating_count' => 1]);

    $this->get('/')->assertOk()->assertSee('Top-rated models')->assertSee('Rated chair 5')->assertDontSee('Barely rated chair')->assertDontSee('Plain oak chair');
});

it('never shows models from a suspended seller, nor unpublished ones', function () {
    $suspended = SellerProfile::factory()->suspended()->create();
    $hidden = Product::factory()->published()->create(['user_id' => $suspended->user_id, 'title' => 'Suspended sellers model']);
    Product::factory()->inReview()->create(['title' => 'Still in review model']);
    landingModel(['title' => 'Visible model']);

    $this->get('/')->assertOk()->assertSee('Visible model')->assertDontSee('Suspended sellers model')->assertDontSee('Still in review model');
});

it('shows top-rated stores only from three reviews, best first, with their model count', function () {
    $good = SellerProfile::factory()->approved()->create(['display_name' => 'Grain & Mesh', 'rating_avg' => 4.8, 'rating_count' => 12]);
    $fair = SellerProfile::factory()->approved()->create(['display_name' => 'Polygon Workshop', 'rating_avg' => 3.9, 'rating_count' => 5]);
    $few = SellerProfile::factory()->approved()->create(['display_name' => 'Brand New Studio', 'rating_avg' => 5.0, 'rating_count' => 2]);
    $empty = SellerProfile::factory()->approved()->create(['display_name' => 'No Models Studio', 'rating_avg' => 5.0, 'rating_count' => 9]);
    $suspended = SellerProfile::factory()->suspended()->create(['display_name' => 'Suspended Studio', 'rating_avg' => 5.0, 'rating_count' => 9]);
    foreach ([$good, $fair, $few, $suspended] as $store) {
        landingModel([], $store->user);
    }
    landingModel([], $good->user);

    $html = $this->get('/')->assertOk()->getContent();
    $stores = substr($html, strpos($html, 'id="home-stores"'));
    $stores = substr($stores, 0, strpos($stores, '</section>'));

    expect($stores)->toContain('Grain &amp; Mesh')->toContain('2 models')->toContain('Polygon Workshop')
        ->toContain(route('sellers.show', $good->slug))
        ->not->toContain('Brand New Studio')->not->toContain('No Models Studio')->not->toContain('Suspended Studio');
    expect(strpos($stores, 'Grain'))->toBeLessThan(strpos($stores, 'Polygon'));
});

it('has a working model search that goes to the catalogue', function () {
    $html = $this->get('/')->assertOk()->assertSee('role="search"', false)->getContent();

    expect($html)->toContain('action="'.route('models.index').'"')->toContain('name="q"');
});

it('shows models and the sell page without any sign-in', function () {
    $product = landingModel(['title' => 'Public oak chair']);

    $this->get(route('models.index'))->assertOk()->assertSee('Public oak chair')->assertSee('Join free');
    $this->get(route('models.show', $product))->assertOk()->assertSee('Public oak chair')->assertSee('Join free');
    $this->get(route('seller.index'))->assertRedirect(route('login'));
});
