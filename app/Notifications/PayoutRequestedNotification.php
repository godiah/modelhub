<?php

namespace App\Notifications;

use App\Models\Payout;
use App\Support\Mail\BrandedMail;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/** Tells staff who approve withdrawals that one is waiting. */
class PayoutRequestedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Payout $payout) {}

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        return BrandedMail::message('A withdrawal is waiting')
            ->subject('Withdrawal to approve: '.Money::formatMinor($this->payout->amount_minor))
            ->greeting("Hello {$notifiable->name},")
            ->line("{$this->payout->user->name} asked to withdraw ".Money::formatMinor($this->payout->amount_minor).'.')
            ->action('Open the payouts queue', route('admin.payouts.index'));
    }

    public function toDatabase($notifiable)
    {
        return ['payout_id' => $this->payout->id, 'name' => $this->payout->user->name, 'amount_minor' => $this->payout->amount_minor, 'url' => route('admin.payouts.index')];
    }

    public static function present(array $data): array
    {
        return [
            'title' => 'Withdrawal to approve',
            'icon' => 'info',
            'content' => ($data['name'] ?? 'A member').' asked to withdraw '.Money::formatMinor((int) ($data['amount_minor'] ?? 0)).'.',
            'action_url' => $data['url'] ?? null,
            'action_label' => 'Review',
        ];
    }
}
