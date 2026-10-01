<?php

use App\Enums\NotificationCategory;
use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductFile;
use App\Models\ProductImage;
use App\Models\SellerProfile;
use App\Models\Software;
use App\Models\User;
use App\Notifications\ProductReviewedNotification;
use App\Notifications\ProductSubmittedNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/*
 * The reviewer's queue and the public catalogue: only published models are public, filters narrow them the way
 * buyers think (category, format, features, price), and a model page shows the technical details sellers entered.
 */

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('local');
    Storage::fake('public');
    Notification::fake();

    $this->seller = User::factory()->create(['name' => 'Kevin Mwangi']);
    SellerProfile::factory()->approved()->create(['user_id' => $this->seller->id, 'display_name' => 'Kevin 3D Studio']);
    $this->reviewer = User::factory()->create();
    $this->reviewer->assignRole('admin');

    $this->chair = Category::where('slug', 'furniture-chair')->first();
    $this->sofa = Category::where('slug', 'furniture-sofa')->first();
    $this->car = Category::where('slug', 'cars-sport-car')->first();
});

function liveModel(array $overrides = [], array $files = ['fbx'], bool $image = true): Product
{
    $product = Product::factory()->published()->create(array_merge(['user_id' => test()->seller->id, 'category_id' => test()->chair->id], $overrides));

    foreach ($files as $extension) {
        ProductFile::create(['product_id' => $product->id, 'disk' => 'local', 'path' => "product-files/{$product->id}/".uniqid().".{$extension}", 'original_name' => "model.{$extension}",
            'extension' => $extension, 'kind' => in_array($extension, ['blend', 'max']) ? 'native' : (in_array($extension, ['png', 'jpg']) ? 'texture' : 'exchange'), 'size_bytes' => 5 * 1048576]);
    }
    if ($image) {
        ProductImage::create(['product_id' => $product->id, 'disk' => 'public', 'path' => "product-images/{$product->id}/cover.jpg", 'position' => 1]);
    }

    return $product;
}

/** ---------------------------------------------------------------- the reviewer's queue */
it('keeps the model review queue for staff with the permission', function () {
    $this->get(route('admin.models.index'))->assertRedirect(route('login'));
    $this->actingAs($this->seller)->get(route('admin.models.index'))->assertForbidden();
    $this->actingAs($this->reviewer)->get(route('admin.models.index'))->assertOk();
});

it('lists models waiting for review first, with files and counts', function () {
    Product::factory()->inReview()->create(['user_id' => $this->seller->id, 'category_id' => $this->chair->id, 'title' => 'Waiting armchair model']);
    Product::factory()->create(['user_id' => $this->seller->id, 'category_id' => $this->chair->id, 'title' => 'Private draft model']);
    $live = liveModel(['title' => 'Already live model']);

    $this->actingAs($this->reviewer)->get(route('admin.models.index'))->assertOk()
        ->assertSee('Waiting armchair model')->assertDontSee('Private draft model')->assertDontSee('Already live model')
        ->assertSee('Kevin 3D Studio')
        ->assertSeeInOrder(['All', '3', 'Draft', '1', 'In review', '1', 'Published', '1']);

    $this->get(route('admin.models.index', ['status' => 'published']))->assertOk()->assertSee('Already live model')
        ->assertSee(route('admin.models.files.download', [$live, $live->files->first()]), false);
});

it('publishes a model in review and tells the seller', function () {
    $product = Product::factory()->inReview()->create(['user_id' => $this->seller->id, 'category_id' => $this->chair->id]);

    $this->actingAs($this->reviewer)->patch(route('admin.models.review', [$product, 'publish']))->assertRedirect();

    $product->refresh();
    expect($product->status)->toBe(ProductStatus::Published)->and($product->published_at)->not->toBeNull()
        ->and($product->reviewed_by)->toBe($this->reviewer->id);
    Notification::assertSentTo($this->seller, ProductReviewedNotification::class, fn ($n) => $n->product->status === ProductStatus::Published);
});

it('asks for changes with a required reason', function () {
    $product = Product::factory()->inReview()->create(['user_id' => $this->seller->id, 'category_id' => $this->chair->id]);
    $this->actingAs($this->reviewer);

    $this->patch(route('admin.models.review', [$product, 'reject']))->assertSessionHas('error');
    expect($product->fresh()->status)->toBe(ProductStatus::InReview);

    $this->patch(route('admin.models.review', [$product, 'reject']), ['notes' => 'Add textures to the preview.'])->assertSessionHas('success');
    expect($product->fresh()->status)->toBe(ProductStatus::Rejected)->and($product->fresh()->review_notes)->toBe('Add textures to the preview.');
    Notification::assertSentTo($this->seller, ProductReviewedNotification::class, fn ($n) => $n->product->review_notes === 'Add textures to the preview.');
});

