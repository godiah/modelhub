<?php

use App\Models\SellerProfile;
use App\Models\User;
use App\Support\Navigation\SidebarMenu;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Volt\Volt;

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
        ->assertSee('Projects')
        ->assertSee('Models');
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

    $this->actingAs($support)->get(route('dashboard'))->assertOk()->assertSee('Administration')->assertSee('Disputed engagements');

    $this->actingAs($admin)->get(route('dashboard'))->assertOk()->assertSee('Administration')->assertSee('Staff roles');
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
        ['label' => 'Projects', 'url' => null],
        ['label' => 'My applications', 'url' => null],
    ]);

    expect(SidebarMenu::breadcrumb('Archived'))->toBe([
        ['label' => 'Projects', 'url' => null],
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
    $this->get(route('jobs.browse'))->assertOk()->assertSee('Join free')->assertSee(route('legal.terms'), false);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertSee('Cancellation &amp; payment policy', false)
        ->assertDontSee('Join free');
});

it('keeps guests on the public shell with the page title in the header band', function () {
    $this->get(route('jobs.browse'))
        ->assertOk()
        ->assertDontSee(SIDEBAR_MARKER, false)
        ->assertSee('Browse projects')
        ->assertSee('Join free');
});

it('shows the landing page footer to guests', function () {
    $this->get('/')->assertOk()->assertSee('Join free');
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

/** ---------------------------------------------------------------- sidebar grouping */
function sidebarGroups(User $user): array
{
    return collect(SidebarMenu::for($user))->mapWithKeys(fn ($group) => [$group['label'] => collect($group['items'])->pluck('label')->all()])->all();
}

it('groups the sidebar by product: Models and Projects, with Engagements inside Projects', function () {
    $groups = sidebarGroups(User::factory()->create());

    expect(array_keys($groups))->toBe(['Overview', 'Models', 'Projects'])
        ->and($groups['Overview'])->toBe(['Dashboard', 'Notifications'])
        ->and($groups['Models'])->toBe(['Browse models', 'Wishlist', 'Sell models'])
        ->and($groups['Projects'])->toBe(['Browse projects', 'My applications', 'Post a project', 'Posted projects', 'Engagements']);
});

it('swaps "Sell models" for My models and My store once a member is an approved seller, under a Selling sub-label', function () {
    $seller = User::factory()->create();
    SellerProfile::factory()->approved()->create(['user_id' => $seller->id]);

    expect(sidebarGroups($seller)['Models'])->toBe(['Browse models', 'Wishlist', 'My models', 'My store']);

    $items = collect(SidebarMenu::for($seller))->firstWhere('label', 'Models')['items'];
    expect(collect($items)->pluck('section')->all())->toBe([null, null, 'Selling', 'Selling']);

    $this->actingAs($seller)->get(route('dashboard'))->assertOk()->assertSee('Selling');
    // Someone who is not a seller sees "Sell models" as a plain item, with no Selling label above it
    $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertOk()->assertDontSee('My store')->assertDontSee('Selling');

    // Applicants still see the way in, not the seller pages
    $applicant = User::factory()->create();
    SellerProfile::factory()->create(['user_id' => $applicant->id]);
    expect(sidebarGroups($applicant)['Models'])->toBe(['Browse models', 'Wishlist', 'Sell models']);
});

it('gives every sidebar entry its own icon', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    SellerProfile::factory()->approved()->create(['user_id' => $admin->id]);

    $icons = collect(SidebarMenu::for($admin))->flatMap->items->pluck('icon');

    expect($icons->duplicates()->all())->toBe([]);
});

it('keeps breadcrumbs working for the seller pages and the moved projects pages', function () {
    $seller = User::factory()->create();
    SellerProfile::factory()->approved()->create(['user_id' => $seller->id]);

    $this->actingAs($seller)->get(route('seller.models.index'));
    expect(SidebarMenu::breadcrumb())->toBe([['label' => 'Models', 'url' => null], ['label' => 'My models', 'url' => null]]);

    $this->actingAs($seller)->get(route('jobs.create'));
    expect(SidebarMenu::breadcrumb())->toBe([['label' => 'Projects', 'url' => null], ['label' => 'Post a project', 'url' => null]]);
});

/** ---------------------------------------------------------------- account menu */
it('shows account links, help and log out in the account menu', function () {
    $user = User::factory()->create(['name' => 'Amina Otieno', 'email' => 'amina@example.test']);

    $this->actingAs($user)->get(route('dashboard'))->assertOk()
        ->assertSee('Amina Otieno')->assertSee('amina@example.test')
        ->assertSee(route('profile').'#overview', false)
        ->assertSee(route('profile').'#profile', false)
        ->assertSee(route('profile').'#security', false)
        ->assertSee(route('legal.terms'), false)
        ->assertSee(route('legal.privacy'), false)
        ->assertSee(route('engagements.policy'), false)
        ->assertSee('Log out')
        ->assertDontSee('Your store')
        ->assertDontSee('Log Out');
});

it('adds the store and its public storefront to the account menu for approved sellers only', function () {
    $seller = User::factory()->create();
    $store = SellerProfile::factory()->approved()->create(['user_id' => $seller->id, 'display_name' => 'Kevin 3D Studio']);

    $this->actingAs($seller)->get(route('dashboard'))->assertOk()
        ->assertSee('Your store')->assertSee('Kevin 3D Studio')
        ->assertSee(route('seller.store.edit'), false)
        ->assertSee(route('sellers.show', $store->slug), false)
        ->assertSee('View public storefront');

    $pending = User::factory()->create();
    SellerProfile::factory()->create(['user_id' => $pending->id]);
    $this->actingAs($pending)->get(route('dashboard'))->assertOk()->assertDontSee('View public storefront');
});

it('logs a member out from the account menu', function () {
    $user = User::factory()->create();

    Volt::actingAs($user)->test('layout.user-menu')->call('logout')->assertRedirect('/');

    $this->assertGuest();
});

it('does not repeat the page name in the breadcrumb on pages that are menu entries themselves', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    SellerProfile::factory()->approved()->create(['user_id' => $admin->id]);
    $this->actingAs($admin);

    foreach (['admin.disputes.index', 'admin.models.index', 'admin.sellers.index', 'admin.reviews.index', 'seller.models.index', 'engagements.policy', 'legal.terms', 'legal.privacy'] as $route) {
        $html = $this->get(route($route))->assertOk()->getContent();
        preg_match('/<nav aria-label="Breadcrumb".*?<\/nav>/s', $html, $nav);
        $labels = collect(preg_split('/\s*\n\s*/', trim(strip_tags($nav[0] ?? ''))))->filter(fn ($l) => $l !== '' && $l !== '/')->values();

        expect($labels->duplicates()->all())->toBe([], "{$route} repeats a breadcrumb label: ".$labels->implode(' > '));
    }
});

