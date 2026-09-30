<?php

use App\Enums\ApplicationStatus;
use App\Enums\EngagementStatus;
use App\Models\ApplicantMessage;
use App\Models\JobApplication;
use App\Models\JobEngagement;
use App\Models\ModelJob;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/*
 * The freelancer's side of applications: My applications (filters, where each one stands, archive), Drafts,
 * the draft form, the submitted-application page and the rules behind them. Lazy loading is blocked outside
 * production, so each request also proves the page eager-loads what it reads; every page test asserts OK first.
 */

beforeEach(function () {
    $this->me = User::factory()->create(['name' => 'Kevin Mwangi']);
    $this->client = User::factory()->create(['name' => 'Amina Otieno']);
    $this->actingAs($this->me);
});

function maJob(array $attributes = []): ModelJob
{
    return ModelJob::factory()->create(array_merge(['user_id' => test()->client->id], $attributes));
}

function maApplication(ModelJob $job, array $attributes = [], ?User $applicant = null): JobApplication
{
    return JobApplication::factory()->create(array_merge([
        'job_id' => $job->id,
        'poster_id' => $job->user_id,
        'applicant_id' => ($applicant ?? test()->me)->id,
        'status' => ApplicationStatus::Submitted,
        'offer_amount' => 900,
        'service_fee' => 90,
        'net_amount' => 810,
        'proposal' => 'I can start this week.',
    ], $attributes));
}

function maEngagement(JobApplication $application, EngagementStatus $status): JobEngagement
{
    return JobEngagement::create([
        'application_id' => $application->id,
        'status' => $status,
        'agreed_amount' => $application->offer_amount,
        'service_fee' => $application->service_fee,
        'net_amount' => $application->net_amount,
    ]);
}

/** ---------------------------------------------------------------- my applications */
it('lists only my sent, unarchived applications', function () {
    maApplication(maJob(['title' => 'Lobby walkthrough']));
    maApplication(maJob(['title' => 'Draft-only villa']), ['status' => ApplicationStatus::Draft]);
    maApplication(maJob(['title' => 'Archived pavilion']), ['is_archived' => true, 'status' => ApplicationStatus::Rejected]);
    maApplication(maJob(['title' => 'Someone elses tower']), [], User::factory()->create());

    $this->get(route('applications.my'))
        ->assertOk()
        ->assertSee('My applications')
        ->assertSee('Lobby walkthrough')
        ->assertDontSee('Draft-only villa')
        ->assertDontSee('Archived pavilion')
        ->assertDontSee('Someone elses tower');
});

it('shows an empty state with a way forward', function () {
    $this->get(route('applications.my'))->assertOk()->assertSee('No applications yet')->assertSee(route('jobs.browse'), false);
});

it('counts applications per status in the filter pills and filters by status', function () {
    maApplication(maJob(['title' => 'Waiting model']));
    maApplication(maJob(['title' => 'Looked at model']), ['status' => ApplicationStatus::Reviewed]);
    maApplication(maJob(['title' => 'Turned down model']), ['status' => ApplicationStatus::Rejected]);

    $this->get(route('applications.my'))->assertOk()->assertSeeInOrder(['All', '3', 'Submitted', '1', 'Reviewed', '1', 'Rejected', '1']);

    $this->get(route('applications.my', ['status' => 'rejected']))
        ->assertOk()
        ->assertSee('Turned down model')
        ->assertDontSee('Waiting model')
        ->assertDontSee('Looked at model');
});

it('searches by project title and sorts by offer', function () {
    maApplication(maJob(['title' => 'Harbour crane rig']), ['offer_amount' => 500]);
    maApplication(maJob(['title' => 'Villa render']), ['offer_amount' => 2500]);

    $this->get(route('applications.my', ['search' => 'harbour']))->assertOk()->assertSee('Harbour crane rig')->assertDontSee('Villa render');
    $this->get(route('applications.my', ['sort' => 'offer_high']))->assertOk()->assertSeeInOrder(['Villa render', 'Harbour crane rig']);
    $this->get(route('applications.my', ['sort' => 'offer_low']))->assertOk()->assertSeeInOrder(['Harbour crane rig', 'Villa render']);
    $this->get(route('applications.my', ['sort' => 'nonsense']))->assertSessionHasErrors('sort');
});

