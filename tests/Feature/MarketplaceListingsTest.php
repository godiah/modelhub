<?php

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductFile;
use App\Models\ProductImage;
use App\Models\SellerProfile;
use App\Models\Software;
use App\Models\User;
use App\Notifications\ProductSubmittedNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/*
 * A seller's model listings: creating a draft, filling it in (details, previews, files), sending it for review,
 * and the rules around who may touch what. Every page test asserts OK first so a 500 cannot satisfy loose text.
 */

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('local');
    Storage::fake('public');
    Notification::fake();

    $this->seller = User::factory()->create(['name' => 'Kevin Mwangi']);
    SellerProfile::factory()->approved()->create(['user_id' => $this->seller->id, 'display_name' => 'Kevin 3D Studio']);

    $this->reviewer = staffWith('Marketplace moderator');

    $this->leaf = Category::where('slug', 'furniture-chair')->first();
    $this->actingAs($this->seller);
});

function draftModel(array $overrides = []): Product
{
    return Product::factory()->create(array_merge(['user_id' => test()->seller->id, 'category_id' => test()->leaf->id], $overrides));
}

/** ---------------------------------------------------------------- access */
it('keeps model listings for approved sellers', function () {
    auth()->logout();
    $this->get(route('seller.models.index'))->assertRedirect(route('login'));

    $applicant = User::factory()->create();
    SellerProfile::factory()->create(['user_id' => $applicant->id]);
    $this->actingAs($applicant)->get(route('seller.models.index'))->assertRedirect(route('seller.index'));
    $this->actingAs(User::factory()->create())->get(route('seller.models.create'))->assertRedirect(route('seller.index'));

    $this->actingAs($this->seller)->get(route('seller.models.index'))->assertOk();
});

it('shows an empty state and a way to start', function () {
    $this->get(route('seller.models.index'))->assertOk()->assertSee('No models yet')->assertSee(route('seller.models.create'), false);
});

/** ---------------------------------------------------------------- creating a draft */
it('offers the categories as top-level choices with their sub-categories', function () {
    $this->get(route('seller.models.create'))->assertOk()->assertSee('Add a model')->assertSee('Furniture')->assertSee('Chair')->assertSee('Scanned Models');
});

it('creates a draft with the price stored in minor units and its currency', function () {
    $this->post(route('seller.models.store'), ['title' => 'Oak armchair', 'category_id' => $this->leaf->id, 'price' => '1250.50'])
        ->assertRedirect();

    $product = Product::first();
    expect($product->status)->toBe(ProductStatus::Draft)
        ->and($product->user_id)->toBe($this->seller->id)
        ->and($product->price_minor)->toBe(125050)
        ->and($product->currency)->toBe('KES')
        ->and($product->slug)->toStartWith('oak-armchair-');

    $this->get(route('seller.models.edit', $product))->assertOk()->assertSee('Oak armchair')->assertSee('Draft');
});

it('creates a free model when the price is zero', function () {
    $this->post(route('seller.models.store'), ['title' => 'Free chair pack', 'category_id' => $this->leaf->id, 'price' => '0']);

    expect(Product::first()->isFree())->toBeTrue();
});

it('validates a new draft', function () {
    $parent = Category::where('slug', 'furniture')->first();

    $this->post(route('seller.models.store'), ['title' => 'Hi', 'category_id' => $this->leaf->id, 'price' => '500'])->assertSessionHasErrors('title');
    $this->post(route('seller.models.store'), ['title' => 'Valid title here', 'category_id' => '', 'price' => '500'])->assertSessionHasErrors('category_id');
    $this->post(route('seller.models.store'), ['title' => 'Valid title here', 'category_id' => $parent->id, 'price' => '500'])->assertSessionHasErrors('category_id');
    $this->post(route('seller.models.store'), ['title' => 'Valid title here', 'category_id' => 999999, 'price' => '500'])->assertSessionHasErrors('category_id');
    $this->post(route('seller.models.store'), ['title' => 'Valid title here', 'category_id' => $this->leaf->id])->assertSessionHasErrors('price');
    $this->post(route('seller.models.store'), ['title' => 'Valid title here', 'category_id' => $this->leaf->id, 'price' => '-5'])->assertSessionHasErrors('price');

    expect(Product::count())->toBe(0);
});

