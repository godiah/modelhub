<?php

namespace App\Support\Mail;

use Illuminate\Notifications\Messages\MailMessage;

/**
 * Start every line-based notification email here. It is a normal MailMessage (subject, greeting, line,
 * action, …) that renders through the app's one email template instead of Laravel's own markdown theme, so
 * it looks like every other ModelHub email. Emails with richer bodies use ->view('emails.…') directly, and
 * those extend the same emails.layouts.master.
 */
class BrandedMail
{
    public static function message(?string $heading = null): MailMessage
    {
        return (new MailMessage)->view('emails.notification', ['heading' => $heading]);
    }
}
