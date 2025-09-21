<?php

namespace App\Http\Controllers;

use App\Models\JobEngagement;
use App\Models\JobPartialPayment;
use App\Services\Payments\PartialPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PartialPaymentController extends Controller
{
    protected $partialPaymentService;

    public function __construct(PartialPaymentService $partialPaymentService)
    {
        $this->partialPaymentService = $partialPaymentService;
    }

    /**
     * Process partial payment for a cancelled engagement
     */
    public function processPartialPayment($id)
    {
        // Find the engagement by ID
        $engagement = JobEngagement::findOrFail($id);

        try {
            // Process the payment with the calculated amount (no manual input)
            $partialPayment = $this->partialPaymentService->processPartialPayment($engagement);

            return redirect()->route('engagements.show-cancelled', $engagement->id)->with([
                'success' => 'Partial payment processed successfully.',
                'alert' => [
                    'type' => 'success',
                    'title' => 'Payment Processed',
                    'text' => "Partial payment of " . number_format($partialPayment->amount, 2) . " has been processed successfully.",
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Payment processing failed', [
                'engagement_id' => $engagement->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to process partial payment: ' . $e->getMessage());
        }
    }

    /**
     * Accept partial payment-Freelancer
     */
    public function acceptPartialPayment($paymentId)
    {
        $payment = JobPartialPayment::findOrFail($paymentId);
        $engagement = $payment->engagement;

        try {
            // Call the service method to handle the payment acceptance
            $this->partialPaymentService->acceptPartialPayment($engagement, $payment);

            return back()->with('success', 'Payment accepted successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to accept payment', [
                'payment_id' => $payment->id,
                'engagement_id' => $engagement->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to accept payment: ' . $e->getMessage());
        }
    }

    /**
     * Show dispute form
     */
    public function disputePartialPayment($paymentId)
    {
        $payment = JobPartialPayment::findOrFail($paymentId);
        $engagement = $payment->engagement;
        $authUser = Auth::user();

        // Verify this is the freelancer
        if ($authUser->id !== $engagement->application->applicant_id) {
            return back()->with('error', 'Unauthorized access.');
        }

        // Verify payment is in pending status
        if ($payment->status !== JobPartialPayment::STATUS_PENDING) {
            return back()->with('error', 'This payment cannot be disputed in its current state.');
        }

        // Show dispute form
        return view('jobBoard.engagements.dispute', [
            'payment' => $payment,
            'engagement' => $engagement
        ]);
    }

    /**
     * Process the dispute form submission
     */
    public function processDisputePartialPayment(Request $request, $paymentId)
    {
        $request->validate([
            'reason' => 'required|string|max:255',
            'details' => 'required|string',
            'evidence' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240',
        ]);

        $payment = JobPartialPayment::findOrFail($paymentId);
        $engagement = $payment->engagement;
        $authUser = Auth::user();

        try {
            // Upload evidence if provided
            $evidence = null;
            if ($request->hasFile('evidence')) {
                $evidencePath = $request->file('evidence')->store('dispute-evidence', 'public');
                $evidence = $evidencePath;
            }

            // Call the service method to handle the dispute
            $dispute = $this->partialPaymentService->disputePartialPayment(
                $engagement,
                $payment,
                $request->reason,
                $request->details,
                $evidence
            );

            return redirect()->route('engagements.show-cancelled', $engagement->id)->with(
                'success',
                'Payment disputed successfully. An administrator will review your case.',
            );
        } catch (\Exception $e) {
            Log::error('Failed to dispute payment', [
                'payment_id' => $payment->id,
                'engagement_id' => $engagement->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to dispute payment: ' . $e->getMessage());
        }
    }
}
