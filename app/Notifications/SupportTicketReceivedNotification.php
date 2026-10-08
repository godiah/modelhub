<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use App\Services\Support\Tickets\TicketTargets;
use App\Support\Mail\BrandedMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/** Sent to the member the moment a request reaches staff: the reference, the aim for a first reply (provisional), and how replies arrive. */
class SupportTicketReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public SupportTicket $ticket) {}

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        return BrandedMail::message('We have your request')
            ->subject("We have your request {$this->ticket->reference}")
            ->greeting("Hi {$notifiable->name},")
            ->line('Your request about '.mb_strtolower($this->ticket->category->label()).' reached our team. Its reference is '.$this->ticket->reference.'.')
            ->line(TicketTargets::sentence($this->ticket->severity))
            ->line('Replies arrive by email and on your request page.')
            ->action('View your request', route('support.requests.show', $this->ticket))
            ->line('Please never send your M-Pesa PIN, a password or a code from an SMS. We will never ask for them.');
    }

    public function toDatabase($notifiable)
    {
        return ['reference' => $this->ticket->reference, 'url' => route('support.requests.show', $this->ticket)];
    }

    public static function present(array $data): array
    {
        return [
            'title' => 'We have your request '.($data['reference'] ?? ''),
            'icon' => 'info',
            'content' => 'A person will read it and reply.',
            'action_url' => $data['url'] ?? null,
            'action_label' => 'View',
        ];
    }
}
