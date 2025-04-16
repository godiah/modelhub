<?php

namespace App\Http\Controllers;

use App\Models\JobDeliverable;
use App\Models\JobEngagement;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class JobDeliverableController extends Controller
{
    /**
     * Store a newly created deliverable in storage.
     */
    public function store(Request $request, JobEngagement $engagement)
    {
        // Check if the user has permission to add a deliverable (only job poster can add)
        if (Auth::id() !== $engagement->poster->id) {
            return $this->respondWithError(
                'Only the job poster can add deliverables.',
                'Permission Denied',
                'Only the job poster can add deliverables to this engagement.'
            );
        }

        // Check if the engagement is in a state where deliverables can be added
        if (in_array($engagement->status, ['cancelled', 'completed'])) {
            return $this->respondWithError(
                'Deliverables cannot be added to this engagement in its current state.',
                'Invalid Operation',
                'This engagement is no longer accepting new deliverables.'
            );
        }

        try {
            // Validate the request data
            $validated = $request->validate([
                'del_title' => 'required|string|max:255',
                'del_description' => 'nullable|string',
                'due_date' => 'nullable|date|after:today',
            ]);

            // Create the deliverable
            $deliverable = $engagement->deliverables()->create([
                'title' => $validated['del_title'],
                'description' => $validated['del_description'] ?? null,
                'due_date' => $validated['due_date'] ?? null,
                'status' => 'pending',
            ]);

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
    public function update(Request $request, JobDeliverable $deliverable)
    {
        // Validate the request data
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'due_date' => 'sometimes|nullable|date|after_or_equal:today',
        ]);

        // Check if the user has permission to edit the deliverable
        $engagement = $deliverable->engagement;

        // Ensure user is either the employer/poster or the applicant of this job
        if (Auth::id() !== $engagement->poster->id && Auth::id() !== $engagement->applicant->id) {
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
            // Only update the fields that were provided
            $updates = [];

            if (isset($validated['title'])) {
                $updates['title'] = $validated['title'];
            }

            if (isset($validated['description'])) {
                $updates['description'] = $validated['description'];
            }

            if (isset($validated['due_date'])) {
                $updates['due_date'] = $validated['due_date'];
            }

            // Update the deliverable with only the provided fields
            $deliverable->update($updates);

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
        // Check if the user has permission to delete the deliverable
        $engagement = $deliverable->engagement;

        // Ensure user is the employer/poster of this job
        if (Auth::id() !== $engagement->poster->id) {
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
            // Delete the deliverable
            $deliverable->delete();

            return back()->with([
                'success' => 'Deliverable has been removed successfully.',
                'alert' => [
                    'type' => 'success',
                    'title' => 'Deliverable has been removed successfully.',
                    'text' => 'Deliverable has been removed successfully.',
                ]
            ]);
        } catch (\Exception $e) {
            return $this->respondWithError(
                'Something went wrong. Please try again.',
                'Error',
                'Something went wrong. Please try again.'
            );
        }
    }

    /**
     * Approve a deliverable
     */
    public function approve(Request $request, JobDeliverable $deliverable)
    {
        // Validate request
        $validated = $request->validate([
            'feedback' => 'nullable|string|max:1000',
        ]);

        // Check if the user has permission to approve the deliverable
        $engagement = $deliverable->engagement;

        // Ensure user is the employer/poster of this job
        if (Auth::id() !== $engagement->poster->id) {
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
                'feedback' => $validated['feedback'] ?? null,
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
    public function reject(Request $request, JobDeliverable $deliverable)
    {
        // Validate request
        $validated = $request->validate([
            'feedback' => 'required|string|max:1000',
        ]);

        // Check if the user has permission to reject the deliverable
        $engagement = $deliverable->engagement;

        // Ensure user is the employer/poster of this job
        if (Auth::id() !== $engagement->poster->id) {
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
            'feedback' => $validated['feedback'],
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
                'completed_at' => now()
            ]);
        }
    }


    /**
     * Helper function to create standardized responses
     */
    protected function respondWith(string $status, string $message, string $title, ?string $text = null): RedirectResponse
    {
        return redirect()->back()->with([
            $status => $message,
            'alert' => [
                'type' => $status,
                'title' => $title,
                'text' => $text ?? $message,
            ]
        ]);
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
