<?php

use App\Enums\DisputeStatus;
use App\Enums\EngagementStatus;
use App\Helpers\Engagements\EngagementNotificationHelper;
use App\Models\JobApplication;
use App\Models\JobCancellation;
use App\Models\JobEngagement;
use App\Models\JobPaymentDispute;
use App\Models\StaffActivity;
use App\Models\User;
use App\Notifications\DisputeCreatedNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function makeDisputedEngagement(float $netAmount = 1000): array
{
    $application = JobApplication::factory()->hired()->create(['net_amount' => $netAmount]);

    $engagement = JobEngagement::create([
        'application_id' => $application->id,
        'status' => EngagementStatus::Disputed,
        'agreed_amount' => $application->offer_amount,
        'service_fee' => $application->service_fee,
        'net_amount' => $netAmount,
    ]);

    // A real dispute is always preceded by a processed partial payment, which already set
    // partial_payment_amount on the cancellation — mirror that here so resolveDispute()'s
    // null-$finalAmount fallback (partial_payment_amount) has a real value to fall back to.
    $cancellation = JobCancellation::create([
        'engagement_id' => $engagement->id,
        'initiator_id' => $application->applicant_id,
        'cancellation_type' => 'dispute',
        'reason_category' => 'other',
        'reason_details' => 'test reason',
        'partial_payment_amount' => $netAmount * 0.5,
        'is_dispute' => true,
    ]);

    $dispute = JobPaymentDispute::create([
        'cancellation_id' => $cancellation->id,
        'disputed_by' => $application->applicant_id,
        'dispute_reason' => 'incorrect_amount',
        'dispute_details' => 'test details',
        'status' => DisputeStatus::Pending,
    ]);

    return compact('application', 'engagement', 'cancellation', 'dispute');
}

test('super admins reach the whole portal; members and signed-out visitors are sent to the staff sign-in', function () {
    $super = staffWith('Super admin');
    ['dispute' => $dispute, 'cancellation' => $cancellation] = makeDisputedEngagement();

    $this->actingAs($super, 'staff')->get(route('admin.disputes.index'))->assertOk();
    $this->actingAs($super, 'staff')->get(route('admin.disputes.show', $cancellation))->assertOk();
    $this->actingAs($super, 'staff')->post(route('admin.disputes.assign', $dispute))->assertRedirect();
    $this->actingAs($super, 'staff')->get(route('admin.staff.index'))->assertOk();

    // A member account, however it is signed in, is not staff (and the staff session from above is gone)
    Auth::guard('staff')->forgetUser();
    $member = User::factory()->create();
    foreach ([route('admin.disputes.index'), route('admin.disputes.show', $cancellation), route('admin.staff.index'), route('admin.dashboard')] as $url) {
        $this->actingAs($member)->get($url)->assertRedirect(route('admin.login'));
    }
    $this->actingAs($member)->post(route('admin.disputes.assign', $dispute))->assertRedirect(route('admin.login'));
});

test('support can view disputes but not assign, resolve or manage staff', function () {
    $support = staffWith('Support');
    ['dispute' => $dispute, 'cancellation' => $cancellation] = makeDisputedEngagement();

    $this->actingAs($support, 'staff')->get(route('admin.disputes.index'))->assertOk();
    $this->actingAs($support, 'staff')->get(route('admin.disputes.show', $cancellation))->assertOk()->assertDontSee('Resolve this dispute')->assertDontSee('Assign to me');
    $this->actingAs($support, 'staff')->post(route('admin.disputes.assign', $dispute))->assertForbidden();
    $this->actingAs($support, 'staff')->post(route('admin.disputes.resolve', $dispute), ['resolution_notes' => 'x'])->assertForbidden();
    $this->actingAs($support, 'staff')->get(route('admin.staff.index'))->assertForbidden();
    $this->actingAs($support, 'staff')->get(route('admin.roles.index'))->assertForbidden();
});

