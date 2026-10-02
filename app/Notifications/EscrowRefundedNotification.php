<?php

namespace App\Notifications;

use App\Models\JobEngagement;
use App\Support\Mail\BrandedMail;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/** Tells a client that what was left in a job's escrow is being returned to them. */
class EscrowRefundedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public JobEngagement $engagement, public string $title, public int $amountMinor, public ?string $phone = null) {}

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        $message = BrandedMail::message('Escrow returned')
            ->subject(Money::formatMinor($this->amountMinor)." returned from \"{$this->title}\"")
            ->greeting("Hello {$notifiable->name},")
            ->line(Money::formatMinor($this->amountMinor)." that was left in escrow for \"{$this->title}\" is being returned to you.");

        if ($this->phone) {
            $message->line("We send it by M-Pesa to {$this->phone}, the number you paid from. It can take a little while to arrive.");
        }

        return $message->action('Open the job', route('engagements.show', $this->engagement));
    }

    public function toDatabase($notifiable)
    {
        return ['engagement_id' => $this->engagement->id, 'title' => $this->title, 'amount_minor' => $this->amountMinor, 'url' => route('engagements.show', $this->engagement)];
    }

    public static function present(array $data): array
    {
        return [
            'title' => 'Escrow returned',
            'icon' => 'success',
            'content' => Money::formatMinor((int) ($data['amount_minor'] ?? 0)).' left in escrow for "'.($data['title'] ?? 'your job').'" is being returned to you.',
            'action_url' => $data['url'] ?? null,
            'action_label' => 'Open the job',
        ];
    }
}
