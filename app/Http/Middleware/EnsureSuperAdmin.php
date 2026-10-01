<?php

namespace App\Http\Middleware;

use App\Support\Staff\StaffAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Platform settings are for Super admins only: no permission can be handed out for them. */
class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless(Auth::guard(StaffAccess::GUARD)->user()?->hasRole(StaffAccess::SUPER_ADMIN), 403);

        return $next($request);
    }
}
