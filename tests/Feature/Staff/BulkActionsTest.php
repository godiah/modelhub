<?php

use App\Enums\ProductStatus;
use App\Enums\SellerStatus;
use App\Models\ModelJob;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ReviewReport;
use App\Models\SellerProfile;
use App\Models\Staff;
use App\Models\StaffActivity;
use App\Models\User;
use App\Notifications\AccountReinstatedNotification;
use App\Notifications\AccountSuspendedNotification;
use App\Notifications\ProductSubmittedNotification;
use App\Notifications\ProjectTakenDownNotification;
use App\Support\Staff\BulkActions;
use Illuminate\Support\Facades\Notification;

/*
 * Bulk actions on the staff lists: the one-at-a-time action done to the ticked items, with the same permission, checks, audit and
 * notifications, one shared reason where the affected person is told why, and a report of what was skipped.
 */

function bulk(string $action, array $ids, array $extra = [])
{
    $response = test()->post(route('admin.bulk', $action), ['ids' => $ids] + $extra);

    // A bulk run turns an unexpected exception into a "skipped" so one bad item cannot stop the rest; in a test that must never happen
    $unexpected = collect(session('bulk_result.skipped', []))->firstWhere('why', 'Something went wrong, so it was left as it was.');
    expect($unexpected)->toBeNull('A bulk item threw an exception: '.json_encode($unexpected));

    return $response;
}

/** ---------------------------------------------------------------- model reviews */
it('publishes the ticked models, skips the ones that cannot be, and logs each one and the batch', function () {
    $moderator = staffWith('Marketplace moderator');
    $this->actingAs($moderator, 'staff');
    $waiting = Product::factory()->inReview()->count(3)->create();
    $draft = Product::factory()->create(['status' => ProductStatus::Draft]);

    bulk('model.publish', [...$waiting->pluck('id')->all(), $draft->id])->assertRedirect()->assertSessionHas('bulk_result');

    expect($waiting->fresh()->pluck('status')->unique()->all())->toBe([ProductStatus::Published])->and($draft->fresh()->status)->toBe(ProductStatus::Draft);

    $result = session('bulk_result');
    expect($result['done'])->toHaveCount(3)->and($result['skipped'])->toHaveCount(1)->and($result['skipped'][0]['name'])->toBe($draft->title);
    expect(StaffActivity::where('action', 'model.published')->where('staff_id', $moderator->id)->count())->toBe(3);
    $batch = StaffActivity::where('action', 'bulk.model.publish')->sole();
    expect($batch->summary)->toBe('Bulk "Publish": 3 done, 1 skipped')->and($batch->details['skipped'][0]['name'])->toBe($draft->title);
});

it('sends models back with one shared reason, which is required', function () {
    $this->actingAs(staffWith('Marketplace moderator'), 'staff');
    $products = Product::factory()->inReview()->count(2)->create();

    bulk('model.reject', $products->pluck('id')->all())->assertSessionHasErrors('reason');
    bulk('model.reject', $products->pluck('id')->all(), ['reason' => 'abc'])->assertSessionHasErrors('reason');
    expect($products->fresh()->pluck('status')->unique()->all())->toBe([ProductStatus::InReview]);

    bulk('model.reject', $products->pluck('id')->all(), ['reason' => 'Add clearer previews of the topology.'])->assertSessionHasNoErrors();

    expect($products->fresh()->pluck('status')->unique()->all())->toBe([ProductStatus::Rejected])
        ->and(StaffActivity::where('action', 'model.sent-back')->count())->toBe(2)
        ->and(StaffActivity::where('action', 'model.sent-back')->first()->details['notes'])->toBe('Add clearer previews of the topology.');
});

/** ---------------------------------------------------------------- seller applications */
it('approves or rejects several applications at once', function () {
    $this->actingAs(staffWith('Marketplace moderator'), 'staff');
    $toApprove = SellerProfile::factory()->count(2)->create();
    $toReject = SellerProfile::factory()->count(2)->create();

    bulk('seller.approve', $toApprove->pluck('id')->all())->assertSessionHasNoErrors();
    bulk('seller.reject', $toReject->pluck('id')->all(), ['reason' => 'Portfolio links do not work.']);

    expect($toApprove->fresh()->pluck('status')->unique()->all())->toBe([SellerStatus::Approved])->and($toReject->fresh()->pluck('status')->unique()->all())->toBe([SellerStatus::Rejected])
        ->and(StaffActivity::where('action', 'seller.approved')->count())->toBe(2)->and(StaffActivity::where('action', 'seller.rejected')->count())->toBe(2);

    // Rejecting what is already approved is skipped, with the service's own reason
    bulk('seller.reject', [$toApprove->first()->id], ['reason' => 'Changed our mind.']);
    expect(session('bulk_result')['skipped'][0]['why'])->toContain('so that action is not available')->and($toApprove->first()->fresh()->status)->toBe(SellerStatus::Approved);
});

