<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupportChatRequest;
use App\Services\Support\SupportAgentClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The chat panel's one endpoint. The browser only ever talks to ModelHub: this signs the request, vouches for the signed-in member, and
 * passes the assistant's stream straight back. Sitting behind the normal session, CSRF and member-active checks is the point: the
 * assistant can never be used by someone who could not use the rest of the app.
 */
class SupportChatController extends Controller
{
    public function store(SupportChatRequest $request, SupportAgentClient $agent): StreamedResponse|JsonResponse
    {
        // The kill switch. Checked first, on every request, before anything leaves this app.
        if (! config('support.enabled')) {
            return $this->error('assistant_disabled', __('The assistant is switched off.'), Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $payload = array_filter($request->safe()->only(['message', 'conversation_id', 'page_context', 'action']), fn ($value) => $value !== null);
        $requestId = (string) Str::uuid();

        try {
            $reply = $agent->chat($request->user(), $request->session()->getId(), $payload, $requestId);
        } catch (ConnectionException|RuntimeException $e) {
            Log::warning('Support assistant unreachable or not configured', ['request_id' => $requestId, 'error' => $e->getMessage()]);

            return $this->unavailable();
        }

        if ($reply->status() === Response::HTTP_OK) {
            return response()->stream(function () use ($reply) {
                $stream = $reply->toPsrResponse()->getBody();

                while (! $stream->eof()) {
                    echo $stream->read(1024);

                    if (ob_get_level() > 0) {
                        ob_flush();
                    }

                    flush();
                }
            }, Response::HTTP_OK, [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'X-Accel-Buffering' => 'no',
                'X-Request-ID' => $requestId,
            ]);
        }

        // Things the member can act on (a message that looks like a PIN, a conversation that is not theirs) are passed on as they are.
        if (in_array($reply->status(), [Response::HTTP_NOT_FOUND, Response::HTTP_UNPROCESSABLE_ENTITY], true)) {
            return response()->json($reply->json() ?? [], $reply->status());
        }

        // Sending too fast, or while the last answer is still being written: the member can act on that, so it is passed on with its wait time.
        // Only the two codes the assistant uses are passed, with its own words, never whatever else a 429 might carry.
        if ($reply->status() === Response::HTTP_TOO_MANY_REQUESTS && in_array($reply->json('error.code'), ['rate_limited', 'busy'], true)) {
            return $this->error((string) $reply->json('error.code'), Str::limit((string) $reply->json('error.message'), 200), Response::HTTP_TOO_MANY_REQUESTS)
                ->withHeaders(['Retry-After' => (string) max(1, min(300, (int) $reply->header('Retry-After') ?: 30))]);
        }

        // Anything else (rejected signature, a crash on their side) is our problem, not the member's, and says nothing about why.
        Log::error('Support assistant refused or failed', ['request_id' => $requestId, 'status' => $reply->status(), 'body' => Str::limit($reply->body(), 300)]);

        return $this->unavailable();
    }

    private function unavailable(): JsonResponse
    {
        return $this->error('assistant_unavailable', __('The assistant is not available right now.'), Response::HTTP_SERVICE_UNAVAILABLE);
    }

    private function error(string $code, string $message, int $status): JsonResponse
    {
        return response()->json(['error' => ['code' => $code, 'message' => $message]], $status);
    }
}
