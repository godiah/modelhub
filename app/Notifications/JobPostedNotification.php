<?php

namespace App\Notifications;

use App\Models\ModelJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JobPostedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $job;

    /**
     * Create a new notification instance.
     */
    public function __construct(ModelJob $job)
    {
        $this->job = $job;
    }

    /**
     * Get the notification channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Project Has Been Posted!')
            ->view(
                'emails.jobs.posted',
                ['job' => $this->job]
            );
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'job_id' => $this->job->id,
            'job_slug' => $this->job->slug,
            'job_title' => $this->job->title,
            'message' => 'Your project has been posted successfully.',
            'url' => route('jobs.show', $this->job->slug),
        ];
    }

    public static function present(array $data): array
    {
        return [
            'title' => 'Job Posted',
            'icon' => 'info',
            'content' => $data['message'] ?? 'Your project "'.($data['job_title'] ?? 'a job').'" has been posted successfully.',
            'action_url' => $data['url'] ?? null,
        ];
    }
}
