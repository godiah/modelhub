<?php

namespace App\Http\Middleware\Support;

use App\Models\SupportReadAudit;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records each read once the caller has proved who it is: which key, which member, which endpoint, the outcome. It never records what was
 * read (no amounts, no references, no query), so "who looked at what" is answerable without the audit table holding financial data.
 * Calls refused before the signature is proved are only logged, so an outsider cannot fill the table.
 */
class AuditSupportRead
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->header('X-Request-ID');
        $requestId = is_string($requestId) && preg_match('/^[A-Za-z0-9._-]{1,64}$/', $requestId) ? $requestId : (string) Str::uuid();
        $request->attributes->set('support.request_id', $requestId);

        /** @var Response $response */
        $response = $next($request);

        // What is returned is private to the member: never cached by a proxy or the browser
        $response->headers->set('Cache-Control', 'no-store');
        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }

    public function terminate(Request $request, Response $response): void
    {
        $principal = $request->attributes->get('support.principal');

        if (! is_string($principal)) {
            return;
        }

        $status = $response->getStatusCode();

        SupportReadAudit::create([
            'request_id' => $request->attributes->get('support.request_id'),
            'principal' => $principal,
            'user_id' => $request->attributes->get('support.member')?->getKey(),
            'endpoint' => $request->route()?->getName(),
            'status' => $status,
            'outcome' => match (true) {
                $status >= 200 && $status < 300 => 'ok',
                $status === 404 => 'not_found',
                $status === 429 => 'throttled',
                $status >= 500 => 'error',
                default => 'denied',
            },
        ]);
    }
}
