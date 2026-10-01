<?php

use App\Models\SellerProfile;
use App\Models\User;
use App\Support\Avatars;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Volt\Volt;

/*
 * Avatars replace initials and uploaded pictures everywhere: a fixed catalogue, a random one for every new member and store,
 * a picker for each, and nothing in the code that draws initials.
 */

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/** ---------------------------------------------------------------- the catalogue */
it('has a deep catalogue of unique styles and seeds for people and for stores', function () {
    $people = config('avatars.people');
    $stores = config('avatars.stores');

    expect(count($people['styles']))->toBeGreaterThanOrEqual(9)->and(count($people['seeds']))->toBeGreaterThanOrEqual(48)
        ->and(count($stores['styles']))->toBeGreaterThanOrEqual(6)->and(count($stores['seeds']))->toBeGreaterThanOrEqual(48);
    expect(count(Avatars::keys('people')))->toBeGreaterThanOrEqual(430)->and(count(Avatars::keys('stores')))->toBeGreaterThanOrEqual(280);

    foreach ([$people, $stores] as $kind) {
        expect(array_unique($kind['seeds']))->toHaveCount(count($kind['seeds']))
            ->and(array_unique(array_column($kind['styles'], 'key')))->toHaveCount(count($kind['styles']));
    }

    // Stores are brands, so their styles are not the people ones
    expect(array_intersect(array_column($people['styles'], 'key'), array_column($stores['styles'], 'key')))->toBe([]);
});

it('has an SVG file on disk for every choice in the catalogue, and none left over', function () {
    foreach (['people', 'stores'] as $kind) {
        foreach (Avatars::keys($kind) as $key) {
            expect(public_path("images/avatars/{$kind}/{$key}.svg"))->toBeFile();
        }

        $onDisk = glob(public_path("images/avatars/{$kind}/*/*.svg"));
        expect($onDisk)->toHaveCount(count(Avatars::keys($kind)));
    }
});

it('only accepts a key that is in the catalogue of that kind', function () {
    expect(Avatars::isValid('people', 'notionists/amara'))->toBeTrue()
        ->and(Avatars::isValid('stores', 'bottts/forge'))->toBeTrue()
        ->and(Avatars::isValid('people', 'bottts/forge'))->toBeFalse()
        ->and(Avatars::isValid('stores', 'notionists/amara'))->toBeFalse()
        ->and(Avatars::isValid('people', 'notionists/not-a-seed'))->toBeFalse()
        ->and(Avatars::isValid('people', 'notionists'))->toBeFalse()
        ->and(Avatars::isValid('people', 'notionists/amara/extra'))->toBeFalse()
        ->and(Avatars::isValid('people', '../../.env'))->toBeFalse()
        ->and(Avatars::isValid('people', null))->toBeFalse()
        ->and(Avatars::isValid('people', ['notionists/amara']))->toBeFalse();
});

it('picks random valid avatars, varied across the catalogue', function () {
    $picks = collect(range(1, 80))->map(fn () => Avatars::random('people'));

    expect($picks->every(fn ($key) => Avatars::isValid('people', $key)))->toBeTrue()
        ->and($picks->unique()->count())->toBeGreaterThan(30)
        ->and(collect(range(1, 40))->map(fn () => Avatars::random('stores'))->every(fn ($key) => Avatars::isValid('stores', $key)))->toBeTrue();
});

it('falls back to the same valid avatar for the same value, and never to a blank', function () {
    expect(Avatars::fallback('people', 42))->toBe(Avatars::fallback('people', 42))
        ->and(Avatars::isValid('people', Avatars::fallback('people', 42)))->toBeTrue()
        ->and(Avatars::fallback('people', 1))->not->toBe(Avatars::fallback('people', 2));

    expect(Avatars::url('people', null, 7))->toBe(asset('images/avatars/people/'.Avatars::fallback('people', 7).'.svg'))
        ->and(Avatars::url('people', 'garbage', 7))->toBe(Avatars::url('people', null, 7))
        ->and(Avatars::url('stores', 'bottts/forge'))->toBe(asset('images/avatars/stores/bottts/forge.svg'));
});

