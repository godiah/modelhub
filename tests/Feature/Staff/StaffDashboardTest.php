<?php

use App\Models\JobDeliverable;
use App\Models\ModelJob;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ReviewReport;
use App\Models\SellerProfile;
use App\Models\Staff;
use App\Models\StaffActivity;
use App\Models\User;
use App\Models\WishlistItem;
use App\Services\Admin\StaffDashboardService;
use App\Support\Staff\StaffAudit;

/*
 * The staff dashboard: what needs the person, their own work, then the platform, each block built only from the permissions
 * they hold. Helpers hire() and openProject() live in PlatformDirectoriesTest; makeDisputedEngagement() in DisputeResolutionTest.
 */

function dashboardFor(Staff $staff)
{
    return test()->actingAs($staff, 'staff')->get(route('admin.dashboard'))->assertOk();
}

function publishedByApprovedSeller(array $overrides = []): Product
{
    $seller = User::factory()->create();
    SellerProfile::factory()->approved()->create(['user_id' => $seller->id]);

    return Product::factory()->published()->create($overrides + ['user_id' => $seller->id]);
}

/** ---------------------------------------------------------------- needs attention */
it('lists the waiting items oldest first and colours them by how long they have waited', function () {
    Product::factory()->inReview()->create(['title' => 'Fresh chair', 'submitted_at' => now()->subHours(5)]);
    Product::factory()->inReview()->create(['title' => 'Stale lamp', 'submitted_at' => now()->subDays(8)]);
    SellerProfile::factory()->create(['display_name' => 'Middling Studio', 'submitted_at' => now()->subDays(4)]);

    $items = app(StaffDashboardService::class)->for(staffWith('Marketplace moderator'))['attention']['items'];

    expect(collect($items)->pluck('title')->all())->toBe(['Stale lamp', 'Middling Studio', 'Fresh chair'])
        ->and(collect($items)->pluck('tone')->all())->toBe(['red', 'amber', 'neutral']);

    dashboardFor(staffWith('Marketplace moderator'))->assertSee('Needs attention')->assertSee('Stale lamp')->assertSee('Middling Studio')
        ->assertSeeInOrder(['Stale lamp', 'Middling Studio', 'Fresh chair']);
});

it('says how many items need the person and how many are late', function () {
    Product::factory()->inReview()->create(['submitted_at' => now()->subDays(9)]);
    Product::factory()->inReview()->create(['submitted_at' => now()->subDay()]);

    dashboardFor(staffWith('Marketplace moderator'))->assertSee('2 items need you')->assertSee('1 has waited over 3 days')->assertSee('1 late');
});

it('says every queue is clear, and shows an all-clear card, when nothing waits', function () {
    dashboardFor(staffWith('Marketplace moderator'))->assertSee('Every queue is clear.')->assertSee('Nothing is waiting for you');
});

it('flags a dispute nobody has taken on, and drops the flag once it is assigned', function () {
    ['dispute' => $dispute] = makeDisputedEngagement();
    $manager = staffWith('Dispute manager');

    dashboardFor($manager)->assertSee('Payment dispute')->assertSee('Unassigned')->assertSee('Nobody has taken it on');

    $dispute->assignAdmin($manager->id);

    dashboardFor($manager)->assertDontSee('Unassigned')->assertSee('You are handling it');
});

it('counts reported reviews by the oldest open report and ignores resolved ones', function () {
    openReport(at: now()->subDays(5));
    $review = ProductReview::factory()->create();
    ReviewReport::create(['review_id' => $review->id, 'user_id' => User::factory()->create()->id, 'reason' => 'spam', 'status' => 'dismissed']);

    $attention = app(StaffDashboardService::class)->for(staffWith('Marketplace moderator'))['attention'];

    expect(collect($attention['queues'])->firstWhere('label', 'Reports'))->toMatchArray(['count' => 1, 'late' => 1])
        ->and($attention['items'])->toHaveCount(1)->and($attention['items'][0]['tone'])->toBe('amber');
});

