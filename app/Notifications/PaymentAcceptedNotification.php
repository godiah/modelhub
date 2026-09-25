<?php

namespace App\Notifications;

use App\Models\JobEngagement;
use App\Models\JobPartialPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentAcceptedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $engagement;

    protected $payment;

    public function __construct(JobEngagement $engagement, JobPartialPayment $payment)
    {
        $this->engagement = $engagement;
        $this->payment = $payment;
    }

    public function via($notifiable)
    {
        return ['mail', 'database', 'broadcast'];
    }

    public function toMail($notifiable)
    {
        $job = $this->engagement->job;
        $freelancer = $this->engagement->applicant;

        return (new MailMessage)
            ->subject("Partial Payment Accepted for \"{$job->title}\"")
            ->view('emails.engagements.payment_accepted', [
                'notifiable' => $notifiable,
                'engagement' => $this->engagement,
                'job' => $job,
                'freelancer' => $freelancer,
                'payment' => $this->payment,
                'actionUrl' => route('engagements.archived-details', $this->engagement),
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
        $job = $this->engagement->job;
        $freelancer = $this->engagement->applicant;

        return [
            'engagement_id' => $this->engagement->id,
            'payment_id' => $this->payment->id,
            'job_id' => $job->id,
            'job_title' => $job->title,
            'freelancer_id' => $freelancer->id,
            'freelancer_name' => $freelancer->name,
            'amount' => $this->payment->amount,
            'accepted_at' => $this->payment->accepted_at,
            'type' => 'payment_accepted',
            'url' => route('engagements.archived-details', $this->engagement),
        ];
    }

    public static function present(array $data): array
    {
        return [
            'title' => 'Partial Payment Accepted',
            'icon' => 'success',
            'content' => ($data['freelancer_name'] ?? 'The freelancer').' accepted the partial payment of '
                .config('app.currency_symbol').number_format($data['amount'] ?? 0, 2)
                .' for job: '.($data['job_title'] ?? 'a job posting').'.',
            'action_url' => $data['url'] ?? null,
        ];
    }
}
