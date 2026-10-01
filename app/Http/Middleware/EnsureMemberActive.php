<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** A member suspended by staff is signed out on their next request, wherever they were signed in. */
class EnsureMemberActive
{
    public function handle(Request $request, Closure $next)
    {
        $member = Auth::guard('web')->user();

        if ($member && $member->isSuspended()) {
            Auth::guard('web')->logout();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['form.email' => 'This account has been suspended. If you think that is a mistake, contact support.']);
        }

        return $next($request);
    }
}