it('points an application with a pending offer at the response form', function () {
    $application = maApplication(maJob(['title' => 'Offer project']), ['status' => ApplicationStatus::Hired]);
    maEngagement($application, EngagementStatus::EmployerAccepted);

    $this->get(route('applications.my'))
        ->assertOk()
        ->assertSee('Offer received')
        ->assertSee('Respond to offer')
        ->assertSee(route('engagements.response-form', $application->id), false)
        ->assertDontSee(route('applications.archive', $application), false);
});

it('marks an application as filled only while someone else is live on the project', function () {
    $job = maJob(['title' => 'Filled project']);
    $mine = maApplication($job);
    $rival = maApplication($job, [], User::factory()->create());
    $cancelled = maEngagement($rival, EngagementStatus::Cancelled);

    $this->get(route('applications.my'))->assertOk()->assertSee('Submitted')->assertDontSee('Position filled');

    $cancelled->update(['status' => EngagementStatus::Active]);
    $this->get(route('applications.my'))->assertOk()->assertSee('Position filled')->assertSee(route('applications.archive', $mine), false);
});

it('only offers to archive finished applications', function () {
    $waiting = maApplication(maJob());
    $rejected = maApplication(maJob(), ['status' => ApplicationStatus::Rejected]);

    $this->get(route('applications.my'))
        ->assertOk()
        ->assertDontSee(route('applications.archive', $waiting), false)
        ->assertSee(route('applications.archive', $rejected), false);
});

/** ---------------------------------------------------------------- archive, restore, delete */
it('archives a finished application, refuses one still in play and protects other peoples', function () {
    $waiting = maApplication(maJob());
    $rejected = maApplication(maJob(), ['status' => ApplicationStatus::Rejected]);
    $theirs = maApplication(maJob(), ['status' => ApplicationStatus::Rejected], User::factory()->create());

    $this->post(route('applications.archive', $waiting))->assertRedirect();
    expect($waiting->fresh()->is_archived)->toBeFalse();

    $this->post(route('applications.archive', $rejected))->assertRedirect();
    expect($rejected->fresh()->is_archived)->toBeTrue();

    $this->post(route('applications.archive', $theirs))->assertForbidden();
    expect($theirs->fresh()->is_archived)->toBeFalse();
});

it('does not archive an application while its engagement is live', function () {
    $application = maApplication(maJob(), ['status' => ApplicationStatus::Hired]);
    $engagement = maEngagement($application, EngagementStatus::Active);

    $this->post(route('applications.archive', $application))->assertRedirect();
    expect($application->fresh()->is_archived)->toBeFalse();

    $engagement->update(['status' => EngagementStatus::Completed]);
    $this->post(route('applications.archive', $application))->assertRedirect();
    expect($application->fresh()->is_archived)->toBeTrue();
});

it('lists archived applications with restore and delete, and restores them', function () {
    $archived = maApplication(maJob(['title' => 'Old pavilion']), ['status' => ApplicationStatus::Rejected, 'is_archived' => true]);
    $active = maApplication(maJob(['title' => 'Current tower']));

    $this->get(route('applications.archived'))
        ->assertOk()
        ->assertSee('Archived applications')
        ->assertSee('Old pavilion')
        ->assertDontSee('Current tower')
        ->assertSee(route('applications.restore', $archived), false)
        ->assertSee(route('applications.destroy', $archived), false);

    $this->post(route('applications.restore', $active))->assertRedirect();
    expect($active->fresh()->is_archived)->toBeFalse();

    $this->post(route('applications.restore', $archived))->assertRedirect();
    expect($archived->fresh()->is_archived)->toBeFalse();
});

it('shows an empty archive', function () {
    $this->get(route('applications.archived'))->assertOk()->assertSee('Nothing archived');
});

