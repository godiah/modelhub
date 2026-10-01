<?php

use App\Models\Staff;
use App\Models\StaffActivity;
use App\Models\User;
use App\Notifications\StaffPasswordNotification;
use App\Support\Staff\StaffAccess;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/*
 * Roles and permissions for staff: the catalogue in code, the default roles, editable roles, several roles per person, and
 * the staff and roles pages. Staff accounts are separate from members, so nothing here touches the members' users.
 */

it('creates every permission and the four default roles, with Super admin holding everything', function () {
    StaffAccess::sync();

    expect(Permission::where('guard_name', 'staff')->pluck('name')->sort()->values()->all())->toBe(collect(StaffAccess::permissions())->sort()->values()->all());
    expect(Role::where('guard_name', 'staff')->pluck('name')->sort()->values()->all())->toBe(['Dispute manager', 'Marketplace moderator', 'Super admin', 'Support']);

    $super = Role::findByName('Super admin', 'staff');
    expect($super->permissions)->toHaveCount(count(StaffAccess::permissions()))->and($super->description)->not->toBeEmpty();
    expect(Role::findByName('Support', 'staff')->permissions->pluck('name')->all())->toBe(['view disputes']);
});

it('only lists permissions that something in the app actually checks', function () {
    // Every permission is gated by a route or a check, so a role can never be given one that does nothing
    $routes = collect(app('router')->getRoutes()->getRoutes())->flatMap(fn ($route) => $route->gatherMiddleware())->filter(fn ($m) => is_string($m) && str_starts_with($m, 'can:'))->map(fn ($m) => substr($m, 4))->unique();

    foreach (StaffAccess::permissions() as $permission) {
        expect($routes->contains($permission))->toBeTrue("{$permission} is not enforced by any route");
    }
});

it('never recreates a default role someone deleted when permissions are re-synced, but a full sync does', function () {
    StaffAccess::sync();
    Role::findByName('Support', 'staff')->delete();

    StaffAccess::ensurePermissions();
    expect(Role::where('name', 'Support')->where('guard_name', 'staff')->exists())->toBeFalse();

    StaffAccess::sync();
    expect(Role::where('name', 'Support')->where('guard_name', 'staff')->exists())->toBeTrue();
});

it('does not overwrite a default role that was edited', function () {
    StaffAccess::sync();
    Role::findByName('Support', 'staff')->givePermissionTo('review models');

    StaffAccess::sync();

    expect(Role::findByName('Support', 'staff')->permissions->pluck('name')->sort()->values()->all())->toBe(['review models', 'view disputes']);
});

it('gives a person the combined permissions of all their roles', function () {
    $staff = staffWith('Marketplace moderator', 'Dispute manager');

    foreach (['review models', 'review sellers', 'moderate reviews', 'view disputes', 'resolve disputes'] as $permission) {
        expect($staff->can($permission))->toBeTrue($permission);
    }
    expect($staff->can('manage staff'))->toBeFalse()->and($staff->can('manage roles'))->toBeFalse()->and($staff->can('view audit log'))->toBeFalse();
});

it('lets a Super admin pass any permission, including ones added later that no role holds yet', function () {
    $super = staffWith('Super admin');

    expect($super->can('review models'))->toBeTrue()->and($super->can('a permission added next year'))->toBeTrue();
    expect(staffWith('Support')->can('a permission added next year'))->toBeFalse();
});

/** ---------------------------------------------------------------- staff accounts */
it('keeps the staff pages to people who manage staff', function () {
    $staff = staffWith('Support');

    foreach ([route('admin.staff.index'), route('admin.staff.create'), route('admin.staff.edit', $staff)] as $url) {
        $this->actingAs($staff, 'staff')->get($url)->assertForbidden();
    }
    $this->actingAs(staffWith('Super admin'), 'staff')->get(route('admin.staff.index'))->assertOk()->assertSee('Invite staff');
});

