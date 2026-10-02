<?php

namespace App\Notifications;

use App\Models\JobEngagement;
use App\Support\Mail\BrandedMail;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/** Tells a client the freelancer accepted their offer, and that work starts once they put the money into escrow. */
class JobAwaitingFundingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public JobEngagement $engagement, public string $title, public string $freelancer, public int $amountMinor) {}

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        return BrandedMail::message('Fund the job to start')
            ->subject("{$this->freelancer} accepted: fund \"{$this->title}\" to start")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$this->freelancer} accepted your offer for \"{$this->title}\".")
            ->line('Work starts once you put '.Money::formatMinor($this->amountMinor).' into escrow by M-Pesa. It is held safely and released to the freelancer only as you approve each deliverable.')
            ->action('Fund the job', route('engagements.show', $this->engagement));
    }

    public function toDatabase($notifiable)
    {
        return ['engagement_id' => $this->engagement->id, 'title' => $this->title, 'name' => $this->freelancer, 'amount_minor' => $this->amountMinor, 'url' => route('engagements.show', $this->engagement)];
    }

    public static function present(array $data): array
    {
        return [
            'title' => 'Fund the job to start',
            'icon' => 'info',
            'content' => ($data['name'] ?? 'The freelancer').' accepted "'.($data['title'] ?? 'your offer').'". Put '.Money::formatMinor((int) ($data['amount_minor'] ?? 0)).' into escrow to start work.',
            'action_url' => $data['url'] ?? null,
            'action_label' => 'Fund the job',
        ];
    }
}
