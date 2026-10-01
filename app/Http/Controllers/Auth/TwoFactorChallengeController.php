<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Support\Auth\SignInChallenge;
use App\Support\Auth\TrustedDevices;
use App\Support\Settings\PlatformSettings;
use App\Support\Staff\StaffAccess;
use App\Support\Staff\StaffAudit;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * The sign-in code step, for members (/two-factor) and staff (/admin/two-factor). Reached only by someone who has already
 * given the right password and is waiting in a SignInChallenge; nobody is signed in until the code is right.
 */
class TwoFactorChallengeController extends Controller
{
    public function show(Request $request)
    {
        $challenge = SignInChallenge::pending($guard = $this->guard($request));

        if (! $challenge) {
            return redirect()->route($this->routes($guard)['login']);
        }

        return view($guard === StaffAccess::GUARD ? 'staff.auth.two-factor' : 'auth.two-factor-challenge', [
            'method' => $challenge['method'],
            'email' => SignInChallenge::account($challenge)?->email,
            'routes' => $this->routes($guard),
            'canTrust' => TrustedDevices::enabled(),
            'trustDays' => PlatformSettings::int('security.trusted_device_days'),
        ]);
    }

    public function store(Request $request)
    {
        $guard = $this->guard($request);
        $request->validate(['code' => ['required', 'string', 'max:32']]);

        try {
            SignInChallenge::verify($guard, $request->input('code'));
        } catch (ValidationException $e) {
            if ($guard === StaffAccess::GUARD && ($challenge = SignInChallenge::pending($guard))) {
                StaffAudit::log('staff.sign-in-failed', 'Entered a wrong sign-in code', staffId: $challenge['id']);
            }

            // A sign-in that has run out of tries or time starts again from the password
            return SignInChallenge::pending($guard) ? throw $e : redirect()->route($this->routes($guard)['login'])->withErrors($e->errors());
        }

        $account = SignInChallenge::complete($guard, $request->boolean('trust_device'));

        if ($account instanceof Staff) {
            $account->forceFill(['last_login_at' => now()])->save();
            StaffAudit::log('staff.signed-in', 'Signed in', staffId: $account->id);
        }

        return redirect()->intended(route($this->routes($guard)['home']));
    }

    public function resend(Request $request)
    {
        $sent = SignInChallenge::resend($this->guard($request));

        return back()->with('status', $sent ? 'A new code is on its way.' : 'Wait a moment before asking for another code.');
    }

    public function cancel(Request $request)
    {
        SignInChallenge::cancel();

        return redirect()->route($this->routes($this->guard($request))['login']);
    }

    private function guard(Request $request): string
    {
        return $request->is('admin/*') ? StaffAccess::GUARD : 'web';
    }

    /** @return array{login: string, home: string, verify: string, resend: string, cancel: string} */
    private function routes(string $guard): array
    {
        return $guard === StaffAccess::GUARD
            ? ['login' => 'admin.login', 'home' => 'admin.dashboard', 'verify' => 'admin.two-factor.verify', 'resend' => 'admin.two-factor.resend', 'cancel' => 'admin.two-factor.cancel']
            : ['login' => 'login', 'home' => 'dashboard', 'verify' => 'two-factor.verify', 'resend' => 'two-factor.resend', 'cancel' => 'two-factor.cancel'];
    }
}
