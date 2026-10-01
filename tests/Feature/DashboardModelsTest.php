<?php

use App\Enums\EngagementStatus;
use App\Enums\ProductStatus;
use App\Models\JobApplication;
use App\Models\JobDeliverable;
use App\Models\JobEngagement;
use App\Models\ModelJob;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\SellerProfile;
use App\Models\User;
use App\Models\WishlistItem;
use Database\Seeders\RolesAndPermissionsSeeder;

/*
 * The models side of the dashboard: seller tiles, the My models card, reviews of the seller's models, and the model items
 * in the one "needs your attention" list. Lazy loading is blocked outside production, so each request also proves the
 * page eager-loads what it reads.
 */

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seller = User::factory()->create(['name' => 'Kevin Mwangi']);
    $this->store = SellerProfile::factory()->approved()->create(['user_id' => $this->seller->id, 'display_name' => 'Kevin 3D Studio']);
});

function sellerModel(array $overrides = []): Product
{
    return Product::factory()->published()->create(array_merge(['user_id' => test()->seller->id], $overrides));
}

/** The big number on a summary tile, found by the tile's label. */
function tileValue(string $html, string $label): ?string
{
    return preg_match('/>'.preg_quote($label, '/').'<.*?<p class="mt-3[^>]*>\s*([^<]+?)\s*<\/p>/s', $html, $match) ? $match[1] : null;
}

function reviewOf(Product $product, array $overrides = []): ProductReview
{
    return ProductReview::factory()->create(array_merge(['product_id' => $product->id], $overrides));
}

it('shows nothing about models to a member who does not sell', function () {
    $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertOk()
        ->assertDontSee('My models')->assertDontSee('Live models')->assertDontSee('Reviews of your models')->assertDontSee('Store rating');
});

it('gives an approved seller with no models the tiles and a way to add the first one', function () {
    $this->actingAs($this->seller)->get(route('dashboard'))->assertOk()
        ->assertSee('Live models')->assertSee('Need changes')->assertSee('Store rating')->assertSee('Saves')
        ->assertSee('Shown after 3 reviews')
        ->assertSee('No models listed yet')->assertSee(route('seller.models.create'), false);
});

it('counts live models, those in review, those sent back or taken down, and saves', function () {
    $live = sellerModel(['title' => 'Oak armchair']);
    sellerModel(['title' => 'Birch table']);
    Product::factory()->inReview()->create(['user_id' => $this->seller->id]);
    Product::factory()->create(['user_id' => $this->seller->id, 'status' => ProductStatus::Rejected]);
    Product::factory()->create(['user_id' => $this->seller->id, 'status' => ProductStatus::Unpublished]);
    WishlistItem::create(['user_id' => User::factory()->create()->id, 'product_id' => $live->id]);
    WishlistItem::create(['user_id' => User::factory()->create()->id, 'product_id' => $live->id]);
    WishlistItem::create(['user_id' => User::factory()->create()->id, 'product_id' => sellerModel()->id]);
    // Someone else's model never counts
    WishlistItem::create(['user_id' => User::factory()->create()->id, 'product_id' => Product::factory()->published()->create()->id]);

    $html = $this->actingAs($this->seller)->get(route('dashboard'))->assertOk()->assertSee('1 more in review')->assertSee('Fix and send again')->getContent();

    expect(tileValue($html, 'Live models'))->toBe('3')
        ->and(tileValue($html, 'Need changes'))->toBe('2')
        ->and(tileValue($html, 'Saves'))->toBe('3');
});

it('shows the store rating once it is public, and not before', function () {
    $this->store->update(['rating_avg' => 4.5, 'rating_count' => 2]);
    $html = $this->actingAs($this->seller)->get(route('dashboard'))->assertOk()->assertSee('Shown after 3 reviews')->getContent();
    expect(tileValue($html, 'Store rating'))->toBe('—');

    $this->store->update(['rating_avg' => 4.5, 'rating_count' => 6]);
    $html = $this->actingAs($this->seller)->get(route('dashboard'))->assertOk()->assertSee('6 buyer reviews')->assertDontSee('Shown after 3 reviews')->getContent();
    expect(tileValue($html, 'Store rating'))->toBe('4.5');
});

it('lists the latest models with status, price, rating and where each one opens', function () {
    $live = sellerModel(['title' => 'Oak armchair', 'price_minor' => 250000, 'rating_avg' => 4.5, 'rating_count' => 3]);
    $draft = Product::factory()->create(['user_id' => $this->seller->id, 'title' => 'Unfinished lamp']);
    sellerModel(['title' => 'Someone elses chair', 'user_id' => User::factory()->create()->id]);

    $this->actingAs($this->seller)->get(route('dashboard'))->assertOk()
        ->assertSee('My models')->assertSee('Oak armchair')->assertSee('Published')->assertSee('2,500')->assertSee('4.5 out of 5 stars', false)
        ->assertSee('Unfinished lamp')->assertSee('Draft')
        ->assertDontSee('Someone elses chair')
        ->assertSee(route('models.show', $live), false)
        ->assertSee(route('seller.models.edit', $draft), false)
        ->assertSee(route('sellers.show', $this->store->slug), false);
});

it('puts model items in the same attention list, with the reason a listing was sent back', function () {
    Product::factory()->create(['user_id' => $this->seller->id, 'title' => 'Rusty gate', 'status' => ProductStatus::Rejected, 'review_notes' => 'Add renders from three angles.', 'reviewed_at' => now()]);
    Product::factory()->create(['user_id' => $this->seller->id, 'title' => 'Copied statue', 'status' => ProductStatus::Unpublished, 'review_notes' => 'Not your own work.', 'reviewed_at' => now()]);

    $product = Product::where('title', 'Rusty gate')->first();

    $this->actingAs($this->seller)->get(route('dashboard'))->assertOk()
        ->assertSee('2 things need your attention')
        ->assertSee('Model needs changes: Rusty gate')->assertSee('Add renders from three angles.')
        ->assertSee('Model taken down: Copied statue')->assertSee('Not your own work.')
        ->assertSee(route('seller.models.edit', $product), false)
        ->assertSeeInOrder(['Model taken down: Copied statue', 'Model needs changes: Rusty gate']);
});

