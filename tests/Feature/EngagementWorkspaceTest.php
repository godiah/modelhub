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
 * The engagement workspace: one tabbed page (Overview, Deliverables, Messages, Activity) per engagement.
 * Lazy loading is blocked outside production, so every request also proves the page eager-loads what it reads.
 * Every test asserts OK first: a 500 page can otherwise satisfy loose text assertions.
 */

beforeEach(function () {
    $this->me = User::factory()->create(['name' => 'Amina Otieno']);
    $this->other = User::factory()->create(['name' => 'Kevin Mwangi']);
    $this->actingAs($this->me);
});

/** URLs printed through @js have their slashes escaped. */
function jsUrl(string $url): string
{
    return str_replace('/', '\\/', $url);
}

function wsEngagement(User $me, User $other, EngagementStatus $status, bool $asFreelancer, array $job = []): JobEngagement
{
    $application = JobApplication::factory()->hired()->create([
        'job_id' => ModelJob::factory()->create(array_merge(['user_id' => $asFreelancer ? $other->id : $me->id, 'title' => 'Villa exterior render'], $job)),
        'applicant_id' => $asFreelancer ? $me->id : $other->id,
        'poster_id' => $asFreelancer ? $other->id : $me->id,
        'proposal' => "I'll deliver in <b>two</b> weeks.",
    ]);

    return JobEngagement::create([
        'application_id' => $application->id,
        'status' => $status,
        'agreed_amount' => 1100,
        'service_fee' => 100,
        'net_amount' => 1000,
        'started_at' => now()->subDays(3),
    ]);
}

function wsDeliverable(JobEngagement $engagement, string $status, string $title = 'First draft', array $extra = []): JobDeliverable
{
    return JobDeliverable::create(array_merge([
        'engagement_id' => $engagement->id,
        'title' => $title,
        'description' => 'Details',
        'due_date' => now()->addDays(5)->toDateString(),
        'status' => $status,
    ], $extra));
}

it('renders the workspace header, tabs and overview for the client', function () {
    $engagement = wsEngagement($this->me, $this->other, EngagementStatus::Active, asFreelancer: false);
    wsDeliverable($engagement, 'approved', 'Done one');
    wsDeliverable($engagement, 'pending', 'Next one');

    $this->get(route('engagements.show', $engagement))
        ->assertOk()
        ->assertSee('Villa exterior render')
        ->assertSee('Client')
        ->assertSee('with Kevin Mwangi')
        ->assertSee('1 of 2 approved')
        ->assertSee('aria-valuenow="50"', false)
        ->assertSee('You pay')
        ->assertSeeInOrder(['Overview', 'Deliverables', 'Messages', 'Activity'])
        ->assertSee('Project description')
        ->assertSee('Key facts')
        ->assertSee('Kevin Mwangi');
});

it('renders the workspace for the freelancer with the amount they receive', function () {
    $engagement = wsEngagement($this->me, $this->other, EngagementStatus::Active, asFreelancer: true);

    $this->get(route('engagements.show', $engagement))
        ->assertOk()
        ->assertSee('Freelancer')
        ->assertSee('You receive')
        ->assertSee(config('app.currency_symbol').'1,000.00');
});

it('keeps strangers out of the workspace', function () {
    $stranger = User::factory()->create();
    $engagement = wsEngagement($this->other, $stranger, EngagementStatus::Active, asFreelancer: false);

    $this->get(route('engagements.show', $engagement))->assertForbidden();
});

it('redirects the old details URL to the workspace', function () {
    $engagement = wsEngagement($this->me, $this->other, EngagementStatus::Completed, asFreelancer: true);

    $this->get(route('engagements.archived-details', $engagement))->assertRedirect(route('engagements.show', $engagement));
});

it('gives the client the management actions and hides the freelancer ones', function () {
    $engagement = wsEngagement($this->me, $this->other, EngagementStatus::Active, asFreelancer: false);
    $pending = wsDeliverable($engagement, 'pending', 'Pending piece');
    $submitted = wsDeliverable($engagement, 'submitted', 'Submitted piece');

    $this->get(route('engagements.show', $engagement))
        ->assertOk()
        ->assertSee('Add deliverable')
        ->assertSee('Approve')
        ->assertSee('Request changes')
        ->assertSee('open-modal\', \'approve-deliverable-'.$submitted->id, false)
        ->assertSee('remove-deliverable-'.$pending->id, false)
        ->assertSee('edit-deliverable-'.$pending->id, false)
        ->assertDontSee('Submit work')
        ->assertDontSee('name="submission_files[]"', false)
        ->assertSee('Review work');
});

