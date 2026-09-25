<?php

namespace App\Notifications;

use App\Models\JobEngagement;
use App\Models\JobPartialPayment;
use App\Models\JobPaymentDispute;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentDisputedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $engagement;

    protected $payment;

    protected $dispute;

    public function __construct(JobEngagement $engagement, JobPartialPayment $payment, JobPaymentDispute $dispute)
    {
        $this->engagement = $engagement;
        $this->payment = $payment;
        $this->dispute = $dispute;
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
            ->subject("Partial Payment Disputed for \"{$job->title}\"")
            ->view('emails.engagements.payment_disputed', [
                'notifiable' => $notifiable,
                'engagement' => $this->engagement,
                'job' => $job,
                'freelancer' => $freelancer,
                'payment' => $this->payment,
                'dispute' => $this->dispute,
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
            'dispute_id' => $this->dispute->id,
            'job_id' => $job->id,
            'job_title' => $job->title,
            'freelancer_id' => $freelancer->id,
            'freelancer_name' => $freelancer->name,
            'amount' => $this->payment->amount,
            'reason' => $this->dispute->formatted_reason,
            'type' => 'payment_disputed',
            'url' => route('engagements.archived-details', $this->engagement),
        ];
    }

    public static function present(array $data): array
    {
        return [
            'title' => 'Partial Payment Disputed',
            'icon' => 'danger',
            'content' => ($data['freelancer_name'] ?? 'The freelancer').' disputed the partial payment of '
                .config('app.currency_symbol').number_format($data['amount'] ?? 0, 2)
                .' for job: '.($data['job_title'] ?? 'a job posting').' — reason: '.($data['reason'] ?? 'not specified'),
            'action_url' => $data['url'] ?? null,
        ];
    }
}
