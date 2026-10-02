<?php

namespace App\Support\Payments;

use App\Enums\GatewayState;

/** What the gateway reports about a payment, from a status query or a callback: its state, the receipt and the amount actually paid. */
final readonly class PaymentOutcome
{
    public function __construct(
        public string $gatewayReference,
        public GatewayState $state,
        public ?string $receipt = null,
        public ?int $amountMinor = null,
        public ?string $reason = null,
    ) {}
}
