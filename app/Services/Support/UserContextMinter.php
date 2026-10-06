<?php

namespace App\Services\Support;

use App\Models\User;
use RuntimeException;

/**
 * Mints the short-lived claim that tells the support assistant which member is asking: "this is member N, in this session, for the next
 * minute". It is signed with an Ed25519 private key that only this app holds, so the service can check a claim but never make one.
 *
 * Only ever built from an authenticated session (the controller sits behind `auth` and the session rules). Because it expires in
 * seconds, a suspended member, a password change or a closed session stops working almost at once. No roles go in it: they go stale.
 */
final class UserContextMinter
{
    public function __construct(
        private readonly string $secretKey,
        private readonly string $issuer = 'modelhub',
        private readonly string $audience = 'support-agent',
        private readonly int $ttlSeconds = 60,
        private readonly string $readAudience = 'support-reads',
        private readonly string $readScope = 'support:read:self',
    ) {}

    public static function fromConfig(): self
    {
        $encoded = config('support.agent.context_private_key');
        $secretKey = is_string($encoded) && $encoded !== '' ? base64_decode($encoded, true) : false;

        if ($secretKey === false || strlen($secretKey) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
            throw new RuntimeException('SUPPORT_CONTEXT_PRIVATE_KEY is missing or not a valid Ed25519 secret key. Run `php artisan support:generate-keys`.');
        }

        return new self(
            $secretKey,
            (string) config('support.agent.context_issuer'),
            (string) config('support.agent.context_audience'),
            (int) config('support.agent.context_ttl'),
            (string) config('support.reads.claim_audience'),
            (string) config('support.reads.claim_scope'),
        );
    }

    public function mint(User $user, string $sessionId, ?int $now = null): string
    {
        $now ??= time();

        $header = ['alg' => 'EdDSA', 'typ' => 'JWT'];
        $claims = [
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'sub' => (string) $user->getKey(),
            // A hash of the session, so a different or ended session cannot reuse the claim
            'sid' => hash('sha256', $sessionId),
            'jti' => bin2hex(random_bytes(16)),
            'iat' => $now,
            'exp' => $now + $this->ttlSeconds,
        ];

        return $this->sign($header, $claims);
    }

    /**
     * The claim the assistant sends back to read this member's own records. A different audience and a scope of its own, so a chat claim
     * cannot be used against the read API and this one cannot be used for chat; no session hash, because an inbound call cannot check it.
     */
    public function mintRead(User $user, ?int $now = null): string
    {
        $now ??= time();

        return $this->sign(['alg' => 'EdDSA', 'typ' => 'JWT'], [
            'iss' => $this->issuer,
            'aud' => $this->readAudience,
            'scope' => $this->readScope,
            'sub' => (string) $user->getKey(),
            'jti' => bin2hex(random_bytes(16)),
            'iat' => $now,
            'exp' => $now + $this->ttlSeconds,
        ]);
    }

    /** @param  array<string, mixed>  $header
     * @param  array<string, mixed>  $claims */
    private function sign(array $header, array $claims): string
    {
        $signingInput = self::base64Url(json_encode($header, JSON_UNESCAPED_SLASHES)).'.'.self::base64Url(json_encode($claims, JSON_UNESCAPED_SLASHES));

        return $signingInput.'.'.self::base64Url(sodium_crypto_sign_detached($signingInput, $this->secretKey));
    }

    /** The public half as a PEM, in the form the service's APP_USER_CONTEXT_PUBLIC_KEY expects. */
    public static function publicKeyPem(string $secretKey): string
    {
        // The fixed prefix of an Ed25519 SubjectPublicKeyInfo, followed by the 32-byte key.
        $der = hex2bin('302a300506032b6570032100').sodium_crypto_sign_publickey_from_secretkey($secretKey);

        return "-----BEGIN PUBLIC KEY-----\n".chunk_split(base64_encode($der), 64, "\n")."-----END PUBLIC KEY-----\n";
    }

    private static function base64Url(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }
}
