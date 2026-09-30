<?php

use App\Enums\ApplicationStatus;
use App\Models\JobApplication;
use App\Models\ModelJob;
use App\Models\Skill;
use App\Models\Software;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/*
 * Browse projects and the project/apply page. Lazy loading is blocked outside production, so each request
 * also proves the page eager-loads what it reads; every test asserts OK before anything else so a 500 page
 * cannot satisfy loose text assertions.
 */

beforeEach(function () {
    Cache::flush();
    $this->me = User::factory()->create(['name' => 'Kevin Mwangi']);
    $this->client = User::factory()->create(['name' => 'Amina Otieno']);
    $this->actingAs($this->me);
});

function bjJob(array $attributes = [], ?User $owner = null): ModelJob
{
    return ModelJob::factory()->create(array_merge(['user_id' => ($owner ?? test()->client)->id], $attributes));
}

/** ---------------------------------------------------------------- browse */
it('lists active projects and leaves out closed, archived and expired ones', function () {
    bjJob(['title' => 'Open lobby model']);
    bjJob(['title' => 'Inactive villa', 'is_active' => false]);
    bjJob(['title' => 'Archived tower', 'is_archived' => true]);
    bjJob(['title' => 'Expired pavilion', 'no_deadline' => false, 'deadline' => now()->subDay()]);

    $this->get(route('jobs.browse'))
        ->assertOk()
        ->assertSee('Browse projects')
        ->assertSee('Open lobby model')
        ->assertDontSee('Inactive villa')
        ->assertDontSee('Archived tower')
        ->assertDontSee('Expired pavilion');
});

it('lets guests browse', function () {
    bjJob(['title' => 'Public lobby model']);
    auth()->logout();

    $this->get(route('jobs.browse'))->assertOk()->assertSee('Public lobby model')->assertSee('Apply');
});

it('searches titles and descriptions', function () {
    bjJob(['title' => 'Harbour crane rig', 'description' => 'Rigging a dockside crane']);
    bjJob(['title' => 'Villa render', 'description' => 'Exterior with pool and harbour views']);
    bjJob(['title' => 'Kitchen model', 'description' => 'Interior only']);

    $this->get(route('jobs.browse', ['search' => 'harbour']))
        ->assertOk()
        ->assertSee('Harbour crane rig')
        ->assertSee('Villa render')
        ->assertDontSee('Kitchen model');
});

it('matches projects that need ANY of the ticked skills or software', function () {
    $modelling = Skill::forceCreate(['name' => 'Hard-Surface Modelling', 'is_active' => true]);
    $animation = Skill::forceCreate(['name' => 'Animation Basics', 'is_active' => true]);
    $blender = Software::forceCreate(['name' => 'Blender', 'is_active' => true]);
    $maya = Software::forceCreate(['name' => 'Maya', 'is_active' => true]);

    bjJob(['title' => 'Needs modelling', 'skills' => ['Hard-Surface Modelling'], 'software' => ['Maya']]);
    bjJob(['title' => 'Needs animation', 'skills' => ['Animation Basics'], 'software' => ['Blender']]);
    bjJob(['title' => 'Needs neither', 'skills' => ['Texturing'], 'software' => ['ZBrush']]);

    $this->get(route('jobs.browse', ['skills' => [$modelling->id, $animation->id]]))
        ->assertOk()->assertSee('Needs modelling')->assertSee('Needs animation')->assertDontSee('Needs neither');

    $this->get(route('jobs.browse', ['software' => [$blender->id]]))
        ->assertOk()->assertSee('Needs animation')->assertDontSee('Needs modelling');

    // Skill AND software narrow the results together.
    $this->get(route('jobs.browse', ['skills' => [$modelling->id, $animation->id], 'software' => [$maya->id]]))
        ->assertOk()->assertSee('Needs modelling')->assertDontSee('Needs animation');

    // Old links carried a single id.
    $this->get(route('jobs.browse', ['skills' => $modelling->id]))->assertOk()->assertSee('Needs modelling')->assertDontSee('Needs animation');
});

