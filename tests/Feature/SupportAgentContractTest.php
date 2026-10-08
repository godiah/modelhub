<?php

use App\Enums\SupportTicketCategory;
use App\Models\Staff;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\Support\SupportAgentClient;
use App\Services\Support\Tickets\TicketService;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/*
 * ModelHub's side of the contract for its calls to the support assistant (docs/support-agent-api.openapi.yaml): hand-off evidence, the note that a ticket
 * was filed, and staff reading a chat. The same file is kept in modelhub-support, whose tests hold the real endpoints to it; both pin its hash.
 *
 * Here: what ModelHub SENDS fits the contract (paths, methods, the headers each call must carry and must not carry, the body), and ModelHub can USE
 * every answer the contract allows (each suggested category is one it knows; a packet with nothing in it still files a ticket).
 */

require_once __DIR__.'/Support/SchemaCheck.php';

// The same value is pinned in modelhub-support's tests/integration/test_agent_contract.py. Change the contract in both repositories, then update both.
const SUPPORT_AGENT_CONTRACT_SHA256 = '43ee4f77376a190200180d4d895ad69801bd3b4bf87db31bff9209f5f9747588';

const AGENT_CONTRACT_CHAT = '6e0f5b1e-8d1a-4d6b-9a52-3c1e5b9d2f21';

beforeEach(function () {
    Http::swap(new Factory);
    config([
        'support.enabled' => true, 'support.agent.url' => 'http://agent.test', 'support.agent.hmac_key_id' => 'current', 'support.agent.hmac_secret' => 's',
        'support.agent.context_private_key' => base64_encode(sodium_crypto_sign_secretkey(sodium_crypto_sign_keypair())),
    ]);
    $this->contract = supportContract(SUPPORT_AGENT_CONTRACT);
    $this->member = User::factory()->create();
});

function agentContractFits(mixed $body, string $schemaName): array
{
    $contract = supportContract(SUPPORT_AGENT_CONTRACT);

    return schemaProblems($body, supportSchema($schemaName, $contract), $contract);
}

/** The packets the contract allows: everything filled in, and nothing at all. */
function agentEvidenceSamples(): array
{
    $full = [
        'conversation_id' => AGENT_CONTRACT_CHAT, 'suggested_category' => 'payment_issue', 'suggested_summary' => 'Payment MHX: Needs review.',
        'entity_refs' => [['type' => 'payment', 'reference' => 'MHX']], 'facts' => ['Payment MHX: Needs review, Ksh5,000'],
        'cards' => [['type' => 'payment', 'reference' => 'MHX', 'status' => 'review', 'as_of' => '2026-10-07T10:00:00+03:00']],
        'messages' => [['role' => 'member', 'text' => 'I paid but I have no licence'], ['role' => 'assistant', 'text' => 'It needs a person.']],
    ];
    $empty = ['conversation_id' => AGENT_CONTRACT_CHAT, 'suggested_category' => 'other', 'suggested_summary' => '', 'entity_refs' => [], 'facts' => [], 'cards' => [], 'messages' => []];

    return [$full, $empty, ['suggested_category' => 'withdrawal_issue'] + $full];
}

it('is the contract both repositories agreed', function () {
    expect(hash_file('sha256', SUPPORT_AGENT_CONTRACT))->toBe(SUPPORT_AGENT_CONTRACT_SHA256);
});

it('documents exactly the three calls the client makes', function () {
    expect(collect($this->contract['paths'])->flatMap(fn ($item, $path) => collect(array_keys($item))->map(fn ($m) => strtoupper($m).' '.$path))->sort()->values()->all())
        ->toBe([
            'GET /v1/conversations/{conversation_id}/evidence',
            'GET /v1/transcripts/{conversation_id}',
            'POST /v1/conversations/{conversation_id}/handoff',
        ]);
});

it('sends each call to the documented path and method, with the headers the contract requires and none that it does not', function () {
    Http::fake(['agent.test/*' => Http::response(null, 204)]);
    $client = app(SupportAgentClient::class);
    $staff = Staff::factory()->create();

    $client->evidence($this->member, 'session', AGENT_CONTRACT_CHAT);
    $client->handedOff($this->member, 'session', AGENT_CONTRACT_CHAT, 'SUP-1234');
    $client->transcript($staff, AGENT_CONTRACT_CHAT, $this->member);

    $calls = collect(Http::recorded())->map(fn ($pair) => $pair[0]);
    expect($calls)->toHaveCount(3);

    foreach ($calls as $request) {
        /** @var Request $request */
        $template = preg_replace('#/'.AGENT_CONTRACT_CHAT.'#', '/{conversation_id}', parse_url($request->url(), PHP_URL_PATH));
        $operation = $this->contract['paths'][$template][strtolower($request->method())] ?? null;

        expect($operation)->not->toBeNull("{$request->method()} {$template} is not in the contract");

        $required = collect($operation['parameters'])->where('in', 'header')->pluck('name')->all();
        foreach ($required as $header) {
            expect($request->hasHeader($header))->toBeTrue("{$template} must carry {$header}");
        }
        // Each kind of claim goes only where it belongs: a staff claim never rides on a member call, nor a member's on the transcript
        foreach (['X-Support-User-Context', 'X-Support-Staff-Claim'] as $claim) {
            if (! in_array($claim, $required, true)) {
                expect($request->hasHeader($claim))->toBeFalse("{$template} must not carry {$claim}");
            }
        }
        foreach (['X-Support-Key-Id', 'X-Support-Timestamp', 'X-Support-Nonce', 'X-Support-Signature'] as $signed) {
            expect($request->hasHeader($signed))->toBeTrue("{$template} must be signed ({$signed})");
        }
    }
});

