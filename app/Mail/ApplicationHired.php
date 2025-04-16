<?php

namespace App\Mail;

use App\Models\JobApplication;
use App\Models\JobEngagement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApplicationHired extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $application;
    public $engagement;

    public function __construct(JobApplication $application, JobEngagement $engagement)
    {
        $this->application = $application;
        $this->engagement = $engagement;
    }

    public function build()
    {
        return $this->subject("You've been hired for {$this->application->job->title}")
            ->markdown('emails.application.hired');
    }
}
