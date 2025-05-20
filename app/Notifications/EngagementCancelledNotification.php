<?php

namespace App\Notifications;

use App\Models\JobCancellation;
use App\Models\JobEngagement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EngagementCancelledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $engagement;
    protected $cancellation;

    public function __construct(JobEngagement $engagement, JobCancellation $cancellation)
    {
        $this->engagement = $engagement;
        $this->cancellation = $cancellation;
    }

    public function via($notifiable)
    {
        return ['mail', 'database', 'broadcast'];
    }

    public function toMail($notifiable)
    {
        $job = $this->engagement->job;
        $initiator = $this->cancellation->initiator;
        $poster = $this->engagement->poster;
        $applicant = $this->engagement->applicant;
        $initiatorType = $initiator->id === $poster->id ? 'Client' : 'Freelancer';

        return (new MailMessage)
            ->subject("Engagement Cancelled: {$job->title}")
            ->view('emails.engagements.cancelled', [
                'notifiable' => $notifiable,
                'engagement' => $this->engagement,
                'cancellation' => $this->cancellation,
                'job' => $job,
                'initiator' => $initiator,
                'poster' => $poster,
                'applicant' => $applicant,
                'initiatorType' => $initiatorType,
                'actionUrl' => route('engagements.show-cancelled', $this->engagement->id),
                'paymentUrl' => route('engagements.process-payment', $this->engagement->id),

            ]);
    }

    public function toArray($notifiable)
    {
        $job = $this->engagement->job;
        $initiator = $this->cancellation->initiator;
        $initiatorType = $initiator->id === $this->engagement->poster->id ? 'Client' : 'Freelancer';

        return [
            'engagement_id' => $this->engagement->id,
            'cancellation_id' => $this->cancellation->id,
            'job_id' => $job->id,
            'job_title' => $job->title,
            'initiator_id' => $initiator->id,
            'initiator_name' => $initiator->name,
            'initiator_type' => $initiatorType,
            'cancellation_type' => $this->cancellation->cancellation_type,
            'reason_category' => $this->cancellation->reason_category,
            'reason_details' => $this->cancellation->reason_details,
            'partial_payment_amount' => $this->cancellation->partial_payment_amount,
            'initiated_at' => $this->cancellation->created_at,
            'type' => 'cancellation',
            'url' => "/engagements/",
        ];
    }

    public function toBroadcast($notifiable)
    {
        return broadcast(new \Illuminate\Notifications\Messages\BroadcastMessage($this->toArray($notifiable)));
    }
}