test('a dispute manager can view, assign and resolve disputes but not manage staff', function () {
    $manager = staffWith('Dispute manager');
    ['dispute' => $dispute, 'cancellation' => $cancellation] = makeDisputedEngagement();

    $this->actingAs($manager, 'staff')->get(route('admin.disputes.index'))->assertOk();
    $this->actingAs($manager, 'staff')->get(route('admin.disputes.show', $cancellation))->assertOk()->assertSee('Resolve this dispute');
    $this->actingAs($manager, 'staff')->post(route('admin.disputes.assign', $dispute))->assertRedirect();
    $this->actingAs($manager, 'staff')->post(route('admin.disputes.resolve', $dispute), ['resolution_notes' => 'Resolved fairly.'])->assertRedirect();
    expect($dispute->fresh())->status->toBe(DisputeStatus::Resolved)->resolved_by->toBe($manager->id);

    $this->actingAs($manager, 'staff')->get(route('admin.staff.index'))->assertForbidden();
});

test('members only see their own disputes, never the staff pages', function () {
    ['engagement' => $engagement, 'application' => $application] = makeDisputedEngagement();
    $bystander = User::factory()->create();

    $this->actingAs($bystander)->get(route('engagements.show-disputed', $engagement->id))->assertRedirect();
    $this->actingAs($application->applicant)->get(route('engagements.show-disputed', $engagement->id))->assertOk()
        ->assertDontSee('Resolve this dispute')->assertDontSee('Finalise resolution');
});

test('resolve() redirects back to where the form was submitted from', function () {
    $manager = staffWith('Dispute manager');
    ['dispute' => $dispute, 'cancellation' => $cancellation] = makeDisputedEngagement();
    $referer = route('admin.disputes.show', $cancellation);

    $this->actingAs($manager, 'staff')->from($referer)->post(route('admin.disputes.resolve', $dispute), ['resolution_notes' => 'Handled.'])->assertRedirect($referer);
});

test('assign() 404s for a nonexistent dispute id instead of silently failing', function () {
    $this->actingAs(staffWith('Dispute manager'), 'staff')->post(route('admin.disputes.assign', 999999))->assertNotFound();
});

test('resolution_amount cannot exceed the engagement net_amount', function () {
    ['dispute' => $dispute] = makeDisputedEngagement(netAmount: 500);

    $this->actingAs(staffWith('Dispute manager'), 'staff')
        ->post(route('admin.disputes.resolve', $dispute), ['resolution_notes' => 'Trying to overpay.', 'resolution_amount' => 999])
        ->assertRedirect();

    expect(session('error'))->toContain('net amount');
    expect($dispute->fresh()->status)->toBe(DisputeStatus::Pending);
});

test('resolution_amount within the net_amount ceiling resolves successfully, recording which staff member settled it', function () {
    ['dispute' => $dispute, 'engagement' => $engagement] = makeDisputedEngagement(netAmount: 500);
    $manager = staffWith('Dispute manager');

    $this->actingAs($manager, 'staff')
        ->post(route('admin.disputes.resolve', $dispute), ['resolution_notes' => 'Partial payout.', 'resolution_amount' => 300])
        ->assertSessionDoesntHaveErrors();

    expect($dispute->fresh())->status->toBe(DisputeStatus::Resolved)->resolution_amount->toEqual('300.00');

    // The settlement payment names the staff member who finalised it, and no member as "processor"
    $payment = $engagement->partialPayments()->first();
    expect($payment->finalized_by)->toBe($manager->id)->and($payment->processed_by)->toBeNull();
});

test('resolving and assigning are written to the activity log', function () {
    ['dispute' => $dispute] = makeDisputedEngagement();
    $manager = staffWith('Dispute manager');

    $this->actingAs($manager, 'staff')->post(route('admin.disputes.assign', $dispute));
    $this->post(route('admin.disputes.resolve', $dispute), ['resolution_notes' => 'Done.']);

    expect(StaffActivity::where('staff_id', $manager->id)->pluck('action')->all())->toBe(['dispute.assigned', 'dispute.resolved']);
});

