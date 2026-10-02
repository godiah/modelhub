<?php

namespace App\Services\Payments;

use App\Enums\EngagementStatus;
use App\Enums\PartialPaymentStatus;
use App\Helpers\Engagements\EngagementAuthorizationHelper;
use App\Helpers\Engagements\EngagementNotificationHelper;
use App\Models\JobCancellation;
use App\Models\JobEngagement;
use App\Models\JobPartialPayment;
use App\Models\JobPaymentDispute;
use App\Support\Money;
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
        if ($engagement->status !== EngagementStatus::Cancelled) {
            return [
                'can_process' => false,
                'message' => 'Partial payments can only be processed for cancelled engagements.',
            ];
        }

        // Check if user is authorized
        $isClient = $authUser->id === $engagement->application->poster_id;

        if (! $isClient) {
            return [
                'can_process' => false,
                'message' => 'Payment is currently pending and has not yet been processed.',
            ];
        }

        // Check for pending deliverables
        if ($engagement->hasPendingDeliverables()) {
            return [
                'can_process' => false,
                'message' => 'There are pending deliverables that need to be approved or rejected before processing payment.',
                'pending_count' => $engagement->getPendingDeliverablesCount(),
            ];
        }

        // A funded job's approved work has already been paid out of escrow as it was approved, so what can still be paid here is only extra, for work
        // that was never approved, and only while what is left in escrow has not yet become the client's to have back.
        $escrow = app(EscrowService::class);
        $funded = $engagement->escrow_minor > 0;

        if ($funded) {
            if (! $escrow->reviewWindowOpen($engagement)) {
                return [
                    'can_process' => false,
                    'message' => 'The review window has ended. What is left in escrow goes back to the client.',
                ];
            }

            if ($escrow->remainingNetMinor($engagement) <= 0) {
                return [
                    'can_process' => false,
                    'message' => 'Nothing is left in escrow to pay for.',
                ];
            }
        } elseif ($engagement->getCompletedDeliverablesCount() === 0) {
            // Check if there are approved deliverables
            return [
                'can_process' => false,
                'message' => 'No approved deliverables found for partial payment.',
            ];
        }

        // Check if payment is already processed
        $cancellation = $engagement->cancellation;
        if ($cancellation && $cancellation->partial_payment_processed) {
            return [
                'can_process' => false,
                'message' => 'Partial payment has already been processed.',
            ];
        }

        return [
            'can_process' => true,
            'message' => $funded
                ? 'The approved work has already been paid from escrow. You can pay extra for work that was not approved, up to '.Money::formatMinor($escrow->remainingNetMinor($engagement)).'. Leave the amount blank to pay nothing more and have the rest of the escrow returned to you.'
                : 'You may proceed with processing the payment.',
            'calculated_amount' => $funded ? 0 : $engagement->calculatePartialPaymentAmount(),
        ];
    }

    /**
     * Calculate partial payment amount
     */
    public function calculatePartialPayment(JobEngagement $engagement)
    {
        // Funded: approved work was paid as it was approved, so nothing more is owed by default
        if ($engagement->escrow_minor > 0) {
            return 0;
        }

        $totalDeliverables = $engagement->getTotalDeliverablesCount();
        $approvedDeliverables = $engagement->getCompletedDeliverablesCount();

        if ($totalDeliverables === 0 || $approvedDeliverables === 0) {
            return 0;
        }

        $paymentPercentage = $approvedDeliverables / $totalDeliverables;

        return round($engagement->net_amount * $paymentPercentage, 2);
    }

    /**
     * Process partial payment for a cancelled engagement. When $manualAmount is
     * omitted, the amount is auto-calculated from the ratio of approved
     * deliverables; when provided, it overrides that calculation (capped at
     * the engagement's net amount).
     */
    public function processPartialPayment(JobEngagement $engagement, $manualAmount = null, ?string $notes = null)
    {
        // First validate if payment can be processed
        $canProcess = $this->canProcessPayment($engagement);
        if (! $canProcess['can_process']) {
            throw new \Exception($canProcess['message']);
        }

        // Check for existing unfinalized partial payment
        $existingPartialPayment = JobPartialPayment::where('engagement_id', $engagement->id)
            ->whereIn('status', [
                PartialPaymentStatus::Pending,
                PartialPaymentStatus::Accepted,
                PartialPaymentStatus::Disputed,
                PartialPaymentStatus::Finalized,
            ])
            ->first();

        if ($existingPartialPayment) {
            throw new \Exception('A partial payment is already pending or in progress for this engagement.');
        }

        $authUser = Auth::user();

        DB::beginTransaction();
        try {
            // Calculate payment amount — manual amount overrides the auto-calculated one
            $amount = $manualAmount ?? $this->calculatePartialPayment($engagement);

            $escrow = app(EscrowService::class);
            $funded = $engagement->escrow_minor > 0;

            if ($amount < 0 || ($amount == 0 && ! $funded)) {
                throw new \Exception('Cannot process a payment amount that is zero or negative.');
            }

            if ($funded) {
                if ((int) round($amount * 100) > $escrow->remainingNetMinor($engagement)) {
                    throw new \Exception('Payment amount cannot exceed what is left in escrow for the freelancer ('.Money::formatMinor($escrow->remainingNetMinor($engagement)).').');
                }
            } elseif ($amount > $engagement->net_amount) {
                throw new \Exception('Payment amount cannot exceed the engagement\'s net amount.');
            }

            // Create partial payment record
            $partialPayment = JobPartialPayment::create([
                'engagement_id' => $engagement->id,
                'amount' => $amount,
                'status' => PartialPaymentStatus::Pending,
                'notes' => $notes ?? 'Partial payment for approved deliverables',
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

            // What is left in escrow stays put until the freelancer has answered
            $escrow->freeze($engagement);

            // Notify the freelancer about the payment
            EngagementNotificationHelper::sendPaymentNotification($engagement, $partialPayment);

            Log::info('Partial payment processed', [
                'engagement_id' => $engagement->id,
                'amount' => $amount,
                'processed_by' => $authUser->id,
            ]);

            DB::commit();

            return $partialPayment;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process partial payment', [
                'engagement_id' => $engagement->id,
                'error' => $e->getMessage(),
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
        if (! EngagementAuthorizationHelper::canRespondToPartialPayment($engagement, $authUser)) {
            throw new \Exception('Only the freelancer can accept the partial payment.');
        }

        // Ensure payment is pending and not already accepted/disputed
        if ($payment->status !== PartialPaymentStatus::Pending) {
            throw new \Exception('This payment has already been accepted or disputed.');
        }

        DB::beginTransaction();
        try {
            // Update payment status
            $payment->update([
                'status' => PartialPaymentStatus::Accepted,
                'accepted_at' => now(),
            ]);

            // Update cancellation record
            if ($cancellation = $engagement->cancellation) {
                $cancellation->acceptPayment();
                $cancellation->update([
                    'partial_payment_processed' => true,
                    'partial_payment_processed_at' => now(),
                    'freelancer_accepted_payment' => true,
                    'freelancer_accepted_at' => now(),
                ]);
            }

            // A funded job pays the accepted amount out of escrow, and what is left becomes the client's to have back
            $this->payFromEscrow($engagement, $payment, (float) $payment->amount, "partial:{$payment->id}");

            // Mark engagement as settled
            $engagement->markAsSettled();
            app(EscrowService::class)->closeOut($engagement);

            // Notify the client about acceptance
            EngagementNotificationHelper::sendPaymentAcceptedNotification($engagement, $payment);

            Log::info('Partial payment accepted', [
                'engagement_id' => $engagement->id,
                'payment_id' => $payment->id,
                'freelancer_id' => $authUser->id,
            ]);

            DB::commit();

            return $payment;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to accept partial payment', [
                'engagement_id' => $engagement->id,
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
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
        if (! EngagementAuthorizationHelper::canRespondToPartialPayment($engagement, $authUser)) {
            throw new \Exception('Only the freelancer can dispute the partial payment.');
        }

        // Ensure payment is pending and not already accepted/disputed
        if ($payment->status !== PartialPaymentStatus::Pending) {
            throw new \Exception('This payment has already been accepted or disputed.');
        }

        DB::beginTransaction();
        try {
            // Update payment status initially
            $payment->update([
                'status' => PartialPaymentStatus::Disputed,
            ]);

            // Get or create cancellation record
            $cancellation = $engagement->cancellation;
            if (! $cancellation) {
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
                'dispute_id' => $dispute->id,
            ]);

            // Add evidence if provided
            if ($evidence) {
                $dispute->addEvidence($evidence);
            }

            // Mark engagement as disputed
            $engagement->markAsDisputed();

            // Notify the client about the dispute
            EngagementNotificationHelper::sendPaymentDisputedNotification($engagement, $payment, $dispute);

            // Notify admins a dispute needs review (same helper cancellation disputes already use)
            EngagementNotificationHelper::sendDisputeNotification($engagement, $cancellation);

            Log::info('Partial payment disputed', [
                'engagement_id' => $engagement->id,
                'payment_id' => $payment->id,
                'dispute_id' => $dispute->id,
                'freelancer_id' => $authUser->id,
            ]);

            DB::commit();

            return $dispute;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to dispute partial payment', [
                'engagement_id' => $engagement->id,
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Resolve a payment dispute (staff only, via the "resolve disputes" permission)
     */
    public function resolveDispute(JobPaymentDispute $dispute, $notes, $finalAmount = null)
    {
        // Only ever staff: members never settle disputes
        $authUser = Auth::guard('staff')->user();

        if (! $authUser) {
            throw new \Exception('Only staff can resolve payment disputes.');
        }

        // Ensure user has permission to resolve disputes
        if (! $authUser->can('resolve disputes')) {
            throw new \Exception('Only administrators can resolve payment disputes.');
        }

        // Ensure dispute is not already resolved
        if ($dispute->isResolved()) {
            throw new \Exception('This dispute has already been resolved.');
        }

        $cancellation = $dispute->cancellation;
        $engagement = $cancellation->engagement;

        // A resolution amount can't exceed what the engagement was ever worth —
        // same ceiling as the manual partial-payment override (see processPartialPayment()).
        if ($finalAmount !== null && $finalAmount > $engagement->net_amount) {
            throw new \Exception('Resolution amount cannot exceed the engagement\'s net amount.');
        }

        // For a funded job the amount is paid out of what is left in escrow, so it cannot be more than that
        if ($finalAmount !== null && $engagement->escrow_minor > 0 && (int) round($finalAmount * 100) > app(EscrowService::class)->remainingNetMinor($engagement)) {
            throw new \Exception('Resolution amount cannot exceed what is left in escrow for the freelancer ('.Money::formatMinor(app(EscrowService::class)->remainingNetMinor($engagement)).').');
        }

        DB::beginTransaction();
        try {
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
                    'status' => PartialPaymentStatus::Finalized,
                    'notes' => 'Payment after dispute resolution: '.$notes,
                    // Created by the settlement, not processed by a member: `finalized_by` records which staff member settled it
                    'processed_by' => null,
                    'processed_at' => now(),
                    'finalized_at' => now(),
                    'finalized_by' => $authUser->id,
                    'dispute_id' => $dispute->id,
                ]);
            }

            // A funded job pays the decided amount out of escrow, and what is left becomes the client's to have back
            $this->payFromEscrow($engagement, $dispute, (float) ($finalAmount ?? $cancellation->partial_payment_amount ?? 0), "dispute:{$dispute->id}", $authUser);

            // Mark engagement as settled
            $engagement->markAsSettled();
            app(EscrowService::class)->closeOut($engagement);

            // Notify involved parties: $engagement->poster and $engagement->applicant are already
            // User models (hasOneThrough) — no ->user needed when wiring up notifications here.
            // Create and send notifications
            // You would need to implement these notification classes

            Log::info('Dispute resolved', [
                'engagement_id' => $engagement->id,
                'dispute_id' => $dispute->id,
                'admin_id' => $authUser->id,
                'final_amount' => $finalAmount ?? $cancellation->partial_payment_amount,
            ]);

            DB::commit();

            return [
                'dispute' => $dispute,
                'payment' => $payment,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to resolve dispute', [
                'dispute_id' => $dispute->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /** Pay the freelancer out of the job's escrow, if it was funded (an unfunded engagement moves no money, as before). */
    private function payFromEscrow(JobEngagement $engagement, $reference, float $amount, string $key, $by = null): void
    {
        if ($engagement->escrow_minor <= 0 || $amount <= 0) {
            return;
        }

        $engagement->loadMissing('application.job');

        app(EscrowService::class)->payExtra($engagement, (int) round($amount * 100), $key, 'Payment settled for "'.$engagement->application->job->title.'"', $reference, $by);
    }
}
