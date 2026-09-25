<?php

/**
 * ProjectDashboardService
 *
 * Powers the "My Projects" tab-filtered overview of a user's engagements.
 * A read-only lens over JobEngagement, separate from EngagementManagementService
 * (which drives the Engagements module's own paginated listing/archive workflow).
 */

namespace App\Services\Projects;

use App\Enums\EngagementStatus;
use App\Models\JobEngagement;
use Illuminate\Database\Eloquent\Collection;

class ProjectDashboardService
{
    public function getEngagementsByTab(int $userId, string $tab): Collection
    {
        $query = JobEngagement::with([
            'application.job',
            'application.applicant',
            'application.poster',
            'deliverables',
        ]);

        return match ($tab) {
            'archived' => $query->archivedForUser($userId)->get(),
            'active' => $query->activeForUser($userId)->where('status', EngagementStatus::Active)->get(),
            'pending' => $query->activeForUser($userId)->where('status', EngagementStatus::EmployerAccepted)->get(),
            'completed' => $query->activeForUser($userId)->where('status', EngagementStatus::Completed)->get(),
            'cancelled' => $query->activeForUser($userId)->where('status', EngagementStatus::Cancelled)->get(),
            'disputed' => $query->activeForUser($userId)->where('status', EngagementStatus::Disputed)->get(),
            'settled' => $query->activeForUser($userId)->where('status', EngagementStatus::Settled)->get(),
            default => $query->activeForUser($userId)->get(), // 'all' and any unrecognized tab
        };
    }

    public function getStats(int $userId): array
    {
        $activeEngagements = JobEngagement::with('application')->activeForUser($userId)->get();

        return [
            'total' => $activeEngagements->count(),
            'active' => $activeEngagements->where('status', EngagementStatus::Active)->count(),
            'pending' => $activeEngagements->where('status', EngagementStatus::EmployerAccepted)->count(),
            'completed' => $activeEngagements->where('status', EngagementStatus::Completed)->count(),
            'cancelled' => $activeEngagements->where('status', EngagementStatus::Cancelled)->count(),
            'disputed' => $activeEngagements->where('status', EngagementStatus::Disputed)->count(),
            'settled' => $activeEngagements->where('status', EngagementStatus::Settled)->count(),
            'archived' => JobEngagement::archivedForUser($userId)->count(),
            'total_earnings' => JobEngagement::where('status', EngagementStatus::Completed)
                ->forApplicant($userId)
                ->sum('net_amount'),
        ];
    }
}
