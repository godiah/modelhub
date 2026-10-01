<?php

namespace App\Notifications;

use App\Support\Mail\BrandedMail;
use Illuminate\Notifications\Notification;

/** The "set your password" email for staff: sent as an invitation to a new account, or after a forgotten password. */
class StaffPasswordNotification extends Notification
{
    public function __construct(public string $token, public bool $invited = false) {}

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $url = route('admin.password.reset', ['token' => $this->token, 'email' => $notifiable->email]);
        $hours = (int) (config('auth.passwords.staff.expire') / 60);

        return $this->invited
            ? BrandedMail::message('You have been invited to the staff portal')
                ->subject('Your '.config('app.name').' staff account')
                ->greeting("Hello {$notifiable->name},")
                ->line('A staff account has been created for you on the '.config('app.name').' staff portal.')
                ->action('Set your password', $url)
                ->line("This link works for {$hours} hours. After that, use \"Forgot your password?\" on the staff sign-in page.")
            : BrandedMail::message('Reset your staff password')
                ->subject('Reset your '.config('app.name').' staff password')
                ->greeting("Hello {$notifiable->name},")
                ->line('We received a request to reset the password for your staff account.')
                ->action('Set a new password', $url)
                ->line("This link works for {$hours} hours. If you did not ask for this, you can ignore it and your password stays the same.");
    }
}
