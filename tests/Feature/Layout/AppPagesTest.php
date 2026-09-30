<?php

use App\Enums\EngagementStatus;
use App\Models\JobApplication;
use App\Models\JobCancellation;
use App\Models\JobDeliverable;
use App\Models\JobEngagement;
use App\Models\Message;
use App\Models\ModelJob;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

/*
 * Every signed-in page must render inside the sidebar shell, keep its breadcrumb (page titles live there now,
 * not in a per-page header slot) and never emit the retired header/footer markup.
 */

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

const SHELL = 'aria-label="Main navigation"';

/** Asserts the page is inside the shell, has the given crumbs in the breadcrumb, and has no embedded marketing footer. */
function assertInShell($response, array $crumbs = []): void
{
    $response->assertOk()
        ->assertSee(SHELL, false)
        ->assertDontSee('Connect With Us')
        ->assertSee('aria-label="Breadcrumb"', false);

    foreach ($crumbs as $crumb) {
        $response->assertSee($crumb);
    }
}

it('renders menu pages inside the shell with their breadcrumb', function (string $route, array $crumbs) {
    assertInShell($this->get(route($route)), $crumbs);
})->with([
    'dashboard' => ['dashboard', ['Overview', 'Dashboard']],
    'notifications' => ['notifications.index', ['Overview', 'Notifications']],
    'profile' => ['profile', ['Account', 'Profile']],
    'browse' => ['jobs.browse', ['Find work', 'Browse projects']],
    'job board home' => ['jobs.index', ['Find work', 'Browse projects']],
    'my applications' => ['applications.my', ['Find work', 'My applications']],
    'archived applications' => ['applications.archived', ['My applications', 'Archived']],
    'drafts' => ['applications.drafts', ['Find work', 'Drafts']],
    'post a project' => ['jobs.create', ['Hire', 'Post a project']],
    'posted projects' => ['my-jobs.index', ['Hire', 'Posted projects']],
    'archived posted projects' => ['my-jobs.archived.posted-jobs', ['Posted projects', 'Archived']],
    'projects' => ['project.index', ['Delivery', 'Projects']],
    'engagements' => ['engagements.index', ['Delivery', 'Engagements']],
    'archived engagements' => ['engagements.archived', ['Engagements', 'Archived']],
    'cancellation policy' => ['engagements.policy', ['Help', 'Cancellation policy']],
]);

it('uses the job title as the breadcrumb tail on the owner project pages', function () {
    $job = ModelJob::factory()->create(['user_id' => $this->user->id, 'title' => 'Modern villa exterior render']);

    assertInShell($this->get(route('jobs.show', $job->slug)), ['Posted projects', 'Modern villa exterior render']);
    assertInShell($this->get(route('jobs.edit', $job->slug)), ['Posted projects', 'Edit: Modern villa exterior render']);
    assertInShell($this->get(route('my-jobs.applications.index', $job->slug)), ['Applications: Modern villa exterior render']);
});

it('renders the public apply page in the shell when signed in, titled by the project', function () {
    $job = ModelJob::factory()->create(['title' => 'Lobby interior visualisation']);

    assertInShell($this->get(route('jobs.apply', $job->slug)), ['Browse projects', 'Lobby interior visualisation']);
});

it('renders application pages for the applicant', function () {
    $job = ModelJob::factory()->create();
    JobApplication::factory()->create(['job_id' => $job->id, 'applicant_id' => $this->user->id, 'poster_id' => $job->user_id]);

    assertInShell($this->get(route('applications.show', $job->slug)), ['My applications', 'Application details']);
});

it('renders the draft resume page for the applicant', function () {
    $job = ModelJob::factory()->create();
    JobApplication::factory()->draft()->create(['job_id' => $job->id, 'applicant_id' => $this->user->id, 'poster_id' => $job->user_id]);

    assertInShell($this->get(route('applications.continue', $job->slug)), ['My applications', 'Resume application']);
});

it('renders the poster views of an application', function () {
    $job = ModelJob::factory()->create(['user_id' => $this->user->id]);
    $application = JobApplication::factory()->create(['job_id' => $job->id, 'poster_id' => $this->user->id]);

    assertInShell($this->get(route('my-jobs.applications.show', $application)), ['Posted projects', 'Application details']);
});

it('renders an archived posted project', function () {
    $job = ModelJob::factory()->create(['user_id' => $this->user->id, 'is_archived' => true, 'title' => 'Archived villa']);

    assertInShell($this->get(route('my-jobs.archived.show', $job)), ['Posted projects', 'Archived villa']);
});

