<?php

namespace App\Notifications;

use App\Models\JobReview;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $review;

    public function __construct(JobReview $review)
    {
        $this->review = $review;
    }

    public function via($notifiable)
    {
        return ['mail', 'database', 'broadcast'];
    }

    public function toMail($notifiable)
    {
        $engagement = $this->review->engagement;
        $job = $engagement->job;
        $reviewer = $this->review->reviewer;

        return (new MailMessage)
            ->subject("You've received a new review for \"{$job->title}\"")
            ->view('emails.engagements.review_submitted', [
                'notifiable' => $notifiable,
                'engagement' => $engagement,
                'job' => $job,
                'reviewer' => $reviewer,
                'review' => $this->review,
                'actionUrl' => route('engagements.archived-details', $engagement),
            ]);
    }

    public function toDatabase($notifiable)
    {
        return $this->toArray($notifiable);
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    protected function toArray($notifiable)
    {
        $engagement = $this->review->engagement;
        $job = $engagement->job;
        $reviewer = $this->review->reviewer;

        return [
            'engagement_id' => $engagement->id,
            'review_id' => $this->review->id,
            'job_id' => $job->id,
            'job_title' => $job->title,
            'reviewer_id' => $reviewer->id,
            'reviewer_name' => $reviewer->name,
            'rating' => $this->review->rating,
            'type' => 'review',
            'url' => route('engagements.archived-details', $engagement),
        ];
    }
}
