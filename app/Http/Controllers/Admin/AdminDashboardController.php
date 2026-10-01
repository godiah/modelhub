<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\StaffDashboardService;
use Illuminate\Http\Request;

/** The staff portal's home. What it shows follows the signed-in person's permissions (see StaffDashboardService). */
class AdminDashboardController extends Controller
{
    public function __invoke(Request $request, StaffDashboardService $dashboard)
    {
        return view('admin.dashboard', $dashboard->for($request->user()));
    }
}
