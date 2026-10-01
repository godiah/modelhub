<?php

namespace App\Notifications;

use App\Models\ModelJob;
use App\Support\Mail\BrandedMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/** Tells a poster that staff put their project back on the board. */
class ProjectRestoredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ModelJob $job) {}

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        return BrandedMail::message('Your project is back')
            ->subject("\"{$this->job->title}\" is back on the board")
            ->greeting("Hello {$notifiable->name},")
            ->line("Our staff restored your project \"{$this->job->title}\".")
            ->action('Open your projects', route('my-jobs.index'));
    }

    public function toDatabase($notifiable)
    {
        return ['job_id' => $this->job->id, 'title' => $this->job->title, 'url' => route('my-jobs.index')];
    }

    public static function present(array $data): array
    {
        return [
            'title' => 'Your project is back',
            'icon' => 'success',
            'content' => '"'.($data['title'] ?? 'Your project').'" was restored by our staff.',
            'action_url' => $data['url'] ?? null,
            'action_label' => 'View projects',
        ];
    }
}
