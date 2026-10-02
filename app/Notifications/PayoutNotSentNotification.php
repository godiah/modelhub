<?php

namespace App\Notifications;

use App\Models\Payout;
use App\Support\Mail\BrandedMail;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/** Tells a member their withdrawal was not sent (turned down by staff, or the transfer failed) and that the money is back with them. */
class PayoutNotSentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Payout $payout) {}

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        return BrandedMail::message('Your withdrawal was not sent')
            ->subject('Your withdrawal of '.Money::formatMinor($this->payout->amount_minor).' was not sent')
            ->greeting("Hello {$notifiable->name},")
            ->line('Your withdrawal of '.Money::formatMinor($this->payout->amount_minor).' could not be sent.'.($this->payout->failure_reason ? " Reason: {$this->payout->failure_reason}" : ''))
            ->line('The money is back in your available balance. You can check the number and ask again.')
            ->action('See your earnings', route('earnings.index'));
    }

    public function toDatabase($notifiable)
    {
        return ['payout_id' => $this->payout->id, 'amount_minor' => $this->payout->amount_minor, 'reason' => $this->payout->failure_reason, 'url' => route('earnings.index')];
    }

    public static function present(array $data): array
    {
        return [
            'title' => 'Withdrawal not sent',
            'icon' => 'danger',
            'content' => Money::formatMinor((int) ($data['amount_minor'] ?? 0)).' was not sent'.(! empty($data['reason']) ? ": {$data['reason']}" : '').'. It is back in your balance.',
            'action_url' => $data['url'] ?? null,
            'action_label' => 'See earnings',
        ];
    }
}