it('does not offer edit or remove on work that has already been submitted', function () {
    $engagement = wsEngagement($this->me, $this->other, EngagementStatus::Active, asFreelancer: false);
    $submitted = wsDeliverable($engagement, 'submitted');

    $this->get(route('engagements.show', $engagement))
        ->assertOk()
        ->assertDontSee("open-modal', 'remove-deliverable-{$submitted->id}'", false)
        ->assertDontSee("open-modal', 'edit-deliverable-{$submitted->id}'", false);
});

it('lets the freelancer submit pending work and resubmit rejected work, with the client feedback shown', function () {
    $engagement = wsEngagement($this->me, $this->other, EngagementStatus::Active, asFreelancer: true);
    wsDeliverable($engagement, 'pending', 'Massing model');
    wsDeliverable($engagement, 'rejected', 'Lighting pass', ['feedback' => 'Shadows are too harsh']);

    $this->get(route('engagements.show', $engagement))
        ->assertOk()
        ->assertSee('Submit work')
        ->assertSee('Resubmit')
        ->assertSee('Feedback from the client')
        ->assertSee('Shadows are too harsh')
        ->assertSee('name="submission_files[]"', false)
        ->assertSee(jsUrl(route('engagements.deliverables.submit', 'DELIVERABLE_ID')), false)
        ->assertDontSee('Add deliverable')
        ->assertDontSee('Request changes');
});

it('links submitted files to the authorised download route, not to a storage URL', function () {
    $engagement = wsEngagement($this->me, $this->other, EngagementStatus::Active, asFreelancer: false);
    $deliverable = wsDeliverable($engagement, 'submitted', 'With files', [
        'submitted_at' => now(),
        'submission_notes' => 'Attached the renders',
        'submission_files' => [['name' => 'render.png', 'path' => 'deliverable-submissions/x.png', 'size' => 2048, 'mime' => 'image/png']],
    ]);

    $this->get(route('engagements.show', $engagement))
        ->assertOk()
        ->assertSee('Attached the renders')
        ->assertSee('render.png')
        ->assertSee(route('engagements.deliverables.download-file', [$deliverable->id, 0]), false)
        ->assertDontSee('/storage/deliverable-submissions', false);
});

it('offers no deliverable actions and no messages tab once the engagement is completed', function () {
    $engagement = wsEngagement($this->me, $this->other, EngagementStatus::Completed, asFreelancer: false);
    wsDeliverable($engagement, 'approved');

    $this->get(route('engagements.show', $engagement))
        ->assertOk()
        ->assertDontSee('Add deliverable')
        ->assertDontSee('id="tab-messages"', false)
        ->assertSee('Leave a review');
});

it('wires the inline chat to the JSON endpoints and renders message text safely', function () {
    $engagement = wsEngagement($this->me, $this->other, EngagementStatus::Active, asFreelancer: true);
    Message::create(['engagement_id' => $engagement->id, 'sender_id' => $this->other->id, 'content' => 'Ping']);
    Message::create(['engagement_id' => $engagement->id, 'sender_id' => $this->other->id, 'content' => 'Pong']);

    $response = $this->get(route('engagements.show', $engagement));

    $response->assertOk()
        ->assertSee('id="tab-messages"', false)
        ->assertSee('data-unread-for="'.$engagement->id.'"', false)
        ->assertSee(jsUrl(route('engagements.data', $engagement)), false)
        ->assertSee(jsUrl(route('messages.store', $engagement)), false)
        ->assertSee(jsUrl(route('messages.read', $engagement)), false)
        // The migrated button keeps its Alpine binding instead of being evaluated as PHP.
        ->assertSee(':disabled="sending || !draft.trim()"', false)
        ->assertSee('x-text="message.content"', false);

    expect($response->getContent())->not->toContain('innerHTML');
});

