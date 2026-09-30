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

it('marks exactly the matching item active, with the policy page not lighting up Engagements', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('engagements.policy'));
    $active = collect(SidebarMenu::for($user))->flatMap->items->where('active', true)->pluck('label')->all();
    expect($active)->toBe(['Cancellation policy']);

    $this->actingAs($user)->get(route('engagements.index'));
    $active = collect(SidebarMenu::for($user))->flatMap->items->where('active', true)->pluck('label')->all();
    expect($active)->toBe(['Engagements']);
});

it('builds the breadcrumb from the active item', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('applications.my'));

    expect(SidebarMenu::current(SidebarMenu::for($user)))->toBe(['group' => 'Find work', 'item' => 'My applications']);
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
