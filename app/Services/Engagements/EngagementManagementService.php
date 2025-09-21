<?php

/**
 * EngagementManagementService
 * 
 * Handles core engagement operations including listing, archiving, and basic management.
 * Manages engagement queries, filters, and archive operations for users.
*/

namespace App\Services\Engagements;

use App\Helpers\Engagements\EngagementAuthorizationHelper;
use App\Models\JobApplication;
use App\Models\JobEngagement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class EngagementManagementService
{
    // Get user's engagements with filtering
    public function getUserEngagements(array $filters): \Illuminate\Pagination\LengthAwarePaginator
    {
        $user = Auth::user();
        
        $query = JobEngagement::with([
            'application.job',
            'application.poster',
            'application.applicant',
            'deliverables',
            'cancellation'
        ])
        ->whereHas('application', function ($q) use ($user) {
            $q->where('applicant_id', $user->id)
                ->orWhere('poster_id', $user->id);
        })
        ->activeForUser($user->id);

        // Apply search filter
        if (!empty($filters['search'])) {
            $this->applySearchFilter($query, $filters['search']);
        }

        // Apply status filter
        if ($filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        return $query->latest()->paginate(10)->withQueryString();
    }

    // Apply search filter to engagement query
    protected function applySearchFilter(Builder $query, string $search): void
    {
        $query->where(function ($q) use ($search) {
            $q->whereHas('application.job', fn($q2) =>
                $q2->where('title', 'like', "%{$search}%"))
                ->orWhereHas('application.applicant', fn($q2) =>
                $q2->where('name', 'like', "%{$search}%"))
                ->orWhereHas('application.poster', fn($q2) =>
                $q2->where('name', 'like', "%{$search}%"));
        });
    }

    // Check if user has archived engagements
    public function hasArchivedEngagements(): bool
    {
        $user = Auth::user();
        return JobEngagement::archivedForUser($user->id)->exists();
    }

    // Get engagement details with authorization check
    public function getEngagementDetails(JobEngagement $engagement): JobEngagement
    {
        $engagement->load([
            'application.job',
            'application.poster:id,name,email',
            'application.applicant:id,name,email',
            'deliverables',
            'cancellation.initiator:id,name',
            'cancellation.dispute.disputedBy:id,name',
            'cancellation.dispute.assignedAdmin:id,name',
            'cancellation.dispute.resolvedBy:id,name',
            'partialPayments.processor:id,name',
            'partialPayments.finalizer:id,name',
            'partialPayments.dispute'
        ]);

        return $engagement;
    }

    // Get response form data for engagement
    public function getResponseFormData(int $applicationId): array
    {
        $user = Auth::user();
        
        $application = JobApplication::with(['job', 'applicant', 'poster', 'engagement.deliverables'])
            ->where('id', $applicationId)
            ->where('applicant_id', $user->id)
            ->firstOrFail();

        $engagement = $application->engagement;

        if (!$engagement) {
            throw new \Exception('No engagement offer found for this application.');
        }

        return compact('application', 'engagement');
    }

    // Archive an engagement
    public function archiveEngagement(int $engagementId): bool
    {
        $engagement = JobEngagement::findOrFail($engagementId);
        $user = Auth::user();

        if (!EngagementAuthorizationHelper::canArchiveEngagement($engagement, $user)) {
            return false;
        }

        // Determine which field to update based on user role
        $applicantId = $engagement->application->applicant_id;
        $posterId = $engagement->application->poster_id;

        if ($user->id === $applicantId) {
            $engagement->is_archived_by_applicant = true;
        } elseif ($user->id === $posterId) {
            $engagement->is_archived_by_poster = true;
        } else {
            return false;
        }

        $engagement->save();
        return true;
    }

    // Restore an archived engagement
    public function restoreEngagement(int $engagementId): bool
    {
        $engagement = JobEngagement::findOrFail($engagementId);
        $user = Auth::user();

        if (!EngagementAuthorizationHelper::canArchiveEngagement($engagement, $user)) {
            return false;
        }

        // Determine which field to update based on user role
        $applicantId = $engagement->application->applicant_id;
        $posterId = $engagement->application->poster_id;

        if ($user->id === $applicantId) {
            $engagement->is_archived_by_applicant = false;
        } elseif ($user->id === $posterId) {
            $engagement->is_archived_by_poster = false;
        } else {
            return false;
        }

        $engagement->save();
        return true;
    }

    // Get archived engagements
    public function getArchivedEngagements(string $status = 'all'): \Illuminate\Pagination\LengthAwarePaginator
    {
        $user = Auth::user();
        
        $query = JobEngagement::archivedForUser($user->id)
            ->with(['application.job', 'application.applicant', 'application.poster']);

        // Apply status filter
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        return $query->latest()->paginate(7);
    }
}
