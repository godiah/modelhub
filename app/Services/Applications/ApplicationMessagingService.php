<?php

// Handle messaging between employers/applicants

namespace App\Services\Applications;

use App\Mail\ApplicationMessage;
use App\Models\ApplicantMessage;
use App\Models\JobApplication;
use App\Notifications\NewApplicationMessage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class ApplicationMessagingService
{
    // Send message to applicant
    public function sendMessage(JobApplication $application, array $messageData): ApplicantMessage
    {
        // Ensure the current user is the owner of this job posting
        if (Auth::user()->id !== $application->job->user_id) {
            abort(403, 'Unauthorized action.');
        }

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

    // Check authorization for sending messages
    public function authorizeMessageSending(JobApplication $application): bool
    {
        return Auth::user()->id === $application->job->user_id;
    }

    // Send message notifications
    protected function sendMessageNotifications(ApplicantMessage $message, JobApplication $application): void
    {
        // Send email notification (queued)
        Mail::to($application->applicant->email)
            ->queue(new ApplicationMessage($message, $application));

        // Create in-app notification
        $application->applicant->notify(new NewApplicationMessage($message));
    }
}
