<?php

namespace App\Support\Payments;

/** A request to send money to a phone. */
final readonly class PayoutRequest
{
    public function __construct(
        public string $reference,   // ours
        public string $msisdn,      // 2547XXXXXXXX
        public int $amountMinor,    // whole shillings, in minor units
        public string $remarks,
    ) {}
}
