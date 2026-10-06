<?php

namespace App\Http\Middleware\Support;

use App\Services\Support\Inbound\InboundRequestVerifier;
use App\Services\Support\Inbound\SupportRequestRejected;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/** Second check: the call is signed with the reads secret, unchanged, fresh and not a replay. Records which key (the principal) signed it. */
class VerifySupportSignature
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $principal = InboundRequestVerifier::fromConfig()->verify(
                $request->method(),
                $request->getRequestUri(), // the path and query exactly as sent: that is what was signed
                $request->getContent(),
                $request->header('X-Support-Key-Id'),
                $request->header('X-Support-Timestamp'),
                $request->header('X-Support-Nonce'),
                $request->header('X-Support-Signature'),
            );
        } catch (SupportRequestRejected $e) {
            Log::warning('Support read refused', ['reason' => $e->reason, 'ip' => $request->server->get('REMOTE_ADDR')]);

            return $e->toResponse();
        }

        $request->attributes->set('support.principal', $principal);

        return $next($request);
    }
}