/** ---------------------------------------------------------------- the top bar's primary action */
function actionOn(string $route, User $user, array $params = []): ?array
{
    test()->actingAs($user)->get(route($route, $params))->assertOk();

    return SidebarMenu::primaryAction($user);
}

it('offers "Post a project" on Projects pages, but not on the page it leads to', function () {
    $user = User::factory()->create();

    expect(actionOn('jobs.browse', $user))->toBe(['type' => 'link', 'label' => 'Post a project', 'url' => route('jobs.create'), 'icon' => 'plus'])
        ->and(actionOn('engagements.index', $user)['label'])->toBe('Post a project')
        ->and(actionOn('applications.my', $user)['label'])->toBe('Post a project')
        ->and(actionOn('jobs.create', $user))->toBeNull();
});

it('offers "Sell your models" to a non-seller and "Add a model" to an approved seller on Models pages', function () {
    $member = User::factory()->create();
    $seller = User::factory()->create();
    SellerProfile::factory()->approved()->create(['user_id' => $seller->id]);

    expect(actionOn('models.index', $member))->toBe(['type' => 'link', 'label' => 'Sell your models', 'url' => route('seller.index'), 'icon' => 'banknotes'])
        ->and(actionOn('wishlist.index', $member)['label'])->toBe('Sell your models')
        ->and(actionOn('seller.index', $member))->toBeNull();

    expect(actionOn('models.index', $seller))->toBe(['type' => 'link', 'label' => 'Add a model', 'url' => route('seller.models.create'), 'icon' => 'plus'])
        ->and(actionOn('seller.models.index', $seller)['label'])->toBe('Add a model')
        ->and(actionOn('seller.store.edit', $seller)['label'])->toBe('Add a model')
        ->and(actionOn('seller.models.create', $seller))->toBeNull();
});

it('offers a Create menu with both choices on the dashboard and other pages outside the two areas', function () {
    $member = User::factory()->create();
    $seller = User::factory()->create();
    SellerProfile::factory()->approved()->create(['user_id' => $seller->id]);

    foreach (['dashboard', 'notifications.index', 'profile'] as $route) {
        $menu = actionOn($route, $member);
        expect($menu['type'])->toBe('menu')->and($menu['label'])->toBe('Create')
            ->and(collect($menu['items'])->pluck('label')->all())->toBe(['Post a project', 'Sell your models']);
    }

    expect(collect(actionOn('dashboard', $seller)['items'])->pluck('label')->all())->toBe(['Post a project', 'Add a model']);

    $this->actingAs($seller)->get(route('dashboard'))->assertOk()->assertSee('aria-label="Create"', false)->assertSee(route('seller.models.create'), false);
});

it('renders a single button, not a menu, inside a product area', function () {
    $this->actingAs(User::factory()->create())->get(route('jobs.browse'))->assertOk()->assertDontSee('aria-label="Create"', false);
    $this->actingAs(User::factory()->create())->get(route('models.index'))->assertOk()->assertDontSee('aria-label="Create"', false)->assertSee('Sell your models');
});
