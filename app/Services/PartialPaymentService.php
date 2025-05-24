<?php

namespace App\Services;

use App\Models\JobCancellation;
use App\Models\JobEngagement;
use App\Models\JobPartialPayment;
use App\Models\JobPaymentDispute;
use App\Notifications\PartialPaymentProcessedNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PartialPaymentService
{
    /**
     * Check if partial payment can be processed
     */
    public function canProcessPayment(JobEngagement $engagement)
    {
        $authUser = Auth::user();

        // Check if engagement is cancelled
        if ($engagement->status !== JobEngagement::STATUS_CANCELLED) {
            return [
                'can_process' => false,
                'message' => 'Partial payments can only be processed for cancelled engagements.'
            ];
        }

        // Check if user is authorized
        $isClient = $authUser->id === $engagement->application->poster_id;
        $isAdmin = $authUser->hasRole('admin');

        if (!$isClient && !$isAdmin) {
            return [
                'can_process' => false,
                'message' => 'Payment is currently pending and has not yet been processed.'
            ];
        }

        // Check for pending deliverables
        if ($engagement->hasPendingDeliverables()) {
            return [
                'can_process' => false,
                'message' => 'There are pending deliverables that need to be approved or rejected before processing payment.',
                'pending_count' => $engagement->getPendingDeliverablesCount()
            ];
        }

        // Check if there are approved deliverables
        if ($engagement->getCompletedDeliverablesCount() === 0) {
            return [
                'can_process' => false,
                'message' => 'No approved deliverables found for partial payment.'
            ];
        }

        // Check if payment is already processed
        $cancellation = $engagement->cancellation;
        if ($cancellation && $cancellation->partial_payment_processed) {
            return [
                'can_process' => false,
                'message' => 'Partial payment has already been processed.'
            ];
        }

        return [
            'can_process' => true,
            'message' => 'You may proceed with processing the payment.',
            'calculated_amount' => $engagement->calculatePartialPaymentAmount()
        ];
    }

    /**
     * Calculate partial payment amount
     */
    public function calculatePartialPayment(JobEngagement $engagement)
    {
        $totalDeliverables = $engagement->getTotalDeliverablesCount();
        $approvedDeliverables = $engagement->getCompletedDeliverablesCount();

        if ($totalDeliverables === 0 || $approvedDeliverables === 0) {
            return 0;
        }

        $paymentPercentage = $approvedDeliverables / $totalDeliverables;
        return round($engagement->net_amount * $paymentPercentage, 2);
    }

    /**
     * Process partial payment for a cancelled engagement
     */
    public function processPartialPayment(JobEngagement $engagement, $manualAmount = null)
    {
        // First validate if payment can be processed
        $canProcess = $this->canProcessPayment($engagement);
        if (!$canProcess['can_process']) {
            throw new \Exception($canProcess['message']);
        }

        // Check for existing unfinalized partial payment
        $existingPartialPayment = JobPartialPayment::where('engagement_id', $engagement->id)
            ->whereIn('status', [
                JobPartialPayment::STATUS_PENDING,
                JobPartialPayment::STATUS_ACCEPTED,
                JobPartialPayment::STATUS_DISPUTED,
                JobPartialPayment::STATUS_FINALIZED,
            ])
            ->first();

        if ($existingPartialPayment) {
            throw new \Exception('A partial payment is already pending or in progress for this engagement.');
        }

        $authUser = Auth::user();

        DB::beginTransaction();
        try {
            // Calculate payment amount
            $amount = $manualAmount;

            // If no manual amount provided, calculate based on approved deliverables
            if ($amount === null) {
                $amount = $this->calculatePartialPayment($engagement);

                if ($amount <= 0) {
                    throw new \Exception('Cannot calculate a valid payment amount.');
                }
            }

            // Create partial payment record
            $partialPayment = JobPartialPayment::create([
                'engagement_id' => $engagement->id,
                'amount' => $amount,
                'status' => JobPartialPayment::STATUS_PENDING,
                'notes' => 'Partial payment for approved deliverables',
                'processed_by' => $authUser->id,
                'processed_at' => now(),
            ]);

            // Update cancellation record if it exists
            if ($cancellation = $engagement->cancellation) {
                $cancellation->update([
                    'partial_payment_amount' => $amount,
                    'payment_calculated_at' => now(),
                    'process_payment_for_work' => true,
                ]);
            } else {
                // Create cancellation record if it doesn't exist
                JobCancellation::create([
                    'engagement_id' => $engagement->id,
                    'initiator_id' => $authUser->id,
                    'cancellation_type' => JobCancellation::TYPE_CLIENT_INITIATED,
                    'process_payment_for_work' => true,
                    'partial_payment_amount' => $amount,
                    'payment_calculated_at' => now(),
                    'partial_payment_processed' => false,
                ]);
            }

            // Notify the freelancer about the payment
            //$freelancer = $engagement->applicant->user;
            //$freelancer->notify(new PartialPaymentReadyNotification($engagement, $partialPayment));

            Log::info('Partial payment processed', [
                'engagement_id' => $engagement->id,
                'amount' => $amount,
                'processed_by' => $authUser->id
            ]);

            DB::commit();

            return $partialPayment;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process partial payment', [
                'engagement_id' => $engagement->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Accept partial payment by freelancer
     */
    public function acceptPartialPayment(JobEngagement $engagement, JobPartialPayment $payment)
    {
        $authUser = Auth::user();

        // Ensure user is the freelancer
        if ($authUser->id !== $engagement->application->applicant_id) {
            throw new \Exception('Only the freelancer can accept the partial payment.');
        }

        // Ensure payment is pending and not already accepted/disputed
        if ($payment->status !== JobPartialPayment::STATUS_PENDING) {
            throw new \Exception('This payment has already been accepted or disputed.');
        }

        DB::beginTransaction();
        try {
            // Update payment status
            $payment->update([
                'status' => JobPartialPayment::STATUS_ACCEPTED,
                'accepted_at' => now()
            ]);

            // Update cancellation record
            if ($cancellation = $engagement->cancellation) {
                $cancellation->acceptPayment();
                $cancellation->update([
                    'partial_payment_processed' => true,
                    'partial_payment_processed_at' => now(),
                    'freelancer_accepted_payment' => true,
                    'freelancer_accepted_at' => now()
                ]);
            }

            // Mark engagement as settled
            $engagement->markAsSettled();

            // Here you would integrate with your payment gateway to process the actual payment
            // For example: $this->paymentGateway->transferFunds($payment->amount, $engagement->applicant);

            // Notify the client about acceptance
            //$client = $engagement->poster->user;
            //$client->notify(new PaymentAcceptedNotification($engagement, $payment));

            Log::info('Partial payment accepted', [
                'engagement_id' => $engagement->id,
                'payment_id' => $payment->id,
                'freelancer_id' => $authUser->id
            ]);

            DB::commit();

            return $payment;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to accept partial payment', [
                'engagement_id' => $engagement->id,
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Dispute partial payment by freelancer
     */
    public function disputePartialPayment(JobEngagement $engagement, JobPartialPayment $payment, $reason, $details, $evidence = null)
    {
        $authUser = Auth::user();

        // Ensure user is the freelancer
        if ($authUser->id !== $engagement->application->applicant_id) {
            throw new \Exception('Only the freelancer can dispute the partial payment.');
        }

        // Ensure payment is pending and not already accepted/disputed
        if ($payment->status !== JobPartialPayment::STATUS_PENDING) {
            throw new \Exception('This payment has already been accepted or disputed.');
        }

        DB::beginTransaction();
        try {
            // Update payment status initially
            $payment->update([
                'status' => JobPartialPayment::STATUS_DISPUTED
            ]);

            // Get or create cancellation record
            $cancellation = $engagement->cancellation;
            if (!$cancellation) {
                $cancellation = JobCancellation::create([
                    'engagement_id' => $engagement->id,
                    'initiator_id' => $authUser->id,
                    'cancellation_type' => JobCancellation::TYPE_FREELANCER_INITIATED,
                    'process_payment_for_work' => true,
                    'partial_payment_amount' => $payment->amount,
                    'payment_calculated_at' => now(),
                    'is_dispute' => true,
                ]);
            } else {
                $cancellation->update([
                    'is_dispute' => true,
                ]);
            }

            // Create dispute record
            $dispute = $cancellation->createDispute($reason, $details, $authUser->id);

            // Update payment with dispute reference
            $payment->update([
                'dispute_id' => $dispute->id
            ]);

            // Add evidence if provided
            if ($evidence) {
                $dispute->addEvidence($evidence);
            }

            // Mark engagement as disputed
            $engagement->markAsDisputed();

            // Notify the client about dispute
            //$client = $engagement->poster->user;
            //$client->notify(new PaymentDisputedNotification($engagement, $payment, $dispute));

            // Notify admins
            //$admins = User::role('admin')->get();
            //Notification::send($admins, new PaymentDisputeAdminNotification($engagement, $payment));

            Log::info('Partial payment disputed', [
                'engagement_id' => $engagement->id,
                'payment_id' => $payment->id,
                'dispute_id' => $dispute->id,
                'freelancer_id' => $authUser->id
            ]);

            DB::commit();

            return $dispute;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to dispute partial payment', [
                'engagement_id' => $engagement->id,
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Resolve a payment dispute (admin only)
     */
    public function resolveDispute(JobPaymentDispute $dispute, $notes, $finalAmount = null)
    {
        $authUser = Auth::user();

        // Ensure user is an admin
        if (!$authUser->hasRole('admin')) {
            throw new \Exception('Only administrators can resolve payment disputes.');
        }

        // Ensure dispute is not already resolved
        if ($dispute->isResolved()) {
            throw new \Exception('This dispute has already been resolved.');
        }

        DB::beginTransaction();
        try {
            // Get related records
            $cancellation = $dispute->cancellation;
            $engagement = $cancellation->engagement;
            $payment = JobPartialPayment::where('dispute_id', $dispute->id)->first();

            // Update dispute status
            $dispute->resolve($authUser->id, $notes, $finalAmount);

            // Update cancellation record
            $cancellation->resolveDispute($finalAmount);

            // Update payment record if exists
            if ($payment) {
                $payment->finalize($authUser->id, $finalAmount);
            } else {
                // Create new payment record if it doesn't exist
                $payment = JobPartialPayment::create([
                    'engagement_id' => $engagement->id,
                    'amount' => $finalAmount ?? $cancellation->partial_payment_amount,
                    'status' => JobPartialPayment::STATUS_FINALIZED,
                    'notes' => 'Payment after dispute resolution: ' . $notes,
                    'processed_by' => $authUser->id,
                    'processed_at' => now(),
                    'finalized_at' => now(),
                    'finalized_by' => $authUser->id,
                    'dispute_id' => $dispute->id,
                ]);
            }

            // Mark engagement as settled
            $engagement->markAsSettled();

            // Here you would integrate with your payment gateway to process the actual payment
            // For example: $this->paymentGateway->transferFunds($finalAmount, $engagement->applicant);

            // Notify involved parties
            $client = $engagement->poster->user;
            $freelancer = $engagement->applicant->user;

            // Create and send notifications
            // You would need to implement these notification classes

            Log::info('Dispute resolved', [
                'engagement_id' => $engagement->id,
                'dispute_id' => $dispute->id,
                'admin_id' => $authUser->id,
                'final_amount' => $finalAmount ?? $cancellation->partial_payment_amount
            ]);

            DB::commit();

            return [
                'dispute' => $dispute,
                'payment' => $payment
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to resolve dispute', [
                'dispute_id' => $dispute->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
