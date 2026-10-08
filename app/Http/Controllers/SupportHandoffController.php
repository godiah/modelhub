<?php

namespace App\Http\Controllers;

use App\Enums\SupportTicketCategory;
use App\Models\SupportTicket;
use App\Services\Support\SupportAgentClient;
use App\Services\Support\Tickets\EntityRefs;
use App\Services\Support\Tickets\SeverityRules;
use App\Services\Support\Tickets\TicketService;
use App\Services\Support\Tickets\TicketTargets;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * "Talk to a person" from the chat panel, in two steps: `prepare` suggests a category and a summary (built by code from what the assistant showed) for
 * the member to confirm or change, and `store` files the ticket.
 *
 * The browser never supplies evidence or the records a ticket is about. The conversation id is only a pointer: ModelHub asks the assistant for that
 * chat's evidence over a signed call scoped to this member, and uses what comes back. If the assistant cannot be reached the ticket is still filed,
 * with the member's own words and no evidence: asking for a person must never depend on the assistant being up.
 */
class SupportHandoffController extends Controller
{
    public function __construct(private readonly SupportAgentClient $agent, private readonly TicketService $tickets, private readonly SeverityRules $severity) {}

    public function prepare(Request $request): JsonResponse
    {
        $data = $request->validate(['conversation_id' => ['nullable', 'uuid']]);
        $conversationId = $data['conversation_id'] ?? null;

        $existing = $conversationId ? $this->existing($request, $conversationId) : null;
        $evidence = $conversationId ? $this->evidence($request, $conversationId) : null;

        $category = SupportTicketCategory::tryFrom((string) ($evidence['suggested_category'] ?? '')) ?? SupportTicketCategory::Other;
        $summary = (string) ($evidence['suggested_summary'] ?? '');

        // The aim shown is the one this request would really get: worked out from the member's live records, the way filing it will
        $refs = EntityRefs::validated($request->user(), (array) ($evidence['entity_refs'] ?? []));
        $severity = $this->severity->evaluate($request->user(), $category, $refs, $summary);

        return response()->json([
            'categories' => array_map(fn (SupportTicketCategory $c) => ['value' => $c->value, 'label' => __($c->label())], SupportTicketCategory::cases()),
            'category' => $category->value,
            'summary' => $summary,
            'existing' => $existing ? ['reference' => $existing->reference, 'url' => route('support.requests.show', $existing)] : null,
            'aim' => TicketTargets::sentence($severity),
        ], Response::HTTP_OK, ['Cache-Control' => 'no-store']);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'conversation_id' => ['nullable', 'uuid'],
            'category' => ['required', 'string'],
            'summary' => ['required', 'string', 'max:'.TicketService::BODY_MAX],
        ]);

        $category = SupportTicketCategory::tryFrom($data['category']);

        if ($category === null) {
            return $this->fail('Choose what this is about from the list.');
        }

        $conversationId = $data['conversation_id'] ?? null;

        // The same chat twice is the same request, not two
        if ($conversationId && ($existing = $this->existing($request, $conversationId))) {
            return response()->json(['reference' => $existing->reference, 'url' => route('support.requests.show', $existing), 'existing' => true, 'aim' => TicketTargets::sentence($existing->severity)]);
        }

        $evidence = $conversationId ? $this->evidence($request, $conversationId) : null;

        $ticket = $this->tickets->open(
            $request->user(),
            $category,
            $data['summary'],
            // Only the records the assistant says the chat was about, and only those the member owns (TicketService checks each one)
            $evidence['entity_refs'] ?? [],
            $evidence ? $this->snapshot($evidence) : null,
            $evidence ? $conversationId : null,
        );

        if (is_string($ticket)) {
            return $this->fail($ticket);
        }

        if ($evidence && $conversationId) {
            $this->tellTheChat($request, $conversationId, $ticket);
        }

        return response()->json(['reference' => $ticket->reference, 'url' => route('support.requests.show', $ticket), 'existing' => false, 'aim' => TicketTargets::sentence($ticket->severity)], Response::HTTP_CREATED);
    }

    private function existing(Request $request, string $conversationId): ?SupportTicket
    {
        return SupportTicket::ownedBy($request->user())->active()->where('conversation_id', $conversationId)->latest('id')->first();
    }

    /** The assistant's evidence for this chat, or null when there is none (no such chat for this member, the assistant is off or unreachable). */
    private function evidence(Request $request, string $conversationId): ?array
    {
        if (! config('support.enabled')) {
            return null;
        }

        try {
            $reply = $this->agent->evidence($request->user(), $request->session()->getId(), $conversationId, (string) Str::uuid());
        } catch (ConnectionException|RuntimeException $e) {
            Log::warning('Support evidence unavailable', ['error' => $e->getMessage()]);

            return null;
        }

        return $reply->successful() && is_array($reply->json()) ? $reply->json() : null;
    }

    /** What is kept on the ticket: facts for a person to read, the cards (with their "as of"), and the last messages, marked by who wrote them. */
    private function snapshot(array $evidence): array
    {
        return [
            'captured_at' => now()->toIso8601String(),
            'source' => 'assistant',
            'suggested_category' => (string) ($evidence['suggested_category'] ?? ''),
            'suggested_summary' => (string) ($evidence['suggested_summary'] ?? ''),
            'facts' => array_values(array_filter((array) ($evidence['facts'] ?? []), 'is_string')),
            'cards' => array_slice((array) ($evidence['cards'] ?? []), 0, 4),
            'messages' => array_slice((array) ($evidence['messages'] ?? []), 0, 12),
        ];
    }

    private function tellTheChat(Request $request, string $conversationId, SupportTicket $ticket): void
    {
        try {
            $this->agent->handedOff($request->user(), $request->session()->getId(), $conversationId, $ticket->reference, (string) Str::uuid());
        } catch (ConnectionException|RuntimeException $e) {
            // The ticket exists; the note in the chat is a courtesy
            Log::warning('Support handoff note not delivered', ['reference' => $ticket->reference, 'error' => $e->getMessage()]);
        }
    }

    private function fail(string $message): JsonResponse
    {
        return response()->json(['error' => ['code' => 'ticket_refused', 'message' => $message]], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
