<?php

use App\Models\User;
use App\Services\Support\AgentRequestSigner;
use App\Services\Support\UserContextMinter;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/*
 * The chat panel's endpoint and the proof it sends to the support assistant service: a signed request plus a short-lived claim that names
 * the signed-in member. The signing format is shared with modelhub-support (app/auth/hmac.py), so one test pins a vector made by that side.
 */

function base64UrlDecode(string $value): string
{
    return base64_decode(strtr($value, '-_', '+/'));
}

/** The streamed reply the service would send. */
function sse(): string
{
    return "event: conversation\r\ndata: {\"conversation_id\": \"c-1\"}\r\n\r\n"
        ."event: delta\r\ndata: {\"text\": \"Hello there\"}\r\n\r\n"
        ."event: done\r\ndata: {\"message_id\": \"m-1\"}\r\n\r\n";
}

beforeEach(function () {
    $keypair = sodium_crypto_sign_keypair();
    $this->secretKey = sodium_crypto_sign_secretkey($keypair);
    $this->publicKey = sodium_crypto_sign_publickey($keypair);

    config([
        'support.enabled' => true,
        'support.agent.url' => 'http://agent.test',
        'support.agent.hmac_key_id' => 'current',
        'support.agent.hmac_secret' => 'test-secret',
        'support.agent.context_private_key' => base64_encode($this->secretKey),
    ]);
});

it('signs requests exactly as the service expects', function () {
    // Vector produced by the service's own sign() in modelhub-support: if either side changes the format, this fails.
    $headers = (new AgentRequestSigner('current', 'vector-secret'))->sign('POST', '/v1/chat', '{"message":"hello"}', 1800000000, 'nonce-1');

    expect($headers['X-Support-Signature'])->toBe('ee410e90f05a621a595965f394ec2f48a83ca8d24c8d03e91ad9ec1f7e5150d3')
        ->and($headers['X-Support-Key-Id'])->toBe('current')
        ->and($headers['X-Support-Timestamp'])->toBe('1800000000')
        ->and($headers['X-Support-Nonce'])->toBe('nonce-1');
});

it('mints a short-lived Ed25519 claim that names the member and the session', function () {
    $user = User::factory()->create();

    $token = (new UserContextMinter($this->secretKey))->mint($user, 'session-abc', 1800000000);
    [$header, $payload, $signature] = explode('.', $token);
    $claims = json_decode(base64UrlDecode($payload), true);

    expect(json_decode(base64UrlDecode($header), true))->toBe(['alg' => 'EdDSA', 'typ' => 'JWT'])
        ->and(sodium_crypto_sign_verify_detached(base64UrlDecode($signature), "$header.$payload", $this->publicKey))->toBeTrue()
        ->and($claims['sub'])->toBe((string) $user->id)
        ->and($claims['iss'])->toBe('modelhub')
        ->and($claims['aud'])->toBe('support-agent')
        ->and($claims['exp'] - $claims['iat'])->toBe(60)
        ->and($claims['sid'])->toBe(hash('sha256', 'session-abc'))
        ->and($claims)->not->toHaveKey('roles')
        ->and($claims)->not->toHaveKey('email');
});

it('refuses to mint with a missing or invalid key', function () {
    config(['support.agent.context_private_key' => 'not-a-key']);

    UserContextMinter::fromConfig();
})->throws(RuntimeException::class);

it('gives the service the public half as a PEM it can read', function () {
    $pem = UserContextMinter::publicKeyPem($this->secretKey);
    $der = base64_decode(preg_replace('/-----[A-Z ]+-----|\s/', '', $pem));

    expect($pem)->toStartWith("-----BEGIN PUBLIC KEY-----\n")
        ->and(strlen($der))->toBe(44)
        ->and(substr($der, 12))->toBe($this->publicKey);
});

it('prints the keys once and saves nothing', function () {
    $this->artisan('support:generate-keys')
        ->expectsOutputToContain('SUPPORT_HMAC_SECRET=')
        ->expectsOutputToContain('SUPPORT_CONTEXT_PRIVATE_KEY=')
        ->expectsOutputToContain('APP_USER_CONTEXT_PUBLIC_KEY=-----BEGIN PUBLIC KEY-----')
        ->assertSuccessful();
});

it('is closed to anyone who is not signed in', function () {
    Http::fake();

    $this->postJson(route('support.chat'), ['message' => 'hi'])->assertUnauthorized();

    Http::assertNothingSent();
});

