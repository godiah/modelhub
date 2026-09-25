<?php

namespace App\Http\Controllers;

use App\Services\Projects\ProjectDashboardService;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function __construct(protected ProjectDashboardService $projectDashboardService) {}

    public function index(Request $request)
    {
        $userId = auth()->id();
        $tab = $request->get('tab', 'all'); // Default to all tab

        $engagements = $this->projectDashboardService->getEngagementsByTab($userId, $tab);

        // If it's an AJAX request, return only the partial view
        if ($request->ajax()) {
            return view('projects.partials.projects-list', compact('engagements', 'tab'))->render();
        }

        $stats = $this->projectDashboardService->getStats($userId);

        return view('projects.index', compact('engagements', 'stats', 'tab'));
    }
}