/** ---------------------------------------------------------------- review reports */
it('dismisses the reports on several reviews, skipping ones with none, and hides reviews with a shared reason', function () {
    $this->actingAs(staffWith('Marketplace moderator'), 'staff');
    $reported = collect(range(1, 2))->map(fn () => openReport());
    $clean = ProductReview::factory()->create();

    bulk('review.dismiss', [...$reported->pluck('id')->all(), $clean->id]);

    expect(session('bulk_result')['done'])->toHaveCount(2)->and(session('bulk_result')['skipped'][0]['why'])->toBe('It has no open reports.')
        ->and(ReviewReport::where('status', 'open')->count())->toBe(0)->and(StaffActivity::where('action', 'review.reports-dismissed')->count())->toBe(2);

    $toHide = collect(range(1, 2))->map(fn () => openReport());
    bulk('review.hide', $toHide->pluck('id')->all())->assertSessionHasErrors('reason');
    bulk('review.hide', $toHide->pluck('id')->all(), ['reason' => 'Contains personal contact details.']);

    expect(ProductReview::whereKey($toHide->pluck('id'))->get()->every(fn ($review) => $review->hidden_at !== null))->toBeTrue()->and(StaffActivity::where('action', 'review.hidden')->count())->toBe(2);
});

/** ---------------------------------------------------------------- disputes */
it('takes on unassigned disputes, but never takes one off a colleague or reassigns your own', function () {
    $me = staffWith('Dispute manager');
    $colleague = staffWith('Dispute manager');
    $this->actingAs($me, 'staff');
    ['dispute' => $free] = makeDisputedEngagement();
    ['dispute' => $theirs] = makeDisputedEngagement();
    ['dispute' => $mine] = makeDisputedEngagement();
    $theirs->assignAdmin($colleague->id);
    $mine->assignAdmin($me->id);

    bulk('dispute.assign', [$free->id, $theirs->id, $mine->id]);

    $skipped = collect(session('bulk_result')['skipped'])->pluck('why')->all();
    expect($free->fresh()->admin_assigned)->toBe($me->id)->and($theirs->fresh()->admin_assigned)->toBe($colleague->id)
        ->and($skipped)->toContain($colleague->name.' is already handling it.')->toContain('You are already handling it.')->and(session('bulk_result')['done'])->toHaveCount(1)
        ->and(StaffActivity::where('action', 'dispute.assigned')->where('staff_id', $me->id)->count())->toBe(1);
});

/** ---------------------------------------------------------------- members */
it('suspends and reinstates members in bulk, emailing each one, with one shared reason', function () {
    Notification::fake();
    $this->actingAs(staffWith('Platform manager'), 'staff');
    $members = User::factory()->count(3)->create();
    $already = User::factory()->create(['suspended_at' => now()]);

    bulk('member.suspend', [...$members->pluck('id')->all(), $already->id])->assertSessionHasErrors('reason');
    bulk('member.suspend', [...$members->pluck('id')->all(), $already->id], ['reason' => 'Repeated spam in project posts.']);

    expect($members->fresh()->every(fn ($m) => $m->isSuspended()))->toBeTrue()->and(session('bulk_result')['skipped'][0]['why'])->toBe('This account is already suspended.')
        ->and($members->first()->fresh()->suspended_reason)->toBe('Repeated spam in project posts.');
    Notification::assertSentTo($members, AccountSuspendedNotification::class);

    bulk('member.reinstate', $members->pluck('id')->all());
    expect($members->fresh()->every(fn ($m) => ! $m->isSuspended()))->toBeTrue();
    Notification::assertSentTo($members, AccountReinstatedNotification::class);
});

/** ---------------------------------------------------------------- projects */
it('takes projects down and restores them in bulk, telling the posters', function () {
    Notification::fake();
    $this->actingAs(staffWith('Platform manager'), 'staff');
    $jobs = ModelJob::factory()->count(2)->create(['is_active' => true]);

    bulk('project.takedown', $jobs->pluck('id')->all(), ['reason' => 'Off-platform payment requests.']);
    expect($jobs->fresh()->every(fn ($j) => $j->isTakenDown()))->toBeTrue();
    Notification::assertSentTo($jobs->map->user, ProjectTakenDownNotification::class);

    bulk('project.restore', $jobs->pluck('id')->all());
    expect($jobs->fresh()->every(fn ($j) => ! $j->isTakenDown()))->toBeTrue();
});

