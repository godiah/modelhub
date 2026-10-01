<?php

use App\Enums\NotificationCategory;
use App\Enums\SellerStatus;
use App\Models\SellerProfile;
use App\Models\User;
use App\Notifications\SellerApplicationSubmittedNotification;
use App\Notifications\SellerReviewedNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Notification;

/*
 * Becoming a seller of 3D models: the member's application, and the reviewer's queue. Every page test asserts OK
 * first so a 500 cannot satisfy loose text assertions.
 */

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Notification::fake();

    $this->member = User::factory()->create(['name' => 'Kevin Mwangi']);
    $this->admin = User::factory()->create(['name' => 'Rita Reviewer']);
    $this->admin->assignRole('admin');
});

function sellerApplication(array $overrides = []): array
{
    return array_merge([
        'display_name' => 'Kevin 3D Studio',
        'bio' => str_repeat('I model furniture and interiors for visualisation. ', 2),
        'focus' => 'Furniture and interior props as FBX and Blender files.',
        'portfolio_url' => 'https://portfolio.example.test/kevin',
        'terms' => '1',
    ], $overrides);
}

/** ---------------------------------------------------------------- the member's side */
it('keeps the seller page for signed-in members', function () {
    $this->get(route('seller.index'))->assertRedirect(route('login'));
});

it('invites a new member to apply', function () {
    $this->actingAs($this->member)->get(route('seller.index'))
        ->assertOk()
        ->assertSee('Sell your 3D models')
        ->assertSee('Apply to sell')
        ->assertSee(route('seller.apply'), false)
        ->assertSee(route('legal.terms'), false);
});

it('submits an application as pending and tells the reviewers', function () {
    $this->actingAs($this->member)->post(route('seller.apply'), sellerApplication())
        ->assertRedirect(route('seller.index'));

    $profile = $this->member->fresh()->sellerProfile;
    expect($profile->status)->toBe(SellerStatus::Pending)
        ->and($profile->display_name)->toBe('Kevin 3D Studio')
        ->and($profile->submitted_at)->not->toBeNull()
        ->and($profile->terms_accepted_at)->not->toBeNull();

    Notification::assertSentTo($this->admin, SellerApplicationSubmittedNotification::class);
    Notification::assertNotSentTo($this->member, SellerApplicationSubmittedNotification::class);

    $this->get(route('seller.index'))->assertOk()->assertSee('Pending review')->assertSee('Kevin 3D Studio')->assertDontSee('Apply to sell');
});

it('validates the application', function () {
    $this->actingAs($this->member);

    $this->post(route('seller.apply'), sellerApplication(['bio' => 'Too short']))->assertSessionHasErrors('bio');
    $this->post(route('seller.apply'), sellerApplication(['display_name' => '']))->assertSessionHasErrors('display_name');
    $this->post(route('seller.apply'), sellerApplication(['focus' => 'short']))->assertSessionHasErrors('focus');
    $this->post(route('seller.apply'), sellerApplication(['portfolio_url' => 'javascript:alert(1)']))->assertSessionHasErrors('portfolio_url');
    $this->post(route('seller.apply'), sellerApplication(['portfolio_url' => 'not a url']))->assertSessionHasErrors('portfolio_url');
    $this->post(route('seller.apply'), sellerApplication(['terms' => null]))->assertSessionHasErrors('terms');

    expect(SellerProfile::count())->toBe(0);
});

it('keeps store names unique', function () {
    SellerProfile::factory()->create(['display_name' => 'Taken Name']);

    $this->actingAs($this->member)->post(route('seller.apply'), sellerApplication(['display_name' => 'Taken Name']))
        ->assertSessionHasErrors('display_name');
});

