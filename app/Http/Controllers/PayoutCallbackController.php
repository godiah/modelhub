<?php

namespace App\Http\Controllers;

use App\Contracts\PayoutGateway;
use App\Contracts\VerifiesCallbacks;
use App\Models\Payout;
use App\Services\Payments\PayoutService;
use Illuminate\Http\Request;

/**
 * Where the gateway tells us a withdrawal landed or failed. Nobody is signed in, so it only acts on a withdrawal we sent and know the
 * gateway's reference for, and always answers with an acknowledgement.
 */
class PayoutCallbackController extends Controller
{
    public function __invoke(Request $request, PayoutService $payouts, PayoutGateway $gateway, string $name)
    {
        abort_unless($gateway->name() === $name, 404);
        abort_if($gateway instanceof VerifiesCallbacks && ! $gateway->verifiesCallback($request), 403);

        $outcome = $gateway->parseCallback($request->all());
        $payout = $outcome ? Payout::where('gateway', $name)->where('gateway_reference', $outcome->gatewayReference)->first() : null;

        if ($outcome && $payout) {
            $payouts->applyOutcome($payout, $outcome);
        }

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }
}
