<?php

use App\Enums\ApplicationStatus;
use App\Enums\EngagementStatus;
use App\Models\ApplicantMessage;
use App\Models\JobApplication;
use App\Models\JobEngagement;
use App\Models\JobReview;
use App\Models\MessageTemplate;
use App\Models\ModelJob;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

/*
 * The poster's side of applications: the list for one project (compare bids, filter, quick actions), the
 * details page (decide, hire, message, quick templates) and the rules behind hiring. Lazy loading is blocked
 * outside production, so each request also proves the page eager-loads what it reads.
 */

beforeEach(function () {
    Notification::fake();

    $this->me = User::factory()->create(['name' => 'Amina Otieno']);
    $this->actingAs($this->me);
    $this->job = ModelJob::factory()->create(['user_id' => $this->me->id, 'title' => 'Lobby walkthrough', 'budget' => 1000]);
});

function paApplication(array $attributes = [], ?ModelJob $job = null): JobApplication
{
    $job ??= test()->job;

    return JobApplication::factory()->create(array_merge([
        'job_id' => $job->id,
        'poster_id' => $job->user_id,
        'applicant_id' => User::factory(),
        'status' => ApplicationStatus::Submitted,
        'offer_amount' => 900,
        'service_fee' => 90,
        'net_amount' => 810,
        'proposal' => 'I can start this week.',
    ], $attributes));
}

function paEngagement(JobApplication $application, EngagementStatus $status): JobEngagement
{
    return JobEngagement::create([
        'application_id' => $application->id, 'status' => $status,
        'agreed_amount' => 1000, 'service_fee' => 100, 'net_amount' => 900,
    ]);
}

/** ------------------------------------------------------------------- list */
it('lists the applications for the poster’s project with bids compared to the budget', function () {
    paApplication(['applicant_id' => User::factory()->create(['name' => 'Zawadi Mwangi']), 'offer_amount' => 800]);
    paApplication(['applicant_id' => User::factory()->create(['name' => 'Kiprono Rotich']), 'offer_amount' => 1200, 'status' => ApplicationStatus::Reviewed]);
    paApplication(['applicant_id' => User::factory()->create(['name' => 'Drafts Only']), 'status' => ApplicationStatus::Draft]);

    $this->get(route('my-jobs.applications.index', $this->job->slug))
        ->assertOk()
        ->assertSee('Lobby walkthrough')
        ->assertSee('Zawadi Mwangi')
        ->assertSee('Kiprono Rotich')
        ->assertDontSee('Drafts Only')
        ->assertSee('20% below budget')
        ->assertSee('20% above budget')
        ->assertSee('Bids at a glance')
        ->assertSeeInOrder(['All', '2', 'New', '1', 'Reviewed', '1']);
});

it('only opens the list for the project’s owner', function () {
    $theirs = ModelJob::factory()->create(['user_id' => User::factory()]);

    $this->get(route('my-jobs.applications.index', $theirs->slug))->assertNotFound();
});

it('filters by status and searches applicants', function () {
    paApplication(['applicant_id' => User::factory()->create(['name' => 'Zawadi Mwangi', 'email' => 'zawadi@example.test'])]);
    paApplication(['applicant_id' => User::factory()->create(['name' => 'Kiprono Rotich']), 'status' => ApplicationStatus::Reviewed]);

    $this->get(route('my-jobs.applications.index', ['slug' => $this->job->slug, 'status' => 'reviewed']))->assertOk()->assertSee('Kiprono Rotich')->assertDontSee('Zawadi Mwangi');
    $this->get(route('my-jobs.applications.index', ['slug' => $this->job->slug, 'search' => 'zawadi@']))->assertOk()->assertSee('Zawadi Mwangi')->assertDontSee('Kiprono Rotich');
    $this->get(route('my-jobs.applications.index', ['slug' => $this->job->slug, 'search' => '']))->assertOk(); // an empty search box is fine
    $this->get(route('my-jobs.applications.index', ['slug' => $this->job->slug, 'search' => 'nobody']))->assertOk()->assertSee('No applications match');
});