/** ---------------------------------------------------------------- staff */
it('deactivates staff in bulk but never yourself or the last active Super admin, and reactivates them', function () {
    $admin = staffWith('Super admin');
    $this->actingAs($admin, 'staff');
    $support = Staff::factory()->count(2)->create();
    $otherSuper = staffWith('Super admin');

    bulk('staff.deactivate', [...$support->pluck('id')->all(), $admin->id, $otherSuper->id]);

    // The other Super admin goes (one remains), then the signed-in one is protected twice over
    $skipped = collect(session('bulk_result')['skipped']);
    expect($support->fresh()->every(fn ($s) => ! $s->is_active))->toBeTrue()->and($admin->fresh()->is_active)->toBeTrue()->and($otherSuper->fresh()->is_active)->toBeFalse()
        ->and($skipped->firstWhere('name', $admin->name)['why'])->toBe('You cannot deactivate your own account.');

    bulk('staff.reactivate', [...$support->pluck('id')->all(), $admin->id]);
    expect($support->fresh()->every(fn ($s) => $s->is_active))->toBeTrue()->and(collect(session('bulk_result')['skipped'])->pluck('why')->all())->toBe(['It is already active.']);
});

/** ---------------------------------------------------------------- notifications */
it('marks only the ticked notifications as read, and only your own', function () {
    $me = staffWith('Marketplace moderator');
    $other = staffWith('Marketplace moderator');
    $this->actingAs($me, 'staff');
    foreach ([$me, $me, $me, $other] as $person) {
        $person->notify(new ProductSubmittedNotification(Product::factory()->inReview()->create()->loadMissing('seller')));
    }
    $mine = $me->notifications()->pluck('id');

    bulk('notification.read', [$mine[0], $mine[1], $other->notifications()->first()->id]);

    expect($me->fresh()->unreadNotifications()->count())->toBe(1)->and($other->fresh()->unreadNotifications()->count())->toBe(1)->and(session('bulk_result')['done'])->toHaveCount(2);
});

/** ---------------------------------------------------------------- the guards on the endpoint */
it('holds each action to its permission', function () {
    $product = Product::factory()->inReview()->create();

    $this->actingAs(staffWith('Support'), 'staff');
    bulk('model.publish', [$product->id])->assertForbidden();
    bulk('member.suspend', [1], ['reason' => 'Some reason here'])->assertForbidden(); // Support is read-only
    bulk('staff.deactivate', [1])->assertForbidden();
    bulk('notification.read', ['00000000-0000-0000-0000-000000000000'])->assertRedirect(); // no permission needed

    $this->actingAs(staffWith('Auditor'), 'staff');
    bulk('project.takedown', [1], ['reason' => 'Some reason here'])->assertForbidden();

    expect($product->fresh()->status)->toBe(ProductStatus::InReview);
});

it('rejects unknown actions, empty or oversized selections and bad ids', function () {
    $this->actingAs(staffWith('Super admin'), 'staff');

    bulk('model.vaporise', [1])->assertNotFound();
    $this->post(route('admin.bulk', 'model.publish'), [])->assertSessionHasErrors('ids');
    bulk('model.publish', [])->assertSessionHasErrors('ids');
    bulk('model.publish', range(1, BulkActions::MAX + 1))->assertSessionHasErrors('ids');
    bulk('model.publish', ['abc'])->assertSessionHasErrors('ids.0');
    bulk('model.publish', [5, 5])->assertSessionHasErrors('ids.0');
    expect(StaffActivity::where('action', 'like', 'bulk.%')->count())->toBe(0);
});

it('reports ids that no longer exist instead of failing', function () {
    $this->actingAs(staffWith('Super admin'), 'staff');
    $real = Product::factory()->inReview()->create();

    bulk('model.publish', [$real->id, 999999]);

    expect(session('bulk_result')['done'])->toHaveCount(1)->and(session('bulk_result')['skipped'][0])->toBe(['name' => '#999999', 'why' => 'It no longer exists.']);
});

it('keeps the bulk endpoint behind the staff sign-in', function () {
    $this->post(route('admin.bulk', 'model.publish'), ['ids' => [1]])->assertRedirect(route('admin.login'));
});

