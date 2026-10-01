<?php

use App\Enums\EngagementStatus;
use App\Helpers\NotificationPresenterHelper;
use App\Models\JobApplication;
use App\Models\JobDeliverable;
use App\Models\JobEngagement;
use App\Models\Message;
use App\Models\ModelJob;
use App\Models\Product;
use App\Models\ProductFile;
use App\Models\ProductReview;
use App\Models\SellerProfile;
use App\Models\Staff;
use App\Models\StaffActivity;
use App\Models\User;
use App\Notifications\ProjectRestoredNotification;
use App\Notifications\ProjectTakenDownNotification;
use App\Services\Admin\PlatformStatsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

/*
 * The staff directories: projects (with take-down), hires (read-only), every model, every store, and the platform overview,
 * each behind its own view permission, plus the dispute conversation that only some staff may read.
 */

function openProject(array $overrides = []): ModelJob
{
    return ModelJob::factory()->create($overrides + ['is_active' => true, 'is_archived' => false, 'no_deadline' => true]);
}

function hire(string $title = 'Hospital lobby model', EngagementStatus $status = EngagementStatus::Active): JobEngagement
{
    $application = JobApplication::factory()->hired()->create(['job_id' => ModelJob::factory()->create(['title' => $title])->id]);

    return JobEngagement::create(['application_id' => $application->id, 'status' => $status, 'agreed_amount' => 1200, 'service_fee' => 200, 'net_amount' => 1000, 'started_at' => now()->subDays(3)]);
}

/** ---------------------------------------------------------------- projects */
it('lists every project in every state with counts, and filters and searches them', function () {
    $poster = User::factory()->create(['name' => 'Amina Otieno']);
    openProject(['user_id' => $poster->id, 'title' => 'Open atrium brief']);
    openProject(['title' => 'Closed villa brief', 'is_active' => false]);
    openProject(['title' => 'Expired lobby brief', 'no_deadline' => false, 'deadline' => now()->subDays(3)]);
    openProject(['title' => 'Archived hotel brief', 'is_archived' => true, 'is_active' => false]);
    openProject(['title' => 'Removed spam brief', 'taken_down_at' => now(), 'taken_down_reason' => 'Spam.', 'is_active' => false]);

    $this->actingAs(staffWith('Auditor'), 'staff')->get(route('admin.projects.index'))->assertOk()
        ->assertSee('Open atrium brief')->assertSee('Closed villa brief')->assertSee('Expired lobby brief')->assertSee('Archived hotel brief')->assertSee('Removed spam brief')->assertSee('Amina Otieno');

    foreach (['open' => 'Open atrium brief', 'closed' => 'Closed villa brief', 'expired' => 'Expired lobby brief', 'archived' => 'Archived hotel brief', 'taken_down' => 'Removed spam brief'] as $status => $title) {
        $response = $this->get(route('admin.projects.index', ['status' => $status]))->assertOk()->assertSee($title);
        foreach (collect(['Open atrium brief', 'Closed villa brief', 'Expired lobby brief', 'Archived hotel brief', 'Removed spam brief'])->reject($title) as $other) {
            $response->assertDontSee($other);
        }
    }

    $this->get(route('admin.projects.index', ['q' => 'Amina']))->assertSee('Open atrium brief')->assertDontSee('Closed villa brief');
    $this->get(route('admin.projects.index', ['q' => 'villa']))->assertSee('Closed villa brief')->assertDontSee('Open atrium brief');
    $this->get(route('admin.projects.index', ['status' => 'bogus']))->assertOk()->assertSee('Closed villa brief');
});

