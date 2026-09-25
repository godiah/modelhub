<?php

// Handle messaging between employers/applicants

namespace App\Services\Applications;

use App\Helpers\Applications\ApplicationNotificationHelper;
use App\Models\ApplicantMessage;
use App\Models\JobApplication;
use Illuminate\Support\Facades\Auth;

class ApplicationMessagingService
{
    // Send message to applicant
    public function sendMessage(JobApplication $application, array $messageData): ApplicantMessage
    {
        // Store the message in the database
        $message = ApplicantMessage::create([
            'job_application_id' => $application->id,
            'sender_id' => Auth::id(),
            'recipient_id' => $application->applicant_id,
            'subject' => $messageData['subject'],
            'message' => $messageData['message'],
        ]);

        // Send notifications
        $this->sendMessageNotifications($message, $application);

        return $message;
    }

    // Send message notifications
    protected function sendMessageNotifications(ApplicantMessage $message, JobApplication $application): void
    {
        ApplicationNotificationHelper::sendNewMessageNotification($message, $application);
    }
}
