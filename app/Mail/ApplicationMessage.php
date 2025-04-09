<?php

namespace App\Mail;

use App\Models\ApplicantMessage;
use App\Models\JobApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApplicationMessage extends Mailable
{
    use Queueable, SerializesModels;

    public $applicantMessage;
    public $application;

    public function __construct(ApplicantMessage $applicantMessage, JobApplication $application)
    {
        $this->applicantMessage = $applicantMessage;
        $this->application = $application;
    }

    public function build()
    {
        return $this->subject($this->applicantMessage->subject)
            ->markdown('emails.application-message', [
                'message' => $this->applicantMessage->message,
                'jobTitle' => $this->application->job->title,
                'employerName' => $this->application->job->user->name,
                'applicationId' => $this->application->id,
            ]);
    }
}