it('renders engagement pages with the job title as the tail', function () {
    $application = JobApplication::factory()->hired()->create([
        'applicant_id' => $this->user->id,
        'job_id' => ModelJob::factory()->create(['title' => 'Hospital lobby model']),
    ]);
    $engagement = JobEngagement::create([
        'application_id' => $application->id,
        'status' => EngagementStatus::Cancelled,
        'agreed_amount' => $application->offer_amount,
        'service_fee' => $application->service_fee,
        'net_amount' => $application->net_amount,
    ]);

    // One deliverable: the cancelled-engagement page divides by the deliverable count (see the note in the summary).
    JobDeliverable::create([
        'engagement_id' => $engagement->id,
        'title' => 'First draft',
        'description' => 'Initial massing model',
        'due_date' => now()->addWeek(),
        'status' => 'pending',
    ]);

    JobCancellation::create([
        'engagement_id' => $engagement->id,
        'initiator_id' => $this->user->id,
        'cancellation_type' => 'client_initiated',
        'reason_category' => 'other',
        'reason_details' => 'test reason',
    ]);

    assertInShell($this->get(route('engagements.show-cancelled', $engagement->id)), ['Engagements', 'Hospital lobby model']);
});

it('keeps a back link on the pages whose parent is not a menu item', function () {
    $job = ModelJob::factory()->create(['user_id' => $this->user->id]);
    $application = JobApplication::factory()->create(['job_id' => $job->id, 'poster_id' => $this->user->id]);

    $this->get(route('my-jobs.applications.show', $application))->assertOk()
        ->assertSee(route('my-jobs.applications.index', ['slug' => $job->slug]), false)
        ->assertSee('Back to Applications');
});

it('shows the job board tabs and archive shortcuts in the page toolbar', function () {
    $this->get(route('jobs.index'))->assertOk()->assertSee('Post a Project')->assertSee('Find a Project');
    $this->get(route('applications.my'))->assertOk()->assertSee('View Archived');
    $this->get(route('my-jobs.index'))->assertOk()->assertSee('Archived Jobs');
});

it('titles the browser tab after the current page', function () {
    $this->get(route('notifications.index'))->assertOk()->assertSee('<title>Notifications · ', false);
});

it('renders the engagements list with real engagements linking to their workspace and the row-level cues', function () {
    $client = User::factory()->create(['name' => 'Kevin Mwangi']);

    // As the client: a submitted deliverable to approve or send back.
    $asClient = JobEngagement::create([
        'application_id' => JobApplication::factory()->hired()->create([
            'job_id' => ModelJob::factory()->create(['user_id' => $this->user->id, 'title' => 'Client-side job']),
            'applicant_id' => $client->id, 'poster_id' => $this->user->id,
        ])->id,
        'status' => EngagementStatus::Active, 'agreed_amount' => 1100, 'service_fee' => 100, 'net_amount' => 1000,
    ]);
    JobDeliverable::create(['engagement_id' => $asClient->id, 'title' => 'Massing model', 'description' => 'd', 'due_date' => now()->addDays(3)->toDateString(), 'status' => 'submitted']);
    Message::create(['engagement_id' => $asClient->id, 'sender_id' => $client->id, 'content' => 'Ping']);

    // As the freelancer: a rejected deliverable to resubmit.
    $asFreelancer = JobEngagement::create([
        'application_id' => JobApplication::factory()->hired()->create([
            'job_id' => ModelJob::factory()->create(['user_id' => $client->id, 'title' => 'Freelance-side job']),
            'applicant_id' => $this->user->id, 'poster_id' => $client->id,
        ])->id,
        'status' => EngagementStatus::Active, 'agreed_amount' => 900, 'service_fee' => 90, 'net_amount' => 810,
    ]);
    JobDeliverable::create(['engagement_id' => $asFreelancer->id, 'title' => 'Lighting pass', 'description' => 'd', 'due_date' => now()->addDays(3)->toDateString(), 'status' => 'rejected']);

    $response = $this->get(route('engagements.index'));

    assertInShell($response, ['Client-side job', 'Freelance-side job']);
    $response->assertSee(route('engagements.show', $asClient), false)
        ->assertSee(route('engagements.show', $asFreelancer), false)
        ->assertSee('Review work')
        ->assertSee('Revise');
});
