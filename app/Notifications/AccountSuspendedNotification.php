<?php

namespace App\Notifications;

use App\Support\Mail\BrandedMail;
use Illuminate\Notifications\Notification;

/** Email to a member whose account staff suspended. Mail only: they can no longer sign in to see anything in the app. */
class AccountSuspendedNotification extends Notification
{
    public function __construct(public string $reason) {}

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return BrandedMail::message('Your account has been suspended')
            ->subject('Your '.config('app.name').' account has been suspended')
            ->greeting("Hello {$notifiable->name},")
            ->line('Your account has been suspended by our staff, so you cannot sign in and your projects and models are hidden for now.')
            ->line('Reason: '.$this->reason)
            ->line('If you think this is a mistake, reply to this email or write to '.(config('mail.support_address') ?: config('mail.from.address')).' and we will look again.');
    }
}
