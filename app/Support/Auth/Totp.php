<?php

namespace App\Support\Auth;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * Time-based one-time passwords (RFC 6238) as authenticator apps (Google Authenticator, Authy, 1Password...) use them:
 * SHA-1, 6 digits, 30-second steps. A code is only accepted once: callers pass the last step they accepted.
 */
final class Totp
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    private const PERIOD = 30;

    public static function generateSecret(): string
    {
        return self::base32Encode(random_bytes(20));
    }

    /** The code for one 30-second step. */
    public static function at(string $secret, int $step): string
    {
        $hash = hash_hmac('sha1', pack('J', $step), self::base32Decode($secret), true);
        $offset = ord($hash[19]) & 0x0F;
        $binary = ((ord($hash[$offset]) & 0x7F) << 24) | (ord($hash[$offset + 1]) << 16) | (ord($hash[$offset + 2]) << 8) | ord($hash[$offset + 3]);

        return str_pad((string) ($binary % 1_000_000), 6, '0', STR_PAD_LEFT);
    }

    public static function step(?int $time = null): int
    {
        return intdiv($time ?? time(), self::PERIOD);
    }

    /**
     * The step a code belongs to, or null if it is wrong. One step either side of now is accepted for clock drift, and a step at or
     * before $after (the last one used) never is, so a code cannot be replayed.
     */
    public static function verify(string $secret, string $code, ?int $after = null, ?int $time = null): ?int
    {
        $code = preg_replace('/\s+/', '', $code);

        if (! preg_match('/^\d{6}$/', $code)) {
            return null;
        }

        foreach ([-1, 0, 1] as $drift) {
            $step = self::step($time) + $drift;

            if (($after === null || $step > $after) && hash_equals(self::at($secret, $step), $code)) {
                return $step;
            }
        }

        return null;
    }

    /** What the QR code holds: the app reads the secret, the issuer and the account label from it. */
    public static function uri(string $secret, string $account, string $issuer): string
    {
        return 'otpauth://totp/'.rawurlencode($issuer.':'.$account).'?'.http_build_query(['secret' => $secret, 'issuer' => $issuer, 'algorithm' => 'SHA1', 'digits' => 6, 'period' => self::PERIOD], '', '&', PHP_QUERY_RFC3986);
    }

    /** The QR code as an inline SVG, drawn on the server so the secret never goes through a third party. */
    public static function qrSvg(string $uri): string
    {
        $svg = (new Writer(new ImageRenderer(new RendererStyle(192, 1), new SvgImageBackEnd)))->writeString($uri);

        return trim(preg_replace('/^<\?xml[^>]*\?>\s*/', '', $svg));
    }

    /** Groups of four, which is easier to type by hand. */
    public static function formatSecret(string $secret): string
    {
        return trim(chunk_split($secret, 4, ' '));
    }

    private static function base32Encode(string $bytes): string
    {
        $bits = '';
        foreach (str_split($bytes) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }

        return collect(str_split($bits, 5))->map(fn ($chunk) => self::ALPHABET[bindec(str_pad($chunk, 5, '0'))])->implode('');
    }

    private static function base32Decode(string $encoded): string
    {
        $bits = '';
        foreach (str_split(strtoupper(preg_replace('/[\s=-]/', '', $encoded))) as $char) {
            $bits .= str_pad(decbin((int) strpos(self::ALPHABET, $char)), 5, '0', STR_PAD_LEFT);
        }

        return collect(str_split($bits, 8))->filter(fn ($byte) => strlen($byte) === 8)->map(fn ($byte) => chr(bindec($byte)))->implode('');
    }
}
