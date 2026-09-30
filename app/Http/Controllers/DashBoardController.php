<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\DashboardOverviewService;
use Illuminate\Support\Facades\Auth;

class DashBoardController extends Controller
{
    public function __construct(protected DashboardOverviewService $dashboardOverviewService) {}

    public function index()
    {
        return view('dashboard.index', [
            'overview' => $this->dashboardOverviewService->overview(Auth::user()),
        ]);
    }
}
