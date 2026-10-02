<?php

namespace App\Services\Payments;

use App\Contracts\PayoutGateway;
use App\Contracts\VerifiesCallbacks;
use App\Enums\GatewayState;
use App\Support\Payments\GatewayResponse;
use App\Support\Payments\PaymentOutcome;
use App\Support\Payments\PayoutRequest;
use Godiah\Common\Mpesa\Dto\B2cRequest;
use Godiah\Common\Mpesa\Exceptions\MpesaException;
use Godiah\Common\Mpesa\MpesaClient;
use Godiah\Common\Mpesa\Parsers\B2cResultParser;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Sending money out through M-Pesa B2C, by way of godiah/laravel-common. Safaricom accepts the request, then posts the result to our
 * callback URL. There is no way to ask where a B2C payment has got to, so a withdrawal whose result never arrives stays "being sent"
 * until staff settle it by hand (see PayoutService::settleByHand).
 *
 * Our payout reference is sent as the OriginatorConversationID and is also what we keep as the gateway reference, so the result (which
 * echoes it) finds its withdrawal, and a repeated request is not paid twice.
 *
 * Only a definite refusal returns a withdrawal's money. When we cannot tell whether Safaricom took the request (the connection dropped),
 * it is treated as sent and left for the callback or for staff: paying twice is worse than waiting.
 */
class DarajaPayoutGateway implements PayoutGateway, VerifiesCallbacks
{
    public function __construct(protected MpesaClient $client) {}

    public function name(): string
    {
        return DarajaCallbacks::GATEWAY;
    }

    public function sendPayout(PayoutRequest $request): GatewayResponse
    {
        $b2c = B2cRequest::make(
            phone: $request->msisdn,
            amount: intdiv($request->amountMinor, 100),
            resultUrl: DarajaCallbacks::payoutUrl(),
            timeoutUrl: DarajaCallbacks::payoutTimeoutUrl(),
            remarks: $request->remarks,
            originatorConversationId: $request->reference,
        );

        try {
            $response = $this->client->b2c($b2c);
        } catch (ConnectionException $e) {
            // The request may or may not have reached Safaricom
            report($e);

            return new GatewayResponse(true, $request->reference, 'Sent; waiting for confirmation.');
        } catch (MpesaException $e) {
            // No credentials or no access token: nothing was sent
            report($e);

            return new GatewayResponse(false, null, 'The transfer service is not available right now. Try again in a moment.');
        }

        if ($response->accepted) {
            return new GatewayResponse(true, $request->reference, $response->responseDescription);
        }

        // Daraja answered with a refusal (it names a code); a bare HTTP failure with no body is not a refusal we can rely on
        if ($response->errorCode !== null || $response->responseCode !== null) {
            Log::warning('Daraja refused a B2C payment', ['reference' => $request->reference, 'code' => $response->errorCode ?? $response->responseCode, 'message' => $response->errorMessage]);

            return new GatewayResponse(false, null, 'The transfer could not be started.');
        }

        Log::warning('Daraja B2C request got no usable answer; left for the callback or staff', ['reference' => $request->reference, 'message' => $response->errorMessage]);

        return new GatewayResponse(true, $request->reference, 'Sent; waiting for confirmation.');
    }

    /** Daraja has no B2C status API: the result callback is the only answer, so until it arrives the payout is still pending. */
    public function queryPayout(string $gatewayReference): PaymentOutcome
    {
        return new PaymentOutcome($gatewayReference, GatewayState::Pending);
    }

    public function parseCallback(array $payload): ?PaymentOutcome
    {
        try {
            $result = B2cResultParser::parse($payload);
        } catch (MpesaException) {
            return null;
        }

        if (blank($result->originatorConversationId)) {
            return null;
        }

        return $result->successful
            ? new PaymentOutcome($result->originatorConversationId, GatewayState::Succeeded, $result->transactionId, $result->amount !== null ? $result->amount * 100 : null)
            : new PaymentOutcome($result->originatorConversationId, GatewayState::Failed, reason: $result->resultDesc ?: 'The transfer failed.');
    }

    public function verifiesCallback(Request $request): bool
    {
        return DarajaCallbacks::authorized($request);
    }
}
