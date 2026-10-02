<?php

namespace App\Contracts;

use App\Support\Payments\GatewayResponse;
use App\Support\Payments\PaymentOutcome;
use App\Support\Payments\PayoutRequest;

/** Sending money out. One implementation per provider; the rest of the app only knows this. */
interface PayoutGateway
{
    public function name(): string;

    /** Ask the gateway to send money to a phone. Nothing has landed yet: the outcome comes later. */
    public function sendPayout(PayoutRequest $request): GatewayResponse;

    /** Where a payout has got to (for when the gateway's callback is late). */
    public function queryPayout(string $gatewayReference): PaymentOutcome;

    /** Read a result the gateway posted to us; null when it is not one we understand. @param array<string, mixed> $payload */
    public function parseCallback(array $payload): ?PaymentOutcome;
}