it('sorts by offer', function () {
    paApplication(['applicant_id' => User::factory()->create(['name' => 'Cheap Bidder']), 'offer_amount' => 300]);
    paApplication(['applicant_id' => User::factory()->create(['name' => 'Pricey Bidder']), 'offer_amount' => 1900]);

    $this->get(route('my-jobs.applications.index', ['slug' => $this->job->slug, 'sort' => 'offer_low']))->assertOk()->assertSeeInOrder(['Cheap Bidder', 'Pricey Bidder']);
    $this->get(route('my-jobs.applications.index', ['slug' => $this->job->slug, 'sort' => 'offer_high']))->assertOk()->assertSeeInOrder(['Pricey Bidder', 'Cheap Bidder']);
});

it('shows each applicant’s rating and offers quick actions only where they apply', function () {
    $rated = User::factory()->create(['name' => 'Rated Freelancer']);
    $new = paApplication(['applicant_id' => $rated]);
    $rejected = paApplication(['status' => ApplicationStatus::Rejected]);
    $hiredJob = ModelJob::factory()->create(['user_id' => $this->me->id]);
    $hired = paApplication(['status' => ApplicationStatus::Hired], $hiredJob);
    paEngagement($hired, EngagementStatus::Active);
    $other = paApplication([], $hiredJob);
    JobReview::create(['engagement_id' => paEngagement(paApplication([], ModelJob::factory()->create(['user_id' => User::factory()])), EngagementStatus::Completed)->id, 'reviewer_id' => User::factory()->create()->id, 'reviewee_id' => $rated->id, 'rating' => 4, 'review' => 'Good', 'is_public' => true]);

    $this->get(route('my-jobs.applications.index', $this->job->slug))
        ->assertOk()
        ->assertSee('4.0')
        ->assertSee('Mark reviewed')                          // only the new one
        ->assertSee(route('my-jobs.applications.show', ['application' => $new, 'hire' => 1]), false)
        ->assertDontSee(route('my-jobs.applications.show', ['application' => $rejected, 'hire' => 1]), false);

    // Once someone is hired, nobody else can be hired from the list.
    $this->get(route('my-jobs.applications.index', $hiredJob->slug))
        ->assertOk()
        ->assertSee('You have hired for this project')
        ->assertDontSee(route('my-jobs.applications.show', ['application' => $other, 'hire' => 1]), false);
});

it('shows an empty state when nobody has applied', function () {
    $this->get(route('my-jobs.applications.index', $this->job->slug))->assertOk()->assertSee('No applications yet');
});

/** ---------------------------------------------------------------- details */
it('shows the application with proposal, offer and decision actions', function () {
    $application = paApplication(['applicant_id' => User::factory()->create(['name' => 'Zawadi Mwangi', 'email' => 'zawadi@example.test']), 'proposal' => "Plan: <b>fast</b>\nSecond line", 'additional_notes' => 'Strong portfolio']);

    $response = $this->get(route('my-jobs.applications.show', $application))
        ->assertOk()
        ->assertSee('Zawadi Mwangi')
        ->assertSee('zawadi@example.test')
        ->assertSee('10% below')
        ->assertSee('Plan: &lt;b&gt;fast&lt;/b&gt;', false)   // proposals are plain text
        ->assertSee('Strong portfolio')
        ->assertSee('Hire')
        ->assertSee('Reject')
        ->assertSee('Mark reviewed')
        ->assertSee('Quick templates')
        ->assertSee('hire-applicant', false)
        ->assertSee('reject-application', false);

    expect($response->getContent())->not->toContain('<b>fast</b>');
});

it('keeps other people out of an application', function () {
    $theirs = paApplication([], ModelJob::factory()->create(['user_id' => User::factory()]));

    $this->get(route('my-jobs.applications.show', $theirs))->assertForbidden();
    $this->patch(route('my-jobs.applications.update-status', $theirs), ['status' => 'reviewed'])->assertForbidden();
    $this->post(route('my-jobs.applications.confirm-hire', $theirs))->assertForbidden();
    $this->post(route('my-jobs.applications.send-message', $theirs), ['subject' => 's', 'message' => 'm'])->assertForbidden();
});

it('marks reviewed, rejects, and reconsiders', function () {
    $application = paApplication();

    $this->patch(route('my-jobs.applications.update-status', $application), ['status' => 'reviewed'])->assertRedirect();
    expect($application->fresh()->status)->toBe(ApplicationStatus::Reviewed);

    $this->patch(route('my-jobs.applications.update-status', $application), ['status' => 'rejected'])->assertRedirect();
    expect($application->fresh()->status)->toBe(ApplicationStatus::Rejected);

    $this->get(route('my-jobs.applications.show', $application))->assertOk()->assertSee('Reconsider');
    $this->patch(route('my-jobs.applications.update-status', $application), ['status' => 'reviewed'])->assertRedirect();
    expect($application->fresh()->status)->toBe(ApplicationStatus::Reviewed);
});

