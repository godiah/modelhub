<?php

use App\Mail\TwoFactorCode;
use App\Models\Staff;
use App\Models\StaffActivity;
use App\Models\User;
use App\Support\Auth\Totp;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Livewire\Volt\Volt;

/*
 * The sign-in code step: one flow for members (Volt login, /two-factor) and staff (/admin/login, /admin/two-factor). It runs when the
 * account turned it on, or the platform requires it; nobody is signed in until the code is right.
 */

function memberSignIn(User $user, string $password = 'password')
{
    return Volt::test('pages.auth.login')->set('form.email', $user->email)->set('form.password', $password)->call('login');
}

/** The code the last queued email carried. */
function emailedCode(): string
{
    return Mail::queued(TwoFactorCode::class)->last()->code;
}

beforeEach(fn () => Mail::fake());

/** ---------------------------------------------------------------- the maths */
it('produces the codes authenticator apps do (RFC 6238 test vector)', function () {
    $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ'; // "12345678901234567890"

    expect(Totp::at($secret, Totp::step(59)))->toBe('287082')
        ->and(Totp::at($secret, Totp::step(1111111109)))->toBe('081804')
        ->and(Totp::at($secret, Totp::step(2000000000)))->toBe('279037');
});

it('accepts a code one step either side for clock drift, and never one used before', function () {
    $secret = Totp::generateSecret();
    $now = 1_700_000_000;
    $step = Totp::step($now);

    expect(Totp::verify($secret, Totp::at($secret, $step), time: $now))->toBe($step)
        ->and(Totp::verify($secret, Totp::at($secret, $step - 1), time: $now))->toBe($step - 1)
        ->and(Totp::verify($secret, Totp::at($secret, $step + 1), time: $now))->toBe($step + 1)
        ->and(Totp::verify($secret, Totp::at($secret, $step - 2), time: $now))->toBeNull()
        ->and(Totp::verify($secret, Totp::at($secret, $step), after: $step, time: $now))->toBeNull()
        ->and(Totp::verify($secret, 'abcdef', time: $now))->toBeNull()
        ->and(Totp::verify($secret, Totp::at($secret, $step), time: $now + 3600))->toBeNull();
});

it('builds a scannable otpauth link and QR code', function () {
    $uri = Totp::uri('JBSWY3DPEHPK3PXP', 'jo@example.com', 'ModelHub');

    expect($uri)->toStartWith('otpauth://totp/ModelHub%3Ajo%40example.com?')->toContain('secret=JBSWY3DPEHPK3PXP')->toContain('issuer=ModelHub')
        ->and(Totp::qrSvg($uri))->toStartWith('<svg')->not->toContain('<?xml')
        ->and(Totp::formatSecret('JBSWY3DPEHPK3PXP'))->toBe('JBSW Y3DP EHPK 3PXP');
});

/** ---------------------------------------------------------------- members: no code unless required */
it('signs a member straight in when nothing asks for a code', function () {
    memberSignIn($user = User::factory()->create())->assertRedirect(route('dashboard', absolute: false));

    expect(Auth::check())->toBeTrue()->and(auth()->id())->toBe($user->id);
    Mail::assertNothingQueued();
});

it('holds a member at the code step when the platform requires a code, signing them in only once it is right', function () {
    setting('security.otp_members_required', true);
    $user = User::factory()->create();

    memberSignIn($user)->assertRedirect(route('two-factor.challenge'));
    expect(Auth::check())->toBeFalse()->and($user->fresh()->last_login_at)->toBeNull();

    $this->get(route('two-factor.challenge'))->assertOk()->assertSee('Verification code')->assertSee('We emailed a 6-digit code');
    Mail::assertQueued(TwoFactorCode::class, 1);

    $this->post(route('two-factor.verify'), ['code' => emailedCode()])->assertRedirect(route('dashboard'));

    expect(Auth::check())->toBeTrue()->and($user->fresh()->last_login_at)->not->toBeNull();
});

