<?php

namespace App\Notifications;

use App\Models\JobApplication;
use App\Models\JobEngagement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HiredNotification extends Notification
{
    use Queueable;

    public $application;
    public $engagement;

    public function __construct(JobApplication $application, JobEngagement $engagement)
    {
        $this->application = $application;
        $this->engagement = $engagement;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        $job = $this->application->job;

        return [
            'application_id' => $this->application->id,
            'engagement_id' => $this->engagement->id,
            'job_id' => $job->id,
            'job_slug' => $job->slug,
            'job_title' => $job->title,
            'employer_name' => $job->user->name,
            'agreed_amount' => $this->engagement->agreed_amount,
            'message_preview' => "You've been hired for the job: {$job->title}. Click to view details."
        ];
    }

    public function toBroadcast($notifiable)
    {
        $job = $this->application->job;

        return new BroadcastMessage([
            'application_id' => $this->application->id,
            'engagement_id' => $this->engagement->id,
            'job_id' => $job->id,
            'job_slug' => $job->slug,
            'job_title' => $job->title,
            'employer_name' => $job->user->name,
            'agreed_amount' => $this->engagement->agreed_amount,
            'subject' => "You've been hired!",
            'message_preview' => "You've been hired for the job: {$job->title}"
        ]);
    }
}
