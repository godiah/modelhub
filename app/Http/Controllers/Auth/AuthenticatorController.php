<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\FlashAlertHelper;
use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Support\Settings\PlatformSettings;
use App\Support\Staff\StaffAccess;
use App\Support\Staff\StaffAudit;
use Illuminate\Http\Request;

/**
 * Setting up, replacing and removing an authenticator app. One controller for both account types: members reach it from their
 * profile, staff from their account page (/admin/account/authenticator). Every step asks for the current password first.
 */
class AuthenticatorController extends Controller
{
    /** Step 1: make a secret. It does nothing until a code from the app confirms it (step 2). */
    public function start(Request $request)
    {
        abort_unless(PlatformSettings::bool('security.authenticator_allowed'), 403);
        $this->confirmPassword($request);

        $account = $request->user();

        if ($account->hasAuthenticator()) {
            return $this->back($request)->with(FlashAlertHelper::error('An authenticator app is already set up. Remove it before setting up another.'));
        }

        $account->startAuthenticatorSetup();

        return $this->back($request);
    }

    /** Step 2: a code from the app proves it works. The recovery codes are shown once, now. */
    public function confirm(Request $request)
    {
        $request->validate(['code' => ['required', 'string', 'max:12']]);

        $codes = $request->user()->confirmAuthenticator($request->input('code'));

        if ($codes === null) {
            return $this->back($request)->withErrors(['code' => 'That code is not right. Codes change every 30 seconds, so try the current one.']);
        }

        $this->audit($request, 'staff.authenticator-enabled', 'Set up an authenticator app');

        return $this->back($request)->with('recovery_codes', $codes)->with(FlashAlertHelper::success('Authenticator app is on'));
    }

    /** Give up a setup that was started but not confirmed. */
    public function cancel(Request $request)
    {
        if ($request->user()->authenticatorSetupPending()) {
            $request->user()->removeAuthenticator();
        }

        return $this->back($request);
    }

    public function recoveryCodes(Request $request)
    {
        $this->confirmPassword($request);
        abort_unless($request->user()->hasAuthenticator(), 404);

        $codes = $request->user()->regenerateRecoveryCodes();
        $this->audit($request, 'staff.recovery-codes-renewed', 'Made new recovery codes');

        return $this->back($request)->with('recovery_codes', $codes);
    }

    public function destroy(Request $request)
    {
        $this->confirmPassword($request);

        $request->user()->removeAuthenticator();
        $this->audit($request, 'staff.authenticator-removed', 'Removed their authenticator app');

        return $this->back($request)->with(FlashAlertHelper::success('Authenticator app removed'));
    }

    private function confirmPassword(Request $request): void
    {
        $guard = $request->user() instanceof Staff ? StaffAccess::GUARD : 'web';

        $request->validate(['password' => ['required', 'current_password:'.$guard]]);
    }

    private function back(Request $request)
    {
        return $request->user() instanceof Staff ? redirect()->route('admin.account.edit') : redirect(route('profile').'#security');
    }

    private function audit(Request $request, string $action, string $summary): void
    {
        if ($request->user() instanceof Staff) {
            StaffAudit::log($action, $summary, staffId: $request->user()->id);
        }
    }
}