it('takes a live model down with a reason', function () {
    $product = liveModel();
    $this->actingAs($this->reviewer);

    $this->patch(route('admin.models.review', [$product, 'takedown']))->assertSessionHas('error');
    $this->patch(route('admin.models.review', [$product, 'takedown']), ['notes' => 'Reported as copied.'])->assertSessionHas('success');

    expect($product->fresh()->status)->toBe(ProductStatus::Unpublished);
    $this->get(route('models.show', $product))->assertOk(); // a reviewer can still open it
    auth()->logout();
    $this->get(route('models.show', $product))->assertNotFound();
});

it('refuses decisions that do not fit the current state', function () {
    $draft = Product::factory()->create(['user_id' => $this->seller->id, 'category_id' => $this->chair->id]);
    $live = liveModel();
    $this->actingAs($this->reviewer);

    $this->patch(route('admin.models.review', [$draft, 'publish']))->assertSessionHas('error');
    $this->patch(route('admin.models.review', [$live, 'publish']))->assertSessionHas('error');
    $this->patch(route('admin.models.review', [$live, 'reject']), ['notes' => 'nope nope'])->assertSessionHas('error');
    $this->patch('/admin/models/'.$live->slug.'/delete')->assertNotFound();
    expect($draft->fresh()->status)->toBe(ProductStatus::Draft)->and($live->fresh()->status)->toBe(ProductStatus::Published);
});

it('lets reviewers download a seller\'s files and nobody else', function () {
    $product = Product::factory()->inReview()->create(['user_id' => $this->seller->id, 'category_id' => $this->chair->id]);
    Storage::disk('local')->put('product-files/r/model.fbx', 'FBXDATA');
    $file = ProductFile::create(['product_id' => $product->id, 'disk' => 'local', 'path' => 'product-files/r/model.fbx', 'original_name' => 'Chair.fbx', 'extension' => 'fbx', 'kind' => 'exchange', 'size_bytes' => 7]);

    $this->actingAs($this->reviewer)->get(route('admin.models.files.download', [$product, $file]))->assertOk()->assertDownload('Chair.fbx');
    $this->actingAs(User::factory()->create())->get(route('admin.models.files.download', [$product, $file]))->assertForbidden();
});

it('shows reviewers the Model reviews entry only if they hold the permission', function () {
    $this->actingAs($this->reviewer)->get(route('dashboard'))->assertOk()->assertSee(route('admin.models.index'), false);
    $this->actingAs($this->seller)->get(route('dashboard'))->assertOk()->assertDontSee(route('admin.models.index'), false);
});

/** ---------------------------------------------------------------- the public catalogue */
it('shows only published models to anyone', function () {
    liveModel(['title' => 'Published armchair']);
    Product::factory()->create(['user_id' => $this->seller->id, 'category_id' => $this->chair->id, 'title' => 'Secret draft chair']);
    Product::factory()->inReview()->create(['user_id' => $this->seller->id, 'category_id' => $this->chair->id, 'title' => 'Waiting for review chair']);

    $this->get(route('models.index'))->assertOk()
        ->assertSee('Published armchair')->assertDontSee('Secret draft chair')->assertDontSee('Waiting for review chair')
        ->assertSee('Kevin 3D Studio')->assertDontSee('Kevin Mwangi');
});

it('shows an empty catalogue politely', function () {
    $this->get(route('models.index'))->assertOk()->assertSee('No models yet');
});

it('filters by category: a top-level category includes its sub-categories', function () {
    liveModel(['title' => 'A comfy chair', 'category_id' => $this->chair->id]);
    liveModel(['title' => 'A big sofa', 'category_id' => $this->sofa->id]);
    liveModel(['title' => 'A fast car', 'category_id' => $this->car->id]);

    $this->get(route('models.index', ['category' => 'furniture']))->assertOk()->assertSee('A comfy chair')->assertSee('A big sofa')->assertDontSee('A fast car');
    $this->get(route('models.index', ['category' => 'furniture-chair']))->assertOk()->assertSee('A comfy chair')->assertDontSee('A big sofa');
    $this->get(route('models.index', ['category' => 'no-such-category']))->assertOk()->assertSee('A fast car');
});

