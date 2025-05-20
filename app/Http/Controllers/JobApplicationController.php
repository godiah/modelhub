<?php

namespace App\Http\Controllers;

use App\Events\NewHireEvent;
use App\Mail\ApplicationHired;
use App\Mail\ApplicationMessage;
use App\Models\ApplicantMessage;
use App\Models\JobApplication;
use App\Models\JobDeliverable;
use App\Models\JobEngagement;
use App\Models\JobReview;
use App\Models\ModelJob;
use App\Models\Skill;
use App\Models\Software;
use App\Notifications\HiredNotification;
use App\Notifications\NewApplicationMessage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class JobApplicationController extends Controller
{
    use AuthorizesRequests;

    // Service fee percentage as a constant
    const SERVICE_FEE_PERCENTAGE = 0.10; // 10%

    /**
     * Store a new job application (either as draft or submitted)
     */
    public function store(Request $request)
    {
        // Determine the action (draft or submit)
        $isDraft = $request->input('action') === 'draft';
        $status = $isDraft ? 'draft' : 'submitted';

        // Set validation rules based on action
        $validationRules = [
            'job_id' => 'required|exists:model_jobs,id',
            'poster_id' => 'required|exists:users,id',
            'applicant_id' => 'required|exists:users,id',
            'portfolio.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240', // 10MB max
        ];

        // Add stricter validation for submission (not for draft)
        if (!$isDraft) {
            $validationRules['offer'] = 'required|numeric|min:1';
            $validationRules['terms'] = 'required|accepted';
        } else {
            // For drafts, make offer optional
            $validationRules['offer'] = 'nullable|numeric|min:1';
            $validationRules['proposal'] = 'nullable|string|max:2500';
        }

        // Validate the request
        $validatedData = $request->validate($validationRules);

        // Remove the action field since we don't need to store it
        $request->request->remove('action');

        // Check if the job exists and is active
        $job = ModelJob::findOrFail($request->job_id);
        if (!$job->is_active || !$job->isActive()) {
            return redirect()->back()
                ->withErrors(['message' => 'This job is no longer accepting new applications']);
        }

        // Check if the user is authorized to apply
        if ($request->applicant_id != Auth::id()) {
            return redirect()->back()->withErrors(['message' => 'Unauthorized action']);
        }

        // Check for existing applications (including soft deleted ones)
        $existingApplicationWithDeleted = JobApplication::withTrashed()
            ->where('job_id', $request->job_id)
            ->where('applicant_id', $request->applicant_id)
            ->first();

        // Check for existing active application (excluding soft deleted)
        $existingApplication = JobApplication::where('job_id', $request->job_id)
            ->where('applicant_id', $request->applicant_id)
            ->first();

        // Prevent creating drafts for previously submitted/processed applications
        if ($existingApplication && $isDraft) {
            // If we're trying to save as draft but an application already exists with certain statuses
            $prohibitedStatuses = ['submitted', 'reviewed', 'rejected', 'hired', 'withdrawn'];

            if (in_array($existingApplication->status, $prohibitedStatuses)) {
                return redirect()->back()->with([
                    'error' => 'Cannot create a draft for this application',
                    'alert' => [
                        'type' => 'error',
                        'title' => 'Action Not Allowed',
                        'text' => 'Your application has already been ' . $existingApplication->status . '. You cannot create a draft version of it.'
                    ]
                ]);
            }
        }

        // Prevent multiple draft applications for the same job
        if ($isDraft && $existingApplication && $existingApplication->status === 'draft') {
            return redirect()->back()->with([
                'info' => 'A draft application already exists for this job',
                'alert' => [
                    'type' => 'info',
                    'title' => 'Existing Draft',
                    'text' => 'You already have a draft application for this job. Please edit the existing draft or submit it.'
                ]
            ]);
        }

        // Prevent reapplying to a job after deletion
        if ($existingApplicationWithDeleted && $existingApplicationWithDeleted->deleted_at) {
            return redirect()->back()->with([
                'error' => 'You cannot reapply to this job after deleting your application',
                'alert' => [
                    'type' => 'error',
                    'title' => 'Reapplication Not Allowed',
                    'text' => 'You have previously deleted your application for this job and cannot apply again.'
                ]
            ]);
        }

        // Check for existing submitted application
        if (!$isDraft && $existingApplication && $existingApplication->status === 'submitted') {
            return redirect()->back()->with([
                'info' => 'You have already submitted an application for this job',
                'alert' => [
                    'type' => 'info',
                    'title' => 'Your Application Already Exists',
                    'text' => 'You have already submitted an application for this job. Your previous application is still pending review.'
                ]
            ]);
        }

        // Calculate service fee and net amount (only if offer is provided)
        $offerAmount = $request->offer ?? 0;
        $serviceFee = $offerAmount * self::SERVICE_FEE_PERCENTAGE;
        $netAmount = $offerAmount - $serviceFee;

        // Start with existing portfolio files if any
        $portfolioFiles = [];

        // Add any existing files that weren't removed
        if ($request->has('existing_portfolio')) {
            $portfolioFiles = $request->input('existing_portfolio', []);
        } elseif ($existingApplication && !empty($existingApplication->portfolio)) {
            $portfolioFiles = $existingApplication->portfolio;
        }

        // Remove any files that were marked for removal
        if ($request->has('removed_files')) {
            $removedFiles = $request->input('removed_files', []);

            // Remove the files from storage
            foreach ($removedFiles as $file) {
                Storage::disk('public')->delete($file);
            }

            // Remove the files from our list
            $portfolioFiles = array_diff($portfolioFiles, $removedFiles);
        }

        // Process new portfolio files if any
        if ($request->hasFile('portfolio')) {
            Log::info('Number of files: ' . count($request->file('portfolio')));
            // Process new uploads
            foreach ($request->file('portfolio') as $index => $file) {
                Log::info("Processing file {$index}: " . $file->getClientOriginalName() . " - Type: " . $file->getMimeType());
                // Create a unique filename using index and microtime to avoid collisions
                $uniquePrefix = microtime(true) . '_' . $index . '_';
                $fileName = $uniquePrefix . $file->getClientOriginalName();
                $filePath = $file->storeAs('portfolios', $fileName, 'public');
                $portfolioFiles[] = $filePath;
            }
        }

        // Reindex the array (in case there were removals)
        $portfolioFiles = array_values($portfolioFiles);

        // Data to save
        $applicationData = [
            'offer_amount' => $offerAmount,
            'service_fee' => $serviceFee,
            'net_amount' => $netAmount,
            'proposal' => $request->proposal,
            'portfolio' => $portfolioFiles,
            'status' => $status,
        ];

        // Only set terms_accepted if provided (required for submission, optional for draft)
        if ($request->has('terms')) {
            $applicationData['terms_accepted'] = true;
        } elseif (!$isDraft) {
            // For submissions, terms must be accepted
            $applicationData['terms_accepted'] = true;
        }

        // Only allow updating existing applications if they are drafts
        if ($existingApplication && $existingApplication->status === 'draft') {
            // Update existing draft application
            $existingApplication->update($applicationData);
            $application = $existingApplication;
        } else {
            // Create a new application with all required fields
            $applicationData = array_merge($applicationData, [
                'job_id' => $request->job_id,
                'applicant_id' => $request->applicant_id,
                'poster_id' => $request->poster_id,
            ]);

            $application = JobApplication::create($applicationData);
        }

        $message = $isDraft ? 'Application saved as draft' : 'Application submitted successfully';
        $redirectRoute = $isDraft ? 'applications.drafts' : 'applications.my';

        // Flash data for SweetAlert
        return redirect()->route($redirectRoute)->with([
            'success' => $message,
            'alert' => [
                'type' => 'success',
                'title' => $isDraft ? 'Draft Saved!' : 'Application Submitted!',
                'text' => $message
            ]
        ]);
    }

    /**
     * Load a draft application for editing
     */
    public function continueDraft($slug)
    {
        // Get the job by slug
        $job = ModelJob::where('slug', $slug)->firstOrFail();

        // Find the draft application for this job and current user
        $application = JobApplication::where('job_id', $job->id)
            ->where('applicant_id', Auth::id())
            ->where('status', 'draft')
            ->firstOrFail();

        // Pass the data to the view
        return view('jobBoard.applications.continue-draft', compact('application', 'job'));
    }

    /**
     * Get all applications by the current user
     */
    public function getUserApplications(Request $request)
    {
        $query = JobApplication::with('job', 'engagement', 'jobEngagements')
            ->where('applicant_id', Auth::id())
            ->where('status', '!=', 'draft')
            ->active();

        $activeFilters = [
            'status' => $request->filled('status') && $request->status !== 'all'
                ? $request->status
                : 'all',
            'sort'   => $request->filled('sort')
                ? $request->sort
                : 'date_desc',
        ];

        if ($activeFilters['status'] !== 'all') {
            $query->where('status', $activeFilters['status']);
        }

        switch ($activeFilters['sort']) {
            case 'date_asc':
                $query->orderBy('created_at', 'asc');
                break;
            case 'status':
                $query->orderBy('status', 'asc');
                break;
            case 'date_desc':
            default:
                $query->latest();
        }

        $applications = $query->paginate(7)->withQueryString();

        // Check if user has any draft applications
        $draftCount = JobApplication::draft()
            ->where('applicant_id', Auth::id())
            ->count();

        if ($request->ajax()) {
            return view('jobBoard.applications.partials.applications-list', compact('applications', 'activeFilters', 'draftCount'))
                ->render();
        }

        return view('jobBoard.applications.index', compact('applications', 'activeFilters', 'draftCount'));
    }


    /**
     * Get user's draft applications
     */
    public function getDraftApplications()
    {
        $drafts = JobApplication::with('job')
            ->where('applicant_id', Auth::id())
            ->where('status', 'draft')
            ->latest()
            ->paginate(10);

        return view('jobBoard.applications.drafts', compact('drafts'));
    }

    /**
     * Delete an application or draft
     */
    public function destroyDraft(JobApplication $application)
    {
        // 1) Authorization
        if ($application->applicant_id !== Auth::id()) {
            return redirect()->back()->with([
                'alert' => [
                    'type'  => 'error',
                    'title' => 'Unauthorized Action',
                    'text'  => 'You do not have permission to delete this application.',
                ]
            ]);
        }

        // 2) Remove any portfolio files from disk
        if (!empty($application->portfolio) && is_array($application->portfolio)) {
            foreach ($application->portfolio as $filePath) {
                Storage::disk('public')->delete($filePath);
            }
        }

        // 3) Delete the application record
        $application->delete();

        // 4) Redirect back (or to a named route) with SweetAlert data
        return redirect()->route('applications.my')->with([
            'success' => 'Application deleted successfully.',
            'alert'   => [
                'type'  => 'success',
                'title' => 'Deleted!',
                'text'  => 'Your application has been removed.',
            ]
        ]);
    }

    /**
     * Display the specific job application details     *
     */
    public function show(ModelJob $job)
    {
        // Get the authenticated user
        $user = Auth::user();

        // Find the application for this job and user
        $application = JobApplication::with(['job', 'applicant', 'poster'])
            ->where('job_id', $job->id)
            ->where('applicant_id', $user->id)
            ->firstOrFail();
        return view('jobBoard.applications.show', compact('application'));
    }

    /**
     * View Archived Job Applications
     */
    public function archived()
    {
        $applications = JobApplication::with(['job', 'applicant', 'poster'])
            ->where('applicant_id', Auth::id())
            ->archived()
            ->latest()
            ->paginate(10);

        return view('jobBoard.applications.archived', compact('applications'));
    }

    /**
     * Archive an application
     */
    public function archive(JobApplication $application)
    {
        $this->authorize('update', $application);

        if (!$application->canBeArchived()) {
            return back()->with('error', 'This application cannot be archived at this time.');
        }

        $application->update(['is_archived' => true]);

        return back()->with([
            'success' => 'Application archived successfully.',
            'alert' => [
                'type' => 'success',
                'title' => 'Application Archived!',
                'text' => 'Application archived successfully.'
            ]
        ]);
    }

    public function restore(JobApplication $application)
    {
        $this->authorize('update', $application);

        $application->update(['is_archived' => false]);

        return back()->with([
            'success' => 'Application restored successfully.',
            'alert' => [
                'type' => 'success',
                'title' => 'Application Restored!',
                'text' => 'Application restored successfully.'
            ]
        ]);
    }

    public function destroy(JobApplication $application)
    {
        $this->authorize('delete', $application);

        if (!$application->is_archived) {
            return back()->with('error', 'You can only delete archived applications.');
        }

        $application->delete(); // Soft delete

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
     * My Posted Jobs Routes
     */

    /**
     * 1.
     * Display jobs the user has posted.
     */
    public function getUserPostedJobs(Request $request)
    {
        // Start with jobs posted by the authenticated user
        $query = ModelJob::where('user_id', Auth::id())
            ->Unarchived();

        // Apply status filter if selected
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('is_active', $request->status === 'active');
        }

        // Apply sorting
        if ($request->has('sort')) {
            switch ($request->sort) {
                case 'newest':
                    $query->orderBy('created_at', 'desc');
                    break;
                case 'deadline':
                    $query->orderBy('deadline', 'asc');
                    break;
                case 'budget_high':
                    $query->orderBy('budget', 'desc');
                    break;
                case 'budget_low':
                    $query->orderBy('budget', 'asc');
                    break;
                default:
                    $query->latest();
            }
        } else {
            $query->latest();
        }

        $postedJobs = $query->paginate(5)->withQueryString();

        // Check if filters are active
        $hasFilters = $request->has('status') && $request->status !== 'all' || $request->has('sort');

        // If this is an AJAX request, return only the jobs grid
        if ($request->ajax()) {
            return view('jobBoard.posted.partials.jobs-grid', compact('postedJobs', 'hasFilters'))->render();
        }

        return view('jobBoard.posted.index', compact('postedJobs', 'hasFilters'));
    }

    /**
     * 2.
     * Display applications for a  job.
     */
    public function getJobApplications(Request $request, $slug)
    {
        // Check if the job belongs to the authenticated user
        $job = ModelJob::where('slug', $slug)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // Build the base query
        $query = $job->applications()->with('applicant');

        // Search filter
        if ($request->has('search') && !empty($request->search)) {
            $query->whereHas('applicant', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        // Status filter
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Get paginated results
        $applications = $query->where('status', '!=', 'draft')->latest()->paginate(10)->withQueryString();

        // Check if filters are active
        $hasFilters = ($request->has('search') && !empty($request->search)) ||
            ($request->has('status') && $request->status !== 'all');

        if ($request->ajax()) {
            return view('jobBoard.posted.applications.partials.applications-list', [
                'applications' => $applications,
                'job' => $job,
                'hasFilters' => $hasFilters
            ])->render();
        }

        return view('jobBoard.posted.applications.index', [
            'job' => $job,
            'applications' => $applications,
            'hasFilters' => $hasFilters
        ]);
    }

    /**
     * 3.
     * View  job application details
     */
    public function showApplications(JobApplication $application)
    {
        // Ensure the current user is the owner of this job posting
        if (Auth::user()->id !== $application->job->user_id) {
            abort(403, 'Unauthorized action.');
        }

        // Get reviews for this applicant
        $reviews = JobReview::where('reviewee_id', $application->applicant_id)
            ->with(['reviewer', 'engagement.application.job'])
            ->public()
            ->latest()
            ->paginate(5);

        // Calculate stats
        $totalReviews = JobReview::where('reviewee_id', $application->applicant_id)->public()->count();
        $averageRating = JobReview::where('reviewee_id', $application->applicant_id)->public()->avg('rating') ?? 0;

        // Find top skill/tag
        $topSkill = null;
        if ($totalReviews > 0) {
            $allTags = JobReview::where('reviewee_id', $application->applicant_id)
                ->public()
                ->get()
                ->pluck('tags')
                ->flatten()
                ->filter();

            $tagCounts = collect();
            foreach ($allTags as $tag) {
                $tagCounts[$tag] = ($tagCounts[$tag] ?? 0) + 1;
            }

            if ($tagCounts->count() > 0) {
                $topSkill = $tagCounts->sortDesc()->keys()->first();
            }
        }

        // Default filter is 'all'
        $ratingFilter = 'all';

        return view('jobBoard.posted.applications.show', [
            'application' => $application,
            'job' => $application->job,
            'reviews' => $reviews,
            'totalReviews' => $totalReviews,
            'averageRating' => $averageRating,
            'topSkill' => $topSkill,
            'ratingFilter' => $ratingFilter
        ]);
    }


    /**
     * Update application status
     */
    public function updateStatus(Request $request, JobApplication $application)
    {
        // Ensure the current user is the owner of this job posting
        if (Auth::user()->id !== $application->job->user_id) {
            abort(403, 'Unauthorized action.');
        }

        // Validate the request
        $validated = $request->validate([
            'status' => 'required|in:submitted,reviewed,hired,rejected',
            'notes' => 'nullable|string|max:1000',
        ]);

        // If status is being changed to hired
        if ($validated['status'] === 'hired' && $application->status !== 'hired') {
            return redirect()->back()
                ->with('show_hire_confirmation', true)
                ->withInput();
        }

        // Update the application
        $application->status = $validated['status'];
        if (isset($validated['notes'])) {
            $application->additional_notes = $validated['notes'];
        }
        $application->save();

        // Optionally, notify the applicant about the status change

        return redirect()->back()->with('success', 'Application status updated successfully.');
    }

    /**
     * Confirm hire and create engagement
     */
    public function confirmHire(Request $request, JobApplication $application)
    {
        $request->validate([
            'deliverables' => 'sometimes|array',
            'deliverables.*.title' => 'required|string|max:255',
            'deliverables.*.description' => 'nullable|string',
            'deliverables.*.due_date' => 'nullable|date',
        ]);

        // Create engagement
        $engagement = JobEngagement::create([
            'application_id' => $application->id,
            'status' => 'employer_accepted',
            'agreed_amount' => $application->offer_amount,
            'service_fee' => $application->service_fee,
            'net_amount' => $application->net_amount,
            'employer_accepted_at' => now(),
        ]);

        // Create deliverables
        if ($request->has('deliverables')) {
            foreach ($request->deliverables as $deliverable) {
                JobDeliverable::create([
                    'engagement_id' => $engagement->id,
                    'title' => $deliverable['title'],
                    'description' => $deliverable['description'],
                    'due_date' => $deliverable['due_date'],
                    'status' => 'pending',
                ]);
            }
        }

        // Update application status
        $application->update(['status' => 'hired']);

        // Send email notification (queued) to applicant
        Mail::to($application->applicant->email)
            ->queue(new ApplicationHired($application, $engagement));

        // Create in-app notification
        $application->applicant->notify(new HiredNotification($application, $engagement));

        return redirect()->back()->with([
            'success' => 'Hire confirmed and applicant notified',
            'alert' => [
                'type' => 'success',
                'title' => 'Hire confirmed and applicant notified',
                'text' => 'Hire confirmed and applicant notified',
            ]
        ]);
    }

    /**
     * Send message to applicant
     */
    public function sendMessage(Request $request, JobApplication $application)
    {
        // Ensure the current user is the owner of this job posting
        if (Auth::user()->id !== $application->job->user_id) {
            abort(403, 'Unauthorized action.');
        }

        // Validate the request
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        // 1. Store the message in the database
        $message = ApplicantMessage::create([
            'job_application_id' => $application->id,
            'sender_id' => Auth::id(),
            'recipient_id' => $application->applicant_id,
            'subject' => $validated['subject'],
            'message' => $validated['message'],
        ]);

        // 2. Send email notification (queued)
        Mail::to($application->applicant->email)
            ->queue(new ApplicationMessage($message, $application));

        // 3. Create in-app notification
        $application->applicant->notify(new NewApplicationMessage($message));

        return redirect()->back()->with([
            'success' => 'Message sent successfully.',
            'alert' => [
                'type' => 'success',
                'title' => 'Message sent successfully.',
                'text' => 'Message sent successfully.',
            ]
        ]);
    }

    /**
     * View Archived Posted Jobs
     */
    public function archivedJobs()
    {
        $archivedJobs = ModelJob::where('user_id', Auth::id())
            ->archived()
            ->with(['applications', 'jobImages'])
            ->withCount('applications')
            ->latest()
            ->paginate(10);

        return view('jobBoard.posted.archived', compact('archivedJobs'));
    }

    /**
     * Archive a job
     */
    public function archiveJob(ModelJob $job)
    {
        if ($job->user_id !== Auth::id()) {
            return redirect()->back()->with([
                'error' => 'Unauthorized Action',
                'alert' => [
                    'type' => 'error',
                    'title' => 'Unauthorized Action',
                    'text' => 'Unauthorized Action'
                ]
            ]);
        }

        $job->archive();

        return redirect()->back()->with([
            'success' => 'Job has been archived successfully.',
            'alert' => [
                'type' => 'success',
                'title' => 'Job Archived!',
                'text' => 'Job archived successfully.'
            ]
        ]);
    }

    /**
     * Restore an archived job
     */
    public function restoreJob(ModelJob $job)
    {
        if ($job->user_id !== Auth::id()) {
            return redirect()->back()->with([
                'error' => 'Unauthorized Action',
                'alert' => [
                    'type' => 'error',
                    'title' => 'Unauthorized Action',
                    'text' => 'Unauthorized Action'
                ]
            ]);
        }

        $job->unarchive();

        return redirect()->back()->with([
            'success' => 'Job restored successfully.',
            'alert' => [
                'type' => 'success',
                'title' => 'Job Restored!',
                'text' => 'Job restored successfully.'
            ]
        ]);
    }

    /**
     * View archived job details
     */
    public function showArchivedJob(ModelJob $job)
    {
        // Check if the user is authorized to view this job
        if ($job->user_id !== Auth::id()) {
            return redirect()->back()->with([
                'error' => 'Unauthorized Action',
                'alert' => [
                    'type' => 'error',
                    'title' => 'Unauthorized Action',
                    'text' => 'Unauthorized Action'
                ]
            ]);
        }

        // Load applications with their related applicant data
        $job->load([
            'applications' => function ($query) {
                $query->with('applicant'); // Eager load applicant data for each application
            }
        ]);

        // Count the total applications
        $job->loadCount('applications');

        // Return the view with the job data
        return view('jobBoard.posted.showArchived', compact('job'));
    }
}
