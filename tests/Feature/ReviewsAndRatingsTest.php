<?php

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\Purchase;
use App\Models\ReviewReport;
use App\Models\SellerProfile;
use App\Models\User;
use App\Notifications\ModelReviewedNotification;
use App\Notifications\ModelReviewHiddenNotification;
use App\Notifications\ModelReviewReplyNotification;
use App\Services\Marketplace\ProductRatingService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Role;

/*
 * Ratings and reviews of models: only verified buyers write one (once), the seller gets one public reply, members
 * can report a review and a reviewer can hide it. Hidden reviews drop out of the average; sellers cannot delete them.
 */

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Notification::fake();

    $this->seller = User::factory()->create(['name' => 'Kevin Mwangi']);
    $this->store = SellerProfile::factory()->approved()->create(['user_id' => $this->seller->id, 'display_name' => 'Kevin 3D Studio']);
    $this->product = Product::factory()->published()->create(['user_id' => $this->seller->id, 'category_id' => Category::where('slug', 'furniture-chair')->value('id'), 'title' => 'Oak armchair']);
    $this->buyer = User::factory()->create(['name' => 'Amina Wanjiku Otieno']);
    Purchase::factory()->create(['user_id' => $this->buyer->id, 'product_id' => $this->product->id]);
    $this->moderator = User::factory()->create();
    $this->moderator->assignRole(Role::findByName('admin'));
});

function reviewBy(User $user, array $overrides = []): ProductReview
{
    return ProductReview::factory()->create(array_merge(['product_id' => test()->product->id, 'user_id' => $user->id], $overrides));
}

/** ---------------------------------------------------------------- writing */
it('lets a verified buyer review a model, shows them as first name and initial, and updates the rating', function () {
    $this->actingAs($this->buyer)
        ->post(route('models.reviews.store', $this->product), ['rating' => 4, 'comment' => 'Clean topology and sharp textures.'])
        ->assertRedirect();

    $review = ProductReview::first();
    expect($review->rating)->toBe(4)->and($review->purchase_id)->not->toBeNull();
    expect($this->product->fresh())->rating_count->toBe(1)->rating_avg->toBe(4.0);
    Notification::assertSentTo($this->seller, ModelReviewedNotification::class);

    auth()->logout();
    $this->get(route('models.show', $this->product))->assertOk()
        ->assertSee('Clean topology and sharp textures.')
        ->assertSee('Amina O.')
        ->assertDontSee('Amina Wanjiku Otieno')
        ->assertSee('Verified buyer');
});

it('refuses reviews from people who have not bought the model, the seller, and second reviews', function () {
    $stranger = User::factory()->create();

    $this->actingAs($stranger)->post(route('models.reviews.store', $this->product), ['rating' => 5, 'comment' => 'Never bought this one.'])->assertSessionHas('error');
    $this->actingAs($this->seller)->post(route('models.reviews.store', $this->product), ['rating' => 5, 'comment' => 'My own model is great.'])->assertSessionHas('error');

    $this->actingAs($this->buyer)->post(route('models.reviews.store', $this->product), ['rating' => 5, 'comment' => 'First review is fine.']);
    $this->actingAs($this->buyer)->post(route('models.reviews.store', $this->product), ['rating' => 1, 'comment' => 'A second try at it.'])->assertSessionHas('error');

    expect(ProductReview::count())->toBe(1);
});

it('does not count a pending or refunded purchase as verified', function () {
    Purchase::query()->update(['status' => 'refunded']);

    $this->actingAs($this->buyer)->post(route('models.reviews.store', $this->product), ['rating' => 5, 'comment' => 'Trying with a refund.'])->assertSessionHas('error');

    expect(ProductReview::count())->toBe(0);
});

it('validates the rating and the comment', function (array $input, string $field) {
    $this->actingAs($this->buyer)->post(route('models.reviews.store', $this->product), $input)->assertSessionHasErrors($field);
})->with([
    'no rating' => [['comment' => 'A long enough comment.'], 'rating'],
    'rating too high' => [['rating' => 6, 'comment' => 'A long enough comment.'], 'rating'],
    'rating zero' => [['rating' => 0, 'comment' => 'A long enough comment.'], 'rating'],
    'no comment' => [['rating' => 4], 'comment'],
    'short comment' => [['rating' => 4, 'comment' => 'Nice'], 'comment'],
]);

