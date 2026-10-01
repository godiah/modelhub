<?php

use App\Models\Staff;
use App\Models\StaffActivity;
use App\Models\User;
use App\Notifications\TwoFactorResetNotification;
use App\Support\Auth\Totp;
use Illuminate\Support\Facades\Notification;

/*
 * Setting up, replacing and removing an authenticator app (member profile, staff account page), and staff resetting someone
 * who lost their phone.
 */

/** ---------------------------------------------------------------- members */
it('walks a member through setting up an authenticator app', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('profile'))->assertOk()->assertSee('Authenticator app')->assertSee('Not set up');

    // Step 1 needs the password
    $this->post(route('authenticator.start'), [])->assertSessionHasErrors('password');
    $this->post(route('authenticator.start'), ['password' => 'wrong'])->assertSessionHasErrors('password');
    expect($user->fresh()->two_factor_secret)->toBeNull();

    $this->post(route('authenticator.start'), ['password' => 'password'])->assertRedirect(route('profile').'#security');
    $user->refresh();
    expect($user->authenticatorSetupPending())->toBeTrue()->and($user->hasAuthenticator())->toBeFalse()->and($user->requiresSecondFactor())->toBeFalse();

    // Step 2 shows the QR code and the key to type by hand
    $this->get(route('profile'))->assertSee('Scan this with your app')->assertSee('<svg', false)->assertSee(Totp::formatSecret($user->two_factor_secret));

    $this->post(route('authenticator.confirm'), ['code' => '000000'])->assertSessionHasErrors('code');
    expect($user->fresh()->hasAuthenticator())->toBeFalse();

    $response = $this->post(route('authenticator.confirm'), ['code' => Totp::at($user->two_factor_secret, Totp::step())])->assertSessionHasNoErrors();
    $codes = session('recovery_codes');
    expect($codes)->toHaveCount(User::RECOVERY_CODE_COUNT)->and($user->fresh()->hasAuthenticator())->toBeTrue()->and($user->fresh()->requiresSecondFactor())->toBeTrue();

    $this->withSession(['recovery_codes' => $codes])->get(route('profile'))->assertSee('Save your recovery codes')->assertSee($codes[0])->assertSee('8 recovery codes left');
    $this->get(route('profile'))->assertDontSee('Save your recovery codes')->assertSee('Authenticator app is on');
});

it('lets a member give up a setup they did not finish', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->post(route('authenticator.start'), ['password' => 'password']);

    $this->delete(route('authenticator.cancel'))->assertRedirect();

    expect($user->fresh()->two_factor_secret)->toBeNull();
});

it('will not start a second setup over a working app', function () {
    $user = User::factory()->create();
    $secret = authenticatorFor($user);

    $this->actingAs($user)->post(route('authenticator.start'), ['password' => 'password'])->assertSessionHas('error');

    expect($user->fresh()->two_factor_secret)->toBe($secret);
});

it('replaces the recovery codes only for someone who confirms their password', function () {
    $user = User::factory()->create();
    authenticatorFor($user);
    [$old] = $user->regenerateRecoveryCodes();
    $this->actingAs($user);

    $this->post(route('authenticator.recovery'), ['password' => 'wrong'])->assertSessionHasErrors('password');
    expect($user->fresh()->consumeRecoveryCode($old))->toBeTrue();
    $user->regenerateRecoveryCodes();

    $this->post(route('authenticator.recovery'), ['password' => 'password']);
    expect(session('recovery_codes'))->toHaveCount(8);
    $user->refresh();
    expect($user->consumeRecoveryCode($old))->toBeFalse();
});

it('removes the app for someone who confirms their password', function () {
    $user = User::factory()->create();
    authenticatorFor($user);
    $this->actingAs($user);

    $this->delete(route('authenticator.destroy'), ['password' => 'wrong'])->assertSessionHasErrors('password');
    expect($user->fresh()->hasAuthenticator())->toBeTrue();

    $this->delete(route('authenticator.destroy'), ['password' => 'password'])->assertRedirect();
    $user->refresh();
    expect($user->hasAuthenticator())->toBeFalse()->and($user->two_factor_secret)->toBeNull()->and($user->recoveryCodesRemaining())->toBe(0)->and($user->requiresSecondFactor())->toBeFalse();
});

it('hides the app setup when the platform switched authenticator apps off, but lets those who have one remove it', function () {
    setting('security.authenticator_allowed', false);
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('profile'))->assertDontSee('Scan this with your app')->assertDontSee('Authenticator app');
    $this->post(route('authenticator.start'), ['password' => 'password'])->assertForbidden();

    authenticatorFor($user);
    $this->get(route('profile'))->assertSee('sign-in codes are emailed to you');
});

it('tells members on their security tab when the platform requires a code', function () {
    setting('security.otp_members_required', true);

    $this->actingAs(User::factory()->create())->get(route('profile'))->assertSee('A sign-in code is required for every member');
});

it('needs a signed-in member for every step', function () {
    $this->post(route('authenticator.start'), ['password' => 'password'])->assertRedirect(route('login'));
    $this->post(route('authenticator.confirm'), ['code' => '123456'])->assertRedirect(route('login'));
    $this->delete(route('authenticator.destroy'), ['password' => 'password'])->assertRedirect(route('login'));
});

