<?php

namespace App\Http\Middleware;

use App\Support\Auth\SessionRules;
use App\Support\Staff\StaffAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Applies the platform's session rules (idle timeout, longest session, one active session) to whoever is signed in, member
 * and staff separately: a session that breaks a rule is signed out on its next request.
 */
class EnforceSessionRules
{
    public function handle(Request $request, Closure $next)
    {
        foreach (['web' => 'login', StaffAccess::GUARD => 'admin.login'] as $guard => $loginRoute) {
            if (! Auth::guard($guard)->check() || ! ($reason = SessionRules::violation($guard, $request))) {
                continue;
            }

            Auth::guard($guard)->logout();
            SessionRules::forget($guard, $request);
            $request->session()->regenerateToken();

            // A background request (Livewire, fetch) cannot show a message: tell it the session is gone and let the page reload
            if ($request->expectsJson() || $request->hasHeader('X-Livewire')) {
                abort(419);
            }

            return redirect()->route($loginRoute)->with('status', $reason);
        }

        return $next($request);
    }
}
