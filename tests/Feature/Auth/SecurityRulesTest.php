<?php

use App\Models\User;
use App\Support\Auth\PasswordPolicy;
use App\Support\Auth\SessionRules;
use App\Support\Auth\SignInChallenge;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/*
 * The platform's session rules (idle timeout, longest session, one active session) and password policy, each set by a Super admin.
 */

/** ---------------------------------------------------------------- idle timeout */
it('signs a member out after the idle time the platform sets, and counts any request as activity', function () {
    setting('security.member_idle_minutes', 10);
    $this->actingAs(User::factory()->create());

    $this->get(route('dashboard'))->assertOk();
    $this->travel(8)->minutes();
    $this->get(route('dashboard'))->assertOk();
    $this->travel(8)->minutes(); // 16 minutes since the first request, but only 8 since the last
    $this->get(route('dashboard'))->assertOk();

    $this->travel(11)->minutes();
    $this->get(route('dashboard'))->assertRedirect(route('login'))->assertSessionHas('status', 'You were signed out because you were inactive for a while.');
    expect(Auth::check())->toBeFalse();
});

it('sets the idle time for staff separately from members', function () {
    setting('security.staff_idle_minutes', 5);
    setting('security.member_idle_minutes', 60);
    $this->actingAs(staffWith('Support'), 'staff')->get(route('admin.dashboard'))->assertOk();

    $this->travel(6)->minutes();
    $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'))->assertSessionHas('status');
    expect(Auth::guard('staff')->check())->toBeFalse();

    $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertOk();
    $this->travel(6)->minutes();
    $this->get(route('dashboard'))->assertOk();
});

it('keeps sessions for the longest idle time any audience is allowed, so the framework never cuts one short', function () {
    setting('security.member_idle_minutes', 30);
    setting('security.staff_idle_minutes', 500);

    $this->get(route('login'));

    expect(config('session.lifetime'))->toBe(500);
});

/** ---------------------------------------------------------------- longest session */
it('ends a session once it is older than the limit, however active it has been', function () {
    setting('security.member_max_hours', 1);
    $this->actingAs(User::factory()->create());
    $this->get(route('dashboard'))->assertOk();

    $this->travel(40)->minutes();
    $this->get(route('dashboard'))->assertOk();

    $this->travel(30)->minutes();
    $this->get(route('dashboard'))->assertRedirect(route('login'))->assertSessionHas('status', 'Your session reached its time limit. Please sign in again.');
});

it('does not honour "keep me signed in" while there is a session limit', function () {
    expect(SessionRules::allowsRemember('web'))->toBeTrue()->and(SessionRules::allowsRemember('staff'))->toBeTrue();

    setting('security.member_max_hours', 8);

    expect(SessionRules::allowsRemember('web'))->toBeFalse()->and(SessionRules::allowsRemember('staff'))->toBeTrue();
});

/** ---------------------------------------------------------------- one active session */
it('signs a member out when the account signs in somewhere else, if the platform wants one session', function () {
    setting('security.member_single_session', true);
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('dashboard'))->assertOk();

    // Another device signs in: the account now holds a different session token
    $user->forceFill(['session_token' => 'another-device'])->save();

    $this->get(route('dashboard'))->assertRedirect(route('login'))->assertSessionHas('status', 'You were signed out because this account signed in on another device.');
    expect(Auth::check())->toBeFalse();
});

it('leaves other sessions alone while the platform allows several', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('dashboard'))->assertOk();
    $user->forceFill(['session_token' => 'another-device'])->save();

    $this->get(route('dashboard'))->assertOk();
});

it('gives every sign-in its own session token, so the newest one wins', function () {
    $user = User::factory()->create();

    SignInChallenge::signIn('web', $user, false);
    $first = $user->fresh()->session_token;
    SignInChallenge::signIn('web', $user, false);

    expect($first)->not->toBeNull()->and($user->fresh()->session_token)->not->toBe($first);
});

it('applies the one-session rule to staff on their own setting', function () {
    setting('security.staff_single_session', true);
    $staff = staffWith('Support');
    $this->post(route('admin.login.store'), ['email' => $staff->email, 'password' => 'password'])->assertRedirect(route('admin.dashboard'));
    $this->get(route('admin.dashboard'))->assertOk();

    $staff->forceFill(['session_token' => 'another-device'])->save();
    Auth::guard('staff')->forgetUser(); // a real request loads the account afresh

    $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
});

it('answers a background request from an ended session with a page-expired status instead of a redirect', function () {
    setting('security.member_idle_minutes', 5);
    $this->actingAs(User::factory()->create())->get(route('dashboard'));
    $this->travel(6)->minutes();

    $this->withHeader('X-Livewire', 'true')->get(route('dashboard'))->assertStatus(419);
});

/** ---------------------------------------------------------------- password policy */
it('applies the platform password rules to new passwords', function () {
    $valid = fn (string $password, bool $staff = false) => Validator::make(['password' => $password], ['password' => [PasswordPolicy::rule($staff)]])->passes();

    expect($valid('alllowercase'))->toBeTrue()->and($valid('short'))->toBeFalse();

    setting('security.password_min_length', 12);
    setting('security.password_mixed_case', true);
    setting('security.password_number', true);
    setting('security.password_symbol', true);

    expect($valid('alllowercase'))->toBeFalse()
        ->and($valid('Shortie1!'))->toBeFalse()
        ->and($valid('NoNumbersHere!!'))->toBeFalse()
        ->and($valid('NoSymbolsHere123'))->toBeFalse()
        ->and($valid('Correct-Horse-9-Staple'))->toBeTrue();
});

it('shows the same rules wherever a member or staff member chooses a password', function () {
    setting('security.password_min_length', 14);

    $validator = fn () => Validator::make(['password' => 'twelve-chars'], ['password' => ['required', Password::defaults()]]);

    expect($validator()->passes())->toBeFalse()->and(PasswordPolicy::describe())->toBe('At least 14 characters.')
        ->and(PasswordPolicy::describe(staff: true))->toBe('At least 14 characters, letters and a number.');
});

it('never lets a staff password drop below 12 characters, whatever the platform says', function () {
    $valid = fn (string $password) => Validator::make(['password' => $password], ['password' => [PasswordPolicy::rule(staff: true)]])->passes();

    expect($valid('onlyten123'))->toBeFalse()->and($valid('twelve-chars1'))->toBeTrue()->and(PasswordPolicy::describe(staff: true))->toBe('At least 12 characters, letters and a number.');
});

it('puts the platform rules on the registration and staff password pages', function () {
    setting('security.password_symbol', true);

    $this->actingAs(staffWith('Support'), 'staff')->put(route('admin.account.password'), ['current_password' => 'password', 'password' => 'NoSymbolsHere12345', 'password_confirmation' => 'NoSymbolsHere12345'])->assertSessionHasErrors('password');
    $this->get(route('admin.account.edit'))->assertSee('At least 12 characters, letters, a number and a symbol.');
});
