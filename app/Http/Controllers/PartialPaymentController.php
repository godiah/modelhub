<?php

namespace App\Http\Controllers;

use App\Enums\PartialPaymentStatus;
use App\Helpers\Engagements\EngagementAuthorizationHelper;
use App\Helpers\FlashAlertHelper;
use App\Http\Requests\Payment\ProcessDisputePartialPaymentRequest;
use App\Http\Requests\Payment\ProcessPartialPaymentRequest;
use App\Models\JobEngagement;
use App\Models\JobPartialPayment;
use App\Models\JobPaymentDispute;
use App\Services\Payments\PartialPaymentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PartialPaymentController extends Controller
{
    protected $partialPaymentService;

    public function __construct(PartialPaymentService $partialPaymentService)
    {
        $this->partialPaymentService = $partialPaymentService;
    }

    /**
     * Process partial payment for a cancelled engagement. The payment amount
     * is optional — when omitted, it's auto-calculated from the ratio of
     * approved deliverables; when provided, it overrides that calculation.
     */
    public function processPartialPayment(ProcessPartialPaymentRequest $request, $id)
    {
        // Find the engagement by ID
        $engagement = JobEngagement::findOrFail($id);

        try {
            $partialPayment = $this->partialPaymentService->processPartialPayment(
                $engagement,
                $request->getPaymentAmount(),
                $request->getPaymentNotes()
            );

            return redirect()->route('engagements.show-cancelled', $engagement->id)->with(
                FlashAlertHelper::success(
                    'Payment Processed',
                    'Partial payment of '.number_format($partialPayment->amount, 2).' has been processed successfully.'
                )
            );
        } catch (\Exception $e) {
            Log::error('Payment processing failed', [
                'engagement_id' => $engagement->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to process partial payment: '.$e->getMessage());
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
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to accept payment: '.$e->getMessage());
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
        if (! EngagementAuthorizationHelper::canRespondToPartialPayment($engagement, $authUser)) {
            return back()->with('error', 'Unauthorized access.');
        }

        // Verify payment is in pending status
        if ($payment->status !== PartialPaymentStatus::Pending) {
            return back()->with('error', 'This payment cannot be disputed in its current state.');
        }

        // Show dispute form
        return view('jobBoard.engagements.dispute', [
            'payment' => $payment,
            'engagement' => $engagement,
        ]);
    }

    /**
     * Process the dispute form submission
     */
    public function processDisputePartialPayment(ProcessDisputePartialPaymentRequest $request, $paymentId)
    {
        $payment = JobPartialPayment::findOrFail($paymentId);
        $engagement = $payment->engagement;

        try {
            // Upload evidence if provided
            $evidence = null;
            if ($request->hasFile('evidence')) {
                $evidencePath = $request->file('evidence')->store('dispute-evidence', 'local');
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
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to dispute payment: '.$e->getMessage());
        }
    }

    /**
     * Download a piece of dispute evidence. Files live on the private disk —
     * only the engagement's poster, applicant, or an admin may download them.
     */
    public function downloadDisputeEvidence(JobPaymentDispute $dispute, int $index)
    {
        if (! EngagementAuthorizationHelper::canView($dispute->engagement, Auth::user())) {
            abort(403);
        }

        $path = $dispute->supporting_evidence[$index] ?? null;

        if (! $path || ! Storage::disk('local')->exists($path)) {
            abort(404);
        }

        return Storage::disk('local')->download($path);
    }
}
