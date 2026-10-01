<?php

use App\Models\PlatformSetting;
use App\Models\StaffActivity;
use App\Support\Settings\PlatformSettings;
use App\Support\Settings\SecuritySettings;

/*
 * Platform settings: declared in code with defaults, changed only by Super admins, cached, and every change logged.
 */

/** A full, valid form submission, with overrides on top. */
function securityForm(array $overrides = []): array
{
    $form = collect(SecuritySettings::definitions())->mapWithKeys(fn ($definition, $key) => [str($key)->after('security.')->toString() => $definition['type'] === 'bool' ? ($definition['default'] ? '1' : '0') : $definition['default']])->all();

    return ['settings' => $overrides + $form];
}

it('falls back to the defaults declared in code until something is changed', function () {
    expect(PlatformSettings::bool('security.otp_members_required'))->toBeFalse()
        ->and(PlatformSettings::bool('security.authenticator_allowed'))->toBeTrue()
        ->and(PlatformSettings::int('security.password_min_length'))->toBe(8)
        ->and(PlatformSettings::int('security.member_idle_minutes'))->toBe(120)
        ->and(PlatformSetting::count())->toBe(0);
});

it('rejects an unknown setting', function () {
    PlatformSettings::get('security.nonsense');
})->throws(InvalidArgumentException::class);

it('is for Super admins only, with no menu entry for anyone else', function () {
    foreach (['Platform manager', 'Auditor', 'Support'] as $role) {
        $this->actingAs(staffWith($role), 'staff')->get(route('admin.settings.security'))->assertForbidden();
        $this->actingAs(staffWith($role), 'staff')->patch(route('admin.settings.security.update'), securityForm())->assertForbidden();
    }

    $this->actingAs(staffWith('Platform manager'), 'staff')->get(route('admin.dashboard'))->assertDontSee(route('admin.settings.security'), false);
    $this->actingAs(staffWith('Super admin'), 'staff')->get(route('admin.dashboard'))->assertSee(route('admin.settings.security'), false);
    $this->get(route('admin.settings.index'))->assertRedirect('/admin/settings/security');
    auth('staff')->logout();
    $this->get(route('admin.settings.security'))->assertRedirect(route('admin.login'));
});

it('shows every security setting grouped by what it does', function () {
    $page = $this->actingAs(staffWith('Super admin'), 'staff')->get(route('admin.settings.security'))->assertOk()->assertSee('Platform settings');

    foreach (SecuritySettings::sections() as $section) {
        $page->assertSee($section['title']);
    }
    foreach (SecuritySettings::definitions() as $key => $definition) {
        $page->assertSee(e($definition['label']), false)->assertSee('name="settings['.str($key)->after('security.').']"', false);
    }
});

it('saves changes, reads them back, and logs the old and new value', function () {
    $admin = staffWith('Super admin');

    $this->actingAs($admin, 'staff')->patch(route('admin.settings.security.update'), securityForm(['otp_members_required' => '1', 'password_min_length' => 12, 'trusted_device_days' => 14]))->assertRedirect();

    expect(PlatformSettings::bool('security.otp_members_required'))->toBeTrue()
        ->and(PlatformSettings::int('security.password_min_length'))->toBe(12)
        ->and(PlatformSettings::int('security.trusted_device_days'))->toBe(14)
        ->and(PlatformSettings::bool('security.otp_staff_required'))->toBeFalse()
        ->and(PlatformSetting::pluck('key')->sort()->values()->all())->toBe(['security.otp_members_required', 'security.password_min_length', 'security.trusted_device_days']);

    $entry = StaffActivity::where('action', 'settings.security-updated')->sole();
    expect($entry->staff_id)->toBe($admin->id)->and($entry->summary)->toContain('Changed 3 security settings')->toContain('Require a sign-in code for members (off to on)')->toContain('Minimum length (8 to 12)')
        ->and($entry->details['changes']['security.password_min_length'])->toMatchArray(['from' => 8, 'to' => 12]);
});

it('treats an unticked switch as off, and writes nothing when nothing changed', function () {
    $admin = staffWith('Super admin');
    $this->actingAs($admin, 'staff')->patch(route('admin.settings.security.update'), securityForm(['password_symbol' => '1']));
    expect(PlatformSettings::bool('security.password_symbol'))->toBeTrue();

    $form = securityForm();
    unset($form['settings']['password_symbol']);
    $this->patch(route('admin.settings.security.update'), $form);
    expect(PlatformSettings::bool('security.password_symbol'))->toBeFalse();

    $before = StaffActivity::count();
    $this->patch(route('admin.settings.security.update'), $form)->assertRedirect();
    expect(StaffActivity::count())->toBe($before);
});

it('keeps each number within its limits', function () {
    $this->actingAs(staffWith('Super admin'), 'staff');

    foreach (['password_min_length' => 4, 'signin_max_attempts' => 1, 'otp_max_attempts' => 50, 'trusted_device_days' => 0, 'member_idle_minutes' => 'abc', 'member_max_hours' => -1] as $field => $bad) {
        $this->patch(route('admin.settings.security.update'), securityForm([$field => $bad]))->assertSessionHasErrors("settings.{$field}");
    }

    expect(PlatformSetting::count())->toBe(0);
});

it('lets the activity log be narrowed to settings changes', function () {
    $admin = staffWith('Super admin');
    $this->actingAs($admin, 'staff')->patch(route('admin.settings.security.update'), securityForm(['signin_max_attempts' => 8]));

    $this->get(route('admin.activity.index', ['area' => 'settings']))->assertOk()->assertSee('Failed sign-ins allowed (5 to 8)');
});
