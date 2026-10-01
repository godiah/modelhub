<?php

use App\Enums\DisputeStatus;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ReviewReport;
use App\Models\SellerProfile;
use App\Models\Staff;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

/*
 * Every admin queue, in every status filter, with records that have already been decided on. Lazy loading is blocked
 * outside production, so a relation a page reads but its controller forgot to load (a decision's reviewer, who hid a
 * review, who resolved a dispute) makes the page throw here, instead of on the first real decision.
 */

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = staffWith('Super admin');
});

function decidedRecords(Staff $reviewer): void
{
    $seller = SellerProfile::factory()->approved()->create(['reviewed_by' => $reviewer->id, 'review_notes' => 'Looks good.']);
    foreach ([SellerProfile::factory()->rejected(), SellerProfile::factory()->suspended()] as $factory) {
        $factory->create(['reviewed_by' => $reviewer->id]);
    }

    $products = [
        Product::factory()->published()->create(['user_id' => $seller->user_id, 'reviewed_by' => $reviewer->id]),
        Product::factory()->inReview()->create(['user_id' => $seller->user_id]),
        Product::factory()->create(['user_id' => $seller->user_id, 'status' => 'rejected', 'reviewed_at' => now(), 'reviewed_by' => $reviewer->id, 'review_notes' => 'Needs better previews.']),
        Product::factory()->create(['user_id' => $seller->user_id, 'status' => 'unpublished', 'reviewed_at' => now(), 'reviewed_by' => $reviewer->id, 'review_notes' => 'Taken down.']),
        Product::factory()->create(['user_id' => $seller->user_id]),
    ];

    $hidden = ProductReview::factory()->hidden('Abusive language.')->create(['product_id' => $products[0]->id, 'hidden_by' => $reviewer->id, 'seller_reply' => 'Thanks.', 'seller_replied_at' => now()]);
    $reported = ProductReview::factory()->create(['product_id' => $products[0]->id]);
    ReviewReport::create(['review_id' => $reported->id, 'user_id' => User::factory()->create()->id, 'reason' => 'spam', 'status' => 'open']);
    ReviewReport::create(['review_id' => $hidden->id, 'user_id' => User::factory()->create()->id, 'reason' => 'abusive', 'status' => 'resolved', 'resolved_by' => $reviewer->id, 'resolved_at' => now()]);
}

it('renders the model queue in every status with decided models', function (string $status) {
    decidedRecords($this->admin);

    $this->actingAs($this->admin, 'staff')->get(route('admin.models.index', ['status' => $status]))->assertOk();
})->with(['in_review', 'published', 'rejected', 'unpublished', 'draft', 'all']);

it('shows who made the last decision on a model, and when the reviewer is gone', function () {
    $reviewer = staffWith('Marketplace moderator');
    $reviewer->update(['name' => 'Rita Reviewer']);
    $seller = SellerProfile::factory()->approved()->create();
    Product::factory()->published()->create(['user_id' => $seller->user_id, 'title' => 'Decided by Rita', 'reviewed_by' => $reviewer->id]);
    Product::factory()->published()->create(['user_id' => $seller->user_id, 'title' => 'Decided by nobody now', 'reviewed_by' => null]);

    $this->actingAs($this->admin, 'staff')->get(route('admin.models.index', ['status' => 'published']))->assertOk()
        ->assertSee('by Rita Reviewer')->assertSee('by a reviewer');
});

it('renders the seller queue in every status with decided applications', function (string $status) {
    decidedRecords($this->admin);

    $this->actingAs($this->admin, 'staff')->get(route('admin.sellers.index', ['status' => $status]))->assertOk();
})->with(['pending', 'approved', 'rejected', 'suspended', 'all']);

it('renders the review moderation queue in every filter with hidden, reported and replied reviews', function (string $status) {
    decidedRecords($this->admin);

    $this->actingAs($this->admin, 'staff')->get(route('admin.reviews.index', ['status' => $status]))->assertOk();
})->with(['reported', 'hidden', 'all']);

it('renders the disputes queue in every status with assigned and resolved disputes', function (string $status) {
    ['dispute' => $pending] = makeDisputedEngagement();
    ['dispute' => $assigned] = makeDisputedEngagement();
    ['dispute' => $resolved] = makeDisputedEngagement();
    $assigned->assignAdmin($this->admin->id);
    $resolved->update(['status' => DisputeStatus::Resolved, 'resolved_at' => now(), 'resolved_by' => $this->admin->id, 'resolution_amount' => 100, 'admin_assigned' => $this->admin->id]);

    $this->actingAs($this->admin, 'staff')->get(route('admin.disputes.index', ['status' => $status]))->assertOk();
})->with(['pending', 'under_review', 'resolved', 'all']);

it('renders the staff page', function () {
    $this->actingAs($this->admin, 'staff')->get(route('admin.staff.index'))->assertOk();
});
