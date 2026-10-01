<?php

use App\Enums\SellerStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\SellerProfile;
use App\Models\User;
use App\Models\WishlistItem;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Storage;

/*
 * A seller's public storefront and members' wishlists. The storefront shows the store name and published models only,
 * never the member's own name or email; suspending a seller hides their models everywhere.
 */

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('public');

    $this->seller = User::factory()->create(['name' => 'Kevin Mwangi', 'email' => 'kevin.private@example.test']);
    $this->store = SellerProfile::factory()->approved()->create([
        'user_id' => $this->seller->id, 'display_name' => 'Kevin 3D Studio', 'bio' => 'I make furniture and props for visualisation.', 'focus' => 'Furniture and props.',
    ]);
    $this->chair = Category::where('slug', 'furniture-chair')->first();
    $this->car = Category::where('slug', 'cars-sport-car')->first();
    $this->buyer = User::factory()->create(['name' => 'Amina Otieno']);
});

function storeModel(array $overrides = [], ?User $seller = null): Product
{
    $product = Product::factory()->published()->create(array_merge(['user_id' => ($seller ?? test()->seller)->id, 'category_id' => test()->chair->id], $overrides));
    ProductImage::create(['product_id' => $product->id, 'disk' => 'public', 'path' => "product-images/{$product->id}/c.jpg", 'position' => 1]);

    return $product;
}

/** ---------------------------------------------------------------- storefront */
it('gives every seller a storefront address from the store name and keeps it stable', function () {
    $second = SellerProfile::factory()->approved()->create(['display_name' => 'Kevin 3D Studio!']);

    expect($this->store->slug)->toBe('kevin-3d-studio')->and($second->slug)->toBe('kevin-3d-studio-2');

    $this->store->update(['display_name' => 'Renamed Studio']);
    expect($this->store->fresh()->slug)->toBe('kevin-3d-studio');
});

it('shows a seller with their published models only', function () {
    storeModel(['title' => 'Oak armchair model']);
    Product::factory()->create(['user_id' => $this->seller->id, 'category_id' => $this->chair->id, 'title' => 'Secret draft model']);
    Product::factory()->inReview()->create(['user_id' => $this->seller->id, 'category_id' => $this->chair->id, 'title' => 'Waiting for review model']);
    storeModel(['title' => 'Someone elses model'], SellerProfile::factory()->approved()->create()->user);

    $this->get(route('sellers.show', $this->store->slug))->assertOk()
        ->assertSee('Kevin 3D Studio')
        ->assertSee('I make furniture and props for visualisation.')
        ->assertSee('1 model')
        ->assertSee('Selling since')
        ->assertSee('Oak armchair model')
        ->assertDontSee('Secret draft model')->assertDontSee('Waiting for review model')->assertDontSee('Someone elses model');
});

it('never shows the member\'s own name or email on the storefront', function () {
    storeModel();

    $this->get(route('sellers.show', $this->store->slug))->assertOk()
        ->assertDontSee('Kevin Mwangi')->assertDontSee('kevin.private@example.test')->assertDontSee('portfolio.example.test');
});

it('is only for approved sellers', function () {
    foreach ([SellerStatus::Pending, SellerStatus::Rejected, SellerStatus::Suspended] as $status) {
        $profile = SellerProfile::factory()->create(['status' => $status]);
        $this->get(route('sellers.show', $profile->slug))->assertNotFound();
    }

    $this->get(route('sellers.show', 'no-such-store'))->assertNotFound();
});

it('filters a storefront by category and sorts it', function () {
    storeModel(['title' => 'Cheap chair model', 'price_minor' => 10000]);
    storeModel(['title' => 'Pricey sports car', 'price_minor' => 900000, 'category_id' => $this->car->id]);

    $this->get(route('sellers.show', $this->store->slug))->assertOk()->assertSeeInOrder(['Furniture', '1', 'Cars', '1']);
    $this->get(route('sellers.show', [$this->store->slug, 'category' => 'cars']))->assertOk()->assertSee('Pricey sports car')->assertDontSee('Cheap chair model');
    $this->get(route('sellers.show', [$this->store->slug, 'sort' => 'price_high']))->assertOk()->assertSeeInOrder(['Pricey sports car', 'Cheap chair model']);
    $this->get(route('sellers.show', [$this->store->slug, 'sort' => 'bogus']))->assertSessionHasErrors('sort');
});

it('shows an empty storefront politely', function () {
    $this->get(route('sellers.show', $this->store->slug))->assertOk()->assertSee('No models here yet');
});

it('links the store name from cards and the model page', function () {
    $product = storeModel(['title' => 'Linked armchair']);

    $this->get(route('models.index'))->assertOk()->assertSee(route('sellers.show', $this->store->slug), false);
    $this->get(route('models.show', $product))->assertOk()->assertSee(route('sellers.show', $this->store->slug), false);
});

it('hides a suspended seller\'s models from the catalogue, their page and the public', function () {
    $product = storeModel(['title' => 'Soon hidden armchair']);
    $this->get(route('models.index'))->assertSee('Soon hidden armchair');

    $this->store->update(['status' => SellerStatus::Suspended]);

    $this->get(route('models.index'))->assertOk()->assertDontSee('Soon hidden armchair');
    $this->get(route('models.show', $product))->assertNotFound();
    $this->get(route('sellers.show', $this->store->slug))->assertNotFound();

    // The owner and reviewers can still open it
    $this->actingAs($this->seller)->get(route('models.show', $product))->assertOk()->assertSee('Preview');
    $this->actingAs(staffWith('Marketplace moderator'), 'staff')->get(route('models.show', $product))->assertOk();
});