it('only builds the queues the person may work', function () {
    Product::factory()->inReview()->create();
    makeDisputedEngagement();

    $labels = fn (Staff $staff) => collect(app(StaffDashboardService::class)->for($staff)['attention']['queues'])->pluck('label')->all();

    expect($labels(staffWith('Marketplace moderator')))->toBe(['Models', 'Applications', 'Reports'])
        ->and($labels(staffWith('Dispute manager')))->toBe(['Disputes'])
        ->and($labels(staffWith('Support')))->toBe(['Disputes'])
        ->and($labels(staffWith('Auditor')))->toBe(['Disputes'])
        ->and($labels(staffWith('Super admin')))->toBe(['Models', 'Applications', 'Reports', 'Disputes'])
        ->and($labels(staffWith()))->toBe([]);

    dashboardFor(staffWith('Dispute manager'))->assertDontSee('Model to review');
});

/** ---------------------------------------------------------------- your work */
it('shows the disputes the person is handling, their decisions this week and what they did last', function () {
    $manager = staffWith('Dispute manager');
    ['dispute' => $dispute, 'application' => $application] = makeDisputedEngagement();
    $dispute->assignAdmin($manager->id);
    StaffAudit::log('model.published', 'Published "Oak chair"', staffId: $manager->id);
    StaffAudit::log('model.rejected', 'Rejected "Bad chair"', staffId: $manager->id);
    StaffAudit::log('dispute.resolved', 'Resolved a dispute', staffId: $manager->id);
    StaffAudit::log('staff.signed-in', 'Signed in', staffId: $manager->id);
    StaffActivity::where('action', 'model.rejected')->update(['created_at' => now()->subDays(20)]);

    $work = app(StaffDashboardService::class)->for($manager)['work'];

    expect($work['week'])->toBe(['model' => 1, 'dispute' => 1])->and($work['recent']->pluck('action')->all())->not->toContain('staff.signed-in');

    dashboardFor($manager)->assertSee('Disputes you are handling')->assertSee($application->job->title)->assertSee('Your last 7 days')->assertSee('Published "Oak chair"');
});

it('says so when the person has made no decisions this week', function () {
    dashboardFor(staffWith('Support'))->assertSee('No decisions yet this week.');
});

/** ---------------------------------------------------------------- the platform */
it('shows the platform pulse only to those who may view the overview', function () {
    User::factory()->count(2)->create();

    dashboardFor(staffWith('Platform manager'))->assertSee('Open projects')->assertSee('In escrow');
    dashboardFor(staffWith('Auditor'))->assertSee('Open projects');
    dashboardFor(staffWith('Super admin'))->assertSee('Live models');
    dashboardFor(staffWith('Support'))->assertDontSee('In escrow')->assertDontSee('Hires in progress');
    dashboardFor(staffWith('Marketplace moderator'))->assertDontSee('In escrow');
});

/** ---------------------------------------------------------------- role feeds */
it('gives each role only the feeds its permissions open', function () {
    $feeds = fn (Staff $staff) => array_keys(app(StaffDashboardService::class)->for($staff)['feeds']);

    expect($feeds(staffWith('Support')))->toBe(['members'])
        ->and($feeds(staffWith('Marketplace moderator')))->toBe(['models'])
        ->and($feeds(staffWith('Dispute manager')))->toBe(['hires'])
        ->and($feeds(staffWith('Auditor')))->toBe(['members', 'projects', 'hires', 'models'])
        ->and($feeds(staffWith('Super admin')))->toBe(['members', 'projects', 'hires', 'models'])
        ->and($feeds(staffWith()))->toBe([]);

    dashboardFor(staffWith('Support'))->assertSee('Newest sign-ups')->assertDontSee('Newly posted')->assertDontSee('Newly published')->assertDontSee('Hires needing a look');
});

it('feeds the members block with sign-ups, suspensions and unverified accounts', function () {
    User::factory()->create(['name' => 'Fresh Face']);
    User::factory()->unverified()->create();
    $banned = User::factory()->create(['name' => 'Banned Bob', 'suspended_at' => now()->subHour(), 'suspended_by' => null]);

    dashboardFor(staffWith('Support'))->assertSee('Fresh Face')->assertSee('Recently suspended')->assertSee('Banned Bob')->assertSee('1 member has not verified their email');
});

