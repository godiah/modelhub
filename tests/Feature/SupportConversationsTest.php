<?php

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/*
 * The panel's chat history. Like the chat itself, the browser only talks to ModelHub: each call is signed, names the signed-in member through a
 * short-lived claim, and goes to the support service, which answers only for that member. Nothing the browser sends can change whose chats
 * are asked for.
 */

const HISTORY_CHAT_ID = '20e4d451-efca-4024-85de-dc79ecb54f1a';

function historyClaimsOf(Request $request): array
{
    [, $payload] = explode('.', $request->header('X-Support-User-Context')[0]);

    return json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
}

/** True when the request carries the signature the service will recompute from the same parts (empty body, exact path). */
function historySignedFor(Request $request, string $method, string $path): bool
{
    $header = fn (string $name) => $request->header($name)[0] ?? null;
    $canonical = implode("\n", [$method, $path, $header('X-Support-Timestamp'), $header('X-Support-Nonce'), hash('sha256', '')]);

    return $request->url() === 'http://agent.test'.$path
        && $request->method() === $method
        && $request->body() === ''
        && $header('X-Support-Key-Id') === 'current'
        && hash_equals(hash_hmac('sha256', $canonical, 'test-secret'), $header('X-Support-Signature'));
}

beforeEach(function () {
    $keypair = sodium_crypto_sign_keypair();

    config([
        'support.enabled' => true,
        'support.agent.url' => 'http://agent.test',
        'support.agent.hmac_key_id' => 'current',
        'support.agent.hmac_secret' => 'test-secret',
        'support.agent.context_private_key' => base64_encode(sodium_crypto_sign_secretkey($keypair)),
    ]);
});

it('is closed to anyone who is not signed in', function () {
    Http::fake();

    $this->getJson(route('support.conversations.index'))->assertUnauthorized();
    $this->getJson(route('support.conversations.show', HISTORY_CHAT_ID))->assertUnauthorized();
    $this->deleteJson(route('support.conversations.destroy', HISTORY_CHAT_ID))->assertUnauthorized();

    Http::assertNothingSent();
});

it('sends nothing anywhere while the kill switch is off', function () {
    Http::fake();
    config(['support.enabled' => false]);
    $user = User::factory()->create();

    $this->actingAs($user)->getJson(route('support.conversations.index'))->assertStatus(503)->assertJsonPath('error.code', 'assistant_disabled');
    $this->actingAs($user)->getJson(route('support.conversations.show', HISTORY_CHAT_ID))->assertStatus(503);
    $this->actingAs($user)->deleteJson(route('support.conversations.destroy', HISTORY_CHAT_ID))->assertStatus(503);

    Http::assertNothingSent();
});

it("lists the member's chats, signed, with the member named", function () {
    $chats = ['conversations' => [['id' => HISTORY_CHAT_ID, 'title' => 'Standard vs Extended licence?', 'updated_at' => '2026-10-06T10:16:36Z']]];
    Http::fake(['agent.test/*' => Http::response($chats, 200)]);
    $user = User::factory()->create();

    $this->actingAs($user)->getJson(route('support.conversations.index'))
        ->assertOk()
        ->assertExactJson($chats)
        ->assertHeader('Cache-Control', 'no-store, private');

    Http::assertSent(fn (Request $request) => historySignedFor($request, 'GET', '/v1/conversations')
        && historyClaimsOf($request)['sub'] === (string) $user->id);
});

it('opens one chat by id, signed for that exact path', function () {
    $chat = ['id' => HISTORY_CHAT_ID, 'title' => 'Licences', 'messages' => [['role' => 'user', 'text' => 'hi', 'citations' => []]]];
    Http::fake(['agent.test/*' => Http::response($chat, 200)]);
    $user = User::factory()->create();

    $this->actingAs($user)->getJson(route('support.conversations.show', HISTORY_CHAT_ID))->assertOk()->assertExactJson($chat);

    Http::assertSent(fn (Request $request) => historySignedFor($request, 'GET', '/v1/conversations/'.HISTORY_CHAT_ID)
        && historyClaimsOf($request)['sub'] === (string) $user->id);
});

it('hides a chat with a signed DELETE and passes the empty success on', function () {
    Http::fake(['agent.test/*' => Http::response('', 204)]);
    $user = User::factory()->create();

    $this->actingAs($user)->deleteJson(route('support.conversations.destroy', HISTORY_CHAT_ID))->assertNoContent();

    Http::assertSent(fn (Request $request) => historySignedFor($request, 'DELETE', '/v1/conversations/'.HISTORY_CHAT_ID)
        && historyClaimsOf($request)['sub'] === (string) $user->id);
});

it('never lets the browser choose whose chats are asked for', function () {
    Http::fake(['agent.test/*' => Http::response(['conversations' => []], 200)]);
    $user = User::factory()->create();

    // A member asking for someone else's chats by adding their id gets the same call as anyone: no query string goes to the service
    $this->actingAs($user)->getJson(route('support.conversations.index', ['user_id' => 1, 'sub' => '1']))->assertOk();

    Http::assertSent(fn (Request $request) => $request->url() === 'http://agent.test/v1/conversations'
        && historyClaimsOf($request)['sub'] === (string) $user->id);
});

it('does not send an id that is not a UUID anywhere', function () {
    Http::fake();
    $user = User::factory()->create();

    $this->actingAs($user)->getJson('/support/conversations/not-a-uuid')->assertNotFound();
    $this->actingAs($user)->deleteJson('/support/conversations/../../v1/chat')->assertNotFound();

    Http::assertNothingSent();
});

it("passes on 'not found' as it is, and keeps its own failures private", function (int $agentStatus, array $agentBody, int $memberStatus, string $code) {
    Http::fake(['agent.test/*' => Http::response($agentBody, $agentStatus)]);

    $response = $this->actingAs(User::factory()->create())->getJson(route('support.conversations.show', HISTORY_CHAT_ID))
        ->assertStatus($memberStatus)
        ->assertJsonPath('error.code', $code);

    // Whatever the service said about why it refused us never reaches the member
    if ($memberStatus === 503) {
        expect($response->getContent())->not->toContain('signature')->not->toContain('boom');
    }
})->with([
    'a chat that is not theirs' => [404, ['error' => ['code' => 'conversation_not_found']], 404, 'conversation_not_found'],
    'our signature rejected' => [401, ['error' => ['code' => 'bad_signature']], 503, 'assistant_unavailable'],
    'the service crashed' => [500, ['error' => 'boom'], 503, 'assistant_unavailable'],
]);

it('says the assistant is unavailable when the service cannot be reached or is not configured', function () {
    $user = User::factory()->create();

    Http::fake(fn () => throw new ConnectionException('connection refused'));
    $this->actingAs($user)->getJson(route('support.conversations.index'))->assertStatus(503)->assertJsonPath('error.code', 'assistant_unavailable');

    config(['support.agent.hmac_secret' => null]);
    Http::fake();
    $this->actingAs($user)->getJson(route('support.conversations.index'))->assertStatus(503)->assertJsonPath('error.code', 'assistant_unavailable');
    Http::assertNothingSent();
});
