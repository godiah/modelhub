<?php

use App\Enums\ApplicationStatus;
use App\Enums\EngagementStatus;
use App\Models\JobApplication;
use App\Models\JobEngagement;
use App\Models\JobImage;
use App\Models\ModelJob;
use App\Models\Skill;
use App\Models\Software;
use App\Models\User;
use App\Notifications\JobPostedNotification;
use App\Services\Jobs\JobManagementService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/*
 * The poster side of jobs: posting a project, its overview page, editing it, and the "Posted projects" list.
 * Lazy loading is blocked outside production, so each request also proves the page eager-loads what it reads;
 * every test asserts OK (or the expected redirect) before anything else.
 */

beforeEach(function () {
    Storage::fake('public');
    Notification::fake();

    $this->me = User::factory()->create(['name' => 'Amina Otieno']);
    $this->other = User::factory()->create(['name' => 'Kevin Mwangi']);
    $this->actingAs($this->me);

    Skill::forceCreate(['name' => 'Rigging', 'is_active' => true]);
    Skill::forceCreate(['name' => 'Sculpting', 'is_active' => true]);
    Software::forceCreate(['name' => 'Blender', 'is_active' => true]);
    Software::forceCreate(['name' => 'Maya', 'is_active' => true]);
});

function pjJob(array $attributes = [], ?User $owner = null): ModelJob
{
    return ModelJob::factory()->create(array_merge([
        'user_id' => ($owner ?? test()->me)->id,
        'skills' => ['Rigging'],
        'software' => ['Blender'],
    ], $attributes));
}

function pjPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Fox mascot rig',
        'description' => "A **fully rigged** mascot.\n\n- Sculpt from turnarounds",
        'skills' => ['Rigging', 'Sculpting'],
        'software' => ['Blender'],
        'budget' => 95000,
        'no_deadline' => '0',
        'deadline' => now()->addDays(10)->toDateString(),
        'image' => UploadedFile::fake()->image('cover.png', 800, 600),
    ], $overrides);
}

function pjEngagement(ModelJob $job, EngagementStatus $status): JobEngagement
{
    $application = JobApplication::factory()->hired()->create(['job_id' => $job->id, 'poster_id' => $job->user_id, 'applicant_id' => User::factory()]);

    return JobEngagement::create([
        'application_id' => $application->id, 'status' => $status,
        'agreed_amount' => 1100, 'service_fee' => 100, 'net_amount' => 1000,
    ]);
}

/** ---------------------------------------------------------------- post a project */
it('shows the post-a-project form with the shared fields', function () {
    $this->get(route('jobs.create'))
        ->assertOk()
        ->assertSee('Post a project')
        ->assertSee('name="title"', false)
        ->assertSee('name="description"', false)
        ->assertSee('name="skills[]"', false)
        ->assertSee('name="software[]"', false)
        ->assertSee('name="image"', false)
        ->assertSee('name="additional_images[]"', false)
        ->assertSee('name="budget"', false)
        ->assertSee('name="deadline"', false)
        ->assertSee('Post project')
        ->assertDontSee('Accepting applications'); // only when editing
});

it('keeps the post form away from guests', function () {
    auth()->logout();

    $this->get(route('jobs.create'))->assertRedirect(route('login'));
    $this->post(route('jobs.store'), pjPayload())->assertRedirect(route('login'));
});

it('posts a project with a cover and extra images', function () {
    $this->post(route('jobs.store'), pjPayload(['additional_images' => [UploadedFile::fake()->image('plan.jpg'), UploadedFile::fake()->image('mood.jpg')]]))
        ->assertRedirect();

    $job = ModelJob::where('title', 'Fox mascot rig')->sole();

    expect($job->user_id)->toBe($this->me->id)
        ->and($job->skills)->toBe(['Rigging', 'Sculpting'])
        ->and($job->software)->toBe(['Blender'])
        ->and((float) $job->budget)->toBe(95000.0)
        ->and($job->no_deadline)->toBeFalse()
        ->and($job->isOpenForApplications())->toBeTrue()
        ->and($job->jobImages)->toHaveCount(2);

    Storage::disk('public')->assertExists($job->images);
    Notification::assertSentTo($this->me, JobPostedNotification::class);
});

it('accepts a deadline of today and "no fixed deadline"', function () {
    $this->post(route('jobs.store'), pjPayload(['title' => 'Due today', 'deadline' => today()->toDateString()]))->assertRedirect();
    $this->post(route('jobs.store'), pjPayload(['title' => 'Open ended', 'no_deadline' => '1', 'deadline' => null, 'image' => UploadedFile::fake()->image('c.png')]))->assertRedirect();

    expect(ModelJob::where('title', 'Due today')->sole()->isOpenForApplications())->toBeTrue();

    $open = ModelJob::where('title', 'Open ended')->sole();
    expect($open->no_deadline)->toBeTrue()->and($open->deadline)->toBeNull();
});

