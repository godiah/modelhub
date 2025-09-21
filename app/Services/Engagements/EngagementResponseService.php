<?php

/**
 * EngagementResponseService
 * 
 * Handles engagement offer responses and related job status updates.
 * Manages acceptance or rejection of engagement offers and their consequences.
*/

namespace App\Services\Engagements;

use App\Helpers\Engagements\EngagementAuthorizationHelper;
use App\Helpers\Engagements\EngagementNotificationHelper;
use App\Models\JobEngagement;
use Illuminate\Support\Facades\Auth;

class EngagementResponseService
{
    // Respond to engagement offer
    public function respondToOffer(JobEngagement $engagement, array $responseData): array
    {
        $user = Auth::user();
        
        // Authorization check
        if (!EngagementAuthorizationHelper::canRespondToOffer($engagement, $user)) {
            throw new \Exception('You are not authorized to respond to this engagement offer.');
        }

        $response = $responseData['response'];
        $notes = $responseData['notes'];

        if ($response === 'accepted') {
            return $this->acceptOffer($engagement, $notes);
        } else {
            return $this->declineOffer($engagement, $notes);
        }
    }

    // Accept engagement offer
    protected function acceptOffer(JobEngagement $engagement, ?string $notes): array
    {
        // Update engagement
        $engagement->update([
            'status' => 'active',
            'started_at' => now(),
            'notes' => $notes
        ]);

        // Update application status
        $engagement->application->update(['status' => 'hired']);

        // Close the job so others can't apply
        $job = $engagement->application->job;
        $job->update(['is_active' => false]);

        // Send notification
        EngagementNotificationHelper::sendResponseNotification($engagement, 'accepted', $notes);

        return [
            'message' => 'Offer accepted successfully! Your engagement has started.',
            'alert_type' => 'success',
            'alert_text' => 'You can now start working on the project deliverables.'
        ];
    }

    // Decline engagement offer
    protected function declineOffer(JobEngagement $engagement, ?string $notes): array
    {
        // Update engagement
        $engagement->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'notes' => $notes
        ]);

        // Update application status
        $engagement->application->update(['status' => 'withdrawn']);

        // Delete deliverables if declined
        $engagement->deliverables()->delete();

        // Send notification
        EngagementNotificationHelper::sendResponseNotification($engagement, 'declined', $notes);

        return [
            'message' => 'Offer declined. The job poster has been notified.',
            'alert_type' => 'info',
            'alert_text' => 'Thank you for your response.'
        ];
    }

    // Check if user can respond to engagement
    public function canUserRespond(JobEngagement $engagement): bool
    {
        return EngagementAuthorizationHelper::canRespondToOffer($engagement, Auth::user());
    }
}
