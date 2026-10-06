<?php

namespace App\Services\Support\Inbound;

use App\Models\User;

/**
 * Checks the read claim the agent sends back: the one ModelHub minted (UserContextMinter::mintRead) when the member sent their chat
 * message. It has its own audience and scope, so a chat claim is no use here and this one is no use for chat, and it names the member
 * only. It lives for a minute, which is the freshness guarantee: it is not tied to a live session (the session id is only hashed in it).
 */
final class ReadClaimVerifier
{
    public function __construct(
        private readonly string $publicKey,
        private readonly string $issuer,
        private readonly string $audience,
        private readonly string $scope,
        private readonly int $maxLifetime = 60,
    ) {}

    public static function fromConfig(): self
    {
        $encoded = config('support.agent.context_private_key');
        $secretKey = is_string($encoded) && $encoded !== '' ? base64_decode($encoded, true) : false;

        if ($secretKey === false || strlen($secretKey) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
            throw new SupportRequestRejected('claim_key_not_configured', 503);
        }

        return new self(
            sodium_crypto_sign_publickey_from_secretkey($secretKey),
            (string) config('support.agent.context_issuer'),
            (string) config('support.reads.claim_audience'),
            (string) config('support.reads.claim_scope'),
            (int) config('support.agent.context_ttl'),
        );
    }

    /** @throws SupportRequestRejected */
    public function member(?string $token, ?int $now = null): User
    {
        $now ??= time();
        $parts = $token ? explode('.', $token) : [];

        if (count($parts) !== 3) {
            throw new SupportRequestRejected('missing_claim');
        }

        [$h, $p, $s] = $parts;
        $header = json_decode(self::decode($h), true);
        $claims = json_decode(self::decode($p), true);
        $signature = self::decode($s);

        if (! is_array($header) || ! is_array($claims) || ($header['alg'] ?? null) !== 'EdDSA' || strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES) {
            throw new SupportRequestRejected('invalid_claim');
        }

        if (! sodium_crypto_sign_verify_detached($signature, $h.'.'.$p, $this->publicKey)) {
            throw new SupportRequestRejected('invalid_claim');
        }

        $exp = $claims['exp'] ?? null;
        $iat = $claims['iat'] ?? null;
        if (! is_int($exp) || ! is_int($iat) || ! is_string($claims['jti'] ?? null)) {
            throw new SupportRequestRejected('invalid_claim');
        }
        if ($exp <= $now) {
            throw new SupportRequestRejected('expired_claim');
        }
        // A long-lived claim defeats the point of minting one per request
        if ($exp - $iat > $this->maxLifetime || $iat > $now + 5) {
            throw new SupportRequestRejected('invalid_claim');
        }
        if (($claims['iss'] ?? null) !== $this->issuer || ($claims['aud'] ?? null) !== $this->audience || ($claims['scope'] ?? null) !== $this->scope) {
            throw new SupportRequestRejected('wrong_claim_audience');
        }

        $sub = $claims['sub'] ?? null;
        $member = is_string($sub) && ctype_digit($sub) ? User::find((int) $sub) : null;

        // The same refusal for a member who does not exist and one who is suspended
        if ($member === null || $member->isSuspended()) {
            throw new SupportRequestRejected('member_not_active');
        }

        return $member;
    }

    private static function decode(string $value): string
    {
        return (string) base64_decode(strtr($value, '-_', '+/'), true);
    }
}
