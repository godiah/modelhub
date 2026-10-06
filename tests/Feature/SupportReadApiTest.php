<?php

use App\Models\SupportReadAudit;
use App\Models\User;
use App\Services\Support\AgentRequestSigner;
use App\Services\Support\Inbound\RequestNonces;
use App\Services\Support\Inbound\SupportRequestRejected;
use App\Services\Support\SupportAgentClient;
use App\Services\Support\UserContextMinter;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/*
 * The read API the support assistant calls (/api/support/v1): every call must pass the switch, the caller's address, the request signature,
 * a read claim naming an active member in the current stage, and a per-member limit. Each failure looks the same to the caller.
 * The endpoint here is `ping`; the real reads (withdrawals, payments, ...) sit behind exactly these checks.
 */

const READS_PATH = '/api/support/v1/ping';

/** Headers for a call as the assistant would make it. */
function readCall(string $path = READS_PATH, array $over = []): array
{
    $secret = $over['secret'] ?? 'reads-secret';
    $signed = (new AgentRequestSigner($over['key_id'] ?? 'current', $secret))->sign(
        $over['method'] ?? 'GET',
        $over['signed_path'] ?? $path,
        '',
        $over['timestamp'] ?? null,
        $over['nonce'] ?? null,
    );

    $headers = $signed + ['X-Request-ID' => 'req-1'];

    if (($over['claim'] ?? true) !== false) {
        $headers['X-Support-Read-Claim'] = $over['claim'];
    }

    return $headers;
}

function claimFor(User $user, ?int $now = null): string
{
    return UserContextMinter::fromConfig()->mintRead($user, $now);
}

function b64u(string $raw): string
{
    return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
}

beforeEach(function () {
    $keypair = sodium_crypto_sign_keypair();
    $this->secretKey = sodium_crypto_sign_secretkey($keypair);
    $this->member = User::factory()->create();

    config([
        'support.agent.context_private_key' => base64_encode($this->secretKey),
        'support.agent.hmac_secret' => 'chat-secret',
        'support.reads.enabled' => true,
        'support.reads.stage' => 'pilot',
        'support.reads.pilot_member_ids' => [$this->member->id],
        'support.reads.allowed_ips' => ['127.0.0.1'],
        'support.reads.hmac_keys' => ['current' => 'reads-secret', 'previous' => 'old-secret'],
        'support.reads.throttle_per_minute' => 60,
    ]);

    RateLimiter::clear('support-reads:member:'.$this->member->id);
});

function call(object $test, array $headers, string $path = READS_PATH)
{
    // a plain GET: getJson() would send a "[]" body, which the assistant never does and the signature covers
    return $test->withHeaders($headers)->get($path);
}

it('is switched off by default and then looks like nothing is there', function () {
    config(['support.reads.enabled' => false]);

    call($this, readCall(over: ['claim' => claimFor($this->member)]))->assertNotFound()->assertExactJson(['error' => 'not_found']);
    expect(SupportReadAudit::count())->toBe(0);
});

it('answers a correctly signed call that names an active pilot member, and keeps it out of caches', function () {
    $response = call($this, readCall(over: ['claim' => claimFor($this->member)]));

    $response->assertOk()->assertJsonPath('status', 'ok')->assertHeader('X-Request-ID', 'req-1');
    expect($response->headers->get('Cache-Control'))->toContain('no-store');
});

it('records who read what, never the values', function () {
    call($this, readCall(over: ['claim' => claimFor($this->member)]))->assertOk();

    $audit = SupportReadAudit::sole();
    expect($audit->principal)->toBe('current')
        ->and($audit->user_id)->toBe($this->member->id)
        ->and($audit->endpoint)->toBe('support.api.ping')
        ->and($audit->status)->toBe(200)
        ->and($audit->outcome)->toBe('ok')
        ->and($audit->request_id)->toBe('req-1')
        ->and(array_keys($audit->getAttributes()))->not->toContain('path', 'query', 'payload');
});