it('saves private notes without changing the status, even on a hired application', function () {
    $application = paApplication(['status' => ApplicationStatus::Hired]);

    $this->patch(route('my-jobs.applications.update-status', $application), ['status' => 'hired', 'notes' => 'Great to work with'])->assertRedirect();

    $fresh = $application->fresh();
    expect($fresh->status)->toBe(ApplicationStatus::Hired)->and($fresh->additional_notes)->toBe('Great to work with');
});

it('does not let a hired or withdrawn application change status', function () {
    $hired = paApplication(['status' => ApplicationStatus::Hired]);
    $withdrawn = paApplication(['status' => ApplicationStatus::Withdrawn]);

    $this->patch(route('my-jobs.applications.update-status', $hired), ['status' => 'rejected'])->assertRedirect()->assertSessionHas('alert');
    $this->patch(route('my-jobs.applications.update-status', $withdrawn), ['status' => 'reviewed'])->assertRedirect()->assertSessionHas('alert');

    expect($hired->fresh()->status)->toBe(ApplicationStatus::Hired)->and($withdrawn->fresh()->status)->toBe(ApplicationStatus::Withdrawn);
});

it('sends "hired" through the confirmation step on the details page', function () {
    $application = paApplication();

    $this->patch(route('my-jobs.applications.update-status', $application), ['status' => 'hired'])
        ->assertRedirect(route('my-jobs.applications.show', ['application' => $application, 'hire' => 1]));

    expect($application->fresh()->status)->toBe(ApplicationStatus::Submitted);

    $this->get(route('my-jobs.applications.show', ['application' => $application, 'hire' => 1]))->assertOk()->assertSee('Confirm hire');
});

it('hires with deliverables and creates the engagement', function () {
    $application = paApplication();

    $this->post(route('my-jobs.applications.confirm-hire', $application), [
        'deliverables' => [
            ['title' => 'Massing model', 'description' => 'First pass', 'due_date' => now()->addWeek()->toDateString()],
            ['title' => 'Final renders', 'description' => '', 'due_date' => ''],
        ],
    ])->assertRedirect();

    $engagement = JobEngagement::where('application_id', $application->id)->sole();

    expect($application->fresh()->status)->toBe(ApplicationStatus::Hired)
        ->and($engagement->status)->toBe(EngagementStatus::EmployerAccepted)
        ->and($engagement->deliverables)->toHaveCount(2);
});

it('hires without deliverables', function () {
    $application = paApplication();

    $this->post(route('my-jobs.applications.confirm-hire', $application))->assertRedirect();

    expect(JobEngagement::where('application_id', $application->id)->exists())->toBeTrue();
});

it('requires a title for every deliverable', function () {
    $application = paApplication();

    $this->post(route('my-jobs.applications.confirm-hire', $application), ['deliverables' => [['title' => '', 'description' => 'x']]])
        ->assertSessionHasErrors('deliverables.0.title');

    expect(JobEngagement::count())->toBe(0);
});

it('will not hire twice, hire someone else for a filled project, or hire withdrawn or archived applications', function () {
    $first = paApplication();
    $second = paApplication();
    $withdrawn = paApplication(['status' => ApplicationStatus::Withdrawn]);

    $this->post(route('my-jobs.applications.confirm-hire', $first))->assertRedirect();
    $this->post(route('my-jobs.applications.confirm-hire', $first))->assertRedirect()->assertSessionHas('alert');   // again
    $this->post(route('my-jobs.applications.confirm-hire', $second))->assertRedirect()->assertSessionHas('alert');  // someone else
    $this->post(route('my-jobs.applications.confirm-hire', $withdrawn))->assertRedirect()->assertSessionHas('alert');

    expect(JobEngagement::count())->toBe(1);

    $archivedJob = ModelJob::factory()->create(['user_id' => $this->me->id, 'is_archived' => true, 'is_active' => false]);
    $this->post(route('my-jobs.applications.confirm-hire', paApplication([], $archivedJob)))->assertRedirect()->assertSessionHas('alert');
    expect(JobEngagement::count())->toBe(1);
});

