<?php

namespace App\Services\Support\Inbound;

/**
 * Checks the signature on a call from the support assistant. The format is the one in AgentRequestSigner, used in the other direction
 * and with its own secret, so a leak of the chat secret does not open the read API (or the reverse):
 *
 *   canonical = METHOD \n path?query \n timestamp \n nonce \n sha256_hex(body)
 *   signature = hex(HMAC_SHA256(secret, canonical))
 */
final class InboundRequestVerifier
{
    /** @param  array<string, string>  $keys  key id => secret */
    public function __construct(private readonly array $keys, private readonly int $maxSkewSeconds, private readonly RequestNonces $nonces) {}

    public static function fromConfig(): self
    {
        return new self((array) config('support.reads.hmac_keys'), (int) config('support.reads.max_skew'), app(RequestNonces::class));
    }

    /** @return string the id of the key that signed it
     *
     * @throws SupportRequestRejected
     */
    public function verify(string $method, string $pathAndQuery, string $body, ?string $keyId, ?string $timestamp, ?string $nonce, ?string $signature, ?int $now = null): string
    {
        if (! $keyId || ! $timestamp || ! $nonce || ! $signature) {
            throw new SupportRequestRejected('missing_signature');
        }

        $secret = $this->keys[$keyId] ?? null;
        if (! is_string($secret) || $secret === '') {
            throw new SupportRequestRejected('unknown_key');
        }

        if (! is_numeric($timestamp) || abs(($now ?? time()) - (float) $timestamp) > $this->maxSkewSeconds) {
            throw new SupportRequestRejected('stale_timestamp');
        }

        $canonical = implode("\n", [strtoupper($method), $pathAndQuery, $timestamp, $nonce, hash('sha256', $body)]);

        if (! hash_equals(hash_hmac('sha256', $canonical, $secret), $signature)) {
            throw new SupportRequestRejected('bad_signature');
        }

        // Only a correctly signed request may use up a nonce, so an outsider cannot fill the store
        if (! $this->nonces->claim($keyId.':'.$nonce, $this->maxSkewSeconds * 2)) {
            throw new SupportRequestRejected('replayed_request');
        }

        return $keyId;
    }
}
