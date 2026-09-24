<?php

namespace App\Http\Controllers;

use App\Enums\EngagementStatus;
use App\Helpers\Engagements\EngagementAuthorizationHelper;
use App\Helpers\FlashAlertHelper;
use App\Http\Requests\Deliverable\ApproveDeliverableRequest;
use App\Http\Requests\Deliverable\RejectDeliverableRequest;
use App\Http\Requests\Deliverable\StoreDeliverableRequest;
use App\Http\Requests\Deliverable\SubmitDeliverableRequest;
use App\Http\Requests\Deliverable\UpdateDeliverableRequest;
use App\Mail\DeliverableSubmitted;
use App\Models\JobDeliverable;
use App\Models\JobEngagement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class JobDeliverableController extends Controller
{
    /**
     * Store a newly created deliverable in storage.
     */
    public function store(StoreDeliverableRequest $request, JobEngagement $engagement)
    {
        // Check if the user has permission to add a deliverable (only job poster can add)
        if (! EngagementAuthorizationHelper::canManageDeliverables($engagement, Auth::user())) {
            return $this->respondWithError(
                'Only the job poster can add deliverables.',
                'Permission Denied',
                'Only the job poster can add deliverables to this engagement.'
            );
        }

        // Check if the engagement is in a state where deliverables can be added
        if (in_array($engagement->status, [EngagementStatus::Cancelled, EngagementStatus::Completed])) {
            return $this->respondWithError(
                'Deliverables cannot be added to this engagement in its current state.',
                'Invalid Operation',
                'This engagement is no longer accepting new deliverables.'
            );
        }

        try {
            $engagement->deliverables()->create($request->getDeliverableData());

            return $this->respondWithSuccess(
                'Deliverable has been added successfully!',
                'Deliverable Added',
                'The deliverable has been added successfully to this engagement.'
            );
        } catch (\Exception $e) {
            return $this->respondWithError(
                'Something went wrong. Please try again.',
                'Error',
                'Something went wrong while adding the deliverable. Please try again.'
            );
        }
    }

    /**
     * Update the specified deliverable.
     */
    public function update(UpdateDeliverableRequest $request, JobDeliverable $deliverable)
    {
        $engagement = $deliverable->engagement;

        // Ensure user is either the employer/poster or the applicant of this job
        if (! EngagementAuthorizationHelper::canEditDeliverable($engagement, Auth::user())) {
            return $this->respondWithError(
                'You do not have permission to edit this deliverable.',
                'Permission Denied',
                'You do not have permission to edit this deliverable.'
            );
        }

        // Check if deliverable is in a state that can be edited
        if (in_array($deliverable->status, ['approved', 'completed'])) {
            return $this->respondWithError(
                'This deliverable cannot be edited in its current state.',
                'Invalid Operation',
                'This deliverable cannot be edited in its current state.'
            );
        }

        try {
            // validated() already excludes fields absent from the request (via `sometimes`)
            $deliverable->update($request->validated());

            return $this->respondWithSuccess(
                'Deliverable updated successfully.',
                'Deliverable Updated',
                'Deliverable updated successfully.'
            );
        } catch (\Exception $e) {
            return $this->respondWithError(
                'Something went wrong. Please try again.',
                'Error',
                'Something went wrong. Please try again.'
            );
        }
    }

    /**
     * Remove the specified deliverable.
     */
    public function destroy(JobDeliverable $deliverable)
    {
        $engagement = $deliverable->engagement;

        // Ensure user is the employer/poster of this job
        if (! EngagementAuthorizationHelper::canManageDeliverables($engagement, Auth::user())) {
            return $this->respondWithError(
                'You do not have permission to edit this deliverable.',
                'Permission Denied',
                'You do not have permission to edit this deliverable.'
            );
        }

        // Check if deliverable is in a state that can be deleted
        if (in_array($deliverable->status, ['approved', 'completed'])) {
            return $this->respondWithError(
                'This deliverable cannot be deleted in its current state.',
                'Invalid Operation',
                'This deliverable cannot be deleted in its current state.'
            );
        }

        try {
            $deliverable->delete();

            return back()->with(FlashAlertHelper::success('Deliverable has been removed successfully.'));
        } catch (\Exception $e) {
            return $this->respondWithError(
                'Something went wrong. Please try again.',
                'Error',
                'Something went wrong. Please try again.'
            );
        }
    }

    /**
     * Submit deliverable files and notes
     */
    public function submit(SubmitDeliverableRequest $request, JobDeliverable $deliverable)
    {
        // Ensure user is the applicant/freelancer for this engagement
        if (! EngagementAuthorizationHelper::canSubmitDeliverable($deliverable->engagement, Auth::user())) {
            return $this->respondWithError(
                'You do not have permission to submit this deliverable.',
                'Permission Denied',
                'You do not have permission to submit this deliverable.'
            );
        }

        // Prevent submission if approved
        if ($deliverable->approved_at) {
            return redirect()->back()->withErrors(['message' => 'Approved deliverables cannot be modified.']);
        }

        // Check if the deliverable is rejected and allow resubmission
        if ($deliverable->rejected_at) {
            // Delete previously submitted files
            if (! empty($deliverable->submission_files)) {
                foreach ($deliverable->submission_files as $file) {
                    Storage::disk('local')->delete($file['path']);
                }
            }

            // Reset previous submission data
            $deliverable->update([
                'submission_files' => [],
                'submission_notes' => null,
                'rejected_at' => null,
                'feedback' => null,
            ]);
        }

        $submissionFiles = [];

        if ($request->hasFile('submission_files')) {
            foreach ($request->file('submission_files') as $file) {
                // Store all files in one main folder instead of per-deliverable subfolders
                $path = $file->store('deliverable-submissions', 'local');

                $submissionFiles[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'size' => $file->getSize(),
                    'mime' => $file->getMimeType(),
                ];
            }
        }

        // Update the deliverable
        $deliverable->update([
            'submission_files' => $submissionFiles,
            'submission_notes' => $request->submission_notes,
            'submitted_at' => now(),
            'status' => 'submitted',
            'rejected_at' => null, // Reset rejected_at if it was set
        ]);

        // Send notification email to the job poster
        if ($deliverable->engagement->job->user) {
            Mail::to($deliverable->engagement->job->user->email)
                ->queue(new DeliverableSubmitted($deliverable));
        }

        return $this->respondWithSuccess(
            'Deliverable Submitted.',
            'Deliverable Submitted Successfully',
            'The deliverable has been submitted successfully.'
        );
    }

    /**
     * Download a submitted deliverable file. Files live on the private disk —
     * only the engagement's poster, applicant, or an admin may download them.
     */
    public function downloadSubmissionFile(JobDeliverable $deliverable, int $index)
    {
        if (! EngagementAuthorizationHelper::canView($deliverable->engagement, Auth::user())) {
            abort(403);
        }

        $file = $deliverable->submission_files[$index] ?? null;

        if (! $file || ! Storage::disk('local')->exists($file['path'])) {
            abort(404);
        }

        return Storage::disk('local')->download($file['path'], $file['name']);
    }

    /**
     * Approve a deliverable
     */
    public function approve(ApproveDeliverableRequest $request, JobDeliverable $deliverable)
    {
        $engagement = $deliverable->engagement;

        // Ensure user is the employer/poster of this job
        if (! EngagementAuthorizationHelper::canManageDeliverables($engagement, Auth::user())) {
            return $this->respondWithError(
                'You do not have permission to edit this deliverable.',
                'Permission Denied',
                'You do not have permission to edit this deliverable.'
            );
        }

        // Check if deliverable is in a state that can be approved
        if ($deliverable->status !== 'submitted') {
            return $this->respondWithError(
                'This deliverable cannot be approved in its current state.',
                'Invalid Operation',
                'This deliverable cannot be approved in its current state.'
            );
        }

        // Use database transaction to ensure data integrity
        DB::beginTransaction();
        try {
            // Update the deliverable
            $deliverable->update([
                'status' => 'approved',
                'feedback' => $request->validated('feedback'),
                'approved_at' => now(),
                'rejected_at' => null, // Ensure it's not rejected
            ]);

            // Check if this is the final deliverable to be approved
            $this->checkEngagementCompletion($engagement);

            DB::commit();

            return $this->respondWithSuccess(
                'Deliverable has been approved.',
                'Deliverable Approved',
                'The deliverable has been approved successfully.'
            );
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->respondWithError(
                'Something went wrong. Please try again.',
                'Error',
                'Something went wrong. Please try again.'
            );
        }
    }

    /**
     * Reject a deliverable
     */
    public function reject(RejectDeliverableRequest $request, JobDeliverable $deliverable)
    {
        $engagement = $deliverable->engagement;

        // Ensure user is the employer/poster of this job
        if (! EngagementAuthorizationHelper::canManageDeliverables($engagement, Auth::user())) {
            return $this->respondWithError(
                'You do not have permission to edit this deliverable.',
                'Permission Denied',
                'You do not have permission to edit this deliverable.'
            );
        }

        // Check if deliverable is in a state that can be rejected
        if ($deliverable->status !== 'submitted') {
            return $this->respondWithError(
                'This deliverable cannot be rejected in its current state.',
                'Invalid Operation',
                'This deliverable cannot be rejected in its current state.'
            );
        }

        // Reject the deliverable
        $deliverable->update([
            'status' => 'rejected',
            'feedback' => $request->validated('feedback'),
            'rejected_at' => now(),
            'approved_at' => null, // Ensure it's not approved
        ]);

        return $this->respondWithSuccess(
            'Deliverable has been rejected. The freelancer will be notified.',
            'Deliverable has been rejected. The freelancer will be notified.',
            'Deliverable has been rejected. The freelancer will be notified.'
        );
    }

    /**
     * Check if all deliverables are approved and mark engagement as completed if so
     */
    private function checkEngagementCompletion(JobEngagement $engagement)
    {
        // Get counts of total and approved deliverables
        $totalDeliverables = $engagement->deliverables()->count();
        $approvedDeliverables = $engagement->deliverables()->where('status', 'approved')->count();

        // If all deliverables are approved, mark the engagement as completed
        if ($totalDeliverables > 0 && $totalDeliverables === $approvedDeliverables) {
            $engagement->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        }
    }

    /**
     * Helper function to create standardized responses
     */
    protected function respondWith(string $status, string $message, string $title, ?string $text = null): RedirectResponse
    {
        return redirect()->back()->with(FlashAlertHelper::make($status, $title, $text ?? $message));
    }

    /**
     * Helper for success responses
     */
    protected function respondWithSuccess(string $message, string $title, ?string $text = null): RedirectResponse
    {
        return $this->respondWith('success', $message, $title, $text);
    }

    /**
     * Helper for error responses
     */
    protected function respondWithError(string $message, string $title, ?string $text = null): RedirectResponse
    {
        return $this->respondWith('error', $message, $title, $text);
    }
}
