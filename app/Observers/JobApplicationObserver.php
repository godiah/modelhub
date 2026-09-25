<?php

namespace App\Observers;

use App\Enums\ApplicationStatus;
use App\Models\JobApplication;

class JobApplicationObserver
{
    public function created(JobApplication $application)
    {
        if ($application->status === ApplicationStatus::Submitted) {
            $application->job()->increment('applicants_count');
        }
    }

    public function updated(JobApplication $application)
    {
        if ($application->isDirty('status')) {
            $original = $application->getOriginal('status');
            $new = $application->status;

            if ($original !== ApplicationStatus::Submitted && $new === ApplicationStatus::Submitted) {
                $application->job()->increment('applicants_count');
            } elseif ($original === ApplicationStatus::Submitted && $new !== ApplicationStatus::Submitted) {
                $application->job()->decrement('applicants_count');
            }
        }
    }

    public function deleted(JobApplication $application)
    {
        if ($application->status === ApplicationStatus::Submitted) {
            $application->job()->decrement('applicants_count');
        }
    }
}
