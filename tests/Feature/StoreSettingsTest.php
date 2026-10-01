<?php

use App\Enums\NotificationCategory;
use App\Enums\SellerStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\SellerProfile;
use App\Models\User;
use App\Notifications\StoreNameChangedNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/*
 * An approved seller keeping their storefront up to date: name (once every 30 days), tagline, text, website and logo.
 */

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('public');
    Notification::fake();

    $this->seller = User::factory()->create(['name' => 'Kevin Mwangi', 'email' => 'kevin.private@example.test']);
    $this->store = SellerProfile::factory()->approved()->create(['user_id' => $this->seller->id, 'display_name' => 'Kevin 3D Studio']);
    $this->reviewer = User::factory()->create();
    $this->reviewer->assignRole('admin');
});

function storeDetails(array $overrides = []): array
{
    return array_merge([
        'display_name' => 'Kevin 3D Studio',
        'tagline' => 'Clean topology, honest scale',
        'bio' => 'I model furniture and interiors for visualisation, with clean topology and calibrated PBR textures, for studios and game teams.',
        'focus' => 'Furniture, decor and interior props.',
        'website_url' => 'https://kevin3d.example.test',
    ], $overrides);
}

/** ---------------------------------------------------------------- access */
it('keeps store settings for approved sellers', function () {
    $this->get(route('seller.store.edit'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create())->get(route('seller.store.edit'))->assertRedirect(route('seller.index'));

    $applicant = User::factory()->create();
    SellerProfile::factory()->create(['user_id' => $applicant->id]);
    $this->actingAs($applicant)->get(route('seller.store.edit'))->assertRedirect(route('seller.index'));
    $this->actingAs($applicant)->patch(route('seller.store.update'), storeDetails())->assertRedirect(route('seller.index'));

    $this->store->update(['status' => SellerStatus::Suspended]);
    $this->actingAs($this->seller)->get(route('seller.store.edit'))->assertRedirect(route('seller.index'));
});

it('shows the settings with the current details and the rename rule', function () {
    $this->actingAs($this->seller)->get(route('seller.store.edit'))->assertOk()
        ->assertSee('My store')->assertSee('Kevin 3D Studio')->assertSee('once every 30 days')
        ->assertSee(route('sellers.show', $this->store->slug), false);
});

it('adds My store to the sidebar for approved sellers only', function () {
    $this->actingAs($this->seller)->get(route('dashboard'))->assertOk()->assertSee(route('seller.store.edit'), false);
    $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertOk()->assertDontSee(route('seller.store.edit'), false);
});

/** ---------------------------------------------------------------- details */
it('updates the details and shows them on the storefront', function () {
    $this->actingAs($this->seller)->patch(route('seller.store.update'), storeDetails(['tagline' => 'Honest scale', 'website_url' => 'https://www.kevin3d.example.test/']))
        ->assertRedirect(route('seller.store.edit'))->assertSessionHas('success');

    $store = $this->store->fresh();
    expect($store->tagline)->toBe('Honest scale')->and($store->website_url)->toBe('https://www.kevin3d.example.test/')->and($store->name_changed_at)->toBeNull();

    auth()->logout(); // buyers see the storefront, not the seller (whose own name shows in their menu)

    $this->get(route('sellers.show', $store->slug))->assertOk()
        ->assertSee('Honest scale')
        ->assertSee('kevin3d.example.test')
        ->assertSee('rel="nofollow noopener"', false)
        ->assertDontSee('Kevin Mwangi')->assertDontSee('kevin.private@example.test');
});

it('lets the optional fields be cleared', function () {
    $this->store->update(['tagline' => 'Old tagline', 'website_url' => 'https://old.example.test']);

    $this->actingAs($this->seller)->patch(route('seller.store.update'), storeDetails(['tagline' => '', 'website_url' => '']));

    expect($this->store->fresh()->tagline)->toBeNull()->and($this->store->fresh()->website_url)->toBeNull();
});

it('validates the details', function () {
    $this->actingAs($this->seller);

    $this->patch(route('seller.store.update'), storeDetails(['display_name' => '']))->assertSessionHasErrors('display_name');
    $this->patch(route('seller.store.update'), storeDetails(['tagline' => str_repeat('a', 81)]))->assertSessionHasErrors('tagline');
    $this->patch(route('seller.store.update'), storeDetails(['bio' => 'Too short']))->assertSessionHasErrors('bio');
    $this->patch(route('seller.store.update'), storeDetails(['focus' => 'short']))->assertSessionHasErrors('focus');
    $this->patch(route('seller.store.update'), storeDetails(['website_url' => 'javascript:alert(1)']))->assertSessionHasErrors('website_url');
    $this->patch(route('seller.store.update'), storeDetails(['website_url' => 'not a url']))->assertSessionHasErrors('website_url');
});

/** ---------------------------------------------------------------- renaming */
it('renames the store, keeps its address and tells the reviewers', function () {
    $this->actingAs($this->seller)->patch(route('seller.store.update'), storeDetails(['display_name' => 'Kevin Studio Works']))->assertRedirect()->assertSessionHas('success');

    $store = $this->store->fresh();
    expect($store->display_name)->toBe('Kevin Studio Works')->and($store->slug)->toBe('kevin-3d-studio')->and($store->name_changed_at)->not->toBeNull();
    Notification::assertSentTo($this->reviewer, StoreNameChangedNotification::class, fn ($n) => $n->oldName === 'Kevin 3D Studio' && $n->store->display_name === 'Kevin Studio Works');

    $this->get(route('sellers.show', 'kevin-3d-studio'))->assertOk()->assertSee('Kevin Studio Works');
});

it('does not tell the reviewers when the name is unchanged', function () {
    $this->actingAs($this->seller)->patch(route('seller.store.update'), storeDetails());

    Notification::assertNothingSent();
    expect($this->store->fresh()->name_changed_at)->toBeNull();
});

it('allows a rename only once every 30 days', function () {
    $this->actingAs($this->seller);

    $this->patch(route('seller.store.update'), storeDetails(['display_name' => 'First New Name']))->assertSessionHasNoErrors();

    $this->travel(10)->days();
    $this->patch(route('seller.store.update'), storeDetails(['display_name' => 'Second New Name']))->assertSessionHasErrors('display_name');
    expect($this->store->fresh()->display_name)->toBe('First New Name');

    // Editing the other details is still fine during the cooldown
    $this->patch(route('seller.store.update'), storeDetails(['display_name' => 'First New Name', 'tagline' => 'Changed tagline']))->assertSessionHasNoErrors();
    expect($this->store->fresh()->tagline)->toBe('Changed tagline');

    $this->get(route('seller.store.edit'))->assertSee('You can change the name again on');

    $this->travel(21)->days();
    $this->patch(route('seller.store.update'), storeDetails(['display_name' => 'Second New Name']))->assertSessionHasNoErrors();
    expect($this->store->fresh()->display_name)->toBe('Second New Name');
});

it('keeps store names unique', function () {
    SellerProfile::factory()->approved()->create(['display_name' => 'Taken Name']);

    $this->actingAs($this->seller)->patch(route('seller.store.update'), storeDetails(['display_name' => 'Taken Name']))->assertSessionHasErrors('display_name');
});

/** ---------------------------------------------------------------- logo */
it('uploads a logo, shows it on the storefront and model page, and replaces it', function () {
    $this->actingAs($this->seller);

    $this->patch(route('seller.store.update'), storeDetails() + ['logo' => UploadedFile::fake()->image('logo.png', 400, 400)])->assertSessionHasNoErrors();
    $first = $this->store->fresh()->logo_path;
    expect($first)->toStartWith("seller-logos/{$this->store->id}/");
    Storage::disk('public')->assertExists($first);

    $this->get(route('sellers.show', $this->store->slug))->assertOk()->assertSee($this->store->fresh()->logoUrl(), false);

    $product = Product::factory()->published()->create(['user_id' => $this->seller->id, 'category_id' => Category::where('slug', 'furniture-chair')->value('id')]);
    ProductImage::create(['product_id' => $product->id, 'disk' => 'public', 'path' => 'product-images/x/c.jpg', 'position' => 1]);
    $this->get(route('models.show', $product))->assertOk()->assertSee($this->store->fresh()->logoUrl(), false);

    $this->patch(route('seller.store.update'), storeDetails() + ['logo' => UploadedFile::fake()->image('new.jpg', 300, 300)]);
    $second = $this->store->fresh()->logo_path;
    expect($second)->not->toBe($first);
    Storage::disk('public')->assertMissing($first);
    Storage::disk('public')->assertExists($second);
});

it('removes a logo on request', function () {
    $this->actingAs($this->seller)->patch(route('seller.store.update'), storeDetails() + ['logo' => UploadedFile::fake()->image('logo.png', 400, 400)]);
    $path = $this->store->fresh()->logo_path;

    $this->patch(route('seller.store.update'), storeDetails() + ['remove_logo' => '1'])->assertSessionHasNoErrors();

    expect($this->store->fresh()->logo_path)->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

it('refuses unsuitable logos', function () {
    $this->actingAs($this->seller);

    $this->patch(route('seller.store.update'), storeDetails() + ['logo' => UploadedFile::fake()->image('tiny.png', 100, 100)])->assertSessionHasErrors('logo');
    $this->patch(route('seller.store.update'), storeDetails() + ['logo' => UploadedFile::fake()->create('logo.pdf', 20)])->assertSessionHasErrors('logo');
    $this->patch(route('seller.store.update'), storeDetails() + ['logo' => UploadedFile::fake()->image('huge.png', 600, 600)->size(config('marketplace.max_logo_mb') * 1024 + 10)])->assertSessionHasErrors('logo');

    expect($this->store->fresh()->logo_path)->toBeNull();
});

it('never uses the member\'s personal profile photo as the store logo', function () {
    $this->seller->profile()->create(['avatar' => 'avatars/personal-photo.jpg']);
    Storage::disk('public')->put('avatars/personal-photo.jpg', 'x');

    $this->get(route('sellers.show', $this->store->slug))->assertOk()->assertDontSee('personal-photo')->assertSee($this->store->initials());
});

/** ---------------------------------------------------------------- reviewers */
it('lets a reviewer remove an unsuitable logo', function () {
    $this->actingAs($this->seller)->patch(route('seller.store.update'), storeDetails() + ['logo' => UploadedFile::fake()->image('logo.png', 400, 400)]);
    $path = $this->store->fresh()->logo_path;

    $this->actingAs($this->reviewer)->get(route('admin.sellers.index', ['status' => 'approved']))->assertOk()->assertSee('Remove logo')->assertSee('Clean topology, honest scale');
    $this->delete(route('admin.sellers.remove-logo', $this->store))->assertRedirect()->assertSessionHas('success');

    expect($this->store->fresh()->logo_path)->toBeNull();
    Storage::disk('public')->assertMissing($path);

    $this->actingAs($this->seller)->delete(route('admin.sellers.remove-logo', $this->store))->assertForbidden();
});

it('files the rename notification under Marketplace and presents it', function () {
    expect(NotificationCategory::forType(StoreNameChangedNotification::class))->toBe(NotificationCategory::Marketplace)
        ->and(StoreNameChangedNotification::present(['old_name' => 'A', 'new_name' => 'B'])['content'])->toBe('"A" is now "B"');
});
