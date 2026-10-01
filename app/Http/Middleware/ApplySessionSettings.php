<?php

namespace App\Http\Middleware;

use App\Support\Settings\PlatformSettings;
use Closure;
use Illuminate\Http\Request;

/**
 * Runs before the session starts. Sessions are kept for the longest idle time any audience is allowed, so the framework
 * never drops a session earlier than a setting says; EnforceSessionRules then applies each audience's own limit.
 */
class ApplySessionSettings
{
    public function handle(Request $request, Closure $next)
    {
        config(['session.lifetime' => max(PlatformSettings::int('security.member_idle_minutes'), PlatformSettings::int('security.staff_idle_minutes'))]);

        return $next($request);
    }
}
