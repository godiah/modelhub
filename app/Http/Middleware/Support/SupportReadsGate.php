<?php

namespace App\Http\Middleware\Support;

use App\Services\Support\Inbound\CallerAllowList;
use App\Services\Support\Inbound\SupportRequestRejected;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/** First check on the read API: is it switched on, and is the caller's address one we expect. */
class SupportReadsGate
{
    public function handle(Request $request, Closure $next)
    {
        // Errors on these routes are always JSON, never a page
        $request->headers->set('Accept', 'application/json');

        if (! config('support.reads.enabled')) {
            return (new SupportRequestRejected('reads_disabled', 404))->toResponse();
        }

        // The connecting address, not a header the caller can write
        $ip = (string) $request->server->get('REMOTE_ADDR');

        if (! CallerAllowList::allows($ip)) {
            Log::warning('Support read refused', ['reason' => 'address_not_allowed', 'ip' => $ip]);

            return (new SupportRequestRejected('address_not_allowed'))->toResponse();
        }

        return $next($request);
    }
}
