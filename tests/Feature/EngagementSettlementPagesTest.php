<?php

use App\Enums\DisputeStatus;
use App\Enums\EngagementStatus;
use App\Enums\PartialPaymentStatus;
use App\Models\JobApplication;
use App\Models\JobCancellation;
use App\Models\JobDeliverable;
use App\Models\JobEngagement;
use App\Models\JobPartialPayment;
use App\Models\JobPaymentDispute;
use App\Models\ModelJob;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/*
 * The three pages that finish an engagement's story: responding to an offer, settling a cancelled engagement
 * (review work, process/accept/dispute the partial payment) and following a payment dispute. Every test asserts
 * OK first: a 500 page can otherwise satisfy loose text assertions. Lazy loading is blocked outside production,
 * so each request also proves the page eager-loads what it reads.
 */

beforeEach(function () {
    $this->me = User::factory()->create(['name' => 'Amina Otieno']);
    $this->other = User::factory()->create(['name' => 'Kevin Mwangi']);
    $this->actingAs($this->me);
});

function stEngagement(User $me, User $other, EngagementStatus $status, bool $asFreelancer, array $job = []): JobEngagement
{
    $application = JobApplication::factory()->hired()->create([
        'job_id' => ModelJob::factory()->create(array_merge(['user_id' => $asFreelancer ? $other->id : $me->id, 'title' => 'Hospital lobby model'], $job)),
        'applicant_id' => $asFreelancer ? $me->id : $other->id,
        'poster_id' => $asFreelancer ? $other->id : $me->id,
    ]);

    return JobEngagement::create([
        'application_id' => $application->id,
        'status' => $status,
        'agreed_amount' => 1100,
        'service_fee' => 100,
        'net_amount' => 1000,
        'started_at' => now()->subDays(6),
    ]);
}

function stDeliverable(JobEngagement $engagement, string $status, string $title = 'Massing model'): JobDeliverable
{
    return JobDeliverable::create([
        'engagement_id' => $engagement->id, 'title' => $title, 'description' => 'Details',
        'due_date' => now()->addDays(4)->toDateString(), 'status' => $status,
    ]);
}

function stCancel(JobEngagement $engagement, User $initiator, array $overrides = []): JobCancellation
{
    return JobCancellation::create(array_merge([
        'engagement_id' => $engagement->id, 'initiator_id' => $initiator->id, 'cancellation_type' => 'client_initiated',
        'reason_category' => 'project_scope_change', 'reason_details' => 'The brief changed completely',
    ], $overrides));
}

function stPayment(JobEngagement $engagement, User $processedBy, PartialPaymentStatus $status = PartialPaymentStatus::Pending): JobPartialPayment
{
    return JobPartialPayment::create([
        'engagement_id' => $engagement->id, 'amount' => 450, 'processed_by' => $processedBy->id,
        'processed_at' => now()->subDay(), 'status' => $status,
    ]);
}

/* ------------------------------------------------------------------ settlement */

it('lets the client process the partial payment once the submitted work is reviewed', function () {
    $engagement = stEngagement($this->me, $this->other, EngagementStatus::Cancelled, asFreelancer: false);
    stDeliverable($engagement, 'approved');
    stCancel($engagement, $this->me);

    $this->get(route('engagements.show-cancelled', $engagement->id))
        ->assertOk()
        ->assertSee('Settlement')
        ->assertSee('Review submitted work')
        ->assertSee('Process payment')
        ->assertSee('name="payment_amount"', false)
        ->assertSee(route('engagements.process-partial-payment', $engagement->id), false)
        ->assertDontSee('Accept payment');
});

it('asks the client to review submitted work before any payment can be processed', function () {
    $engagement = stEngagement($this->me, $this->other, EngagementStatus::Cancelled, asFreelancer: false);
    $submitted = stDeliverable($engagement, 'submitted', 'Submitted piece');
    stDeliverable($engagement, 'approved', 'Approved piece');
    stCancel($engagement, $this->me);

    $this->get(route('engagements.show-cancelled', $engagement->id))
        ->assertOk()
        ->assertSee('Review the submitted work before the payment can be processed.')
        ->assertSee('Request changes')
        ->assertSee('approve-deliverable-'.$submitted->id, false)
        ->assertSee('There are pending deliverables')
        ->assertDontSee('Process payment');
});