it('only accepts reviews on published models', function () {
    $draft = Product::factory()->inReview()->create(['user_id' => $this->seller->id]);
    Purchase::factory()->create(['user_id' => $this->buyer->id, 'product_id' => $draft->id]);

    $this->actingAs($this->buyer)->post(route('models.reviews.store', $draft), ['rating' => 5, 'comment' => 'Not even live yet.'])->assertNotFound();
});

it('needs a signed-in member', function () {
    $this->post(route('models.reviews.store', $this->product), ['rating' => 5, 'comment' => 'Not signed in at all.'])->assertRedirect(route('login'));
});

it('shows the review form only to a buyer who has not reviewed yet', function () {
    $this->actingAs($this->buyer)->get(route('models.show', $this->product))->assertSee('Review this model');

    reviewBy($this->buyer);
    $this->actingAs($this->buyer)->get(route('models.show', $this->product))->assertDontSee('Review this model')->assertSee('Your review');

    $this->actingAs(User::factory()->create())->get(route('models.show', $this->product))->assertDontSee('Review this model');
    $this->actingAs($this->seller)->get(route('models.show', $this->product))->assertDontSee('Review this model');
});

/** ---------------------------------------------------------------- editing */
it('lets an author edit and delete their own review, keeping the average in step', function () {
    $review = reviewBy($this->buyer, ['rating' => 2]);
    app(ProductRatingService::class)->recompute($this->product);

    $this->actingAs($this->buyer)->patch(route('reviews.update', $review), ['rating' => 5, 'comment' => 'Changed my mind, it is great.'])->assertRedirect();
    expect($review->fresh())->rating->toBe(5)->and($review->fresh()->edited_at)->not->toBeNull();
    expect($this->product->fresh()->rating_avg)->toBe(5.0);

    $this->actingAs($this->buyer)->delete(route('reviews.destroy', $review))->assertRedirect();
    expect(ProductReview::count())->toBe(0);
    expect($this->product->fresh())->rating_count->toBe(0)->rating_avg->toBeNull();
});

it('stops anyone else editing or deleting a review', function () {
    $review = reviewBy($this->buyer);

    $this->actingAs($this->seller)->patch(route('reviews.update', $review), ['rating' => 1, 'comment' => 'The seller rewriting it.'])->assertForbidden();
    $this->actingAs($this->seller)->delete(route('reviews.destroy', $review))->assertForbidden();
    $this->actingAs($this->moderator)->delete(route('reviews.destroy', $review))->assertForbidden();

    expect($review->fresh()->comment)->not->toBe('The seller rewriting it.');
});

/** ---------------------------------------------------------------- the average */
it('averages visible reviews, rounds to two places and breaks them down by star', function () {
    foreach ([5, 5, 4] as $rating) {
        reviewBy(User::factory()->create(), ['rating' => $rating]);
    }
    $service = app(ProductRatingService::class);
    $service->recompute($this->product);

    expect($this->product->fresh())->rating_count->toBe(3)->rating_avg->toBe(4.67);
    expect($service->distribution($this->product))->toBe([5 => 2, 4 => 1, 3 => 0, 2 => 0, 1 => 0]);

    $this->get(route('models.show', $this->product))->assertSee('4.7')->assertSee('3 reviews');
});

it('puts the best rated models first and unrated ones last when sorting by top rated', function () {
    $good = Product::factory()->published()->create(['user_id' => $this->seller->id, 'title' => 'Good one', 'rating_avg' => 4.9, 'rating_count' => 8]);
    $fair = Product::factory()->published()->create(['user_id' => $this->seller->id, 'title' => 'Fair one', 'rating_avg' => 3.2, 'rating_count' => 3]);

    $this->get(route('models.index', ['sort' => 'top_rated']))->assertOk()
        ->assertSeeInOrder(['Good one', 'Fair one', 'Oak armchair']);
});

