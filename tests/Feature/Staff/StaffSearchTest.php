<?php

use App\Models\JobApplication;
use App\Models\JobEngagement;
use App\Models\ModelJob;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\Staff;
use App\Models\User;
use App\Services\Admin\StaffSearchService;

/*
 * The Ctrl+K palette and its search: groups per area, each only for staff who may view that area, contact details masked.
 */

function searchFor(Staff $staff, string $term): array
{
    return collect(app(StaffSearchService::class)->search($staff, $term))->mapWithKeys(fn ($group) => [$group['label'] => collect($group['items'])->pluck('title')->all()])->all();
}

it('finds across members, projects, models, stores and staff, linking to each page', function () {
    $member = User::factory()->create(['name' => 'Zanele Findable']);
    ModelJob::factory()->create(['title' => 'Zanele lobby render']);
    $store = SellerProfile::factory()->approved()->create(['display_name' => 'Zanele Studio']);
    Product::factory()->published()->create(['title' => 'Zanele armchair', 'user_id' => $store->user_id]);
    Staff::factory()->create(['name' => 'Zanele Staffer']);

    $found = searchFor(staffWith('Super admin'), 'zanele');

    expect($found)->toHaveKeys(['Members', 'Projects', 'Models', 'Stores', 'Staff'])
        ->and($found['Members'])->toContain('Zanele Findable')->and($found['Projects'])->toBe(['Zanele lobby render'])->and($found['Models'])->toBe(['Zanele armchair'])
        ->and($found['Stores'])->toBe(['Zanele Studio'])->and($found['Staff'])->toBe(['Zanele Staffer']);

    $this->actingAs(staffWith('Super admin'), 'staff')->getJson(route('admin.search', ['q' => 'zanele findable']))->assertOk()
        ->assertJsonPath('groups.0.label', 'Members')->assertJsonPath('groups.0.items.0.url', route('admin.members.show', $member));
});

it('searches hires and disputes by project title', function () {
    $application = JobApplication::factory()->hired()->create(['job_id' => ModelJob::factory()->create(['title' => 'Quokka pavilion'])->id]);
    JobEngagement::create(['application_id' => $application->id, 'status' => 'active', 'agreed_amount' => 100, 'service_fee' => 10, 'net_amount' => 90, 'started_at' => now()]);
    ['application' => $disputed] = makeDisputedEngagement();
    $disputed->job->update(['title' => 'Quokka dispute job']);

    $found = searchFor(staffWith('Super admin'), 'quokka');

    expect($found['Hires'])->toContain('Quokka pavilion')->and($found['Disputes'])->toBe(['Quokka dispute job']);
});

it('only searches the areas the person may view', function () {
    User::factory()->create(['name' => 'Yara Everywhere']);
    ModelJob::factory()->create(['title' => 'Yara project']);
    Staff::factory()->create(['name' => 'Yara Staff']);

    expect(array_keys(searchFor(staffWith('Support'), 'yara')))->toBe(['Members'])
        ->and(array_keys(searchFor(staffWith('Marketplace moderator'), 'yara')))->toBe([])
        ->and(array_keys(searchFor(staffWith('Auditor'), 'yara')))->toBe(['Members', 'Projects'])
        ->and(array_keys(searchFor(staffWith('Super admin'), 'yara')))->toBe(['Members', 'Projects', 'Staff'])
        ->and(searchFor(staffWith(), 'yara'))->toBe([]);
});

it('masks email addresses, and only matches them for staff who may see them', function () {
    User::factory()->create(['name' => 'Private Person', 'email' => 'secret.address@example.test']);

    $auditor = staffWith('Auditor'); // may view members, not their contact details
    expect(searchFor($auditor, 'secret.address'))->toBe([])
        ->and(searchFor(staffWith('Support'), 'secret.address'))->toHaveKey('Members');

    $items = app(StaffSearchService::class)->search($auditor, 'Private')[0]['items'];
    expect($items[0]['subtitle'])->not->toContain('secret.address')->toContain('•');
});

it('ignores very short searches and treats wildcard characters as plain text', function () {
    User::factory()->create(['name' => 'Alpha Person']);
    User::factory()->create(['name' => 'Percent 100% Person']);
    $admin = staffWith('Super admin');

    expect(searchFor($admin, 'a'))->toBe([])->and(searchFor($admin, ''))->toBe([])
        ->and(searchFor($admin, '%%'))->toBe([])
        ->and(searchFor($admin, '100%')['Members'])->toBe(['Percent 100% Person']);
});

it('caps each group, and keeps the search behind the staff sign-in', function () {
    User::factory()->count(8)->create(['name' => 'Capped Person']);

    expect(searchFor(staffWith('Support'), 'capped')['Members'])->toHaveCount(StaffSearchService::PER_GROUP);

    auth('staff')->logout();
    $this->getJson(route('admin.search', ['q' => 'capped']))->assertUnauthorized();
});

it('puts the palette on every staff page, listing only the pages and actions the person may use', function () {
    $page = $this->actingAs(staffWith('Support'), 'staff')->get(route('admin.dashboard'))->assertOk();
    $page->assertSee('open-palette', false)->assertSee(route('admin.members.index'), false)->assertSee('Your account')
        ->assertDontSee('Invite staff')->assertDontSee('Security settings')->assertDontSee(route('admin.settings.security'), false);

    $this->actingAs(staffWith('Super admin'), 'staff')->get(route('admin.dashboard'))->assertSee('Invite staff')->assertSee('Security settings');
});