it('keeps the project pages to staff who may view projects, and the actions to those who may moderate them', function () {
    $project = openProject();

    $this->actingAs(staffWith('Support'), 'staff')->get(route('admin.projects.index'))->assertForbidden();
    $this->actingAs(staffWith('Auditor'), 'staff')->get(route('admin.projects.show', $project))->assertOk()->assertDontSee('Take down');
    $this->actingAs(staffWith('Auditor'), 'staff')->post(route('admin.projects.take-down', $project), ['reason' => 'Spam brief.'])->assertForbidden();
    $this->actingAs(staffWith('Platform manager'), 'staff')->get(route('admin.projects.show', $project))->assertOk()->assertSee('Take down');
});

it('shows a project\'s brief, applicants and hires', function () {
    $project = openProject(['title' => 'Atrium render', 'description' => 'A **bright** atrium with timber details', 'skills' => ['Rendering & Lighting'], 'software' => ['Blender']]);
    $applicant = User::factory()->create(['name' => 'Kevin Mwangi']);
    JobApplication::factory()->create(['job_id' => $project->id, 'applicant_id' => $applicant->id, 'offer_amount' => 5500]);

    $this->actingAs(staffWith('Platform manager'), 'staff')->get(route('admin.projects.show', $project))->assertOk()
        ->assertSee('Atrium render')->assertSee('bright')->assertSee('Rendering &amp; Lighting', false)->assertSee('Blender')
        ->assertSee('Applicants (1)')->assertSee('Kevin Mwangi')->assertSee('5,500')->assertSee(route('admin.members.show', $applicant), false);
});

it('takes a project down with a reason: off the board, the poster told, cannot be reopened by them, and logged', function () {
    Notification::fake();
    $poster = User::factory()->create();
    $project = openProject(['user_id' => $poster->id, 'title' => 'Dodgy brief']);
    $manager = staffWith('Platform manager');
    expect(ModelJob::openForApplications()->whereKey($project->id)->exists())->toBeTrue();

    $this->actingAs($manager, 'staff')->post(route('admin.projects.take-down', $project), ['reason' => 'Asks for work outside the platform.'])->assertSessionHas('success');

    $project->refresh();
    expect($project->isTakenDown())->toBeTrue()->and($project->is_active)->toBeFalse()->and($project->taken_down_by)->toBe($manager->id)->and($project->taken_down_reason)->toBe('Asks for work outside the platform.');
    expect(ModelJob::openForApplications()->whereKey($project->id)->exists())->toBeFalse()->and($project->isOpenForApplications())->toBeFalse()->and($project->listingStatus()[0])->toBe('Taken down');
    Notification::assertSentTo($poster, ProjectTakenDownNotification::class);
    expect(StaffActivity::where('action', 'project.taken-down')->first()->details['reason'])->toBe('Asks for work outside the platform.');

    // The poster switching it back on does not put it back on the board
    $project->update(['is_active' => true]);
    expect(ModelJob::openForApplications()->whereKey($project->id)->exists())->toBeFalse()->and($project->fresh()->isOpenForApplications())->toBeFalse();
    $this->get(route('jobs.browse'))->assertDontSee('Dodgy brief');
});

it('needs a reason to take a project down, and refuses to do it twice', function () {
    $project = openProject();
    $this->actingAs(staffWith('Platform manager'), 'staff');

    $this->post(route('admin.projects.take-down', $project), ['reason' => 'no'])->assertSessionHasErrors('reason');
    $this->post(route('admin.projects.take-down', $project), ['reason' => 'A real reason.']);
    $this->post(route('admin.projects.take-down', $project), ['reason' => 'Again, a real reason.'])->assertSessionHas('error');
    $this->post(route('admin.projects.restore', openProject()))->assertSessionHas('error');
});

