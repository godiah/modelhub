<?php

namespace App\Policies;

use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class JobApplicationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the job application.
     */
    public function view(User $user, JobApplication $application)
    {
        return $user->id === $application->applicant_id || $user->id === $application->poster_id;
    }

    /**
     * Determine whether the user can update the job application.
     */
    public function update(User $user, JobApplication $application)
    {
        return $user->id === $application->applicant_id;
    }

    /**
     * Determine whether the user can delete the job application.
     */
    public function delete(User $user, JobApplication $application)
    {
        return $user->id === $application->applicant_id && $application->is_archived;
    }
}
