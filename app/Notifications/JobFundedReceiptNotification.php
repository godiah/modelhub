<?php

namespace App\Notifications;

use App\Models\JobEngagement;
use App\Support\Mail\BrandedMail;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/** Tells a client their payment into a job's escrow arrived. */
class JobFundedReceiptNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public JobEngagement $engagement, public string $title, public int $amountMinor, public ?string $receipt = null) {}

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        $message = BrandedMail::message('Payment received')
            ->subject("Payment received for \"{$this->title}\"")
            ->greeting("Hello {$notifiable->name},")
            ->line('We received '.Money::formatMinor($this->amountMinor)." for \"{$this->title}\". It is held in escrow and released to the freelancer as you approve each deliverable.");

        if ($this->receipt) {
            $message->line("M-Pesa receipt: {$this->receipt}");
        }

        return $message->action('Open the workspace', route('engagements.show', $this->engagement));
    }

    public function toDatabase($notifiable)
    {
        return ['engagement_id' => $this->engagement->id, 'title' => $this->title, 'amount_minor' => $this->amountMinor, 'receipt' => $this->receipt, 'url' => route('engagements.show', $this->engagement)];
    }

    public static function present(array $data): array
    {
        return [
            'title' => 'Escrow funded',
            'icon' => 'success',
            'content' => 'Your payment of '.Money::formatMinor((int) ($data['amount_minor'] ?? 0)).' for "'.($data['title'] ?? 'your job').'" is held in escrow.',
            'action_url' => $data['url'] ?? null,
            'action_label' => 'Open the workspace',
        ];
    }
}