it('feeds the projects block with new projects, quiet ones and take-downs', function () {
    openProject(['title' => 'Brand new brief']);
    $quiet = openProject(['title' => 'Lonely brief']);
    $quiet->forceFill(['created_at' => now()->subDays(10)])->save();
    ModelJob::factory()->create(['title' => 'Removed brief', 'taken_down_at' => now()]);

    dashboardFor(staffWith('Platform manager'))->assertSee('Newly posted')->assertSee('Brand new brief')->assertSee('1 open project has had no applicants for over a week')->assertSee('1 project is taken down');
});

it('lists active hires with overdue deliverables, worst first, and ignores those on schedule', function () {
    $late = hire('Very late hire');
    $recent = hire('Slightly late hire');
    $fine = hire('Punctual hire');
    JobDeliverable::create(['engagement_id' => $late->id, 'title' => 'a', 'description' => 'd', 'status' => 'pending', 'due_date' => today()->subDays(9)]);
    JobDeliverable::create(['engagement_id' => $late->id, 'title' => 'b', 'description' => 'd', 'status' => 'pending', 'due_date' => today()->subDays(2)]);
    JobDeliverable::create(['engagement_id' => $recent->id, 'title' => 'c', 'description' => 'd', 'status' => 'pending', 'due_date' => today()->subDay()]);
    JobDeliverable::create(['engagement_id' => $fine->id, 'title' => 'd', 'description' => 'd', 'status' => 'pending', 'due_date' => today()->addDays(4)]);

    dashboardFor(staffWith('Dispute manager'))->assertSeeInOrder(['Very late hire', '2 overdue', 'oldest 9 days late', 'Slightly late hire'])->assertDontSee('Punctual hire');
});

it('says when no hire is late', function () {
    hire();

    dashboardFor(staffWith('Dispute manager'))->assertSee('Every active hire is on schedule.');
});

it('feeds the models block with the newest published and the most saved this week', function () {
    $hot = publishedByApprovedSeller(['title' => 'Hot sofa']);
    $cold = publishedByApprovedSeller(['title' => 'Cold sofa']);
    foreach (range(1, 2) as $i) {
        WishlistItem::create(['user_id' => User::factory()->create()->id, 'product_id' => $hot->id]);
    }
    WishlistItem::create(['user_id' => User::factory()->create()->id, 'product_id' => $cold->id]);
    $old = WishlistItem::create(['user_id' => User::factory()->create()->id, 'product_id' => $cold->id]);
    WishlistItem::where('id', $old->id)->update(['created_at' => now()->subDays(30)]);

    $saved = app(StaffDashboardService::class)->for(staffWith('Marketplace moderator'))['feeds']['models']['saved'];

    expect($saved->pluck('title')->all())->toBe(['Hot sofa', 'Cold sofa'])->and($saved->pluck('week_saves')->all())->toBe([2, 1]);

    dashboardFor(staffWith('Marketplace moderator'))->assertSee('Newly published')->assertSee('Most saved this week')->assertSee('2 saves');
});

/** ---------------------------------------------------------------- team and security */
it('shows the team block to those who may read the log or manage staff, and nobody else', function () {
    dashboardFor(staffWith('Auditor'))->assertSee('Team and security')->assertSee('Decisions this week')->assertSee('failed sign-in attempts')->assertDontSee('active staff without a role');
    dashboardFor(staffWith('Super admin'))->assertSee('Team and security')->assertSee('active staff without a role')->assertSee('deactivated accounts');
    dashboardFor(staffWith('Support'))->assertDontSee('Team and security');
    dashboardFor(staffWith('Marketplace moderator'))->assertDontSee('Team and security');
    dashboardFor(staffWith('Dispute manager'))->assertDontSee('Team and security');
});

