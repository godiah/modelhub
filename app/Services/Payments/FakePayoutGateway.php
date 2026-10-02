<?php

namespace App\Services\Payments;

use App\Contracts\PayoutGateway;
use App\Enums\GatewayState;
use App\Support\Payments\GatewayResponse;
use App\Support\Payments\PaymentOutcome;
use App\Support\Payments\PayoutRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * A pretend M-Pesa B2C for building and testing withdrawals. The last digit of the phone number decides what happens, and a payout stays
 * pending for a few seconds first:  0  the gateway refuses the request    1  the transfer fails    anything else  paid.
 */
class FakePayoutGateway implements PayoutGateway
{
    public function name(): string
    {
        return 'fake';
    }

    public function sendPayout(PayoutRequest $request): GatewayResponse
    {
        $last = (int) substr($request->msisdn, -1);

        if ($last === 0) {
            return new GatewayResponse(false, null, 'The transfer could not be started.');
        }

        $reference = 'AG_'.Str::upper(Str::random(16));
        Cache::put($this->key($reference), [
            'state' => $last === 1 ? GatewayState::Failed : GatewayState::Succeeded,
            'ready_at' => now()->addSeconds((int) config('payments.fake.delay_seconds'))->timestamp,
            'amount_minor' => $request->amountMinor,
            'receipt' => 'FAKEPO'.Str::upper(Str::random(6)),
        ], now()->addHour());

        return new GatewayResponse(true, $reference, 'Transfer accepted.');
    }

    public function queryPayout(string $gatewayReference): PaymentOutcome
    {
        $payout = Cache::get($this->key($gatewayReference));

        if (! $payout) {
            return new PaymentOutcome($gatewayReference, GatewayState::Failed, reason: 'The gateway does not know this transfer.');
        }

        if ($payout['ready_at'] > now()->timestamp) {
            return new PaymentOutcome($gatewayReference, GatewayState::Pending);
        }

        $paid = $payout['state'] === GatewayState::Succeeded;

        return new PaymentOutcome($gatewayReference, $payout['state'], $paid ? $payout['receipt'] : null, $paid ? $payout['amount_minor'] : null, $paid ? null : 'The transfer failed.');
    }

    /** The fake's callback is simple JSON: {gateway_reference, state, receipt?, amount_minor?, reason?}. */
    public function parseCallback(array $payload): ?PaymentOutcome
    {
        $state = GatewayState::tryFrom((string) ($payload['state'] ?? ''));

        if (! $state || blank($payload['gateway_reference'] ?? null)) {
            return null;
        }

        return new PaymentOutcome((string) $payload['gateway_reference'], $state, $payload['receipt'] ?? null, isset($payload['amount_minor']) ? (int) $payload['amount_minor'] : null, $payload['reason'] ?? null);
    }

    private function key(string $reference): string
    {
        return "fake-payout-gateway.{$reference}";
    }
}