/** The caller sees one answer; the log must show the real reason, or a test could pass for the wrong one. */
function expectRefusalLogged(string $reason): void
{
    Log::shouldHaveReceived('warning')->withArgs(fn ($message, $context = []) => $message === 'Support read refused' && ($context['reason'] ?? null) === $reason)->once();
}

it('refuses every kind of bad signature with the same answer, so the caller learns nothing about which check failed', function (array $over, string $reason) {
    Log::spy();
    $headers = readCall(over: $over + ['claim' => claimFor($this->member)]);

    call($this, $headers)->assertUnauthorized()->assertExactJson(['error' => 'unauthorized']);

    expectRefusalLogged($reason);
})->with([
    'wrong secret' => [['secret' => 'not-the-secret'], 'bad_signature'],
    'unknown key id' => [['key_id' => 'nobody'], 'unknown_key'],
    'signed for another path' => [['signed_path' => '/api/support/v1/other'], 'bad_signature'],
    'signed as a POST' => [['method' => 'POST'], 'bad_signature'],
    'too old' => [['timestamp' => time() - 3600], 'stale_timestamp'],
    'from the future' => [['timestamp' => time() + 3600], 'stale_timestamp'],
    'the chat secret' => [['secret' => 'chat-secret'], 'bad_signature'],
]);

it('refuses a call with no signature at all', function () {
    Log::spy();
    call($this, ['X-Support-Read-Claim' => claimFor($this->member)])->assertUnauthorized();
    expectRefusalLogged('missing_signature');
    expect(SupportReadAudit::count())->toBe(0); // unproved callers are logged, not recorded
});

it('refuses a replayed request, and the replay is the same answer as any other refusal', function () {
    $headers = readCall(over: ['claim' => claimFor($this->member), 'nonce' => 'fixed-nonce']);

    call($this, $headers)->assertOk();
    Log::spy();
    call($this, $headers)->assertUnauthorized()->assertExactJson(['error' => 'unauthorized']);
    expectRefusalLogged('replayed_request');
});

it('binds the signature to the query string too', function () {
    Log::spy();
    $headers = readCall(over: ['claim' => claimFor($this->member), 'signed_path' => READS_PATH.'?x=1']);

    call($this, $headers, READS_PATH.'?x=2')->assertUnauthorized();
    expectRefusalLogged('bad_signature');
});

it('accepts the previous key while keys are being rotated, and stops when it is removed', function () {
    $headers = readCall(over: ['claim' => claimFor($this->member), 'key_id' => 'previous', 'secret' => 'old-secret']);

    call($this, $headers)->assertOk();
    expect(SupportReadAudit::sole()->principal)->toBe('previous');

    config(['support.reads.hmac_keys' => ['current' => 'reads-secret']]);
    call($this, readCall(over: ['claim' => claimFor($this->member), 'key_id' => 'previous', 'secret' => 'old-secret']))->assertUnauthorized();
});

it('refuses the call when the replay store is down, instead of letting it through', function () {
    $this->app->bind(RequestNonces::class, fn () => new class extends RequestNonces
    {
        public function claim(string $key, int $ttlSeconds): bool
        {
            throw new SupportRequestRejected('nonce_store_unavailable', 503);
        }
    });

    call($this, readCall(over: ['claim' => claimFor($this->member)]))->assertStatus(503)->assertExactJson(['error' => 'unavailable']);
});

it('only trusts the connecting address, never a forwarded header', function () {
    Log::spy();
    config(['support.reads.allowed_ips' => ['203.0.113.7']]);

    $headers = readCall(over: ['claim' => claimFor($this->member)]) + ['X-Forwarded-For' => '203.0.113.7', 'X-Real-IP' => '203.0.113.7'];

    call($this, $headers)->assertUnauthorized();
    expectRefusalLogged('address_not_allowed');
});

