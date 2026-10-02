<?php

namespace App\Notifications;

use App\Models\Payment;
use App\Models\Product;
use App\Support\Mail\BrandedMail;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/** Tells a seller they made a sale, and what their share is. */
class ModelSoldNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Payment $payment, public Product $product) {}

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        $days = $this->payment->hold_days;

        return BrandedMail::message('You made a sale')
            ->subject("You sold \"{$this->product->title}\"")
            ->greeting("Hello {$notifiable->name},")
            ->line("Someone bought \"{$this->product->title}\" with a {$this->payment->tier->label()} licence.")
            ->line('Sale: '.Money::formatMinor($this->payment->amount_minor).'. Your share, after the platform commission, is '.Money::formatMinor($this->payment->seller_share_minor).'.')
            ->line($days > 0 ? "Your share becomes available to withdraw after {$days} ".($days === 1 ? 'day' : 'days').'.' : 'Your share is available to withdraw now.')
            ->action('See your model', route('models.show', $this->product));
    }

    public function toDatabase($notifiable)
    {
        return ['payment_id' => $this->payment->id, 'title' => $this->product->title, 'tier' => $this->payment->tier->label(), 'share_minor' => $this->payment->seller_share_minor, 'url' => route('models.show', $this->product)];
    }

    public static function present(array $data): array
    {
        return [
            'title' => 'You made a sale',
            'icon' => 'success',
            'content' => '"'.($data['title'] ?? 'Your model').'" sold with a '.($data['tier'] ?? 'Standard').' licence. Your share: '.Money::formatMinor((int) ($data['share_minor'] ?? 0)).'.',
            'action_url' => $data['url'] ?? null,
            'action_label' => 'View model',
        ];
    }
}