it('shows category chips with counts of published models only', function () {
    liveModel(['category_id' => $this->chair->id]);
    liveModel(['category_id' => $this->sofa->id]);
    Product::factory()->create(['user_id' => $this->seller->id, 'category_id' => $this->car->id]);

    $this->get(route('models.index'))->assertOk()->assertSeeInOrder(['Furniture', '2'])->assertDontSee('Cars');
});

it('searches titles, descriptions and tags', function () {
    liveModel(['title' => 'Walnut table', 'description' => 'A solid table.', 'tags' => ['walnut']]);
    liveModel(['title' => 'Plain shelf', 'description' => 'Made of reclaimed timber.', 'tags' => ['storage']]);
    liveModel(['title' => 'Metal frame', 'description' => 'Industrial.', 'tags' => ['storage', 'metal']]);

    $this->get(route('models.index', ['q' => 'walnut']))->assertOk()->assertSee('Walnut table')->assertDontSee('Plain shelf');
    $this->get(route('models.index', ['q' => 'reclaimed']))->assertOk()->assertSee('Plain shelf')->assertDontSee('Walnut table');
    $this->get(route('models.index', ['q' => 'storage']))->assertOk()->assertSee('Plain shelf')->assertSee('Metal frame')->assertDontSee('Walnut table');
});

it('filters by file format and software', function () {
    liveModel(['title' => 'Blender-only chair'], ['blend']);
    $multi = liveModel(['title' => 'Chair in FBX and OBJ'], ['fbx', 'obj']);
    liveModel(['title' => 'Textures only pack'], ['png']);
    $software = Software::create(['name' => 'Blender', 'is_active' => true]);
    $multi->software()->attach($software->id);

    $this->get(route('models.index', ['format' => 'fbx']))->assertOk()->assertSee('Chair in FBX and OBJ')->assertDontSee('Blender-only chair');
    $this->get(route('models.index', ['format' => 'png']))->assertOk()->assertSee('No models match'); // textures are not model formats
    $this->get(route('models.index', ['software' => $software->id]))->assertOk()->assertSee('Chair in FBX and OBJ')->assertDontSee('Blender-only chair');
    $this->get(route('models.index'))->assertOk()->assertSee('value="blend"', false)->assertSee('value="fbx"', false)->assertDontSee('value="png"', false);
});

it('filters by free, features and price, and sorts', function () {
    liveModel(['title' => 'Cheap rigged guy', 'price_minor' => 50000, 'is_rigged' => true]);
    liveModel(['title' => 'Free low-poly rock', 'price_minor' => 0, 'is_low_poly' => true]);
    liveModel(['title' => 'Premium animated dragon', 'price_minor' => 900000, 'is_animated' => true, 'is_pbr' => true]);

    $this->get(route('models.index', ['features' => ['free']]))->assertOk()->assertSee('Free low-poly rock')->assertDontSee('Cheap rigged guy');
    $this->get(route('models.index', ['features' => ['animated', 'pbr']]))->assertOk()->assertSee('Premium animated dragon')->assertDontSee('Cheap rigged guy');
    $this->get(route('models.index', ['features' => ['rigged']]))->assertOk()->assertSee('Cheap rigged guy')->assertDontSee('Free low-poly rock');
    $this->get(route('models.index', ['min_price' => 100, 'max_price' => 6000]))->assertOk()->assertSee('Cheap rigged guy')->assertDontSee('Premium animated dragon')->assertDontSee('Free low-poly rock');
    $this->get(route('models.index', ['sort' => 'price_high']))->assertOk()->assertSeeInOrder(['Premium animated dragon', 'Cheap rigged guy', 'Free low-poly rock']);
    $this->get(route('models.index', ['sort' => 'price_low']))->assertOk()->assertSeeInOrder(['Free low-poly rock', 'Cheap rigged guy', 'Premium animated dragon']);
    $this->get(route('models.index', ['features' => ['bogus']]))->assertSessionHasErrors('features.0');
});

it('filters by textures and VR / AR ready, alone and together with other features', function () {
    liveModel(['title' => 'Textured crate', 'has_textures' => true]);
    liveModel(['title' => 'Headset ready robot', 'is_vr_ready' => true, 'is_rigged' => true]);
    liveModel(['title' => 'Textured VR lamp', 'has_textures' => true, 'is_vr_ready' => true]);
    liveModel(['title' => 'Bare mesh rock']);

    $this->get(route('models.index', ['features' => ['textures']]))->assertOk()
        ->assertSee('Textured crate')->assertSee('Textured VR lamp')->assertDontSee('Headset ready robot')->assertDontSee('Bare mesh rock');
    $this->get(route('models.index', ['features' => ['vr']]))->assertOk()
        ->assertSee('Headset ready robot')->assertSee('Textured VR lamp')->assertDontSee('Textured crate')->assertDontSee('Bare mesh rock');
    $this->get(route('models.index', ['features' => ['textures', 'vr']]))->assertOk()
        ->assertSee('Textured VR lamp')->assertDontSee('Textured crate')->assertDontSee('Headset ready robot');

    $this->get(route('models.index', ['features' => ['vr']]))->assertSee('value="textures"', false)->assertSee('value="vr"', false)->assertSee('VR / AR');
});

