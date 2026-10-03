<?php

namespace App\Services\Support;

use RuntimeException;

/**
 * Signs a request to the support assistant service so it can tell the request really came from this app, was not changed on the way,
 * and is not a replay. The format is shared with the service (app/auth/hmac.py in modelhub-support); change both together.
 *
 *   canonical = METHOD \n path?query \n timestamp \n nonce \n sha256_hex(body)
 *   signature = hex(HMAC_SHA256(secret, canonical))
 */
final class AgentRequestSigner
{
    public function __construct(private readonly string $keyId, private readonly string $secret) {}

    public static function fromConfig(): self
    {
        $secret = config('support.agent.hmac_secret');

        if (! is_string($secret) || $secret === '') {
            throw new RuntimeException('SUPPORT_HMAC_SECRET is not set.');
        }

        return new self((string) config('support.agent.hmac_key_id'), $secret);
    }

    /** @return array<string, string> the four headers to send */
    public function sign(string $method, string $pathAndQuery, string $body, ?int $timestamp = null, ?string $nonce = null): array
    {
        $timestamp = (string) ($timestamp ?? time());
        $nonce ??= bin2hex(random_bytes(16));

        $canonical = implode("\n", [strtoupper($method), $pathAndQuery, $timestamp, $nonce, hash('sha256', $body)]);

        return [
            'X-Support-Key-Id' => $this->keyId,
            'X-Support-Timestamp' => $timestamp,
            'X-Support-Nonce' => $nonce,
            'X-Support-Signature' => hash_hmac('sha256', $canonical, $this->secret),
        ];
    }
}