it('restores a project: back on the board only if it is still open, and the poster is told', function () {
    Notification::fake();
    $manager = staffWith('Platform manager');
    $this->actingAs($manager, 'staff');

    $open = openProject(['taken_down_at' => now(), 'taken_down_reason' => 'Spam.', 'taken_down_by' => $manager->id, 'is_active' => false]);
    $expired = openProject(['no_deadline' => false, 'deadline' => now()->subDays(2), 'taken_down_at' => now(), 'taken_down_reason' => 'Spam.', 'is_active' => false]);
    $archived = openProject(['is_archived' => true, 'taken_down_at' => now(), 'taken_down_reason' => 'Spam.', 'is_active' => false]);

    foreach ([$open, $expired, $archived] as $project) {
        $this->post(route('admin.projects.restore', $project))->assertSessionHas('success');
    }

    expect($open->fresh()->isTakenDown())->toBeFalse()->and($open->fresh()->is_active)->toBeTrue()->and(ModelJob::openForApplications()->whereKey($open->id)->exists())->toBeTrue();
    expect($expired->fresh()->isTakenDown())->toBeFalse()->and($expired->fresh()->is_active)->toBeFalse();
    expect($archived->fresh()->isTakenDown())->toBeFalse()->and($archived->fresh()->is_active)->toBeFalse();
    Notification::assertSentToTimes($open->user, ProjectRestoredNotification::class, 1);
});

it('shows a poster that their project was taken down, with the reason in their notifications', function () {
    $poster = User::factory()->create();
    $project = openProject(['user_id' => $poster->id, 'title' => 'Dodgy brief']);
    $this->actingAs(staffWith('Platform manager'), 'staff')->post(route('admin.projects.take-down', $project), ['reason' => 'Asks for work outside the platform.']);

    $shown = NotificationPresenterHelper::present($poster->notifications()->first());
    expect($shown['title'])->toBe('Your project was taken down')->and($shown['content'])->toContain('Asks for work outside the platform.');

    Auth::guard('staff')->forgetUser();
    $this->actingAs($poster)->get(route('my-jobs.index'))->assertOk()->assertSee('Taken down');
});

/** ---------------------------------------------------------------- hires */
it('shows hires read-only, with money and deliverables, and never the messages', function () {
    $engagement = hire('Boutique lobby concept');
    JobDeliverable::create(['engagement_id' => $engagement->id, 'title' => 'Massing model', 'description' => 'd', 'status' => 'approved']);
    JobDeliverable::create(['engagement_id' => $engagement->id, 'title' => 'Lighting pass', 'description' => 'd', 'status' => 'submitted']);
    Message::create(['engagement_id' => $engagement->id, 'sender_id' => $engagement->application->poster_id, 'content' => 'Our secret negotiation text']);

    $staff = staffWith('Auditor');
    $this->actingAs($staff, 'staff')->get(route('admin.engagements.index'))->assertOk()->assertSee('Boutique lobby concept')->assertSee($engagement->application->poster->name)->assertSee('1,200');
    $this->get(route('admin.engagements.show', $engagement))->assertOk()
        ->assertSee('Boutique lobby concept')->assertSee('1 of 2 approved')->assertSee('Massing model')->assertSee('Lighting pass')->assertSee('1,000.00')
        ->assertDontSee('Our secret negotiation text');
});

it('filters and searches hires, and keeps them to staff who may view engagements', function () {
    hire('Active villa job', EngagementStatus::Active);
    hire('Finished lobby job', EngagementStatus::Completed);

    $this->actingAs(staffWith('Support'), 'staff')->get(route('admin.engagements.index'))->assertForbidden();
    $this->actingAs(staffWith('Dispute manager'), 'staff')->get(route('admin.engagements.index', ['status' => 'completed']))->assertOk()->assertSee('Finished lobby job')->assertDontSee('Active villa job');
    $this->get(route('admin.engagements.index', ['q' => 'villa']))->assertSee('Active villa job')->assertDontSee('Finished lobby job');
});

