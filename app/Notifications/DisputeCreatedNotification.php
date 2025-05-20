<?php

namespace App\Notifications;

use App\Models\JobCancellation;
use App\Models\JobEngagement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DisputeCreatedNotification extends Notification implements ShouldQueue
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
        $client = $this->engagement->poster;
        $freelancer = $this->engagement->applicant;

        return (new MailMessage)
            ->subject("Urgent: Dispute Reported for Job #{$job->id}")
            ->view('emails.disputes.created', [
                'notifiable' => $notifiable,
                'engagement' => $this->engagement,
                'cancellation' => $this->cancellation,
                'job' => $job,
                'initiator' => $initiator,
                'client' => $client,
                'freelancer' => $freelancer,
                'actionUrl' => url("/admin/disputes/{$this->cancellation->id}")
            ]);
    }


    public function toDatabase($notifiable)
    {
        $job = $this->engagement->job;
        $initiator = $this->cancellation->initiator;

        return [
            'engagement_id' => $this->engagement->id,
            'cancellation_id' => $this->cancellation->id,
            'job_id' => $job->id,
            'job_title' => $job->title,
            'initiator_id' => $initiator->id,
            'initiator_name' => $initiator->name,
            'reason_category' => $this->cancellation->reason_category,
            'reason_details' => $this->cancellation->reason_details,
            'initiated_at' => $this->cancellation->created_at,
            'type' => 'dispute',
            'url' => '/admin/disputes/' . $this->cancellation->id,
        ];
    }

    public function toBroadcast($notifiable)
    {
        $job = $this->engagement->job;
        $initiator = $this->cancellation->initiator;

        return new BroadcastMessage([
            'engagement_id' => $this->engagement->id,
            'cancellation_id' => $this->cancellation->id,
            'job_id' => $job->id,
            'job_title' => $job->title,
            'initiator_id' => $initiator->id,
            'initiator_name' => $initiator->name,
            'reason_category' => $this->cancellation->reason_category,
            'reason_details' => $this->cancellation->reason_details,
            'initiated_at' => $this->cancellation->created_at,
            'type' => 'dispute',
            'url' => '/admin/disputes/' . $this->cancellation->id,
        ]);
    }
}
