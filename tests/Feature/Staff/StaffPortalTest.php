<?php

use App\Models\JobPaymentDispute;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ReviewReport;
use App\Models\SellerProfile;
use App\Models\Staff;
use App\Models\StaffActivity;
use App\Models\User;
use App\Notifications\ProductSubmittedNotification;
use App\Services\Marketplace\ProductService;
use App\Support\Avatars;
use App\Support\Navigation\StaffMenu;
use App\Support\Staff\StaffAudit;
use Illuminate\Support\Facades\Hash;

/*
 * The staff portal's own shell and pages: the dashboard of queues, a menu limited to what the person may do, notifications,
 * their account, and the activity log.
 */

function menuLabels(Staff $staff): array
{
    return collect(StaffMenu::for($staff))->flatMap->items->pluck('label')->all();
}

/** ---------------------------------------------------------------- the menu */
it('shows each staff member only the menu entries their roles allow', function () {
    $this->actingAs(staffWith('Super admin'), 'staff')->get(route('admin.dashboard')); // the request the menu reads the current route from

    expect(menuLabels(staffWith('Super admin')))->toBe(['Dashboard', 'Notifications', 'Model reviews', 'Seller applications', 'Review reports', 'Payment disputes', 'Staff', 'Roles', 'Activity log'])
        ->and(menuLabels(staffWith('Marketplace moderator')))->toBe(['Dashboard', 'Notifications', 'Model reviews', 'Seller applications', 'Review reports'])
        ->and(menuLabels(staffWith('Dispute manager')))->toBe(['Dashboard', 'Notifications', 'Payment disputes'])
        ->and(menuLabels(staffWith('Support')))->toBe(['Dashboard', 'Notifications', 'Payment disputes'])
        ->and(menuLabels(staffWith()))->toBe(['Dashboard', 'Notifications']);
});

it('puts live queue counts on the menu entries', function () {
    Product::factory()->inReview()->count(2)->create();
    SellerProfile::factory()->count(3)->create();

    expect(StaffMenu::count('models'))->toBe(2)->and(StaffMenu::count('sellers'))->toBe(3)->and(StaffMenu::count('reports'))->toBe(0)->and(StaffMenu::count('disputes'))->toBe(0);

    $html = $this->actingAs(staffWith('Marketplace moderator'), 'staff')->get(route('admin.dashboard'))->assertOk()->getContent();
    expect($html)->toMatch('/Model reviews<\/span>\s*<span[^>]*>2</')->toMatch('/Seller applications<\/span>\s*<span[^>]*>3</');
});

it('renders the staff shell, not the member shell', function () {
    $this->actingAs(staffWith('Support'), 'staff')->get(route('admin.dashboard'))->assertOk()
        ->assertSee('Staff portal')->assertSee('aria-label="Staff navigation"', false)->assertSee('noindex', false)
        ->assertDontSee('Browse models')->assertDontSee('Post a project')->assertDontSee('aria-label="Main navigation"', false)
        // The menus, drawer and dialogs run on Alpine, which these pages only get by loading Livewire's scripts
        ->assertSee('/livewire/livewire', false);
});

/** ---------------------------------------------------------------- the dashboard */
it('shows a dashboard of the queues each person may work, with how long the oldest has waited', function () {
    Product::factory()->inReview()->create(['submitted_at' => now()->subDays(4)]);
    SellerProfile::factory()->create(['submitted_at' => now()->subDays(2)]);

    $this->actingAs(staffWith('Marketplace moderator'), 'staff')->get(route('admin.dashboard'))->assertOk()
        ->assertSee('Model reviews')->assertSee('models are waiting for a decision')->assertSee('oldest from 4 days ago')
        ->assertSee('Seller applications')->assertSee('Review reports')->assertSee('No reviews are reported.')
        ->assertDontSee('Payment disputes');

    $this->actingAs(staffWith('Support'), 'staff')->get(route('admin.dashboard'))->assertOk()->assertSee('Payment disputes')->assertDontSee('Model reviews');
});

it('welcomes a person with no role and says what to do', function () {
    $this->actingAs(Staff::factory()->create(['name' => 'Newcomer Staff']), 'staff')->get(route('admin.dashboard'))->assertOk()
        ->assertSee('Welcome, Newcomer')->assertSee('no role yet');
});

