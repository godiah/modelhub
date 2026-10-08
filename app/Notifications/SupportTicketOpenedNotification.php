<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use App\Support\Mail\BrandedMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/** Tells the staff who answer support tickets that one is waiting. Carries no member-written text: the mail says who and what kind, not what they wrote. */
class SupportTicketOpenedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public SupportTicket $ticket) {}

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        return BrandedMail::message('A support request is waiting')
            ->subject("Support request {$this->ticket->reference} ({$this->ticket->severity->value})")
            ->greeting("Hello {$notifiable->name},")
            ->line('A member handed a request to staff: '.$this->ticket->category->label().'. Urgency: '.$this->ticket->severity->label().'.')
            ->action('Open the support queue', route('admin.support.tickets.show', $this->ticket));
    }

    public function toDatabase($notifiable)
    {
        return ['reference' => $this->ticket->reference, 'category' => $this->ticket->category->value, 'severity' => $this->ticket->severity->value, 'url' => route('admin.support.tickets.show', $this->ticket)];
    }

    public static function present(array $data): array
    {
        return [
            'title' => 'Support request '.($data['reference'] ?? ''),
            'icon' => 'info',
            'content' => 'A member handed a request to staff ('.str_replace('_', ' ', (string) ($data['category'] ?? 'other')).', '.($data['severity'] ?? 'normal').').',
            'action_url' => $data['url'] ?? null,
            'action_label' => 'Open',
        ];
    }
}
