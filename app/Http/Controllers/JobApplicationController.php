<?php

/**
 * JobApplicationController
 *
 * The applicant's side of their own job applications: submitting, saving
 * drafts, viewing, archiving, restoring, and deleting. For the employer's
 * review/hiring queue, see PostedJobApplicationController.
 * Delegates business logic to specialized service classes for maintainability.
 */

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Helpers\FlashAlertHelper;
use App\Http\Requests\Application\BrowseApplicationsRequest;
use App\Http\Requests\Application\StoreApplicationRequest;
use App\Models\JobApplication;
use App\Models\ModelJob;
use App\Services\Applications\ApplicationManagementService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class JobApplicationController extends Controller
{
    use AuthorizesRequests;

    protected ApplicationManagementService $applicationManagementService;

    public function __construct(ApplicationManagementService $applicationManagementService)
    {
        $this->applicationManagementService = $applicationManagementService;
    }

    // Store a new job application (either as draft or submitted)
    public function store(StoreApplicationRequest $request)
    {
        $processedData = $request->getProcessedData();

        // Validate application constraints
        $validationResult = $this->applicationManagementService->validateApplicationConstraints($processedData);
        if ($validationResult) {
            return redirect()->back()->with($validationResult);
        }

        // Store the application
        $application = $this->applicationManagementService->store($request, $processedData);

        $message = $request->isDraft() ? 'Application saved as draft' : 'Application submitted successfully';
        $redirectRoute = $request->isDraft() ? 'applications.drafts' : 'applications.my';

        return redirect()->route($redirectRoute)->with(
            FlashAlertHelper::success($request->isDraft() ? 'Draft Saved!' : 'Application Submitted!', $message)
        );
    }

    // Load a draft application for editing
    public function continueDraft($slug)
    {
        $data = $this->applicationManagementService->getDraftApplication($slug);

        return view('jobBoard.applications.continue-draft', $data);
    }

    // Get all applications by the current user
    public function getUserApplications(BrowseApplicationsRequest $request)
    {
        $filters = $request->getActiveFilters();

        return view('jobBoard.applications.index', [
            'applications' => $this->applicationManagementService->getUserApplications($filters),
            'filters' => $filters,
            'counts' => $this->applicationManagementService->getApplicationCounts(),
        ]);
    }

    // Get user's draft applications
    public function getDraftApplications()
    {
        $drafts = $this->applicationManagementService->getDraftApplications();

        return view('jobBoard.applications.drafts', compact('drafts'));
    }

    // Delete a draft
    public function destroyDraft(JobApplication $application)
    {
        $this->authorize('update', $application);

        if (! $this->applicationManagementService->deleteDraft($application)) {
            return redirect()->route('applications.my')->with(
                FlashAlertHelper::error('Not a draft', 'Only drafts can be deleted here. Archive a finished application instead.')
            );
        }

        return redirect()->route('applications.drafts')->with(
            FlashAlertHelper::success('Draft deleted', 'Your draft has been removed.')
        );
    }

    // Display the specific job application details
    public function show(ModelJob $job)
    {
        $application = $this->applicationManagementService->getApplicationDetails($job);

        if ($application->status === ApplicationStatus::Draft) {
            return redirect()->route('applications.continue', $job->slug);
        }

        return view('jobBoard.applications.show', [
            'application' => $application,
            'clientMessages' => $this->applicationManagementService->getClientMessages($application),
        ]);
    }

    // View Archived Job Applications
    public function archived()
    {
        $applications = $this->applicationManagementService->getArchivedApplications();

        return view('jobBoard.applications.archived', compact('applications'));
    }

    // Archive an application
    public function archive(JobApplication $application)
    {
        $this->authorize('update', $application);

        if (! $this->applicationManagementService->archiveApplication($application)) {
            return back()->with(FlashAlertHelper::error('Cannot archive yet', 'An application can be archived once it is finished: rejected, withdrawn, filled by someone else, or its engagement has ended.'));
        }

        return back()->with(FlashAlertHelper::success('Application Archived!', 'Application archived successfully.'));
    }

    // Restore an archived application
    public function restore(JobApplication $application)
    {
        $this->authorize('update', $application);

        if (! $this->applicationManagementService->restoreApplication($application)) {
            return back()->with(FlashAlertHelper::error('Nothing to restore', 'This application is not archived.'));
        }

        return back()->with(FlashAlertHelper::success('Application Restored!', 'Application restored successfully.'));
    }

    // Delete an archived application
    public function destroy(JobApplication $application)
    {
        $this->authorize('delete', $application);

        if (! $this->applicationManagementService->deleteArchivedApplication($application)) {
            return back()->with(FlashAlertHelper::error('Cannot delete', 'Only archived applications can be deleted, and not one that led to an engagement.'));
        }

        return back()->with(FlashAlertHelper::success('Application Deleted!', 'Application deleted successfully.'));
    }
}
