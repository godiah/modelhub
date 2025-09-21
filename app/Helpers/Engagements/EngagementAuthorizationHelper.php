<?php

/**
 * EngagementAuthorizationHelper
 * 
 * Centralizes authorization logic for engagement operations.
 * Handles permission checks for viewing, responding, cancelling, and managing engagements.
*/

namespace App\Helpers\Engagements;

use App\Models\JobEngagement;
use App\Models\User;

class EngagementAuthorizationHelper
{
    // Check if user can view engagement
    public static function canView(JobEngagement $engagement, User $user): bool
    {
        $application = $engagement->application;
        
        return $user->id === $application->poster_id || 
               $user->id === $application->applicant_id ||
               $user->hasRole('admin');
    }

    // Check if user can respond to engagement offer
    public static function canRespondToOffer(JobEngagement $engagement, User $user): bool
    {
        return $user->id === $engagement->application->applicant_id;
    }

    // Check if user can leave a review
    public static function canLeaveReview(JobEngagement $engagement, User $user): bool
    {
        $application = $engagement->application;
        
        return ($user->id === $application->poster_id || 
                $user->id === $application->applicant_id) &&
               in_array($engagement->status, ['completed', 'cancelled', 'settled']);
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
               in_array($engagement->status, ['cancelled', 'settled']);
    }

    // Check if user can archive engagement
    public static function canArchiveEngagement(JobEngagement $engagement, User $user): bool
    {
        $application = $engagement->application;
        
        return $user->id === $application->poster_id || 
               $user->id === $application->applicant_id;
    }

    // Get user role in engagement (poster, applicant, or admin)
    public static function getUserRole(JobEngagement $engagement, User $user): string
    {
        $application = $engagement->application;
        
        if ($user->id === $application->poster_id) {
            return 'poster';
        } elseif ($user->id === $application->applicant_id) {
            return 'applicant';
        } elseif ($user->hasRole('admin')) {
            return 'admin';
        }
        
        return 'unauthorized';
    }

    // Check if user has already reviewed engagement
    public static function hasUserReviewed(JobEngagement $engagement, User $user): bool
    {
        return $engagement->hasBeenReviewedByUser($user->id);
    }
}
