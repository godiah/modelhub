<?php

/**
 * EngagementAuthorizationHelper
 *
 * Centralizes authorization logic for engagement operations.
 * Handles permission checks for viewing, responding, cancelling, and managing engagements.
 */

namespace App\Helpers\Engagements;

use App\Enums\EngagementStatus;
use App\Models\JobEngagement;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class EngagementAuthorizationHelper
{
    // Check if user can view engagement
    public static function canView(JobEngagement $engagement, User $user): bool
    {
        return Gate::forUser($user)->allows('view', $engagement);
    }

    // Check if user can respond to engagement offer
    public static function canRespondToOffer(JobEngagement $engagement, User $user): bool
    {
        return Gate::forUser($user)->allows('respondToOffer', $engagement);
    }

    // Check if user can leave a review
    public static function canLeaveReview(JobEngagement $engagement, User $user): bool
    {
        $application = $engagement->application;

        return ($user->id === $application->poster_id ||
                $user->id === $application->applicant_id) &&
               in_array($engagement->status, [EngagementStatus::Completed, EngagementStatus::Cancelled, EngagementStatus::Settled]);
    }

    // Check if user can cancel engagement
    public static function canCancelEngagement(JobEngagement $engagement, User $user): bool
    {
        $application = $engagement->application;

        return ($user->id === $application->poster_id ||
                $user->id === $application->applicant_id) &&
               $engagement->canBeCancelled();
    }

    // Check if user can process payment
    public static function canProcessPayment(JobEngagement $engagement, User $user): bool
    {
        return $user->id === $engagement->application->poster_id ||
               $user->hasRole('admin');
    }

    // Check if user can reopen job
    public static function canReopenJob(JobEngagement $engagement, User $user): bool
    {
        return $user->id === $engagement->application->poster_id &&
               in_array($engagement->status, [EngagementStatus::Cancelled, EngagementStatus::Settled]);
    }

    // Check if user can archive engagement
    public static function canArchiveEngagement(JobEngagement $engagement, User $user): bool
    {
        $application = $engagement->application;

        return $user->id === $application->poster_id ||
               $user->id === $application->applicant_id;
    }

    // Check if user has already reviewed engagement
    public static function hasUserReviewed(JobEngagement $engagement, User $user): bool
    {
        return $engagement->hasBeenReviewedByUser($user->id);
    }

    // Check if user can manage engagement deliverables (add/approve/reject/remove) — poster only
    public static function canManageDeliverables(JobEngagement $engagement, User $user): bool
    {
        return $user->id === $engagement->poster->id;
    }

    // Check if user can edit a deliverable (poster or applicant)
    public static function canEditDeliverable(JobEngagement $engagement, User $user): bool
    {
        return $user->id === $engagement->poster->id || $user->id === $engagement->applicant->id;
    }

    // Check if user can submit a deliverable — applicant only
    public static function canSubmitDeliverable(JobEngagement $engagement, User $user): bool
    {
        return $user->id === $engagement->applicant->id;
    }

    // Check if user can accept or dispute a partial payment — freelancer/applicant only
    public static function canRespondToPartialPayment(JobEngagement $engagement, User $user): bool
    {
        return $user->id === $engagement->application->applicant_id;
    }
}
