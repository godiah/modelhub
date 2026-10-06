<?php

/*
 * Shared by the read API tests: calls made the way the support assistant makes them, and the setup every one of them needs.
 */

use App\Models\User;
use App\Services\Support\AgentRequestSigner;
use App\Services\Support\UserContextMinter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

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

/** Switches the read API on for a test, with one pilot member, and returns that member. */
function setUpReadApi(object $test): void
{
    $keypair = sodium_crypto_sign_keypair();
    $test->secretKey = sodium_crypto_sign_secretkey($keypair);
    $test->member = User::factory()->create();

    config([
        'support.agent.context_private_key' => base64_encode($test->secretKey),
        'support.agent.hmac_secret' => 'chat-secret',
        'support.reads.enabled' => true,
        'support.reads.stage' => 'pilot',
        'support.reads.pilot_member_ids' => [$test->member->id],
        'support.reads.allowed_ips' => ['127.0.0.1'],
        'support.reads.hmac_keys' => ['current' => 'reads-secret', 'previous' => 'old-secret'],
        'support.reads.throttle_per_minute' => 60,
    ]);

    RateLimiter::clear('support-reads:member:'.$test->member->id);
}

function call(object $test, array $headers, string $path = READS_PATH)
{
    // a plain GET: getJson() would send a "[]" body, which the assistant never does and the signature covers
    // flushHeaders: the test client keeps headers between calls, so a claim from an earlier call would otherwise ride along on a later one
    return $test->flushHeaders()->withHeaders($headers)->get($path);
}

/** The caller sees one answer; the log must show the real reason, or a test could pass for the wrong one. */
function expectRefusalLogged(string $reason): void
{
    Log::shouldHaveReceived('warning')->withArgs(fn ($message, $context = []) => $message === 'Support read refused' && ($context['reason'] ?? null) === $reason)->once();
}
