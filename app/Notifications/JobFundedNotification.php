<?php

namespace App\Notifications;

use App\Models\JobEngagement;
use App\Support\Mail\BrandedMail;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/** Tells a freelancer the client has put the job's money into escrow, so work can start. */
class JobFundedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public JobEngagement $engagement, public string $title, public int $amountMinor) {}

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        return BrandedMail::message('The job is funded')
            ->subject("\"{$this->title}\" is funded: you can start")
            ->greeting("Hello {$notifiable->name},")
            ->line('The client has put '.Money::formatMinor($this->amountMinor)." into escrow for \"{$this->title}\". It is held safely and released to you as each deliverable is approved.")
            ->action('Start work', route('engagements.show', $this->engagement));
    }

    public function toDatabase($notifiable)
    {
        return ['engagement_id' => $this->engagement->id, 'title' => $this->title, 'amount_minor' => $this->amountMinor, 'url' => route('engagements.show', $this->engagement)];
    }

    public static function present(array $data): array
    {
        return [
            'title' => 'Job funded',
            'icon' => 'success',
            'content' => '"'.($data['title'] ?? 'Your job').'" is funded with '.Money::formatMinor((int) ($data['amount_minor'] ?? 0)).' in escrow. You can start work.',
            'action_url' => $data['url'] ?? null,
            'action_label' => 'Open the workspace',
        ];
    }
}