it('lets a dispute manager read the conversation in a dispute, logged, and keeps it from support', function () {
    ['engagement' => $engagement, 'cancellation' => $cancellation, 'application' => $application] = makeDisputedEngagement();
    Message::create(['engagement_id' => $engagement->id, 'sender_id' => $application->poster_id, 'content' => 'You never sent the final files']);
    Message::create(['engagement_id' => $engagement->id, 'sender_id' => $application->applicant_id, 'content' => 'I uploaded them on Tuesday']);

    $support = staffWith('Support');
    $this->actingAs($support, 'staff')->get(route('admin.disputes.show', $cancellation))->assertOk()->assertDontSee('You never sent the final files')->assertDontSee('Conversation');

    $manager = staffWith('Dispute manager');
    $this->actingAs($manager, 'staff')->get(route('admin.disputes.show', $cancellation))->assertOk()->assertSee('Conversation')->assertSee('You never sent the final files')->assertSee('I uploaded them on Tuesday');
    $this->get(route('admin.disputes.show', $cancellation));
    expect(StaffActivity::where('action', 'dispute.messages-read')->where('staff_id', $manager->id)->count())->toBe(1)
        ->and(StaffActivity::where('action', 'dispute.messages-read')->where('staff_id', $support->id)->exists())->toBeFalse();
});

/** ---------------------------------------------------------------- models and stores */
it('lists every model in every status, with search, sort and counts', function () {
    $store = SellerProfile::factory()->approved()->create(['display_name' => 'Kevin 3D Studio']);
    Product::factory()->published()->create(['user_id' => $store->user_id, 'title' => 'Live oak chair', 'rating_avg' => 4.5, 'rating_count' => 3, 'price_minor' => 100000]);
    Product::factory()->create(['user_id' => $store->user_id, 'title' => 'Private draft chair']);
    Product::factory()->inReview()->create(['title' => 'Waiting lamp model']);
    Product::factory()->published()->create(['title' => 'Cheap table model', 'price_minor' => 5000]);

    $this->actingAs(staffWith('Auditor'), 'staff')->get(route('admin.catalogue.index'))->assertOk()
        ->assertSee('Live oak chair')->assertSee('Private draft chair')->assertSee('Waiting lamp model')->assertSee('Kevin 3D Studio');
    $this->get(route('admin.catalogue.index', ['status' => 'draft']))->assertSee('Private draft chair')->assertDontSee('Live oak chair');
    $this->get(route('admin.catalogue.index', ['status' => 'in_review']))->assertSee('Waiting lamp model')->assertDontSee('Live oak chair');
    $this->get(route('admin.catalogue.index', ['q' => 'Kevin']))->assertSee('Live oak chair')->assertDontSee('Waiting lamp model');
    $this->get(route('admin.catalogue.index', ['sort' => 'price']))->assertSeeInOrder(['Live oak chair', 'Cheap table model']);
    $this->get(route('admin.catalogue.index', ['status' => 'bogus', 'sort' => 'bogus']))->assertOk();
});

it('shows a model with its files, reviews and review history, and only offers the review queue to reviewers', function () {
    $product = Product::factory()->published()->create(['title' => 'Live oak chair', 'review_notes' => 'Looks great.', 'reviewed_at' => now(), 'reviewed_by' => staffWith('Marketplace moderator')->id]);
    SellerProfile::factory()->approved()->create(['user_id' => $product->user_id]);
    ProductFile::create(['product_id' => $product->id, 'disk' => 'local', 'path' => 'x/chair.fbx', 'original_name' => 'Chair.fbx', 'extension' => 'fbx', 'kind' => 'exchange', 'size_bytes' => 2097152]);
    ProductReview::factory()->create(['product_id' => $product->id, 'comment' => 'Sharp textures and clean topology.']);
    ProductReview::factory()->hidden()->create(['product_id' => $product->id, 'comment' => 'Hidden review text']);

    $this->actingAs(staffWith('Auditor'), 'staff')->get(route('admin.catalogue.show', $product))->assertOk()
        ->assertSee('Live oak chair')->assertSee('Chair.fbx')->assertSee('2.0 MB')->assertSee('Sharp textures and clean topology.')->assertSee('1 is hidden')->assertSee('Looks great.')
        ->assertDontSee('Open in the review queue');

    $this->actingAs(staffWith('Marketplace moderator'), 'staff')->get(route('admin.catalogue.show', $product))->assertSee('Open in the review queue');
    $this->actingAs(staffWith('Support'), 'staff')->get(route('admin.catalogue.show', $product))->assertForbidden();
});

