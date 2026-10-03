<?php

namespace App\Services\Support;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Sends one chat message to the support assistant service on behalf of a signed-in member.
 *
 * Every call carries two proofs: the request is signed (so it came from this app, unchanged, once) and a 60-second user context says who
 * is asking. The reply is returned still open, so the controller can pass the stream straight on to the browser.
 */
class SupportAgentClient
{
    private const CHAT_PATH = '/v1/chat';

    /**
     * @param  array{message: string, conversation_id?: string, page_context?: array<string, string>}  $payload
     *
     * @throws ConnectionException when the service cannot be reached
     * @throws RuntimeException when this app is not configured to talk to it
     */
    public function chat(User $user, string $sessionId, array $payload, ?string $requestId = null): Response
    {
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $headers = AgentRequestSigner::fromConfig()->sign('POST', self::CHAT_PATH, $body) + [
            'X-Support-User-Context' => UserContextMinter::fromConfig()->mint($user, $sessionId),
            'Accept' => 'text/event-stream',
        ];

        if ($requestId !== null) {
            $headers['X-Request-ID'] = $requestId;
        }

        return Http::withHeaders($headers)
            ->withOptions(['stream' => true])
            ->timeout((int) config('support.agent.timeout'))
            ->withBody($body, 'application/json')
            ->post(rtrim((string) config('support.agent.url'), '/').self::CHAT_PATH);
    }
}