it('lets the freelancer accept or dispute a pending payment and explains what a dispute does', function () {
    $engagement = stEngagement($this->me, $this->other, EngagementStatus::Cancelled, asFreelancer: true);
    stDeliverable($engagement, 'approved');
    stCancel($engagement, $this->other);
    $payment = stPayment($engagement, $this->other);

    $this->get(route('engagements.show-cancelled', $engagement->id))
        ->assertOk()
        ->assertSee('Accept payment')
        ->assertSee('Dispute payment')
        ->assertSee(route('engagements.accept-partial-payment', $payment->id), false)
        ->assertSee(route('engagements.dispute-form', $payment->id), false)
        ->assertSee('The engagement will be frozen')
        ->assertSee('The client has processed this payment. Please review and respond.')
        ->assertDontSee('Process payment');
});

it('tells the client the freelancer has yet to respond, without offering the freelancer actions', function () {
    $engagement = stEngagement($this->me, $this->other, EngagementStatus::Cancelled, asFreelancer: false);
    stDeliverable($engagement, 'approved');
    stCancel($engagement, $this->me);
    stPayment($engagement, $this->me);

    $this->get(route('engagements.show-cancelled', $engagement->id))
        ->assertOk()
        ->assertSee('Waiting for the freelancer to respond.')
        ->assertDontSee('Accept payment')
        ->assertDontSee('Dispute payment');
});

it('shows a settled payment without any response actions', function () {
    $engagement = stEngagement($this->me, $this->other, EngagementStatus::Cancelled, asFreelancer: true);
    stDeliverable($engagement, 'approved');
    stCancel($engagement, $this->other);
    stPayment($engagement, $this->other, PartialPaymentStatus::Accepted);

    $this->get(route('engagements.show-cancelled', $engagement->id))
        ->assertOk()
        ->assertSee('You accepted this payment.')
        ->assertDontSee('Accept payment')
        ->assertDontSee('Dispute payment');
});

it('shows readable cancellation details instead of stored slugs', function () {
    $engagement = stEngagement($this->me, $this->other, EngagementStatus::Cancelled, asFreelancer: false);
    stDeliverable($engagement, 'approved');
    stCancel($engagement, $this->me);

    $this->get(route('engagements.show-cancelled', $engagement->id))
        ->assertOk()
        ->assertSee('Project scope changed')
        ->assertSee('Client initiated')
        ->assertSee('The brief changed completely')
        ->assertSee('Cancelled by you')
        ->assertDontSee('project_scope_change')
        ->assertDontSee('client_initiated');
});

it('survives a cancelled engagement that never had deliverables', function () {
    $engagement = stEngagement($this->me, $this->other, EngagementStatus::Cancelled, asFreelancer: false);
    stCancel($engagement, $this->me);

    $this->get(route('engagements.show-cancelled', $engagement->id))
        ->assertOk()
        ->assertSee('No deliverables were added to this engagement.');
});

it('offers to reopen the job to the client only while it is closed', function () {
    $engagement = stEngagement($this->me, $this->other, EngagementStatus::Cancelled, asFreelancer: false, job: ['is_active' => false]);
    stCancel($engagement, $this->me);

    $this->get(route('engagements.show-cancelled', $engagement->id))->assertOk()->assertSee('Reopen job');

    $engagement->application->job->update(['is_active' => true]);
    $this->get(route('engagements.show-cancelled', $engagement->id))->assertOk()->assertDontSee('Reopen job');
});

it('keeps strangers off the settlement page', function () {
    $stranger = User::factory()->create();
    $engagement = stEngagement($this->other, $stranger, EngagementStatus::Cancelled, asFreelancer: false);
    stCancel($engagement, $this->other);

    $this->get(route('engagements.show-cancelled', $engagement->id))->assertRedirect();
});

/* --------------------------------------------------------------------- dispute */

