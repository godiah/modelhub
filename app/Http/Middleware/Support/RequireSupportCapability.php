<?php

namespace App\Http\Middleware\Support;

use App\Services\Support\Inbound\SupportRequestRejected;
use Closure;
use Illuminate\Http\Request;

/** One switch per kind of read (`support.reads.capabilities.withdrawals`), so a single read can be turned off without the rest. Off looks like nothing is there. */
class RequireSupportCapability
{
    public function handle(Request $request, Closure $next, string $capability)
    {
        if (! config("support.reads.capabilities.{$capability}", false)) {
            return (new SupportRequestRejected('capability_off', 404))->toResponse();
        }

        return $next($request);
    }
}
