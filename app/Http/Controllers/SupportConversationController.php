<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ForwardsToSupportAgent;
use App\Services\Support\SupportAgentClient;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The chat panel's history: the member's past chats, one chat's messages, and hiding a chat. Like the chat itself, the browser only
 * ever talks to ModelHub: this signs each call and names the signed-in member, and the service answers only for that member.
 */
class SupportConversationController extends Controller
{
    use ForwardsToSupportAgent;

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
}