it('lets a cancelled hire be replaced by another applicant', function () {
    $first = paApplication(['status' => ApplicationStatus::Hired]);
    paEngagement($first, EngagementStatus::Cancelled);
    $second = paApplication();

    $this->post(route('my-jobs.applications.confirm-hire', $second))->assertRedirect();

    expect(JobEngagement::where('application_id', $second->id)->exists())->toBeTrue();
});

it('shows why hiring is unavailable instead of the Hire button', function () {
    $hiredJob = ModelJob::factory()->create(['user_id' => $this->me->id]);
    paEngagement(paApplication(['status' => ApplicationStatus::Hired], $hiredJob), EngagementStatus::Active);
    $other = paApplication([], $hiredJob);

    $this->get(route('my-jobs.applications.show', $other))
        ->assertOk()
        ->assertSee('You have already hired someone for this project.')
        ->assertDontSee('hire-applicant', false);
});

it('messages the applicant and keeps a record of what was sent', function () {
    $application = paApplication();

    $this->post(route('my-jobs.applications.send-message', $application), ['subject' => 'Interview invite', 'message' => 'Can you talk on Friday?'])->assertRedirect();

    expect(ApplicantMessage::where('job_application_id', $application->id)->sole()->subject)->toBe('Interview invite');

    $this->get(route('my-jobs.applications.show', $application))->assertOk()->assertSee('Interview invite')->assertSee('Can you talk on Friday?');
    $this->post(route('my-jobs.applications.send-message', $application), ['subject' => '', 'message' => ''])->assertSessionHasErrors(['subject', 'message']);
});

/** ------------------------------------------------------------ quick templates */
it('renders the quick-templates modal wired to the JSON endpoints', function () {
    $application = paApplication();

    $response = $this->get(route('my-jobs.applications.show', $application))->assertOk();

    $response->assertSee('message-templates', false)
        ->assertSee('max-w-6xl', false)           // the wide layout: three template cards per row
        ->assertSee('Quick templates')
        ->assertSee('message-template-selected', false)
        ->assertSee(str_replace('/', '\/', route('my-jobs.message-templates')), false)
        ->assertSee(str_replace('/', '\/', route('my-jobs.message-templates.store')), false);

    expect($response->getContent())->not->toContain('id="showTemplates"');
});

it('lists the poster’s own templates and the default ones, never anyone else’s', function () {
    MessageTemplate::create(['user_id' => $this->me->id, 'name' => 'Mine', 'subject' => 's', 'message' => 'm']);
    MessageTemplate::create(['user_id' => null, 'name' => 'Default one', 'subject' => 's', 'message' => 'm']);
    MessageTemplate::create(['user_id' => User::factory()->create()->id, 'name' => 'Somebody else’s', 'subject' => 's', 'message' => 'm']);

    $names = collect($this->getJson(route('my-jobs.message-templates'))->assertOk()->json())->pluck('name')->all();

    expect($names)->toContain('Mine', 'Default one')->not->toContain('Somebody else’s');
});

it('creates and deletes the poster’s own templates but never a default or someone else’s', function () {
    $this->postJson(route('my-jobs.message-templates.store'), ['name' => 'Follow up', 'subject' => 'Checking in', 'message' => 'Any update?'])
        ->assertSuccessful()->assertJsonPath('name', 'Follow up');

    $mine = MessageTemplate::where('name', 'Follow up')->sole();
    $default = MessageTemplate::create(['user_id' => null, 'name' => 'Default', 'subject' => 's', 'message' => 'm']);
    $theirs = MessageTemplate::create(['user_id' => User::factory()->create()->id, 'name' => 'Theirs', 'subject' => 's', 'message' => 'm']);

    $this->deleteJson(route('my-jobs.message-templates.destroy', $default))->assertForbidden();
    $this->deleteJson(route('my-jobs.message-templates.destroy', $theirs))->assertForbidden();
    $this->deleteJson(route('my-jobs.message-templates.destroy', $mine))->assertOk();

    expect(MessageTemplate::find($mine->id))->toBeNull()->and(MessageTemplate::find($default->id))->not->toBeNull();

    $this->postJson(route('my-jobs.message-templates.store'), ['name' => '', 'subject' => '', 'message' => ''])->assertUnprocessable()->assertJsonValidationErrors(['name', 'subject', 'message']);
});