it('filters by budget range and posting window', function () {
    bjJob(['title' => 'Small gig', 'budget' => 200]);
    bjJob(['title' => 'Medium job', 'budget' => 2000]);
    bjJob(['title' => 'Large contract', 'budget' => 20000]);
    bjJob(['title' => 'Old listing', 'budget' => 2500])->forceFill(['created_at' => now()->subMonths(2)])->save();

    $this->get(route('jobs.browse', ['budget_min' => 1000, 'budget_max' => 5000]))
        ->assertOk()->assertSee('Medium job')->assertSee('Old listing')->assertDontSee('Small gig')->assertDontSee('Large contract');

    $this->get(route('jobs.browse', ['posted' => 'week']))
        ->assertOk()->assertSee('Medium job')->assertDontSee('Old listing');
});

it('sorts by budget', function () {
    bjJob(['title' => 'Cheap one', 'budget' => 100]);
    bjJob(['title' => 'Pricey one', 'budget' => 9000]);

    $this->get(route('jobs.browse', ['sort' => 'budget_high']))->assertOk()->assertSeeInOrder(['Pricey one', 'Cheap one']);
    $this->get(route('jobs.browse', ['sort' => 'budget_low']))->assertOk()->assertSeeInOrder(['Cheap one', 'Pricey one']);
});

it('rejects nonsense filters instead of querying with them', function () {
    $this->get(route('jobs.browse', ['posted' => 'decade']))->assertSessionHasErrors('posted');
    $this->get(route('jobs.browse', ['budget_min' => 500, 'budget_max' => 100]))->assertSessionHasErrors('budget_max');
    $this->get(route('jobs.browse', ['skills' => [999999]]))->assertSessionHasErrors('skills.0');
});