/** ---------------------------------------------------------------- seller reply */
it('lets the seller reply once publicly, edit the reply and remove it, telling the buyer on the first reply', function () {
    $review = reviewBy($this->buyer);

    $this->actingAs($this->seller)->post(route('reviews.reply', $review), ['reply' => 'Thanks, glad you like it!'])->assertRedirect();
    expect($review->fresh()->seller_reply)->toBe('Thanks, glad you like it!');
    Notification::assertSentToTimes($this->buyer, ModelReviewReplyNotification::class, 1);

    $this->actingAs($this->seller)->post(route('reviews.reply', $review), ['reply' => 'Thanks, glad you like it. More coming soon.']);
    expect($review->fresh()->seller_reply)->toBe('Thanks, glad you like it. More coming soon.');
    Notification::assertSentToTimes($this->buyer, ModelReviewReplyNotification::class, 1);

    $this->get(route('models.show', $this->product))->assertSee('Reply from Kevin 3D Studio')->assertSee('More coming soon.');

    $this->actingAs($this->seller)->delete(route('reviews.reply.destroy', $review));
    expect($review->fresh()->seller_reply)->toBeNull();
});

it('only lets the seller of that model reply', function () {
    $review = reviewBy($this->buyer);
    $otherSeller = User::factory()->create();
    SellerProfile::factory()->approved()->create(['user_id' => $otherSeller->id]);

    $this->actingAs($otherSeller)->post(route('reviews.reply', $review), ['reply' => 'Not my model at all.'])->assertSessionHas('error');
    $this->actingAs($this->buyer)->post(route('reviews.reply', $review), ['reply' => 'Replying to myself.'])->assertRedirect();
    $this->actingAs($otherSeller)->delete(route('reviews.reply.destroy', $review))->assertForbidden();

    expect($review->fresh()->seller_reply)->toBeNull();
});

it('does not let a seller reply to a hidden review', function () {
    $review = reviewBy($this->buyer, ['status' => 'hidden', 'hidden_reason' => 'Abusive language.']);

    $this->actingAs($this->seller)->post(route('reviews.reply', $review), ['reply' => 'Replying anyway.'])->assertSessionHas('error');
});

/** ---------------------------------------------------------------- reports and moderation */
it('lets members report a review once, but not their own, and the seller cannot delete or hide it', function () {
    $review = reviewBy($this->buyer);
    $reader = User::factory()->create();

    $this->actingAs($reader)->post(route('reviews.report', $review), ['reason' => 'spam', 'details' => 'Looks like an advert.'])->assertSessionHas('success');
    $this->actingAs($reader)->post(route('reviews.report', $review), ['reason' => 'abusive'])->assertSessionHas('error');
    $this->actingAs($this->buyer)->post(route('reviews.report', $review), ['reason' => 'spam'])->assertSessionHas('error');
    $this->actingAs($reader)->post(route('reviews.report', $review), ['reason' => 'nonsense'])->assertSessionHasErrors('reason');

    expect(ReviewReport::count())->toBe(1);

    $this->actingAs($this->seller)->delete(route('reviews.destroy', $review))->assertForbidden();
    $this->actingAs($this->seller)->post(route('admin.reviews.hide', $review), ['reason' => 'I do not like it.'])->assertForbidden();
    expect($review->fresh()->isVisible())->toBeTrue();
});

it('lets a reviewer hide a review with a reason, which drops it from the average and tells the author', function () {
    $review = reviewBy($this->buyer, ['rating' => 1]);
    reviewBy(User::factory()->create(), ['rating' => 5]);
    $service = app(ProductRatingService::class);
    $service->recompute($this->product);
    expect($this->product->fresh()->rating_avg)->toBe(3.0);

    ReviewReport::create(['review_id' => $review->id, 'user_id' => User::factory()->create()->id, 'reason' => 'abusive', 'status' => 'open']);

    $this->actingAs($this->moderator)->post(route('admin.reviews.hide', $review), ['reason' => 'Abusive language towards the seller.'])->assertSessionHas('success');

    expect($review->fresh())->status->toBe('hidden')->hidden_reason->toBe('Abusive language towards the seller.');
    expect($this->product->fresh())->rating_count->toBe(1)->rating_avg->toBe(5.0);
    expect(ReviewReport::first()->status)->toBe('resolved');
    Notification::assertSentTo($this->buyer, ModelReviewHiddenNotification::class);

    $this->get(route('models.show', $this->product))->assertDontSee($review->comment);
});