it('invites a new staff member with roles, emails them a link, and logs it', function () {
    Notification::fake();
    $super = staffWith('Super admin');

    $this->actingAs($super, 'staff')->post(route('admin.staff.store'), ['name' => 'Rita Reviewer', 'email' => 'Rita@Example.test', 'roles' => ['Marketplace moderator', 'Support']])->assertRedirect();

    $rita = Staff::where('email', 'rita@example.test')->first();
    expect($rita)->not->toBeNull()->and($rita->roles->pluck('name')->sort()->values()->all())->toBe(['Marketplace moderator', 'Support'])->and($rita->is_active)->toBeTrue();
    Notification::assertSentTo($rita, StaffPasswordNotification::class, fn ($n) => $n->invited === true);
    expect(StaffActivity::where('action', 'staff.invited')->where('staff_id', $super->id)->exists())->toBeTrue();

    // Nobody knows the password they were created with: the only way in is the emailed link
    expect(Auth::guard('staff')->validate(['email' => $rita->email, 'password' => 'password']))->toBeFalse();
});

it('validates an invitation: a real email, not already staff, and only roles that exist', function () {
    $super = staffWith('Super admin');
    $existing = staffWith('Support');
    $this->actingAs($super, 'staff');

    $this->post(route('admin.staff.store'), ['name' => '', 'email' => 'nope'])->assertSessionHasErrors(['name', 'email']);
    $this->post(route('admin.staff.store'), ['name' => 'Copy Cat', 'email' => $existing->email])->assertSessionHasErrors('email');

    $this->post(route('admin.staff.store'), ['name' => 'Sneaky', 'email' => 'sneaky@example.test', 'roles' => ['Does not exist', 'web-admin']])->assertRedirect();
    expect(Staff::where('email', 'sneaky@example.test')->first()->roles)->toBeEmpty();
});

it('changes roles, adding and removing several at once, and logs the change', function () {
    $super = staffWith('Super admin');
    $person = staffWith('Support');

    $this->actingAs($super, 'staff')->patch(route('admin.staff.update', $person), ['roles' => ['Dispute manager', 'Marketplace moderator']])->assertSessionHas('success');
    expect($person->fresh()->roles->pluck('name')->sort()->values()->all())->toBe(['Dispute manager', 'Marketplace moderator']);

    $this->patch(route('admin.staff.update', $person), ['roles' => []])->assertSessionHas('success');
    expect($person->fresh()->roles)->toBeEmpty();

    expect(StaffActivity::where('action', 'staff.roles-changed')->count())->toBe(2);
});

it('can never demote or deactivate the last active Super admin, and not yourself', function () {
    $only = staffWith('Super admin');
    $this->actingAs($only, 'staff');

    $this->patch(route('admin.staff.update', $only), ['roles' => ['Support']])->assertSessionHas('error');
    expect($only->fresh()->isSuperAdmin())->toBeTrue();

    $this->post(route('admin.staff.deactivate', $only))->assertSessionHas('error');
    expect($only->fresh()->is_active)->toBeTrue();

    // With a second active Super admin, the first can step down
    $second = staffWith('Super admin');
    $this->patch(route('admin.staff.update', $only), ['roles' => ['Support']])->assertSessionHas('success');
    expect($only->fresh()->isSuperAdmin())->toBeFalse();

    // ...but a deactivated one does not count as a second
    $this->actingAs($second, 'staff');
    $third = staffWith('Super admin');
    $third->update(['is_active' => false]);
    $this->post(route('admin.staff.deactivate', $second))->assertSessionHas('error');
});

it('deactivates and reactivates an account, keeping its history', function () {
    $super = staffWith('Super admin');
    $person = staffWith('Support');
    StaffActivity::create(['staff_id' => $person->id, 'action' => 'dispute.assigned', 'summary' => 'Took on dispute #1']);

    $this->actingAs($super, 'staff')->post(route('admin.staff.deactivate', $person))->assertSessionHas('success');
    expect($person->fresh()->is_active)->toBeFalse()->and(StaffActivity::where('staff_id', $person->id)->exists())->toBeTrue();

    $this->get(route('admin.staff.index'))->assertDontSee($person->email);
    $this->get(route('admin.staff.index', ['status' => 'inactive']))->assertSee($person->email)->assertSee('Deactivated');

    $this->post(route('admin.staff.reactivate', $person))->assertSessionHas('success');
    expect($person->fresh()->is_active)->toBeTrue();
});

