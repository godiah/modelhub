<?php

namespace App\Notifications;

use App\Models\JobEngagement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EngagementResponseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $engagement;
    public $response;
    public $notes;

    public function __construct(JobEngagement $engagement, string $response, ?string $notes = null)
    {
        $this->engagement = $engagement;
        $this->response = $response;
        $this->notes = $notes;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast', 'mail'];
    }

    public function toMail($notifiable)
    {
        $application = $this->engagement->application;
        $job = $application->job;
        $applicant = $application->applicant;

        $responseText = $this->response === 'accept' ? 'accepted' : 'declined';

        return (new MailMessage)
            ->subject("Engagement Response for {$job->title}")
            ->view(
                'emails.engagements.response', // Create this view
                [
                    'notifiable' => $notifiable,
                    'engagement' => $this->engagement,
                    'application' => $application,
                    'job' => $job,
                    'applicant' => $applicant,
                    'response' => $this->response,
                    'responseText' => $responseText,
                    'notes' => $this->notes,
                    'actionUrl' => url("/jobs/{$job->slug}/applications/{$application->id}")
                ]
            );
    }

    public function toDatabase($notifiable)
    {
        $application = $this->engagement->application;
        $job = $application->job;
        $applicant = $application->applicant;

        return [
            'engagement_id' => $this->engagement->id,
            'application_id' => $application->id,
            'job_id' => $job->id,
            'job_slug' => $job->slug,
            'job_title' => $job->title,
            'applicant_id' => $applicant->id,
            'applicant_name' => $applicant->name,
            'response' => $this->response,
            'notes' => $this->notes,
            'agreed_amount' => $this->engagement->agreed_amount,
        ];
    }

    public function toBroadcast($notifiable)
    {
        $application = $this->engagement->application;
        $job = $application->job;
        $applicant = $application->applicant;

        return new BroadcastMessage([
            'engagement_id' => $this->engagement->id,
            'application_id' => $application->id,
            'job_id' => $job->id,
            'job_slug' => $job->slug,
            'job_title' => $job->title,
            'applicant_id' => $applicant->id,
            'applicant_name' => $applicant->name,
            'response' => $this->response,
            'notes' => $this->notes,
            'agreed_amount' => $this->engagement->agreed_amount,
        ]);
    }
}
