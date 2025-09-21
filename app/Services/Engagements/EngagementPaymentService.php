<?php

/**
 * EngagementPaymentService
 * 
 * Handles payment processing for cancelled engagements.
 * Acts as a wrapper around PartialPaymentService with engagement-specific logic.
*/

namespace App\Services\Engagements;

use App\Helpers\Engagements\EngagementAuthorizationHelper;
use App\Models\JobEngagement;
use App\Services\Payments\PartialPaymentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EngagementPaymentService
{
    protected PartialPaymentService $partialPaymentService;

    public function __construct(PartialPaymentService $partialPaymentService)
    {
        $this->partialPaymentService = $partialPaymentService;
    }

    // Process partial payment for cancelled engagement
    public function processPartialPayment(JobEngagement $engagement, array $paymentData): array
    {
        $user = Auth::user();
        
        // Authorization check
        if (!EngagementAuthorizationHelper::canProcessPayment($engagement, $user)) {
            return [
                'success' => false,
                'error' => 'You are not authorized to process payments for this engagement.'
            ];
        }

        DB::beginTransaction();
        try {
            // Record the partial payment
            $engagement->partialPayments()->create([
                'amount' => $paymentData['payment_amount'],
                'notes' => $paymentData['payment_notes'] ?? null,
                'processed_by' => $user->id,
                'processed_at' => now(),
            ]);

            // Here you would integrate with your payment processor
            // processPayment($engagement, $paymentData['payment_amount']);

            // Update the cancellation record if it exists
            if ($engagement->cancellation) {
                $engagement->cancellation->update([
                    'partial_payment_processed' => true,
                    'partial_payment_processed_at' => now(),
                ]);
            }

            DB::commit();

            // Notify the freelancer about the payment (implement if needed)
            // $engagement->application->applicant->notify(new PaymentProcessed($engagement, $paymentData['payment_amount']));

            return [
                'success' => true,
                'message' => 'Payment for partial work has been processed successfully.',
                'alert' => [
                    'type' => 'success',
                    'title' => 'Payment Processed',
                    'text' => 'Payment for partial work has been processed successfully.',
                ]
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'error' => 'Failed to process payment: ' . $e->getMessage()
            ];
        }
    }

    // Get payment information using PartialPaymentService
    public function getPaymentInfo(JobEngagement $engagement): array
    {
        return $this->partialPaymentService->canProcessPayment($engagement);
    }

    // Check if payment can be processed
    public function canProcessPayment(JobEngagement $engagement): bool
    {
        $paymentInfo = $this->getPaymentInfo($engagement);
        return $paymentInfo['can_process'];
    }

    // Get latest payment for engagement
    public function getLatestPayment(JobEngagement $engagement)
    {
        return $engagement->partialPayments()->latest()->first();
    }

    // Calculate partial payment amount
    public function calculatePartialPayment(JobEngagement $engagement): float
    {
        return $this->partialPaymentService->calculatePartialPayment($engagement);
    }
}
