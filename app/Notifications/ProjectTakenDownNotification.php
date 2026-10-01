<?php

namespace App\Notifications;

use App\Models\ModelJob;
use App\Support\Mail\BrandedMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/** Tells a poster that staff took their project down, and why. */
class ProjectTakenDownNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ModelJob $job) {}

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        return BrandedMail::message('Your project was taken down')
            ->subject("\"{$this->job->title}\" was taken down")
            ->greeting("Hello {$notifiable->name},")
            ->line("Our staff took your project \"{$this->job->title}\" off the board, so it no longer accepts applications.")
            ->line('Reason: '.$this->job->taken_down_reason)
            ->line('If you think this is a mistake, write to '.(config('mail.support_address') ?: config('mail.from.address')).'.')
            ->action('Open your projects', route('my-jobs.index'));
    }

    public function toDatabase($notifiable)
    {
        return ['job_id' => $this->job->id, 'title' => $this->job->title, 'reason' => $this->job->taken_down_reason, 'restored' => false, 'url' => route('my-jobs.index')];
    }

    public static function present(array $data): array
    {
        return [
            'title' => 'Your project was taken down',
            'icon' => 'danger',
            'content' => '"'.($data['title'] ?? 'Your project').'": '.($data['reason'] ?? 'it broke the rules.'),
            'action_url' => $data['url'] ?? null,
            'action_label' => 'View projects',
        ];
    }
}
