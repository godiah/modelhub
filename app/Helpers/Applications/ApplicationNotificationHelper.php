<?php

/**
 * ApplicationNotificationHelper
 *
 * Centralizes notification sending for application-related events (messaging, hiring),
 * matching the EngagementNotificationHelper convention.
 */

namespace App\Helpers\Applications;

use App\Models\ApplicantMessage;
use App\Models\JobApplication;
use App\Models\JobEngagement;
use App\Notifications\HiredNotification;
use App\Notifications\NewApplicationMessage;

class ApplicationNotificationHelper
{
    // Send new-message notification to the applicant
    public static function sendNewMessageNotification(ApplicantMessage $message, JobApplication $application): void
    {
        $application->applicant->notify(new NewApplicationMessage($message));
    }

    // Send hired notification to the applicant
    public static function sendHiredNotification(JobApplication $application, JobEngagement $engagement): void
    {
        $application->applicant->notify(new HiredNotification($application, $engagement));
    }
}