/** ---------------------------------------------------------------- staff */
it('lets staff set up an app from their account page, and logs it', function () {
    $staff = staffWith('Support');
    $this->actingAs($staff, 'staff');

    $this->get(route('admin.account.edit'))->assertOk()->assertSee('Authenticator app')->assertSee('Not set up');
    $this->post(route('admin.account.authenticator.start'), ['password' => 'password'])->assertRedirect(route('admin.account.edit'));
    $staff->refresh();
    $this->get(route('admin.account.edit'))->assertSee('Scan this with your app')->assertSee('<svg', false);

    $this->post(route('admin.account.authenticator.confirm'), ['code' => Totp::at($staff->two_factor_secret, Totp::step())])->assertSessionHasNoErrors();
    expect(session('recovery_codes'))->toHaveCount(8)->and($staff->fresh()->hasAuthenticator())->toBeTrue()
        ->and(StaffActivity::where('action', 'staff.authenticator-enabled')->where('staff_id', $staff->id)->exists())->toBeTrue();

    $this->delete(route('admin.account.authenticator.destroy'), ['password' => 'password'])->assertRedirect(route('admin.account.edit'));
    expect($staff->fresh()->hasAuthenticator())->toBeFalse()->and(StaffActivity::where('action', 'staff.authenticator-removed')->exists())->toBeTrue();
});

it('asks for the staff password, not a member\'s', function () {
    $this->actingAs(staffWith('Support'), 'staff')->post(route('admin.account.authenticator.start'), ['password' => 'wrong'])->assertSessionHasErrors('password');
});

it('says on the staff account page when a code is required of all staff', function () {
    setting('security.otp_staff_required', true);

    $this->actingAs(staffWith('Support'), 'staff')->get(route('admin.account.edit'))->assertSee('A sign-in code is required for all staff');
});

/** ---------------------------------------------------------------- reset by staff */
it('lets staff who manage members reset a member who lost their phone, and emails the member', function () {
    Notification::fake();
    $member = User::factory()->create(['two_factor_enabled' => true]);
    authenticatorFor($member);
    $member->trustedDevices()->create(['token_hash' => str_repeat('a', 64), 'expires_at' => now()->addDay()]);
    $admin = staffWith('Super admin');

    $this->actingAs($admin, 'staff')->get(route('admin.members.show', $member))->assertSee('Reset two-step sign-in');
    $this->post(route('admin.members.two-factor-reset', $member))->assertRedirect();

    $member->refresh();
    expect($member->hasAuthenticator())->toBeFalse()->and($member->hasTwoFactorEnabled())->toBeFalse()->and($member->trustedDevices()->count())->toBe(0)
        ->and(StaffActivity::where('action', 'member.two-factor-reset')->where('staff_id', $admin->id)->exists())->toBeTrue();
    Notification::assertSentTo($member, TwoFactorResetNotification::class);
});

it('leaves the reset button off members who have no two-step sign-in', function () {
    $this->actingAs(staffWith('Super admin'), 'staff')->get(route('admin.members.show', User::factory()->create()))->assertDontSee('Reset two-step sign-in');
});

it('keeps the member reset to staff who manage members', function () {
    $member = User::factory()->create();
    authenticatorFor($member);

    foreach (['Auditor', 'Marketplace moderator', 'Dispute manager'] as $role) {
        $this->actingAs(staffWith($role), 'staff')->post(route('admin.members.two-factor-reset', $member))->assertForbidden();
    }

    expect($member->fresh()->hasAuthenticator())->toBeTrue();
});

it('lets staff who manage staff reset a colleague, but not themselves', function () {
    Notification::fake();
    $colleague = staffWith('Support');
    authenticatorFor($colleague);
    $admin = staffWith('Super admin');
    authenticatorFor($admin);

    $this->actingAs($admin, 'staff')->get(route('admin.staff.edit', $colleague))->assertSee('Reset two-step sign-in');
    $this->get(route('admin.staff.edit', $admin))->assertDontSee('Reset two-step sign-in');

    $this->post(route('admin.staff.two-factor-reset', $admin))->assertForbidden();
    $this->post(route('admin.staff.two-factor-reset', $colleague))->assertRedirect();

    expect($colleague->fresh()->hasAuthenticator())->toBeFalse()->and($admin->fresh()->hasAuthenticator())->toBeTrue()
        ->and(StaffActivity::where('action', 'staff.two-factor-reset')->exists())->toBeTrue();
    Notification::assertSentTo($colleague, TwoFactorResetNotification::class);

    $this->actingAs(staffWith('Support'), 'staff')->post(route('admin.staff.two-factor-reset', $colleague))->assertForbidden();
});

it('sends the reset email to the right portal', function () {
    $member = User::factory()->create();
    $mail = (new TwoFactorResetNotification(route('profile').'#security'))->toMail($member);

    expect($mail->actionUrl)->toBe(route('profile').'#security')->and($mail->subject)->toBe('Your two-step sign-in was reset');
});