it('asks for a reply to new, visible, unanswered reviews only, one row per model', function () {
    $oak = sellerModel(['title' => 'Oak armchair']);
    $birch = sellerModel(['title' => 'Birch table']);
    reviewOf($oak);
    reviewOf($oak);
    reviewOf($birch, ['seller_reply' => 'Thanks!']);                       // already answered
    reviewOf($birch, ['created_at' => now()->subDays(45)]);                // too old to nag about
    reviewOf($birch)->update(['status' => 'hidden']);                      // hidden by a reviewer

    $this->actingAs($this->seller)->get(route('dashboard'))->assertOk()
        ->assertSee('Reply to 2 new reviews')->assertSee('Oak armchair')->assertSee('1 thing needs your attention');
});

it('lists model items among the job-board ones by urgency', function () {
    $other = User::factory()->create();
    $application = JobApplication::factory()->hired()->create([
        'job_id' => ModelJob::factory()->create(['title' => 'Villa render', 'user_id' => $other->id]),
        'applicant_id' => $this->seller->id, 'poster_id' => $other->id,
    ]);
    $engagement = JobEngagement::create([
        'application_id' => $application->id, 'status' => EngagementStatus::Active, 'agreed_amount' => $application->offer_amount,
        'service_fee' => $application->service_fee, 'net_amount' => $application->net_amount, 'started_at' => now(),
    ]);
    JobDeliverable::create(['engagement_id' => $engagement->id, 'title' => 'Massing', 'description' => 'd', 'due_date' => now()->subDays(2)->toDateString(), 'status' => 'pending']);
    Product::factory()->create(['user_id' => $this->seller->id, 'title' => 'Rusty gate', 'status' => ProductStatus::Rejected, 'reviewed_at' => now()]);
    reviewOf(sellerModel(['title' => 'Oak armchair']));

    $this->actingAs($this->seller)->get(route('dashboard'))->assertOk()
        ->assertSee('3 things need your attention')
        ->assertSeeInOrder(['Overdue: Massing', 'Model needs changes: Rusty gate', 'Reply to 1 new review']);
});

it('shows recent buyer reviews of the seller models, who wrote them as first name and initial, and whether they were answered', function () {
    $oak = sellerModel(['title' => 'Oak armchair']);
    $birch = sellerModel(['title' => 'Birch table']);
    reviewOf($oak, ['user_id' => User::factory()->create(['name' => 'Amina Wanjiku Otieno'])->id, 'comment' => 'Clean topology and sharp textures.', 'rating' => 5]);
    reviewOf($birch, ['comment' => 'Already handled review text.', 'seller_reply' => 'Thank you!']);
    reviewOf($oak, ['comment' => 'Hidden review text here.'])->update(['status' => 'hidden']);
    reviewOf(sellerModel(['title' => 'Other sellers model', 'user_id' => User::factory()->create()->id]), ['comment' => 'Someone elses review text.']);

    $html = $this->actingAs($this->seller)->get(route('dashboard'))->assertOk()
        ->assertSee('Reviews of your models')
        ->assertSee('Amina O.')->assertDontSee('Amina Wanjiku Otieno')
        ->assertSee('Clean topology and sharp textures.')->assertSee('Oak armchair')
        ->assertSee('You replied')
        ->assertDontSee('Hidden review text here.')->assertDontSee('Someone elses review text.')
        ->getContent();

    expect($html)->toContain(route('models.show', $oak).'#review-');
});

it('tells a member whose seller application was turned down or suspended, but never shows the seller cards', function () {
    $rejected = User::factory()->create();
    SellerProfile::factory()->rejected('Please link to some of your work.')->create(['user_id' => $rejected->id]);
    $suspended = User::factory()->create();
    SellerProfile::factory()->suspended('Copied models were listed.')->create(['user_id' => $suspended->id]);
    $pending = User::factory()->create();
    SellerProfile::factory()->create(['user_id' => $pending->id]);

    $this->actingAs($rejected)->get(route('dashboard'))->assertOk()
        ->assertSee('Your seller application needs changes')->assertSee('Please link to some of your work.')->assertSee(route('seller.index'), false)
        ->assertDontSee('My models')->assertDontSee('Live models');

    $this->actingAs($suspended)->get(route('dashboard'))->assertOk()
        ->assertSee('Your store is suspended')->assertSee('Copied models were listed.')->assertDontSee('My models');

    $this->actingAs($pending)->get(route('dashboard'))->assertOk()->assertSee("You're all caught up")->assertDontSee('seller application');
});

it('keeps "Needs your action" as a tile for members who do not sell, and leaves it to the greeting for sellers', function () {
    $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertOk()
        ->assertSee('Needs your action')->assertDontSee('>Projects<', false)->assertDontSee('>Models<', false);

    Product::factory()->create(['user_id' => $this->seller->id, 'status' => ProductStatus::Rejected, 'reviewed_at' => now()]);

    $html = $this->actingAs($this->seller)->get(route('dashboard'))->assertOk()
        ->assertDontSee('Needs your action')
        ->assertSee('>Projects<', false)->assertSee('>Models<', false)
        ->assertSee('1 thing needs your attention')->getContent();

    expect($html)->toContain('href="#attention"');
});
