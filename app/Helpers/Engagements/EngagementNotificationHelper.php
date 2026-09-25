<?php

/**
 * EngagementNotificationHelper
 *
 * Centralizes notification sending for engagement-related events.
 * Handles notifications for responses, cancellations, disputes, and reviews.
 */

namespace App\Helpers\Engagements;

use App\Models\JobEngagement;
use App\Models\JobPartialPayment;
use App\Models\JobPaymentDispute;
use App\Models\User;
use App\Notifications\DisputeCreatedNotification;
use App\Notifications\EngagementCancelledNotification;
use App\Notifications\EngagementResponseNotification;
use App\Notifications\PartialPaymentProcessedNotification;
use App\Notifications\PaymentAcceptedNotification;
use App\Notifications\PaymentDisputedNotification;
use App\Notifications\ReviewSubmittedNotification;
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

    // Send review notification to the reviewee
    public static function sendReviewNotification(JobEngagement $engagement, $review): void
    {
        $review->reviewee->notify(new ReviewSubmittedNotification($review));
    }

    // Send partial payment processed notification to the freelancer
    public static function sendPaymentNotification(JobEngagement $engagement, $payment): void
    {
        $engagement->applicant->notify(new PartialPaymentProcessedNotification($engagement, $payment));
    }

    // Send partial payment accepted notification to the client
    public static function sendPaymentAcceptedNotification(JobEngagement $engagement, JobPartialPayment $payment): void
    {
        $engagement->poster->notify(new PaymentAcceptedNotification($engagement, $payment));
    }

    // Send partial payment disputed notification to the client
    public static function sendPaymentDisputedNotification(JobEngagement $engagement, JobPartialPayment $payment, JobPaymentDispute $dispute): void
    {
        $engagement->poster->notify(new PaymentDisputedNotification($engagement, $payment, $dispute));
    }
}