it('requires a reason to hide a review', function () {
    $review = reviewBy($this->buyer);

    $this->actingAs($this->moderator)->post(route('admin.reviews.hide', $review), ['reason' => ''])->assertSessionHasErrors('reason');
    $this->actingAs($this->moderator)->post(route('admin.reviews.hide', $review), ['reason' => 'no'])->assertSessionHasErrors('reason');
    expect($review->fresh()->isVisible())->toBeTrue();
});

it('lets a reviewer restore a hidden review, dismiss reports and remove a seller reply', function () {
    $hidden = reviewBy($this->buyer, ['rating' => 2, 'status' => 'hidden', 'hidden_reason' => 'Mistaken.']);
    $this->actingAs($this->moderator)->post(route('admin.reviews.restore', $hidden))->assertSessionHas('success');
    expect($hidden->fresh()->isVisible())->toBeTrue()->and($this->product->fresh()->rating_count)->toBe(1);

    ReviewReport::create(['review_id' => $hidden->id, 'user_id' => User::factory()->create()->id, 'reason' => 'spam', 'status' => 'open']);
    $this->actingAs($this->moderator)->post(route('admin.reviews.dismiss', $hidden));
    expect(ReviewReport::first()->status)->toBe('resolved')->and($hidden->fresh()->isVisible())->toBeTrue();

    $hidden->update(['seller_reply' => 'An inappropriate reply.']);
    $this->actingAs($this->moderator)->delete(route('admin.reviews.reply.remove', $hidden));
    expect($hidden->fresh()->seller_reply)->toBeNull();
});

it('keeps moderation to people with the permission', function () {
    $review = reviewBy($this->buyer);

    $this->actingAs($this->buyer)->get(route('admin.reviews.index'))->assertForbidden();
    $this->actingAs($this->buyer)->post(route('admin.reviews.hide', $review), ['reason' => 'Hiding it myself.'])->assertForbidden();
    $this->actingAs($this->moderator)->get(route('admin.reviews.index'))->assertOk();
});

it('lists reported and hidden reviews for moderators', function () {
    $reported = reviewBy($this->buyer, ['comment' => 'Reported review text here.']);
    ReviewReport::create(['review_id' => $reported->id, 'user_id' => User::factory()->create()->id, 'reason' => 'fake', 'details' => 'Not about this model.', 'status' => 'open']);
    reviewBy(User::factory()->create(), ['comment' => 'Perfectly fine review text.']);
    reviewBy(User::factory()->create(), ['comment' => 'Hidden review text here.', 'status' => 'hidden', 'hidden_reason' => 'Spam.', 'hidden_by' => $this->moderator->id, 'hidden_at' => now()]);

    $this->actingAs($this->moderator)->get(route('admin.reviews.index'))->assertOk()
        ->assertSee('Reported review text here.')->assertSee('Not about this model.')
        ->assertDontSee('Perfectly fine review text.')->assertDontSee('Hidden review text here.');

    $this->actingAs($this->moderator)->get(route('admin.reviews.index', ['status' => 'hidden']))->assertSee('Hidden review text here.')->assertDontSee('Reported review text here.');
    $this->actingAs($this->moderator)->get(route('admin.reviews.index', ['status' => 'all']))->assertSee('Perfectly fine review text.');
});

/** ---------------------------------------------------------------- display */
it('shows the rating on catalogue cards and the model page, but nothing for an unrated model', function () {
    $this->get(route('models.index'))->assertOk()->assertDontSee('out of 5 stars');

    reviewBy($this->buyer, ['rating' => 4]);
    app(ProductRatingService::class)->recompute($this->product);

    $this->get(route('models.index'))->assertSee('4.0 out of 5 stars', false);
    $this->get(route('models.show', $this->product))->assertSee('4.0 out of 5 stars', false);
});