it('allows a block of addresses, and denies everything when none are configured outside local', function () {
    config(['support.reads.allowed_ips' => ['127.0.0.0/8']]);
    call($this, readCall(over: ['claim' => claimFor($this->member)]))->assertOk();

    config(['support.reads.allowed_ips' => []]);
    call($this, readCall(over: ['claim' => claimFor($this->member)]))->assertUnauthorized();
});

it('refuses a missing, tampered, expired or foreign claim', function (string $kind, string $reason) {
    Log::spy();
    $good = claimFor($this->member);
    [$h, $p, $s] = explode('.', $good);

    $claim = match ($kind) {
        'missing' => false,
        'tampered subject' => $h.'.'.b64u(json_encode(['sub' => '999'] + json_decode(base64_decode(strtr($p, '-_', '+/')), true))).'.'.$s,
        'bad signature' => $h.'.'.$p.'.'.b64u(random_bytes(64)),
        'expired' => claimFor($this->member, time() - 3600),
        'a chat claim' => UserContextMinter::fromConfig()->mint($this->member, 'session-id'),
        'alg none' => b64u('{"alg":"none"}').'.'.$p.'.',
        'garbage' => 'not-a-token',
    };

    $over = $claim === false ? ['claim' => false] : ['claim' => $claim];

    call($this, readCall(over: $over))->assertUnauthorized()->assertExactJson(['error' => 'unauthorized']);
    expectRefusalLogged($reason);
})->with([
    'missing' => ['missing', 'missing_claim'],
    'tampered subject' => ['tampered subject', 'invalid_claim'],
    'bad signature' => ['bad signature', 'invalid_claim'],
    'expired' => ['expired', 'expired_claim'],
    'a chat claim' => ['a chat claim', 'wrong_claim_audience'],
    'alg none' => ['alg none', 'invalid_claim'],
    'garbage' => ['garbage', 'missing_claim'],
]);

it('refuses a claim signed with some other key', function () {
    Log::spy();
    $other = new UserContextMinter(sodium_crypto_sign_secretkey(sodium_crypto_sign_keypair()));

    call($this, readCall(over: ['claim' => $other->mintRead($this->member)]))->assertUnauthorized();
    expectRefusalLogged('invalid_claim');
});

it('refuses a claim that lives longer than a minute', function () {
    Log::spy();
    $long = new UserContextMinter($this->secretKey, ttlSeconds: 3600);

    call($this, readCall(over: ['claim' => $long->mintRead($this->member)]))->assertUnauthorized();
    expectRefusalLogged('invalid_claim');
});

it('refuses a suspended member and an unknown one in the same words', function () {
    Log::spy();
    $this->member->forceFill(['suspended_at' => now()])->save();
    call($this, readCall(over: ['claim' => claimFor($this->member)]))->assertUnauthorized()->assertExactJson(['error' => 'unauthorized']);

    $ghost = User::factory()->make(['id' => 987654]);
    call($this, readCall(over: ['claim' => claimFor($ghost)]))->assertUnauthorized()->assertExactJson(['error' => 'unauthorized']);
    Log::shouldHaveReceived('warning')->withArgs(fn ($m, $c = []) => ($c['reason'] ?? null) === 'member_not_active')->twice();
});

it('keeps non-pilot members out while the stage is pilot, lets everyone in at all, and closes on an unknown stage', function () {
    $other = User::factory()->create();

    call($this, readCall(over: ['claim' => claimFor($other)]))->assertForbidden();

    config(['support.reads.stage' => 'all']);
    call($this, readCall(over: ['claim' => claimFor($other)]))->assertOk();

    config(['support.reads.stage' => 'typo']);
    call($this, readCall(over: ['claim' => claimFor($other)]))->assertForbidden();
});

it('limits each member, not each address', function () {
    config(['support.reads.throttle_per_minute' => 2]);

    call($this, readCall(over: ['claim' => claimFor($this->member)]))->assertOk();
    call($this, readCall(over: ['claim' => claimFor($this->member)]))->assertOk();
    call($this, readCall(over: ['claim' => claimFor($this->member)]))->assertStatus(429)->assertHeader('Retry-After');

    // another member, same caller address: not affected
    config(['support.reads.stage' => 'all']);
    call($this, readCall(over: ['claim' => claimFor(User::factory()->create())]))->assertOk();
    expect(SupportReadAudit::where('outcome', 'throttled')->count())->toBe(1);
});