function stDisputedEngagement(User $me, User $other, array $dispute = []): array
{
    $engagement = stEngagement($me, $other, EngagementStatus::Disputed, asFreelancer: true);
    $cancellation = stCancel($engagement, $other, ['cancellation_type' => 'dispute', 'is_dispute' => true]);
    $payment = stPayment($engagement, $other, PartialPaymentStatus::Disputed);
    $disputeModel = JobPaymentDispute::create(array_merge([
        'cancellation_id' => $cancellation->id, 'disputed_by' => $me->id, 'dispute_reason' => 'incorrect_amount',
        'dispute_details' => 'The amount ignores two approved deliverables', 'status' => DisputeStatus::Pending,
    ], $dispute));
    $payment->update(['dispute_id' => $disputeModel->id]);

    return [$engagement, $disputeModel];
}

it('shows an open dispute with its reason, payment and who filed it', function () {
    [$engagement] = stDisputedEngagement($this->me, $this->other, ['supporting_evidence' => [['name' => 'chat.png', 'path' => 'x/chat.png']]]);

    $this->get(route('engagements.show-disputed', $engagement->id))
        ->assertOk()
        ->assertSee('Payment dispute')
        ->assertSee('Incorrect Amount')
        ->assertSee('The amount ignores two approved deliverables')
        ->assertSee('Evidence 1')
        ->assertSee('Payment under dispute')
        ->assertSee('Filed by')
        ->assertSee('An administrator is reviewing this dispute.')
        ->assertDontSee('Resolve this dispute');
});

it('shows the outcome of a resolved dispute', function () {
    $admin = User::factory()->create(['name' => 'Wanjiru Admin']);
    [$engagement] = stDisputedEngagement($this->me, $this->other, [
        'status' => DisputeStatus::Resolved, 'resolved_at' => now(), 'resolved_by' => $admin->id,
        'resolution_notes' => 'Pay for the two approved deliverables', 'resolution_amount' => 700,
    ]);

    $this->get(route('engagements.show-disputed', $engagement->id))
        ->assertOk()
        ->assertSee('This dispute has been resolved.')
        ->assertSee('Final resolution amount')
        ->assertSee(config('app.currency_symbol').'700.00')
        ->assertSee('Pay for the two approved deliverables')
        ->assertSee('by Wanjiru Admin');
});

it('gives administrators the resolution form only while the dispute is open', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::findOrCreate('admin'));
    $admin->givePermissionTo(Permission::findOrCreate('resolve disputes'), Permission::findOrCreate('view disputes'));
    [$engagement, $dispute] = stDisputedEngagement($this->me, $this->other);

    $this->actingAs($admin)->get(route('engagements.show-disputed', $engagement->id))
        ->assertOk()
        ->assertSee('Resolve this dispute')
        ->assertSee(route('admin.disputes.resolve', $dispute->id), false);

    $dispute->update(['status' => DisputeStatus::Resolved, 'resolved_at' => now()]);
    $this->actingAs($admin)->get(route('engagements.show-disputed', $engagement->id))
        ->assertOk()
        ->assertDontSee('Resolve this dispute');
});

/* --------------------------------------------------------------------- respond */

it('shows the offer terms and lets the freelancer accept or decline', function () {
    $engagement = stEngagement($this->me, $this->other, EngagementStatus::EmployerAccepted, asFreelancer: true);
    stDeliverable($engagement, 'pending', 'Massing model');

    $this->get(route('engagements.response-form', ['applicationId' => $engagement->application_id]))
        ->assertOk()
        ->assertSee('Hospital lobby model')
        ->assertSee('Awaiting your response')
        ->assertSee('From Kevin Mwangi')
        ->assertSee(config('app.currency_symbol').'1,100.00')
        ->assertSee(config('app.currency_symbol').'1,000.00')
        ->assertSee('Massing model')
        ->assertSee('name="response" value="accepted"', false)
        ->assertSee('name="response" value="declined"', false)
        ->assertSee(route('engagements.respond', $engagement), false)
        ->assertSee('Your response is final')
        ->assertSee(':disabled="!choice"', false);
});

