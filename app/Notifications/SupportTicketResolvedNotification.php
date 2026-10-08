<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use App\Support\Mail\BrandedMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/** Tells the member staff marked their request resolved, and that replying reopens it for a while. */
class SupportTicketResolvedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public SupportTicket $ticket) {}

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        return BrandedMail::message('Your request is resolved')
            ->subject("Your request {$this->ticket->reference} is resolved")
            ->greeting("Hi {$notifiable->name},")
            ->line("ModelHub staff marked your request {$this->ticket->reference} as resolved.")
            ->line('If something is still wrong, reply on your request page within '.(int) config('support.tickets.reopen_days').' days and we will pick it up again.')
            ->action('View your request', route('support.requests.show', $this->ticket));
    }

    public function toDatabase($notifiable)
    {
        return ['reference' => $this->ticket->reference, 'url' => route('support.requests.show', $this->ticket)];
    }

    public static function present(array $data): array
    {
        return [
            'title' => ($data['reference'] ?? 'Your request').' is resolved',
            'icon' => 'info',
            'content' => 'If something is still wrong, reply and staff will pick it up again.',
            'action_url' => $data['url'] ?? null,
            'action_label' => 'View',
        ];
    }
}
