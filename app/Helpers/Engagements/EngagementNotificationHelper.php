<?php

/**
 * EngagementNotificationHelper
 * 
 * Centralizes notification sending for engagement-related events.
 * Handles notifications for responses, cancellations, disputes, and reviews.
*/

namespace App\Helpers\Engagements;

use App\Models\JobEngagement;
use App\Models\User;
use App\Notifications\DisputeCreatedNotification;
use App\Notifications\EngagementCancelledNotification;
use App\Notifications\EngagementResponseNotification;
use Illuminate\Support\Facades\Notification;

class EngagementNotificationHelper
{
    // Send engagement response notification
    public static function sendResponseNotification(JobEngagement $engagement, string $response, ?string $notes = null): void
    {
        $jobPoster = $engagement->application->poster;
        $jobPoster->notify(new EngagementResponseNotification($engagement, $response, $notes));
    }

    // Send engagement cancellation notification
    public static function sendCancellationNotification(JobEngagement $engagement, $cancellation, User $initiator): void
    {
        // Determine who to notify
        $application = $engagement->application;
        $userToNotify = ($initiator->id === $application->poster_id) 
            ? $application->applicant 
            : $application->poster;

        $userToNotify->notify(new EngagementCancelledNotification($engagement, $cancellation));
    }

    // Send dispute creation notification to admins
    public static function sendDisputeNotification(JobEngagement $engagement, $cancellation): void
    {
        // Get all users with admin role
        $adminUsers = User::role('admin')->get();
        
        // Notify admins about dispute
        Notification::send($adminUsers, new DisputeCreatedNotification($engagement, $cancellation));
    }

    // Send review notification (if needed in future)
    public static function sendReviewNotification(JobEngagement $engagement, $review): void
    {
        // Implementation for review notifications if needed
        // Currently not implemented in original controller
    }

    // Send payment notification (wrapper for future payment notifications)
    public static function sendPaymentNotification(JobEngagement $engagement, $payment): void
    {
        // Implementation for payment notifications if needed
        // Would integrate with PartialPaymentService notifications
    }
}