it('strips raw HTML from the project description and escapes the proposal', function () {
    $engagement = wsEngagement($this->me, $this->other, EngagementStatus::Active, asFreelancer: true, job: [
        'description' => "Build a **lobby** model.\n\n<script>window.pwned = 1</script>\n\n[bad](javascript:alert(1))",
    ]);

    $response = $this->get(route('engagements.show', $engagement));

    $response->assertOk()->assertSee('<strong>lobby</strong>', false);
    expect($response->getContent())
        ->not->toContain('<script>window.pwned')
        ->not->toContain('href="javascript:')
        ->not->toContain('I\'ll deliver in <b>two</b>');
});

it('shows skills and software only when the job has them', function () {
    $withTags = wsEngagement($this->me, $this->other, EngagementStatus::Active, asFreelancer: true, job: ['skills' => ['3D modelling'], 'software' => []]);

    $this->get(route('engagements.show', $withTags))
        ->assertOk()
        ->assertSee('3D modelling')
        ->assertSee('Skills')
        ->assertDontSee('Software');
});

it('builds an activity timeline from deliverables, payments and cancellation, newest first', function () {
    $engagement = wsEngagement($this->me, $this->other, EngagementStatus::Cancelled, asFreelancer: false);
    $engagement->update(['employer_accepted_at' => now()->subDays(10), 'started_at' => now()->subDays(9), 'cancelled_at' => now()->subDay()]);
    wsDeliverable($engagement, 'approved', 'Massing model', [
        'submitted_at' => now()->subDays(5),
        'approved_at' => now()->subDays(4),
        'feedback' => 'Lovely work',
    ]);
    JobCancellation::create([
        'engagement_id' => $engagement->id,
        'initiator_id' => $this->me->id,
        'cancellation_type' => 'client_initiated',
        'reason_category' => 'project_scope_change',
        'reason_details' => 'The brief changed',
    ]);

    $this->get(route('engagements.show', $engagement))
        ->assertOk()
        ->assertSeeInOrder(['Engagement cancelled', 'Approved: Massing model', 'Submitted: Massing model', 'Work started', 'Offer sent'])
        ->assertSee('Project scope changed')
        ->assertSee('The brief changed')
        ->assertSee('Lovely work')
        ->assertDontSee('project_scope_change');
});

it('renders a disputed engagement with its dispute on the timeline', function () {
    $engagement = wsEngagement($this->me, $this->other, EngagementStatus::Disputed, asFreelancer: true);
    $cancellation = JobCancellation::create([
        'engagement_id' => $engagement->id, 'initiator_id' => $this->other->id, 'cancellation_type' => 'dispute',
        'reason_category' => 'other', 'reason_details' => 'x', 'partial_payment_amount' => 300, 'is_dispute' => true,
    ]);
    JobPaymentDispute::create([
        'cancellation_id' => $cancellation->id, 'disputed_by' => $this->me->id, 'dispute_reason' => 'incorrect_amount',
        'dispute_details' => 'The amount is wrong', 'status' => DisputeStatus::Pending,
    ]);

    $this->get(route('engagements.show', $engagement))
        ->assertOk()
        ->assertSee('Payment disputed')
        ->assertSee('The amount is wrong')
        ->assertSee('View dispute');
});

it('shows an empty state for the activity of a brand-new offer', function () {
    $engagement = wsEngagement($this->me, $this->other, EngagementStatus::EmployerAccepted, asFreelancer: false);

    $this->get(route('engagements.show', $engagement))
        ->assertOk()
        ->assertSee('Waiting for the freelancer to respond to your offer.');
});

it('includes the cancellation modal only while the engagement can still be cancelled', function () {
    $active = wsEngagement($this->me, $this->other, EngagementStatus::Active, asFreelancer: false);
    $done = wsEngagement($this->me, $this->other, EngagementStatus::Completed, asFreelancer: false);

    $this->get(route('engagements.show', $active))->assertOk()->assertSee('cancel-engagement-'.$active->id, false);
    $this->get(route('engagements.show', $done))->assertOk()->assertDontSee('cancel-engagement-'.$done->id, false);
});
