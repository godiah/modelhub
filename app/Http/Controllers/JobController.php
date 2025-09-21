<?php

// JobController manages HTTP requests for job-related operations, including creating, updating, viewing, and browsing jobs.
// This controller integrates with JobManagementService and JobBrowsingService to handle business logic,
// validates requests using dedicated request classes, and manages user authorization and error responses.

namespace App\Http\Controllers;

use App\Http\Requests\Job\BrowseJobsRequest;
use App\Http\Requests\Job\CheckTitleRequest;
use App\Http\Requests\Job\StoreJobRequest;
use App\Http\Requests\Job\UpdateJobRequest;
use App\Models\ModelJob;
use App\Services\Jobs\JobBrowsingService;
use App\Services\Jobs\JobManagementService;
use Illuminate\Http\Request;


class JobController extends Controller
{
    protected JobManagementService $jobManagementService;
    protected JobBrowsingService $jobBrowsingService;

    public function __construct(
        JobManagementService $jobManagementService, 
        JobBrowsingService $jobBrowsingService
    ) {
        $this->jobManagementService = $jobManagementService;
        $this->jobBrowsingService = $jobBrowsingService;
    }
    
    // Job Board Home Page
    public function index()
    {
        return view('jobBoard.jobs.index');
    }

    // Job Board New Job Page
    public function new()
    {
        $data = $this->jobManagementService->getNewJobData();
        
        return view('jobBoard.jobs.new', $data);
    }

    // Store a new job
    public function store(StoreJobRequest $request)
    {
        $validatedData = $request->getProcessedData();
        
        $job = $this->jobManagementService->store($request, $validatedData);

        return redirect()->route('jobs.show', $job->slug)
            ->with('success', 'Project posted successfully!');
    }

    // Return view to edit a job
    public function edit(ModelJob $job)
    {
        // Check if user has permission to edit this job
        // $this->authorize('update', $job);

        $data = $this->jobManagementService->getEditJobData($job);

        return view('jobBoard.jobs.edit', $data);
    }

    // Update job details
    public function update(UpdateJobRequest $request, ModelJob $job)
    {
        $updateData = $request->getUpdateData();
        
        $this->jobManagementService->update($job, $updateData);

        return redirect()->route('jobs.show', $job->slug)
            ->with('success', 'Project details updated successfully');
    }

    // Show a specific job
    public function show(ModelJob $job)
    {
        if (!$this->jobManagementService->authorizeJobView($job)) {
            return $this->unauthorizedError();
        }

        $jobUrl = url("/jobs/{$job->slug}");
        
        return view('jobBoard.jobs.show', compact('job', 'jobUrl'));
    }

    // List all active projects/jobs
    public function browseJobs(BrowseJobsRequest $request)
    {
        $filters = $request->getFilters();
        $jobs = $this->jobBrowsingService->browseJobs($filters);

        // If it's an AJAX request, return only the jobs list partial
        if ($request->isAjaxRequest()) {
            return view('jobBoard.jobs.partials.jobs-list', compact('jobs'));
        }

        return view('jobBoard.jobs.browse', compact('jobs', 'filters'));
    }

    // Apply for a job
    public function apply(ModelJob $job)
    {
        $similarJobs = $this->jobBrowsingService->getSimilarJobs($job);

        // If job is not active, return error
        if ($similarJobs->isEmpty() && (!$job->is_active || !$job->isActive())) {
            return $this->jobClosedError();
        }

        return view('jobBoard.jobs.apply', compact('job', 'similarJobs'));
    }

    // Check if project title exists
    public function checkTitle(CheckTitleRequest $request)
    {
        $title = $request->getTitle();
        $exists = $this->jobManagementService->titleExists($title);

        return response()->json(['exists' => $exists]);
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

    // Handle job closed error
    protected function jobClosedError()
    {
        return redirect()->route('jobs.browse')->with([
            'error' => 'This job is no longer accepting new applications',
            'alert' => [
                'type' => 'error',
                'title' => 'This job is no longer accepting new applications.',
                'text' => 'This job is no longer accepting new applications.',
                'icon' => 'error'
            ]
        ]);
    }
}
