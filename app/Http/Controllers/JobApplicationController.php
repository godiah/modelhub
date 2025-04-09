<?php

namespace App\Http\Controllers;

use App\Mail\ApplicationMessage;
use App\Models\ApplicantMessage;
use App\Models\JobApplication;
use App\Models\ModelJob;
use App\Models\Skill;
use App\Models\Software;
use App\Notifications\NewApplicationMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class JobApplicationController extends Controller
{
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
            // $validationRules['proposal'] = 'required|string|max:2500';
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

        if (!$isDraft) {
            $existingSubmittedApplication = JobApplication::where('job_id', $request->job_id)
                ->where('applicant_id', $request->applicant_id)
                ->where('status', 'submitted')
                ->exists();

            if ($existingSubmittedApplication) {
                return redirect()->back()->with([
                    'info' => 'You have already submitted an application for this job',
                    'alert' => [
                        'type' => 'info',
                        'title' => 'Your Application Already Exists',
                        'text' => 'You have already submitted an application for this job. Your previous application is still pending review.'
                    ]
                ]);
            }
        }

        // Calculate service fee and net amount (only if offer is provided)
        $offerAmount = $request->offer ?? 0;
        $serviceFee = $offerAmount * self::SERVICE_FEE_PERCENTAGE;
        $netAmount = $offerAmount - $serviceFee;

        // Check if the user has already applied to this job (draft or submitted)
        $existingApplication = JobApplication::where('job_id', $request->job_id)
            ->where('applicant_id', $request->applicant_id)
            ->first();

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

        if ($existingApplication) {
            // Update existing application
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
    public function getUserApplications()
    {
        $applications = JobApplication::with('job')
            ->where('applicant_id', Auth::id())
            ->where('status', '!=', 'draft')
            ->latest()
            ->paginate(2);

        return view('jobBoard.applications.index', compact('applications'));
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
    public function destroy($id)
    {
        $application = JobApplication::findOrFail($id);

        // Check if the user is authorized to delete this application
        if ($application->applicant_id != Auth::id()) {
            return response()->json(['message' => 'Unauthorized action'], 403);
        }

        // Delete portfolio files if any
        if (!empty($application->portfolio)) {
            foreach ($application->portfolio as $file) {
                Storage::disk('public')->delete($file);
            }
        }

        $application->delete();

        return redirect()->route('applications.drafts')->with('success', 'Application deleted successfully');
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
     * My Posted Jobs Routes
     */

    /**
     * 1.
     * Display jobs the user has posted.
     */
    public function getUserPostedJobs()
    {
        // Get jobs posted by the authenticated user
        $postedJobs = ModelJob::where('user_id', Auth::id())->paginate(2);

        return view('jobBoard.posted.index', compact('postedJobs'));
    }

    /**
     * 2.
     * Display applications for a  job.
     */
    public function getJobApplications($slug)
    {
        // Check if the job belongs to the authenticated user
        $job = ModelJob::where('slug', $slug)
            ->where('user_id', Auth::id())
            ->with('applications.applicant') // Eager load applications and applicants
            ->firstOrFail();

        return view('jobBoard.posted.applications.index', [
            'job' => $job,
            'applications' => $job->applications
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

        return view('jobBoard.posted.applications.show', [
            'application' => $application,
            'job' => $application->job
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
            'status' => 'required|in:submitted,reviewed,interviewing,hired,rejected',
            'notes' => 'nullable|string|max:1000',
        ]);

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
}