it('shows an answered offer read-only', function () {
    $engagement = stEngagement($this->me, $this->other, EngagementStatus::Active, asFreelancer: true);

    $response = $this->get(route('engagements.response-form', ['applicationId' => $engagement->application_id]))
        ->assertOk()
        ->assertSee('You accepted this offer on')
        ->assertSee('Open engagement')
        ->assertDontSee('Your response is final');

    expect($response->getContent())->toContain('disabled')->not->toContain('Submit response');
});

it('does not break on a declined offer that has no cancellation date', function () {
    $engagement = stEngagement($this->me, $this->other, EngagementStatus::Cancelled, asFreelancer: true);

    $this->get(route('engagements.response-form', ['applicationId' => $engagement->application_id]))
        ->assertOk()
        ->assertSee('You declined this offer')
        ->assertSee('Declined');
});

it('only shows an offer to the freelancer it was made to', function () {
    $engagement = stEngagement($this->me, $this->other, EngagementStatus::EmployerAccepted, asFreelancer: false);

    $this->get(route('engagements.response-form', ['applicationId' => $engagement->application_id]))
        ->assertRedirect(route('engagements.index'));
});

/* --------------------------------------------------------------- dispute form */

it('shows the freelancer the dispute form for a pending payment', function () {
    $engagement = stEngagement($this->me, $this->other, EngagementStatus::Cancelled, asFreelancer: true);
    stDeliverable($engagement, 'approved');
    stCancel($engagement, $this->other);
    $payment = stPayment($engagement, $this->other);

    $this->get(route('engagements.dispute-form', $payment->id))
        ->assertOk()
        ->assertSee('Dispute payment')
        ->assertSee('Hospital lobby model')
        ->assertSee('name="reason"', false)
        ->assertSee('Incorrect Amount')
        ->assertSee('Other (please specify)')
        ->assertSee('name="details"', false)
        ->assertSee('name="evidence"', false)
        ->assertSee('enctype="multipart/form-data"', false)
        ->assertSee(route('engagements.process-dispute-partial-payment', $payment->id), false)
        ->assertSee(route('engagements.policy').'#dispute', false)
        ->assertSee(':disabled="!acknowledged"', false);
});

it('keeps the dispute form away from the client and from payments that are no longer pending', function () {
    $asClient = stEngagement($this->me, $this->other, EngagementStatus::Cancelled, asFreelancer: false);
    stCancel($asClient, $this->me);
    $clientPayment = stPayment($asClient, $this->me);

    $asFreelancer = stEngagement($this->me, $this->other, EngagementStatus::Cancelled, asFreelancer: true);
    stCancel($asFreelancer, $this->other);
    $accepted = stPayment($asFreelancer, $this->other, PartialPaymentStatus::Accepted);

    $this->get(route('engagements.dispute-form', $clientPayment->id))->assertRedirect();
    $this->get(route('engagements.dispute-form', $accepted->id))->assertRedirect();
});

/* ---------------------------------------------------------------------- policy */

it('renders the cancellation policy with its table of contents and every section', function () {
    $response = $this->get(route('engagements.policy'))->assertOk();

    $response->assertSee('Cancellation &amp; Payment Policy', false)
        ->assertSee('On this page')
        ->assertSeeInOrder([
            'Cancellation overview', 'Handling deliverables', 'Payment eligibility', 'Payment processing flow',
            'Escrow and fund release', 'Dispute resolution', 'Platform rights', 'Communication and notifications', 'Policy updates',
        ]);

    foreach (['cancellation-overview', 'handling-deliverables', 'payment-eligibility', 'payment-processing', 'escrow', 'dispute', 'platform-rights', 'communication', 'policy-updates'] as $id) {
        $response->assertSee('id="'.$id.'"', false)->assertSee('href="#'.$id.'"', false);
    }

    $response->assertSee('Deliverables that have already been approved are considered')
        ->assertSee('final and eligible for payment')
        ->assertSee('within 7–14 business days')
        ->assertSee(config('app.name').' reserves the right to');
});

it('keeps the policy behind sign-in', function () {
    auth()->logout();

    $this->get(route('engagements.policy'))->assertRedirect(route('login'));
});
