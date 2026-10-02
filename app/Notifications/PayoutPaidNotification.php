<?php

namespace App\Notifications;

use App\Models\Payout;
use App\Support\Mail\BrandedMail;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/** Tells a member their withdrawal reached their phone. */
class PayoutPaidNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Payout $payout) {}

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        return BrandedMail::message('Your withdrawal was sent')
            ->subject('Your withdrawal of '.Money::formatMinor($this->payout->net_minor).' was sent')
            ->greeting("Hello {$notifiable->name},")
            ->line(Money::formatMinor($this->payout->net_minor).' was sent to your M-Pesa number ending '.substr($this->payout->msisdn, -3).'.'.($this->payout->receipt ? " M-Pesa receipt: {$this->payout->receipt}." : ''))
            ->line('You asked for '.Money::formatMinor($this->payout->amount_minor).'; the withdrawal fee was '.Money::formatMinor($this->payout->fee_minor).'.')
            ->action('See your earnings', route('earnings.index'));
    }

    public function toDatabase($notifiable)
    {
        return ['payout_id' => $this->payout->id, 'net_minor' => $this->payout->net_minor, 'receipt' => $this->payout->receipt, 'url' => route('earnings.index')];
    }

    public static function present(array $data): array
    {
        return [
            'title' => 'Withdrawal sent',
            'icon' => 'success',
            'content' => Money::formatMinor((int) ($data['net_minor'] ?? 0)).' was sent to your M-Pesa'.(! empty($data['receipt']) ? " (receipt {$data['receipt']})" : '').'.',
            'action_url' => $data['url'] ?? null,
            'action_label' => 'See earnings',
        ];
    }
}
