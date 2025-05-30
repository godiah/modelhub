<?php

namespace App\Policies;

use App\Models\JobApplication;
use App\Models\JobEngagement;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class JobEngagementPolicy
{
    use HandlesAuthorization;


    /**
     * Determine if the user can view the response form.
     */
    public function viewResponseForm(User $user, JobApplication $application)
    {
        return $user->id === $application->applicant_id;
    }

    /**
     * Determine if the user can respond to the engagement offer.
     */
    public function respondToOffer(User $user, JobEngagement $engagement)
    {
        return $user->id === $engagement->application->applicant_id;
    }

    public function view(User $user, JobEngagement $engagement)
    {
        // User can view if they are the poster or applicant
        return $user->id === $engagement->application->poster_id ||
            $user->id === $engagement->application->applicant_id ||
            $user->hasRole('admin');
    }

    /**
     * Determine whether the user can send messages in the engagement.
     */
    public function message(User $user, JobEngagement $engagement)
    {
        // User can message if they are the client or freelancer AND the engagement is active
        return ($user->id === $engagement->application->poster_id || $user->id === $engagement->application->applicant_id)
            && in_array($engagement->status, ['active', 'cancelled']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, JobEngagement $jobEngagement): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, JobEngagement $jobEngagement): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, JobEngagement $jobEngagement): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, JobEngagement $jobEngagement): bool
    {
        return false;
    }
}