it('lists every store whatever its status, and shows one with its models', function () {
    $approved = SellerProfile::factory()->approved()->create(['display_name' => 'Approved Studio']);
    SellerProfile::factory()->create(['display_name' => 'Pending Studio']);
    SellerProfile::factory()->rejected()->create(['display_name' => 'Rejected Studio']);
    SellerProfile::factory()->suspended()->create(['display_name' => 'Suspended Studio']);
    Product::factory()->published()->create(['user_id' => $approved->user_id, 'title' => 'Oak armchair model']);

    $this->actingAs(staffWith('Auditor'), 'staff')->get(route('admin.stores.index'))->assertOk()->assertSee('Approved Studio')->assertSee('Pending Studio')->assertSee('Rejected Studio')->assertSee('Suspended Studio')->assertSee('Live / all models')->assertSee('1 / 1');
    $this->get(route('admin.stores.index', ['status' => 'suspended']))->assertSee('Suspended Studio')->assertDontSee('Approved Studio');
    $this->get(route('admin.stores.show', $approved))->assertOk()->assertSee('Approved Studio')->assertSee('Oak armchair model')->assertDontSee('Suspend store');

    $this->actingAs(staffWith('Marketplace moderator'), 'staff')->get(route('admin.stores.show', $approved))->assertSee('Suspend store');
    $this->actingAs(staffWith('Dispute manager'), 'staff')->get(route('admin.stores.index'))->assertForbidden();
});

/** ---------------------------------------------------------------- the overview */
it('shows platform numbers and trends to staff who may view the overview, and nobody else', function () {
    Cache::forget(PlatformStatsService::CACHE_KEY);
    User::factory()->count(3)->create();
    User::factory()->create(['created_at' => now()->subDays(10)]);
    openProject();
    Product::factory()->published()->create();
    hire();

    $stats = app(PlatformStatsService::class)->get();
    expect($stats['members']['total'])->toBe(User::count())->and($stats['members']['new']['now'])->toBeGreaterThanOrEqual(3)->and($stats['projects']['open'])->toBeGreaterThanOrEqual(1)->and($stats['hires']['active'])->toBe(1)
        ->and($stats['series']['members'])->toHaveCount(PlatformStatsService::WEEKS);

    $this->actingAs(staffWith('Auditor'), 'staff')->get(route('admin.overview'))->assertOk()->assertSee('Platform overview')->assertSee('New members per week')->assertSee('Hires in progress');
    $this->actingAs(staffWith('Support'), 'staff')->get(route('admin.overview'))->assertForbidden();
});

it('caches the platform numbers for a few minutes', function () {
    Cache::forget(PlatformStatsService::CACHE_KEY);
    $first = app(PlatformStatsService::class)->get();
    User::factory()->count(2)->create();

    expect(app(PlatformStatsService::class)->get()['members']['total'])->toBe($first['members']['total']);

    Cache::forget(PlatformStatsService::CACHE_KEY);
    expect(app(PlatformStatsService::class)->get()['members']['total'])->toBe($first['members']['total'] + 2);
});