it('lists the disputes the person is handling, and the recent activity only for those who may see the log', function () {
    $manager = staffWith('Dispute manager', 'Marketplace moderator');
    ['dispute' => $dispute, 'application' => $application] = makeDisputedEngagement();
    $dispute->assignAdmin($manager->id);
    StaffAudit::log('model.published', 'Published "Oak chair"', staffId: $manager->id);

    $this->actingAs($manager, 'staff')->get(route('admin.dashboard'))->assertOk()->assertSee('Disputes you are handling')->assertSee($application->job->title)->assertDontSee('Recent staff activity');

    $this->actingAs(staffWith('Super admin'), 'staff')->get(route('admin.dashboard'))->assertOk()->assertSee('Recent staff activity')->assertSee('published "Oak chair"');
});

it('counts reports and disputes on the dashboard', function () {
    $review = ProductReview::factory()->create();
    ReviewReport::create(['review_id' => $review->id, 'user_id' => User::factory()->create()->id, 'reason' => 'spam', 'status' => 'open']);
    makeDisputedEngagement();

    $this->actingAs(staffWith('Super admin'), 'staff')->get(route('admin.dashboard'))->assertOk()->assertSee('2 items are waiting for you');
    expect(StaffMenu::count('reports'))->toBe(1)->and(StaffMenu::count('disputes'))->toBe(JobPaymentDispute::count());
});

/** ---------------------------------------------------------------- notifications */
it('lists a staff notification in the portal, and notifies only active staff who can review models', function () {
    $moderator = staffWith('Marketplace moderator');
    $deactivated = staffWith('Marketplace moderator');
    $deactivated->update(['is_active' => false]);
    staffWith('Support');
    $product = Product::factory()->create(['title' => 'Oak chair model']);

    // The people a submission notifies (see ProductService::submit): active staff holding the permission
    expect(Staff::permission('review models')->where('is_active', true)->pluck('id')->all())->toBe([$moderator->id]);

    $moderator->notify(new ProductSubmittedNotification($product->loadMissing('seller')));

    $this->actingAs($moderator, 'staff')->get(route('admin.notifications.index'))->assertOk()->assertSee('Oak chair model')
        ->assertSee(route('admin.notifications.open', $moderator->notifications()->first()->id), false);
});

it('opens a notification, marks it read and goes where it points; and marks all read', function () {
    $moderator = staffWith('Marketplace moderator');
    $product = Product::factory()->create();
    $moderator->notify(new ProductSubmittedNotification($product->loadMissing('seller')));
    $moderator->notify(new ProductSubmittedNotification($product));
    $this->actingAs($moderator, 'staff');
    expect($moderator->unreadNotifications()->count())->toBe(2);

    $this->get(route('admin.notifications.open', $moderator->notifications()->first()->id))->assertRedirect(route('admin.models.index', ['status' => 'in_review']));
    expect($moderator->fresh()->unreadNotifications()->count())->toBe(1);

    $this->post(route('admin.notifications.read-all'))->assertRedirect();
    expect($moderator->fresh()->unreadNotifications()->count())->toBe(0);

    // Someone else's notification is not reachable
    $other = staffWith('Marketplace moderator');
    $other->notify(new ProductSubmittedNotification($product));
    $this->get(route('admin.notifications.open', $other->notifications()->first()->id))->assertNotFound();
});

/** ---------------------------------------------------------------- their account */
it('lets staff change their name, avatar and password, and logs the password change', function () {
    $staff = staffWith('Support');
    $this->actingAs($staff, 'staff');

    $this->get(route('admin.account.edit'))->assertOk()->assertSee('Your account')->assertSee('Choose an avatar');

    $this->patch(route('admin.account.update'), ['name' => 'Renamed Person'])->assertSessionHas('success');
    expect($staff->fresh()->name)->toBe('Renamed Person');

    $this->patch(route('admin.account.avatar'), ['avatar' => 'lorelei/mia'])->assertSessionHas('success');
    $this->patch(route('admin.account.avatar'), ['avatar' => 'bottts/forge'])->assertSessionHasErrors('avatar');
    expect($staff->fresh()->avatar)->toBe('lorelei/mia');

    $this->put(route('admin.account.password'), ['current_password' => 'wrong', 'password' => 'a-new-passphrase-42', 'password_confirmation' => 'a-new-passphrase-42'])->assertSessionHasErrors('current_password');
    $this->put(route('admin.account.password'), ['current_password' => 'password', 'password' => 'short', 'password_confirmation' => 'short'])->assertSessionHasErrors('password');
    $this->put(route('admin.account.password'), ['current_password' => 'password', 'password' => 'a-new-passphrase-42', 'password_confirmation' => 'a-new-passphrase-42'])->assertSessionHas('success');

    expect(Hash::check('a-new-passphrase-42', $staff->fresh()->password))->toBeTrue()
        ->and(StaffActivity::where('action', 'staff.password-changed')->exists())->toBeTrue();
});