it('returns just the results for the live filter and keeps filters on the page links', function () {
    ModelJob::factory()->count(12)->create(['user_id' => $this->client->id, 'budget' => 1500]);

    $ajax = $this->get(route('jobs.browse', ['budget_min' => 1000]), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();
    $ajax->assertSee('data-jobs-grid', false)->assertSee('12 projects found')->assertDontSee('Browse projects')->assertDontSee('Filters');
    $ajax->assertSee('budget_min=1000', false)->assertSee('page=2', false);
});

it('shows an empty state with a way to clear the filters', function () {
    $this->get(route('jobs.browse', ['search' => 'nothing matches this']))
        ->assertOk()
        ->assertSee('No projects match your filters')
        ->assertSee('data-clear-filters', false);
});

it('shows the right call to action for each project', function () {
    $open = bjJob(['title' => 'Fresh project']);
    $applied = bjJob(['title' => 'Applied project']);
    $draft = bjJob(['title' => 'Drafted project']);
    $hired = bjJob(['title' => 'Hired project']);

    JobApplication::factory()->create(['job_id' => $applied->id, 'applicant_id' => $this->me->id, 'poster_id' => $this->client->id, 'status' => ApplicationStatus::Submitted]);
    JobApplication::factory()->draft()->create(['job_id' => $draft->id, 'applicant_id' => $this->me->id, 'poster_id' => $this->client->id]);
    JobApplication::factory()->hired()->create(['job_id' => $hired->id, 'applicant_id' => $this->me->id, 'poster_id' => $this->client->id]);

    $this->get(route('jobs.browse'))
        ->assertOk()
        ->assertSee(route('jobs.apply', $open->slug), false)
        ->assertSee(route('applications.continue', $draft->slug), false)
        ->assertSee(route('applications.show', $applied->slug), false)
        ->assertSee(route('applications.show', $hired->slug), false)
        ->assertSee('Continue draft')
        ->assertSee('View application');
});

it('shows descriptions as plain text excerpts, never as markup', function () {
    bjJob(['title' => 'Markup project', 'description' => '**Bold** intro <script>window.pwned = 1</script> and <img src=x onerror=alert(1)> [link](javascript:alert(1))']);

    $response = $this->get(route('jobs.browse'))->assertOk()->assertSee('Bold intro');

    expect($response->getContent())->not->toContain('<script>window.pwned')->not->toContain('<img src=x')->not->toContain('javascript:alert');
});

/** ------------------------------------------------------------- apply page */
it('asks guests to sign in instead of showing the form', function () {
    $job = bjJob();
    auth()->logout();

    $this->get(route('jobs.apply', $job->slug))
        ->assertOk()
        ->assertSee('Sign in to apply')
        ->assertSee(route('login'), false)
        ->assertDontSee('name="offer"', false);
});

it('shows signed-in freelancers the proposal form with the service fee, and no identity fields to forge', function () {
    $job = bjJob(['title' => 'Lobby walkthrough', 'budget' => 4800]);

    $response = $this->get(route('jobs.apply', $job->slug))
        ->assertOk()
        ->assertSee('Lobby walkthrough')
        ->assertSee('Amina Otieno')
        ->assertSee('Send your proposal')
        ->assertSee('name="offer"', false)
        ->assertSee('name="proposal"', false)
        ->assertSee('name="portfolio[]"', false)
        ->assertSee('Service fee (10%)')
        ->assertSee('value="'.$job->id.'"', false)
        ->assertSee(':disabled="!(amount', false) // the Alpine binding survives Blade
        ->assertSee('Save draft')
        ->assertSee('Submit application');

    expect($response->getContent())->not->toContain('name="poster_id"')->not->toContain('name="applicant_id"')->not->toContain('name="status"');
});

it('tells the poster this is their own project', function () {
    $job = bjJob([], $this->me);

    $this->get(route('jobs.apply', $job->slug))
        ->assertOk()
        ->assertSee('This is your project')
        ->assertSee(route('my-jobs.applications.index', $job->slug), false)
        ->assertDontSee('name="offer"', false);
});

it('shows a saved draft and a submitted application instead of the form', function () {
    $drafted = bjJob();
    $applied = bjJob();
    JobApplication::factory()->draft()->create(['job_id' => $drafted->id, 'applicant_id' => $this->me->id, 'poster_id' => $this->client->id]);
    JobApplication::factory()->create(['job_id' => $applied->id, 'applicant_id' => $this->me->id, 'poster_id' => $this->client->id, 'status' => ApplicationStatus::Submitted]);

    $this->get(route('jobs.apply', $drafted->slug))->assertOk()->assertSee('You have a saved draft')->assertSee(route('applications.continue', $drafted->slug), false)->assertDontSee('name="offer"', false);
    $this->get(route('jobs.apply', $applied->slug))->assertOk()->assertSee('You applied to this project')->assertSee('waiting for the client')->assertDontSee('name="offer"', false);
});

it('renders the description as Markdown with raw HTML stripped', function () {
    $job = bjJob(['description' => "We need a **walkthrough**.\n\n- Model the lobby\n\n<script>window.pwned = 1</script>\n\n[bad](javascript:alert(1))"]);

    $response = $this->get(route('jobs.apply', $job->slug))->assertOk()->assertSee('<strong>walkthrough</strong>', false)->assertSee('Model the lobby');

    expect($response->getContent())->not->toContain('<script>window.pwned')->not->toContain('href="javascript:');
});

it('lists requirements only when the project has them and shows similar projects', function () {
    $job = bjJob(['skills' => ['Hard-Surface Modelling'], 'software' => ['Blender']]);
    bjJob(['title' => 'Shares a skill', 'skills' => ['Hard-Surface Modelling']]);
    bjJob(['title' => 'Shares software', 'software' => ['Blender']]);
    bjJob(['title' => 'Quote "tag" project', 'skills' => ['Say "hello"']]);

    $this->get(route('jobs.apply', $job->slug))
        ->assertOk()
        ->assertSee('What the client is looking for')
        ->assertSee('Hard-Surface Modelling')
        ->assertSee('Similar projects')
        ->assertSee('Shares a skill')
        ->assertSee('Shares software');

    $plain = bjJob();
    $this->get(route('jobs.apply', $plain->slug))->assertOk()->assertDontSee('What the client is looking for');
});

it('finds similar projects for tags containing quotes without breaking the query', function () {
    $job = bjJob(['skills' => ['Say "hello"', "O'Brien rigs"]]);
    bjJob(['title' => 'Quoted match', 'skills' => ['Say "hello"']]);

    $this->get(route('jobs.apply', $job->slug))->assertOk()->assertSee('Quoted match');
});

/** ------------------------------------------------------ submitting an application */
it('always records the signed-in user as applicant and the project owner as poster', function () {
    Storage::fake('local');
    Storage::fake('public');
    $job = bjJob();
    $attacker = User::factory()->create();

    $this->post(route('applications.store'), [
        'job_id' => $job->id,
        'poster_id' => $this->me->id,       // forged: pretend to be the poster
        'applicant_id' => $attacker->id,    // forged: apply as someone else
        'status' => 'hired',                // forged: skip the review queue
        'offer' => 1000,
        'proposal' => 'I can do this.',
        'terms' => '1',
        'action' => 'submitted',
    ])->assertRedirect(route('applications.my'));

    $application = JobApplication::where('job_id', $job->id)->sole();

    expect($application->applicant_id)->toBe($this->me->id)
        ->and($application->poster_id)->toBe($this->client->id)
        ->and($application->status)->toBe(ApplicationStatus::Submitted)
        ->and((float) $application->service_fee)->toBe(100.0)
        ->and((float) $application->net_amount)->toBe(900.0);
});

it('refuses applications to your own project', function () {
    $job = bjJob([], $this->me);

    $this->post(route('applications.store'), ['job_id' => $job->id, 'offer' => 1000, 'proposal' => 'Hire me', 'terms' => '1', 'action' => 'submitted'])
        ->assertRedirect();

    expect(JobApplication::where('job_id', $job->id)->exists())->toBeFalse();
});

it('limits portfolio uploads to five files', function () {
    Storage::fake('local');
    $job = bjJob();
    $files = collect(range(1, 6))->map(fn ($i) => UploadedFile::fake()->image("sample-{$i}.png"))->all();

    $this->post(route('applications.store'), ['job_id' => $job->id, 'offer' => 500, 'proposal' => 'x', 'terms' => '1', 'action' => 'submitted', 'portfolio' => $files])
        ->assertSessionHasErrors('portfolio');

    expect(JobApplication::where('job_id', $job->id)->exists())->toBeFalse();
});

it('keeps the offer and terms requirements for submissions but not for drafts', function () {
    $job = bjJob();

    $this->post(route('applications.store'), ['job_id' => $job->id, 'action' => 'submitted'])->assertSessionHasErrors(['offer', 'terms']);

    $this->post(route('applications.store'), ['job_id' => $job->id, 'action' => 'draft', 'proposal' => 'Work in progress'])->assertRedirect(route('applications.drafts'));
    expect(JobApplication::where('job_id', $job->id)->sole()->status)->toBe(ApplicationStatus::Draft);
});

it('ignores a forged poster_id, which would otherwise make the applicant the poster of someone else’s project', function () {
    $job = bjJob();

    $this->post(route('applications.store'), [
        'job_id' => $job->id,
        'poster_id' => $this->me->id,
        'offer' => 1000,
        'proposal' => 'Make me the poster',
        'terms' => '1',
        'action' => 'submitted',
    ])->assertRedirect(route('applications.my'));

    expect(JobApplication::where('job_id', $job->id)->sole()->poster_id)->toBe($this->client->id);
});

/** ------------------------------------------- only projects open for applications */
it('never lists the viewer’s own projects on browse', function () {
    bjJob(['title' => 'Somebody else’s project']);
    bjJob(['title' => 'My own project'], $this->me);

    $this->get(route('jobs.browse'))->assertOk()->assertSee('Somebody else’s project')->assertDontSee('My own project');
});

it('keeps a project open through the end of its deadline day', function () {
    bjJob(['title' => 'Due today', 'no_deadline' => false, 'deadline' => today()]);
    bjJob(['title' => 'Due tomorrow', 'no_deadline' => false, 'deadline' => today()->addDay()]);
    bjJob(['title' => 'Due yesterday', 'no_deadline' => false, 'deadline' => today()->subDay()]);

    $this->get(route('jobs.browse'))->assertOk()->assertSee('Due today')->assertSee('Due tomorrow')->assertDontSee('Due yesterday');
});

it('stops listing a project once the freelancer accepts the offer and it is closed', function () {
    $job = bjJob(['title' => 'Filled project']);
    expect($job->isOpenForApplications())->toBeTrue();

    $this->get(route('jobs.browse'))->assertOk()->assertSee('Filled project');

    $job->update(['is_active' => false]);

    $this->get(route('jobs.browse'))->assertOk()->assertDontSee('Filled project');
});

it('only serves the apply page for projects that are open', function (array $attributes) {
    $job = bjJob($attributes);

    $this->get(route('jobs.apply', $job->slug))
        ->assertRedirect(route('jobs.browse'))
        ->assertSessionHas('alert');
})->with([
    'switched off' => [['is_active' => false]],
    'archived' => [['is_archived' => true]],
    'deadline passed' => [['no_deadline' => false, 'deadline' => '2020-01-01']],
]);

it('serves the apply page on the deadline day itself', function () {
    $job = bjJob(['no_deadline' => false, 'deadline' => today()]);

    $this->get(route('jobs.apply', $job->slug))->assertOk()->assertSee('Send your proposal');
});

it('refuses applications to projects that are no longer open', function (array $attributes) {
    $job = bjJob($attributes);

    $this->post(route('applications.store'), ['job_id' => $job->id, 'offer' => 1000, 'proposal' => 'x', 'terms' => '1', 'action' => 'submitted'])
        ->assertRedirect();

    expect(JobApplication::where('job_id', $job->id)->exists())->toBeFalse();
})->with([
    'switched off' => [['is_active' => false]],
    'archived' => [['is_archived' => true]],
    'deadline passed' => [['no_deadline' => false, 'deadline' => '2020-01-01']],
]);

it('accepts applications on the deadline day', function () {
    $job = bjJob(['no_deadline' => false, 'deadline' => today()]);

    $this->post(route('applications.store'), ['job_id' => $job->id, 'offer' => 1000, 'proposal' => 'x', 'terms' => '1', 'action' => 'submitted'])
        ->assertRedirect(route('applications.my'));

    expect(JobApplication::where('job_id', $job->id)->exists())->toBeTrue();
});

it('deactivates expired projects only after their deadline day has ended', function () {
    $today = bjJob(['no_deadline' => false, 'deadline' => today()]);
    $yesterday = bjJob(['no_deadline' => false, 'deadline' => today()->subDay()]);
    $open = bjJob();

    $this->artisan('jobs:deactivate-expired')->assertSuccessful();

    expect($today->fresh()->is_active)->toBeTrue()
        ->and($yesterday->fresh()->is_active)->toBeFalse()
        ->and($open->fresh()->is_active)->toBeTrue();
});

it('drops closed and own projects from the cached similar projects', function () {
    $job = bjJob(['skills' => ['Rigging']]);
    bjJob(['title' => 'Open match', 'skills' => ['Rigging']]);
    $willClose = bjJob(['title' => 'Closes later', 'skills' => ['Rigging']]);
    bjJob(['title' => 'Mine match', 'skills' => ['Rigging']], $this->me);

    // Warm the 24h cache while everything is still open…
    $this->get(route('jobs.apply', $job->slug))->assertOk()->assertSee('Open match')->assertSee('Closes later');

    // …then a suggestion gets filled. The cached list must not keep offering it.
    $willClose->update(['is_active' => false]);

    $this->get(route('jobs.apply', $job->slug))->assertOk()->assertSee('Open match')->assertDontSee('Closes later')->assertDontSee('Mine match');
});

/** ---------------------------------------------------------------- card details */
it('tints software pills differently from skill pills', function () {
    bjJob(['skills' => ['Rigging'], 'software' => ['Blender']]);

    $response = $this->get(route('jobs.browse'))->assertOk();

    expect($response->getContent())
        ->toMatch('/border-neutral-200 bg-neutral-50 px-2\.5 py-0\.5 text-xs text-neutral-700">Rigging</')
        ->toMatch('/border-teal-100 bg-teal-50 px-2\.5 py-0\.5 text-xs text-teal-800">Blender</');
});

it('keeps paragraphs and list items apart in the plain-text excerpt', function () {
    bjJob(['description' => "Intro paragraph.\n\n- first item\n- second item\n\nClosing line."]);

    $this->get(route('jobs.browse'))->assertOk()->assertSee('Intro paragraph. first item second item Closing line.');
});

it('shows each similar project’s cover image, with a placeholder when it has none', function () {
    $job = bjJob(['skills' => ['Rigging']]);
    bjJob(['title' => 'With cover', 'skills' => ['Rigging'], 'images' => 'job_images/cover-photo.jpg']);
    bjJob(['title' => 'Without cover', 'skills' => ['Rigging']]);

    $response = $this->get(route('jobs.apply', $job->slug))->assertOk()->assertSee('storage/job_images/cover-photo.jpg', false);

    expect($response->getContent())->toContain('aspect-[16/9]')->and(substr_count($response->getContent(), 'aspect-[16/9]'))->toBe(2);
});
