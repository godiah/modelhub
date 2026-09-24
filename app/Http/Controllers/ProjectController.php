<?php

namespace App\Http\Controllers;

use App\Enums\EngagementStatus;
use App\Models\JobEngagement;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $tab = $request->get('tab', 'all'); // Default to all tab

        // Base query for user's engagements
        $query = JobEngagement::with([
            'application.job',
            'application.applicant',
            'application.poster',
            'deliverables',
        ]);

        // Filter based on tab selection
        $engagements = $this->getEngagementsByTab($query, $tab, $user->id);

        // Get engagement statistics
        $stats = $this->getEngagementStats($user->id);

        // If it's an AJAX request, return only the partial view
        if ($request->ajax()) {
            return view('projects.partials.projects-list', compact('engagements', 'tab'))->render();
        }

        return view('projects.index', compact('engagements', 'stats', 'tab'));
    }

    private function getEngagementsByTab($query, $tab, $userId)
    {
        switch ($tab) {
            case 'all':
                return $query->activeForUser($userId)->get(); // All except archived
            case 'active':
                return $query->activeForUser($userId)->where('status', EngagementStatus::Active)->get();
            case 'pending':
                return $query->activeForUser($userId)->where('status', EngagementStatus::EmployerAccepted)->get();
            case 'completed':
                return $query->activeForUser($userId)->where('status', EngagementStatus::Completed)->get();
            case 'cancelled':
                return $query->activeForUser($userId)->where('status', EngagementStatus::Cancelled)->get();
            case 'disputed':
                return $query->activeForUser($userId)->where('status', EngagementStatus::Disputed)->get();
            case 'settled':
                return $query->activeForUser($userId)->where('status', EngagementStatus::Settled)->get();
            case 'archived':
                return $query->archivedForUser($userId)->get();
            default:
                return $query->activeForUser($userId)->get();
        }
    }

    private function getEngagementStats($userId)
    {
        // Get all active engagements for stats calculation
        $allActiveEngagements = JobEngagement::with('application')
            ->activeForUser($userId)
            ->get();

        return [
            'total' => $allActiveEngagements->count(),
            'active' => $allActiveEngagements->where('status', EngagementStatus::Active)->count(),
            'pending' => $allActiveEngagements->where('status', EngagementStatus::EmployerAccepted)->count(),
            'completed' => $allActiveEngagements->where('status', EngagementStatus::Completed)->count(),
            'cancelled' => $allActiveEngagements->where('status', EngagementStatus::Cancelled)->count(),
            'disputed' => $allActiveEngagements->where('status', EngagementStatus::Disputed)->count(),
            'settled' => $allActiveEngagements->where('status', EngagementStatus::Settled)->count(),
            'archived' => JobEngagement::archivedForUser($userId)->count(),
            'total_earnings' => JobEngagement::where('status', EngagementStatus::Completed)
                ->forApplicant($userId)
                ->sum('net_amount'),
        ];
    }
}
