<?php

namespace App\Notifications;

use App\Models\JobEngagement;
use App\Models\JobPartialPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PartialPaymentProcessedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $engagement;
    protected $partialPayment;

    public function __construct(JobEngagement $engagement, JobPartialPayment $partialPayment)
    {
        $this->engagement = $engagement;
        $this->partialPayment = $partialPayment;
    }

    public function via($notifiable)
    {
        return ['mail', 'database', 'broadcast'];
    }

    public function toMail($notifiable)
    {
        $job = $this->engagement->job;
        $client = $this->engagement->poster;

        return (new MailMessage)
            ->subject('Partial Payment Processed for Job: ' . $job->title)
            ->view('emails.engagements.partial_payment_processed', [
                'job' => $job,
                'client' => $client,
                'engagement' => $this->engagement,
                'payment' => $this->partialPayment,
                'notifiable' => $notifiable,
            ]);
    }

    public function toArray($notifiable)
    {
        $job = $this->engagement->job;
        $client = $this->engagement->poster;

        return [
            'engagement_id' => $this->engagement->id,
            'payment_id' => $this->partialPayment->id,
            'job_id' => $job->id,
            'job_title' => $job->title,
            'client_id' => $client->id,
            'client_name' => $client->name,
            'amount' => $this->partialPayment->amount,
            'notes' => $this->partialPayment->notes,
            'processed_at' => $this->partialPayment->processed_at,
            'type' => 'payment',
            'url' => '/engagements/' . $this->engagement->id,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
