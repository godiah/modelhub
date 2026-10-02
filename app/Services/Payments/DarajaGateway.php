<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Contracts\VerifiesCallbacks;
use App\Enums\GatewayState;
use App\Support\Payments\GatewayResponse;
use App\Support\Payments\PaymentOutcome;
use App\Support\Payments\PaymentRequest;
use Godiah\Common\Mpesa\Dto\StkPushRequest;
use Godiah\Common\Mpesa\Exceptions\MpesaException;
use Godiah\Common\Mpesa\MpesaClient;
use Godiah\Common\Mpesa\Parsers\StkCallbackParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Taking money through M-Pesa (Daraja STK Push), by way of godiah/laravel-common. We ask a phone to pay; Safaricom answers by posting to
 * our callback URL, and the status query is the fallback when that does not arrive.
 *
 * The status query tells us paid / cancelled / timed out but not the receipt or the amount, so a payment settled by the query takes the
 * amount we asked for (an STK prompt cannot be changed by the customer) and gets its receipt from the callback when that does come.
 */
class DarajaGateway implements PaymentGateway, VerifiesCallbacks
{
    public function __construct(protected MpesaClient $client) {}

    public function name(): string
    {
        return DarajaCallbacks::GATEWAY;
    }

    public function requestPayment(PaymentRequest $request): GatewayResponse
    {
        $response = $this->client->stkPush(StkPushRequest::make(
            phone: $request->msisdn,
            amount: intdiv($request->amountMinor, 100),
            ref: $request->reference,
            callbackUrl: DarajaCallbacks::paymentUrl(),
            description: $request->description,
        ));

        if (! $response->successful) {
            Log::warning('Daraja refused an STK push', ['reference' => $request->reference, 'code' => $response->errorCode ?? $response->responseCode, 'message' => $response->errorMessage]);

            return new GatewayResponse(false, null, 'The M-Pesa prompt could not be sent. Check the number and try again.');
        }

        return new GatewayResponse(true, $response->checkoutRequestId, $response->customerMessage);
    }

    public function queryPayment(string $gatewayReference): PaymentOutcome
    {
        $query = $this->client->stkQuery($gatewayReference);

        // A failed call, or "still being processed", says nothing about the payment: keep waiting
        return match (true) {
            ! $query->httpSuccessful, $query->pending() => new PaymentOutcome($gatewayReference, GatewayState::Pending),
            $query->succeeded() => new PaymentOutcome($gatewayReference, GatewayState::Succeeded),
            $query->cancelled() => new PaymentOutcome($gatewayReference, GatewayState::Cancelled, reason: 'The payment was cancelled on the phone.'),
            $query->timedOut() => new PaymentOutcome($gatewayReference, GatewayState::TimedOut, reason: 'The prompt was not answered in time.'),
            default => new PaymentOutcome($gatewayReference, GatewayState::Failed, reason: $query->resultDesc ?: 'The payment did not go through.'),
        };
    }

    public function parseCallback(array $payload): ?PaymentOutcome
    {
        try {
            $callback = StkCallbackParser::parse($payload);
        } catch (MpesaException) {
            return null;
        }

        if ($callback->checkoutRequestId === '') {
            return null;
        }

        return match (true) {
            $callback->successful() => new PaymentOutcome($callback->checkoutRequestId, GatewayState::Succeeded, $callback->receipt, $callback->amount !== null ? (int) round($callback->amount * 100) : null),
            $callback->cancelled() => new PaymentOutcome($callback->checkoutRequestId, GatewayState::Cancelled, reason: 'The payment was cancelled on the phone.'),
            $callback->timedOut() => new PaymentOutcome($callback->checkoutRequestId, GatewayState::TimedOut, reason: 'The prompt was not answered in time.'),
            default => new PaymentOutcome($callback->checkoutRequestId, GatewayState::Failed, reason: $callback->resultDesc ?: 'The payment did not go through.'),
        };
    }

    public function verifiesCallback(Request $request): bool
    {
        return DarajaCallbacks::authorized($request);
    }
}