it('sends a new password link on request', function () {
    Notification::fake();
    $person = staffWith('Support');

    $this->actingAs(staffWith('Super admin'), 'staff')->post(route('admin.staff.invite', $person))->assertSessionHas('success');

    Notification::assertSentTo($person, StaffPasswordNotification::class);
});

it('lists and searches staff, with counts on the filters', function () {
    $super = staffWith('Super admin');
    $rita = staffWith('Support');
    $rita->update(['name' => 'Rita Reviewer', 'email' => 'rita@example.test']);
    $gone = staffWith('Support');
    $gone->update(['name' => 'Gone Person', 'is_active' => false]);

    $this->actingAs($super, 'staff')->get(route('admin.staff.index'))->assertOk()->assertSee('Rita Reviewer')->assertDontSee('Gone Person')->assertSee('Never signed in');
    $this->get(route('admin.staff.index', ['q' => 'rita@']))->assertSee('Rita Reviewer')->assertDontSee(route('admin.staff.edit', $super), false);
    $this->get(route('admin.staff.index', ['status' => 'all']))->assertSee('Gone Person');
    $this->get(route('admin.staff.index', ['q' => 'zzzz']))->assertSee('No staff match that search.');
});

/** ---------------------------------------------------------------- roles */
it('keeps the roles pages to people who manage roles', function () {
    $role = Role::findByName('Support', 'staff') ?? null;
    StaffAccess::sync();
    $role = Role::findByName('Support', 'staff');

    foreach ([route('admin.roles.index'), route('admin.roles.create'), route('admin.roles.edit', $role)] as $url) {
        $this->actingAs(staffWith('Support'), 'staff')->get($url)->assertForbidden();
    }
    $this->actingAs(staffWith('Super admin'), 'staff')->get(route('admin.roles.index'))->assertOk()->assertSee('Marketplace moderator')->assertSee('Locked');
});

it('creates a custom role with chosen permissions, which staff can then be given', function () {
    $super = staffWith('Super admin');

    $this->actingAs($super, 'staff')->post(route('admin.roles.store'), ['name' => 'Review lead', 'description' => 'Runs the review queues', 'permissions' => ['review models', 'moderate reviews', 'view audit log']])->assertRedirect(route('admin.roles.index'));

    $role = Role::findByName('Review lead', 'staff');
    expect($role->permissions->pluck('name')->sort()->values()->all())->toBe(['moderate reviews', 'review models', 'view audit log'])->and($role->description)->toBe('Runs the review queues');

    $person = staffWith();
    $this->patch(route('admin.staff.update', $person), ['roles' => ['Review lead']]);
    expect($person->fresh()->can('review models'))->toBeTrue()->and($person->fresh()->can('review sellers'))->toBeFalse();
    expect(StaffActivity::where('action', 'role.created')->exists())->toBeTrue();
});

it('validates a role: a unique name, and only real permissions', function () {
    $this->actingAs(staffWith('Super admin'), 'staff');

    $this->post(route('admin.roles.store'), ['name' => ''])->assertSessionHasErrors('name');
    $this->post(route('admin.roles.store'), ['name' => 'Support'])->assertSessionHasErrors('name');
    $this->post(route('admin.roles.store'), ['name' => 'Odd one', 'permissions' => ['delete everything']])->assertSessionHasErrors('permissions.*');
    expect(Role::where('name', 'Odd one')->exists())->toBeFalse();
});