/** ---------------------------------------------------------------- wishlist */
it('sends guests to sign in when they try to save a model', function () {
    $product = storeModel();

    $this->post(route('models.wishlist.toggle', $product))->assertRedirect(route('login'));
    $this->get(route('wishlist.index'))->assertRedirect(route('login'));
    $this->get(route('models.index'))->assertOk()->assertSee('Sign in to save to your wishlist');
});

it('saves and unsaves a model, as JSON and as a plain form', function () {
    $product = storeModel();
    $this->actingAs($this->buyer);

    $this->postJson(route('models.wishlist.toggle', $product))->assertOk()->assertJson(['saved' => true, 'count' => 1]);
    expect(WishlistItem::where('user_id', $this->buyer->id)->where('product_id', $product->id)->exists())->toBeTrue();

    $this->postJson(route('models.wishlist.toggle', $product))->assertOk()->assertJson(['saved' => false, 'count' => 0]);
    expect(WishlistItem::count())->toBe(0);

    $this->post(route('models.wishlist.toggle', $product))->assertRedirect()->assertSessionHas('success');
    expect(WishlistItem::count())->toBe(1);
});

it('saves a model only once per member', function () {
    $product = storeModel();

    $this->actingAs($this->buyer)->postJson(route('models.wishlist.toggle', $product));
    $this->actingAs(User::factory()->create())->postJson(route('models.wishlist.toggle', $product))->assertJson(['count' => 2]);

    expect(WishlistItem::where('product_id', $product->id)->count())->toBe(2)
        ->and(WishlistItem::where('user_id', $this->buyer->id)->count())->toBe(1);
});

it('does not let an unpublished model be saved', function () {
    $draft = Product::factory()->create(['user_id' => $this->seller->id, 'category_id' => $this->chair->id]);

    $this->actingAs($this->buyer)->postJson(route('models.wishlist.toggle', $draft))->assertNotFound();
    expect(WishlistItem::count())->toBe(0);
});

it('lists my saved models newest first and keeps others\' lists private', function () {
    $first = storeModel(['title' => 'First saved armchair']);
    $second = storeModel(['title' => 'Second saved armchair']);
    storeModel(['title' => 'Never saved armchair']);

    $this->actingAs($this->buyer);
    $this->post(route('models.wishlist.toggle', $first));
    $this->travel(5)->minutes();
    $this->post(route('models.wishlist.toggle', $second));

    $this->get(route('wishlist.index'))->assertOk()
        ->assertSeeInOrder(['Second saved armchair', 'First saved armchair'])
        ->assertDontSee('Never saved armchair');

    $this->actingAs(User::factory()->create())->get(route('wishlist.index'))->assertOk()->assertSee('Nothing saved yet')->assertDontSee('First saved armchair');
});

it('hides saved models that are no longer live, and brings them back with the model', function () {
    $product = storeModel(['title' => 'Comes and goes armchair']);
    $this->actingAs($this->buyer)->post(route('models.wishlist.toggle', $product));

    $product->update(['status' => 'unpublished']);
    $this->get(route('wishlist.index'))->assertOk()->assertDontSee('Comes and goes armchair')->assertSee('Nothing saved yet');
    expect(WishlistItem::count())->toBe(1);

    $product->update(['status' => 'published']);
    $this->get(route('wishlist.index'))->assertOk()->assertSee('Comes and goes armchair');
});

it('fills the heart for saved models and shows how many members saved a model', function () {
    $product = storeModel();
    $other = storeModel(['title' => 'Not saved armchair']);
    $this->actingAs($this->buyer)->post(route('models.wishlist.toggle', $product));
    $this->actingAs(User::factory()->create())->post(route('models.wishlist.toggle', $product));

    $this->actingAs($this->buyer);
    $page = $this->get(route('models.show', $product))->assertOk()->assertSee('Saved to wishlist')->assertSee('saved: true', false)->assertSee('count: 2', false);
    expect(substr_count($this->get(route('models.index'))->getContent(), 'saved: true'))->toBe(1);
    $this->get(route('models.show', $other))->assertOk()->assertSee('Save to wishlist')->assertSee('saved: false', false);
});

it('shows a seller how many members saved each of their models', function () {
    $product = storeModel(['title' => 'Popular armchair']);
    $this->actingAs($this->buyer)->post(route('models.wishlist.toggle', $product));
    $this->actingAs(User::factory()->create())->post(route('models.wishlist.toggle', $product));

    $this->actingAs($this->seller)->get(route('seller.models.index'))->assertOk()->assertSee('Members who saved this model')->assertSeeInOrder(['Popular armchair', '2']);
});

it('removes saved items when the model or the member is deleted', function () {
    $product = storeModel();
    $this->actingAs($this->buyer)->post(route('models.wishlist.toggle', $product));

    $this->buyer->delete();
    expect(WishlistItem::count())->toBe(0);

    $other = User::factory()->create();
    WishlistItem::create(['user_id' => $other->id, 'product_id' => $product->id]);
    $product->forceDelete();
    expect(WishlistItem::count())->toBe(0);
});

it('puts Wishlist in the sidebar for members', function () {
    $this->actingAs($this->buyer)->get(route('dashboard'))->assertOk()->assertSee(route('wishlist.index'), false)->assertSee('Wishlist');
});