it('paginates the catalogue', function () {
    foreach (range(1, 26) as $i) {
        liveModel(['title' => "Catalogue model number {$i}"], ['fbx'], false);
    }

    $this->get(route('models.index'))->assertOk()->assertSee('Showing 1–24 of 26')->assertSee('page=2', false);
    $this->get(route('models.index', ['page' => 2]))->assertOk()->assertSee('Showing 25–26 of 26');
});

/** ---------------------------------------------------------------- a model's page */
it('shows a published model with its files grouped by kind, details and a disabled purchase', function () {
    $software = Software::create(['name' => 'Blender', 'is_active' => true]);
    $product = liveModel([
        'title' => 'Mid-century armchair', 'description' => "A **lovely** armchair.\n\n<script>alert(1)</script>", 'tags' => ['armchair', 'oak'],
        'price_minor' => 125000, 'polygons' => 12500, 'vertices' => 12800, 'is_pbr' => true, 'is_low_poly' => true,
        'geometry_type' => 'polygon_mesh', 'uv_layout' => 'non_overlapping', 'render_engine' => 'Cycles 4.2',
    ], ['blend', 'fbx', 'obj', 'png']);
    $product->software()->attach($software->id);

    $this->get(route('models.show', $product))->assertOk()
        ->assertSee('Mid-century armchair')
        ->assertSee('<strong>lovely</strong>', false)
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('Kevin 3D Studio')
        ->assertSee('Ksh1,250.00')
        ->assertSee('Native formats')->assertSee('Exchange formats')->assertSee('Textures')
        ->assertSee('20.0 MB')
        ->assertSee('12,500')->assertSee('12,800')->assertSee('Polygon mesh')->assertSee('Non-overlapping')->assertSee('Cycles 4.2')->assertSee('Blender')
        ->assertSee('PBR')->assertSee('Low-poly')
        ->assertSee('Purchases open soon')
        ->assertSee('#'.$product->id)
        ->assertSee(route('models.index', ['q' => 'armchair']), false)
        ->assertDontSee('model.fbx'); // file names stay private until purchase
});

it('shows related models from the same category', function () {
    $product = liveModel(['title' => 'Main armchair here']);
    liveModel(['title' => 'Related armchair two']);
    liveModel(['title' => 'Different category sofa', 'category_id' => $this->sofa->id]);

    $this->get(route('models.show', $product))->assertOk()->assertSee('More in this category')->assertSee('Related armchair two')->assertDontSee('Different category sofa');
});

it('hides unpublished models from the public but lets the owner preview them', function () {
    $draft = Product::factory()->create(['user_id' => $this->seller->id, 'category_id' => $this->chair->id, 'title' => 'My private draft']);

    $this->get(route('models.show', $draft))->assertNotFound();
    $this->actingAs(User::factory()->create())->get(route('models.show', $draft))->assertNotFound();

    $this->actingAs($this->seller)->get(route('models.show', $draft))->assertOk()->assertSee('Preview: this model is draft')->assertSee('Back to editing');
    $this->actingAs($this->reviewer)->get(route('models.show', $draft))->assertOk()->assertSee('Preview');
});

it('does not show a deleted model', function () {
    $product = liveModel();
    $product->delete();

    $this->get(route('models.show', $product->slug))->assertNotFound();
});

it('files the model notifications under Marketplace and presents them sensibly', function () {
    expect(NotificationCategory::forType(ProductReviewedNotification::class))->toBe(NotificationCategory::Marketplace)
        ->and(NotificationCategory::forType(ProductSubmittedNotification::class))->toBe(NotificationCategory::Marketplace);

    expect(ProductReviewedNotification::present(['status' => 'published', 'title' => 'Chair'])['title'])->toBe('Your model is live')
        ->and(ProductReviewedNotification::present(['status' => 'rejected', 'title' => 'Chair', 'notes' => 'Darker previews'])['content'])->toContain('Darker previews');
});
