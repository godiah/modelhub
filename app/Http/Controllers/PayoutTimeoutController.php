<?php

namespace App\Http\Controllers;

use App\Contracts\PayoutGateway;
use App\Contracts\VerifiesCallbacks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Where the gateway says a withdrawal sat in its queue too long. That is not a result: the money may still go, so nothing is changed
 * here (a withdrawal is only ended by its result, or by staff). It is logged, and acknowledged so the gateway stops retrying.
 */
class PayoutTimeoutController extends Controller
{
    public function __invoke(Request $request, PayoutGateway $gateway, string $name)
    {
        abort_unless($gateway->name() === $name, 404);
        abort_if($gateway instanceof VerifiesCallbacks && ! $gateway->verifiesCallback($request), 403);

        Log::warning('Payout gateway reported a queue timeout', ['gateway' => $name, 'payload' => $request->all()]);

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }
}