test('the disputes queue only shows "Assign to me" to staff who can actually resolve disputes', function () {
    makeDisputedEngagement();

    $this->actingAs(staffWith('Support'), 'staff')->get(route('admin.disputes.index'))->assertOk()->assertDontSee('Assign to me');
    $this->actingAs(staffWith('Dispute manager'), 'staff')->get(route('admin.disputes.index'))->assertOk()->assertSee('Assign to me');
});

/** ---------------------------------------------------------------- the disputes queue page */
test('the queue shows each dispute with its job, both parties, reason, amount and details', function () {
    ['application' => $application, 'dispute' => $dispute, 'cancellation' => $cancellation] = makeDisputedEngagement(2000);
    $dispute->update(['dispute_details' => 'The amount ignores two approved deliverables']);

    $this->actingAs(staffWith('Dispute manager'), 'staff')->get(route('admin.disputes.index'))->assertOk()
        ->assertSee($application->job->title)->assertSee($application->poster->name)->assertSee($application->applicant->name)
        ->assertSee('Incorrect Amount')->assertSee('1,000.00')->assertSee('The amount ignores two approved deliverables')
        ->assertSee('Not assigned to anyone yet')
        ->assertSee(route('admin.disputes.show', $cancellation), false);
});

test('anyone who can view disputes can open one, whoever it is assigned to', function () {
    ['dispute' => $dispute, 'cancellation' => $cancellation] = makeDisputedEngagement();
    $dispute->assignAdmin(staffWith('Super admin')->id);
    $url = route('admin.disputes.show', $cancellation);

    foreach ([staffWith('Support'), staffWith('Dispute manager')] as $viewer) {
        $this->actingAs($viewer, 'staff')->get(route('admin.disputes.index', ['status' => 'under_review']))->assertOk()->assertSee('Open dispute')->assertSee($url, false);
        $this->actingAs($viewer, 'staff')->get($url)->assertOk();
    }
});

test('the status filter is whitelisted, defaults to pending, and shows counts on every pill', function () {
    makeDisputedEngagement();
    ['dispute' => $assigned] = makeDisputedEngagement();
    $assigned->assignAdmin(staffWith('Super admin')->id);
    $admin = staffWith('Super admin');

    $count = fn (array $query = []) => substr_count($this->actingAs($admin, 'staff')->get(route('admin.disputes.index', $query))->getContent(), 'Open dispute');

    expect($count())->toBe(1)->and($count(['status' => 'under_review']))->toBe(1)->and($count(['status' => 'all']))->toBe(2)->and($count(['status' => 'bogus']))->toBe(1);
    $this->actingAs($admin, 'staff')->get(route('admin.disputes.index', ['status' => 'resolved']))->assertOk()->assertSee('No disputes have been resolved yet.');
});

test('the queue lists the dispute that has waited longest first, then resolved ones newest first', function () {
    ['dispute' => $newer, 'application' => $newerApp] = makeDisputedEngagement();
    ['dispute' => $older, 'application' => $olderApp] = makeDisputedEngagement();
    ['dispute' => $done, 'application' => $doneApp] = makeDisputedEngagement();
    $older->forceFill(['created_at' => now()->subDays(5)])->save();
    $done->update(['status' => DisputeStatus::Resolved, 'resolved_at' => now(), 'resolved_by' => staffWith('Super admin')->id]);

    $this->actingAs(staffWith('Super admin'), 'staff')->get(route('admin.disputes.index', ['status' => 'all']))->assertOk()
        ->assertSeeInOrder([$olderApp->job->title, $newerApp->job->title, $doneApp->job->title])->assertSee('waiting 5 days');
});

