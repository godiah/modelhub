<?php

namespace App\Notifications;

use App\Models\IssuedLicence;
use App\Models\Payment;
use App\Support\Mail\BrandedMail;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/** The buyer's receipt: what they paid for, the M-Pesa receipt, and where their licence and files are. */
class ModelPurchasedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Payment $payment, public IssuedLicence $licence) {}

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        $mail = BrandedMail::message('Your purchase')
            ->subject("Your {$this->licence->tier->label()} licence for \"{$this->licence->product_title}\"")
            ->greeting("Hello {$notifiable->name},")
            ->line("Thank you for buying \"{$this->licence->product_title}\" from {$this->licence->seller_name}.")
            ->line('Amount paid: '.Money::formatMinor($this->payment->amount_minor).($this->payment->receipt ? " (M-Pesa receipt {$this->payment->receipt})" : ''))
            ->line("Your {$this->licence->tier->label()} licence number is {$this->licence->key}. It lists what you may do with the model, and you can print it for your records.")
            ->action('Open your licence and download your files', route('licences.show', $this->licence));

        return $mail;
    }

    public function toDatabase($notifiable)
    {
        return ['payment_id' => $this->payment->id, 'title' => $this->licence->product_title, 'tier' => $this->licence->tier->label(), 'key' => $this->licence->key, 'url' => route('licences.show', $this->licence)];
    }

    public static function present(array $data): array
    {
        return [
            'title' => 'Purchase complete',
            'icon' => 'success',
            'content' => 'You bought "'.($data['title'] ?? 'a model').'" with a '.($data['tier'] ?? 'Standard').' licence.',
            'action_url' => $data['url'] ?? null,
            'action_label' => 'Open licence',
        ];
    }
}
