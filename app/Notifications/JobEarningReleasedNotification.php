<?php

namespace App\Notifications;

use App\Models\JobEngagement;
use App\Support\Mail\BrandedMail;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/** Tells a freelancer money from a job has been released to their balance, after the client approved work. */
class JobEarningReleasedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public JobEngagement $engagement, public string $title, public int $amountMinor) {}

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        return BrandedMail::message('Payment released')
            ->subject(Money::formatMinor($this->amountMinor)." released for \"{$this->title}\"")
            ->greeting("Hello {$notifiable->name},")
            ->line(Money::formatMinor($this->amountMinor)." for \"{$this->title}\" is now in your balance, after the platform service fee. You can withdraw it from your earnings.")
            ->action('See your earnings', route('earnings.index'));
    }

    public function toDatabase($notifiable)
    {
        return ['engagement_id' => $this->engagement->id, 'title' => $this->title, 'amount_minor' => $this->amountMinor, 'url' => route('earnings.index')];
    }

    public static function present(array $data): array
    {
        return [
            'title' => 'Payment released',
            'icon' => 'success',
            'content' => Money::formatMinor((int) ($data['amount_minor'] ?? 0)).' for "'.($data['title'] ?? 'your job').'" is in your balance.',
            'action_url' => $data['url'] ?? null,
            'action_label' => 'See earnings',
        ];
    }
}
