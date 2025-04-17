<?php

namespace App\Mail;

use App\Models\JobDeliverable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DeliverableSubmitted extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $deliverable;

    /**
     * Create a new message instance.
     */
    public function __construct(JobDeliverable $deliverable)
    {
        $this->deliverable = $deliverable;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('New Deliverable Submitted: ' . $this->deliverable->title)
            ->markdown('emails.deliverables.submitted');
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