it('deletes only archived applications of mine that have no engagement', function () {
    $active = maApplication(maJob());
    $archived = maApplication(maJob(), ['status' => ApplicationStatus::Rejected, 'is_archived' => true]);
    $hired = maApplication(maJob(), ['status' => ApplicationStatus::Hired, 'is_archived' => true]);
    maEngagement($hired, EngagementStatus::Completed);
    $theirs = maApplication(maJob(), ['is_archived' => true], User::factory()->create());

    $this->delete(route('applications.destroy', $active))->assertForbidden();
    $this->delete(route('applications.destroy', $hired))->assertRedirect();
    $this->delete(route('applications.destroy', $theirs))->assertForbidden();
    expect(JobApplication::find($active->id))->not->toBeNull()
        ->and(JobApplication::find($hired->id))->not->toBeNull()
        ->and(JobApplication::find($theirs->id))->not->toBeNull();

    $this->delete(route('applications.destroy', $archived))->assertRedirect();
    expect(JobApplication::find($archived->id))->toBeNull();
});

/** ---------------------------------------------------------------- drafts */
it('lists my drafts and flags the ones whose project has closed', function () {
    maApplication(maJob(['title' => 'Open draft project']), ['status' => ApplicationStatus::Draft, 'proposal' => 'Half written pitch']);
    maApplication(maJob(['title' => 'Closed draft project', 'is_active' => false]), ['status' => ApplicationStatus::Draft]);
    maApplication(maJob(['title' => 'Sent project']));

    $this->get(route('applications.drafts'))
        ->assertOk()
        ->assertSee('Open draft project')
        ->assertSee('Half written pitch')
        ->assertSee('Closed draft project')
        ->assertSee('Project closed')
        ->assertDontSee('Sent project');
});

it('shows an empty drafts state', function () {
    $this->get(route('applications.drafts'))->assertOk()->assertSee('No drafts');
});

it('deletes a draft for good so the project can be applied to again', function () {
    $job = maJob();
    $draft = maApplication($job, ['status' => ApplicationStatus::Draft]);

    $this->delete(route('destroy.drafts', $draft))->assertRedirect(route('applications.drafts'));
    expect(JobApplication::withTrashed()->find($draft->id))->toBeNull();

    $this->post(route('applications.store'), ['job_id' => $job->id, 'offer' => 800, 'terms' => '1', 'action' => 'submitted'])
        ->assertRedirect(route('applications.my'));
    expect(JobApplication::where('job_id', $job->id)->where('applicant_id', $this->me->id)->first()->status)->toBe(ApplicationStatus::Submitted);
});

it('never deletes a sent application or someone elses draft through the drafts route', function () {
    $sent = maApplication(maJob());
    $theirs = maApplication(maJob(), ['status' => ApplicationStatus::Draft], User::factory()->create());

    $this->delete(route('destroy.drafts', $sent))->assertRedirect(route('applications.my'));
    $this->delete(route('destroy.drafts', $theirs))->assertForbidden();

    expect(JobApplication::find($sent->id))->not->toBeNull()->and(JobApplication::find($theirs->id))->not->toBeNull();
});

/** ---------------------------------------------------------------- the draft form */
it('resumes a draft with its saved offer, proposal and files', function () {
    $job = maJob(['title' => 'Resume project']);
    maApplication($job, ['status' => ApplicationStatus::Draft, 'offer_amount' => 1200, 'proposal' => 'My saved pitch', 'portfolio' => ['portfolios/sample.png'], 'terms_accepted' => true]);

    $this->get(route('applications.continue', $job->slug))
        ->assertOk()
        ->assertSee('Finish your application')
        ->assertSee('Resume project')
        ->assertSee('My saved pitch')
        ->assertSee('sample.png')
        ->assertSee('name="job_id"', false)
        ->assertDontSee('name="poster_id"', false)
        ->assertDontSee('name="applicant_id"', false);
});

