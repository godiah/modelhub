<?php

namespace App\Support\Payments;

/** A request to take money from a phone: what to charge, from which number, and the reference to show on the statement. */
final readonly class PaymentRequest
{
    public function __construct(
        public string $reference,      // ours, 12 characters at most (an M-Pesa limit)
        public string $msisdn,         // 2547XXXXXXXX
        public int $amountMinor,       // whole shillings, in minor units
        public string $description,    // 13 characters at most (an M-Pesa limit)
    ) {}
}