it('lets a top-level category without sub-categories be chosen', function () {
    $textures = Category::where('slug', 'textures-materials')->first();

    $this->post(route('seller.models.store'), ['title' => 'Marble texture set', 'category_id' => $textures->id, 'price' => '500'])->assertRedirect();

    expect(Product::first()->category_id)->toBe($textures->id);
});

/** ---------------------------------------------------------------- editing */
it('keeps other sellers out of a listing', function () {
    $product = draftModel();
    $other = User::factory()->create();
    SellerProfile::factory()->approved()->create(['user_id' => $other->id]);

    $this->actingAs($other);
    $this->get(route('seller.models.edit', $product))->assertForbidden();
    $this->patch(route('seller.models.update', $product), ['title' => 'Stolen title', 'category_id' => $this->leaf->id, 'price' => '1'])->assertForbidden();
    $this->post(route('seller.models.files.store', $product), ['file' => UploadedFile::fake()->create('x.fbx', 10)])->assertForbidden();
    $this->delete(route('seller.models.destroy', $product))->assertForbidden();
    expect($product->fresh()->title)->not->toBe('Stolen title');
});

it('saves the details, tags, features and compatible software', function () {
    $product = draftModel();
    $blender = Software::create(['name' => 'Blender', 'is_active' => true]);

    $this->patch(route('seller.models.update', $product), [
        'title' => 'Mid-century oak armchair',
        'category_id' => $this->leaf->id,
        'description' => 'A carefully modelled armchair with PBR textures and clean topology.',
        'tags' => 'Armchair, OAK, mid-century, oak,  , living room',
        'price' => '2400',
        'geometry_type' => 'polygon_mesh',
        'polygons' => '12500',
        'vertices' => '12800',
        'uv_layout' => 'non_overlapping',
        'render_engine' => 'Cycles 4.2',
        'is_pbr' => '1',
        'is_low_poly' => '1',
        'is_rigged' => '0',
        'software' => [$blender->id],
    ])->assertRedirect(route('seller.models.edit', $product));

    $product->refresh();
    expect($product->title)->toBe('Mid-century oak armchair')
        ->and($product->tags)->toBe(['armchair', 'oak', 'mid-century', 'living room'])
        ->and($product->price_minor)->toBe(240000)
        ->and($product->polygons)->toBe(12500)
        ->and($product->is_pbr)->toBeTrue()
        ->and($product->is_low_poly)->toBeTrue()
        ->and($product->is_rigged)->toBeFalse()
        ->and($product->geometry_type->value)->toBe('polygon_mesh')
        ->and($product->software->pluck('name')->all())->toBe(['Blender']);

    $this->get(route('seller.models.edit', $product))->assertOk()->assertSee('Mid-century oak armchair')->assertSee('Cycles 4.2')->assertSee('armchair, oak, mid-century, living room');
});

it('rejects invalid details', function () {
    $product = draftModel();
    $base = ['title' => 'Valid title here', 'category_id' => $this->leaf->id, 'price' => '500'];

    $this->patch(route('seller.models.update', $product), $base + ['geometry_type' => 'sculpt'])->assertSessionHasErrors('geometry_type');
    $this->patch(route('seller.models.update', $product), $base + ['polygons' => '-4'])->assertSessionHasErrors('polygons');
    $this->patch(route('seller.models.update', $product), $base + ['software' => [999999]])->assertSessionHasErrors('software.0');
    $this->patch(route('seller.models.update', $product), $base + ['description' => str_repeat('a', 20001)])->assertSessionHasErrors('description');
});

it('caps the number of tags', function () {
    $product = draftModel();
    $tags = implode(',', range(1, 40));

    $this->patch(route('seller.models.update', $product), ['title' => 'Valid title here', 'category_id' => $this->leaf->id, 'price' => '500', 'tags' => $tags]);

    expect($product->fresh()->tags)->toHaveCount(config('marketplace.max_tags'));
});

/** ---------------------------------------------------------------- uploads */
it('stores a model file privately under a random name with its checksum', function () {
    $product = draftModel();

    $response = $this->postJson(route('seller.models.files.store', $product), ['file' => UploadedFile::fake()->create('Armchair Final.FBX', 200)]);

    $response->assertCreated()->assertJsonPath('name', 'Armchair Final.FBX')->assertJsonPath('kind', 'exchange');
    $file = ProductFile::first();
    expect($file->extension)->toBe('fbx')
        ->and($file->disk)->toBe('local')
        ->and($file->path)->toStartWith("product-files/{$product->id}/")
        ->and($file->path)->not->toContain('Armchair')
        ->and($file->checksum)->toHaveLength(64);
    Storage::disk('local')->assertExists($file->path);
});