it('warns and blocks sending when the draft project has closed', function () {
    $job = maJob(['is_active' => false]);
    maApplication($job, ['status' => ApplicationStatus::Draft]);

    $this->get(route('applications.continue', $job->slug))->assertOk()->assertSee('no longer accepting applications');
});

it('404s the resume page when there is no draft for that project', function () {
    $job = maJob();
    maApplication($job);

    $this->get(route('applications.continue', $job->slug))->assertNotFound();
});

it('updates the existing draft when it is saved again, without creating another', function () {
    $job = maJob();
    $draft = maApplication($job, ['status' => ApplicationStatus::Draft, 'proposal' => 'First pass']);

    $this->post(route('applications.store'), ['job_id' => $job->id, 'action' => 'draft', 'offer' => 700, 'proposal' => 'Second pass'])
        ->assertRedirect(route('applications.drafts'));

    expect(JobApplication::where('job_id', $job->id)->where('applicant_id', $this->me->id)->count())->toBe(1)
        ->and($draft->fresh()->proposal)->toBe('Second pass')
        ->and((float) $draft->fresh()->offer_amount)->toBe(700.0)
        ->and($draft->fresh()->status)->toBe(ApplicationStatus::Draft);
});

it('turns a draft into the sent application when submitted', function () {
    $job = maJob();
    $draft = maApplication($job, ['status' => ApplicationStatus::Draft]);

    $this->post(route('applications.store'), ['job_id' => $job->id, 'action' => 'submitted', 'offer' => 950, 'terms' => '1', 'proposal' => 'Ready'])
        ->assertRedirect(route('applications.my'));

    expect(JobApplication::where('job_id', $job->id)->count())->toBe(1)
        ->and($draft->fresh()->status)->toBe(ApplicationStatus::Submitted)
        ->and((float) $draft->fresh()->net_amount)->toBe(855.0);
});

it('does not start a second application once one was sent, whatever its status', function () {
    $job = maJob();
    maApplication($job, ['status' => ApplicationStatus::Reviewed]);

    $this->post(route('applications.store'), ['job_id' => $job->id, 'action' => 'submitted', 'offer' => 500, 'terms' => '1'])->assertRedirect();

    expect(JobApplication::where('job_id', $job->id)->where('applicant_id', $this->me->id)->count())->toBe(1);
});

it('caps the proposal at 2500 characters when submitting too', function () {
    $job = maJob();

    $this->post(route('applications.store'), ['job_id' => $job->id, 'action' => 'submitted', 'offer' => 500, 'terms' => '1', 'proposal' => str_repeat('a', 2501)])
        ->assertSessionHasErrors('proposal');
});

it('only lets the form keep or remove files the draft already owns', function () {
    Storage::fake('public');
    Storage::disk('public')->put('portfolios/mine.png', 'x');
    Storage::disk('public')->put('portfolios/drop.png', 'x');
    Storage::disk('public')->put('portfolios/someone-elses.png', 'x');

    $job = maJob();
    $draft = maApplication($job, ['status' => ApplicationStatus::Draft, 'portfolio' => ['portfolios/mine.png', 'portfolios/drop.png']]);

    $this->post(route('applications.store'), [
        'job_id' => $job->id,
        'action' => 'draft',
        'existing_portfolio' => ['portfolios/mine.png', 'portfolios/someone-elses.png'],
        'removed_files' => ['portfolios/drop.png', 'portfolios/someone-elses.png'],
    ])->assertRedirect(route('applications.drafts'));

    expect($draft->fresh()->portfolio)->toBe(['portfolios/mine.png']);
    Storage::disk('public')->assertExists('portfolios/mine.png');
    Storage::disk('public')->assertMissing('portfolios/drop.png');
    Storage::disk('public')->assertExists('portfolios/someone-elses.png');
});

it('allows at most five files in total', function () {
    Storage::fake('public');
    $job = maJob();
    maApplication($job, ['status' => ApplicationStatus::Draft, 'portfolio' => ['portfolios/a.png', 'portfolios/b.png', 'portfolios/c.png', 'portfolios/d.png']]);

    $this->post(route('applications.store'), [
        'job_id' => $job->id,
        'action' => 'draft',
        'existing_portfolio' => ['portfolios/a.png', 'portfolios/b.png', 'portfolios/c.png', 'portfolios/d.png'],
        'portfolio' => [UploadedFile::fake()->image('e.png'), UploadedFile::fake()->image('f.png')],
    ])->assertSessionHasErrors('portfolio');
});

