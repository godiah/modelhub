<?php

namespace App\Notifications;

use App\Support\Mail\BrandedMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/** Tells a member or staff member that their two-step sign-in setup was reset by staff, so they can spot one they did not ask for. */
class TwoFactorResetNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $portalUrl) {}

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return BrandedMail::message('Your two-step sign-in was reset')
            ->subject('Your two-step sign-in was reset')
            ->greeting("Hello {$notifiable->name},")
            ->line('Our staff removed the authenticator app and recovery codes from your account, usually because you asked for help getting back in.')
            ->line('You can set up a new authenticator app from your account settings.')
            ->line('If you did not ask for this, change your password now and write to '.(config('mail.support_address') ?: config('mail.from.address')).'.')
            ->action('Open your account', $this->portalUrl);
    }
}
