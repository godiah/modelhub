<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Enums\GatewayState;
use App\Support\Payments\GatewayResponse;
use App\Support\Payments\PaymentOutcome;
use App\Support\Payments\PaymentRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * A pretend M-Pesa for building and testing checkout without Safaricom. The last digit of the phone number decides what happens, and a
 * payment stays pending for a few seconds first, as a real prompt does:
 *
 *   0  the gateway refuses the request      1  insufficient funds      2  the buyer cancels      3  the prompt times out      anything else  paid
 */
class FakeGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'fake';
    }

    public function requestPayment(PaymentRequest $request): GatewayResponse
    {
        $last = (int) substr($request->msisdn, -1);

        if ($last === 0) {
            return new GatewayResponse(false, null, 'The prompt could not be sent to that number.');
        }

        $reference = 'ws_CO_'.Str::upper(Str::random(16));
        Cache::put($this->key($reference), [
            'state' => match ($last) {
                1 => GatewayState::Failed,
                2 => GatewayState::Cancelled,
                3 => GatewayState::TimedOut,
                default => GatewayState::Succeeded,
            },
            'ready_at' => now()->addSeconds((int) config('payments.fake.delay_seconds'))->timestamp,
            'amount_minor' => $request->amountMinor,
            'receipt' => 'FAKE'.Str::upper(Str::random(6)),
        ], now()->addHour());

        return new GatewayResponse(true, $reference, 'Prompt sent.');
    }

    public function queryPayment(string $gatewayReference): PaymentOutcome
    {
        $payment = Cache::get($this->key($gatewayReference));

        if (! $payment) {
            return new PaymentOutcome($gatewayReference, GatewayState::Failed, reason: 'The gateway does not know this payment.');
        }

        if ($payment['ready_at'] > now()->timestamp) {
            return new PaymentOutcome($gatewayReference, GatewayState::Pending);
        }

        $state = $payment['state'];
        $paid = $state === GatewayState::Succeeded;

        return new PaymentOutcome($gatewayReference, $state, $paid ? $payment['receipt'] : null, $paid ? $payment['amount_minor'] : null, match ($state) {
            GatewayState::Failed => 'Insufficient funds.',
            GatewayState::Cancelled => 'The payment was cancelled on the phone.',
            GatewayState::TimedOut => 'The prompt was not answered in time.',
            default => null,
        });
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
        return "fake-gateway.{$reference}";
    }
}
