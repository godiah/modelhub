<?php

namespace App\Notifications;

use App\Models\ApplicantMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewApplicationMessage extends Notification
{
    use Queueable;

    public $message;

    public function __construct(ApplicantMessage $message)
    {
        $this->message = $message;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        $application = $this->message->application;
        $job = $application->job;

        return [
            'message_id' => $this->message->id,
            'application_id' => $application->id,
            'job_id' => $job->id,
            'job_slug' => $job->slug,
            'job_title' => $job->title,
            'sender_name' => $this->message->sender->name,
            'subject' => $this->message->subject,
            'message_preview' => \Illuminate\Support\Str::limit($this->message->message, 100),
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'message_id' => $this->message->id,
            'application_id' => $this->message->application->id,
            'job_id' => $this->message->application->job->id,
            'job_slug' => $this->message->application->job->slug,
            'job_title' => $this->message->application->job->title,
            'sender_name' => $this->message->sender->name,
            'subject' => $this->message->subject,
            'message_preview' => \Illuminate\Support\Str::limit($this->message->message, 100),
        ]);
    }
}
