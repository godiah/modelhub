<?php

use App\Enums\DisputeStatus;
use App\Enums\EngagementStatus;
use App\Models\JobApplication;
use App\Models\JobCancellation;
use App\Models\JobDeliverable;
use App\Models\JobEngagement;
use App\Models\JobPaymentDispute;
use App\Models\Message;
use App\Models\ModelJob;
use App\Models\User;

/*
 * The engagements list: one scannable row per engagement (role, what is waiting on you, honest progress,
 * amount, one primary action) plus status tabs with real counts. Lazy loading is blocked outside
 * production, so these requests double as N+1 guards.
 */

beforeEach(function () {
    $this->user = User::factory()->create(['name' => 'Amina Otieno']);
    $this->other = User::factory()->create(['name' => 'Kevin Mwangi']);
    $this->actingAs($this->user);
});

function listEngagement(User $me, User $other, EngagementStatus $status, bool $asFreelancer, string $title = 'Villa render', array $money = []): JobEngagement
{
    $application = JobApplication::factory()->hired()->create([
        'job_id' => ModelJob::factory()->create(['user_id' => $asFreelancer ? $other->id : $me->id, 'title' => $title]),
        'applicant_id' => $asFreelancer ? $me->id : $other->id,
        'poster_id' => $asFreelancer ? $other->id : $me->id,
    ]);

    return JobEngagement::create([
        'application_id' => $application->id,
        'status' => $status,
        'agreed_amount' => $money['agreed'] ?? 1100,
        'service_fee' => 100,
        'net_amount' => $money['net'] ?? 1000,
        'started_at' => now()->subDays(3),
    ]);
}

/** A disputed engagement always carries a cancellation and a dispute; the row panel reads them. */
function listDisputedEngagement(User $me, User $other, string $title = 'Dispute job'): JobEngagement
{
    $engagement = listEngagement($me, $other, EngagementStatus::Disputed, true, $title);
    $cancellation = JobCancellation::create([
        'engagement_id' => $engagement->id, 'initiator_id' => $other->id, 'cancellation_type' => 'dispute',
        'reason_category' => 'other', 'reason_details' => 'x', 'partial_payment_amount' => 300, 'is_dispute' => true,
    ]);
    JobPaymentDispute::create([
        'cancellation_id' => $cancellation->id, 'disputed_by' => $me->id, 'dispute_reason' => 'incorrect_amount',
        'dispute_details' => 'd', 'status' => DisputeStatus::Pending,
    ]);

    return $engagement;
}

function listDeliverable(JobEngagement $engagement, string $status, ?string $due = null, string $title = 'Deliverable'): JobDeliverable
{
    return JobDeliverable::create([
        'engagement_id' => $engagement->id, 'title' => $title, 'description' => 'd', 'due_date' => $due, 'status' => $status,
    ]);
}

it('shows status tabs with the real counts, not counts of the current page', function () {
    listEngagement($this->user, $this->other, EngagementStatus::Active, true, 'A1');
    listEngagement($this->user, $this->other, EngagementStatus::Active, false, 'A2');
    listEngagement($this->user, $this->other, EngagementStatus::EmployerAccepted, true, 'P1');
    listEngagement($this->user, $this->other, EngagementStatus::Completed, true, 'C1');

    // Filtering to one status must not change the other tabs' counts.
    $this->get(route('engagements.index', ['status' => 'completed']))
        ->assertOk()
        ->assertSeeInOrder(['All', '4', 'Active', '2', 'Pending', '1', 'Completed', '1', 'Withdrawn', '0'])
        ->assertDontSee('Disputed');
});

it('only offers the Disputed and Settled tabs when there is something in them', function () {
    listDisputedEngagement($this->user, $this->other, 'D1');

    $this->get(route('engagements.index'))->assertOk()->assertSee('Disputed')->assertDontSee('Settled');
});

it('filters by the Pending tab (employer_accepted); the old `pending` value is not a valid status', function () {
    listEngagement($this->user, $this->other, EngagementStatus::EmployerAccepted, true, 'Waiting job');
    listEngagement($this->user, $this->other, EngagementStatus::Active, true, 'Running job');

    $this->get(route('engagements.index', ['status' => 'employer_accepted']))
        ->assertOk()
        ->assertSee('Waiting job')->assertOk()->assertDontSee('Running job');

    $this->get(route('engagements.index', ['status' => 'pending']))->assertSessionHasErrors('status');
});

