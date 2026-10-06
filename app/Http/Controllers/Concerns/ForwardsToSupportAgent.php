<?php

namespace App\Http\Controllers\Concerns;

use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response as AgentResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Passing one signed call to the support assistant service on to the browser: the kill switch first, then what the member may see of the
 * answer. A "not found" is theirs to act on and goes through; anything else (a rejected signature, a crash on the service's side) is
 * our problem, is logged here, and tells the member only that the assistant is not available.
 */
trait ForwardsToSupportAgent
{
    /** @param  Closure(string): AgentResponse  $call  makes the call, given a request id to carry on it */
    private function forward(Closure $call): Response
    {
        // The kill switch, checked on every call before anything leaves this app.
        if (! config('support.enabled')) {
            return $this->supportError('assistant_disabled', __('The assistant is switched off.'), Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $requestId = (string) Str::uuid();

        try {
            $reply = $call($requestId);
        } catch (ConnectionException|RuntimeException $e) {
            Log::warning('Support assistant unreachable or not configured', ['request_id' => $requestId, 'error' => $e->getMessage()]);

            return $this->supportUnavailable();
        }

        // What is passed on is private to the member: never let a browser or a proxy keep a copy.
        $headers = ['Cache-Control' => 'no-store', 'X-Request-ID' => $requestId];

        return match ($reply->status()) {
            Response::HTTP_OK => response()->json($reply->json() ?? [], Response::HTTP_OK, $headers),
            Response::HTTP_NO_CONTENT => response()->noContent(Response::HTTP_NO_CONTENT, $headers),
            Response::HTTP_NOT_FOUND => response()->json($reply->json() ?? [], Response::HTTP_NOT_FOUND, $headers),
            default => $this->supportFailed($requestId, $reply),
        };
    }

    private function supportFailed(string $requestId, AgentResponse $reply): JsonResponse
    {
        Log::error('Support assistant refused or failed', ['request_id' => $requestId, 'status' => $reply->status(), 'body' => Str::limit($reply->body(), 300)]);

        return $this->supportUnavailable();
    }

    private function supportUnavailable(): JsonResponse
    {
        return $this->supportError('assistant_unavailable', __('The assistant is not available right now.'), Response::HTTP_SERVICE_UNAVAILABLE);
    }

    private function supportError(string $code, string $message, int $status): JsonResponse
    {
        return response()->json(['error' => ['code' => $code, 'message' => $message]], $status);
    }
}
