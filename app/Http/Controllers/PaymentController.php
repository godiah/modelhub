<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\Payments\PaymentService;
use Illuminate\Http\Request;

/** The page a buyer waits on while the M-Pesa prompt is on their phone, and the status check it polls. Only the buyer can see theirs. */
class PaymentController extends Controller
{
    public function __construct(protected PaymentService $payments) {}

    public function show(Request $request, Payment $payment)
    {
        $payment = $this->mine($request, $payment);
        $payment = $this->payments->refresh($payment);

        if ($payment->status === PaymentStatus::Succeeded) {
            return redirect()->route('licences.show', $payment->purchase->licence)->with('status', 'Payment received. Your licence is ready.');
        }

        return view('payments.show', ['payment' => $payment->load('product')]);
    }

    /** JSON for the waiting page: where the payment has got to, and where to go next. */
    public function status(Request $request, Payment $payment)
    {
        $payment = $this->payments->refresh($this->mine($request, $payment));

        return response()->json([
            'status' => $payment->status->value,
            'final' => $payment->status->isFinal(),
            'message' => $payment->failure_reason,
            'redirect' => $payment->status === PaymentStatus::Succeeded ? route('licences.show', $payment->purchase->licence) : null,
        ]);
    }

    private function mine(Request $request, Payment $payment): Payment
    {
        abort_unless($payment->user_id === $request->user()->id, 404);

        return $payment;
    }
}
