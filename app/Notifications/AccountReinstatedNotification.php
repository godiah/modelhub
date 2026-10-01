<?php

namespace App\Notifications;

use App\Support\Mail\BrandedMail;
use Illuminate\Notifications\Notification;

/** Email to a member whose suspension staff lifted. */
class AccountReinstatedNotification extends Notification
{
    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return BrandedMail::message('Your account is active again')
            ->subject('Your '.config('app.name').' account is active again')
            ->greeting("Hello {$notifiable->name},")
            ->line('The suspension on your account has been lifted. You can sign in again, and your projects and models are visible again.')
            ->action('Sign in', route('login'));
    }
}