it('allows only one application at a time and none while suspended', function () {
    $this->actingAs($this->member);
    $this->post(route('seller.apply'), sellerApplication());
    $this->post(route('seller.apply'), sellerApplication(['display_name' => 'Another Name']))->assertRedirect()->assertSessionHas('error');
    expect(SellerProfile::count())->toBe(1)->and($this->member->sellerProfile->display_name)->toBe('Kevin 3D Studio');

    $this->member->sellerProfile->update(['status' => SellerStatus::Suspended, 'review_notes' => 'Breach']);
    $this->post(route('seller.apply'), sellerApplication(['display_name' => 'Back Again']))->assertRedirect()->assertSessionHas('error');
    expect($this->member->sellerProfile->fresh()->status)->toBe(SellerStatus::Suspended);
});

it('lets a rejected applicant read the reason, improve and apply again', function () {
    SellerProfile::factory()->rejected('Please link some of your work.')->create(['user_id' => $this->member->id, 'display_name' => 'Kevin 3D Studio']);

    $this->actingAs($this->member)->get(route('seller.index'))
        ->assertOk()
        ->assertSee('Not approved')
        ->assertSee('Please link some of your work.')
        ->assertSee('Update and send again');

    $this->post(route('seller.apply'), sellerApplication(['display_name' => 'Kevin 3D Studio']))->assertRedirect(route('seller.index'));

    $profile = $this->member->fresh()->sellerProfile;
    expect(SellerProfile::count())->toBe(1)
        ->and($profile->status)->toBe(SellerStatus::Pending)
        ->and($profile->review_notes)->toBeNull()
        ->and($profile->reviewed_at)->toBeNull();
});

it('shows an approved seller what happens next', function () {
    SellerProfile::factory()->approved()->create(['user_id' => $this->member->id]);

    $this->actingAs($this->member)->get(route('seller.index'))
        ->assertOk()
        ->assertSee('Approved')
        ->assertSee('You are approved to sell')
        ->assertDontSee('Apply to sell');

    expect($this->member->fresh()->isApprovedSeller())->toBeTrue();
});

it('shows a suspended seller the reason and no form', function () {
    SellerProfile::factory()->suspended('Stolen assets reported.')->create(['user_id' => $this->member->id]);

    $this->actingAs($this->member)->get(route('seller.index'))
        ->assertOk()->assertSee('Suspended')->assertSee('Stolen assets reported.')->assertDontSee('Send application');
});

it('adds Sell models to the sidebar', function () {
    $this->actingAs($this->member)->get(route('dashboard'))->assertOk()->assertSee(route('seller.index'), false)->assertSee('Sell models');
});

/** ---------------------------------------------------------------- the reviewer's side */
it('keeps the review queue for staff with the permission', function () {
    $this->get(route('admin.sellers.index'))->assertRedirect(route('login'));
    $this->actingAs($this->member)->get(route('admin.sellers.index'))->assertForbidden();
    $this->actingAs($this->admin)->get(route('admin.sellers.index'))->assertOk();
});

it('lists applications with status counts, pending first, and filters them', function () {
    SellerProfile::factory()->create(['display_name' => 'Pending Studio', 'bio' => 'Bio of the pending studio, long enough to be real text here.']);
    SellerProfile::factory()->approved()->create(['display_name' => 'Approved Studio']);
    SellerProfile::factory()->rejected()->create(['display_name' => 'Rejected Studio']);

    $this->actingAs($this->admin)->get(route('admin.sellers.index'))
        ->assertOk()
        ->assertSee('Pending Studio')
        ->assertSee('Bio of the pending studio')
        ->assertDontSee('Approved Studio')
        ->assertSeeInOrder(['Pending', '1', 'Approved', '1', 'Not approved', '1', 'Suspended', '0', 'All', '3']);

    $this->get(route('admin.sellers.index', ['status' => 'approved']))->assertOk()->assertSee('Approved Studio')->assertDontSee('Pending Studio');
    $this->get(route('admin.sellers.index', ['status' => 'all']))->assertOk()->assertSee('Pending Studio')->assertSee('Approved Studio')->assertSee('Rejected Studio');
    $this->get(route('admin.sellers.index', ['status' => 'bogus']))->assertOk()->assertSee('Pending Studio');
});

