<?php

namespace App\Http\Controllers;

use App\Services\Support\SupportAgentClient;
use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * The chat panel's history: the member's past chats, one chat's messages, and hiding a chat. Like the chat itself, the browser only
 * ever talks to ModelHub: this signs each call and names the signed-in member, and the service answers only for that member.
 */
class SupportConversationController extends Controller
{
    public function index(Request $request, SupportAgentClient $agent): Response
    {
        return $this->forward(fn (string $requestId) => $agent->conversations($request->user(), $request->session()->getId(), $requestId));
    }

    public function show(Request $request, SupportAgentClient $agent, string $conversation): Response
    {
        return $this->forward(fn (string $requestId) => $agent->conversation($request->user(), $request->session()->getId(), $conversation, $requestId));
    }

    public function destroy(Request $request, SupportAgentClient $agent, string $conversation): Response
    {
        return $this->forward(fn (string $requestId) => $agent->archive($request->user(), $request->session()->getId(), $conversation, $requestId));
    }

    /** @param  Closure(string): \Illuminate\Http\Client\Response  $call */
    private function forward(Closure $call): Response
    {
        // The kill switch, checked on every call before anything leaves this app.
        if (! config('support.enabled')) {
            return $this->error('assistant_disabled', __('The assistant is switched off.'), Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $requestId = (string) Str::uuid();

        try {
            $reply = $call($requestId);
        } catch (ConnectionException|RuntimeException $e) {
            Log::warning('Support assistant unreachable or not configured', ['request_id' => $requestId, 'error' => $e->getMessage()]);

            return $this->unavailable();
        }

        // A member's chats are private: never let a browser or a proxy keep a copy.
        $headers = ['Cache-Control' => 'no-store', 'X-Request-ID' => $requestId];

        return match ($reply->status()) {
            Response::HTTP_OK => response()->json($reply->json() ?? [], Response::HTTP_OK, $headers),
            Response::HTTP_NO_CONTENT => response()->noContent(Response::HTTP_NO_CONTENT, $headers),
            Response::HTTP_NOT_FOUND => response()->json($reply->json() ?? [], Response::HTTP_NOT_FOUND, $headers),
            default => $this->failed($requestId, $reply),
        };
    }

    /** Anything else (rejected signature, a crash on their side) is our problem, not the member's, and says nothing about why. */
    private function failed(string $requestId, \Illuminate\Http\Client\Response $reply): JsonResponse
    {
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