it('only answers GET', function () {
    $this->withHeaders(readCall(over: ['claim' => claimFor($this->member), 'method' => 'POST']))->post(READS_PATH)->assertStatus(405);
});

it('mints a read claim that is narrow: its own audience and scope, the member only, a minute', function () {
    $claims = json_decode(base64_decode(strtr(explode('.', claimFor($this->member, 1800000000))[1], '-_', '+/')), true);

    expect($claims)->toMatchArray([
        'iss' => 'modelhub', 'aud' => 'support-reads', 'scope' => 'support:read:self',
        'sub' => (string) $this->member->id, 'iat' => 1800000000, 'exp' => 1800000060,
    ])->and($claims)->not->toHaveKey('sid');

    $chat = json_decode(base64_decode(strtr(explode('.', UserContextMinter::fromConfig()->mint($this->member, 's', 1800000000))[1], '-_', '+/')), true);
    expect($chat['aud'])->toBe('support-agent')->and($chat)->not->toHaveKey('scope');
});

it('sends the read claim with a chat message only when reads are on', function () {
    config(['support.enabled' => true, 'support.agent.url' => 'http://agent.test', 'support.agent.hmac_key_id' => 'current']);
    Http::fake(['agent.test/*' => Http::response("event: done\r\ndata: {}\r\n\r\n", 200, ['Content-Type' => 'text/event-stream'])]);

    $client = new SupportAgentClient;

    config(['support.reads.enabled' => false]);
    $client->chat($this->member, 'session-1', ['message' => 'hi']);
    config(['support.reads.enabled' => true]);
    $client->chat($this->member, 'session-1', ['message' => 'hi']);

    $sent = Http::recorded()->map(fn ($pair) => $pair[0])->values();
    expect($sent[0]->hasHeader('X-Support-Read-Claim'))->toBeFalse()
        ->and($sent[1]->hasHeader('X-Support-Read-Claim'))->toBeTrue()
        ->and($sent[1]->header('X-Support-User-Context'))->not->toBe($sent[1]->header('X-Support-Read-Claim'));
});

it('checks the audience, the scope and the issuer each on its own', function (array $change) {
    Log::spy();
    $now = time();
    $claims = $change + [
        'iss' => 'modelhub', 'aud' => 'support-reads', 'scope' => 'support:read:self',
        'sub' => (string) $this->member->id, 'jti' => 'j1', 'iat' => $now, 'exp' => $now + 60,
    ];
    $input = b64u(json_encode(['alg' => 'EdDSA', 'typ' => 'JWT'])).'.'.b64u(json_encode($claims));
    $token = $input.'.'.b64u(sodium_crypto_sign_detached($input, $this->secretKey));

    call($this, readCall(over: ['claim' => $token]))->assertUnauthorized();
    expectRefusalLogged('wrong_claim_audience');
})->with([
    'right scope, chat audience' => [['aud' => 'support-agent']],
    'right audience, another scope' => [['scope' => 'support:read:all']],
    'right audience, no scope' => [['scope' => null]],
    'another issuer' => [['iss' => 'someone-else']],
]);

it('accepts a hand-made claim that is right on every count, so the cases above fail for the one thing changed', function () {
    $now = time();
    $claims = ['iss' => 'modelhub', 'aud' => 'support-reads', 'scope' => 'support:read:self', 'sub' => (string) $this->member->id, 'jti' => 'j1', 'iat' => $now, 'exp' => $now + 60];
    $input = b64u(json_encode(['alg' => 'EdDSA', 'typ' => 'JWT'])).'.'.b64u(json_encode($claims));

    call($this, readCall(over: ['claim' => $input.'.'.b64u(sodium_crypto_sign_detached($input, $this->secretKey))]))->assertOk();
});