it('keeps a member who gives a wrong code signed out, and tells them', function () {
    setting('security.otp_members_required', true);
    memberSignIn(User::factory()->create());

    $this->from(route('two-factor.challenge'))->post(route('two-factor.verify'), ['code' => '000000'])->assertRedirect(route('two-factor.challenge'))->assertSessionHasErrors('code');
    expect(Auth::check())->toBeFalse();
});

it('renders the member code page as a full, styled document, also after a wrong code', function () {
    setting('security.otp_members_required', true);
    memberSignIn(User::factory()->create());

    $assertDocument = fn ($page) => $page->assertOk()->assertSee('<!DOCTYPE html>', false)->assertSee('<title>ModelHub</title>', false)->assertSee('/build/assets/app-', false)->assertSee('Two-step verification');

    $assertDocument($this->get(route('two-factor.challenge')));

    $this->from(route('two-factor.challenge'))->post(route('two-factor.verify'), ['code' => '000000']);
    $assertDocument($this->get(route('two-factor.challenge')))->assertSee('That code is not right or has expired.');
});

it('still asks a member who turned codes on themselves, whatever the platform says', function () {
    $user = User::factory()->create(['two_factor_enabled' => true]);

    memberSignIn($user)->assertRedirect(route('two-factor.challenge'));
});

it('sends nobody to the code step without a password first', function () {
    $this->get(route('two-factor.challenge'))->assertRedirect(route('login'));
    $this->post(route('two-factor.verify'), ['code' => '123456'])->assertRedirect(route('login'));
});

it('lets the code step lapse after ten minutes', function () {
    setting('security.otp_members_required', true);
    memberSignIn(User::factory()->create());

    $this->travel(11)->minutes();

    $this->get(route('two-factor.challenge'))->assertRedirect(route('login'));
});

it('counts wrong codes per account, so starting over does not hand out new guesses', function () {
    setting('security.otp_members_required', true);
    setting('security.otp_max_attempts', 3);
    $user = User::factory()->create();

    memberSignIn($user);
    foreach (range(1, 3) as $i) {
        $this->post(route('two-factor.verify'), ['code' => '000000'])->assertSessionHasErrors('code');
    }

    // The fourth try is refused even if it is right, and the sign-in is thrown away
    $this->post(route('two-factor.verify'), ['code' => emailedCode()])->assertRedirect(route('login'))->assertSessionHasErrors('code');
    expect(Auth::check())->toBeFalse();

    // Starting over does not help: the lockout is reported at the password step, before any code is asked for
    memberSignIn($user)->assertHasErrors(['form.email'])->assertNoRedirect();
    expect(Auth::check())->toBeFalse();
    $this->get(route('two-factor.challenge'))->assertRedirect(route('login'));
});

it('shows the person why they were sent back to the sign-in after too many wrong codes', function () {
    setting('security.otp_members_required', true);
    setting('security.otp_max_attempts', 3);
    setting('security.signin_lockout_minutes', 20);
    memberSignIn(User::factory()->create());

    foreach (range(1, 3) as $i) {
        $this->post(route('two-factor.verify'), ['code' => '000000']);
    }
    $this->post(route('two-factor.verify'), ['code' => '000000'])->assertRedirect(route('login'));

    $this->get(route('login'))->assertSee('Too many wrong codes. Try signing in again in 20 minutes.');
});

it('lets the code lockout end after the lockout time the platform sets', function () {
    setting('security.otp_members_required', true);
    setting('security.otp_max_attempts', 3);
    setting('security.signin_lockout_minutes', 5);
    $user = User::factory()->create();
    memberSignIn($user);
    foreach (range(1, 3) as $i) {
        $this->post(route('two-factor.verify'), ['code' => '000000']);
    }

    memberSignIn($user)->assertHasErrors('form.email');

    $this->travel(6)->minutes();
    memberSignIn($user)->assertRedirect(route('two-factor.challenge'));
    $this->post(route('two-factor.verify'), ['code' => emailedCode()])->assertRedirect(route('dashboard'));
});