it('validates the brief', function (array $overrides, string|array $errors) {
    $this->post(route('jobs.store'), pjPayload($overrides))->assertSessionHasErrors($errors);

    expect(ModelJob::count())->toBe(0);
})->with([
    'no title' => [['title' => ''], 'title'],
    'no description' => [['description' => ''], 'description'],
    'no skills' => [['skills' => []], 'skills'],
    'no software' => [['software' => []], 'software'],
    'unknown skill' => [['skills' => ['Telekinesis']], 'skills.0'],
    'unknown software' => [['software' => ['Notepad']], 'software.0'],
    'no budget' => [['budget' => ''], 'budget'],
    'zero budget' => [['budget' => 0], 'budget'],
    'no cover image' => [['image' => null], 'image'],
    'cover that is not an image' => [['image' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf')], 'image'],
    'deadline in the past' => [['deadline' => now()->subDay()->toDateString()], 'deadline'],
    'no deadline and not ticked' => [['deadline' => null], 'deadline'],
    'more than five extras' => [['additional_images' => array_map(fn ($i) => UploadedFile::fake()->image("e{$i}.png"), range(1, 6))], 'additional_images'],
]);

it('rejects a title that is already in use', function () {
    pjJob(['title' => 'Fox mascot rig'], $this->other);

    $this->post(route('jobs.store'), pjPayload())->assertSessionHasErrors('title');
});

it('only lets signed-in users look up titles, and answers correctly', function () {
    pjJob(['title' => 'Taken title']);

    $this->getJson(route('jobs.check-title', ['title' => 'Taken title']))->assertOk()->assertJson(['exists' => true]);
    $this->getJson(route('jobs.check-title', ['title' => 'Free title']))->assertOk()->assertJson(['exists' => false]);

    auth()->logout();
    $this->getJson(route('jobs.check-title', ['title' => 'Taken title']))->assertUnauthorized();
});

it('previews Markdown with raw HTML stripped', function () {
    $response = $this->postJson(route('jobs.preview'), ['description' => '**Bold** <script>window.pwned = 1</script> [bad](javascript:alert(1))'])->assertOk();

    expect($response->json('html'))->toContain('<strong>Bold</strong>')->not->toContain('<script')->not->toContain('javascript:');

    auth()->logout();
    $this->postJson(route('jobs.preview'), ['description' => 'x'])->assertUnauthorized();
});

/** ------------------------------------------------------------ project overview */
it('shows the poster their project with its applications', function () {
    $job = pjJob(['title' => 'Lobby walkthrough', 'budget' => 4800]);
    $applicant = User::factory()->create(['name' => 'Zawadi Mwangi']);
    JobApplication::factory()->create(['job_id' => $job->id, 'poster_id' => $this->me->id, 'applicant_id' => $applicant->id, 'status' => ApplicationStatus::Submitted, 'offer_amount' => 4200]);
    JobApplication::factory()->draft()->create(['job_id' => $job->id, 'poster_id' => $this->me->id, 'applicant_id' => User::factory()->create(['name' => 'Drafts Only'])->id]);

    $this->get(route('jobs.show', $job->slug))
        ->assertOk()
        ->assertSee('Your project')
        ->assertSee('Lobby walkthrough')
        ->assertSee('Open')
        ->assertSee('Zawadi Mwangi')
        ->assertSee('1 new')
        ->assertDontSee('Drafts Only')
        ->assertSee(route('jobs.edit', $job->slug), false)
        ->assertSee(route('jobs.apply', $job->slug), false) // the public link that "Copy link" copies
        ->assertSee(route('my-jobs.applications.index', $job->slug), false);
});

it('renders the description as sanitised Markdown on the overview', function () {
    $job = pjJob(['description' => 'Hello **there** <script>window.pwned = 1</script>']);

    $response = $this->get(route('jobs.show', $job->slug))->assertOk()->assertSee('<strong>there</strong>', false);

    expect($response->getContent())->not->toContain('<script>window.pwned');
});

it('sends everyone except the poster to the public page', function () {
    $job = pjJob([], $this->other);

    $this->get(route('jobs.show', $job->slug))->assertRedirect(route('jobs.apply', $job->slug));

    auth()->logout();
    $this->get(route('jobs.show', $job->slug))->assertRedirect(route('jobs.apply', $job->slug));
});

it('says when a project is not taking applications, and links the hire', function () {
    $closed = pjJob(['is_active' => false]);
    $filled = pjJob(['is_active' => false]);
    $engagement = pjEngagement($filled, EngagementStatus::Active);

    $this->get(route('jobs.show', $closed->slug))->assertOk()->assertSee('Closed')->assertSee('not visible on Browse projects');
    $this->get(route('jobs.show', $filled->slug))->assertOk()->assertSee('In progress')->assertSee(route('engagements.show', $engagement), false)->assertDontSee('not visible on Browse projects');
});

it('offers to archive only closed projects with no work in progress', function () {
    $open = pjJob();
    $closed = pjJob(['is_active' => false]);
    $filled = pjJob(['is_active' => false]);
    pjEngagement($filled, EngagementStatus::Active);

    $this->get(route('jobs.show', $open->slug))->assertOk()->assertDontSee('Archive project');
    $this->get(route('jobs.show', $closed->slug))->assertOk()->assertSee('Archive project');
    $this->get(route('jobs.show', $filled->slug))->assertOk()->assertDontSee('Archive project');
});

/** ---------------------------------------------------------------------- edit */
it('locks the edit page and the update to the poster', function () {
    $theirs = pjJob(['title' => 'Not mine'], $this->other);

    $this->get(route('jobs.edit', $theirs->slug))->assertForbidden();
    $this->patch(route('jobs.update', $theirs->slug), pjPayload(['title' => 'Hijacked', 'image' => null]))->assertForbidden();

    expect($theirs->fresh()->title)->toBe('Not mine');

    auth()->logout();
    $this->get(route('jobs.edit', $theirs->slug))->assertRedirect(route('login'));
});

it('shows the edit form filled in, with the application switch', function () {
    $job = pjJob(['title' => 'Editable project', 'skills' => ['Rigging'], 'software' => ['Maya'], 'budget' => 12000, 'images' => 'job_images/cover.jpg']);
    $image = JobImage::create(['model_job_id' => $job->id, 'image_path' => 'job_additional_images/extra.jpg']);

    $this->get(route('jobs.edit', $job->slug))
        ->assertOk()
        ->assertSee('Edit project')
        ->assertSee('Editable project')
        ->assertSee('value="12000"', false)
        ->assertSee('Accepting applications')
        ->assertSee('storage/job_images/cover.jpg', false)
        ->assertSee('storage/job_additional_images/extra.jpg', false)
        ->assertSee('Save changes');
});

it('lets the poster change the whole brief without changing the slug', function () {
    $job = pjJob(['title' => 'Before']);
    $slug = $job->slug;

    $this->patch(route('jobs.update', $job->slug), [
        'title' => 'After',
        'description' => 'A rewritten brief',
        'skills' => ['Sculpting'],
        'software' => ['Maya', 'Blender'],
        'budget' => 77000,
        'no_deadline' => '1',
        'is_active' => '1',
    ])->assertRedirect(route('jobs.show', $slug));

    $fresh = $job->fresh();

    expect($fresh->title)->toBe('After')
        ->and($fresh->slug)->toBe($slug)
        ->and($fresh->description)->toBe('A rewritten brief')
        ->and($fresh->skills)->toBe(['Sculpting'])
        ->and($fresh->software)->toBe(['Maya', 'Blender'])
        ->and((float) $fresh->budget)->toBe(77000.0)
        ->and($fresh->no_deadline)->toBeTrue()
        ->and($fresh->deadline)->toBeNull();
});

it('keeps the project’s own title valid but not another project’s', function () {
    $job = pjJob(['title' => 'Mine']);
    pjJob(['title' => 'Taken by someone'], $this->other);

    $payload = fn (string $title) => ['title' => $title, 'description' => 'x', 'skills' => ['Rigging'], 'software' => ['Blender'], 'budget' => 100, 'no_deadline' => '1', 'is_active' => '1'];

    $this->patch(route('jobs.update', $job->slug), $payload('Mine'))->assertRedirect();
    $this->patch(route('jobs.update', $job->slug), $payload('Taken by someone'))->assertSessionHasErrors('title');
});

it('replaces the cover, removes chosen extras and adds new ones, cleaning up the files', function () {
    Storage::disk('public')->put('job_images/old-cover.jpg', 'old');
    Storage::disk('public')->put('job_additional_images/drop.jpg', 'drop');
    Storage::disk('public')->put('job_additional_images/keep.jpg', 'keep');
    $job = pjJob(['images' => 'job_images/old-cover.jpg']);
    $drop = JobImage::create(['model_job_id' => $job->id, 'image_path' => 'job_additional_images/drop.jpg']);
    $keep = JobImage::create(['model_job_id' => $job->id, 'image_path' => 'job_additional_images/keep.jpg']);

    $this->patch(route('jobs.update', $job->slug), [
        'title' => $job->title, 'description' => 'x', 'skills' => ['Rigging'], 'software' => ['Blender'], 'budget' => 100, 'no_deadline' => '1', 'is_active' => '1',
        'image' => UploadedFile::fake()->image('new-cover.png'),
        'remove_images' => [$drop->id],
        'additional_images' => [UploadedFile::fake()->image('added.png')],
    ])->assertRedirect();

    $job->refresh();

    expect($job->images)->not->toBe('job_images/old-cover.jpg')
        ->and($job->jobImages)->toHaveCount(2)
        ->and(JobImage::find($drop->id))->toBeNull()
        ->and(JobImage::find($keep->id))->not->toBeNull();

    Storage::disk('public')->assertMissing('job_images/old-cover.jpg');
    Storage::disk('public')->assertMissing('job_additional_images/drop.jpg');
    Storage::disk('public')->assertExists('job_additional_images/keep.jpg');
    Storage::disk('public')->assertExists($job->images);
});

it('never removes another project’s images', function () {
    Storage::disk('public')->put('job_additional_images/theirs.jpg', 'theirs');
    $theirJob = pjJob([], $this->other);
    $theirs = JobImage::create(['model_job_id' => $theirJob->id, 'image_path' => 'job_additional_images/theirs.jpg']);
    $mine = pjJob();

    $this->patch(route('jobs.update', $mine->slug), [
        'title' => $mine->title, 'description' => 'x', 'skills' => ['Rigging'], 'software' => ['Blender'], 'budget' => 100, 'no_deadline' => '1', 'is_active' => '1',
        'remove_images' => [$theirs->id],
    ])->assertRedirect();

    expect(JobImage::find($theirs->id))->not->toBeNull();
    Storage::disk('public')->assertExists('job_additional_images/theirs.jpg');
});

it('caps a project at five extra images', function () {
    $job = pjJob();
    foreach (range(1, 4) as $i) {
        JobImage::create(['model_job_id' => $job->id, 'image_path' => "job_additional_images/e{$i}.jpg"]);
    }

    $base = ['title' => $job->title, 'description' => 'x', 'skills' => ['Rigging'], 'software' => ['Blender'], 'budget' => 100, 'no_deadline' => '1', 'is_active' => '1'];

    $this->patch(route('jobs.update', $job->slug), $base + ['additional_images' => [UploadedFile::fake()->image('a.png'), UploadedFile::fake()->image('b.png')]])
        ->assertSessionHasErrors('additional_images');

    // Removing one makes room for two.
    $this->patch(route('jobs.update', $job->slug), $base + ['remove_images' => [$job->jobImages()->first()->id], 'additional_images' => [UploadedFile::fake()->image('a.png'), UploadedFile::fake()->image('b.png')]])
        ->assertSessionDoesntHaveErrors();

    expect($job->jobImages()->count())->toBe(5);
});

it('closes and reopens a project from the edit form', function () {
    $job = pjJob();
    $base = ['title' => $job->title, 'description' => 'x', 'skills' => ['Rigging'], 'software' => ['Blender'], 'budget' => 100, 'no_deadline' => '1'];

    $this->patch(route('jobs.update', $job->slug), $base + ['is_active' => '0'])->assertRedirect();
    expect($job->fresh()->isOpenForApplications())->toBeFalse();

    $this->patch(route('jobs.update', $job->slug), $base + ['is_active' => '1'])->assertRedirect();
    expect($job->fresh()->isOpenForApplications())->toBeTrue();
});

it('will not reopen a project a freelancer is already working on, or an archived one', function () {
    $filled = pjJob(['is_active' => false]);
    pjEngagement($filled, EngagementStatus::Active);
    $archived = pjJob(['is_active' => false, 'is_archived' => true]);
    $base = ['description' => 'x', 'skills' => ['Rigging'], 'software' => ['Blender'], 'budget' => 100, 'no_deadline' => '1', 'is_active' => '1'];

    $this->patch(route('jobs.update', $filled->slug), $base + ['title' => $filled->title])->assertSessionHasErrors('is_active');
    $this->patch(route('jobs.update', $archived->slug), $base + ['title' => $archived->title])->assertSessionHasErrors('is_active');

    expect($filled->fresh()->is_active)->toBeFalse();

    $this->get(route('jobs.edit', $filled->slug))->assertOk()->assertSee('A freelancer is already working on this project');
});

it('reopens a project whose engagement was cancelled', function () {
    $job = pjJob(['is_active' => false]);
    pjEngagement($job, EngagementStatus::Cancelled);

    $this->patch(route('jobs.update', $job->slug), ['title' => $job->title, 'description' => 'x', 'skills' => ['Rigging'], 'software' => ['Blender'], 'budget' => 100, 'no_deadline' => '1', 'is_active' => '1'])
        ->assertRedirect();

    expect($job->fresh()->is_active)->toBeTrue();
});

it('keeps a past deadline on a closed project but will not accept a new past date or reopen it on the old one', function () {
    $job = pjJob(['is_active' => false, 'no_deadline' => false, 'deadline' => now()->subWeek()->toDateString()]);
    $base = ['title' => $job->title, 'description' => 'x', 'skills' => ['Rigging'], 'software' => ['Blender'], 'budget' => 100, 'no_deadline' => '0'];
    $old = now()->subWeek()->toDateString();

    $this->patch(route('jobs.update', $job->slug), $base + ['deadline' => $old, 'is_active' => '0'])->assertSessionDoesntHaveErrors();
    $this->patch(route('jobs.update', $job->slug), $base + ['deadline' => now()->subDays(2)->toDateString(), 'is_active' => '0'])->assertSessionHasErrors('deadline');
    $this->patch(route('jobs.update', $job->slug), $base + ['deadline' => $old, 'is_active' => '1'])->assertSessionHasErrors('deadline');
    $this->patch(route('jobs.update', $job->slug), $base + ['deadline' => now()->addWeek()->toDateString(), 'is_active' => '1'])->assertSessionDoesntHaveErrors();

    expect($job->fresh()->isOpenForApplications())->toBeTrue();
});

/** -------------------------------------------------------------- posted projects */
it('lists only the viewer’s projects with status tabs and counts', function () {
    pjJob(['title' => 'Open one']);
    pjJob(['title' => 'Closed one', 'is_active' => false]);
    pjJob(['title' => 'Expired one', 'no_deadline' => false, 'deadline' => now()->subDay()->toDateString()]);
    pjJob(['title' => 'Archived one', 'is_active' => false, 'is_archived' => true]);
    pjJob(['title' => 'Somebody else’s'], $this->other);

    $this->get(route('my-jobs.index'))
        ->assertOk()
        ->assertSee('Posted projects')
        ->assertSee('Open one')
        ->assertSee('Closed one')
        ->assertSee('Expired one')
        ->assertDontSee('Archived one')
        ->assertDontSee('Somebody else’s')
        ->assertSeeInOrder(['All', '3', 'Open', '1', 'Closed', '2']);

    $this->get(route('my-jobs.index', ['status' => 'active']))->assertOk()->assertSee('Open one')->assertDontSee('Closed one')->assertDontSee('Expired one');
    $this->get(route('my-jobs.index', ['status' => 'closed']))->assertOk()->assertDontSee('Open one')->assertSee('Closed one')->assertSee('Expired one');
});

it('labels each project with its state', function () {
    pjJob(['title' => 'Plain open']);
    pjJob(['title' => 'Plain closed', 'is_active' => false]);
    $working = pjJob(['title' => 'Being worked on', 'is_active' => false]);
    pjEngagement($working, EngagementStatus::Active);
    $offer = pjJob(['title' => 'Offer pending']);
    pjEngagement($offer, EngagementStatus::EmployerAccepted);

    $response = $this->get(route('my-jobs.index'))->assertOk();

    expect($response->getContent())->toContain('In progress')->toContain('Offer sent');
});

it('reports new applications per project and offers archive only on closed ones without work in progress', function () {
    $open = pjJob(['title' => 'Has applicants']);
    JobApplication::factory()->count(2)->create(['job_id' => $open->id, 'poster_id' => $this->me->id, 'status' => ApplicationStatus::Submitted]);
    $closed = pjJob(['title' => 'Nobody home', 'is_active' => false]);

    $this->get(route('my-jobs.index'))
        ->assertOk()
        ->assertSee('2 new')
        ->assertSee(route('my-jobs.archive', $closed), false)
        ->assertDontSee(route('my-jobs.archive', $open), false);
});

it('sorts the list', function () {
    pjJob(['title' => 'Cheapest', 'budget' => 100]);
    pjJob(['title' => 'Priciest', 'budget' => 9000]);

    $this->get(route('my-jobs.index', ['sort' => 'budget_high']))->assertOk()->assertSeeInOrder(['Priciest', 'Cheapest']);
    $this->get(route('my-jobs.index', ['sort' => 'budget_low']))->assertOk()->assertSeeInOrder(['Cheapest', 'Priciest']);
    $this->get(route('my-jobs.index', ['status' => 'sideways']))->assertSessionHasErrors('status');
});

it('shows a first-project prompt when nothing has been posted', function () {
    $this->get(route('my-jobs.index'))->assertOk()->assertSee('You have not posted any projects yet')->assertSee(route('jobs.create'), false);
});

it('works out a project’s state for the poster', function () {
    $open = pjJob()->load('engagements');
    $closed = pjJob(['is_active' => false])->load('engagements');
    $expired = pjJob(['no_deadline' => false, 'deadline' => now()->subDay()->toDateString()])->load('engagements');
    $working = tap(pjJob(['is_active' => false]), fn ($job) => pjEngagement($job, EngagementStatus::Active))->load('engagements');
    $done = tap(pjJob(['is_active' => false]), fn ($job) => pjEngagement($job, EngagementStatus::Completed))->load('engagements');

    expect($open->listingStatus()[0])->toBe('Open')
        ->and($closed->listingStatus()[0])->toBe('Closed')
        ->and($expired->listingStatus()[0])->toBe('Expired')
        ->and($working->listingStatus()[0])->toBe('In progress')
        ->and($done->listingStatus()[0])->toBe('Completed');
});

it('flags only deadlines within the next few days as closing soon', function () {
    $job = fn (array $a) => new ModelJob($a + ['no_deadline' => false]);

    expect($job(['deadline' => today()])->deadlineIsSoon())->toBeTrue()
        ->and($job(['deadline' => today()->addDays(3)])->deadlineIsSoon())->toBeTrue()
        ->and($job(['deadline' => today()->addDays(4)])->deadlineIsSoon())->toBeFalse()
        ->and($job(['deadline' => today()->addWeeks(2)])->deadlineIsSoon())->toBeFalse()
        ->and($job(['deadline' => today()->subDay()])->deadlineIsSoon())->toBeFalse()
        ->and($job(['deadline' => null])->deadlineIsSoon())->toBeFalse()
        ->and((new ModelJob(['no_deadline' => true, 'deadline' => today()]))->deadlineIsSoon())->toBeFalse();
});

it('shows a far-off deadline without the amber warning', function () {
    $far = pjJob(['no_deadline' => false, 'deadline' => today()->addDays(20)->toDateString()]);
    $near = pjJob(['no_deadline' => false, 'deadline' => today()->addDays(2)->toDateString()]);

    $this->get(route('jobs.show', $far->slug))->assertOk()->assertDontSee('text-amber-700', false);
    $this->get(route('jobs.show', $near->slug))->assertOk()->assertSee('text-amber-700', false);
});

it('searches the poster’s projects by title and keeps the search across tabs and sorting', function () {
    pjJob(['title' => 'Harbour crane rig']);
    pjJob(['title' => 'Villa render']);

    $this->get(route('my-jobs.index', ['search' => 'crane']))
        ->assertOk()
        ->assertSee('Harbour crane rig')
        ->assertDontSee('Villa render')
        ->assertSee('search=crane', false)    // tab links carry the search
        ->assertSee('Clear filters');

    $this->get(route('my-jobs.index', ['search' => 'no such project']))->assertOk()->assertSee('No projects match');
});

it('summarises the poster’s projects in headline numbers', function () {
    $open = pjJob();
    pjJob(['is_active' => false]);
    JobApplication::factory()->count(3)->create(['job_id' => $open->id, 'poster_id' => $this->me->id, 'status' => ApplicationStatus::Submitted]);
    JobApplication::factory()->create(['job_id' => $open->id, 'poster_id' => $this->me->id, 'status' => ApplicationStatus::Reviewed]);
    pjEngagement(pjJob(['is_active' => false]), EngagementStatus::Active);
    pjEngagement(pjJob(['is_active' => false]), EngagementStatus::Completed);
    pjEngagement(pjJob(['is_active' => false]), EngagementStatus::Completed);

    $stats = app(JobManagementService::class)->getPostedJobStats();

    expect($stats)->toBe(['open' => 1, 'new_applications' => 3, 'in_progress' => 1, 'completed' => 2]);

    $this->get(route('my-jobs.index'))->assertOk()->assertSee('Open projects')->assertSee('New applications')->assertSee('In progress')->assertSee('Completed');
});

it('gives every listed project a detail panel with its applicants and pipeline', function () {
    $job = pjJob(['title' => 'Panel project']);
    $zawadi = User::factory()->create(['name' => 'Zawadi Mwangi']);
    JobApplication::factory()->create(['job_id' => $job->id, 'poster_id' => $this->me->id, 'applicant_id' => $zawadi->id, 'status' => ApplicationStatus::Submitted, 'offer_amount' => 4200]);
    JobApplication::factory()->create(['job_id' => $job->id, 'poster_id' => $this->me->id, 'status' => ApplicationStatus::Reviewed]);
    JobApplication::factory()->draft()->create(['job_id' => $job->id, 'poster_id' => $this->me->id, 'applicant_id' => User::factory()->create(['name' => 'Drafts Only'])->id]);
    $quiet = pjJob(['title' => 'Quiet project']);
    $hired = pjJob(['title' => 'Hired project', 'is_active' => false]);
    $engagement = pjEngagement($hired, EngagementStatus::Active);

    $this->get(route('my-jobs.index'))
        ->assertOk()
        ->assertSee('Zawadi Mwangi')
        ->assertDontSee('Drafts Only')
        ->assertSee('Submitted')
        ->assertSee('Reviewed')
        ->assertSee('2 applications')
        ->assertSee('No applications yet. Copy the link')
        ->assertSee('Open the engagement')
        ->assertSee(route('engagements.show', $engagement), false)
        ->assertSee(route('my-jobs.applications.index', $job->slug), false)
        ->assertSee(route('jobs.edit', $quiet->slug), false);
});

/** ------------------------------------------------------------------ archive */
it('archives only closed projects with nobody working on them', function () {
    $open = pjJob();
    $closed = pjJob(['is_active' => false]);
    $working = pjJob(['is_active' => false]);
    pjEngagement($working, EngagementStatus::Active);
    $done = pjJob(['is_active' => false]);
    pjEngagement($done, EngagementStatus::Completed);
    $cancelled = pjJob(['is_active' => false]);
    pjEngagement($cancelled, EngagementStatus::Cancelled);

    $this->patch(route('my-jobs.archive', $open))->assertRedirect()->assertSessionHas('alert');
    $this->patch(route('my-jobs.archive', $working))->assertRedirect()->assertSessionHas('alert');
    expect($open->fresh()->is_archived)->toBeFalse()->and($open->fresh()->is_active)->toBeTrue()
        ->and($working->fresh()->is_archived)->toBeFalse();

    foreach ([$closed, $done, $cancelled] as $job) {
        $this->patch(route('my-jobs.archive', $job))->assertRedirect();
        expect($job->fresh()->is_archived)->toBeTrue();
    }
});

it('does not let anyone else archive or restore a project', function () {
    $theirs = pjJob(['is_active' => false], $this->other);
    $theirsArchived = pjJob(['is_active' => false, 'is_archived' => true], $this->other);

    $this->patch(route('my-jobs.archive', $theirs))->assertRedirect()->assertSessionHas('alert');
    $this->patch(route('my-jobs.archived.restore', $theirsArchived))->assertRedirect()->assertSessionHas('alert');

    expect($theirs->fresh()->is_archived)->toBeFalse()->and($theirsArchived->fresh()->is_archived)->toBeTrue();
});

it('restores a project and reopens it when that is safe', function () {
    $job = pjJob(['is_active' => false, 'is_archived' => true]);

    $this->patch(route('my-jobs.archived.restore', $job))->assertRedirect();

    $job->refresh();
    expect($job->is_archived)->toBeFalse()->and($job->is_active)->toBeTrue();
});

it('restores a project closed when someone is working on it or its deadline has passed', function () {
    $working = pjJob(['is_active' => false, 'is_archived' => true]);
    pjEngagement($working, EngagementStatus::Active);
    $expired = pjJob(['is_active' => false, 'is_archived' => true, 'no_deadline' => false, 'deadline' => today()->subDay()->toDateString()]);

    $this->patch(route('my-jobs.archived.restore', $working))->assertRedirect();
    $this->patch(route('my-jobs.archived.restore', $expired))->assertRedirect();

    expect($working->fresh()->is_archived)->toBeFalse()->and($working->fresh()->is_active)->toBeFalse()
        ->and($expired->fresh()->is_archived)->toBeFalse()->and($expired->fresh()->is_active)->toBeFalse();
});

it('refuses to restore a project that is not archived', function () {
    $job = pjJob();

    $this->patch(route('my-jobs.archived.restore', $job))->assertRedirect()->assertSessionHas('alert');
});

it('knows which projects can be archived', function () {
    $open = pjJob()->load('engagements');
    $closed = pjJob(['is_active' => false])->load('engagements');
    $archived = pjJob(['is_active' => false, 'is_archived' => true])->load('engagements');
    $working = tap(pjJob(['is_active' => false]), fn ($job) => pjEngagement($job, EngagementStatus::Disputed))->load('engagements');

    expect($open->canBeArchived())->toBeFalse()
        ->and($closed->canBeArchived())->toBeTrue()
        ->and($archived->canBeArchived())->toBeFalse()
        ->and($working->canBeArchived())->toBeFalse();
});

/** --------------------------------------------------------- archived pages */
it('lists the poster’s archived projects only, with search and real application counts', function () {
    $mine = pjJob(['title' => 'Archived harbour rig', 'is_active' => false, 'is_archived' => true]);
    pjJob(['title' => 'Archived villa render', 'is_active' => false, 'is_archived' => true]);
    pjJob(['title' => 'Still posted']);
    pjJob(['title' => 'Somebody else’s archive', 'is_active' => false, 'is_archived' => true], $this->other);
    JobApplication::factory()->count(2)->create(['job_id' => $mine->id, 'poster_id' => $this->me->id, 'status' => ApplicationStatus::Submitted]);
    JobApplication::factory()->draft()->create(['job_id' => $mine->id, 'poster_id' => $this->me->id]);

    $this->get(route('my-jobs.archived.posted-jobs'))
        ->assertOk()
        ->assertSee('Archived projects')
        ->assertSee('Archived harbour rig')
        ->assertSee('Archived villa render')
        ->assertDontSee('Still posted')
        ->assertDontSee('Somebody else’s archive')
        ->assertSee('2 applications')
        ->assertSee(route('my-jobs.archived.restore', $mine), false)
        ->assertSee(route('my-jobs.archived.show', $mine), false);

    $this->get(route('my-jobs.archived.posted-jobs', ['search' => 'harbour']))->assertOk()->assertSee('Archived harbour rig')->assertDontSee('Archived villa render');
    $this->get(route('my-jobs.archived.posted-jobs', ['search' => 'nothing like this']))->assertOk()->assertSee('No archived projects match');
});

it('shows an empty archive and paginates a long one', function () {
    $this->get(route('my-jobs.archived.posted-jobs'))->assertOk()->assertSee('Nothing archived');

    foreach (range(1, 11) as $i) {
        pjJob(['title' => "Old project {$i}", 'is_active' => false, 'is_archived' => true]);
    }

    $this->get(route('my-jobs.archived.posted-jobs'))->assertOk()->assertSee('Showing 1–10 of 11')->assertSee('page=2', false);
});

it('shows an archived project with its applications, proposals and a restore action', function () {
    $job = pjJob(['title' => 'Old lobby model', 'is_active' => false, 'is_archived' => true, 'description' => 'Was **great** <script>window.pwned = 1</script>']);
    $applicant = User::factory()->create(['name' => 'Zawadi Mwangi']);
    JobApplication::factory()->create(['job_id' => $job->id, 'poster_id' => $this->me->id, 'applicant_id' => $applicant->id, 'status' => ApplicationStatus::Rejected, 'proposal' => 'I can do this <b>fast</b>', 'offer_amount' => 4200]);
    JobApplication::factory()->draft()->create(['job_id' => $job->id, 'poster_id' => $this->me->id, 'applicant_id' => User::factory()->create(['name' => 'Drafts Only'])->id]);

    $response = $this->get(route('my-jobs.archived.show', $job))
        ->assertOk()
        ->assertSee('Archived project')
        ->assertSee('Old lobby model')
        ->assertSee('Zawadi Mwangi')
        ->assertSee('Rejected')
        ->assertSee('I can do this &lt;b&gt;fast&lt;/b&gt;', false)   // proposals are plain text
        ->assertSee('<strong>great</strong>', false)
        ->assertDontSee('Drafts Only')
        ->assertSee(route('my-jobs.archived.restore', $job), false);

    expect($response->getContent())->not->toContain('<script>window.pwned')->not->toContain('<b>fast</b>');
});

it('keeps other people out of an archived project and sends live projects to their normal page', function () {
    $theirs = pjJob(['is_active' => false, 'is_archived' => true], $this->other);
    $live = pjJob();

    $this->get(route('my-jobs.archived.show', $theirs))->assertRedirect()->assertSessionHas('alert');
    $this->get(route('my-jobs.archived.show', $live))->assertRedirect(route('jobs.show', $live->slug));
});
