<?php

use App\Models\Staff;
use App\Models\StaffActivity;
use App\Models\User;
use App\Notifications\StaffPasswordNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Volt\Volt;

/*
 * Staff sign in at /admin/login with their own accounts. Members and staff are separate: different tables, different
 * session guards, neither can use the other's sign-in.
 */

function signIn(string $email, string $password = 'password', array $extra = [])
{
    return test()->post(route('admin.login.store'), ['email' => $email, 'password' => $password] + $extra);
}

it('shows the staff sign-in page, apart from the member one', function () {
    $this->get(route('admin.login'))->assertOk()->assertSee('Staff sign in')->assertSee('Staff portal')->assertSee('noindex', false);
});

it('signs a staff member in, records it, and takes them to the portal', function () {
    $staff = staffWith('Support');

    signIn($staff->email)->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs($staff, 'staff');
    $this->assertGuest('web');
    expect($staff->fresh()->last_login_at)->not->toBeNull()
        ->and(StaffActivity::where('action', 'staff.signed-in')->where('staff_id', $staff->id)->exists())->toBeTrue();
});

it('sends staff back to the page they were after', function () {
    $staff = staffWith('Support');

    $this->get(route('admin.disputes.index'))->assertRedirect(route('admin.login'));
    signIn($staff->email)->assertRedirect(route('admin.disputes.index'));
});

it('refuses a wrong password, deactivated accounts and member accounts, with the same message and a log entry', function () {
    $staff = staffWith('Support');
    $gone = staffWith('Support');
    $gone->update(['is_active' => false]);
    User::factory()->create(['email' => 'member@example.test', 'password' => 'password']);

    foreach ([[$staff->email, 'wrong-password'], [$gone->email, 'password'], ['member@example.test', 'password'], ['nobody@example.test', 'password']] as [$email, $password]) {
        signIn($email, $password)->assertSessionHasErrors(['email' => 'These credentials do not match our records.']);
        $this->assertGuest('staff');
    }

    expect(StaffActivity::where('action', 'staff.sign-in-failed')->count())->toBe(4);
});

it('slows down repeated failed sign-ins', function () {
    $staff = staffWith('Support');

    foreach (range(1, 5) as $attempt) {
        signIn($staff->email, 'wrong')->assertSessionHasErrors('email');
    }

    signIn($staff->email, 'password')->assertSessionHasErrors('email');
    expect(session('errors')->first('email'))->toContain('Too many sign-in attempts');
    $this->assertGuest('staff');

    RateLimiter::clear(Str::transliterate(strtolower($staff->email).'|127.0.0.1'));
});

it('does not let a staff account sign in to the member app, nor a member to the staff portal', function () {
    $staff = staffWith('Super admin');
    User::factory()->create(['email' => 'member@example.test', 'password' => 'password']);

    Volt::test('pages.auth.login')->set('form.email', $staff->email)->set('form.password', 'password')->call('login')->assertHasErrors();
    $this->assertGuest('web');

    $this->actingAs(User::find(User::where('email', 'member@example.test')->value('id')))->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
});

it('keeps the two sessions separate: signing out of staff leaves the member signed in', function () {
    $member = User::factory()->create();
    $staff = staffWith('Support');
    $this->actingAs($member)->actingAs($staff, 'staff');

    $this->post(route('admin.logout'))->assertRedirect(route('admin.login'));

    $this->assertGuest('staff');
    $this->assertAuthenticatedAs($member, 'web');
});

it('sends signed-in staff away from the sign-in page', function () {
    $this->actingAs(staffWith('Support'), 'staff')->get(route('admin.login'))->assertRedirect(route('admin.dashboard'));
});

it('signs a deactivated account out on its next request', function () {
    $staff = staffWith('Support');
    $this->actingAs($staff, 'staff')->get(route('admin.dashboard'))->assertOk();

    $staff->update(['is_active' => false]);

    $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
    $this->assertGuest('staff');
});

/** ---------------------------------------------------------------- passwords */
it('sends a reset link only to staff, and gives the same answer either way', function () {
    Notification::fake();
    $staff = staffWith('Support');
    $member = User::factory()->create();

    $this->post(route('admin.password.email'), ['email' => $staff->email])->assertSessionHas('status');
    $this->post(route('admin.password.email'), ['email' => $member->email])->assertSessionHas('status');
    $this->post(route('admin.password.email'), ['email' => 'nobody@example.test'])->assertSessionHas('status');

    Notification::assertSentTo($staff, StaffPasswordNotification::class, fn ($n) => $n->invited === false);
    Notification::assertNotSentTo($member, StaffPasswordNotification::class);
});

it('lets staff set a new password with a valid link, and only a strong one', function () {
    $staff = staffWith('Support');
    $token = Password::broker('staff')->createToken($staff);

    $this->get(route('admin.password.reset', ['token' => $token, 'email' => $staff->email]))->assertOk()->assertSee('Set your password')->assertSee($staff->email);

    foreach (['short1', 'onlyletterspassword', '123456789012'] as $weak) {
        $this->post(route('admin.password.update'), ['token' => $token, 'email' => $staff->email, 'password' => $weak, 'password_confirmation' => $weak])->assertSessionHasErrors('password');
    }

    $this->post(route('admin.password.update'), ['token' => $token, 'email' => $staff->email, 'password' => 'a-strong-passphrase-42', 'password_confirmation' => 'a-strong-passphrase-42'])
        ->assertRedirect(route('admin.login'));

    signIn($staff->email, 'a-strong-passphrase-42')->assertRedirect(route('admin.dashboard'));
    expect(StaffActivity::where('action', 'staff.password-set')->exists())->toBeTrue();
});

it('rejects a reset with a wrong or reused token', function () {
    $staff = staffWith('Support');
    $token = Password::broker('staff')->createToken($staff);
    $good = ['email' => $staff->email, 'password' => 'a-strong-passphrase-42', 'password_confirmation' => 'a-strong-passphrase-42'];

    $this->post(route('admin.password.update'), $good + ['token' => 'wrong'])->assertSessionHasErrors('email');
    $this->post(route('admin.password.update'), $good + ['token' => $token])->assertRedirect(route('admin.login'));
    $this->post(route('admin.password.update'), $good + ['token' => $token])->assertSessionHasErrors('email');
});

it('words invitations and resets differently, and links to the staff portal', function () {
    $staff = Staff::factory()->create(['name' => 'Rita Reviewer']);

    $invite = (new StaffPasswordNotification('tok123', invited: true))->toMail($staff);
    $reset = (new StaffPasswordNotification('tok123'))->toMail($staff);

    expect($invite->subject)->toContain('staff account')->and($reset->subject)->toContain('Reset')
        ->and($invite->actionUrl)->toContain('/admin/reset-password/tok123')->and($invite->actionUrl)->toContain(urlencode($staff->email));
});

it('keeps staff reset tokens apart from the members\' when one person has both accounts', function () {
    $staff = staffWith('Support');
    $member = User::factory()->create(['email' => $staff->email]);

    $staffToken = Password::broker('staff')->createToken($staff);
    $memberToken = Password::broker('users')->createToken($member);

    expect($staffToken)->not->toBe($memberToken)
        ->and(DB::table('staff_password_reset_tokens')->where('email', $staff->email)->exists())->toBeTrue()
        ->and(DB::table('password_reset_tokens')->where('email', $member->email)->exists())->toBeTrue();
});