it('sorts files into native, exchange, texture, archive and document kinds', function () {
    $product = draftModel();

    foreach (['chair.blend' => 'native', 'chair.obj' => 'exchange', 'wood.png' => 'texture', 'all.zip' => 'archive', 'readme.pdf' => 'document'] as $name => $kind) {
        $this->postJson(route('seller.models.files.store', $product), ['file' => UploadedFile::fake()->create($name, 20)])->assertCreated()->assertJsonPath('kind', $kind);
    }
});

it('refuses unknown file types and oversized files', function () {
    $product = draftModel();

    $this->postJson(route('seller.models.files.store', $product), ['file' => UploadedFile::fake()->create('virus.exe', 10)])->assertUnprocessable()->assertJsonValidationErrors('file');
    $this->postJson(route('seller.models.files.store', $product), ['file' => UploadedFile::fake()->create('huge.fbx', (config('marketplace.max_file_mb') * 1024) + 1)])
        ->assertUnprocessable()->assertJsonPath('errors.file.0', fn ($m) => str_contains($m, '50 MB'));
    $this->postJson(route('seller.models.files.store', $product), [])->assertUnprocessable();

    expect(ProductFile::count())->toBe(0);
});

it('limits how many files a listing can hold', function () {
    $product = draftModel();
    config(['marketplace.max_files' => 2]);

    $this->postJson(route('seller.models.files.store', $product), ['file' => UploadedFile::fake()->create('a.fbx', 10)])->assertCreated();
    $this->postJson(route('seller.models.files.store', $product), ['file' => UploadedFile::fake()->create('b.fbx', 10)])->assertCreated();
    $this->postJson(route('seller.models.files.store', $product), ['file' => UploadedFile::fake()->create('c.fbx', 10)])->assertUnprocessable();
});

it('removes a file from the listing and from storage', function () {
    $product = draftModel();
    $this->postJson(route('seller.models.files.store', $product), ['file' => UploadedFile::fake()->create('a.fbx', 10)]);
    $file = ProductFile::first();

    $this->deleteJson(route('seller.models.files.destroy', [$product, $file]))->assertOk();

    expect(ProductFile::count())->toBe(0);
    Storage::disk('local')->assertMissing($file->path);
});

it('does not let a file be removed through a different listing', function () {
    $product = draftModel();
    $other = draftModel(['title' => 'Another model here']);
    $this->postJson(route('seller.models.files.store', $product), ['file' => UploadedFile::fake()->create('a.fbx', 10)]);

    $this->deleteJson(route('seller.models.files.destroy', [$other, ProductFile::first()]))->assertNotFound();
});

it('stores preview images publicly, sets the cover and removes them', function () {
    $product = draftModel();

    $first = $this->postJson(route('seller.models.images.store', $product), ['image' => UploadedFile::fake()->image('front.jpg', 800, 600)])->assertCreated();
    $second = $this->postJson(route('seller.models.images.store', $product), ['image' => UploadedFile::fake()->image('back.png', 900, 700)])->assertCreated();

    $images = $product->images()->get();
    expect($images)->toHaveCount(2)->and($images->first()->id)->toBe($first->json('id'));
    Storage::disk('public')->assertExists($images->first()->path);

    $this->postJson($second->json('cover_url'))->assertOk();
    expect($product->images()->get()->first()->id)->toBe($second->json('id'));

    $this->deleteJson($first->json('delete_url'))->assertOk();
    expect($product->images()->count())->toBe(1);
    Storage::disk('public')->assertMissing($images->first()->path);
});

