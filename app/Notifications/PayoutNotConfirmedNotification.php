<?php

namespace App\Notifications;

use App\Models\Payout;
use App\Support\Mail\BrandedMail;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/** Tells staff who approve withdrawals that one was sent but M-Pesa has not confirmed it, so they can check the portal and settle it. */
class PayoutNotConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Payout $payout) {}

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        return BrandedMail::message('A withdrawal has not been confirmed')
            ->subject('Withdrawal not confirmed: '.Money::formatMinor($this->payout->amount_minor))
            ->greeting("Hello {$notifiable->name},")
            ->line("The withdrawal {$this->payout->reference} of ".Money::formatMinor($this->payout->net_minor).' was sent but M-Pesa has not confirmed it.')
            ->line('Check the M-Pesa portal for this payment, then mark it as sent (with the receipt) or not sent.')
            ->action('Open the payouts queue', route('admin.payouts.index', ['status' => 'processing']));
    }

    public function toDatabase($notifiable)
    {
        return ['payout_id' => $this->payout->id, 'reference' => $this->payout->reference, 'amount_minor' => $this->payout->net_minor, 'url' => route('admin.payouts.index', ['status' => 'processing'])];
    }

    public static function present(array $data): array
    {
        return [
            'title' => 'Withdrawal not confirmed',
            'icon' => 'info',
            'content' => 'Withdrawal '.($data['reference'] ?? '').' of '.Money::formatMinor((int) ($data['amount_minor'] ?? 0)).' was sent but M-Pesa has not confirmed it. Check the portal and settle it.',
            'action_url' => $data['url'] ?? null,
            'action_label' => 'Settle',
        ];
    }
}
