<?php

namespace App\Http\Controllers;

use App\Events\EngagementResponseEvent;
use App\Models\JobApplication;
use App\Models\JobEngagement;
use App\Models\JobReview;
use App\Models\User;
use App\Notifications\DisputeCreatedNotification;
use App\Notifications\EngagementCancelledNotification;
use App\Notifications\EngagementResponseNotification;
use App\Services\PartialPaymentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class JobEngagementController extends Controller
{
    use AuthorizesRequests;

    protected $partialPaymentService;

    public function __construct(PartialPaymentService $partialPaymentService)
    {
        $this->partialPaymentService = $partialPaymentService;
    }

    /**
     * Display a listing of the user's job engagements.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // 1) build a base query for all engagements the user is involved in
        $query = JobEngagement::with([
            'application.job',
            'application.poster',
            'application.applicant',
            'deliverables',
            'cancellation'
        ])
            ->whereHas('application', function ($q) use ($user) {
                $q->where('applicant_id', $user->id)
                    ->orWhere('poster_id', $user->id);
            })
            ->activeForUser($user->id); // Pass the user ID here

        // 2) apply search scope
        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                // example: search by job title, poster name, applicant name...
                $q->whereHas('application.job', fn($q2) =>
                $q2->where('title', 'like', "%{$term}%"))
                    ->orWhereHas('application.applicant', fn($q2) =>
                    $q2->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('application.poster', fn($q2) =>
                    $q2->where('name', 'like', "%{$term}%"));
            });
        }

        // 3) apply status filter
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // 4) paginate & keep query string
        $engagements = $query->latest()
            ->paginate(10)
            ->withQueryString();

        // 5) flag if any filters are active
        $hasFilters = $request->filled('search')
            || ($request->filled('status') && $request->status !== 'all');

        // 6) Update this to use the new archiving system
        $hasArchivedEngagements = \App\Models\JobEngagement::archivedForUser($user->id)->exists();

        // 7) support AJAX refresh
        if ($request->ajax()) {
            return view('jobBoard.engagements.partials.engagements-list', [
                'engagements' => $engagements,
                'hasFilters'  => $hasFilters,
                'hasArchivedEngagements' => $hasArchivedEngagements
            ])->render();
        }

        return view('jobBoard.engagements.index', compact('engagements', 'hasFilters', 'hasArchivedEngagements'));
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
        if (! in_array($engagement->status, ['completed', 'cancelled', 'settled'])) {
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
        if ($engagement->hasBeenReviewedByUser($reviewer_id)) {
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

            // Get reviewee name for personalized message
            $reviewee = User::find($reviewee_id);
            $revieweeName = $reviewee ? $reviewee->name : 'the ' . ($reviewerType === 'employer' ? 'freelancer' : 'client');

            return redirect()->route('engagements.index', $engagement)->with([
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
     * Show the cancellation form
     */
    public function showCancellationForm(JobEngagement $engagement)
    {
        // Authorization check - only the client or freelancer can cancel
        $application = $engagement->application;
        $authUser = Auth::user();

        if ($authUser->id !== $application->poster_id && $authUser->id !== $application->applicant_id) {
            return back()->with('error', 'Unauthorized Action.');
        }

        return view('engagements.cancel', compact('engagement'));
    }

    /**
     * Process engagement cancellation
     */
    public function cancelEngagement(JobEngagement $engagement, Request $request)
    {
        // Authorization check - only the client or freelancer can cancel
        $application = $engagement->application;
        $authUser = Auth::user();

        if ($authUser->id !== $application->poster_id && $authUser->id !== $application->applicant_id) {
            return back()->with('error', 'You are not authorized to cancel this engagement.');
        }

        // Only allow cancellation before completion
        if ($engagement->status === 'completed') {
            return back()->with('error', 'Cannot cancel a completed engagement.');
        }

        // Validate request
        $validated = $request->validate([
            'cancellation_type' => 'required|string|in:mutual,client_initiated,freelancer_initiated,dispute',
            'reason_category' => 'required|string',
            'cancellation_reason' => 'required|string|min:10',
            'terms' => 'required|accepted',
        ]);

        DB::beginTransaction();
        try {
            // Determine the initiator and recipient
            $isClient = $authUser->id === $application->poster_id;
            $initiator = $isClient ? 'client' : 'freelancer';

            $cancellationExists = $engagement->cancellation()
                ->where('initiator_id', $authUser->id)
                ->exists();

            if ($cancellationExists) {
                return back()->with('error', 'You have already submitted a cancellation for this engagement.');
            }


            // Create cancellation record
            $cancellation = $engagement->cancellation()->create([
                'initiator_id' => $authUser->id,
                'cancellation_type' => $validated['cancellation_type'],
                'reason_category' => $validated['reason_category'],
                'reason_details' => $validated['cancellation_reason'],
                'is_dispute' => $validated['cancellation_type'] === 'dispute',
            ]);

            // Update engagement status
            $engagement->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'notes' => $request->cancellation_reason,
            ]);

            // If it's a dispute, notify administrators
            if ($validated['cancellation_type'] === 'dispute') {
                // Get all users with admin role
                $adminUsers = User::role('admin')->get();

                // Notify admins about dispute
                Notification::send($adminUsers, new DisputeCreatedNotification($engagement, $cancellation));
            }

            // Determine who to notify
            $userToNotify = ($authUser->id === $application->poster_id)
                ? $application->applicant
                : $application->poster;

            // Send in-app notification and mail
            $userToNotify->notify(new EngagementCancelledNotification($engagement, $cancellation));

            DB::commit();

            return redirect()->route('engagements.index', $engagement)->with([
                'success' => 'Engagement cancelled successfully. All parties have been notified.',
                'alert' => [
                    'type' => 'success',
                    'title' => 'Engagement cancelled successfully.',
                    'text' => "Engagement cancelled successfully.",
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to cancel engagement: ' . $e->getMessage());
        }
    }

    /**
     * Display a cancelled engagement
     */
    public function showCancelledEngagement($id)
    {
        $engagement = JobEngagement::findOrFail($id);
        $application = $engagement->application;
        $authUser = Auth::user();

        // Check if the user is the poster or applicant
        if ($authUser->id !== $application->poster_id && $authUser->id !== $application->applicant_id && !$authUser->hasRole('admin')) {
            return back()->with('error', 'Unauthorized Access.');
        }

        // Allow access only if the engagement is cancelled, settled, or disputed
        if (!in_array($engagement->status, ['cancelled', 'settled', 'disputed'])) {
            return back()->with('error', 'Unauthorized Action');
        }


        // Get payment information from the service
        $paymentInfo = $this->partialPaymentService->canProcessPayment($engagement);
        $latestPayment = $engagement->partialPayments()->latest()->first();

        return view('jobBoard.engagements.cancelled-engagements', [
            'engagement' => $engagement,
            'paymentInfo' => $paymentInfo,
            'canProcess' => $paymentInfo['can_process'],
            'payment' => $latestPayment,
        ]);
    }

    /**
     * Display a disputed engagement
     */
    public function showDisputedEngagement($id)
    {
        $engagement = JobEngagement::with([
            'application',
            'cancellation.dispute.partialPayment',
        ])->findOrFail($id);

        $application = $engagement->application;
        $authUser = Auth::user();

        // Check if the user is the poster or applicant
        if ($authUser->id !== $application->poster_id && $authUser->id !== $application->applicant_id && !$authUser->hasRole('admin')) {
            return back()->with('error', 'Unauthorized Access.');
        }

        // Allow access only if the engagement is settled, or disputed
        if (!in_array($engagement->status, ['settled', 'disputed'])) {
            return back()->with('error', 'Unauthorized Action');
        }

        return view('jobBoard.engagements.disputed-engagements', [
            'engagement' => $engagement,
            'dispute' => $engagement->cancellation->dispute ?? null,
            'partialPayment' => optional($engagement->cancellation->dispute)->partialPayment,
        ]);
    }

    /**
     * Reopen a job after cancellation
     */
    public function reopenJob(JobEngagement $engagement, Request $request)
    {
        // Authorization check - only the client can reopen the job
        $application = $engagement->application;

        if (Auth::id() !== $application->poster_id) {
            return back()->with('error', 'Only the job poster can reopen this job.');
        }

        // Make sure the engagement is either cancelled or settled
        if (!in_array($engagement->status, ['cancelled', 'settled'])) {
            return back()->with('error', 'Only cancelled or settled engagements can have their jobs reopened.');
        }


        try {
            $job = $engagement->job;

            $job->update([
                'is_active' => true,
            ]);

            return redirect()->route('engagements.index')->with([
                'success' => 'Job has been reopened successfully',
                'alert' => [
                    'type' => 'success',
                    'title' => 'Job has been reopened successfully',
                    'text' => "Job has been reopened successfully",
                ]
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to reopen job: ' . $e->getMessage());
        }
    }

    /**
     * Display a listing of archived engagements
     */
    public function archivedEngagements()
    {
        $user = Auth::user();

        $archivedEngagements = JobEngagement::archivedForUser($user->id)
            ->with(['application.job', 'application.applicant', 'application.poster'])
            ->latest()
            ->paginate(7);

        return view('jobBoard.engagements.archived', compact('archivedEngagements'));
    }

    /**
     * Archive an engagement
     */
    public function archive(Request $request)
    {
        $engagement = JobEngagement::findOrFail($request->engagement_id);

        // Check authorization - user must be either the applicant or poster
        $user = Auth::user();
        $applicantId = $engagement->application->applicant_id;
        $posterId = $engagement->application->poster_id;

        // Determine which field to update based on the user's role
        if ($user->id === $applicantId) {
            $engagement->is_archived_by_applicant = true;
        } elseif ($user->id === $posterId) {
            $engagement->is_archived_by_poster = true;
        } else {
            return back()->with([
                'error' => 'You are not authorized to archive this engagement.',
                'alert' => [
                    'type' => 'error',
                    'title' => 'Authorization Error',
                    'text' => "You don't have permission to archive this engagement.",
                ]
            ]);
        }

        $engagement->save();

        return back()->with([
            'success' => 'Engagement archived successfully.',
            'alert' => [
                'type' => 'success',
                'title' => 'Engagement archived successfully',
                'text' => "Engagement archived successfully",
            ]
        ]);
    }

    /**
     * Restore an archived engagement
     */
    public function restore(Request $request)
    {
        $engagement = JobEngagement::findOrFail($request->engagement_id);

        // Check authorization - user must be either the applicant or poster
        $user = Auth::user();
        $applicantId = $engagement->application->applicant_id;
        $posterId = $engagement->application->poster_id;

        // Determine which field to update based on the user's role
        if ($user->id === $applicantId) {
            $engagement->is_archived_by_applicant = false;
        } elseif ($user->id === $posterId) {
            $engagement->is_archived_by_poster = false;
        } else {
            return back()->with([
                'error' => 'You are not authorized to unarchive this engagement.',
                'alert' => [
                    'type' => 'error',
                    'title' => 'Authorization Error',
                    'text' => "You don't have permission to unarchive this engagement.",
                ]
            ]);
        }

        $engagement->save();

        return back()->with([
            'success' => 'Engagement unarchived successfully.',
            'alert' => [
                'type' => 'success',
                'title' => 'Engagement unarchived successfully',
                'text' => "Engagement unarchived successfully",
            ]
        ]);
    }

    /**
     * Engagement Details
     */
    public function show(JobEngagement $engagement)
    {
        // Load all necessary relationships
        $engagement->load([
            'application.job',
            'application.poster:id,name,email',
            'application.applicant:id,name,email',
            'deliverables',
            'cancellation.initiator:id,name',
            'cancellation.dispute.disputedBy:id,name',
            'cancellation.dispute.assignedAdmin:id,name',
            'cancellation.dispute.resolvedBy:id,name',
            'partialPayments.processor:id,name',
            'partialPayments.finalizer:id,name',
            'partialPayments.dispute'
        ]);

        // Check authorization (ensure user can view this engagement)
        $this->authorize('view', $engagement);

        return view('jobBoard.engagements.show', compact('engagement'));
    }

    /**
     * Process partial payment for cancelled engagement
     */
    // public function processPartialPayment(JobEngagement $engagement, Request $request)
    // {
    //     // Authorization check - only the client can process payments


    //     // Validate the request
    //     $validated = $request->validate([
    //         'payment_amount' => 'required|numeric|min:0',
    //         'payment_notes' => 'nullable|string',
    //     ]);

    //     DB::beginTransaction();
    //     try {
    //         // Record the partial payment
    //         $engagement->partialPayments()->create([
    //             'amount' => $validated['payment_amount'],
    //             'notes' => $validated['payment_notes'] ?? null,
    //             'processed_by' => Auth::id(),
    //             'processed_at' => now(),
    //         ]);

    //         // Here you would integrate with your payment processor
    //         // processPayment($engagement, $validated['payment_amount']);

    //         // Update the cancellation record if it exists
    //         if ($engagement->cancellation) {
    //             $engagement->cancellation->update([
    //                 'partial_payment_processed' => true,
    //                 'partial_payment_processed_at' => now(),
    //             ]);
    //         }

    //         DB::commit();

    //         // Notify the freelancer about the payment
    //         // $engagement->application->applicant->notify(new PaymentProcessed($engagement, $validated['payment_amount']));

    //         return redirect()->route('engagements.show', $engagement)
    //             ->with('success', 'Payment for partial work has been processed successfully.');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return back()->with('error', 'Failed to process payment: ' . $e->getMessage());
    //     }
    // }
}
