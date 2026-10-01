<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Support\Auth\SessionRules;
use App\Support\Auth\SignInChallenge;
use App\Support\Settings\PlatformSettings;
use App\Support\Staff\StaffAccess;
use App\Support\Staff\StaffAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Staff sign in and out, at /admin/login. Separate from members: different accounts, different session guard. */
class StaffLoginController extends Controller
{
    public function create()
    {
        return view('staff.auth.login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate(['email' => ['required', 'string', 'email'], 'password' => ['required', 'string']]);
        $key = Str::transliterate(Str::lower($credentials['email']).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($key, PlatformSettings::int('security.signin_max_attempts'))) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages(['email' => "Too many sign-in attempts. Try again in {$seconds} seconds."]);
        }

        // A deactivated account fails here like a wrong password, so the form does not reveal which accounts exist
        $staff = SignInChallenge::authenticate(StaffAccess::GUARD, $credentials + ['is_active' => true]);

        if (! $staff) {
            RateLimiter::hit($key, PlatformSettings::int('security.signin_lockout_minutes') * 60);
            StaffAudit::log('staff.sign-in-failed', "Tried to sign in as {$credentials['email']} and failed");

            throw ValidationException::withMessages(['email' => 'These credentials do not match our records.']);
        }

        RateLimiter::clear($key);

        // Staff who need a sign-in code (their own authenticator app, or the platform rule) are signed in after entering it
        if (! SignInChallenge::attempt(StaffAccess::GUARD, $staff, $request->boolean('remember'))) {
            return redirect()->route('admin.two-factor.challenge');
        }

        $staff->forceFill(['last_login_at' => now()])->save();
        StaffAudit::log('staff.signed-in', 'Signed in', staffId: $staff->id);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request)
    {
        // Only the staff guard is signed out: a member session in the same browser stays as it is
        Auth::guard(StaffAccess::GUARD)->logout();
        SessionRules::forget(StaffAccess::GUARD, $request);
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
