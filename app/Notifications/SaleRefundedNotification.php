<?php

namespace App\Notifications;

use App\Models\Payment;
use App\Support\Mail\BrandedMail;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/** Tells a seller one of their sales was refunded and that their share of it was taken back. */
class SaleRefundedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Payment $payment) {}

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        $title = $this->payment->product?->title ?? 'your model';

        return BrandedMail::message('A sale was refunded')
            ->subject("A sale of \"{$title}\" was refunded")
            ->greeting("Hello {$notifiable->name},")
            ->line("The buyer's payment for \"{$title}\" was refunded, so the buyer's licence has ended and your share of that sale, ".Money::formatMinor($this->payment->seller_share_minor).', was taken back from your earnings.')
            ->line('Reason: '.$this->payment->refund_reason)
            ->action('See your earnings', route('earnings.index'));
    }

    public function toDatabase($notifiable)
    {
        return ['payment_id' => $this->payment->id, 'title' => $this->payment->product?->title, 'share_minor' => $this->payment->seller_share_minor, 'reason' => $this->payment->refund_reason, 'url' => route('earnings.index')];
    }

    public static function present(array $data): array
    {
        return [
            'title' => 'A sale was refunded',
            'icon' => 'danger',
            'content' => '"'.($data['title'] ?? 'Your model').'" was refunded and '.Money::formatMinor((int) ($data['share_minor'] ?? 0)).' was taken back from your earnings.',
            'action_url' => $data['url'] ?? null,
            'action_label' => 'See earnings',
        ];
    }
}
