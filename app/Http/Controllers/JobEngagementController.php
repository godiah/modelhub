<?php

namespace App\Http\Controllers;

use App\Events\EngagementResponseEvent;
use App\Models\JobApplication;
use App\Models\JobEngagement;
use App\Models\JobReview;
use App\Models\User;
use App\Notifications\EngagementResponseNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class JobEngagementController extends Controller
{
    /**
     * Display a listing of the user's job engagements.
     */
    public function index()
    {
        $user = Auth::user();

        // Get engagements where the user is either the applicant or the poster
        $engagements = JobEngagement::with(['application.job', 'application.poster', 'application.applicant', 'deliverables'])
            ->whereHas('application', function ($query) use ($user) {
                $query->where('applicant_id', $user->id)
                    ->orWhere('poster_id', $user->id);
            })
            ->latest()
            ->get();

        return view('jobBoard.engagements.index', compact('engagements'));
    }

    /**
     * Show response form for the job engagement.
     */
    public function showResponseForm($applicationId)
    {
        // Get the authenticated user
        $user = Auth::user();

        // Find the application with related data
        $application = JobApplication::with(['job', 'applicant', 'poster', 'engagement.deliverables'])
            ->where('id', $applicationId)
            ->where('applicant_id', $user->id)
            ->firstOrFail();

        // Get the engagement
        $engagement = $application->engagement;

        // Make sure there's an engagement to respond to
        if (!$engagement) {
            return redirect()->route('engagements.index')->with('error', 'No engagement offer found for this application.');
        }

        return view('jobBoard.engagements.respond', compact('application', 'engagement'));
    }

    /**
     * Response to the job engagement offer form.
     */
    public function respondToOffer(Request $request, $engagementId)
    {
        $engagement = JobEngagement::with(['application.job', 'application.poster'])->findOrFail($engagementId);

        // Ensure the authenticated user is the applicant
        if (Auth::id() !== $engagement->application->applicant_id) {
            return redirect()->route('engagements.index')->with('error', 'You are not authorized to respond to this engagement offer.');
        }

        $request->validate([
            'response' => 'required|in:accepted,declined',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($request->response === 'accepted') {
            $engagement->update([
                'status' => 'active',
                'started_at' => now(),
                'notes' => $request->notes
            ]);

            $message = 'Offer accepted successfully! Your engagement has started.';
            $alertType = 'success';

            // Update application status
            $engagement->application->update(['status' => 'hired']);

            // Close the job so others can't apply
            $job = $engagement->application->job;
            $job->update([
                'is_active' => false,
            ]);
        } else {
            $engagement->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'notes' => $request->notes
            ]);

            // Update application status
            $engagement->application->update(['status' => 'withdrawn']);

            $message = 'Offer declined. The job poster has been notified.';
            $alertType = 'info';

            // Delete deliverables if declined
            $engagement->deliverables()->delete();
        }

        // Send in-app notification to job poster
        $jobPoster = $engagement->application->poster;
        $jobPoster->notify(new \App\Notifications\EngagementResponseNotification(
            $engagement,
            $request->response,
            $request->notes
        ));

        // Send email notification if needed
        // Mail::to($jobPoster->email)
        //    ->queue(new EngagementResponseMail($engagement, $request->response, $request->notes));

        return redirect()->route('engagements.index')->with([
            'success' => $message,
            'alert' => [
                'type' => $alertType,
                'title' => $message,
                'text' => $request->response === 'accepted'
                    ? 'You can now start working on the project deliverables.'
                    : 'Thank you for your response.',
            ]
        ]);
    }

    /**
     * Leave a review for a completed job
     */
    public function leaveReview(JobEngagement $engagement, Request $request)
    {
        // Check if the job is completed
        if ($engagement->status !== 'completed') {
            return redirect()->back()->with([
                'error' => 'You can only review completed jobs',
                'alert' => [
                    'type' => 'error',
                    'title' => 'Review Not Allowed',
                    'text' => 'You can only leave reviews for completed jobs.',
                ]
            ]);
        }

        $application = $engagement->application;
        $authUser = Auth::user();

        // Determine reviewer and reviewee roles
        if ($authUser->id === $application->poster_id) {
            $reviewer_id = $application->poster_id;
            $reviewee_id = $application->applicant_id;
            $reviewerType = 'employer';
        } elseif ($authUser->id === $application->applicant_id) {
            $reviewer_id = $application->applicant_id;
            $reviewee_id = $application->poster_id;
            $reviewerType = 'freelancer';
        } else {
            return redirect()->back()->with([
                'error' => 'Unauthorized',
                'alert' => [
                    'type' => 'error',
                    'title' => 'Access Denied',
                    'text' => 'You are not authorized to leave a review for this job.',
                ]
            ]);
        }

        // Check if a review already exists
        $existingReview = JobReview::where([
            'engagement_id' => $engagement->id,
            'reviewer_id' => $reviewer_id,
        ])->first();

        if ($existingReview) {
            return redirect()->back()->with([
                'error' => 'You have already reviewed this job',
                'alert' => [
                    'type' => 'warning',
                    'title' => 'Review Already Submitted',
                    'text' => 'You have already submitted a review for this job.',
                ]
            ]);
        }

        try {
            // Validate request
            $validated = $request->validate([
                'rating' => 'required|integer|min:1|max:5',
                'review' => 'required|string|min:10',
                'tags' => 'nullable|array',
                'is_public' => 'nullable|boolean',
            ]);

            // Set is_public to true if not provided
            $isPublic = isset($validated['is_public']) ? (bool)$validated['is_public'] : true;

            // Create review
            $review = JobReview::create([
                'engagement_id' => $engagement->id,
                'reviewer_id' => $reviewer_id,
                'reviewee_id' => $reviewee_id,
                'rating' => $validated['rating'],
                'review' => $validated['review'],
                'tags' => $validated['tags'] ?? [],
                'is_public' => $isPublic,
            ]);

            // DEBUG: Log created review
            Log::info('Created review:', $review->toArray());

            // Get reviewee name for personalized message
            $reviewee = User::find($reviewee_id);
            $revieweeName = $reviewee ? $reviewee->name : 'the ' . ($reviewerType === 'employer' ? 'freelancer' : 'client');

            return redirect()->route('engagements.index', $engagement)->with([
                'hasReviewed' => $existingReview,
                'success' => 'Review submitted successfully',
                'alert' => [
                    'type' => 'success',
                    'title' => 'Review Submitted',
                    'text' => "Thank you for reviewing $revieweeName. Your feedback helps build trust in our community.",
                ]
            ]);
        } catch (\Exception $e) {
            // DEBUG: Log any exceptions
            Log::error('Error saving review: ' . $e->getMessage());
            Log::error($e->getTraceAsString());

            return redirect()->back()->with([
                'error' => 'Failed to submit review',
                'alert' => [
                    'type' => 'error',
                    'title' => 'Error',
                    'text' => 'There was a problem submitting your review. Please try again.',
                ]
            ]);
        }
    }

    /**
     * Cancel an engagement- work on this
     
    public function cancelEngagement(JobEngagement $engagement, Request $request)
    {
        // Authorization checks
        $application = $engagement->application;
        $authUser = Auth::user();

        if ($authUser->id !== $application->poster_id && $authUser->id !== $application->applicant_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Only allow cancellation before completion
        if ($engagement->status === 'completed') {
            return response()->json(['error' => 'Cannot cancel a completed engagement'], 400);
        }

        // Validate request
        $request->validate([
            'cancellation_reason' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            // Handle refunds if payment was escrowed
            if ($engagement->isPaymentEscrowed()) {
                // Implement your refund logic here
                // ...
            }

            // Update engagement
            $engagement->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'notes' => $request->cancellation_reason,
            ]);

            DB::commit();

            // Determine who to notify
            $userToNotify = ($authUser->id === $application->poster_id)
                ? $application->applicant
                : $application->poster;

            // Create and use a notification class for cancellations
            // $userToNotify->notify(new EngagementCancelled($engagement));

            return response()->json([
                'message' => 'Engagement cancelled successfully',
                'engagement' => $engagement->refresh()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to cancel engagement: ' . $e->getMessage()], 500);
        }
    }
     */
}