it('formats a public name without the full surname', function (string $name, string $expected) {
    expect((new User(['name' => $name]))->publicName())->toBe($expected);
})->with([
    ['Amina Otieno', 'Amina O.'],
    ['Amina Wanjiku Otieno', 'Amina O.'],
    ['Cher', 'Cher'],
    ['  ', 'A buyer'],
]);

/** ---------------------------------------------------------------- store rating */
function storeReviews(Product $product, array $ratings): void
{
    foreach ($ratings as $rating) {
        reviewBy(User::factory()->create(), ['product_id' => $product->id, 'rating' => $rating]);
    }
    app(ProductRatingService::class)->recompute($product);
}

it('rates a store from all its reviews together, not from each model\'s average', function () {
    $second = Product::factory()->published()->create(['user_id' => $this->seller->id]);

    storeReviews($this->product, [5]);
    storeReviews($second, [3, 3, 3]);

    // (5 + 3 + 3 + 3) / 4 = 3.5. Averaging the two model averages would give 4.0.
    expect($this->store->fresh())->rating_count->toBe(4)->rating_avg->toBe(3.5);
});

it('leaves out hidden reviews and models that are not published or are deleted', function () {
    $other = Product::factory()->published()->create(['user_id' => $this->seller->id]);
    storeReviews($this->product, [5, 5]);
    storeReviews($other, [1, 1]);
    expect($this->store->fresh())->rating_count->toBe(4)->rating_avg->toBe(3.0);

    $other->update(['status' => ProductStatus::Unpublished]);
    expect($this->store->fresh())->rating_count->toBe(2)->rating_avg->toBe(5.0);

    $other->update(['status' => ProductStatus::Published]);
    expect($this->store->fresh()->rating_count)->toBe(4);

    $other->delete();
    expect($this->store->fresh())->rating_count->toBe(2)->rating_avg->toBe(5.0);

    $other->restore();
    expect($this->store->fresh()->rating_count)->toBe(4);

    app(ProductRatingService::class)->hide(ProductReview::where('product_id', $other->id)->first(), $this->moderator, 'Not about the model.');
    expect($this->store->fresh())->rating_count->toBe(3)->rating_avg->toBe(3.67);
});

it('does not mix one seller\'s reviews into another store', function () {
    $rival = SellerProfile::factory()->approved()->create();
    $theirs = Product::factory()->published()->create(['user_id' => $rival->user_id]);

    storeReviews($theirs, [1, 1, 1]);
    storeReviews($this->product, [5, 5, 5]);

    expect($this->store->fresh()->rating_avg)->toBe(5.0)->and($rival->fresh()->rating_avg)->toBe(1.0);
});

it('shows a store rating only from three reviews', function () {
    $second = Product::factory()->published()->create(['user_id' => $this->seller->id]);
    storeReviews($this->product, [5, 4]);
    expect($this->store->fresh()->hasPublicRating())->toBeFalse();

    $this->get(route('sellers.show', $this->store->slug))->assertOk()->assertSee('Not rated yet')->assertDontSee('buyer reviews across all their models');
    $this->get(route('models.show', $this->product))->assertDontSee('4.3 out of 5 stars', false);

    storeReviews($second, [4]);
    expect($this->store->fresh())->hasPublicRating()->toBeTrue()->rating_avg->toBe(4.33);

    // The store's 4.3 is shown beside the model's own 4.5
    $this->get(route('sellers.show', $this->store->slug))->assertSee('Average of 3 buyer reviews across all their models')->assertDontSee('Not rated yet');
    $this->get(route('models.show', $this->product))->assertSee('4.3 out of 5 stars', false)->assertSee('4.5 out of 5 stars', false);
});

it('shows a seller their store rating on their profile, apart from their freelance rating', function () {
    storeReviews($this->product, [5, 5, 4]);

    Volt::actingAs($this->seller)->test('profile.overview')
        ->assertSee('Model store')->assertSee('Kevin 3D Studio')->assertSee('4.7 out of 5 stars', false)->assertSee('No reviews yet');

    Volt::actingAs($this->buyer)->test('profile.overview')->assertDontSee('Model store');
});
