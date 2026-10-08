<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use App\Support\Mail\BrandedMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/** Tells whoever has a ticket (or, if nobody has, everyone who answers them) that the member wrote back. */
class SupportTicketMemberRepliedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public SupportTicket $ticket) {}

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        return BrandedMail::message('A member replied')
            ->subject("Reply on {$this->ticket->reference}")
            ->greeting("Hello {$notifiable->name},")
            ->line("The member wrote back on {$this->ticket->reference}.")
            ->action('Open the ticket', route('admin.support.tickets.show', $this->ticket));
    }

    public function toDatabase($notifiable)
    {
        return ['reference' => $this->ticket->reference, 'url' => route('admin.support.tickets.show', $this->ticket)];
    }

    public static function present(array $data): array
    {
        return [
            'title' => 'Reply on '.($data['reference'] ?? 'a ticket'),
            'icon' => 'info',
            'content' => 'The member wrote back.',
            'action_url' => $data['url'] ?? null,
            'action_label' => 'Open',
        ];
    }
}