it('gives staff an avatar from the people catalogue when their account is created', function () {
    expect(Avatars::isValid('people', Staff::factory()->create()->avatar))->toBeTrue();
});

/** ---------------------------------------------------------------- the activity log */
it('keeps the activity log to people who may view it', function () {
    $this->actingAs(staffWith('Support'), 'staff')->get(route('admin.activity.index'))->assertForbidden();
    $this->actingAs(staffWith('Super admin'), 'staff')->get(route('admin.activity.index'))->assertOk()->assertSee('Activity log');
});

it('records moderation decisions with who, what and why, and shows them filtered', function () {
    $moderator = staffWith('Marketplace moderator');
    $this->actingAs($moderator, 'staff');
    $product = Product::factory()->inReview()->create(['title' => 'Rusty gate model']);
    SellerProfile::factory()->approved()->create(['user_id' => $product->user_id]);
    $applicant = SellerProfile::factory()->create(['display_name' => 'Kijani Studio']);

    $this->patch(route('admin.models.review', [$product, 'reject']), ['notes' => 'Add renders from three angles.']);
    $this->patch(route('admin.sellers.review', [$applicant, 'approve']));

    expect(StaffActivity::where('staff_id', $moderator->id)->orderBy('id')->pluck('action')->all())->toBe(['model.sent-back', 'seller.approved']);

    $viewer = staffWith('Super admin');
    $this->actingAs($viewer, 'staff')->get(route('admin.activity.index'))->assertOk()
        ->assertSee($moderator->name)->assertSee('asked for changes to &quot;Rusty gate model&quot;', false)->assertSee('Add renders from three angles.')->assertSee('approved Kijani Studio');

    $this->get(route('admin.activity.index', ['area' => 'seller']))->assertSee('Kijani Studio')->assertDontSee('Rusty gate model');
    $this->get(route('admin.activity.index', ['staff' => $moderator->id]))->assertSee('Kijani Studio');
    $this->get(route('admin.activity.index', ['staff' => $viewer->id]))->assertDontSee('Kijani Studio')->assertSee('No activity matches this filter.');
    $this->get(route('admin.activity.index', ['area' => 'bogus']))->assertOk()->assertSee('Kijani Studio');
});

it('logs review moderation and model take-downs too', function () {
    $moderator = staffWith('Marketplace moderator');
    $this->actingAs($moderator, 'staff');
    $review = ProductReview::factory()->create();

    $this->post(route('admin.reviews.hide', $review), ['reason' => 'Abusive language.']);
    $this->post(route('admin.reviews.restore', $review));

    $live = Product::factory()->published()->create();
    SellerProfile::factory()->approved()->create(['user_id' => $live->user_id]);
    $this->patch(route('admin.models.review', [$live, 'takedown']), ['notes' => 'Copied work.']);

    expect(StaffActivity::where('staff_id', $moderator->id)->orderBy('id')->pluck('action')->all())->toBe(['review.hidden', 'review.restored', 'model.taken-down']);
});

it('never edits or removes a log entry, and keeps the entry if the person is later deleted', function () {
    $staff = staffWith('Support');
    $entry = StaffAudit::log('dispute.assigned', 'Took on dispute #1', staffId: $staff->id);

    expect($entry->updated_at ?? null)->toBeNull();
    $staff->delete();

    expect($entry->fresh())->not->toBeNull()->staff_id->toBeNull();
});

/** ---------------------------------------------------------------- previews and the member app */
it('lets staff who review models preview an unpublished model on the public page, and nobody else without an account', function () {
    $product = Product::factory()->inReview()->create(['title' => 'Waiting chair model']);
    SellerProfile::factory()->approved()->create(['user_id' => $product->user_id]);

    $this->get(route('models.show', $product))->assertNotFound();
    $this->actingAs(staffWith('Support'), 'staff')->get(route('models.show', $product))->assertNotFound();
    $this->actingAs(staffWith('Marketplace moderator'), 'staff')->get(route('models.show', $product))->assertOk()->assertSee('Waiting chair model')->assertSee('Preview');
});

it('does not let staff use member pages with a staff session', function () {
    $this->actingAs(staffWith('Super admin'), 'staff')->get(route('dashboard'))->assertRedirect(route('login'));
    $this->get('/sell/models')->assertRedirect(route('login'));
});
