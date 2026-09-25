<?php

namespace App\Notifications;

use App\Models\JobApplication;
use App\Models\JobEngagement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HiredNotification extends Notification implements ShouldQueue
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
        return ['mail', 'database', 'broadcast'];
    }

    public function toMail($notifiable)
    {
        $job = $this->application->job;

        return (new MailMessage)
            ->subject("You've been hired for {$job->title}")
            ->view('emails.application.hired', [
                'application' => $this->application,
                'engagement' => $this->engagement,
            ]);
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
            'message_preview' => "You've been hired for the job: {$job->title}. Click to view details.",
            'url' => route('engagements.response-form', $this->application->id),
        ];
    }

    public static function present(array $data): array
    {
        return [
            'title' => "You've been hired!",
            'icon' => 'success',
            'content' => 'You\'ve been hired for job: '.($data['job_title'] ?? 'a job posting')
                .' with an agreed amount of '.config('app.currency_symbol').number_format($data['agreed_amount'] ?? 0, 2),
            'action_url' => $data['url'] ?? null,
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
            'message_preview' => "You've been hired for the job: {$job->title}",
        ]);
    }
}
