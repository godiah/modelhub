<?php

namespace App\Http\Controllers;

use App\Events\EngagementResponseEvent;
use App\Models\JobApplication;
use App\Models\JobEngagement;
use App\Notifications\EngagementResponseNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JobEngagementController extends Controller
{
    /**
     * Display a listing of the user's job engagements.
     */
    public function index()
    {
        $user = Auth::user();

        // Get engagements where the user is either the applicant or the poster
        $engagements = JobEngagement::with(['application.job', 'application.poster', 'application.applicant', 'deliverables'])
            ->whereHas('application', function ($query) use ($user) {
                $query->where('applicant_id', $user->id)
                    ->orWhere('poster_id', $user->id);
            })
            ->latest()
            ->get();

        return view('jobBoard.engagements.index', compact('engagements'));
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

        // Send email notification if needed
        // Mail::to($jobPoster->email)
        //    ->queue(new EngagementResponseMail($engagement, $request->response, $request->notes));

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
}
