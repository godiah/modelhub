<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Support\Staff\StaffAudit;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

/** Forgotten passwords and invitations: the same "set your password" link, sent to a staff email address. */
class StaffPasswordController extends Controller
{
    public function request()
    {
        return view('staff.auth.forgot-password');
    }

    public function email(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        // The same answer whether or not the address belongs to staff, so it cannot be used to find out who is
        Password::broker('staff')->sendResetLink($request->only('email'));

        return back()->with('status', 'If that address belongs to a staff account, a link to set a new password is on its way.');
    }

    public function reset(Request $request, string $token)
    {
        return view('staff.auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(12)->letters()->numbers()],
        ]);

        $status = Password::broker('staff')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (Staff $staff) use ($request) {
                $staff->forceFill(['password' => $request->password, 'remember_token' => Str::random(60)])->save();
                event(new PasswordReset($staff));
                StaffAudit::log('staff.password-set', 'Set a new password', $staff, staffId: $staff->id);
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('admin.login')->with('status', 'Your password is set. Sign in with it.')
            : back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
    }
}
