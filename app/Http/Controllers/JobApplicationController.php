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
        $activeFilters = $request->getActiveFilters();
        $applications = $this->applicationManagementService->getUserApplications($activeFilters);
        $draftCount = $this->applicationManagementService->getDraftCount();

        if ($request->isAjaxRequest()) {
            return view('jobBoard.applications.partials.applications-list',
                compact('applications', 'activeFilters', 'draftCount'))->render();
        }

        return view('jobBoard.applications.index', compact('applications', 'activeFilters', 'draftCount'));
    }

    // Get user's draft applications
    public function getDraftApplications()
    {
        $drafts = $this->applicationManagementService->getDraftApplications();

        return view('jobBoard.applications.drafts', compact('drafts'));
    }

    // Delete an application or draft
    public function destroyDraft(JobApplication $application)
    {
        if (! $this->applicationManagementService->deleteApplication($application)) {
            return redirect()->back()->with(
                FlashAlertHelper::error('Unauthorized Action', 'You do not have permission to delete this application.')
            );
        }

        return redirect()->route('applications.my')->with(
            FlashAlertHelper::success('Deleted!', 'Your application has been removed.')
        );
    }

    // Display the specific job application details
    public function show(ModelJob $job)
    {
        $application = $this->applicationManagementService->getApplicationDetails($job);

        return view('jobBoard.applications.show', compact('application'));
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
            return back()->with('error', 'This application cannot be archived at this time.');
        }

        return back()->with(FlashAlertHelper::success('Application Archived!', 'Application archived successfully.'));
    }

    // Restore an archived application
    public function restore(JobApplication $application)
    {
        $this->authorize('update', $application);

        $this->applicationManagementService->restoreApplication($application);

        return back()->with(FlashAlertHelper::success('Application Restored!', 'Application restored successfully.'));
    }

    // Delete an archived application
    public function destroy(JobApplication $application)
    {
        $this->authorize('delete', $application);

        if (! $this->applicationManagementService->deleteArchivedApplication($application)) {
            return back()->with('error', 'You can only delete archived applications.');
        }

        return back()->with(FlashAlertHelper::success('Application Deleted!', 'Application deleted successfully.'));
    }
}