it('refuses images that are too small, the wrong type, too many or too big', function () {
    $product = draftModel();

    $this->postJson(route('seller.models.images.store', $product), ['image' => UploadedFile::fake()->image('tiny.jpg', 100, 100)])->assertUnprocessable()->assertJsonValidationErrors('image');
    $this->postJson(route('seller.models.images.store', $product), ['image' => UploadedFile::fake()->create('doc.pdf', 20)])->assertUnprocessable();
    $this->postJson(route('seller.models.images.store', $product), ['image' => UploadedFile::fake()->image('big.jpg', 800, 600)->size(config('marketplace.max_image_mb') * 1024 + 10)])->assertUnprocessable();

    config(['marketplace.max_images' => 1]);
    $this->postJson(route('seller.models.images.store', $product), ['image' => UploadedFile::fake()->image('a.jpg', 800, 600)])->assertCreated();
    $this->postJson(route('seller.models.images.store', $product), ['image' => UploadedFile::fake()->image('b.jpg', 800, 600)])->assertUnprocessable();
});

it('downloads a file for its owner only', function () {
    $product = draftModel();
    Storage::disk('local')->put('product-files/x/model.fbx', 'FBXDATA');
    $file = ProductFile::create(['product_id' => $product->id, 'disk' => 'local', 'path' => 'product-files/x/model.fbx', 'original_name' => 'My Model.fbx', 'extension' => 'fbx', 'kind' => 'exchange', 'size_bytes' => 7]);

    $this->get(route('seller.models.files.download', [$product, $file]))->assertOk()->assertDownload('My Model.fbx');

    $other = User::factory()->create();
    SellerProfile::factory()->approved()->create(['user_id' => $other->id]);
    $this->actingAs($other)->get(route('seller.models.files.download', [$product, $file]))->assertForbidden();
});

/** ---------------------------------------------------------------- sending for review */
function completeDraft(Product $product): Product
{
    test()->postJson(route('seller.models.images.store', $product), ['image' => UploadedFile::fake()->image('front.jpg', 800, 600)]);
    test()->postJson(route('seller.models.files.store', $product), ['file' => UploadedFile::fake()->create('chair.fbx', 100)]);
    $product->update(['description' => 'A carefully modelled armchair with PBR textures and clean topology.']);

    return $product->fresh();
}

it('does not send an incomplete listing for review and says what is missing', function () {
    $product = draftModel(['description' => null]);

    $this->post(route('seller.models.submit', $product))->assertRedirect(route('seller.models.edit', $product))->assertSessionHas('error');

    expect($product->fresh()->status)->toBe(ProductStatus::Draft);
    Notification::assertNothingSent();
    expect(array_keys($product->readinessProblems()))->toBe(['description', 'images', 'files']);
});

it('sends a complete listing for review and tells the reviewers', function () {
    $product = completeDraft(draftModel());

    $this->post(route('seller.models.submit', $product))->assertRedirect(route('seller.models.index'));

    $product->refresh();
    expect($product->status)->toBe(ProductStatus::InReview)->and($product->submitted_at)->not->toBeNull();
    Notification::assertSentTo($this->reviewer, ProductSubmittedNotification::class, fn ($n) => $n->product->is($product));
});

it('saves and submits in one step from the edit form', function () {
    $product = completeDraft(draftModel());

    $this->patch(route('seller.models.update', $product), ['title' => 'Final title for review', 'category_id' => $this->leaf->id, 'price' => '900', 'description' => 'A carefully modelled armchair with PBR textures and clean topology.', 'then' => 'submit'])
        ->assertRedirect(route('seller.models.index'));

    expect($product->fresh()->title)->toBe('Final title for review')->and($product->fresh()->status)->toBe(ProductStatus::InReview);
});

it('locks a listing while it is in review and when it is live', function () {
    $product = completeDraft(draftModel());
    $this->post(route('seller.models.submit', $product));

    $this->get(route('seller.models.edit', $product))->assertOk()->assertSee('A reviewer is checking this model')->assertSee('Unpublish to edit')->assertDontSee('id="product-form"', false);
    $this->patch(route('seller.models.update', $product), ['title' => 'Sneaky edit here', 'category_id' => $this->leaf->id, 'price' => '1'])->assertForbidden();
    $this->postJson(route('seller.models.files.store', $product), ['file' => UploadedFile::fake()->create('late.fbx', 10)])->assertForbidden();
    $this->post(route('seller.models.submit', $product))->assertForbidden();
    $this->delete(route('seller.models.destroy', $product))->assertRedirect()->assertSessionHas('error');
    expect($product->fresh()->title)->not->toBe('Sneaky edit here')->and(Product::count())->toBe(1);
});