it('tells staff the same, at the password step and on the sign-in page', function () {
    setting('security.otp_staff_required', true);
    setting('security.otp_max_attempts', 3);
    $staff = staffWith('Support');
    $this->post(route('admin.login.store'), ['email' => $staff->email, 'password' => 'password']);
    foreach (range(1, 3) as $i) {
        $this->post(route('admin.two-factor.verify'), ['code' => '000000']);
    }

    $this->post(route('admin.login.store'), ['email' => $staff->email, 'password' => 'password'])->assertSessionHasErrors('email');
    $this->post(route('admin.two-factor.verify'), ['code' => '000000'])->assertRedirect(route('admin.login'));
    $this->get(route('admin.login'))->assertSee('Too many wrong codes');
});

it('lets a member resend the emailed code, but not in quick succession', function () {
    setting('security.otp_members_required', true);
    memberSignIn(User::factory()->create());

    $this->post(route('two-factor.resend'))->assertSessionHas('status', 'A new code is on its way.');
    $this->post(route('two-factor.resend'))->assertSessionHas('status', 'Wait a moment before asking for another code.');

    Mail::assertQueued(TwoFactorCode::class, 2);
});

it('cancels the code step and goes back to the sign-in', function () {
    setting('security.otp_members_required', true);
    memberSignIn(User::factory()->create());

    $this->post(route('two-factor.cancel'))->assertRedirect(route('login'));
    $this->get(route('two-factor.challenge'))->assertRedirect(route('login'));
});

/** ---------------------------------------------------------------- authenticator app and recovery codes */
it('uses the authenticator app for a member who set one up, even when the platform does not require a code', function () {
    $user = User::factory()->create();
    $secret = authenticatorFor($user);

    memberSignIn($user)->assertRedirect(route('two-factor.challenge'));
    $this->get(route('two-factor.challenge'))->assertSee('Authenticator code');
    Mail::assertNothingQueued();

    $this->post(route('two-factor.verify'), ['code' => Totp::at($secret, Totp::step())])->assertRedirect(route('dashboard'));
    expect(Auth::check())->toBeTrue();
});

it('accepts each authenticator code only once', function () {
    $user = User::factory()->create();
    $secret = authenticatorFor($user);
    $code = Totp::at($secret, Totp::step());

    memberSignIn($user);
    $this->post(route('two-factor.verify'), ['code' => $code])->assertRedirect(route('dashboard'));
    Auth::logout();

    memberSignIn($user);
    $this->post(route('two-factor.verify'), ['code' => $code])->assertSessionHasErrors('code');
    expect(Auth::check())->toBeFalse();
});

it('lets a recovery code stand in for the app, once', function () {
    $user = User::factory()->create();
    authenticatorFor($user);
    [$first, $second] = $user->regenerateRecoveryCodes();

    memberSignIn($user);
    $this->post(route('two-factor.verify'), ['code' => strtoupper($first)])->assertRedirect(route('dashboard'));
    expect($user->fresh()->recoveryCodesRemaining())->toBe(User::RECOVERY_CODE_COUNT - 1);
    Auth::logout();

    memberSignIn($user);
    $this->post(route('two-factor.verify'), ['code' => $first])->assertSessionHasErrors('code');
    $this->post(route('two-factor.verify'), ['code' => $second])->assertRedirect(route('dashboard'));
});

it('falls back to emailed codes for someone with an app when the platform stops allowing apps', function () {
    $user = User::factory()->create();
    $secret = authenticatorFor($user);
    setting('security.authenticator_allowed', false);

    memberSignIn($user)->assertRedirect(route('two-factor.challenge'));
    Mail::assertQueued(TwoFactorCode::class, 1);

    $this->post(route('two-factor.verify'), ['code' => Totp::at($secret, Totp::step())])->assertSessionHasErrors('code');
    $this->post(route('two-factor.verify'), ['code' => emailedCode()])->assertRedirect(route('dashboard'));
});

