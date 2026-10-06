<?php

/**
 * EngagementCancellationService
 *
 * Handles engagement cancellation and dispute management.
 * Manages cancellation requests, dispute creation, and related notifications.
 */

namespace App\Services\Engagements;

use App\Enums\EngagementStatus;
use App\Helpers\Engagements\EngagementAuthorizationHelper;
use App\Helpers\Engagements\EngagementNotificationHelper;
use App\Helpers\FlashAlertHelper;
use App\Models\JobEngagement;
use App\Models\User;
use App\Services\Payments\EscrowService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EngagementCancellationService
{
    // Cancel an engagement
    public function cancelEngagement(JobEngagement $engagement, array $cancellationData): array
    {
        $user = Auth::user();

        // Authorization check
        if (! EngagementAuthorizationHelper::canCancelEngagement($engagement, $user)) {
            return [
                'success' => false,
                'error' => 'You are not authorized to cancel this engagement.',
            ];
        }

        // Check if engagement can be cancelled
        if ($engagement->status === EngagementStatus::Completed) {
            return [
                'success' => false,
                'error' => 'Cannot cancel a completed engagement.',
            ];
        }

        // Already in a dispute or settled: there is nothing left to cancel, and a second cancellation would reopen a decision
        if (in_array($engagement->status, [EngagementStatus::Disputed, EngagementStatus::Settled], true)) {
            return [
                'success' => false,
                'error' => 'This engagement can no longer be cancelled.',
            ];
        }

        // A client cannot cancel "as the freelancer" (that makes the escrow refund due at once, skipping the review window), or the reverse
        if (! $this->typeAllowedFor($engagement, $user, $cancellationData['cancellation_type'])) {
            return [
                'success' => false,
                'error' => 'That kind of cancellation is not available to you.',
            ];
        }

        // Check for existing cancellation
        if ($this->hasExistingCancellation($engagement, $user)) {
            return [
                'success' => false,
                'error' => 'You have already submitted a cancellation for this engagement.',
            ];
        }

        DB::beginTransaction();
        try {
            // Create cancellation record
            $cancellation = $this->createCancellation($engagement, $user, $cancellationData);

            // Update engagement status
            $engagement->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'notes' => $cancellationData['cancellation_reason'],
            ]);

            // Money held in escrow for the job is now the client's to have back (after their review window, unless the freelancer walked away)
            app(EscrowService::class)->onCancelled($engagement, $cancellationData['cancellation_type']);

            // Cancelling as a dispute opens a real dispute for staff to decide (so the link in their notification works), and nothing
            // held in escrow is returned until they have
            if ($cancellationData['cancellation_type'] === 'dispute') {
                $cancellation->createDispute($cancellationData['reason_category'], $cancellationData['cancellation_reason'], $user->id);
                $engagement->markAsDisputed();
                app(EscrowService::class)->freeze($engagement);

                EngagementNotificationHelper::sendDisputeNotification($engagement, $cancellation);
            }

            // Send cancellation notification
            EngagementNotificationHelper::sendCancellationNotification($engagement, $cancellation, $user);

            DB::commit();

            return [
                'success' => true,
                'cancellation' => $cancellation,
                'message' => 'Engagement cancelled successfully. All parties have been notified.',
                'alert' => FlashAlertHelper::success('Engagement cancelled successfully.')['alert'],
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'error' => 'Failed to cancel engagement: '.$e->getMessage(),
            ];
        }
    }

    // Which kinds of cancellation this person may choose: their own, a mutual one, or a dispute
    protected function typeAllowedFor(JobEngagement $engagement, User $user, string $type): bool
    {
        $application = $engagement->application;

        $allowed = match (true) {
            $user->id === $application->poster_id => ['mutual', 'client_initiated', 'dispute'],
            $user->id === $application->applicant_id => ['mutual', 'freelancer_initiated', 'dispute'],
            default => [],
        };

        return in_array($type, $allowed, true);
    }

    // Create cancellation record
    protected function createCancellation(JobEngagement $engagement, User $user, array $cancellationData): mixed
    {
        return $engagement->cancellation()->create([
            'initiator_id' => $user->id,
            'cancellation_type' => $cancellationData['cancellation_type'],
            'reason_category' => $cancellationData['reason_category'],
            'reason_details' => $cancellationData['cancellation_reason'],
            'is_dispute' => $cancellationData['cancellation_type'] === 'dispute',
        ]);
    }

    // Check for existing cancellation by user
    protected function hasExistingCancellation(JobEngagement $engagement, User $user): bool
    {
        return $engagement->cancellation()
            ->where('initiator_id', $user->id)
            ->exists();
    }

    // Get cancelled engagement details
    public function getCancelledEngagementDetails(int $engagementId): array
    {
        $engagement = JobEngagement::with([
            'deliverables',
            'application.job',
            'application.poster.profile',
            'application.applicant.profile',
            'cancellation.initiator.profile',
            'cancellation.dispute',
        ])->findOrFail($engagementId);
        $user = Auth::user();

        // Authorization check
        if (! EngagementAuthorizationHelper::canView($engagement, $user)) {
            throw new \Exception('Unauthorized Access.');
        }

        // Check engagement status
        if (! in_array($engagement->status, [EngagementStatus::Cancelled, EngagementStatus::Settled, EngagementStatus::Disputed])) {
            throw new \Exception('Unauthorized Action');
        }

        return [
            'engagement' => $engagement,
            'user' => $user,
        ];
    }

    // Get disputed engagement details
    public function getDisputedEngagementDetails(int $engagementId): array
    {
        $engagement = JobEngagement::with([
            'application.job',
            'application.poster.profile',
            'application.applicant.profile',
            'cancellation.dispute.disputedBy.profile',
            'cancellation.dispute.resolvedBy',
            'cancellation.dispute.partialPayment.processor',
        ])->findOrFail($engagementId);

        $user = Auth::user();

        // Authorization check
        if (! EngagementAuthorizationHelper::canView($engagement, $user)) {
            throw new \Exception('Unauthorized Access.');
        }

        // Check engagement status
        if (! in_array($engagement->status, [EngagementStatus::Settled, EngagementStatus::Disputed])) {
            throw new \Exception('Unauthorized Action');
        }

        return [
            'engagement' => $engagement,
            'dispute' => $engagement->cancellation->dispute ?? null,
            'partialPayment' => optional($engagement->cancellation->dispute)->partialPayment,
        ];
    }

    // Reopen job after cancellation
    public function reopenJob(JobEngagement $engagement): array
    {
        $user = Auth::user();

        // Authorization check
        if (! EngagementAuthorizationHelper::canReopenJob($engagement, $user)) {
            return [
                'success' => false,
                'error' => 'Only the job poster can reopen this job.',
            ];
        }

        // Check engagement status
        if (! in_array($engagement->status, [EngagementStatus::Cancelled, EngagementStatus::Settled])) {
            return [
                'success' => false,
                'error' => 'Only cancelled or settled engagements can have their jobs reopened.',
            ];
        }

        try {
            $job = $engagement->job;
            $job->update(['is_active' => true]);

            return [
                'success' => true,
                'message' => 'Job has been reopened successfully',
                'alert' => FlashAlertHelper::success('Job has been reopened successfully')['alert'],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to reopen job: '.$e->getMessage(),
            ];
        }
    }
}
