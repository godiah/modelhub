<?php

namespace App\Support\Payments;

/** The gateway's answer to "please prompt this phone": accepted (and the gateway's own reference for it), or not. */
final readonly class GatewayResponse
{
    public function __construct(public bool $accepted, public ?string $gatewayReference = null, public ?string $message = null) {}
}
