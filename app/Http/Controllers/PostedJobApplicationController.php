<?php

/**
 * PostedJobApplicationController
 *
 * The employer's side of the applicant/hiring relationship: browsing jobs
 * they've posted, reviewing applicants for a job, messaging applicants,
 * updating application status, confirming a hire, and archived-jobs handling.
 * Delegates business logic to specialized service classes for maintainability.
 */

namespace App\Http\Controllers;

use App\Helpers\FlashAlertHelper;
use App\Http\Requests\Application\BrowseApplicationsRequest;
use App\Http\Requests\Application\BrowsePostedJobsRequest;
use App\Http\Requests\Application\ConfirmHireRequest;
use App\Http\Requests\Application\SendMessageRequest;
use App\Http\Requests\Application\UpdateApplicationStatusRequest;
use App\Models\JobApplication;
use App\Models\ModelJob;
use App\Services\Applications\ApplicationBrowsingService;
use App\Services\Applications\ApplicationHiringService;
use App\Services\Applications\ApplicationMessagingService;
use App\Services\Jobs\JobManagementService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PostedJobApplicationController extends Controller
{
    use AuthorizesRequests;

    protected ApplicationBrowsingService $applicationBrowsingService;

    protected ApplicationHiringService $applicationHiringService;

    protected ApplicationMessagingService $applicationMessagingService;

    protected JobManagementService $jobManagementService;

    public function __construct(
        ApplicationBrowsingService $applicationBrowsingService,
        ApplicationHiringService $applicationHiringService,
        ApplicationMessagingService $applicationMessagingService,
        JobManagementService $jobManagementService
    ) {
        $this->applicationBrowsingService = $applicationBrowsingService;
        $this->applicationHiringService = $applicationHiringService;
        $this->applicationMessagingService = $applicationMessagingService;
        $this->jobManagementService = $jobManagementService;
    }

    // Display jobs the user has posted
    public function getUserPostedJobs(BrowsePostedJobsRequest $request)
    {
        $filters = [
            'status' => $request->getStatusFilter(),
            'sort' => $request->getSortOption(),
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
            'status' => $request->getStatusFilter(),
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
        $this->authorize('manage', $application);

        $data = $this->applicationBrowsingService->getApplicationDetails($application);

        return view('jobBoard.posted.applications.show', $data);
    }

    // Update application status
    public function updateStatus(UpdateApplicationStatusRequest $request, JobApplication $application)
    {
        $this->authorize('manage', $application);

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
        $this->authorize('manage', $application);

        $deliverables = $request->hasDeliverables() ? $request->getDeliverables() : [];

        $engagement = $this->applicationHiringService->confirmHire($application, $deliverables);

        return redirect()->back()->with(FlashAlertHelper::success('Hire confirmed and applicant notified'));
    }

    // Send message to applicant
    public function sendMessage(SendMessageRequest $request, JobApplication $application)
    {
        $this->authorize('manage', $application);

        $messageData = $request->getMessageData();
        $this->applicationMessagingService->sendMessage($application, $messageData);

        return redirect()->back()->with(FlashAlertHelper::success('Message sent successfully.'));
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
        if (! $this->jobManagementService->archiveJob($job)) {
            return $this->unauthorizedError();
        }

        return redirect()->back()->with(FlashAlertHelper::success('Job Archived!', 'Job archived successfully.'));
    }

    // Restore an archived job
    public function restoreJob(ModelJob $job)
    {
        if (! $this->jobManagementService->restoreJob($job)) {
            return $this->unauthorizedError();
        }

        return redirect()->back()->with(FlashAlertHelper::success('Job Restored!', 'Job restored successfully.'));
    }

    // View archived job details
    public function showArchivedJob(ModelJob $job)
    {
        if (! $this->jobManagementService->authorizeArchivedJobAccess($job)) {
            return $this->unauthorizedError();
        }

        // Load applications with their related applicant data
        $job->load([
            'applications' => function ($query) {
                $query->with('applicant');
            },
        ]);

        $job->loadCount('applications');

        return view('jobBoard.posted.showArchived', compact('job'));
    }

    // Handle unauthorized access error
    protected function unauthorizedError()
    {
        return redirect()->back()->with(FlashAlertHelper::error('Unauthorized Action'));
    }
}