/** ---------------------------------------------------------------- the submitted application */
it('shows my application with its offer, proposal and progress', function () {
    $job = maJob(['title' => 'Showcase project', 'budget' => 1500]);
    maApplication($job, ['status' => ApplicationStatus::Reviewed, 'proposal' => "First line\nSecond line", 'portfolio' => ['portfolios/work.png', 'portfolios/brief.pdf']]);

    $this->get(route('applications.show', $job->slug))
        ->assertOk()
        ->assertSee('Showcase project')
        ->assertSee('Reviewed')
        ->assertSee('The client has looked at your application.')
        ->assertSee('First line')
        ->assertSee('Second line')
        ->assertSee('work.png')
        ->assertSee('brief.pdf')
        ->assertSee('Amina Otieno')
        ->assertSee('Service fee')
        ->assertDontSee('Respond to offer');
});

it('renders the proposal as plain text', function () {
    $job = maJob();
    maApplication($job, ['proposal' => '<script>alert(1)</script> <b>bold</b>']);

    $this->get(route('applications.show', $job->slug))->assertOk()
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertDontSee('<b>bold</b>', false)
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
});

it('shows the messages the client sent about it, and only mine', function () {
    $job = maJob();
    $application = maApplication($job);
    ApplicantMessage::create(['job_application_id' => $application->id, 'sender_id' => $this->client->id, 'recipient_id' => $this->me->id, 'subject' => 'Interview invite', 'message' => 'Can you talk on Friday?']);
    ApplicantMessage::create(['job_application_id' => $application->id, 'sender_id' => $this->client->id, 'recipient_id' => $this->client->id, 'subject' => 'Not for you', 'message' => 'Internal']);

    $this->get(route('applications.show', $job->slug))->assertOk()
        ->assertSee('Messages from the client')
        ->assertSee('Interview invite')
        ->assertSee('Can you talk on Friday?')
        ->assertDontSee('Not for you');
});

it('points to the offer when I was hired and to the workspace once I accepted', function () {
    $job = maJob();
    $application = maApplication($job, ['status' => ApplicationStatus::Hired]);
    $engagement = maEngagement($application, EngagementStatus::EmployerAccepted);

    $this->get(route('applications.show', $job->slug))->assertOk()
        ->assertSee('Offer received')
        ->assertSee(route('engagements.response-form', $application->id), false);

    $engagement->update(['status' => EngagementStatus::Active]);
    $this->get(route('applications.show', $job->slug))->assertOk()
        ->assertSee('In progress')
        ->assertSee('Open workspace')
        ->assertSee(route('engagements.show', $engagement), false);
});

it('offers archive on a finished application and restore on an archived one', function () {
    $job = maJob();
    $application = maApplication($job, ['status' => ApplicationStatus::Rejected]);

    $this->get(route('applications.show', $job->slug))->assertOk()
        ->assertSee('Rejected')
        ->assertSee('The client chose another freelancer.')
        ->assertSee(route('applications.archive', $application), false);

    $application->update(['is_archived' => true]);
    $this->get(route('applications.show', $job->slug))->assertOk()
        ->assertSee(route('applications.restore', $application), false)
        ->assertSee(route('applications.destroy', $application), false);
});

it('sends a draft to the resume page instead of the submitted view', function () {
    $job = maJob();
    maApplication($job, ['status' => ApplicationStatus::Draft]);

    $this->get(route('applications.show', $job->slug))->assertRedirect(route('applications.continue', $job->slug));
});

it('does not show my application to anyone else', function () {
    $job = maJob();
    maApplication($job);
    $this->actingAs(User::factory()->create());

    $this->get(route('applications.show', $job->slug))->assertNotFound();
});