it('never stores the secret or recovery codes in the clear', function () {
    $user = User::factory()->create();
    $secret = authenticatorFor($user);
    [$code] = $user->regenerateRecoveryCodes();
    $raw = DB::table('users')->where('id', $user->id)->first();

    expect($raw->two_factor_secret)->not->toContain($secret)->and($raw->two_factor_recovery_codes)->not->toContain($code)
        ->and($user->fresh()->toArray())->not->toHaveKeys(['two_factor_secret', 'two_factor_recovery_codes', 'two_factor_code', 'session_token']);
});

/** ---------------------------------------------------------------- staff */
it('holds staff at the code step when the platform requires one for staff, and records the sign-in once it is right', function () {
    setting('security.otp_staff_required', true);
    $staff = staffWith('Support');

    $this->post(route('admin.login.store'), ['email' => $staff->email, 'password' => 'password'])->assertRedirect(route('admin.two-factor.challenge'));
    expect(Auth::guard('staff')->check())->toBeFalse()->and(StaffActivity::where('action', 'staff.signed-in')->exists())->toBeFalse();

    $this->get(route('admin.two-factor.challenge'))->assertOk()->assertSee('Two-step verification')->assertSee('Staff portal');

    $this->post(route('admin.two-factor.verify'), ['code' => '000000'])->assertSessionHasErrors('code');
    expect(StaffActivity::where('action', 'staff.sign-in-failed')->where('staff_id', $staff->id)->exists())->toBeTrue();

    $this->post(route('admin.two-factor.verify'), ['code' => emailedCode()])->assertRedirect(route('admin.dashboard'));

    expect(Auth::guard('staff')->check())->toBeTrue()->and($staff->fresh()->last_login_at)->not->toBeNull()
        ->and(StaffActivity::where('action', 'staff.signed-in')->where('staff_id', $staff->id)->exists())->toBeTrue();
});

it('does not require a code from staff when only the member rule is on', function () {
    setting('security.otp_members_required', true);
    $staff = staffWith('Support');

    $this->post(route('admin.login.store'), ['email' => $staff->email, 'password' => 'password'])->assertRedirect(route('admin.dashboard'));
});

it('keeps the staff and member code steps apart', function () {
    setting('security.otp_staff_required', true);
    $staff = staffWith('Support');
    $this->post(route('admin.login.store'), ['email' => $staff->email, 'password' => 'password']);

    // A staff challenge is not a member challenge
    $this->get(route('two-factor.challenge'))->assertRedirect(route('login'));
});

it('uses a staff member\'s authenticator app', function () {
    $staff = staffWith('Support');
    $secret = authenticatorFor($staff);

    $this->post(route('admin.login.store'), ['email' => $staff->email, 'password' => 'password'])->assertRedirect(route('admin.two-factor.challenge'));
    $this->post(route('admin.two-factor.verify'), ['code' => Totp::at($secret, Totp::step())])->assertRedirect(route('admin.dashboard'));
    Mail::assertNothingQueued();
});