it('lets a seller take a listing back, edit it and send it again', function () {
    $product = completeDraft(draftModel());
    $this->post(route('seller.models.submit', $product));

    $this->post(route('seller.models.unpublish', $product))->assertRedirect(route('seller.models.edit', $product));
    expect($product->fresh()->status)->toBe(ProductStatus::Unpublished);

    $this->patch(route('seller.models.update', $product), ['title' => 'Edited after unpublish', 'category_id' => $this->leaf->id, 'price' => '500'])->assertRedirect();
    expect($product->fresh()->title)->toBe('Edited after unpublish');

    $this->post(route('seller.models.unpublish', $product))->assertRedirect()->assertSessionHas('error');
});

it('shows a seller the reviewer\'s reason when changes are asked for', function () {
    $product = draftModel(['status' => ProductStatus::Rejected, 'review_notes' => 'The preview images are too dark.']);

    $this->get(route('seller.models.edit', $product))->assertOk()->assertSee('Needs changes')->assertSee('The preview images are too dark.');
    $this->get(route('seller.models.index'))->assertOk()->assertSee('The preview images are too dark.');
});

it('deletes a draft with its files and images', function () {
    $product = completeDraft(draftModel());
    $filePath = ProductFile::first()->path;
    $imagePath = ProductImage::first()->path;

    $this->delete(route('seller.models.destroy', $product))->assertRedirect(route('seller.models.index'));

    expect(Product::count())->toBe(0)->and(ProductFile::count())->toBe(0)->and(ProductImage::count())->toBe(0);
    Storage::disk('local')->assertMissing($filePath);
    Storage::disk('public')->assertMissing($imagePath);
});

/** ---------------------------------------------------------------- my models */
it('lists my models with status counts and search', function () {
    draftModel(['title' => 'Walnut desk lamp']);
    draftModel(['title' => 'Concrete planter', 'status' => ProductStatus::InReview]);
    Product::factory()->create(['title' => 'Someone elses sofa', 'category_id' => $this->leaf->id]);

    $this->get(route('seller.models.index'))->assertOk()
        ->assertSee('Walnut desk lamp')->assertSee('Concrete planter')->assertDontSee('Someone elses sofa')
        ->assertSeeInOrder(['All', '2', 'Draft', '1', 'In review', '1']);

    $this->get(route('seller.models.index', ['status' => 'in_review']))->assertOk()->assertSee('Concrete planter')->assertDontSee('Walnut desk lamp');
    $this->get(route('seller.models.index', ['search' => 'lamp']))->assertOk()->assertSee('Walnut desk lamp')->assertDontSee('Concrete planter');
});

it('puts My models in the sidebar for approved sellers only', function () {
    $this->get(route('dashboard'))->assertOk()->assertSee(route('seller.models.index'), false)->assertSee(route('models.index'), false);

    $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertOk()
        ->assertDontSee(route('seller.models.index'), false)->assertSee(route('models.index'), false);
});

/** ---------------------------------------------------------------- the minimum price */
it('lets a model be free or cost at least the platform minimum, and says what the minimum is', function () {
    $post = fn (string $price) => $this->post(route('seller.models.store'), ['title' => 'Priced chair', 'category_id' => $this->leaf->id, 'price' => $price]);

    $post('50')->assertSessionHasErrors(['price' => 'A paid model must cost at least KES 100, or be free.']);
    $post('99.99')->assertSessionHasErrors('price');
    $post('0')->assertSessionHasNoErrors();
    $post('100')->assertSessionHasNoErrors();

    $this->get(route('seller.models.create'))->assertSee('A paid model costs at least')->assertSee('The platform keeps 15% of each sale.');
});

it('follows the minimum price and the commission a Super admin sets', function () {
    setting('fees.min_model_price', 250);
    setting('fees.models_percent', 12.5);

    $this->post(route('seller.models.store'), ['title' => 'Priced chair', 'category_id' => $this->leaf->id, 'price' => '200'])->assertSessionHasErrors(['price' => 'A paid model must cost at least KES 250, or be free.']);
    $this->get(route('seller.models.create'))->assertSee('KES 250')->assertSee('keeps 12.5% of each sale');
});

it('shows the seller their own commission when they have a special rate', function () {
    $this->seller->sellerProfile->forceFill(['commission_percent' => 8])->save();

    $this->get(route('seller.models.create'))->assertSee('keeps 8% of each sale');
});
