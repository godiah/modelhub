<?php

namespace App\Notifications;

use App\Models\ApplicantMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class NewApplicationMessage extends Notification implements ShouldQueue
{
    use Queueable;

    public $message;

    public function __construct(ApplicantMessage $message)
    {
        $this->message = $message;
    }

    public function via($notifiable)
    {
        return ['mail', 'database', 'broadcast'];
    }

    public function toMail($notifiable)
    {
        $application = $this->message->application;
        $job = $application->job;

        return (new MailMessage)
            ->subject($this->message->subject)
            ->view('emails.application-message', [
                'messageBody' => $this->message->message,
                'jobTitle' => $job->title,
                'employerName' => $job->user->name,
                'applicationId' => $application->id,
            ]);
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
            'message_preview' => Str::limit($this->message->message, 100),
            'url' => route('applications.show', $job->slug),
        ];
    }

    public static function present(array $data): array
    {
        return [
            'title' => $data['subject'] ?? 'New message',
            'icon' => 'message',
            'content' => ($data['message_preview'] ?? 'You received a new message')
                .' regarding job: '.($data['job_title'] ?? 'a job posting'),
            'action_url' => $data['url'] ?? null,
            'action_label' => 'View application',
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
            'message_preview' => Str::limit($this->message->message, 100),
        ]);
    }
}
