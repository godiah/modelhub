<?php

namespace App\Contracts;

use App\Support\Payments\GatewayResponse;
use App\Support\Payments\PaymentOutcome;
use App\Support\Payments\PaymentRequest;

/** Taking money in. One implementation per provider; the rest of the app only knows this. */
interface PaymentGateway
{
    /** The gateway's short name, as it appears in config and in callback URLs ("fake", "mpesa"). */
    public function name(): string;

    /** Ask the gateway to prompt a phone for payment. Nothing is paid yet: the outcome comes later. */
    public function requestPayment(PaymentRequest $request): GatewayResponse;

    /** Ask the gateway where a payment has got to (for when its callback is late or never comes). */
    public function queryPayment(string $gatewayReference): PaymentOutcome;

    /** Read a callback the gateway posted to us; null when it is not a payment result we understand. @param array<string, mixed> $payload */
    public function parseCallback(array $payload): ?PaymentOutcome;
}
