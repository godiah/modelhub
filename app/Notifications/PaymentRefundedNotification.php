<?php

namespace App\Notifications;

use App\Models\Payment;
use App\Support\Mail\BrandedMail;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/** Tells a buyer their payment was refunded, and that a refunded sale ends its licence. */
class PaymentRefundedNotification extends Notification implements ShouldQueue
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
        $amount = Money::formatMinor($this->payment->received_minor ?? $this->payment->amount_minor);

        return BrandedMail::message('Your payment was refunded')
            ->subject("Refund for \"{$title}\"")
            ->greeting("Hello {$notifiable->name},")
            ->line("We have refunded your payment of {$amount} for \"{$title}\" (payment {$this->payment->reference}).")
            ->line('Reason: '.$this->payment->refund_reason)
            ->line('The money is sent back to the M-Pesa number you paid from. A refund also ends the licence for that model, so please stop using its files in new work.')
            ->action('See your licences', route('licences.index'));
    }

    public function toDatabase($notifiable)
    {
        return ['payment_id' => $this->payment->id, 'title' => $this->payment->product?->title, 'amount_minor' => $this->payment->received_minor ?? $this->payment->amount_minor, 'reason' => $this->payment->refund_reason, 'url' => route('licences.index')];
    }

    public static function present(array $data): array
    {
        return [
            'title' => 'Payment refunded',
            'icon' => 'info',
            'content' => Money::formatMinor((int) ($data['amount_minor'] ?? 0)).' for "'.($data['title'] ?? 'a model').'" was refunded: '.($data['reason'] ?? ''),
            'action_url' => $data['url'] ?? null,
            'action_label' => 'See licences',
        ];
    }
}