it('sends nothing anywhere while the kill switch is off', function () {
    Http::fake();
    config(['support.enabled' => false]);

    $this->actingAs(User::factory()->create())
        ->postJson(route('support.chat'), ['message' => 'hi'])
        ->assertStatus(503)
        ->assertJsonPath('error.code', 'assistant_disabled');

    Http::assertNothingSent();
});

it('forwards the message signed and with the member named, and streams the answer back', function () {
    Http::fake(['agent.test/*' => Http::response(sse(), 200, ['Content-Type' => 'text/event-stream'])]);
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('support.chat'), ['message' => 'How do licences work?']);

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toStartWith('text/event-stream')
        ->and($response->streamedContent())->toContain('event: delta')->toContain('Hello there');

    Http::assertSent(function (Request $request) use ($user) {
        $body = $request->body();
        $headers = fn (string $name) => $request->header($name)[0] ?? null;

        // The signature is what the service will recompute from the same parts
        $canonical = implode("\n", ['POST', '/v1/chat', $headers('X-Support-Timestamp'), $headers('X-Support-Nonce'), hash('sha256', $body)]);
        [$h, $p, $s] = explode('.', $headers('X-Support-User-Context'));
        $claims = json_decode(base64UrlDecode($p), true);

        return $request->url() === 'http://agent.test/v1/chat'
            && $headers('X-Support-Key-Id') === 'current'
            && hash_equals(hash_hmac('sha256', $canonical, 'test-secret'), $headers('X-Support-Signature'))
            && $claims['sub'] === (string) $user->id
            && json_decode($body, true) === ['message' => 'How do licences work?'];
    });
});

it('never forwards who the member is from the request body', function () {
    Http::fake(['agent.test/*' => Http::response(sse(), 200)]);
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('support.chat'), ['message' => 'hi', 'user_id' => 999, 'sub' => '999', 'conversation_id' => '8b1f4c3e-5d7a-4e0b-9a62-1c2d3e4f5a6b'])->streamedContent();

    Http::assertSent(fn (Request $request) => json_decode($request->body(), true) === [
        'message' => 'hi',
        'conversation_id' => '8b1f4c3e-5d7a-4e0b-9a62-1c2d3e4f5a6b',
    ]);
});

it('rejects bad messages before anything is sent', function (array $payload) {
    Http::fake();

    $this->actingAs(User::factory()->create())->postJson(route('support.chat'), $payload)->assertUnprocessable();

    Http::assertNothingSent();
})->with([
    'missing' => [[]],
    'blank' => [['message' => '   ']],
    'too long' => [['message' => str_repeat('x', 4001)]],
    'bad conversation id' => [['message' => 'hi', 'conversation_id' => 'not-a-uuid']],
    'bad page context' => [['message' => 'hi', 'page_context' => ['route_key' => 'Has Spaces!']]],
]);

it('passes on answers the member can act on, and keeps its own failures private', function (int $agentStatus, array $agentBody, int $memberStatus, string $code) {
    Http::fake(['agent.test/*' => Http::response($agentBody, $agentStatus)]);

    $response = $this->actingAs(User::factory()->create())->postJson(route('support.chat'), ['message' => 'hi'])
        ->assertStatus($memberStatus)
        ->assertJsonPath('error.code', $code);

    // Whatever the service said about why it refused us never reaches the member
    if ($memberStatus === 503) {
        expect($response->getContent())->not->toContain('signature')->not->toContain('boom');
    }
})->with([
    'a message that looks like a PIN' => [422, ['error' => ['code' => 'sensitive_content', 'message' => 'That looks like a PIN']], 422, 'sensitive_content'],
    'a conversation that is not theirs' => [404, ['error' => ['code' => 'conversation_not_found']], 404, 'conversation_not_found'],
    'our signature rejected' => [401, ['error' => ['code' => 'bad_signature']], 503, 'assistant_unavailable'],
    'the service crashed' => [500, ['error' => 'boom'], 503, 'assistant_unavailable'],
]);

it('says the assistant is unavailable when the service cannot be reached or is not configured', function () {
    $user = User::factory()->create();

    Http::fake(fn () => throw new ConnectionException('connection refused'));
    $this->actingAs($user)->postJson(route('support.chat'), ['message' => 'hi'])
        ->assertStatus(503)->assertJsonPath('error.code', 'assistant_unavailable');

    config(['support.agent.hmac_secret' => null]);
    Http::fake();
    $this->actingAs($user)->postJson(route('support.chat'), ['message' => 'hi'])
        ->assertStatus(503)->assertJsonPath('error.code', 'assistant_unavailable');
    Http::assertNothingSent();
});
