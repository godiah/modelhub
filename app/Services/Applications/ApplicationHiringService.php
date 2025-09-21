<?php

// Handle hiring process, engagement creation, deliverables

namespace App\Services\Applications;

use App\Mail\ApplicationHired;
use App\Models\JobApplication;
use App\Models\JobDeliverable;
use App\Models\JobEngagement;
use App\Notifications\HiredNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class ApplicationHiringService
{
    // Update application status
    public function updateStatus(JobApplication $application, array $updateData): bool
    {
        // Ensure the current user is the owner of this job posting
        if (Auth::user()->id !== $application->job->user_id) {
            return false;
        }

        // Update the application
        $application->update($updateData);

        return true;
    }

    // Check if status change requires hire confirmation
    public function requiresHireConfirmation(JobApplication $application, string $newStatus): bool
    {
        return $newStatus === 'hired' && $application->status !== 'hired';
    }

    // Confirm hire and create engagement
    public function confirmHire(JobApplication $application, array $deliverables = []): JobEngagement
    {
        // Create engagement
        $engagement = JobEngagement::create([
            'application_id' => $application->id,
            'status' => 'employer_accepted',
            'agreed_amount' => $application->offer_amount,
            'service_fee' => $application->service_fee,
            'net_amount' => $application->net_amount,
            'employer_accepted_at' => now(),
        ]);

        // Create deliverables if provided
        if (!empty($deliverables)) {
            $this->createDeliverables($engagement, $deliverables);
        }

        // Update application status
        $application->update(['status' => 'hired']);

        // Send notifications
        $this->sendHireNotifications($application, $engagement);

        return $engagement;
    }

    // Check authorization for status updates
    public function authorizeStatusUpdate(JobApplication $application): bool
    {
        return Auth::user()->id === $application->job->user_id;
    }

    // Create deliverables for an engagement
    protected function createDeliverables(JobEngagement $engagement, array $deliverables): void
    {
        foreach ($deliverables as $deliverable) {
            JobDeliverable::create([
                'engagement_id' => $engagement->id,
                'title' => $deliverable['title'],
                'description' => $deliverable['description'] ?? null,
                'due_date' => $deliverable['due_date'] ?? null,
                'status' => 'pending',
            ]);
        }
    }

    // Send hire notifications
    protected function sendHireNotifications(JobApplication $application, JobEngagement $engagement): void
    {
        // Send email notification (queued) to applicant
        Mail::to($application->applicant->email)
            ->queue(new ApplicationHired($application, $engagement));

        // Create in-app notification
        $application->applicant->notify(new HiredNotification($application, $engagement));
    }
}