it('sends a hand-off note body that fits the contract, with a reference of the form the contract names', function () {
    Http::fake(['agent.test/*' => Http::response(null, 204)]);
    $ticket = app(TicketService::class)->open($this->member, SupportTicketCategory::Other, 'I need help with my request', conversationId: AGENT_CONTRACT_CHAT);
    expect($ticket)->toBeInstanceOf(SupportTicket::class);

    app(SupportAgentClient::class)->handedOff($this->member, 's', AGENT_CONTRACT_CHAT, $ticket->reference);

    Http::assertSent(function (Request $request) use ($ticket) {
        $body = json_decode($request->body(), true);

        return agentContractFits($body, 'HandoffNote') === [] && $body === ['reference' => $ticket->reference] && preg_match('/^SUP-[0-9]{4,10}$/', $body['reference']) === 1;
    });
});

it('can use every packet the contract allows: it files the ticket and keeps what is in it', function (array $packet) {
    expect(agentContractFits($packet, 'Evidence'))->toBe([]);
    Http::fake(['agent.test/v1/conversations/*/evidence' => Http::response($packet, 200), 'agent.test/v1/conversations/*/handoff' => Http::response(null, 204)]);

    $response = $this->actingAs($this->member)->postJson(route('support.handoff.store'), ['conversation_id' => AGENT_CONTRACT_CHAT, 'category' => $packet['suggested_category'], 'summary' => 'Please help'])->assertCreated();

    $ticket = SupportTicket::where('reference', $response->json('reference'))->firstOrFail();
    expect($ticket->conversation_id)->toBe(AGENT_CONTRACT_CHAT)
        ->and($ticket->evidence['facts'])->toBe($packet['facts'])
        ->and($ticket->evidence['messages'])->toBe($packet['messages']);
})->with(fn () => array_map(fn ($sample) => [$sample], agentEvidenceSamples()));

it('knows every category the contract lets the assistant suggest', function () {
    $suggestable = $this->contract['components']['schemas']['Evidence']['properties']['suggested_category']['enum'];

    foreach ($suggestable as $value) {
        expect(SupportTicketCategory::tryFrom($value))->not->toBeNull("the assistant may suggest {$value}, which ModelHub does not know");
    }
});

it('can show every transcript the contract allows', function () {
    $transcript = [
        'conversation_id' => AGENT_CONTRACT_CHAT, 'member_id' => (string) $this->member->id, 'truncated' => true,
        'messages' => [
            ['role' => 'member', 'text' => 'hello', 'at' => '2026-10-07T10:00:00+03:00', 'cards' => [], 'guard' => null],
            ['role' => 'assistant', 'text' => 'hi', 'at' => '2026-10-07T10:00:05+03:00', 'cards' => [['type' => 'payment', 'reference' => 'MHX']], 'guard' => 'read'],
        ],
    ];
    expect(agentContractFits($transcript, 'Transcript'))->toBe([]);

    Http::fake(['agent.test/v1/transcripts/*' => Http::response($transcript, 200)]);
    $ticket = app(TicketService::class)->open($this->member, SupportTicketCategory::Other, 'I need help with my request', conversationId: AGENT_CONTRACT_CHAT);

    $this->actingAs(staffWith('Super admin'), 'staff')->get(route('admin.support.tickets.transcript', $ticket))
        ->assertOk()->assertSee('hello')->assertSee('only the latest messages');
});

it('checks the contract\'s own samples the way it checks real answers: a field it does not name, or a wrong value, is a problem', function () {
    [$full] = agentEvidenceSamples();

    expect(agentContractFits($full + ['surprise' => 1], 'Evidence'))->not->toBe([])
        ->and(agentContractFits(['suggested_category' => 'refund_issue'] + $full, 'Evidence'))->not->toBe([])
        ->and(agentContractFits(array_diff_key($full, ['facts' => 1]), 'Evidence'))->not->toBe([])
        ->and(agentContractFits(['error' => ['code' => 'x', 'message' => 'y', 'fields' => ['reference']]], 'Error'))->toBe([]);
});
