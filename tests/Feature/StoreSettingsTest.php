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

/** ---------------------------------------------------------------- avatar */
it('lets the seller pick a store avatar from the catalogue and shows it on the storefront and model page', function () {
    $this->actingAs($this->seller)->patch(route('seller.store.update'), storeDetails(['avatar' => 'bottts/forge']))->assertSessionHasNoErrors();

    expect($this->store->fresh()->avatar)->toBe('bottts/forge');
    $url = asset('images/avatars/stores/bottts/forge.svg');

    $this->get(route('sellers.show', $this->store->slug))->assertOk()->assertSee($url, false);

    $product = Product::factory()->published()->create(['user_id' => $this->seller->id, 'category_id' => Category::where('slug', 'furniture-chair')->value('id')]);
    ProductImage::create(['product_id' => $product->id, 'disk' => 'public', 'path' => 'product-images/x/c.jpg', 'position' => 1]);
    $this->get(route('models.show', $product))->assertOk()->assertSee($url, false);
});

it('keeps the current avatar when none is sent, and refuses anything outside the store catalogue', function () {
    $this->store->update(['avatar' => 'shapes/atlas']);
    $this->actingAs($this->seller);

    $this->patch(route('seller.store.update'), storeDetails(['tagline' => 'Changed tagline']))->assertSessionHasNoErrors();
    expect($this->store->fresh())->avatar->toBe('shapes/atlas')->tagline->toBe('Changed tagline');

    foreach (['notionists/felix', 'bottts/not-a-seed', 'nonsense', '../../etc/passwd', 'bottts/forge/extra'] as $bad) {
        $this->patch(route('seller.store.update'), storeDetails(['avatar' => $bad]))->assertSessionHasErrors('avatar');
    }
    expect($this->store->fresh()->avatar)->toBe('shapes/atlas');
});

it('shows the picker, with no file upload, on the store settings page', function () {
    $this->actingAs($this->seller)->get(route('seller.store.edit'))->assertOk()
        ->assertSee('Store avatar')->assertSee('Choose an avatar')->assertSee('Surprise me')
        ->assertDontSee('type="file"', false)->assertDontSee('Remove my logo')->assertDontSee('multipart');
});

it('has no logo upload or logo removal for reviewers any more', function () {
    $this->actingAs($this->reviewer)->get(route('admin.sellers.index', ['status' => 'approved']))->assertOk()
        ->assertSee('Kevin 3D Studio')->assertSee($this->store->avatarUrl(), false)->assertDontSee('Remove logo');

    expect(Route::has('admin.sellers.remove-logo'))->toBeFalse();
});

it('files the rename notification under Marketplace and presents it', function () {
    expect(NotificationCategory::forType(StoreNameChangedNotification::class))->toBe(NotificationCategory::Marketplace)
        ->and(StoreNameChangedNotification::present(['old_name' => 'A', 'new_name' => 'B'])['content'])->toBe('"A" is now "B"');
});