it('offers each list only the actions the person may run', function () {
    $keys = fn (string $page, Staff $staff) => collect(BulkActions::forPage($page, $staff))->pluck('key')->all();

    expect($keys('models', staffWith('Marketplace moderator')))->toBe(['model.publish', 'model.reject'])
        ->and($keys('models', staffWith('Support')))->toBe([])
        ->and($keys('members', staffWith('Platform manager')))->toBe(['member.suspend', 'member.reinstate'])
        ->and($keys('members', staffWith('Support')))->toBe([])
        ->and($keys('members', staffWith('Auditor')))->toBe([])
        ->and($keys('notifications', staffWith()))->toBe(['notification.read'])
        ->and($keys('staff', staffWith('Super admin')))->toBe(['staff.deactivate', 'staff.reactivate']);
});

it('shows what a batch did, and what it skipped, once at the top of the next page', function () {
    $this->actingAs(staffWith('Marketplace moderator'), 'staff');
    $waiting = Product::factory()->inReview()->create();
    $draft = Product::factory()->create(['status' => ProductStatus::Draft, 'title' => 'Still a draft']);

    bulk('model.publish', [$waiting->id, $draft->id]);

    $this->get(route('admin.models.index'))->assertSee('1 model published.')->assertSee('1 skipped:')->assertSee('Still a draft');
    $this->get(route('admin.models.index'))->assertDontSee('1 model published.');
});

/** ---------------------------------------------------------------- what each list shows */
it('offers tick boxes and the bulk bar only to those who may run the actions', function () {
    User::factory()->count(2)->create();

    $page = $this->actingAs(staffWith('Platform manager'), 'staff')->get(route('admin.members.index'))->assertOk();
    $page->assertSee('Select all on this page')->assertSee('Bulk actions')->assertSee('member.suspend')->assertSee('member.reinstate')->assertDontSee('staff.deactivate');
    expect(substr_count($page->getContent(), 'type="checkbox"'))->toBeGreaterThanOrEqual(3);

    $this->actingAs(staffWith('Auditor'), 'staff')->get(route('admin.members.index'))->assertOk()->assertDontSee('Select all on this page')->assertDontSee('Bulk actions');
});

it('only ticks the models that are waiting for review', function () {
    $this->actingAs(staffWith('Marketplace moderator'), 'staff');
    $waiting = Product::factory()->inReview()->create(['title' => 'Waiting chair']);
    $live = Product::factory()->published()->create(['title' => 'Live table']);

    $this->get(route('admin.models.index', ['status' => 'all']))->assertOk()->assertSee('Select Waiting chair')->assertDontSee('Select Live table')->assertSee('Select all on this page');
    $this->get(route('admin.models.index', ['status' => 'published']))->assertOk()->assertDontSee('Select all on this page')->assertDontSee('Select Live table');
});

it('never offers to tick your own staff account', function () {
    $admin = staffWith('Super admin');
    $other = Staff::factory()->create(['name' => 'Other Person']);
    $this->actingAs($admin, 'staff');

    $html = $this->get(route('admin.staff.index'))->assertOk()->getContent();

    // The header box plus one per colleague: not one for yourself
    expect(substr_count($html, 'value="'.$other->id.'"'))->toBe(1)->and(substr_count($html, 'value="'.$admin->id.'"'))->toBe(0);
});

it('only ticks disputes nobody has taken on, and unread notifications', function () {
    $me = staffWith('Dispute manager');
    $this->actingAs($me, 'staff');
    ['dispute' => $free] = makeDisputedEngagement();
    ['dispute' => $taken] = makeDisputedEngagement();
    $taken->assignAdmin(staffWith('Dispute manager')->id);

    $html = $this->get(route('admin.disputes.index', ['status' => 'all']))->assertOk()->getContent();
    expect($html)->toContain('Select dispute #'.$free->id)->not->toContain('Select dispute #'.$taken->id);

    $product = Product::factory()->inReview()->create();
    $me->notify(new ProductSubmittedNotification($product->loadMissing('seller')));
    $me->notifications()->first()->markAsRead();
    $me->notify(new ProductSubmittedNotification($product));

    $page = $this->get(route('admin.notifications.index'))->assertOk();
    expect(substr_count($page->getContent(), 'Select this notification'))->toBe(1);
});

it('lets the activity log be narrowed to bulk actions', function () {
    $this->actingAs(staffWith('Marketplace moderator', 'Auditor'), 'staff');
    $waiting = Product::factory()->inReview()->count(2)->create();
    bulk('model.publish', $waiting->pluck('id')->all());

    $this->actingAs(staffWith('Super admin'), 'staff')->get(route('admin.activity.index', ['area' => 'bulk']))->assertOk()->assertSee('bulk &quot;Publish&quot;: 2 done, 0 skipped', false);
});