it('reports each person\'s decisions, failed sign-ins and the accounts that need tidying', function () {
    $busy = staffWith('Marketplace moderator');
    $busy->forceFill(['last_login_at' => now()])->save();
    foreach (range(1, 3) as $i) {
        StaffAudit::log('model.published', 'Published', staffId: $busy->id);
    }
    StaffAudit::log('staff.sign-in-failed', 'Tried to sign in and failed');
    StaffAudit::log('staff.sign-in-failed', 'Tried to sign in and failed');
    StaffActivity::where('action', 'staff.sign-in-failed')->first()->forceFill(['created_at' => now()->subDays(3)])->save();
    Staff::factory()->create(); // no role, never signed in
    Staff::factory()->inactive()->create();

    $team = app(StaffDashboardService::class)->for(staffWith('Super admin'))['team'];

    expect($team['decisions'][0]['staff']->is($busy))->toBeTrue()->and($team['decisions'][0]['total'])->toBe(3)
        ->and($team['failed_signins'])->toBe(1)
        ->and($team['active_today']->pluck('id'))->toContain($busy->id)
        ->and($team['no_role'])->toBe(1)->and($team['never_signed_in'])->toBeGreaterThanOrEqual(2)->and($team['deactivated'])->toBe(1);
});

/** ---------------------------------------------------------------- the page as a whole */
it('renders for a person with no role, with no data blocks at all', function () {
    $page = app(StaffDashboardService::class)->for(Staff::factory()->create());

    expect($page['attention']['queues'])->toBe([])->and($page['pulse'])->toBeNull()->and($page['feeds'])->toBe([])->and($page['team'])->toBeNull();
});

it('renders fully for a Super admin on a busy platform without lazy loading anything', function () {
    Product::factory()->inReview()->count(3)->create();
    SellerProfile::factory()->count(2)->create();
    openReport();
    makeDisputedEngagement();
    $late = hire();
    JobDeliverable::create(['engagement_id' => $late->id, 'title' => 'a', 'description' => 'd', 'status' => 'pending', 'due_date' => today()->subDays(3)]);
    openProject();
    WishlistItem::create(['user_id' => User::factory()->create()->id, 'product_id' => publishedByApprovedSeller()->id]);
    $admin = staffWith('Super admin');
    StaffAudit::log('model.published', 'Published', staffId: $admin->id);

    dashboardFor($admin)->assertSee('Needs attention')->assertSee('Your work')->assertSee('Platform')->assertSee('Newest sign-ups')->assertSee('Newly posted')
        ->assertSee('Hires needing a look')->assertSee('Newly published')->assertSee('Team and security');
});

it('counts the person\'s decisions per day, for the week and the week before', function () {
    $staff = staffWith('Marketplace moderator');
    foreach ([0, 0, 1, 3] as $daysAgo) {
        StaffAudit::log('model.published', 'Published a model', staffId: $staff->id);
        StaffActivity::latest('id')->first()->forceFill(['created_at' => now()->subDays($daysAgo)])->save();
    }
    foreach ([8, 10, 12] as $daysAgo) {
        StaffAudit::log('seller.approved', 'Approved a store', staffId: $staff->id);
        StaffActivity::latest('id')->first()->forceFill(['created_at' => now()->subDays($daysAgo)])->save();
    }
    StaffAudit::log('model.published', 'Too old to count', staffId: $staff->id);
    StaffActivity::latest('id')->first()->forceFill(['created_at' => now()->subDays(20)])->save();

    $work = app(StaffDashboardService::class)->for($staff)['work'];

    expect($work['total'])->toBe(4)->and($work['previous'])->toBe(3)->and($work['week'])->toBe(['model' => 4])
        ->and(collect($work['daily'])->pluck('count')->all())->toBe([0, 0, 0, 1, 0, 1, 2])
        ->and(collect($work['daily'])->last()['today'])->toBeTrue()->and($work['daily'])->toHaveCount(7);

    dashboardFor($staff)->assertSee('Your last 7 days')->assertSee('+1')->assertSee('3 the week before');
});

it('shows an empty week as a friendly note, not a blank chart', function () {
    $work = app(StaffDashboardService::class)->for(staffWith('Support'))['work'];

    expect($work['total'])->toBe(0)->and(collect($work['daily'])->sum('count'))->toBe(0);

    dashboardFor(staffWith('Support'))->assertSee('No decisions yet this week.')->assertSee('are counted here');
});

