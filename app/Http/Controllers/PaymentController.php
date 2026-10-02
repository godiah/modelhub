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
            return $this->afterSuccess($payment)->with('status', $payment->isEscrow() ? 'Payment received. The job is funded and work can start.' : 'Payment received. Your licence is ready.');
        }

        return view('payments.show', ['payment' => $payment->load('product', 'engagement.application.job')]);
    }

    /** JSON for the waiting page: where the payment has got to, and where to go next. */
    public function status(Request $request, Payment $payment)
    {
        $payment = $this->payments->refresh($this->mine($request, $payment));

        return response()->json([
            'status' => $payment->status->value,
            'final' => $payment->status->isFinal(),
            'message' => $payment->failure_reason,
            'redirect' => $payment->status === PaymentStatus::Succeeded ? $this->afterSuccess($payment)->getTargetUrl() : null,
        ]);
    }

    /** Where a paid payment leads: the licence for a model, the workspace for a funded job. */
    private function afterSuccess(Payment $payment)
    {
        return $payment->isEscrow()
            ? redirect()->route('engagements.show', $payment->engagement_id)
            : redirect()->route('licences.show', $payment->purchase->licence);
    }

    private function mine(Request $request, Payment $payment): Payment
    {
        abort_unless($payment->user_id === $request->user()->id, 404);

        return $payment;
    }
}