/** ---------------------------------------------------------------- trusted devices */
it('lets a device that was trusted skip the code, until it expires or the password changes', function () {
    setting('security.otp_staff_required', true);
    setting('security.trusted_devices_enabled', true);
    setting('security.trusted_device_days', 10);
    $staff = staffWith('Support');

    $this->post(route('admin.login.store'), ['email' => $staff->email, 'password' => 'password']);
    $response = $this->post(route('admin.two-factor.verify'), ['code' => emailedCode(), 'trust_device' => '1'])->assertRedirect(route('admin.dashboard'))->assertCookie('trusted_device_staff');
    $token = $response->getCookie('trusted_device_staff')->getValue();
    expect($staff->trustedDevices()->count())->toBe(1)->and($staff->trustedDevices()->first()->token_hash)->not->toBe($token);
    $this->post(route('admin.logout'));

    $signIn = fn () => $this->withCookie('trusted_device_staff', $token)->post(route('admin.login.store'), ['email' => $staff->email, 'password' => 'password']);

    $signIn()->assertRedirect(route('admin.dashboard'));
    $this->post(route('admin.logout'));

    // Another account's token means nothing, and a password change ends the trust
    $staff->update(['password' => 'a-brand-new-passphrase-1']);
    expect($staff->trustedDevices()->count())->toBe(0);
    $this->withCookie('trusted_device_staff', $token)->post(route('admin.login.store'), ['email' => $staff->email, 'password' => 'a-brand-new-passphrase-1'])->assertRedirect(route('admin.two-factor.challenge'));
});

it('forgets a trusted device after its days are up, or when the platform switches the feature off', function () {
    setting('security.otp_staff_required', true);
    setting('security.trusted_devices_enabled', true);
    setting('security.trusted_device_days', 5);
    $staff = staffWith('Support');

    $this->post(route('admin.login.store'), ['email' => $staff->email, 'password' => 'password']);
    $token = $this->post(route('admin.two-factor.verify'), ['code' => emailedCode(), 'trust_device' => '1'])->getCookie('trusted_device_staff')->getValue();
    $this->post(route('admin.logout'));

    setting('security.trusted_devices_enabled', false);
    $this->withCookie('trusted_device_staff', $token)->post(route('admin.login.store'), ['email' => $staff->email, 'password' => 'password'])->assertRedirect(route('admin.two-factor.challenge'));

    setting('security.trusted_devices_enabled', true);
    $this->travel(6)->days();
    $this->withCookie('trusted_device_staff', $token)->post(route('admin.login.store'), ['email' => $staff->email, 'password' => 'password'])->assertRedirect(route('admin.two-factor.challenge'));
});

it('only offers to trust a device when the platform allows it', function () {
    setting('security.otp_members_required', true);
    memberSignIn(User::factory()->create());
    $this->get(route('two-factor.challenge'))->assertDontSee('Trust this device');

    setting('security.trusted_devices_enabled', true);
    $this->get(route('two-factor.challenge'))->assertSee('Trust this device for 30 days');
});

/** ---------------------------------------------------------------- sign-in throttling */
it('locks a member out after the number of failed sign-ins the platform allows', function () {
    setting('security.signin_max_attempts', 3);
    $user = User::factory()->create();

    foreach (range(1, 3) as $i) {
        memberSignIn($user, 'wrong')->assertHasErrors();
    }

    memberSignIn($user)->assertHasErrors('form.email');
    expect(Auth::check())->toBeFalse();
});

it('locks staff out the same way, for as long as the platform says', function () {
    setting('security.signin_max_attempts', 3);
    $staff = staffWith('Support');

    foreach (range(1, 3) as $i) {
        $this->post(route('admin.login.store'), ['email' => $staff->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
    }

    $this->post(route('admin.login.store'), ['email' => $staff->email, 'password' => 'password'])->assertSessionHasErrors('email');
    expect(Auth::guard('staff')->check())->toBeFalse();

    $this->travel(2)->minutes();
    $this->post(route('admin.login.store'), ['email' => $staff->email, 'password' => 'password'])->assertRedirect(route('admin.dashboard'));
});

it('does not tell a suspended member apart from a wrong password until the password is right', function () {
    $user = User::factory()->create(['suspended_at' => now()]);

    memberSignIn($user, 'wrong')->assertHasErrors(['form.email' => 'These credentials do not match our records.']);
    memberSignIn($user)->assertHasErrors('form.email');
    expect(Auth::check())->toBeFalse();
});
