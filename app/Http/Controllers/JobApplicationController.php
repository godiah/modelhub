<?php
/**
 * JobApplicationController
 * 
 * Manages job applications for both applicants and employers.
 * Handles application creation, status updates, hiring process, messaging, and archiving.
 * Delegates business logic to specialized service classes for maintainability.
 */

namespace App\Http\Controllers;

use App\Http\Requests\Application\BrowseApplicationsRequest;
use App\Http\Requests\Application\BrowsePostedJobsRequest;
use App\Http\Requests\Application\ConfirmHireRequest;
use App\Http\Requests\Application\SendMessageRequest;
use App\Http\Requests\Application\StoreApplicationRequest;
use App\Http\Requests\Application\UpdateApplicationStatusRequest;
use App\Models\JobApplication;
use App\Models\ModelJob;
use App\Services\Applications\ApplicationBrowsingService;
use App\Services\Applications\ApplicationHiringService;
use App\Services\Applications\ApplicationManagementService;
use App\Services\Applications\ApplicationMessagingService;
use App\Services\Jobs\JobManagementService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class JobApplicationController extends Controller
{
    use AuthorizesRequests;

    protected ApplicationManagementService $applicationManagementService;
    protected ApplicationBrowsingService $applicationBrowsingService;
    protected ApplicationHiringService $applicationHiringService;
    protected ApplicationMessagingService $applicationMessagingService;
    protected JobManagementService $jobManagementService;

    public function __construct(
        ApplicationManagementService $applicationManagementService,
        ApplicationBrowsingService $applicationBrowsingService,
        ApplicationHiringService $applicationHiringService,
        ApplicationMessagingService $applicationMessagingService,
        JobManagementService $jobManagementService
    ) {
        $this->applicationManagementService = $applicationManagementService;
        $this->applicationBrowsingService = $applicationBrowsingService;
        $this->applicationHiringService = $applicationHiringService;
        $this->applicationMessagingService = $applicationMessagingService;
        $this->jobManagementService = $jobManagementService;
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

        return redirect()->route($redirectRoute)->with([
            'success' => $message,
            'alert' => [
                'type' => 'success',
                'title' => $request->isDraft() ? 'Draft Saved!' : 'Application Submitted!',
                'text' => $message
            ]
        ]);
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
        if (!$this->applicationManagementService->deleteApplication($application)) {
            return redirect()->back()->with([
                'alert' => [
                    'type' => 'error',
                    'title' => 'Unauthorized Action',
                    'text' => 'You do not have permission to delete this application.',
                ]
            ]);
        }

        return redirect()->route('applications.my')->with([
            'success' => 'Application deleted successfully.',
            'alert' => [
                'type' => 'success',
                'title' => 'Deleted!',
                'text' => 'Your application has been removed.',
            ]
        ]);
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

        if (!$this->applicationManagementService->archiveApplication($application)) {
            return back()->with('error', 'This application cannot be archived at this time.');
        }

        return back()->with([
            'success' => 'Application archived successfully.',
            'alert' => [
                'type' => 'success',
                'title' => 'Application Archived!',
                'text' => 'Application archived successfully.'
            ]
        ]);
    }

    // Restore an archived application
    public function restore(JobApplication $application)
    {
        $this->authorize('update', $application);

        $this->applicationManagementService->restoreApplication($application);

        return back()->with([
            'success' => 'Application restored successfully.',
            'alert' => [
                'type' => 'success',
                'title' => 'Application Restored!',
                'text' => 'Application restored successfully.'
            ]
        ]);
    }

    // Delete an archived application
    public function destroy(JobApplication $application)
    {
        $this->authorize('delete', $application);

        if (!$this->applicationManagementService->deleteArchivedApplication($application)) {
            return back()->with('error', 'You can only delete archived applications.');
        }

        return back()->with([
            'success' => 'Application deleted successfully.',
            'alert' => [
                'type' => 'success',
                'title' => 'Application Deleted!',
                'text' => 'Application deleted successfully.'
            ]
        ]);
    }

    /**
     * User Posted Jobs Controller Methods
    */

    // Display jobs the user has posted
    public function getUserPostedJobs(BrowsePostedJobsRequest $request)
    {
        $filters = [
            'status' => $request->getStatusFilter(),
            'sort' => $request->getSortOption()
        ];

        $postedJobs = $this->jobManagementService->getUserPostedJobs($filters);
        $hasFilters = $request->hasActiveFilters();

        if ($request->isAjaxRequest()) {
            return view('jobBoard.posted.partials.jobs-grid', compact('postedJobs', 'hasFilters'))->render();
        }

        return view('jobBoard.posted.index', compact('postedJobs', 'hasFilters'));
    }

    // Display applications for a given job
    public function getJobApplications(BrowseApplicationsRequest $request, $slug)
    {
        $filters = [
            'search' => $request->getSearchTerm(),
            'status' => $request->getStatusFilter()
        ];

        $data = $this->applicationBrowsingService->getJobApplications($slug, $filters);

        if ($request->isAjaxRequest()) {
            return view('jobBoard.posted.applications.partials.applications-list', $data)->render();
        }

        return view('jobBoard.posted.applications.index', $data);
    }

    // View job application details
    public function showApplications(JobApplication $application)
    {
        $data = $this->applicationBrowsingService->getApplicationDetails($application);
        
        return view('jobBoard.posted.applications.show', $data);
    }

    // Update application status
    public function updateStatus(UpdateApplicationStatusRequest $request, JobApplication $application)
    {
        if (!$this->applicationHiringService->authorizeStatusUpdate($application)) {
            abort(403, 'Unauthorized action.');
        }

        // Check if hiring confirmation is required
        if ($this->applicationHiringService->requiresHireConfirmation($application, $request->status)) {
            return redirect()->back()
                ->with('show_hire_confirmation', true)
                ->withInput();
        }

        // Update the application status
        $updateData = $request->getUpdateData();
        $this->applicationHiringService->updateStatus($application, $updateData);

        return redirect()->back()->with('success', 'Application status updated successfully.');
    }

    // Confirm hire and create engagement
    public function confirmHire(ConfirmHireRequest $request, JobApplication $application)
    {
        $deliverables = $request->hasDeliverables() ? $request->getDeliverables() : [];
        
        $engagement = $this->applicationHiringService->confirmHire($application, $deliverables);

        return redirect()->back()->with([
            'success' => 'Hire confirmed and applicant notified',
            'alert' => [
                'type' => 'success',
                'title' => 'Hire confirmed and applicant notified',
                'text' => 'Hire confirmed and applicant notified',
            ]
        ]);
    }

    // Send message to applicant
    public function sendMessage(SendMessageRequest $request, JobApplication $application)
    {
        if (!$this->applicationMessagingService->authorizeMessageSending($application)) {
            abort(403, 'Unauthorized action.');
        }

        $messageData = $request->getMessageData();
        $this->applicationMessagingService->sendMessage($application, $messageData);

        return redirect()->back()->with([
            'success' => 'Message sent successfully.',
            'alert' => [
                'type' => 'success',
                'title' => 'Message sent successfully.',
                'text' => 'Message sent successfully.',
            ]
        ]);
    }

    // View Archived Posted Jobs
    public function archivedJobs()
    {
        $archivedJobs = $this->jobManagementService->getArchivedJobs();
        
        return view('jobBoard.posted.archived', compact('archivedJobs'));
    }

    // Archive a job
    public function archiveJob(ModelJob $job)
    {
        if (!$this->jobManagementService->archiveJob($job)) {
            return $this->unauthorizedError();
        }

        return redirect()->back()->with([
            'success' => 'Job has been archived successfully.',
            'alert' => [
                'type' => 'success',
                'title' => 'Job Archived!',
                'text' => 'Job archived successfully.'
            ]
        ]);
    }

    // Restore an archived job
    public function restoreJob(ModelJob $job)
    {
        if (!$this->jobManagementService->restoreJob($job)) {
            return $this->unauthorizedError();
        }

        return redirect()->back()->with([
            'success' => 'Job restored successfully.',
            'alert' => [
                'type' => 'success',
                'title' => 'Job Restored!',
                'text' => 'Job restored successfully.'
            ]
        ]);
    }

    // View archived job details
    public function showArchivedJob(ModelJob $job)
    {
        if (!$this->jobManagementService->authorizeArchivedJobAccess($job)) {
            return $this->unauthorizedError();
        }

        // Load applications with their related applicant data
        $job->load([
            'applications' => function ($query) {
                $query->with('applicant');
            }
        ]);

        $job->loadCount('applications');

        return view('jobBoard.posted.showArchived', compact('job'));
    }

    // Handle unauthorized access error
    protected function unauthorizedError()
    {
        return redirect()->back()->with([
            'error' => 'Unauthorized Action',
            'alert' => [
                'type' => 'error',
                'title' => 'Unauthorized Action',
                'text' => 'Unauthorized Action'
            ]
        ]);
    }
}
