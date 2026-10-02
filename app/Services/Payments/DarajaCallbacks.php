<?php

namespace App\Services\Payments;

use Illuminate\Http\Request;
use RuntimeException;

/**
 * The URLs Safaricom posts results to. Daraja signs nothing, so each URL carries a secret token (PAYMENTS_DARAJA_CALLBACK_SECRET) that
 * only we and Safaricom know; a request without it is refused. Daraja also rejects URLs containing words like "mpesa" or "safaricom",
 * which is why the gateway is called "daraja" in these paths.
 */
final class DarajaCallbacks
{
    public const GATEWAY = 'daraja';

    public static function secret(): string
    {
        $secret = (string) config('payments.daraja.callback_secret');

        if (strlen($secret) < 16) {
            throw new RuntimeException('Set PAYMENTS_DARAJA_CALLBACK_SECRET to a random string of at least 16 characters to use the Daraja gateway.');
        }

        return $secret;
    }

    public static function paymentUrl(): string
    {
        return route('webhooks.payments', ['name' => self::GATEWAY, 'token' => self::secret()]);
    }

    public static function payoutUrl(): string
    {
        return route('webhooks.payouts', ['name' => self::GATEWAY, 'token' => self::secret()]);
    }

    public static function payoutTimeoutUrl(): string
    {
        return route('webhooks.payouts.timeout', ['name' => self::GATEWAY, 'token' => self::secret()]);
    }

    public static function authorized(Request $request): bool
    {
        return hash_equals(self::secret(), (string) $request->query('token'));
    }
}
