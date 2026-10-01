<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Every web request starts on the member guard. The staff portal's `auth:staff` middleware then switches the default
 * guard for its own routes, so `auth()->user()` there is the staff member and everywhere else it is the member.
 * (A real request never carries the switch over, but a long-lived process or a test would.)
 */
class ResetDefaultGuard
{
    public function handle(Request $request, Closure $next)
    {
        Auth::shouldUse('web');

        return $next($request);
    }

    /** And back again once the request is done, so whatever runs next (another request, a test) starts from the member guard. */
    public function terminate(Request $request, $response): void
    {
        Auth::shouldUse('web');
    }
}
