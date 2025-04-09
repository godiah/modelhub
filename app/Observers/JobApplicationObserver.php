<?php

namespace App\Observers;

use App\Models\JobApplication;

class JobApplicationObserver
{
    public function created(JobApplication $application)
    {
        if ($application->status === 'submitted') {
            $application->job()->increment('applicants_count');
        }
    }

    public function updated(JobApplication $application)
    {
        if ($application->isDirty('status')) {
            $original = $application->getOriginal('status');
            $new = $application->status;

            if ($original !== 'submitted' && $new === 'submitted') {
                $application->job()->increment('applicants_count');
            } elseif ($original === 'submitted' && $new !== 'submitted') {
                $application->job()->decrement('applicants_count');
            }
        }
    }

    public function deleted(JobApplication $application)
    {
        if ($application->status === 'submitted') {
            $application->job()->decrement('applicants_count');
        }
    }
}