/** ---------------------------------------------------------------- sorting the lists */
it('sorts projects by budget and applicants, newest first by default, and ignores unknown sorts', function () {
    openProject(['title' => 'Cheap job', 'budget' => 100, 'created_at' => now()->subDays(1)]);
    openProject(['title' => 'Pricey job', 'budget' => 9000, 'created_at' => now()->subDays(3)]);
    openProject(['title' => 'Mid job', 'budget' => 500, 'created_at' => now()->subDays(2)]);
    $this->actingAs(staffWith('Auditor'), 'staff');

    $this->get(route('admin.projects.index'))->assertSeeInOrder(['Cheap job', 'Mid job', 'Pricey job']);
    $this->get(route('admin.projects.index', ['sort' => 'budget']))->assertSeeInOrder(['Pricey job', 'Mid job', 'Cheap job']);
    $this->get(route('admin.projects.index', ['sort' => 'budget', 'dir' => 'asc']))->assertSeeInOrder(['Cheap job', 'Mid job', 'Pricey job'])->assertSee('aria-sort="ascending"', false);
    $this->get(route('admin.projects.index', ['sort' => 'title', 'dir' => 'asc']))->assertSeeInOrder(['Cheap job', 'Mid job', 'Pricey job']);
    $this->get(route('admin.projects.index', ['sort' => 'nonsense', 'dir' => 'up']))->assertOk()->assertSeeInOrder(['Cheap job', 'Mid job', 'Pricey job']);
});

it('sorts the model catalogue by price and rating, keeping unrated models last', function () {
    $store = SellerProfile::factory()->approved()->create();
    Product::factory()->published()->create(['user_id' => $store->user_id, 'title' => 'Mid model', 'price_minor' => 5000, 'rating_avg' => 4.0, 'rating_count' => 3]);
    Product::factory()->published()->create(['user_id' => $store->user_id, 'title' => 'Dear model', 'price_minor' => 90000, 'rating_avg' => null, 'rating_count' => 0]);
    Product::factory()->published()->create(['user_id' => $store->user_id, 'title' => 'Cheap model', 'price_minor' => 100, 'rating_avg' => 5.0, 'rating_count' => 9]);
    $this->actingAs(staffWith('Auditor'), 'staff');

    $this->get(route('admin.catalogue.index', ['sort' => 'price']))->assertSeeInOrder(['Dear model', 'Mid model', 'Cheap model']);
    $this->get(route('admin.catalogue.index', ['sort' => 'price', 'dir' => 'asc']))->assertSeeInOrder(['Cheap model', 'Mid model', 'Dear model']);
    $this->get(route('admin.catalogue.index', ['sort' => 'rating']))->assertSeeInOrder(['Cheap model', 'Mid model', 'Dear model']);
});

it('sorts stores and staff by their columns', function () {
    SellerProfile::factory()->approved()->create(['display_name' => 'Zed Store']);
    SellerProfile::factory()->approved()->create(['display_name' => 'Alpha Store']);
    $this->actingAs(staffWith('Auditor'), 'staff')->get(route('admin.stores.index', ['sort' => 'name', 'dir' => 'asc']))->assertSeeInOrder(['Alpha Store', 'Zed Store']);
    $this->get(route('admin.stores.index', ['sort' => 'name', 'dir' => 'desc']))->assertSeeInOrder(['Zed Store', 'Alpha Store']);

    Staff::factory()->create(['name' => 'Aaron Staffer']);
    Staff::factory()->create(['name' => 'Zoe Staffer']);
    $this->actingAs(staffWith('Super admin'), 'staff')->get(route('admin.staff.index', ['sort' => 'name', 'dir' => 'asc']))->assertSeeInOrder(['Aaron Staffer', 'Zoe Staffer']);
    $this->get(route('admin.staff.index', ['sort' => 'name', 'dir' => 'desc']))->assertSeeInOrder(['Zoe Staffer', 'Aaron Staffer']);
});

it('sorts hires by amount', function () {
    $small = hire('Small hire');
    $big = hire('Big hire');
    $small->update(['agreed_amount' => 100]);
    $big->update(['agreed_amount' => 9000]);

    $this->actingAs(staffWith('Auditor'), 'staff')->get(route('admin.engagements.index', ['sort' => 'amount']))->assertSeeInOrder(['Big hire', 'Small hire']);
    $this->get(route('admin.engagements.index', ['sort' => 'amount', 'dir' => 'asc']))->assertSeeInOrder(['Small hire', 'Big hire']);
});