test('assigning moves a dispute to under review and says who has it; a resolved dispute cannot be assigned', function () {
    ['dispute' => $dispute] = makeDisputedEngagement();
    $manager = staffWith('Dispute manager');

    $this->actingAs($manager, 'staff')->post(route('admin.disputes.assign', $dispute))->assertSessionHas('success');
    expect($dispute->fresh())->status->toBe(DisputeStatus::UnderReview)->admin_assigned->toBe($manager->id);

    $this->actingAs($manager, 'staff')->get(route('admin.disputes.index', ['status' => 'under_review']))->assertOk()->assertSee('Assigned to you');
    $this->actingAs(staffWith('Support'), 'staff')->get(route('admin.disputes.index', ['status' => 'under_review']))->assertOk()->assertSee('Assigned to '.$manager->name);

    $dispute->update(['status' => DisputeStatus::Resolved]);
    $this->actingAs($manager, 'staff')->post(route('admin.disputes.assign', $dispute))->assertSessionHas('error');
    expect($dispute->fresh()->status)->toBe(DisputeStatus::Resolved);
});

test('resolved disputes show who resolved them and the final amount', function () {
    ['dispute' => $dispute] = makeDisputedEngagement(1000);
    $manager = staffWith('Dispute manager');
    $dispute->update(['status' => DisputeStatus::Resolved, 'resolved_at' => now(), 'resolved_by' => $manager->id, 'resolution_amount' => 420.50]);

    $this->actingAs($manager, 'staff')->get(route('admin.disputes.index', ['status' => 'resolved']))->assertOk()
        ->assertSee('Resolved '.now()->format('M j, Y').' by '.$manager->name)->assertSee('Settled at')->assertSee('420.50')->assertDontSee('Assign to me');
});

test('the staff dispute page shows the evidence, the parties and the cancellation, and staff can download evidence', function () {
    Storage::fake('local');
    Storage::disk('local')->put('dispute-evidence/proof.pdf', 'proof');
    ['dispute' => $dispute, 'cancellation' => $cancellation, 'application' => $application] = makeDisputedEngagement();
    $dispute->update(['supporting_evidence' => ['dispute-evidence/proof.pdf'], 'dispute_details' => 'Two deliverables were approved but ignored.']);

    $this->actingAs(staffWith('Support'), 'staff')->get(route('admin.disputes.show', $cancellation))->assertOk()
        ->assertSee($application->job->title)->assertSee('Two deliverables were approved but ignored.')
        ->assertSee($application->poster->email)->assertSee($application->applicant->email)->assertSee('Evidence 1')
        ->assertSee(route('admin.disputes.evidence', [$dispute, 0]), false);

    $this->get(route('admin.disputes.evidence', [$dispute, 0]))->assertOk();
    $this->get(route('admin.disputes.evidence', [$dispute, 5]))->assertNotFound();
});

test('only staff who can resolve disputes may submit a resolution', function () {
    ['dispute' => $dispute] = makeDisputedEngagement();

    $this->actingAs(staffWith('Support'), 'staff')->post(route('admin.disputes.resolve', $dispute), ['resolution_notes' => 'Trying anyway.'])->assertForbidden();
    expect($dispute->fresh()->status)->toBe(DisputeStatus::Pending);
});

test('old dispute links from earlier notifications still land on the staff dispute page', function () {
    ['cancellation' => $cancellation, 'engagement' => $engagement] = makeDisputedEngagement();
    $admin = staffWith('Super admin');

    // The stored link was /admin/disputes/{cancellation id}: that is now the dispute page itself
    $this->actingAs($admin, 'staff')->get('/admin/disputes/'.$cancellation->id)->assertOk();

    $notification = new DisputeCreatedNotification($engagement->fresh(), $cancellation->fresh());
    expect($notification->toDatabase($admin)['url'])->toBe(route('admin.disputes.show', $cancellation->id));
});

test('new disputes notify active staff who can view disputes, and nobody else', function () {
    Notification::fake();
    ['engagement' => $engagement, 'cancellation' => $cancellation] = makeDisputedEngagement();

    $support = staffWith('Support');
    $super = staffWith('Super admin');
    $moderator = staffWith('Marketplace moderator');
    $deactivated = staffWith('Dispute manager');
    $deactivated->update(['is_active' => false]);

    EngagementNotificationHelper::sendDisputeNotification($engagement, $cancellation);

    Notification::assertSentTo([$support, $super], DisputeCreatedNotification::class);
    Notification::assertNotSentTo([$moderator, $deactivated], DisputeCreatedNotification::class);
});
