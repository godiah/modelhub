<?php

namespace App\Services\Support;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Talks to the support assistant service on behalf of a signed-in member: sends a chat message, and lists, opens and archives their
 * past chats.
 *
 * Every call carries two proofs: the request is signed (so it came from this app, unchanged, once) and a 60-second user context says who
 * is asking. The reply is returned still open, so the controller can pass the stream straight on to the browser.
 */
class SupportAgentClient
{
    private const CHAT_PATH = '/v1/chat';

    private const CONVERSATIONS_PATH = '/v1/conversations';

    private const ARTICLES_PATH = '/v1/articles';

    /** Listing and opening chats are quick lookups, unlike an answer, so they do not wait as long. */
    private const HISTORY_TIMEOUT = 15;

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

        // The claim the assistant uses to read this member's own records during this answer; not sent unless reads are switched on
        if (config('support.reads.enabled')) {
            $headers['X-Support-Read-Claim'] = UserContextMinter::fromConfig()->mintRead($user);
        }

        if ($requestId !== null) {
            $headers['X-Request-ID'] = $requestId;
        }

        return Http::withHeaders($headers)
            ->withOptions(['stream' => true])
            ->timeout((int) config('support.agent.timeout'))
            ->withBody($body, 'application/json')
            ->post(rtrim((string) config('support.agent.url'), '/').self::CHAT_PATH);
    }

    /** The member's chats, most recent first. */
    public function conversations(User $user, string $sessionId, ?string $requestId = null): Response
    {
        return $this->history('GET', self::CONVERSATIONS_PATH, $user, $sessionId, $requestId);
    }

    /** One of the member's chats with all its messages. The service answers "not found" for a chat that is not theirs. */
    public function conversation(User $user, string $sessionId, string $conversationId, ?string $requestId = null): Response
    {
        return $this->history('GET', self::CONVERSATIONS_PATH.'/'.$conversationId, $user, $sessionId, $requestId);
    }

    /** Hide one of the member's chats from them. The service keeps the record. */
    public function archive(User $user, string $sessionId, string $conversationId, ?string $requestId = null): Response
    {
        return $this->history('DELETE', self::CONVERSATIONS_PATH.'/'.$conversationId, $user, $sessionId, $requestId);
    }

    /** A help article, with today's numbers. `$chunk` (from a citation) marks the passage an answer was built from. */
    public function article(User $user, string $sessionId, string $slug, ?string $chunk = null, ?string $requestId = null): Response
    {
        // The query string is part of what is signed, so it is built once and used for both the signature and the address
        $pathAndQuery = self::ARTICLES_PATH.'/'.$slug.($chunk !== null ? '?chunk='.$chunk : '');

        return $this->history('GET', $pathAndQuery, $user, $sessionId, $requestId);
    }

    /** What the assistant can tell staff about this chat, built by code from what it stored. 404 if the chat is not the member's. */
    public function evidence(User $user, string $sessionId, string $conversationId, ?string $requestId = null): Response
    {
        return $this->history('GET', self::CONVERSATIONS_PATH.'/'.$conversationId.'/evidence', $user, $sessionId, $requestId);
    }

    /** Tell the assistant a ticket was filed from this chat, so the chat says so. Safe to repeat. */
    public function handedOff(User $user, string $sessionId, string $conversationId, string $reference, ?string $requestId = null): Response
    {
        $path = self::CONVERSATIONS_PATH.'/'.$conversationId.'/handoff';
        $body = json_encode(['reference' => $reference], JSON_UNESCAPED_SLASHES);

        $headers = AgentRequestSigner::fromConfig()->sign('POST', $path, $body) + [
            'X-Support-User-Context' => UserContextMinter::fromConfig()->mint($user, $sessionId),
            'Accept' => 'application/json',
        ];

        if ($requestId !== null) {
            $headers['X-Request-ID'] = $requestId;
        }

        return Http::withHeaders($headers)->timeout(self::HISTORY_TIMEOUT)->withBody($body, 'application/json')->post(rtrim((string) config('support.agent.url'), '/').$path);
    }

    /**
     * The whole of a member's chat, for a staff member reading their ticket. Signed like every call, and carrying a staff claim for this one
     * chat and this one member instead of a member's context. The caller has already checked the staff member's permission and logged the read.
     */
    public function transcript(Staff $staff, string $conversationId, User $member, ?string $requestId = null): Response
    {
        $path = '/v1/transcripts/'.$conversationId;

        $headers = AgentRequestSigner::fromConfig()->sign('GET', $path, '') + [
            'X-Support-Staff-Claim' => UserContextMinter::fromConfig()->mintStaffTranscript($staff, $conversationId, $member),
            'Accept' => 'application/json',
        ];

        if ($requestId !== null) {
            $headers['X-Request-ID'] = $requestId;
        }

        return Http::withHeaders($headers)->timeout(self::HISTORY_TIMEOUT)->get(rtrim((string) config('support.agent.url'), '/').$path);
    }

    /**
     * Same two proofs as a chat message. There is no body, and no query string except the one the article call builds itself: the member
     * is named by the signed claim and nothing the browser sends can change whose chats are asked for.
     */
    private function history(string $method, string $pathAndQuery, User $user, string $sessionId, ?string $requestId): Response
    {
        $headers = AgentRequestSigner::fromConfig()->sign($method, $pathAndQuery, '') + [
            'X-Support-User-Context' => UserContextMinter::fromConfig()->mint($user, $sessionId),
            'Accept' => 'application/json',
        ];

        if ($requestId !== null) {
            $headers['X-Request-ID'] = $requestId;
        }

        return Http::withHeaders($headers)
            ->timeout(self::HISTORY_TIMEOUT)
            ->send($method, rtrim((string) config('support.agent.url'), '/').$pathAndQuery);
    }
}
