<?php

// JobController manages HTTP requests for job-related operations, including creating, updating, viewing, and browsing jobs.
// This controller integrates with JobManagementService and JobBrowsingService to handle business logic,
// validates requests using dedicated request classes, and manages user authorization and error responses.

namespace App\Http\Controllers;

use App\Helpers\FlashAlertHelper;
use App\Http\Requests\Job\BrowseJobsRequest;
use App\Http\Requests\Job\CheckTitleRequest;
use App\Http\Requests\Job\StoreJobRequest;
use App\Http\Requests\Job\UpdateJobRequest;
use App\Models\ModelJob;
use App\Services\Jobs\JobBrowsingService;
use App\Services\Jobs\JobManagementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

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
        Gate::authorize('update', $job);

        $job->load('jobImages');

        return view('jobBoard.jobs.edit', $this->jobManagementService->getEditJobData($job) + [
            'locked' => $job->hasEngagementInProgress(),
        ]);
    }

    // Update a job: authorisation and validation live in UpdateJobRequest
    public function update(UpdateJobRequest $request, ModelJob $job)
    {
        $this->jobManagementService->update($job, $request->getUpdateData(), $request, $request->getRemovedImageIds());

        return redirect()->route('jobs.show', $job->slug)
            ->with('success', 'Project details updated successfully');
    }

    // The poster's own project page. Everyone else is sent to the public page for the project.
    public function show(ModelJob $job)
    {
        if (! auth()->user()?->can('view', $job)) {
            return redirect()->route('jobs.apply', $job->slug);
        }

        $job->load(['jobImages', 'engagements']);

        return view('jobBoard.jobs.show', [
            'job' => $job,
            'publicUrl' => route('jobs.apply', $job->slug),
        ] + $this->jobManagementService->getPosterOverview($job));
    }

    // Render Markdown for the editor's preview tab, with the same sanitising the project pages use
    public function previewDescription(Request $request)
    {
        $validated = $request->validate(['description' => 'nullable|string|max:20000']);

        return response()->json([
            'html' => (string) Str::markdown((string) ($validated['description'] ?? ''), ['html_input' => 'strip', 'allow_unsafe_links' => false]),
        ]);
    }

    // List all active projects/jobs
    public function browseJobs(BrowseJobsRequest $request)
    {
        $filters = $request->getFilters();
        $jobs = $this->jobBrowsingService->browseJobs($filters, $request->user());
        $statuses = $this->jobBrowsingService->applicationStatuses($request->user(), $jobs);

        // If it's an AJAX request, return only the results partial
        if ($request->isAjaxRequest()) {
            return view('jobBoard.jobs.partials.jobs-list', compact('jobs', 'statuses'));
        }

        $filterOptions = $this->jobBrowsingService->getFilterOptions();

        return view('jobBoard.jobs.browse', [
            'jobs' => $jobs,
            'statuses' => $statuses,
            'filters' => $filters,
            'skills' => $filterOptions['skills'],
            'software' => $filterOptions['software'],
        ]);
    }

    // Apply for a job
    public function apply(ModelJob $job)
    {
        // Only projects that are open for applications have an apply page
        if (! $job->isOpenForApplications()) {
            return $this->jobClosedError();
        }

        $similarJobs = $this->jobBrowsingService->getSimilarJobs($job, auth()->user());

        $job->loadMissing(['user.profile', 'jobImages']);

        return view('jobBoard.jobs.apply', [
            'job' => $job,
            'similarJobs' => $similarJobs,
            'applicationStatus' => $this->jobBrowsingService->applicationStatuses(auth()->user(), [$job])[$job->id] ?? null,
        ]);
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
        return redirect()->back()->with(FlashAlertHelper::error('Unauthorized Action'));
    }

    // Handle job closed error
    protected function jobClosedError()
    {
        return redirect()->route('jobs.browse')->with(
            FlashAlertHelper::error('This job is no longer accepting new applications.')
        );
    }
}