it('approves a pending application and tells the member', function () {
    $seller = SellerProfile::factory()->create(['user_id' => $this->member->id]);

    $this->actingAs($this->admin)->patch(route('admin.sellers.review', [$seller, 'approve']))->assertRedirect();

    $seller->refresh();
    expect($seller->status)->toBe(SellerStatus::Approved)
        ->and($seller->reviewed_by)->toBe($this->admin->id)
        ->and($seller->reviewed_at)->not->toBeNull();
    Notification::assertSentTo($this->member, SellerReviewedNotification::class, fn ($n) => $n->seller->status === SellerStatus::Approved);
});

it('rejects with a required reason that the member can read', function () {
    $seller = SellerProfile::factory()->create(['user_id' => $this->member->id]);
    $this->actingAs($this->admin);

    $this->patch(route('admin.sellers.review', [$seller, 'reject']))->assertRedirect()->assertSessionHas('error');
    expect($seller->fresh()->status)->toBe(SellerStatus::Pending);

    $this->patch(route('admin.sellers.review', [$seller, 'reject']), ['notes' => 'We could not verify your work.'])->assertRedirect()->assertSessionHas('success');
    expect($seller->fresh()->status)->toBe(SellerStatus::Rejected)->and($seller->fresh()->review_notes)->toBe('We could not verify your work.');
    Notification::assertSentTo($this->member, SellerReviewedNotification::class, fn ($n) => $n->seller->review_notes === 'We could not verify your work.');
});

it('suspends an approved seller with a reason and can reinstate them', function () {
    $seller = SellerProfile::factory()->approved()->create(['user_id' => $this->member->id]);
    $this->actingAs($this->admin);

    $this->patch(route('admin.sellers.review', [$seller, 'suspend']))->assertSessionHas('error');
    expect($seller->fresh()->status)->toBe(SellerStatus::Approved);

    $this->patch(route('admin.sellers.review', [$seller, 'suspend']), ['notes' => 'Reported for stolen assets.']);
    expect($seller->fresh()->status)->toBe(SellerStatus::Suspended);
    expect($this->member->fresh()->isApprovedSeller())->toBeFalse();

    $this->patch(route('admin.sellers.review', [$seller, 'approve']));
    expect($seller->fresh()->status)->toBe(SellerStatus::Approved)->and($seller->fresh()->review_notes)->toBeNull();
});

it('refuses decisions that do not fit the current state', function () {
    $approved = SellerProfile::factory()->approved()->create();
    $this->actingAs($this->admin);

    $this->patch(route('admin.sellers.review', [$approved, 'reject']), ['notes' => 'Changed my mind'])->assertSessionHas('error');
    $this->patch(route('admin.sellers.review', [$approved, 'approve']))->assertSessionHas('error');
    expect($approved->fresh()->status)->toBe(SellerStatus::Approved);

    $this->patch('/admin/sellers/'.$approved->id.'/delete')->assertNotFound();
});

it('does not let ordinary members review sellers', function () {
    $seller = SellerProfile::factory()->create();

    $this->actingAs($this->member)->patch(route('admin.sellers.review', [$seller, 'approve']))->assertForbidden();

    expect($seller->fresh()->status)->toBe(SellerStatus::Pending);
});

it('shows reviewers the Seller applications entry only if they hold the permission', function () {
    $this->actingAs($this->admin)->get(route('dashboard'))->assertOk()->assertSee(route('admin.sellers.index'), false);
    $this->actingAs($this->member)->get(route('dashboard'))->assertOk()->assertDontSee(route('admin.sellers.index'), false);
});

it('files the notifications under Marketplace and presents them sensibly', function () {
    expect(NotificationCategory::forType(SellerReviewedNotification::class))->toBe(NotificationCategory::Marketplace)
        ->and(NotificationCategory::forType(SellerApplicationSubmittedNotification::class))->toBe(NotificationCategory::Marketplace);

    expect(SellerReviewedNotification::present(['status' => 'approved', 'display_name' => 'Kevin 3D Studio'])['title'])->toBe('You are approved to sell')
        ->and(SellerReviewedNotification::present(['status' => 'rejected', 'notes' => 'More detail please'])['content'])->toContain('More detail please');
});
