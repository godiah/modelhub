<?php

/**
 * EngagementCancellationService
 * 
 * Handles engagement cancellation and dispute management.
 * Manages cancellation requests, dispute creation, and related notifications.
*/

namespace App\Services\Engagements;

use App\Helpers\Engagements\EngagementAuthorizationHelper;
use App\Helpers\Engagements\EngagementNotificationHelper;
use App\Models\JobEngagement;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EngagementCancellationService
{
    // Cancel an engagement
    public function cancelEngagement(JobEngagement $engagement, array $cancellationData): array
    {
        $user = Auth::user();
        
        // Authorization check
        if (!EngagementAuthorizationHelper::canCancelEngagement($engagement, $user)) {
            return [
                'success' => false,
                'error' => 'You are not authorized to cancel this engagement.'
            ];
        }

        // Check if engagement can be cancelled
        if ($engagement->status === 'completed') {
            return [
                'success' => false,
                'error' => 'Cannot cancel a completed engagement.'
            ];
        }

        // Check for existing cancellation
        if ($this->hasExistingCancellation($engagement, $user)) {
            return [
                'success' => false,
                'error' => 'You have already submitted a cancellation for this engagement.'
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

            // Handle dispute notifications
            if ($cancellationData['cancellation_type'] === 'dispute') {
                EngagementNotificationHelper::sendDisputeNotification($engagement, $cancellation);
            }

            // Send cancellation notification
            EngagementNotificationHelper::sendCancellationNotification($engagement, $cancellation, $user);

            DB::commit();

            return [
                'success' => true,
                'cancellation' => $cancellation,
                'message' => 'Engagement cancelled successfully. All parties have been notified.',
                'alert' => [
                    'type' => 'success',
                    'title' => 'Engagement cancelled successfully.',
                    'text' => 'Engagement cancelled successfully.',
                ]
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'error' => 'Failed to cancel engagement: ' . $e->getMessage()
            ];
        }
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
        $engagement = JobEngagement::findOrFail($engagementId);
        $user = Auth::user();

        // Authorization check
        if (!$this->canViewCancelledEngagement($engagement, $user)) {
            throw new \Exception('Unauthorized Access.');
        }

        // Check engagement status
        if (!in_array($engagement->status, ['cancelled', 'settled', 'disputed'])) {
            throw new \Exception('Unauthorized Action');
        }

        return [
            'engagement' => $engagement,
            'user' => $user
        ];
    }

    // Get disputed engagement details  
    public function getDisputedEngagementDetails(int $engagementId): array
    {
        $engagement = JobEngagement::with([
            'application',
            'cancellation.dispute.partialPayment',
        ])->findOrFail($engagementId);

        $user = Auth::user();

        // Authorization check
        if (!$this->canViewDisputedEngagement($engagement, $user)) {
            throw new \Exception('Unauthorized Access.');
        }

        // Check engagement status
        if (!in_array($engagement->status, ['settled', 'disputed'])) {
            throw new \Exception('Unauthorized Action');
        }

        return [
            'engagement' => $engagement,
            'dispute' => $engagement->cancellation->dispute ?? null,
            'partialPayment' => optional($engagement->cancellation->dispute)->partialPayment,
        ];
    }

    // Check if user can view cancelled engagement
    protected function canViewCancelledEngagement(JobEngagement $engagement, User $user): bool
    {
        $application = $engagement->application;
        
        return $user->id === $application->poster_id || 
               $user->id === $application->applicant_id || 
               $user->hasRole('admin');
    }

    // Check if user can view disputed engagement
    protected function canViewDisputedEngagement(JobEngagement $engagement, User $user): bool
    {
        return $this->canViewCancelledEngagement($engagement, $user);
    }

    // Reopen job after cancellation
    public function reopenJob(JobEngagement $engagement): array
    {
        $user = Auth::user();
        
        // Authorization check
        if (!EngagementAuthorizationHelper::canReopenJob($engagement, $user)) {
            return [
                'success' => false,
                'error' => 'Only the job poster can reopen this job.'
            ];
        }

        // Check engagement status
        if (!in_array($engagement->status, ['cancelled', 'settled'])) {
            return [
                'success' => false,
                'error' => 'Only cancelled or settled engagements can have their jobs reopened.'
            ];
        }

        try {
            $job = $engagement->job;
            $job->update(['is_active' => true]);

            return [
                'success' => true,
                'message' => 'Job has been reopened successfully',
                'alert' => [
                    'type' => 'success',
                    'title' => 'Job has been reopened successfully',
                    'text' => 'Job has been reopened successfully',
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to reopen job: ' . $e->getMessage()
            ];
        }
    }
}