it('shows each late hire with both people, delivery progress and how late it is, and counts them all', function () {
    $late = hire('Progress hire');
    JobDeliverable::create(['engagement_id' => $late->id, 'title' => 'a', 'description' => 'd', 'status' => 'approved']);
    JobDeliverable::create(['engagement_id' => $late->id, 'title' => 'b', 'description' => 'd', 'status' => 'approved']);
    JobDeliverable::create(['engagement_id' => $late->id, 'title' => 'c', 'description' => 'd', 'status' => 'pending', 'due_date' => today()->subDays(8)]);
    foreach (range(1, 6) as $i) {
        $other = hire("Other late {$i}");
        JobDeliverable::create(['engagement_id' => $other->id, 'title' => 'x', 'description' => 'd', 'status' => 'pending', 'due_date' => today()->subDay()]);
    }

    $feed = app(StaffDashboardService::class)->for(staffWith('Dispute manager'))['feeds']['hires'];
    $row = $feed['stuck']->firstWhere('id', $late->id);

    // Five rows are shown, but the badge counts every late hire
    expect($feed['stuck'])->toHaveCount(5)->and($feed['late'])->toBe(7)->and($feed['active'])->toBe(7)
        ->and($row->approved_deliverables)->toBe(2)->and($row->total_deliverables)->toBe(3)->and($row->overdue_count)->toBe(1);

    dashboardFor(staffWith('Dispute manager'))->assertSee('7 late')->assertSee('Progress hire')->assertSee('2/3')->assertSee('oldest 8 days late');
});

it('says how many hires are in progress when none is late', function () {
    hire();
    hire('Second hire');

    dashboardFor(staffWith('Dispute manager'))->assertSee('Every active hire is on schedule.')->assertSee('2 hires in progress, none overdue.')->assertDontSee('1 late')->assertDontSee('2 late');
});

it('ranks decisions, counts who is active and flags security gaps for the team block', function () {
    $quiet = staffWith('Support');
    $busy = staffWith('Marketplace moderator');
    $busy->forceFill(['last_login_at' => now()->subMinutes(5)])->save();
    foreach (range(1, 3) as $i) {
        StaffAudit::log('model.published', 'Published', staffId: $busy->id);
    }
    StaffAudit::log('model.rejected', 'Rejected', staffId: $quiet->id);
    authenticatorFor($busy);
    Staff::factory()->create(); // no role, never signed in

    $team = app(StaffDashboardService::class)->for(staffWith('Super admin'))['team'];

    expect($team['decisions']->pluck('total')->all())->toBe([3, 1])->and($team['decisions'][0]['staff']->is($busy))->toBeTrue()
        ->and($team['active_staff'])->toBe(4)->and($team['with_app'])->toBe(1)->and($team['no_role'])->toBe(1);

    dashboardFor(staffWith('Super admin'))->assertSee('staff use an authenticator app')->assertSee('Active in the last 15 minutes');
});

it('shows the platform rules in force to Super admins only', function () {
    setting('security.otp_staff_required', true);

    $team = app(StaffDashboardService::class)->for(staffWith('Super admin'))['team'];
    expect(collect($team['posture'])->pluck('on', 'label')->all())->toBe(['Member sign-in codes' => false, 'Staff sign-in codes' => true, 'One session per account' => false]);

    dashboardFor(staffWith('Super admin'))->assertSee('Platform rules')->assertSee('Staff sign-in codes: on')->assertSee('Member sign-in codes: off');
    expect(app(StaffDashboardService::class)->for(staffWith('Auditor'))['team']['posture'])->toBeNull();
    dashboardFor(staffWith('Auditor'))->assertSee('Team and security')->assertDontSee('Platform rules');
});

it('shows only the latest four entries in recent staff activity, newest first', function () {
    $admin = staffWith('Super admin');
    foreach (range(1, 6) as $i) {
        StaffAudit::log('model.published', "Published model number {$i}", staffId: $admin->id);
    }

    $activity = app(StaffDashboardService::class)->for($admin)['activity'];

    expect($activity)->toHaveCount(4)->and($activity->pluck('summary')->all())->toBe(['Published model number 6', 'Published model number 5', 'Published model number 4', 'Published model number 3']);

    dashboardFor($admin)->assertSee('published model number 6')->assertDontSee('published model number 2')->assertDontSee('published model number 1');
});
