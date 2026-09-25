<?php

/**
 * EngagementReviewService
 *
 * Handles review creation and management for completed engagements.
 * Manages review submissions, validation, and reviewer/reviewee relationships.
 */

namespace App\Services\Engagements;

use App\Helpers\Engagements\EngagementAuthorizationHelper;
use App\Helpers\Engagements\EngagementNotificationHelper;
use App\Helpers\FlashAlertHelper;
use App\Models\JobEngagement;
use App\Models\JobReview;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class EngagementReviewService
{
    // Submit a review for an engagement
    public function submitReview(JobEngagement $engagement, array $reviewData): array
    {
        $user = Auth::user();

        // Check if engagement can be reviewed
        if (! $this->canLeaveReview($engagement, $user)) {
            return [
                'success' => false,
                'error' => 'You can only review completed jobs',
                'alert' => FlashAlertHelper::error('Review Not Allowed', 'You can only leave reviews for completed jobs.')['alert'],
            ];
        }

        // Determine reviewer and reviewee roles
        $roles = $this->determineReviewerRoles($engagement, $user);
        if (! $roles) {
            return [
                'success' => false,
                'error' => 'Unauthorized',
                'alert' => FlashAlertHelper::error(
                    'Access Denied',
                    'You are not authorized to leave a review for this job.'
                )['alert'],
            ];
        }

        // Check if review already exists
        if ($this->hasUserAlreadyReviewed($engagement, $user)) {
            return [
                'success' => false,
                'error' => 'You have already reviewed this job',
                'alert' => FlashAlertHelper::warning(
                    'Review Already Submitted',
                    'You have already submitted a review for this job.'
                )['alert'],
            ];
        }

        try {
            // Create the review
            $review = JobReview::create([
                'engagement_id' => $engagement->id,
                'reviewer_id' => $roles['reviewer_id'],
                'reviewee_id' => $roles['reviewee_id'],
                'rating' => $reviewData['rating'],
                'review' => $reviewData['review'],
                'tags' => $reviewData['tags'],
                'is_public' => $reviewData['is_public'],
            ]);

            // Get reviewee name for personalized message
            $reviewee = User::find($roles['reviewee_id']);
            $revieweeName = $reviewee ? $reviewee->name : 'the '.($roles['reviewer_type'] === 'employer' ? 'freelancer' : 'client');

            EngagementNotificationHelper::sendReviewNotification($engagement, $review);

            return [
                'success' => true,
                'review' => $review,
                'alert' => FlashAlertHelper::success(
                    'Review Submitted',
                    "Thank you for reviewing $revieweeName. Your feedback helps build trust in our community."
                )['alert'],
            ];
        } catch (\Exception $e) {
            // Log::error('Error saving review: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => 'Failed to submit review',
                'alert' => FlashAlertHelper::error(
                    'Error',
                    'There was a problem submitting your review. Please try again.'
                )['alert'],
            ];
        }
    }

    // Check if user can leave a review
    public function canLeaveReview(JobEngagement $engagement, User $user): bool
    {
        return EngagementAuthorizationHelper::canLeaveReview($engagement, $user);
    }

    // Check if user has already reviewed this engagement
    public function hasUserAlreadyReviewed(JobEngagement $engagement, User $user): bool
    {
        return EngagementAuthorizationHelper::hasUserReviewed($engagement, $user);
    }

    // Determine reviewer and reviewee roles
    protected function determineReviewerRoles(JobEngagement $engagement, User $user): ?array
    {
        $application = $engagement->application;

        if ($user->id === $application->poster_id) {
            return [
                'reviewer_id' => $application->poster_id,
                'reviewee_id' => $application->applicant_id,
                'reviewer_type' => 'employer',
            ];
        } elseif ($user->id === $application->applicant_id) {
            return [
                'reviewer_id' => $application->applicant_id,
                'reviewee_id' => $application->poster_id,
                'reviewer_type' => 'freelancer',
            ];
        }

        return null;
    }
}
