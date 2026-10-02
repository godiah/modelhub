<?php

namespace App\Http\Controllers;

use App\Contracts\PaymentGateway;
use App\Models\Payment;
use App\Services\Payments\PaymentService;
use Illuminate\Http\Request;

/**
 * Where the payment gateway tells us a payment has been paid, declined or cancelled. Nobody is signed in here, so nothing is taken on
 * trust: the callback can only act on a payment we started and know the gateway's reference for, and the amount is checked before a licence is issued.
 * The gateway is always answered with an acknowledgement, so it does not keep retrying.
 */
class PaymentCallbackController extends Controller
{
    public function __invoke(Request $request, PaymentService $payments, PaymentGateway $gateway, string $name)
    {
        abort_unless($gateway->name() === $name, 404);

        $outcome = $gateway->parseCallback($request->all());
        $payment = $outcome ? Payment::where('gateway', $name)->where('gateway_reference', $outcome->gatewayReference)->first() : null;

        if ($outcome && $payment) {
            $payments->applyOutcome($payment, $outcome);
        }

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }
}