/** ---------------------------------------------------------------- everyone starts with one */
it('gives every new member and store a random valid avatar, and keeps one that is set', function () {
    $user = User::factory()->create();
    $store = SellerProfile::factory()->create();

    expect(Avatars::isValid('people', $user->avatar))->toBeTrue()
        ->and(Avatars::isValid('stores', $store->avatar))->toBeTrue()
        ->and($user->avatarUrl())->toBe(asset("images/avatars/people/{$user->avatar}.svg"))
        ->and($store->avatarUrl())->toBe(asset("images/avatars/stores/{$store->avatar}.svg"));

    expect(User::factory()->create(['avatar' => 'lorelei/mia'])->avatar)->toBe('lorelei/mia')
        ->and(SellerProfile::factory()->create(['avatar' => 'rings/nova'])->avatar)->toBe('rings/nova');

    expect(collect(range(1, 25))->map(fn () => User::factory()->create()->avatar)->unique()->count())->toBeGreaterThan(10);
});

it('registers a new member with an avatar already', function () {
    Volt::test('pages.auth.register')
        ->set('name', 'Wanjiru Kamau')->set('email', 'wanjiru@example.test')
        ->set('password', 'password')->set('password_confirmation', 'password')
        ->call('register')->assertHasNoErrors();

    expect(Avatars::isValid('people', User::where('email', 'wanjiru@example.test')->first()?->avatar))->toBeTrue();
});

it('refuses to quietly show the wrong avatar when a query leaves the column out', function () {
    $user = User::factory()->create();

    expect(fn () => User::select('id', 'name')->find($user->id)->avatarUrl())->toThrow(LogicException::class, 'avatar column was not selected');
});

/** ---------------------------------------------------------------- a member picks theirs */
it('lets a member pick an avatar from the people catalogue', function () {
    $user = User::factory()->create(['avatar' => 'notionists/felix']);

    $this->actingAs($user)->patch(route('profile.avatar.update'), ['avatar' => 'open-peeps/zuri'])
        ->assertRedirect(route('profile').'#profile')->assertSessionHas('success');

    expect($user->fresh()->avatar)->toBe('open-peeps/zuri');
});

it('refuses an avatar outside the people catalogue, and needs a signed-in member', function () {
    $user = User::factory()->create(['avatar' => 'notionists/felix']);

    foreach (['bottts/forge', 'nonsense', '', '../x/y'] as $bad) {
        $this->actingAs($user)->patch(route('profile.avatar.update'), ['avatar' => $bad])->assertSessionHasErrors('avatar');
    }
    expect($user->fresh()->avatar)->toBe('notionists/felix');

    auth()->logout();
    $this->patch(route('profile.avatar.update'), ['avatar' => 'open-peeps/zuri'])->assertRedirect(route('login'));
});

it('shows the picker on the profile page with the member\'s avatar, and no photo upload', function () {
    $user = User::factory()->create(['avatar' => 'lorelei/mia']);

    $this->actingAs($user)->get(route('profile'))->assertOk()
        ->assertSee(asset('images/avatars/people/lorelei/mia.svg'), false)
        ->assertSee('Choose an avatar')->assertSee('Surprise me')->assertSee(route('profile.avatar.update'), false)
        ->assertDontSee('Upload new')->assertDontSee('id="avatar-upload"', false);
});

it('shows a member\'s avatar picture in the menu and on the dashboard instead of letters', function () {
    $user = User::factory()->create(['name' => 'Kevin Mwangi', 'avatar' => 'avataaars/leo']);
    $url = asset('images/avatars/people/avataaars/leo.svg');

    $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee($url, false)->assertDontSee('>KM<', false);
});

/** ---------------------------------------------------------------- no initials, anywhere */
it('has no code that draws initials, in the app, the views or the routes', function () {
    $offenders = collect([app_path(), resource_path('views'), base_path('routes'), resource_path('js')])
        ->flatMap(fn ($dir) => is_dir($dir) ? collect(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS))) : [])
        ->filter(fn ($file) => $file->isFile() && preg_match('/\.(php|js|vue)$/', $file->getFilename()))
        ->filter(fn ($file) => preg_match('/getInitials|\binitials\b/i', file_get_contents($file->getPathname())))
        ->map(fn ($file) => str_replace(base_path().'/', '', $file->getPathname()))
        ->values()->all();

    expect($offenders)->toBe([]);
});

it('does not fall back to an external avatar service', function () {
    $hits = collect([app_path(), resource_path('views')])
        ->flatMap(fn ($dir) => collect(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS))))
        ->filter(fn ($file) => $file->isFile() && preg_match('/ui-avatars|gravatar|dicebear\.com/i', file_get_contents($file->getPathname())))
        ->count();

    expect($hits)->toBe(0);
});