it('searches by project or person and returns just the list for ajax requests', function () {
    listEngagement($this->user, $this->other, EngagementStatus::Active, true, 'Hospital lobby');
    listEngagement($this->user, $this->other, EngagementStatus::Active, true, 'Airport terminal');

    $response = $this->get(route('engagements.index', ['search' => 'Hospital']), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();

    $response->assertSee('Hospital lobby')->assertDontSee('Airport terminal')->assertDontSee('role="tablist"', false);
});

it('shows the role, counterpart and what each side owes', function () {
    listEngagement($this->user, $this->other, EngagementStatus::Active, true, 'Freelance gig', ['agreed' => 1100, 'net' => 1000]);
    listEngagement($this->user, $this->other, EngagementStatus::Active, false, 'Client gig', ['agreed' => 3300, 'net' => 3000]);

    $this->get(route('engagements.index'))
        ->assertOk()
        ->assertSee('Freelancer')->assertOk()->assertSee('Client')
        ->assertSee('with Kevin Mwangi')
        ->assertSee('You receive')->assertSee(config('app.currency_symbol').'1,000')
        ->assertSee('You pay')->assertSee(config('app.currency_symbol').'3,300');
});

it('counts only approved deliverables as progress (submitted or rejected work is not done)', function () {
    $engagement = listEngagement($this->user, $this->other, EngagementStatus::Active, true);
    listDeliverable($engagement, 'approved', title: 'One');
    listDeliverable($engagement, 'submitted', title: 'Two');
    listDeliverable($engagement, 'rejected', title: 'Three');
    listDeliverable($engagement, 'pending', title: 'Four');

    $this->get(route('engagements.index'))
        ->assertOk()
        ->assertSee('1 of 4 approved')
        ->assertSee('aria-valuenow="25"', false);
});

it('says so when there are no deliverables yet', function () {
    listEngagement($this->user, $this->other, EngagementStatus::Active, true);

    $this->get(route('engagements.index'))->assertOk()->assertSee('No deliverables yet');
});

it('flags what is waiting on the user: review as client, revise as freelancer, overdue, unread messages', function () {
    $asClient = listEngagement($this->user, $this->other, EngagementStatus::Active, false, 'Client side');
    listDeliverable($asClient, 'submitted');
    listDeliverable($asClient, 'submitted');

    $asFreelancer = listEngagement($this->user, $this->other, EngagementStatus::Active, true, 'Freelance side');
    listDeliverable($asFreelancer, 'rejected', now()->subDays(2)->toDateString());
    Message::create(['engagement_id' => $asFreelancer->id, 'sender_id' => $this->other->id, 'content' => 'Ping']);
    Message::create(['engagement_id' => $asFreelancer->id, 'sender_id' => $this->other->id, 'content' => 'Pong']);
    Message::create(['engagement_id' => $asFreelancer->id, 'sender_id' => $this->user->id, 'content' => 'My own message']);

    $this->get(route('engagements.index'))
        ->assertOk()
        ->assertSee('2 to review')
        ->assertSee('1 to revise')
        ->assertSee('Overdue')
        ->assertSee('2 new messages')
        ->assertSee('Review work')
        ->assertSee('Revise');
});

it('gives each state one primary action', function () {
    listEngagement($this->user, $this->other, EngagementStatus::EmployerAccepted, true, 'Offer job');
    listEngagement($this->user, $this->other, EngagementStatus::Completed, true, 'Done job');
    listDisputedEngagement($this->user, $this->other, 'Dispute job');

    $cancelled = listEngagement($this->user, $this->other, EngagementStatus::Cancelled, false, 'Cancelled job');
    listDeliverable($cancelled, 'approved');
    JobCancellation::create([
        'engagement_id' => $cancelled->id, 'initiator_id' => $this->user->id, 'cancellation_type' => 'client_initiated',
        'reason_category' => 'other', 'reason_details' => 'x',
    ]);

    $this->get(route('engagements.index'))
        ->assertOk()
        ->assertSee('Respond to offer')
        ->assertSee('Leave a review')
        ->assertSee('View dispute')
        ->assertSee('Settle');
});

it('keeps Cancel engagement in the row menu, not as a red tile on every card', function () {
    $active = listEngagement($this->user, $this->other, EngagementStatus::Active, true);
    listEngagement($this->user, $this->other, EngagementStatus::Completed, true, 'Finished');

    $response = $this->get(route('engagements.index'))->assertOk();

    $response->assertSee("cancel-engagement-{$active->id}", false)
        ->assertSee('Cancel engagement')
        ->assertSee('More actions')
        ->assertDontSee('Engagement Actions'); // the old red tile's caption

    // Finished engagements can't be cancelled.
    expect(substr_count($response->getContent(), "\$dispatch('open-modal', 'cancel-engagement-"))->toBe(1);
});

it("never lists another pair's engagements", function () {
    $stranger = User::factory()->create();
    listEngagement($stranger, $this->other, EngagementStatus::Active, true, 'Not mine');

    $this->get(route('engagements.index'))->assertOk()->assertDontSee('Not mine')->assertSee('No engagements yet');
});

it('renders a cancelled engagement that never had a deliverable (was a division-by-zero 500)', function () {
    $engagement = listEngagement($this->user, $this->other, EngagementStatus::Cancelled, false, 'Cancelled early');
    JobCancellation::create([
        'engagement_id' => $engagement->id, 'initiator_id' => $this->user->id, 'cancellation_type' => 'client_initiated',
        'reason_category' => 'other', 'reason_details' => 'Changed my mind',
    ]);

    $this->get(route('engagements.show-cancelled', $engagement->id))->assertOk()->assertSee('Cancelled early');
});
