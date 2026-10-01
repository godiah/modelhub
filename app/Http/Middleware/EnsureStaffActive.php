<?php

namespace App\Http\Middleware;

use App\Support\Staff\StaffAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** A deactivated staff account is signed out on its next request, wherever it was signed in. */
class EnsureStaffActive
{
    public function handle(Request $request, Closure $next)
    {
        $staff = Auth::guard(StaffAccess::GUARD)->user();

        if ($staff && ! $staff->is_active) {
            Auth::guard(StaffAccess::GUARD)->logout();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')->withErrors(['email' => 'This staff account has been deactivated.']);
        }

        return $next($request);
    }
}
