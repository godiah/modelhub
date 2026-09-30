<?php

namespace App\Notifications;

use App\Models\JobApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Tells the poster a freelancer withdrew their application. */
class ApplicationWithdrawnNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public JobApplication $application) {}

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        $job = $this->application->job;

        return (new MailMessage)
            ->subject("{$this->application->applicant->name} withdrew their application")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$this->application->applicant->name} withdrew their application for \"{$job->title}\".")
            ->line('It stays in your applications list as Withdrawn, and your project is unaffected.')
            ->action('View applications', route('my-jobs.applications.index', $job->slug));
    }

    public function toDatabase($notifiable)
    {
        $job = $this->application->job;

        return [
            'application_id' => $this->application->id,
            'job_id' => $job->id,
            'job_slug' => $job->slug,
            'job_title' => $job->title,
            'applicant_id' => $this->application->applicant_id,
            'applicant_name' => $this->application->applicant->name,
            'url' => route('my-jobs.applications.show', $this->application),
        ];
    }

    public static function present(array $data): array
    {
        return [
            'title' => 'Application withdrawn',
            'icon' => 'info',
            'content' => ($data['applicant_name'] ?? 'An applicant').' withdrew their application for '.($data['job_title'] ?? 'your project'),
            'action_url' => $data['url'] ?? null,
            'action_label' => 'View application',
        ];
    }
}