it('edits a role, and the change reaches the staff who hold it straight away', function () {
    $this->actingAs(staffWith('Super admin'), 'staff');
    $person = staffWith('Support');
    $support = Role::findByName('Support', 'staff');
    expect($person->can('review models'))->toBeFalse();

    $this->patch(route('admin.roles.update', $support), ['name' => 'Support', 'description' => 'Reads disputes and reviews models', 'permissions' => ['view disputes', 'review models']])->assertRedirect();

    expect($person->fresh()->can('review models'))->toBeTrue()->and($support->fresh()->description)->toBe('Reads disputes and reviews models');
    expect(StaffActivity::where('action', 'role.updated')->exists())->toBeTrue();
});

it('shows the Super admin role but never lets it be edited or deleted', function () {
    $super = Role::findByName('Super admin', 'staff') ?: (function () {
        StaffAccess::sync();

        return Role::findByName('Super admin', 'staff');
    })();
    $this->actingAs(staffWith('Super admin'), 'staff');

    $this->get(route('admin.roles.edit', $super))->assertOk()->assertSee('cannot be edited');
    $this->patch(route('admin.roles.update', $super), ['name' => 'Renamed', 'permissions' => []])->assertForbidden();
    $this->delete(route('admin.roles.destroy', $super))->assertForbidden();
    expect($super->fresh()->name)->toBe('Super admin')->and($super->fresh()->permissions)->toHaveCount(count(StaffAccess::permissions()));
});

it('refuses to delete a role that staff still hold, and deletes one that nobody does', function () {
    $this->actingAs(staffWith('Super admin'), 'staff');
    $role = Role::create(['name' => 'Temp role', 'guard_name' => 'staff']);
    $person = staffWith('Temp role');

    $this->delete(route('admin.roles.destroy', $role))->assertSessionHas('error');
    expect(Role::where('name', 'Temp role')->exists())->toBeTrue();

    $person->removeRole('Temp role');
    $this->delete(route('admin.roles.destroy', $role))->assertRedirect(route('admin.roles.index'));
    expect(Role::where('name', 'Temp role')->exists())->toBeFalse()->and(StaffActivity::where('action', 'role.deleted')->exists())->toBeTrue();
});

it('has no member-side roles left, and members cannot hold any', function () {
    expect(Role::where('guard_name', 'web')->count())->toBe(0)->and(Permission::where('guard_name', 'web')->count())->toBe(0);
    expect(method_exists(User::class, 'assignRole'))->toBeFalse()->and(method_exists(User::class, 'hasRole'))->toBeFalse();
});

it('creates the first Super admin from the console, with a strong password, and no built-in account', function () {
    $this->artisan('staff:create-admin', ['email' => 'first@example.test', '--name' => 'First Admin'])
        ->expectsQuestion('Password (at least 12 characters, with letters and numbers)', 'weak')
        ->expectsOutputToContain('password')
        ->assertFailed();
    expect(Staff::count())->toBe(0);

    $this->artisan('staff:create-admin', ['email' => 'first@example.test', '--name' => 'First Admin'])
        ->expectsQuestion('Password (at least 12 characters, with letters and numbers)', 'a-long-passphrase-42')
        ->expectsQuestion('Confirm the password', 'a-long-passphrase-42')
        ->assertSuccessful();

    $first = Staff::where('email', 'first@example.test')->first();
    expect($first->isSuperAdmin())->toBeTrue()->and(StaffActivity::where('action', 'staff.created-from-console')->exists())->toBeTrue();
    expect(Staff::where('email', 'admin@modelhub.com')->exists())->toBeFalse();
});

it('only seeds the demo admin account in local development', function () {
    $this->seed(AdminUserSeeder::class);
    expect(Staff::where('email', 'admin@modelhub.com')->first()?->isSuperAdmin())->toBeTrue(); // the test environment

    Staff::query()->delete();

    try {
        app()->detectEnvironment(fn () => 'production');
        (new AdminUserSeeder)->run();

        expect(Staff::where('email', 'admin@modelhub.com')->exists())->toBeFalse();
    } finally {
        app()->detectEnvironment(fn () => 'testing');
    }
});
