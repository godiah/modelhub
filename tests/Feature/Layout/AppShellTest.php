<?php

use App\Models\User;
use App\Support\Navigation\SidebarMenu;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

// Distinguishes the two shells: the sidebar shell labels its nav, the public shell renders the top navbar.
const SIDEBAR_MARKER = 'aria-label="Main navigation"';

it('shows the landing page to guests', function () {
    $this->get('/')->assertOk()->assertDontSee(SIDEBAR_MARKER, false);
});

it('redirects signed-in users from the landing page to the dashboard', function () {
    $this->actingAs(User::factory()->create())
        ->get('/')
        ->assertRedirect(route('dashboard'));
});

it('renders the sidebar shell for signed-in users on app pages', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(SIDEBAR_MARKER, false)
        ->assertSee('Post a project')
        ->assertSee('Find work');
});

it('renders the same public pages inside the sidebar shell when signed in, and the top navbar for guests', function () {
    $this->get(route('jobs.browse'))->assertOk()->assertDontSee(SIDEBAR_MARKER, false);

    $this->actingAs(User::factory()->create())
        ->get(route('jobs.browse'))
        ->assertOk()
        ->assertSee(SIDEBAR_MARKER, false);
});

it('hides the administration group from regular users', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Administration')
        ->assertDontSee('Staff roles');
});

it('shows the administration group to staff according to their permissions', function () {
    $support = User::factory()->create();
    $support->assignRole('support');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($support)->get(route('dashboard'))->assertSee('Administration')->assertSee('Disputed engagements');

    $this->actingAs($admin)->get(route('dashboard'))->assertSee('Administration')->assertSee('Staff roles');
});

it('marks exactly the matching sidebar item active; unlisted pages light up nothing', function () {
    $user = User::factory()->create();
    $activeLabels = fn () => collect(SidebarMenu::for($user))->flatMap->items->where('active', true)->pluck('label')->all();

    $this->actingAs($user)->get(route('engagements.index'));
    expect($activeLabels())->toBe(['Engagements']);

    // The policy page lives in the footer, not the sidebar, and must not light up Engagements.
    $this->actingAs($user)->get(route('engagements.policy'));
    expect($activeLabels())->toBe([]);
});

it('builds the breadcrumb from the active item, linking it when the page adds a tail', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('applications.my'));

    expect(SidebarMenu::breadcrumb())->toBe([
        ['label' => 'Find work', 'url' => null],
        ['label' => 'My applications', 'url' => null],
    ]);

    expect(SidebarMenu::breadcrumb('Archived'))->toBe([
        ['label' => 'Find work', 'url' => null],
        ['label' => 'My applications', 'url' => route('applications.my')],
        ['label' => 'Archived', 'url' => null],
    ]);
});

it('resolves breadcrumbs for pages that are not sidebar entries', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('profile'));
    expect(SidebarMenu::breadcrumb())->toBe([
        ['label' => 'Account', 'url' => null],
        ['label' => 'Profile', 'url' => null],
    ]);

    $this->actingAs($user)->get(route('engagements.policy'));
    expect(collect(SidebarMenu::breadcrumb())->pluck('label')->all())->toBe(['Help', 'Cancellation policy']);
});

it('gives signed-in users the slim app footer and guests the marketing footer', function () {
    $this->get(route('jobs.browse'))->assertSee('Connect With Us')->assertDontSee('Cancellation &amp; payment policy', false);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertSee('Cancellation &amp; payment policy', false)
        ->assertDontSee('Connect With Us');
});

it('keeps guests on the public shell with the page title in the header band', function () {
    $this->get(route('jobs.browse'))
        ->assertOk()
        ->assertDontSee(SIDEBAR_MARKER, false)
        ->assertSee('Browse Available Jobs')
        ->assertSee('Connect With Us');
});

it('shows the landing page footer to guests', function () {
    $this->get('/')->assertOk()->assertSee('Connect With Us');
});

it('renders the sidebar collapsed when the collapse cookie is set', function () {
    $this->actingAs(User::factory()->create())
        ->withUnencryptedCookie('modelhub_sidebar', '1')
        ->get(route('dashboard'))
        ->assertSee('class="sidebar-collapsed"', false);
});

it('renders the sidebar expanded by default', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertDontSee('class="sidebar-collapsed"', false);
});
