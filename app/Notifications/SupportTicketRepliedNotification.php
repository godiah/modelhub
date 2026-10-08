<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use App\Support\Mail\BrandedMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Tells the member staff replied. The reply itself is NOT in the email: email is the least private place we write to, and the reply may mention
 * their money. They open their request page (signed in) to read it.
 */
class SupportTicketRepliedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public SupportTicket $ticket) {}

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        return BrandedMail::message('There is a reply on your request')
            ->subject("A reply on your request {$this->ticket->reference}")
            ->greeting("Hi {$notifiable->name},")
            ->line("Someone from ModelHub replied to your request {$this->ticket->reference}. Sign in to read it and answer.")
            ->action('Read the reply', route('support.requests.show', $this->ticket))
            ->line('Replies sent to this email address are not read. Please answer on your request page.');
    }

    public function toDatabase($notifiable)
    {
        return ['reference' => $this->ticket->reference, 'url' => route('support.requests.show', $this->ticket)];
    }

    public static function present(array $data): array
    {
        return [
            'title' => 'A reply on '.($data['reference'] ?? 'your request'),
            'icon' => 'info',
            'content' => 'ModelHub staff replied to your request.',
            'action_url' => $data['url'] ?? null,
            'action_label' => 'Read',
        ];
    }
}
