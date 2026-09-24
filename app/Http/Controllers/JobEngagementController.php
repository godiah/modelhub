<?php

/**
 * JobEngagementController
 *
 * Manages job engagement lifecycle from offer response to completion.
 * Handles responses, reviews, cancellations, disputes, payments, and archiving.
 * Delegates business logic to specialized service classes for maintainability.
 */

namespace App\Http\Controllers;

use App\Helpers\FlashAlertHelper;
use App\Http\Requests\Engagement\ArchiveEngagementRequest;
use App\Http\Requests\Engagement\BrowseEngagementsRequest;
use App\Http\Requests\Engagement\CancelEngagementRequest;
use App\Http\Requests\Engagement\LeaveReviewRequest;
use App\Http\Requests\Engagement\RespondToOfferRequest;
use App\Http\Requests\Job\ReopenJobRequest;
use App\Models\JobEngagement;
use App\Services\Engagements\EngagementCancellationService;
use App\Services\Engagements\EngagementManagementService;
use App\Services\Engagements\EngagementPaymentService;
use App\Services\Engagements\EngagementResponseService;
use App\Services\Engagements\EngagementReviewService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class JobEngagementController extends Controller
{
    use AuthorizesRequests;

    protected EngagementManagementService $engagementManagementService;

    protected EngagementResponseService $engagementResponseService;

    protected EngagementReviewService $engagementReviewService;

    protected EngagementCancellationService $engagementCancellationService;

    protected EngagementPaymentService $engagementPaymentService;

    public function __construct(
        EngagementManagementService $engagementManagementService,
        EngagementResponseService $engagementResponseService,
        EngagementReviewService $engagementReviewService,
        EngagementCancellationService $engagementCancellationService,
        EngagementPaymentService $engagementPaymentService
    ) {
        $this->engagementManagementService = $engagementManagementService;
        $this->engagementResponseService = $engagementResponseService;
        $this->engagementReviewService = $engagementReviewService;
        $this->engagementCancellationService = $engagementCancellationService;
        $this->engagementPaymentService = $engagementPaymentService;
    }

    // Display a listing of the user's job engagements
    public function index(BrowseEngagementsRequest $request)
    {
        $filters = [
            'search' => $request->getSearchTerm(),
            'status' => $request->getStatusFilter(),
        ];

        $engagements = $this->engagementManagementService->getUserEngagements($filters);
        $hasFilters = $request->hasActiveFilters();
        $hasArchivedEngagements = $this->engagementManagementService->hasArchivedEngagements();

        if ($request->isAjaxRequest()) {
            return view('jobBoard.engagements.partials.engagements-list', [
                'engagements' => $engagements,
                'hasFilters' => $hasFilters,
                'hasArchivedEngagements' => $hasArchivedEngagements,
            ])->render();
        }

        return view('jobBoard.engagements.index', compact('engagements', 'hasFilters', 'hasArchivedEngagements'));
    }

    // Show response form for the job engagement
    public function showResponseForm($applicationId)
    {
        try {
            $data = $this->engagementManagementService->getResponseFormData($applicationId);

            return view('jobBoard.engagements.respond', $data);
        } catch (\Exception $e) {
            return redirect()->route('engagements.index')->with('error', $e->getMessage());
        }
    }

    // Response to the job engagement offer form
    public function respondToOffer(RespondToOfferRequest $request, $engagementId)
    {
        $engagement = JobEngagement::with(['application.job', 'application.poster'])->findOrFail($engagementId);

        try {
            $responseData = $request->getResponseData();
            $result = $this->engagementResponseService->respondToOffer($engagement, $responseData);

            return redirect()->route('engagements.index')->with(
                FlashAlertHelper::make($result['alert_type'], $result['message'], $result['alert_text'])
            );
        } catch (\Exception $e) {
            return redirect()->route('engagements.index')->with('error', $e->getMessage());
        }
    }

    // Leave a review for a completed job
    public function leaveReview(JobEngagement $engagement, LeaveReviewRequest $request)
    {
        $reviewData = $request->getReviewData();
        $result = $this->engagementReviewService->submitReview($engagement, $reviewData);

        if ($result['success']) {
            return redirect()->route('engagements.index', $engagement)->with([
                'success' => 'Review submitted successfully',
                'alert' => $result['alert'],
            ]);
        } else {
            return redirect()->back()->with($result);
        }
    }

    // Show the cancellation form
    public function showCancellationForm(JobEngagement $engagement)
    {
        return view('engagements.cancel', compact('engagement'));
    }

    // Process engagement cancellation
    public function cancelEngagement(JobEngagement $engagement, CancelEngagementRequest $request)
    {
        $cancellationData = $request->getCancellationData();
        $result = $this->engagementCancellationService->cancelEngagement($engagement, $cancellationData);

        if ($result['success']) {
            return redirect()->route('engagements.index', $engagement)->with([
                'success' => $result['message'],
                'alert' => $result['alert'],
            ]);
        } else {
            return back()->with('error', $result['error']);
        }
    }

    // Display a cancelled engagement
    public function showCancelledEngagement($id)
    {
        try {
            $data = $this->engagementCancellationService->getCancelledEngagementDetails($id);
            $engagement = $data['engagement'];

            // Get payment information
            $paymentInfo = $this->engagementPaymentService->getPaymentInfo($engagement);
            $latestPayment = $this->engagementPaymentService->getLatestPayment($engagement);

            return view('jobBoard.engagements.cancelled-engagements', [
                'engagement' => $engagement,
                'paymentInfo' => $paymentInfo,
                'canProcess' => $paymentInfo['can_process'],
                'payment' => $latestPayment,
            ]);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    // Display a disputed engagement
    public function showDisputedEngagement($id)
    {
        try {
            $data = $this->engagementCancellationService->getDisputedEngagementDetails($id);

            return view('jobBoard.engagements.disputed-engagements', $data);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    // Reopen a job after cancellation
    public function reopenJob(JobEngagement $engagement, ReopenJobRequest $request)
    {
        $result = $this->engagementCancellationService->reopenJob($engagement);

        if ($result['success']) {
            return redirect()->route('engagements.index')->with([
                'success' => $result['message'],
                'alert' => $result['alert'],
            ]);
        } else {
            return back()->with('error', $result['error']);
        }
    }

    // Display a listing of archived engagements
    public function archivedEngagements(Request $request)
    {
        try {
            $status = $request->get('status', 'all');
            $archivedEngagements = $this->engagementManagementService->getArchivedEngagements($status);

            // Return JSON for AJAX requests
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'html' => view('jobBoard.engagements.partials.archived-list', compact('archivedEngagements'))->render(),
                    'total' => $archivedEngagements->total(),
                    'pagination' => $archivedEngagements->links()->render(),
                ]);
            }

            return view('jobBoard.engagements.archived', compact('archivedEngagements'));
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to load engagements',
                    'message' => app()->environment('local') ? $e->getMessage() : 'Server error',
                ], 500);
            }

            throw $e;
        }
    }

    // Archive an engagement
    public function archive(ArchiveEngagementRequest $request)
    {
        $engagementId = $request->getEngagementId();

        if ($this->engagementManagementService->archiveEngagement($engagementId)) {
            return back()->with(FlashAlertHelper::success('Engagement archived successfully'));
        } else {
            return back()->with(
                FlashAlertHelper::error('Authorization Error', "You don't have permission to archive this engagement.")
            );
        }
    }

    // Restore an archived engagement
    public function restore(ArchiveEngagementRequest $request)
    {
        $engagementId = $request->getEngagementId();

        if ($this->engagementManagementService->restoreEngagement($engagementId)) {
            return back()->with(FlashAlertHelper::success('Engagement unarchived successfully'));
        } else {
            return back()->with(
                FlashAlertHelper::error('Authorization Error', "You don't have permission to unarchive this engagement.")
            );
        }
    }

    // Engagement Details
    public function show(JobEngagement $engagement)
    {
        // Check authorization (ensure user can view this engagement)
        $this->authorize('view', $engagement);

        $engagement = $this->engagementManagementService->getEngagementDetails($engagement);

        return view('jobBoard.engagements.show', compact('engagement'));
    }
}
